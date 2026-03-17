<?php
require_once __DIR__ . '/../config.php';

if (!DEV_MODE) {
    http_response_code(403);
    echo json_encode(['error' => 'Not available outside dev mode']);
    exit;
}

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');

function sse(string $event, array $data): void {
    echo "event: $event\n";
    echo 'data: ' . json_encode($data) . "\n\n";
    if (ob_get_level()) ob_flush();
    flush();
}

$count = isset($_GET['count']) ? max(1, min(500, (int)$_GET['count'])) : 50;

// ── Word pools for generating realistic-ish posts ─────────────────────────────

$topics = [
    // Tech
    'javascript', 'typescript', 'react', 'vue', 'svelte', 'nextjs', 'nodejs',
    'python', 'rust', 'golang', 'php', 'linux', 'docker', 'kubernetes', 'git',
    'openai', 'llm', 'chatgpt', 'claude', 'ai', 'machine learning', 'neural network',
    'open source', 'github', 'vscode', 'terminal', 'api', 'database', 'sqlite',
    // Pop culture
    'movie', 'netflix', 'spotify', 'apple', 'tesla', 'spacex', 'starlink',
    'coffee', 'pizza', 'sushi', 'hiking', 'gym', 'running', 'cycling',
    'photography', 'music', 'concert', 'book', 'podcast', 'youtube',
    // News-ish
    'climate', 'economy', 'election', 'privacy', 'crypto', 'bitcoin', 'nft',
    'startup', 'venture capital', 'layoffs', 'remote work', 'work from home',
    // Chirp-specific (will make these trend)
    'chirp', 'chirping', 'rechirp', 'trending',
];

$templates = [
    "Just tried {topic} for the first time and I'm hooked 🔥",
    "Hot take: {topic} is overrated and everyone knows it",
    "Can we talk about how {topic} has completely changed everything?",
    "Nobody talks about {topic} enough. It's literally everywhere.",
    "Day {n} of learning {topic}. Still confused but making progress.",
    "{topic} is the future. Change my mind.",
    "Unpopular opinion: {topic} is actually kind of amazing",
    "My {topic} setup is finally perfect. Took long enough.",
    "Why does everyone suddenly care about {topic}?",
    "Just shipped a project using {topic}. So satisfying.",
    "The {topic} community is genuinely one of the best online.",
    "If you're not using {topic} in {year}, what are you doing?",
    "{topic} just dropped something huge and I am not okay",
    "Reminder that {topic} exists and it's incredible",
    "Three years into {topic} and I still learn something new every week",
    "The more I use {topic} the more I appreciate it",
    "{topic} vs everything else: {topic} wins every time",
    "Finally got around to trying {topic}. Why did I wait so long?",
    "Spent all weekend on {topic}. No regrets whatsoever.",
    "Real question: how did we survive before {topic}?",
    "Everyone's talking about {topic} and honestly they're right",
    "Just had a great conversation about {topic} with a stranger. Love that.",
    "The {topic} hype is real and it's earned",
    "There's something special about the {topic} ecosystem",
    "Learning {topic} is painful but worth every second",
    "Quick {topic} tip that changed my life: just keep at it",
    "{topic}? More like the best thing that happened to me this year.",
    "Woke up thinking about {topic} again. This is fine.",
    "Obsessed with {topic} lately. Send help.",
    "Another day, another {topic} rabbit hole. Worth it.",
];

// ── Dummy account pool ────────────────────────────────────────────────────────

$dummyUsers = [
    ['devbot_alex',    'Alex Rivera'],
    ['devbot_casey',   'Casey Morgan'],
    ['devbot_jordan',  'Jordan Lee'],
    ['devbot_riley',   'Riley Chen'],
    ['devbot_avery',   'Avery Patel'],
    ['devbot_quinn',   'Quinn Thompson'],
    ['devbot_sage',    'Sage Williams'],
    ['devbot_morgan',  'Morgan Davis'],
    ['devbot_blake',   'Blake Garcia'],
    ['devbot_drew',    'Drew Martinez'],
];

try {
    $db = new PDO('sqlite:' . __DIR__ . '/../../chirp.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    sse('error', ['message' => 'DB error: ' . $e->getMessage()]);
    exit;
}

// Ensure dummy accounts exist
sse('progress', ['message' => 'Ensuring dummy accounts exist...', 'progress' => 2]);

$dummyPassword = password_hash('devbot_password_not_for_real_use', PASSWORD_DEFAULT);
$createdAccounts = 0;

foreach ($dummyUsers as [$uname, $displayName]) {
    $check = $db->prepare('SELECT id FROM users WHERE username = :u');
    $check->execute([':u' => $uname]);
    if (!$check->fetchColumn()) {
        $ins = $db->prepare('INSERT INTO users (username, name, email, password_hash, usedInvite) VALUES (:u, :n, :e, :p, :inv)');
        $ins->execute([
            ':u'   => $uname,
            ':n'   => $displayName,
            ':e'   => $uname . '@devbot.local',
            ':p'   => $dummyPassword,
            ':inv' => 'DEV_BOT_' . $uname,
        ]);
        $createdAccounts++;
    }
}

sse('progress', ['message' => "Ready. $createdAccounts new dummy accounts created.", 'progress' => 5]);

// Fetch all dummy user IDs
$idMap = [];
foreach ($dummyUsers as [$uname, $_]) {
    $s = $db->prepare('SELECT id FROM users WHERE username = :u');
    $s->execute([':u' => $uname]);
    $id = $s->fetchColumn();
    if ($id) $idMap[$uname] = (int)$id;
}

if (empty($idMap)) {
    sse('error', ['message' => 'Could not find any dummy user IDs.']);
    exit;
}

$userIds = array_values($idMap);
$now = time();
$year = date('Y');
$inserted = 0;

$stmt = $db->prepare('INSERT INTO chirps (user, type, chirp, timestamp, via) VALUES (:u, :t, :c, :ts, :via)');

for ($i = 0; $i < $count; $i++) {
    // Pick random template and topic
    $template = $templates[array_rand($templates)];
    $topic    = $topics[array_rand($topics)];
    $userId   = $userIds[array_rand($userIds)];

    $text = str_replace(
        ['{topic}', '{n}', '{year}'],
        [$topic, rand(1, 100), $year],
        $template
    );

    // Scatter timestamps over the past 7 days so trends pick them up
    $ts = $now - rand(0, 7 * 86400);

    $stmt->execute([':u' => $userId, ':t' => 'post', ':c' => $text, ':ts' => $ts, ':via' => 'DevBot']);
    $inserted++;

    $pct = 5 + (int)(($i + 1) / $count * 90);
    if ($i % max(1, (int)($count / 20)) === 0 || $i === $count - 1) {
        sse('progress', [
            'message'  => "Posted $inserted / $count chirps...",
            'progress' => $pct,
        ]);
    }
}

sse('done', [
    'message'  => "Done! $inserted dummy chirps posted across " . count($userIds) . " bot accounts.",
    'inserted' => $inserted,
]);
