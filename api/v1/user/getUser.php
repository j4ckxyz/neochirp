<?php

 }

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
