<?php
namespace App\Http\Controllers\api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SinopsisController extends Controller
{
    public function generar(Request $request)
    {
        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'autor'  => 'required|string|max:255',
        ]);

        $titulo = $validated['titulo'];
        $autor  = $validated['autor'];

        $system = "Eres un redactor editorial. Escribe sinopsis breves (80–100 palabras), claras, sin spoilers, en español neutro.";
        $user   = "Genera una sinopsis atractiva para el libro titulado \"{$titulo}\" escrito por {$autor}. Devuelve solo la sinopsis en un único párrafo.";

        $key = config('services.openai.key', env('OPENAI_API_KEY'));

        // -------- función helper para llamar Responses API --------
        $call = function(array $payload) use ($key) {
            return Http::timeout(20)
                ->withToken($key)
                ->post('https://api.openai.com/v1/responses', $payload);
        };

        // -------- payload base (GPT-5 nano) --------
        $payload = [
            'model' => 'gpt-5-nano',
            'input' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user',   'content' => $user],
            ],
            // Clave: minimiza reasoning para que deje tokens a la salida
            'reasoning' => ['effort' => 'minimal'],
            // Sube el límite de salida
            'max_output_tokens' => 500,
            // Solo texto, sin formatos raros
            'text' => ['format' => ['type' => 'text'], 'verbosity' => 'low'],
        ];

        try {
            // 1) Primer intento
            $response = $call($payload);
            $json = $response->json();

            // Si el modelo no existe en tu cuenta, fallback a 4o-mini
            if ($response->failed()) {
                $err = strtolower((string) data_get($json, 'error.message', ''));
                Log::warning('OpenAI primary call failed', ['status' => $response->status(), 'body' => $json]);

                if (str_contains($err, 'does not exist') || $response->status() === 404) {
                    $payload['model'] = 'gpt-4o-mini';
                    unset($payload['reasoning']); // 4o-mini no necesita esto
                    $payload['max_output_tokens'] = 300;
                    $response = $call($payload);
                    $json = $response->json();
                }
            }

            // 2) Si viene "incomplete / max_output_tokens", reintenta con más tokens
            $isIncompleteMax = (data_get($json, 'status') === 'incomplete')
                && (data_get($json, 'incomplete_details.reason') === 'max_output_tokens');

            if (!$response->failed() && $isIncompleteMax) {
                Log::info('Retry: incomplete due to max_output_tokens; increasing budget');
                $payload['max_output_tokens'] = 700; // más margen
                // mantener 'reasoning' minimal
                $response = $call($payload);
                $json = $response->json();
            }

            // 3) Si sigue fallando, probar con gpt-5-mini (permite temperature)
            $isStillIncomplete = (data_get($json, 'status') === 'incomplete')
                && (data_get($json, 'incomplete_details.reason') === 'max_output_tokens');

            if ((!$response->ok()) || $isStillIncomplete) {
                Log::warning('Switching to gpt-5-mini as final fallback');
                $payloadMini = [
                    'model' => 'gpt-5-mini',
                    'input' => $payload['input'],
                    // mini suele emitir texto sin tanto razonamiento
                    'max_output_tokens' => 300,
                    // Podemos ajustar temperatura en mini
                    'temperature' => 0.7,
                    'text' => ['format' => ['type' => 'text'], 'verbosity' => 'low'],
                ];
                $response = $call($payloadMini);
                $json = $response->json();
            }

            if ($response->failed()) {
                Log::error('OpenAI error (final)', ['status' => $response->status(), 'body' => $json]);
                if (app()->environment('local')) {
                    return response()->json([
                        'error'  => 'OpenAI',
                        'status' => $response->status(),
                        'body'   => $json,
                    ], 502);
                }
                return response()->json(['error' => 'Error al generar sinopsis'], 502);
            }

            // ------- Parseo robusto -------
            $sinopsis = data_get($json, 'output_text');

            if (!is_string($sinopsis) || trim($sinopsis) === '') {
                $items = data_get($json, 'output', []);
                if (is_array($items)) {
                    foreach ($items as $item) {
                        if (data_get($item, 'type') === 'message') {
                            foreach ((array) data_get($item, 'content', []) as $part) {
                                $txt = data_get($part, 'text');
                                if (is_string($txt) && trim($txt) !== '') {
                                    $sinopsis = $txt;
                                    break 2;
                                }
                            }
                        }
                    }
                }
            }

            if (!is_string($sinopsis) || trim($sinopsis) === '') {
                $sinopsis = data_get($json, 'choices.0.message.content', '');
            }

            if (!is_string($sinopsis) || trim($sinopsis) === '') {
                if (app()->environment('local')) {
                    return response()->json([
                        'error'    => 'Sin contenido textual en la respuesta',
                        'response' => $json,
                    ], 502);
                }
                $sinopsis = 'No se pudo generar la sinopsis en este momento.';
            }

            // Limpieza final
            $sinopsis = trim($sinopsis);
            return response()->json(['sinopsis' => $sinopsis]);

        } catch (\Throwable $e) {
            Log::error('OpenAI exception', ['ex' => $e->getMessage()]);
            return response()->json(['error' => 'Servicio no disponible'], 503);
        }
    }
}
