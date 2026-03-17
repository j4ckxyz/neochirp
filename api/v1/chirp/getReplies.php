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

$chirpId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$chirpId) chirp_api_error(400, 'Missing id parameter');

$offset = isset($_GET['offset']) ? max(0, (int)$_GET['offset']) : 0;
$limit  = 20;
$userId = (int)$apiUser['user_id'];

$stmt = $db->prepare('
    SELECT chirps.id, chirps.chirp, chirps.timestamp, chirps.type, chirps.via, chirps.parent,
           users.username, users.name, users.profilePic, users.isVerified,
           (SELECT COUNT(*) FROM likes WHERE chirp_id = chirps.id) AS like_count,
           (SELECT COUNT(*) FROM rechirps WHERE chirp_id = chirps.id) AS rechirp_count,
           (SELECT COUNT(*) FROM chirps AS replies WHERE replies.parent = chirps.id AND replies.type = "reply") AS reply_count,
           (SELECT COUNT(*) FROM likes WHERE chirp_id = chirps.id AND user_id = :uid) AS liked_by_me,
           (SELECT COUNT(*) FROM rechirps WHERE chirp_id = chirps.id AND user_id = :uid) AS rechirped_by_me
    FROM chirps
    INNER JOIN users ON chirps.user = users.id
    WHERE chirps.parent = :parent AND chirps.type = "reply"
    ORDER BY chirps.timestamp ASC
    LIMIT :lim OFFSET :off
');
$stmt->execute([':uid' => $userId, ':parent' => $chirpId, ':lim' => $limit, ':off' => $offset]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$result = [];
foreach ($rows as $row) {
    $row['isVerified'] = (bool)$row['isVerified'];
    $row['liked_by_me'] = (bool)$row['liked_by_me'];
    $row['rechirped_by_me'] = (bool)$row['rechirped_by_me'];
    $row['profilePic'] = htmlspecialchars_decode((string)($row['profilePic'] ?? ''));
    $row['via'] = $row['via'] ?? null;
    $result[] = $row;
}

echo json_encode($result);
