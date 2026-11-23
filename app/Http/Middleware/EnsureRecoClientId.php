<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class EnsureRecoClientId
{
    public function handle(Request $request, Closure $next)
    {
        $cookieName = 'reco_id';

        // 1) Si hay sesión activa, ignoramos la cookie completamente
        if (Auth::check()) {
            $request->attributes->set('reco_id', null);
            // Pasamos al controlador SIN tocar ni enviar cookie
            return $next($request);
        }

        // 2) Usuario anónimo: mantenemos la lógica de cookie
        $id = $request->cookie($cookieName);
        if (! $id) {
            $id = (string) Str::uuid();
        }
        $request->attributes->set('reco_id', $id);

        // Procesa la petición
        $response = $next($request);

        // Si no había cookie previa, la enviamos al cliente
        if (! $request->cookie($cookieName)) {
            $response->headers->setCookie(
                cookie(
                    $cookieName,
                    $id,
                    60 * 24 * 365, // 1 año
                    '/',           // path
                    null,          // domain
                    false,         // secure
                    true,          // httpOnly
                    false,         // raw
                    'Lax'          // sameSite
                )
            );
        }

        return $response;
    }
}
