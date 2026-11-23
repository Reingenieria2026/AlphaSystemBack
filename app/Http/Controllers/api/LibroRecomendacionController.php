<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use App\Models\Libro;

class LibroRecomendacionController extends Controller
{
    /**
     * POST /api/libros/recomendar
     * Body: { "descripcion": "..." }
     */
    public function recomendar(Request $request)
    {
        // 1) Validación
        $request->validate([
            'descripcion' => 'required|string|min:3|max:2000',
        ]);
        $descripcion = $request->input('descripcion');

        // 2) Normalización & claves
        $descNorm = $this->normalizeText($descripcion);
        $descKey  = 'reco:desc:' . md5($descNorm);
        $indexKey = 'reco:descs_index';
        $index    = Cache::get($indexKey, []);

        // 3) Reuso por similitud
        [$bestKey, $bestScore] = $this->findSimilarDesc($descNorm, $index, 0.82);

        // 4) Candidatos (cacheados)
        $candidatos = Cache::remember('reco:candidatos_libros', 3600, function () {
            return Libro::select('isbn', 'titulo', 'autor')
                ->get()
                ->map(fn($l) => [
                    'isbn'   => (string) $l->isbn,
                    'titulo' => (string) $l->titulo,
                    'autor'  => (string) $l->autor,
                ])->values()->all();
        });
        if (empty($candidatos)) {
            return response()->json(['ok' => true, 'data' => []], 200);
        }

        // 5) Mapas normalizados
        [$mapAutor, $mapTitulo] = $this->buildNormalizedMaps($candidatos);

        // 6) ¿Hay intención explícita de autor?
        $autorIntent = $this->extractAuthorIntent($descNorm, $mapAutor);
        $shortlist = [];
        if ($autorIntent !== null) {
            // Si encontramos un autor claro, limitamos la shortlist SOLO a sus libros
            $autorIsbns = $mapAutor[$autorIntent] ?? [];
            if (!empty($autorIsbns)) {
                $byIsbn = [];
                foreach ($candidatos as $c) { $byIsbn[$c['isbn']] = $c; }
                foreach ($autorIsbns as $i) {
                    if (isset($byIsbn[$i])) $shortlist[] = $byIsbn[$i];
                }
            }
        }

        // 7) Si no hay shortlist por autor, usar flujo semántico + keywords (amplio)
        if (empty($shortlist)) {
            $tituloMatchIsbns  = $this->matchTitulo($descNorm, $mapTitulo);
            $temaKeywords      = $this->expandTopicKeywords($descNorm);
            $topSemanticos     = $this->prefiltroSemanticoOKeywords($candidatos, $descNorm, 120, $temaKeywords);
            $topDeterministas  = $this->prefiltrarCandidatosKeywords($candidatos, $descNorm, 80, $temaKeywords);
            // Merge priorizado: título > semántico > keywords
            $shortlistIsbns = $this->mergePrioritized(
                $tituloMatchIsbns,
                Arr::pluck($topSemanticos, 'isbn'),
                Arr::pluck($topDeterministas, 'isbn'),
            );
            $shortlistIsbns = array_slice($shortlistIsbns, 0, 120);

            $byIsbn = [];
            foreach ($candidatos as $c) { $byIsbn[$c['isbn']] = $c; }
            foreach ($shortlistIsbns as $i) {
                if (isset($byIsbn[$i])) $shortlist[] = $byIsbn[$i];
            }
            if (empty($shortlist)) {
                $shortlist = array_slice($candidatos, 0, min(120, count($candidatos)));
            }
        }

        // 8) Prompts (JSON estricto)
        $systemPrompt = <<<PROMPT
Eres un recomendador experto de una biblioteca local.
Dispones de una lista de libros (isbn, titulo, autor) y una descripción del usuario (gustos, temas, autores preferidos, etc.).
Selecciona hasta 10 libros de la lista que mejor encajen con la descripción.
Devuelve ÚNICAMENTE un JSON VÁLIDO con este formato EXACTO:
{
  "matches": [
    { "isbn": "XXXXXXXXXXXX", "score": 0.95 }
  ]
}
Reglas y criterios:
- SOLO usa ISBN de la lista dada.
- "matches" es un array de 0..10 elementos.
- "score" (0..1) indica el grado de afinidad; ordénalos de mayor a menor.
- Si el usuario menciona explícitamente un autor (por ejemplo "de gabriel garcia marquez"), prioriza estrictamente SUS libros.
- Considera género, temas, tono, estilo, ritmo, complejidad, autores o títulos mencionados, y evita duplicados.
- Tolerancia a acentos/variantes (ej. "garciamarquez" ~ "garcia marquez").
- Si no hay coincidencias claras, responde {"matches": []}.
- NO escribas nada fuera del JSON.
PROMPT;

        $userPrompt = "Descripción del usuario: \"{$descripcion}\".\n\nLista de candidatos (isbn, titulo, autor):\n"
                    . json_encode($shortlist, JSON_UNESCAPED_UNICODE);

        // 9) Cache por similitud
        if ($bestKey) {
            $isbnsAI = Cache::get($bestKey, []);
        } else {
            $isbnsAI = Cache::get($descKey);
            if ($isbnsAI === null) {
                // 10) Llamadas a OpenAI
                $apiKey = config('services.openai.key', env('OPENAI_API_KEY'));
                $primaryModel   = env('OPENAI_RECO_MODEL', 'gpt-5-mini');
                $fallbackNano   = 'gpt-5-nano';
                $maxOutPrimary  = (int) env('OPENAI_RECO_MAX_OUT', 520);
                $temperature    = (float) env('OPENAI_RECO_TEMP', 0.6);

                $payloadMini = [
                    'model' => $primaryModel,
                    'input' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user',   'content' => $userPrompt],
                    ],
                    'temperature' => $temperature,
                    'max_output_tokens' => $maxOutPrimary,
                    'response_format' => ['type' => 'json_object'],
                ];
                $payloadNano = [
                    'model' => $fallbackNano,
                    'input' => $payloadMini['input'],
                    'reasoning' => ['effort' => 'minimal'], // nano: sin gastar tokens en "pensar"
                    'max_output_tokens' => max(500, $maxOutPrimary),
                    'response_format' => ['type' => 'json_object'],
                ];

                $callResponses = fn(array $pl) => Http::timeout(25)
                    ->withToken($apiKey)
                    ->post('https://api.openai.com/v1/responses', $pl);

                try {
                    // Intento A: mini
                    $resp = $callResponses($payloadMini);
                    $json = $resp->json();

                    // Retry por max_output_tokens
                    $incompleteMax = (data_get($json, 'status') === 'incomplete')
                        && (data_get($json, 'incomplete_details.reason') === 'max_output_tokens');
                    if (!$resp->failed() && $incompleteMax) {
                        $payloadMini['max_output_tokens'] = $maxOutPrimary + 240;
                        $resp = $callResponses($payloadMini);
                        $json = $resp->json();
                    }

                    // Fallback a nano
                    $stillIncomplete = (data_get($json, 'status') === 'incomplete')
                        && (data_get($json, 'incomplete_details.reason') === 'max_output_tokens');
                    if ($resp->failed() || $stillIncomplete) {
                        $resp = $callResponses($payloadNano);
                        $json = $resp->json();
                    }

                    if ($resp->failed()) {
                        Log::error('OpenAI fallo (reco):', ['status' => $resp->status(), 'body' => $resp->body()]);
                        $isbnsAI = $this->fallbackDeterminista($shortlist, $descNorm, 8);
                    } else {
                        // Parseo JSON
                        $raw = data_get($json, 'output_text', '');
                        if (!is_string($raw) || trim($raw) === '') {
                            $items = data_get($json, 'output', []);
                            foreach ((array) $items as $item) {
                                if (data_get($item, 'type') === 'message') {
                                    foreach ((array) data_get($item, 'content', []) as $part) {
                                        $t = data_get($part, 'text');
                                        if (is_string($t) && trim($t) !== '') { $raw = $t; break 2; }
                                    }
                                }
                            }
                            if ($raw === '') {
                                $raw = data_get($json, 'choices.0.message.content', '');
                            }
                        }
                        $raw = trim($raw);
                        if ($raw !== '' && ($p1 = strpos($raw, '{')) !== false && ($p2 = strrpos($raw, '}')) !== false) {
                            $raw = substr($raw, $p1, $p2 - $p1 + 1);
                        }
                        $decoded = json_decode($raw, true);
                        if (!is_array($decoded) || !isset($decoded['matches']) || !is_array($decoded['matches'])) {
                            $decoded = ['matches' => []];
                        }

                        // Validar contra shortlist
                        $permitidos = Arr::pluck($shortlist, 'isbn');
                        $isbnsAI = collect($decoded['matches'])
                            ->pluck('isbn')
                            ->filter(fn($i) => in_array($i, $permitidos, true))
                            ->unique()->take(10)->values()->all();

                        if (empty($isbnsAI)) {
                            $isbnsAI = $this->fallbackDeterminista($shortlist, $descNorm, 8);
                        }
                    }

                    // Cache + índice
                    Cache::put($descKey, $isbnsAI, 86400);
                    $index[] = ['key' => $descKey, 'desc' => (string) $descNorm];
                    Cache::put($indexKey, $index, 86400);

                } catch (\Throwable $e) {
                    Log::error('Excepción IA (reco): ' . $e->getMessage());
                    $isbnsAI = $this->fallbackDeterminista($shortlist, $descNorm, 8);
                }
            }
        }

