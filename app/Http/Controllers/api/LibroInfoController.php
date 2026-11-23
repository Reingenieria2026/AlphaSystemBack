<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\GoogleBooksService;

class LibroInfoController extends Controller
{
    public function obtenerInfo(Request $request, GoogleBooksService $service)
{
    $validated = $request->validate([
        'titulo' => 'required|string',
        'autor'  => 'required|string'
    ]);

    try {
        $info = $service->buscarLibro($validated['titulo'], $validated['autor']);
    } catch (\Exception $e) {
        \Log::error('Error Google Books: '.$e->getMessage());
        return response()->json([
            'error' => 'Error al consultar la API de Google Books'
        ], 500);
    }

    if (empty($info)) {
        return response()->json(['error' => 'Libro no encontrado'], 404);
    }

    return response()->json($info, 200);
}

public function infoCompleto(Request $request, GoogleBooksService $service)
    {
        // 1) Validación básica
        $validated = $request->validate([
            'titulo' => 'required|string',
            'autor'  => 'required|string',
        ]);

        try {
            // 2) Llamamos al servicio que devuelve TODO el item de Google Books
            $item = $service->buscarLibroCompleto(
                $validated['titulo'],
                $validated['autor']
            );
        } catch (\Exception $e) {
            Log::error('Error al consultar Google Books: '.$e->getMessage());
            return response()->json([
                'ok'    => false,
                'error' => 'Error interno al consultar Google Books'
            ], 500);
        }

        // 3) Si no hay resultado
        if (! $item) {
            return response()->json([
                'ok'   => true,
                'data' => null
            ], 200);
        }

        // 4) Devolvemos todo el objeto
        return response()->json([
            'ok'   => true,
            'data' => $item
        ], 200);
    }

}
