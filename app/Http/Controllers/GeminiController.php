<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GeminiController extends Controller
{
    public function generarSinopsis(Request $request)
    {
        $titulo = $request->input('titulo');
        $autor = $request->input('autor');

        $prompt = "Genera una sinopsis breve en español para el libro titulado \"$titulo\", escrito por $autor.";

        $response = Http::withHeaders([
            'Content-Type' => 'application/json'
        ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-pro:generateContent?key=" . config('services.gemini.key'), [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ]
        ]);

        if ($response->successful()) {
            $sinopsis = $response->json('candidates.0.content.parts.0.text');
            return response()->json(['sinopsis' => $sinopsis]);
        }

        return response()->json([
            'error' => 'No se pudo obtener la sinopsis',
            'details' => $response->json()
        ], 500);
    }
}
