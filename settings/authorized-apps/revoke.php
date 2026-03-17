<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /signin/');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /settings/authorized-apps/');
    exit;
}

$tokenId = (int)($_POST['token_id'] ?? 0);
$userId  = (int)$_SESSION['user_id'];

if (!$tokenId) {
    header('Location: /settings/authorized-apps/');
    exit;
}

$db = new PDO('sqlite:' . DB_PATH);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Verify the token belongs to the current user before revoking
$stmt = $db->prepare('SELECT id FROM oauth_tokens WHERE id = :id AND user_id = :uid AND revoked = 0');
$stmt->execute([':id' => $tokenId, ':uid' => $userId]);
if ($stmt->fetchColumn()) {
    $db->prepare('UPDATE oauth_tokens SET revoked = 1 WHERE id = :id')->execute([':id' => $tokenId]);
}

header('Location: /settings/authorized-apps/');
exit;
