<?php
/**
 * Shared trends computation.
 *
 * Algorithm:
 *  1. Tokenise chirps from the past 7 days; count unique word occurrences per chirp.
 *  2. Take the top $candidateCount words by chirp-frequency.
 *  3. If Ollama is reachable, embed all candidates in one batch call and merge
 *     semantically-similar words (cosine sim > $mergeThreshold) into the most
 *     frequent member of each cluster.
 *  4. Return up to $maxResults trends, each with ['word' => ..., 'count' => ...].
 *
 * Falls back silently to pure word-frequency if Ollama is unavailable.
 */

require_once __DIR__ . '/../config.php';

// ── Cache helpers ─────────────────────────────────────────────────────────────
define('TRENDS_CACHE_FILE', __DIR__ . '/../trends_cache.json');
define('TRENDS_CACHE_TTL', 3600); // 1 hour

function get_cached_trends(int $maxResults = 10): ?array {
    if (!file_exists(TRENDS_CACHE_FILE)) return null;
    $raw = @file_get_contents(TRENDS_CACHE_FILE);
    if ($raw === false) return null;
    $data = json_decode($raw, true);
    if (!is_array($data) || !isset($data['timestamp'], $data['trends'])) return null;
    if ((time() - (int)$data['timestamp']) > TRENDS_CACHE_TTL) return null;
    return array_slice($data['trends'], 0, $maxResults);
}

function save_trends_cache(array $trends): void {
    $data = ['timestamp' => time(), 'trends' => $trends];
    @file_put_contents(TRENDS_CACHE_FILE, json_encode($data), LOCK_EX);
}

// ── Stopwords ────────────────────────────────────────────────────────────────
function chirp_stopwords(): array {
    return [
        'the','a','an','and','or','but','in','on','at','to','for','of','with',
        'by','from','up','about','into','than','then','that','this','these',
        'those','is','are','was','were','be','been','being','have','has','had',
        'do','does','did','will','would','could','should','may','might','shall',
        'can','not','no','nor','so','yet','both','either','neither','whether',
        'i','me','my','myself','we','our','ours','ourselves','you','your',
        'yours','yourself','yourselves','he','him','his','himself','she','her',
        'hers','herself','it','its','itself','they','them','their','theirs',
        'themselves','what','which','who','whom','whose','here','there','when',
        'where','why','how','all','each','every','few','more','most','other',
        'some','such','only','own','same','very','just','because','if','while',
        'as','out','get','got','said','also','like','dont','im','one','new',
        'now','go','want','time','know','think','come','see','way','make',
        'look','use','day','good','need','feel','put','take','much','well',
        'many','great','never','give','still','us','off','two','right',
        'really','again','too','even','back','any','after','first','things',
        'thing','people','cant','wont','isnt','arent','wasnt','werent',
        'didnt','doesnt','hadnt','hasnt','havent','wouldnt','couldnt',
        'shouldnt','ill','ive','id','theyre','theyve','theyd','weve','wed',
        'youre','youve','youd','hes','shes','thats','whats','lets','heres',
        'theres','via','amp','rt','gt','lt','http','https','www','com','net',
        'org','io','co','chirp','chirpsocial',
    ];
}

// ── Blocklist ─────────────────────────────────────────────────────────────────
function chirp_blocklist(): array {
    return [
        'nigger','niggers','nigga','niggas','faggot','faggots','fag','fags',
        'kike','kikes','spic','spics','chink','chinks','gook','gooks',
        'wetback','wetbacks','tranny','trannies','retard','retards','retarded',
        'cunt','cunts','whore','whores','slut','sluts','dyke','dykes',
        'beaner','beaners','towelhead','raghead','cracker','honky','jap',
        'japs','coon','coons','shemale','troon','troons',
    ];
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function cosine_similarity(array $a, array $b): float {
    $dot = $normA = $normB = 0.0;
    $n = count($a);
    for ($i = 0; $i < $n; $i++) {
        $dot  += $a[$i] * $b[$i];
        $normA += $a[$i] * $a[$i];
        $normB += $b[$i] * $b[$i];
    }
    if ($normA == 0.0 || $normB == 0.0) return 0.0;
    return $dot / (sqrt($normA) * sqrt($normB));
}

/**
 * Batch-embed an array of strings via Ollama.
 * Returns array of float[] on success, null on failure.
 */
function ollama_embed(array $inputs): ?array {
    if (empty($inputs)) return [];

    $payload = json_encode([
        'model' => OLLAMA_EMBED_MODEL,
        'input' => $inputs,
    ]);

    $ctx = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\nContent-Length: " . strlen($payload) . "\r\n",
            'content' => $payload,
            'timeout' => OLLAMA_TIMEOUT,
            'ignore_errors' => true,
        ],
    ]);

    $raw = @file_get_contents(OLLAMA_HOST . '/api/embed', false, $ctx);
    if ($raw === false) return null;

    $data = json_decode($raw, true);
    return $data['embeddings'] ?? null;
}