        // 11) Sin ISBN válidos
        if (empty($isbnsAI)) {
            return response()->json(['ok' => true, 'data' => []], 200);
        }

        // 12) Recuperar respetando orden
        $orderMap = array_flip($isbnsAI);
        $librosResp = Libro::whereIn('isbn', $isbnsAI)
            ->select('isbn', 'titulo', 'fotoUsr', 'autor')
            ->get()
            ->sortBy(fn($l) => $orderMap[$l->isbn] ?? 9999)
            ->values();

        // 13) Historial
        /** @var \App\Models\Usuario|null $user */
        $user = $request->user('sanctum');
        if ($user) {
            $uid = $user->idUsr ?? $user->id ?? Auth::id();
            $key = 'reco:user:' . $uid;
            $hist = Cache::get($key, []);
            array_unshift($hist, [
                'desc'  => $descripcion,
                'isbns' => $isbnsAI,
                'fecha' => now()->toIso8601String(),
            ]);
            $hist = array_slice($hist, 0, 15);
            Cache::put($key, $hist, now()->addDays(7));
        }

        return response()->json(['ok' => true, 'data' => $librosResp], 200);
    }

    /**
     * GET /api/libros/recomendar/historial
     */
    public function historial(Request $request)
    {
        $uid = Auth::id();
        $hist = [];
        if ($uid) {
            $hist = Cache::get('reco:user:' . $uid, []);
            if (empty($hist) && $request->user('sanctum')) {
                $hist = Cache::get('reco:user:' . ($request->user('sanctum')->idUsr ?? ''), []);
            }
        }
        return response()->json(['ok' => true, 'data' => $hist], 200);
    }

    /**
     * DELETE /api/libros/recomendar/historial
     */
    public function limpiarHistorial(Request $request)
    {
        $uid = Auth::id();
        if ($uid) {
            Cache::forget('reco:user:' . $uid);
            if ($request->user('sanctum') && ($request->user('sanctum')->idUsr ?? null)) {
                Cache::forget('reco:user:' . $request->user('sanctum')->idUsr);
            }
        }
        return response()->json(['ok' => true], 200);
    }

    /**
     * POST /api/libros/by-isbns
     */
    public function byIsbns(Request $request)
    {
        $data = $request->validate([
            'isbns'   => ['required','array','min:1','max:20'],
            'isbns.*' => ['string','distinct'],
        ]);
        $isbns = $data['isbns'];

        $order = array_flip($isbns);
        $libros = Libro::whereIn('isbn', $isbns)
            ->select('isbn','titulo','fotoUsr','autor')
            ->get()
            ->sortBy(fn($l) => $order[$l->isbn] ?? 9999)
            ->values();

        return response()->json(['ok'=>true,'data'=>$libros],200);
    }

    // ===================== Helpers =====================

    private function normalizeText(string $text): string
    {
        $t = Str::of($text)->lower()->squish();
        $t = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $t);
        if ($t === false) $t = (string) Str::of($text)->lower()->squish();
        $t = preg_replace('/[^a-z0-9\s]/', ' ', $t);
        return trim(preg_replace('/\s+/', ' ', $t));
    }

    private function buildNormalizedMaps(array $candidatos): array
    {
        $mapAutor = []; $mapTitulo = [];
        foreach ($candidatos as $c) {
            $a = $this->normalizeText($c['autor'] ?? '');
            $t = $this->normalizeText($c['titulo'] ?? '');
            $isbn = $c['isbn'];
            if ($a !== '') { $mapAutor[$a][] = $isbn; }
            if ($t !== '') { $mapTitulo[$t][] = $isbn; }
        }
        return [$mapAutor, $mapTitulo];
    }

    /**
     * Detecta intención "de <autor>" o mención fuerte del autor.
     * Devuelve el autor normalizado tal como está en $mapAutor, o null.
     */
    private function extractAuthorIntent(string $descNorm, array $mapAutor): ?string
    {
        // patrones comunes
        $patrones = [
            '/\bde\s+([a-z0-9\s]{3,})$/',             // "de gabriel garcia marquez"
            '/\bde\s+([a-z0-9\s]{3,})\b/',            // "recomiendame libros de gabriel ..."
            '/\bpor\s+([a-z0-9\s]{3,})\b/',           // "libro por gabriel ..."
            '/\bautor\s+([a-z0-9\s]{3,})\b/',         // "autor gabriel ..."
        ];
        $candidato = null;
        foreach ($patrones as $re) {
            if (preg_match($re, $descNorm, $m)) {
                $candidato = trim($m[1]);
                break;
            }
        }
        // si no hubo "de <autor>", intenta detectar nombre propio suelto
        if ($candidato === null) {
            // heurística: buscar apellidos frecuentes / emparejar tokens con autores
            // aquí simplemente usamos el mejor match contra el catálogo de autores
            $best = null; $bestScore = 0.0;
            foreach ($mapAutor as $autorNorm => $_) {
                $score = $this->softContainScore($descNorm, $autorNorm); // ~0..1
                if ($score > $bestScore) { $bestScore = $score; $best = $autorNorm; }
            }
            if ($best !== null && $bestScore >= 0.75) return $best;
            return null;
        }

        // normalizar candidato y compararlo con autores
        $cand = $this->normalizeText($candidato);
        // también versión sin espacios (para "garciamarquez")
        $candNoSpace = str_replace(' ', '', $cand);

        $best = null; $bestScore = 0.0;
        foreach ($mapAutor as $autorNorm => $_) {
            $score = max(
                $this->softContainScore($cand, $autorNorm),
                $this->softContainScore($candNoSpace, str_replace(' ', '', $autorNorm))
            );
            if ($score > $bestScore) { $bestScore = $score; $best = $autorNorm; }
        }
        return ($bestScore >= 0.72) ? $best : null;
    }

    /**
     * Scoring de "contención suave" entre textos normalizados: 0..1
     */
    private function softContainScore(string $a, string $b): float
    {
        // tokens >=3 chars
        $ta = array_values(array_filter(explode(' ', $a), fn($p) => strlen($p) >= 3));
        $tb = array_values(array_filter(explode(' ', $b), fn($p) => strlen($p) >= 3));
        if (empty($ta) || empty($tb)) return 0.0;
        $hits = 0;
        foreach ($ta as $wa) {
            foreach ($tb as $wb) {
                if (str_contains($wa, $wb) || str_contains($wb, $wa)) { $hits++; break; }
            }
        }
        return min(1.0, $hits / max(count($tb), 1));
    }

    private function matchTitulo(string $descNorm, array $mapTitulo): array
    {
        $out = [];
        foreach ($mapTitulo as $tituloNorm => $isbns) {
            if ($tituloNorm === '') continue;
            $parts = array_filter(explode(' ', $tituloNorm), fn($p) => strlen($p) >= 4);
            $hits = 0;
            foreach ($parts as $p) if (str_contains($descNorm, $p)) $hits++;
            if ($hits >= max(1, floor(count($parts) * 0.5))) {
                $out = array_merge($out, $isbns);
            }
        }
        return array_values(array_unique($out));
    }

    private function expandTopicKeywords(string $descNorm): array
    {
        $kw = [];
        if (str_contains($descNorm, 'historia')) {
            $kw = array_merge($kw, ['historia', 'humanidad', 'evolucion', 'antropologia', 'civilizacion']);
        }
        if (str_contains($descNorm, 'humana') || str_contains($descNorm, 'humanidad')) {
            $kw = array_merge($kw, ['humanidad', 'evolucion', 'prehistoria', 'sapiens']);
        }
        return array_values(array_unique($kw));
    }

    private function mergePrioritized(...$lists): array
    {
        $seen = []; $out = [];
        foreach ($lists as $list) {
            foreach ($list as $i) {
                if (!isset($seen[$i])) { $seen[$i] = true; $out[] = $i; }
            }
        }
        return $out;
    }

    private function prefiltrarCandidatosKeywords(array $candidatos, string $descNorm, int $topN = 80, array $topicKeywords = []): array
    {
        $parts = preg_split('/\s+/', $descNorm, -1, PREG_SPLIT_NO_EMPTY);
        $stop = ['el','la','los','las','un','una','de','del','y','o','u','en','con','a','por','para','que','se','su','sus','al'];
        $keywords = array_values(array_diff($parts, $stop));
        $keywords = array_unique(array_merge($keywords, $topicKeywords));

        $scored = [];
        foreach ($candidatos as $c) {
            $hay = $this->normalizeText(($c['titulo'] ?? '') . ' ' . ($c['autor'] ?? ''));
            $score = 0;
            foreach ($keywords as $kw) {
                if ($kw !== '' && str_contains($hay, $kw)) $score++;
            }
            // plus si el autor completo está contenido
            $autorNorm = $this->normalizeText($c['autor'] ?? '');
            if ($autorNorm !== '' && str_contains($descNorm, $autorNorm)) $score += 3;
            $scored[] = [$c, $score];
        }

        usort($scored, fn($a, $b) => $b[1] <=> $a[1]);
        $top = array_slice($scored, 0, max(10, $topN));
        return array_map(fn($x) => $x[0], $top);
    }

    private function prefiltroSemanticoOKeywords(array $candidatos, string $descNorm, int $topN = 120, array $topicKeywords = []): array
    {
        try {
            $apiKey = config('services.openai.key', env('OPENAI_API_KEY'));
            $model  = env('OPENAI_EMBED_MODEL', 'text-embedding-3-small');

            $descEmb = $this->embeddingForText($descNorm, $model, $apiKey);

            $missingKeys = [];
            $vectors = [];
            $texts   = [];
            foreach ($candidatos as $c) {
                $isbn = $c['isbn'];
                $cacheKey = 'emb:libro:' . md5($isbn . '|' . ($c['titulo'] ?? '') . '|' . ($c['autor'] ?? ''));
                $vec = Cache::get($cacheKey);
                if (is_array($vec)) {
                    $vectors[$isbn] = $vec;
                } else {
                    $txt = $this->normalizeText(($c['titulo'] ?? '') . ' ' . ($c['autor'] ?? ''));
                    $texts[$isbn] = $txt;
                    $missingKeys[$isbn] = $cacheKey;
                }
            }

            if (!empty($texts)) {
                $batchVecs = $this->embeddingForBatch(array_values($texts), $model, $apiKey);
                $isns = array_keys($texts);
                foreach ($isns as $i => $isbn) {
                    $vec = $batchVecs[$i] ?? null;
                    if (is_array($vec)) {
                        $vectors[$isbn] = $vec;
                        Cache::put($missingKeys[$isbn], $vec, now()->addDays(7));
                    }
                }
            }

            $scores = [];
            foreach ($candidatos as $c) {
                $isbn = $c['isbn'];
                if (!isset($vectors[$isbn])) continue;
                $score = $this->cosine($descEmb, $vectors[$isbn]);
                if (!empty($topicKeywords)) {
                    $hay = $this->normalizeText(($c['titulo'] ?? '') . ' ' . ($c['autor'] ?? ''));
                    $bonus = 0;
                    foreach ($topicKeywords as $kw) if ($kw !== '' && str_contains($hay, $kw)) $bonus += 0.02;
                    $score += min(0.1, $bonus);
                }
                $scores[] = [$c, $score];
            }

            usort($scores, fn($a, $b) => $b[1] <=> $a[1]);
            $top = array_slice($scores, 0, max(10, $topN));
            return array_map(fn($x) => $x[0], $top);

        } catch (\Throwable $e) {
            Log::warning('Embeddings fallback to keywords: ' . $e->getMessage());
            return $this->prefiltrarCandidatosKeywords($candidatos, $descNorm, $topN, $topicKeywords);
        }
    }

    private function fallbackDeterminista(array $shortlist, string $descNorm, int $n = 8): array
    {
        $scored = [];
        $parts = array_filter(preg_split('/\s+/', $descNorm, -1, PREG_SPLIT_NO_EMPTY), fn($p) => strlen($p) >= 3);
        foreach ($shortlist as $c) {
            $hay = $this->normalizeText(($c['titulo'] ?? '') . ' ' . ($c['autor'] ?? ''));
            $score = 0;
            foreach ($parts as $p) if (str_contains($hay, $p)) $score++;
            $scored[] = [$c['isbn'], $score];
        }
        usort($scored, fn($a, $b) => $b[1] <=> $a[1]);
        return array_values(array_unique(array_slice(array_map(fn($x) => $x[0], $scored), 0, $n)));
    }

    private function embeddingForText(string $text, string $model, string $apiKey): array
    {
        $resp = Http::timeout(20)
            ->withToken($apiKey)
            ->post('https://api.openai.com/v1/embeddings', [
                'model' => $model,
                'input' => $text,
            ]);
        if ($resp->failed()) {
            throw new \RuntimeException('Embedding request failed: ' . $resp->body());
        }
        $data = $resp->json();
        $vec = data_get($data, 'data.0.embedding');
        if (!is_array($vec)) {
            throw new \RuntimeException('Invalid embedding response');
        }
        return $vec;
    }

    private function embeddingForBatch(array $texts, string $model, string $apiKey): array
    {
        $resp = Http::timeout(30)
            ->withToken($apiKey)
            ->post('https://api.openai.com/v1/embeddings', [
                'model' => $model,
                'input' => array_values($texts),
            ]);
        if ($resp->failed()) {
            throw new \RuntimeException('Embedding batch failed: ' . $resp->body());
        }
        $data = $resp->json();
        $arr  = data_get($data, 'data');
        if (!is_array($arr)) {
            throw new \RuntimeException('Invalid embedding batch response');
        }
        usort($arr, fn($a, $b) => ($a['index'] ?? 0) <=> ($b['index'] ?? 0));
        return array_map(fn($row) => $row['embedding'] ?? [], $arr);
    }

    private function cosine(array $a, array $b): float
    {
        $dot = 0.0; $na = 0.0; $nb = 0.0;
        $n = min(count($a), count($b));
        for ($i = 0; $i < $n; $i++) {
            $ai = (float) $a[$i]; $bi = (float) $b[$i];
            $dot += $ai * $bi;
            $na  += $ai * $ai;
            $nb  += $bi * $bi;
        }
        if ($na == 0.0 || $nb == 0.0) return 0.0;
        return $dot / (sqrt($na) * sqrt($nb));
    }

    private function findSimilarDesc(string $needle, array $index, float $threshold = 0.8): array
    {
        $bestKey = null; $best = 0.0;
        foreach ($index as $item) {
            similar_text($needle, $item['desc'], $percent);
            $score = $percent / 100.0;
            if ($score > $best) { $best = $score; $bestKey = $item['key']; }
        }
        return ($best >= $threshold) ? [$bestKey, $best] : [null, 0.0];
    }
}
