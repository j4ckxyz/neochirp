<?php
define('API_WRITE', true);

 }

require_once __DIR__ . '/../auth.php';
$apiUser = chirp_api_auth();
$db = chirp_api_db();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') chirp_api_error(405, 'Method not allowed');

$body = json_decode(file_get_contents('php://input'), true);
$chirpId = isset($body['chirp_id']) ? (int)$body['chirp_id'] : 0;
if (!$chirpId) chirp_api_error(400, 'chirp_id is required');

$userId = (int)$apiUser['user_id'];

// Check if already liked
$check = $db->prepare('SELECT COUNT(*) FROM likes WHERE chirp_id = :cid AND user_id = :uid');
$check->execute([':cid' => $chirpId, ':uid' => $userId]);
$exists = (int)$check->fetchColumn() > 0;

// Get chirp owner
$ownerStmt = $db->prepare('SELECT user FROM chirps WHERE id = :id');
$ownerStmt->execute([':id' => $chirpId]);
$chirpOwner = $ownerStmt->fetchColumn();
if ($chirpOwner === false) chirp_api_error(404, 'Chirp not found');

$now = time();

if ($exists) {
    $db->prepare('DELETE FROM likes WHERE chirp_id = :cid AND user_id = :uid')
       ->execute([':cid' => $chirpId, ':uid' => $userId]);
    $liked = false;
    // Delete notification
    if ((int)$chirpOwner !== $userId) {
        $db->prepare('DELETE FROM notifications WHERE user_id = :owner AND actor_id = :actor AND type = :type AND chirp_id = :cid')
           ->execute([':owner' => $chirpOwner, ':actor' => $userId, ':type' => 'like', ':cid' => $chirpId]);
    }
} else {
    $db->prepare('INSERT INTO likes (chirp_id, user_id, timestamp) VALUES (:cid, :uid, :ts)')
       ->execute([':cid' => $chirpId, ':uid' => $userId, ':ts' => $now]);
    $liked = true;
    // Insert notification
    if ((int)$chirpOwner !== $userId) {
        $db->prepare('INSERT OR IGNORE INTO notifications (user_id, actor_id, type, chirp_id, timestamp) VALUES (:owner, :actor, :type, :cid, :ts)')
           ->execute([':owner' => $chirpOwner, ':actor' => $userId, ':type' => 'like', ':cid' => $chirpId, ':ts' => $now]);
    }
}

$countStmt = $db->prepare('SELECT COUNT(*) FROM likes WHERE chirp_id = :cid');
$countStmt->execute([':cid' => $chirpId]);
$likeCount = (int)$countStmt->fetchColumn();

echo json_encode(['liked' => $liked, 'like_count' => $likeCount]);