/**
 * Merge semantically-similar word clusters.
 * Modifies $wordCounts in-place: less-frequent duplicates are folded into
 * the top member of their cluster and their key removed.
 *
 * @param array  $wordCounts  ['word' => chirp_count, ...]  already sorted DESC
 * @param array  $embeddings  parallel array of float[] per word
 * @param array  $words       ordered word list parallel to $embeddings
 * @param float  $threshold   cosine-similarity cutoff for "same topic"
 */
function merge_similar_clusters(array &$wordCounts, array $embeddings, array $words, float $threshold = 0.82): void {
    $absorbed = [];  // set of words already merged into another

    for ($i = 0; $i < count($words); $i++) {
        $wi = $words[$i];
        if (isset($absorbed[$wi])) continue;

        for ($j = $i + 1; $j < count($words); $j++) {
            $wj = $words[$j];
            if (isset($absorbed[$wj])) continue;

            $sim = cosine_similarity($embeddings[$i], $embeddings[$j]);
            if ($sim >= $threshold) {
                // $wi has higher count (list is sorted); fold $wj into $wi
                $wordCounts[$wi] += $wordCounts[$wj] ?? 0;
                unset($wordCounts[$wj]);
                $absorbed[$wj] = true;
            }
        }
    }
}

/**
 * Main entry point.
 *
 * @param PDO   $db             Open database connection
 * @param int   $maxResults     Number of trends to return
 * @param int   $candidateCount How many top words to feed into Ollama
 * @param int   $minChirps      Minimum chirp-count for a word to qualify
 * @return array  [['word' => string, 'count' => int], ...]
 */
function compute_trends(PDO $db, int $maxResults = 10, int $candidateCount = 50, int $minChirps = 2): array {
    $cached = get_cached_trends($maxResults);
    if ($cached !== null) return $cached;

    $stopwords = chirp_stopwords();
    $blockset  = array_flip(chirp_blocklist());

    $sevenDaysAgo = time() - (7 * 86400);
    $stmt = $db->prepare("SELECT chirp FROM chirps WHERE type = 'post' AND timestamp > :since LIMIT 3000");
    $stmt->bindValue(':since', $sevenDaysAgo, PDO::PARAM_INT);
    $stmt->execute();

    $wordCounts = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $text = strip_tags($row['chirp']);
        $text = preg_replace('/https?:\/\/\S+/', '', $text);
        $text = preg_replace('/[^a-zA-Z\s]/', ' ', $text);
        $text = strtolower($text);

        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $seen  = [];
        foreach ($words as $word) {
            if (strlen($word) < 3)               continue;
            if (in_array($word, $stopwords))     continue;
            if (isset($blockset[$word]))          continue;
            if (preg_match('/^(.)\1+$/', $word))  continue; // "aaaa" etc.
            if (!isset($seen[$word])) {
                $seen[$word]        = true;
                $wordCounts[$word]  = ($wordCounts[$word] ?? 0) + 1;
            }
        }
    }

    // Filter low-frequency words and sort
    $wordCounts = array_filter($wordCounts, fn($c) => $c >= $minChirps);
    arsort($wordCounts);

    // Take top candidates for Ollama
    $candidates = array_slice(array_keys($wordCounts), 0, $candidateCount, true);

    if (!empty($candidates)) {
        $embeddings = ollama_embed($candidates);

        if ($embeddings !== null && count($embeddings) === count($candidates)) {
            // Narrow wordCounts to just candidates before merging
            $subset = array_intersect_key($wordCounts, array_flip($candidates));
            merge_similar_clusters($subset, $embeddings, $candidates);
            arsort($subset);
            $wordCounts = $subset;
        }
        // else: Ollama unreachable — fall through to plain word-freq
    }

    $trends = [];
    $i = 0;
    foreach ($wordCounts as $word => $count) {
        $trends[] = ['word' => $word, 'count' => $count];
        if (++$i >= $maxResults) break;
    }
    save_trends_cache($trends);
    return $trends;
}
