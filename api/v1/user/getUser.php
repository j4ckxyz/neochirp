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

$username = isset($_GET['username']) ? trim($_GET['username']) : '';
if (empty($username)) chirp_api_error(400, 'username parameter is required');

$stmt = $db->prepare('SELECT id, username, name, profilePic, bio, isVerified, created_at,
    (SELECT COUNT(*) FROM following WHERE following_id = users.id) AS follower_count,
    (SELECT COUNT(*) FROM following WHERE follower_id = users.id) AS following_count,
    (SELECT COUNT(*) FROM chirps WHERE user = users.id AND type = "post") AS chirp_count
    FROM users WHERE username = :uname');
$stmt->execute([':uname' => $username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) chirp_api_error(404, 'User not found');

$user['isVerified'] = (bool)$user['isVerified'];
$user['profilePic'] = htmlspecialchars_decode((string)($user['profilePic'] ?? ''));

echo json_encode($user);
