<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../include/trends.php';

if (!DEV_MODE) {
    http_response_code(403);
    exit;
}

// SSE headers
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');

// Disable output buffering
if (ob_get_level() > 0) {
    ob_end_clean();
}
ob_implicit_flush(true);

/**
 * Send a Server-Sent Event.
 */
function sse(string $event, array $data): void {
    echo "event: {$event}\n";
    echo 'data: ' . json_encode($data) . "\n\n";
    if (ob_get_level() > 0) ob_flush();
    flush();
}

// Step 1: start
sse('progress', ['step' => 'start', 'message' => 'Starting trends rescan...', 'progress' => 5]);

// Step 2: fetch chirps from DB
sse('progress', ['step' => 'fetch', 'message' => 'Fetching chirps from last 7 days...', 'progress' => 15]);

try {
    $db = new PDO('sqlite:' . __DIR__ . '/../../chirp.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    sse('done', ['step' => 'error', 'message' => 'DB connection failed: ' . $e->getMessage(), 'progress' => 100, 'trends' => []]);
    exit;
}

$stopwords = chirp_stopwords();
$blockset  = array_flip(chirp_blocklist());
$candidateCount = 50;
$minChirps = 2;
$maxResults = 10;

$sevenDaysAgo = time() - (7 * 86400);
$stmt = $db->prepare("SELECT chirp FROM chirps WHERE type = 'post' AND timestamp > :since LIMIT 3000");
$stmt->bindValue(':since', $sevenDaysAgo, PDO::PARAM_INT);
$stmt->execute();
$chirpRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$chirpCount = count($chirpRows);

// Step 3: tokenize
$wordCounts = [];
foreach ($chirpRows as $row) {
    $text = strip_tags($row['chirp']);
    $text = preg_replace('/https?:\/\/\S+/', '', $text);
    $text = preg_replace('/[^a-zA-Z\s]/', ' ', $text);
    $text = strtolower($text);

    $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
    $seen  = [];
    foreach ($words as $word) {
        if (strlen($word) < 3)              continue;
        if (in_array($word, $stopwords))    continue;
        if (isset($blockset[$word]))         continue;
        if (preg_match('/^(.)\1+$/', $word)) continue;
        if (!isset($seen[$word])) {
            $seen[$word]       = true;
            $wordCounts[$word] = ($wordCounts[$word] ?? 0) + 1;
        }
    }
}

$wordCounts = array_filter($wordCounts, fn($c) => $c >= $minChirps);
arsort($wordCounts);
$candidates = array_slice(array_keys($wordCounts), 0, $candidateCount, true);

sse('progress', [
    'step'     => 'tokenize',
    'message'  => "Processing {$chirpCount} chirps, found " . count($wordCounts) . " candidate words...",
    'progress' => 40,
]);

// Step 4: embed with Ollama
$ollamaOk = false;
$embeddings = null;

if (!empty($candidates)) {
    sse('progress', [
        'step'    => 'embed',
        'message' => 'Sending ' . count($candidates) . ' words to Ollama (' . OLLAMA_EMBED_MODEL . ')...',
        'progress' => 55,
    ]);

    $embeddings = ollama_embed($candidates);

    if ($embeddings !== null && count($embeddings) === count($candidates)) {
        $ollamaOk = true;
    }
}

// Step 5: merge or fallback
if ($ollamaOk) {
    sse('progress', ['step' => 'merge', 'message' => 'Merging semantically similar terms...', 'progress' => 75]);
    $subset = array_intersect_key($wordCounts, array_flip($candidates));
    merge_similar_clusters($subset, $embeddings, $candidates);
    arsort($subset);
    $wordCounts = $subset;
} else {
    sse('progress', ['step' => 'embed_fail', 'message' => 'Ollama unavailable, using word frequency only', 'progress' => 75]);
    // Keep wordCounts as-is (already sorted, already candidate-filtered)
    $wordCounts = array_intersect_key($wordCounts, array_flip($candidates));
    arsort($wordCounts);
}

// Step 6: save cache
sse('progress', ['step' => 'save', 'message' => 'Saving trends cache...', 'progress' => 90]);

$trends = [];
$i = 0;
foreach ($wordCounts as $word => $count) {
    $trends[] = ['word' => $word, 'count' => $count];
    if (++$i >= $maxResults) break;
}
save_trends_cache($trends);

// Step 7: done
sse('done', [
    'step'     => 'done',
    'message'  => 'Found ' . count($trends) . ' trends!',
    'progress' => 100,
    'trends'   => $trends,
]);
