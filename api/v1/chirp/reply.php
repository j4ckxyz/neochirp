<?php
define('API_WRITE', true);

 }

require_once __DIR__ . '/../auth.php';
$apiUser = chirp_api_auth();
$db = chirp_api_db();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') chirp_api_error(405, 'Method not allowed');

$body = json_decode(file_get_contents('php://input'), true);
$text = trim($body['text'] ?? '');
$parentId = isset($body['parent_id']) ? (int)$body['parent_id'] : 0;

if (empty($text)) chirp_api_error(400, 'text is required');
if (strlen($text) > 510) chirp_api_error(400, 'text exceeds 510 characters');
if (!$parentId) chirp_api_error(400, 'parent_id is required');

// Verify parent exists
$pCheck = $db->prepare('SELECT id FROM chirps WHERE id = :id');
$pCheck->execute([':id' => $parentId]);
if (!$pCheck->fetchColumn()) chirp_api_error(404, 'Parent chirp not found');

$userId = (int)$apiUser['user_id'];
$now = time();
$via = substr($apiUser['app_name'] ?? 'Chirp API', 0, 100);

$stmt = $db->prepare("INSERT INTO chirps (chirp, user, timestamp, type, parent, via) VALUES (:chirp, :user, :ts, 'reply', :parent, :via)");
$stmt->execute([':chirp' => $text, ':user' => $userId, ':ts' => $now, ':parent' => $parentId, ':via' => $via]);
$chirpId = (int)$db->lastInsertId();

// Notify parent post owner
$pStmt = $db->prepare('SELECT user FROM chirps WHERE id = :pid');
$pStmt->execute([':pid' => $parentId]);
$parentOwner = $pStmt->fetchColumn();
if ($parentOwner && (int)$parentOwner !== $userId) {
    $nStmt = $db->prepare('INSERT INTO notifications (user_id, actor_id, type, chirp_id, timestamp) VALUES (:uid, :actor, :type, :cid, :ts)');
    $nStmt->execute([':uid' => $parentOwner, ':actor' => $userId, ':type' => 'reply', ':cid' => $chirpId, ':ts' => $now]);
}

echo json_encode(['id' => $chirpId, 'ok' => true]);
