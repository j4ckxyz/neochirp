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
    WHERE chirps.id = :id
');
$stmt->execute([':uid' => $userId, ':id' => $chirpId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) chirp_api_error(404, 'Chirp not found');

$row['isVerified'] = (bool)$row['isVerified'];
$row['liked_by_me'] = (bool)$row['liked_by_me'];
$row['rechirped_by_me'] = (bool)$row['rechirped_by_me'];
$row['profilePic'] = htmlspecialchars_decode((string)($row['profilePic'] ?? ''));
$row['via'] = $row['via'] ?? null;

echo json_encode($row);
