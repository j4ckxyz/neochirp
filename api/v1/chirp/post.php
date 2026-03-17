<?php
define('API_WRITE', true);

 }

require_once __DIR__ . '/../auth.php';
$apiUser = chirp_api_auth();
$db = chirp_api_db();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') chirp_api_error(405, 'Method not allowed');

$body = json_decode(file_get_contents('php://input'), true);
$text = trim($body['text'] ?? '');
if (empty($text)) chirp_api_error(400, 'text is required');
if (strlen($text) > 510) chirp_api_error(400, 'text exceeds 510 characters');

$userId = (int)$apiUser['user_id'];
$now = time();
$via = substr($apiUser['app_name'] ?? 'Chirp API', 0, 100);

$stmt = $db->prepare("INSERT INTO chirps (chirp, user, timestamp, type, via) VALUES (:chirp, :user, :ts, 'post', :via)");
$stmt->execute([':chirp' => $text, ':user' => $userId, ':ts' => $now, ':via' => $via]);
$chirpId = (int)$db->lastInsertId();

echo json_encode(['id' => $chirpId, 'ok' => true]);
