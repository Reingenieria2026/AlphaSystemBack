<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cookie;

class AuthController extends Controller
{
    private const ESTADO_ACTIVO     = 1;
    private const ESTADO_INACTIVO   = 2;
    private const ESTADO_SUSPENDIDO = 3;
    private const ESTADO_ELIMINADO  = 4;
    private const LIMITE_INTENTOS   = 10;

    // LOGIN con suspensión tras 10 intentos
    public function login(Request $request)
    {
        $request->validate([
            'correoUsr'  => 'required|email',
            'contrasena' => 'required|string',
        ]);

        $usuario = Usuario::where('correoUsr', $request->correoUsr)->first();

        if (!$usuario) {
            return response()->json(['message' => 'Credenciales inválidas'], 401);
        }

        // Si no está ACTIVO, no puede entrar
        if ((int)$usuario->idEstadoUsr !== self::ESTADO_ACTIVO) {
            return response()->json([
                'message' => 'Usuario inactivo, suspendido o eliminado. Acude a tu biblioteca para reactivar el acceso.'
            ], 403);
        }

        // Validar contraseña
        if (!Hash::check($request->contrasena, $usuario->contrasena)) {
            $usuario->intentos_fallidos = ($usuario->intentos_fallidos ?? 0) + 1;

            // Si llegó al límite → Suspender
            if ($usuario->intentos_fallidos >= self::LIMITE_INTENTOS) {
                $usuario->idEstadoUsr = self::ESTADO_SUSPENDIDO;
                $usuario->save();

                return response()->json([
                    'message' => 'Cuenta suspendida por intentos fallidos. Solicita reactivación en tu biblioteca.',
                    'suspendido' => true
                ], 403);
            }

            $usuario->save();

            return response()->json([
                'message' => 'Credenciales inválidas',
                'intentos_restantes' => max(0, self::LIMITE_INTENTOS - $usuario->intentos_fallidos)
            ], 401);
        }

        // Login correcto → reinicia intentos
        $usuario->intentos_fallidos = 0;
        $usuario->save();

        $token = $usuario->createToken('token-admin')->plainTextToken;
        $newRecoId = (string) Str::uuid();

        return response()
            ->json([
                'message' => 'Login exitoso',
                'token'   => $token,
                'usuario' => $usuario
            ])
            ->cookie('reco_id', $newRecoId, 60 * 24 * 30);
    }

    // LOGOUT
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        Cookie::queue(Cookie::forget('reco_id'));

        return response()->json(['message' => 'Sesión cerrada']);
    }

    // REACTIVAR CUENTA (solo bibliotecario/admin) → solo cambia contraseña
    public function reactivarCuenta(Request $request, $id)
    {
        $request->validate([
            'nueva_contrasena' => 'required|string|min:6'
        ]);

        $bibliotecario = $request->user();
        if (!$bibliotecario || (int)($bibliotecario->idTipoUsr ?? 0) !== 1) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $usuario = Usuario::findOrFail($id);

        // Cambiar contraseña (mutador la hashea)
        $usuario->contrasena = $request->nueva_contrasena;

       
        $usuario->save();

        return response()->json([
            'message' => 'Contraseña actualizada por el bibliotecario',
            'usuario' => $usuario
        ]);
    }
}
