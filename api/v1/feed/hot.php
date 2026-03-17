<?php

$allowedOrigins = [
    "https://beta.chirpsocial.net",
    "https://chirpsocial.net",
    "http://legacy.chirpsocial.net",
    "https://legacy.chirpsocial.net"
];
if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowedOrigins)) {
    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
}
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../auth.php';
$apiUser = chirp_api_auth();
$db = chirp_api_db();

$offset = isset($_GET['offset']) ? max(0, (int)$_GET['offset']) : 0;
$limit  = isset($_GET['limit'])  ? min(50, max(1, (int)$_GET['limit'])) : 20;
$userId = (int)$apiUser['user_id'];

$now = time();
$sevenDaysAgo = $now - (7 * 86400);

$stmt = $db->prepare('
    SELECT chirps.id, chirps.chirp, chirps.timestamp, chirps.type, chirps.via, chirps.user,
           users.username, users.name, users.profilePic, users.isVerified,
           (SELECT COUNT(*) FROM likes WHERE chirp_id = chirps.id) AS like_count,
           (SELECT COUNT(*) FROM rechirps WHERE chirp_id = chirps.id) AS rechirp_count,
           (SELECT COUNT(*) FROM chirps AS replies WHERE replies.parent = chirps.id AND replies.type = "reply") AS reply_count,
           (SELECT COUNT(*) FROM likes WHERE chirp_id = chirps.id AND user_id = :uid) AS liked_by_me,
           (SELECT COUNT(*) FROM rechirps WHERE chirp_id = chirps.id AND user_id = :uid) AS rechirped_by_me
    FROM chirps
    INNER JOIN users ON chirps.user = users.id
    WHERE chirps.type = "post"
      AND chirps.timestamp > :seven_days_ago
    ORDER BY chirps.timestamp DESC
    LIMIT 500
');
$stmt->execute([':uid' => $userId, ':seven_days_ago' => $sevenDaysAgo]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get following list for network boost
$followingIds = [];
$followStmt = $db->prepare('SELECT following_id FROM following WHERE follower_id = :uid');
$followStmt->execute([':uid' => $userId]);
while ($f = $followStmt->fetch(PDO::FETCH_ASSOC)) {
    $followingIds[$f['following_id']] = true;
}

// Score each chirp
foreach ($rows as &$row) {
    $ageHours = ($now - (int)$row['timestamp']) / 3600.0;
    $interactions = (int)$row['like_count'] + (int)$row['rechirp_count'] * 2 + (int)$row['reply_count'];
    $decay = pow($ageHours + 2, 1.5);
    $score = $interactions / $decay;
    if (isset($followingIds[(int)$row['user']])) {
        $score *= 1.5;
    }
    $row['_hot_score'] = $score;
}
unset($row);

usort($rows, function($a, $b) {
    return $b['_hot_score'] <=> $a['_hot_score'];
});

$page = array_slice($rows, $offset, $limit);

$result = [];
foreach ($page as $row) {
    unset($row['_hot_score']);
    $row['isVerified'] = (bool)$row['isVerified'];
    $row['liked_by_me'] = (bool)$row['liked_by_me'];
    $row['rechirped_by_me'] = (bool)$row['rechirped_by_me'];
    $row['profilePic'] = htmlspecialchars_decode((string)($row['profilePic'] ?? ''));
    $row['via'] = $row['via'] ?? null;
    $result[] = $row;
}

echo json_encode($result);
