<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GoogleBooksService
{
    public function buscarLibro($titulo, $autor)
    {
        $apiKey = env('GOOGLE_BOOKS_API_KEY');
        $query = "intitle:" . urlencode($titulo) . "+inauthor:" . urlencode($autor);

        $url = "https://www.googleapis.com/books/v1/volumes?q={$query}&key={$apiKey}";

        $response = Http::get($url);

        if ($response->successful() && isset($response['items'][0]['volumeInfo'])) {
            $info = $response['items'][0]['volumeInfo'];
            $access = $response['items'][0]['accessInfo'] ?? [];

            return [
                'titulo' => $info['title'] ?? null,
                'autor' => $info['authors'][0] ?? null,
                'descripcion' => $info['description'] ?? null,
                'calificacion' => $info['averageRating'] ?? null,
                'paginaFuente' => 'Google Books',
                'paginas' => $info['pageCount'] ?? null,
                'portada' => $info['imageLinks']['thumbnail'] ?? null,
                'previewLink' => $access['previewLink'] ?? null,
                'webReaderLink' => $access['webReaderLink'] ?? null,
                'viewability' => $access['viewability'] ?? null,
                'epubDisponible' => $access['epub']['isAvailable'] ?? false,
                'pdfDisponible' => $access['pdf']['isAvailable'] ?? false,
            ];
        }

        return null;
    }

    public function buscarLibroCompleto(string $titulo, string $autor): ?array
    {
        $apiKey = env('GOOGLE_BOOKS_API_KEY');
        $query  = 'intitle:' . urlencode($titulo) . '+inauthor:' . urlencode($autor);

        $response = Http::get('https://www.googleapis.com/books/v1/volumes', [
            'q'          => $query,
            'key'        => $apiKey,
            'maxResults' => 1,
        ]);

        if (! $response->successful()) {
            return null;
        }

        $json = $response->json();
        if (empty($json['items'][0])) {
            return null;
        }

        // Devolvemos el objeto completo del ítem (incluye volumeInfo, accessInfo, etc)
        return $json['items'][0];
    }
}

/*
{
  "titulo": "Sapiens. De animales a dioses",
  "autor": "Yuval Noah Harari",
  "descripcion": "Texto extenso generado desde Google Books con sinopsis o contraportada del libro...",
  "calificacion": 4,
  "paginaFuente": "Google Books",
  "paginas": 520,
  "portada": "http://books.google.com/books/content?id=qmj7AwAAQBAJ&printsec=frontcover&img=1&zoom=1&edge=curl&source=gbs_api"
}
*/ 