<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\Usuario;

class UsuarioController extends Controller
{
    public function index()
    {
        return response()->json(['estatus' => 1, 'data' => Usuario::all()]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombreUsr'   => 'required|string',
            'apellidoP'   => 'required|string',
            'apellidoM'   => 'nullable|string',
            'fotoUsr'     => 'nullable|image|max:10240',
            'calle'       => 'nullable|string',
            'colonia'     => 'nullable|string',
            'idCiudad'    => 'required|exists:ciudad,idCiudad',
            'telefonoUsr' => 'nullable|string',
            'correoUsr'   => 'required|email|unique:usuario,correoUsr',
            'contrasena'  => 'required|string|min:6',
            'idTipoUsr'   => 'required|exists:tipo_usuario,idTipoUsr',
            'idEstadoUsr' => 'required|exists:estado_usuario,idEstadoUsr',
        ]);

        if ($validator->fails()) {
            return response()->json(['estatus' => 0, 'mensaje' => $validator->errors()]);
        }

        $data = $request->all();

        // El mutador en el modelo hashea automáticamente
        $data['contrasena'] = $data['contrasena'];

        if ($request->hasFile('fotoUsr')) {
            $ruta = $request->file('fotoUsr')->store('usuarios', 'public');
            $data['fotoUsr'] = $ruta;
        }

        $usuario = Usuario::create($data);

        return response()->json(['estatus' => 1, 'mensaje' => 'Usuario creado', 'data' => $usuario]);
    }

    public function show($id)
    {
        $usuario = Usuario::find($id);
        return $usuario
            ? response()->json(['estatus' => 1, 'data' => $usuario])
            : response()->json(['estatus' => 0, 'mensaje' => 'Usuario no encontrado']);
    }

    public function update(Request $request, $id)
    {
        Log::info("UPDATE /usuarios/{$id} - INPUT:", $request->all());

        $validator = Validator::make($request->all(), [
            'nombreUsr'   => 'required|string',
            'apellidoP'   => 'required|string',
            'apellidoM'   => 'nullable|string',
            'fotoUsr'     => 'nullable|image|max:10240',
            'calle'       => 'nullable|string',
            'colonia'     => 'nullable|string',
            'idCiudad'    => 'sometimes|nullable|exists:ciudad,idCiudad',
            'telefonoUsr' => 'nullable|string',
            'correoUsr'   => "required|email|unique:usuario,correoUsr,{$id},idUsr",
            'contrasena'  => 'sometimes|nullable|string|min:6',
            'idTipoUsr'   => 'required|exists:tipo_usuario,idTipoUsr',
            'idEstadoUsr' => 'required|exists:estado_usuario,idEstadoUsr',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'estatus' => 0,
                'mensaje' => $validator->errors(),
            ]);
        }
    
        $usuario = Usuario::findOrFail($id);
        $datos = $request->except(['contrasena']);

        // Asignar contraseña si viene
        if ($request->filled('contrasena')) {
            $datos['contrasena'] = $request->contrasena; // mutador la hashea
        }

        if ($request->hasFile('fotoUsr')) {
            if ($usuario->fotoUsr && Storage::disk('public')->exists($usuario->fotoUsr)) {
                Storage::disk('public')->delete($usuario->fotoUsr);
            }
            $datos['fotoUsr'] = $request->file('fotoUsr')->store('usuarios', 'public');
        } elseif ($request->has('fotoUsr') && $request->input('fotoUsr') === '') {
            if ($usuario->fotoUsr && Storage::disk('public')->exists($usuario->fotoUsr)) {
                Storage::disk('public')->delete($usuario->fotoUsr);
            }
            $datos['fotoUsr'] = null;
        }

        if (! $request->has('idCiudad')) {
            $datos['idCiudad'] = 1; // por defecto
        }

        $usuario->update($datos);

        return response()->json([
            'estatus' => 1,
            'mensaje' => 'Usuario actualizado'
        ]);
    }

    public function destroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:usuario,idUsr'
        ]);

        if ($validator->fails()) {
            return response()->json(['estatus' => 0, 'mensaje' => $validator->errors()]);
        }

        $usuario = Usuario::find($request->id);
        if ($usuario->fotoUsr && Storage::disk('public')->exists($usuario->fotoUsr)) {
            Storage::disk('public')->delete($usuario->fotoUsr);
        }

        Usuario::destroy($request->id);
        return response()->json(['estatus' => 1, 'mensaje' => 'Usuario eliminado']);
    }

    public function getByCorreo($correo)
    {
        $u = Usuario::where('correoUsr', $correo)->first();
        return $u
            ? response()->json(['estatus' => 1, 'data' => $u])
            : response()->json(['estatus' => 0, 'mensaje' => 'Usuario no encontrado']);
    }

    public function indexNames()
    {
        $users = Usuario::where('idEstadoUsr', 1)
            ->select('idUsr','nombreUsr','apellidoP','fotoUsr')
            ->get();
        return response()
            ->json(['estatus' => 1, 'data' => $users]);
    }
}