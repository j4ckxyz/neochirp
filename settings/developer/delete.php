<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /signin/');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /settings/developer/');
    exit;
}

$appId  = (int)($_POST['app_id'] ?? 0);
$userId = (int)$_SESSION['user_id'];

if (!$appId) {
    header('Location: /settings/developer/');
    exit;
}

$db = new PDO('sqlite:' . DB_PATH);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Verify ownership
$stmt = $db->prepare('SELECT * FROM oauth_apps WHERE id = :id AND owner_user_id = :uid');
$stmt->execute([':id' => $appId, ':uid' => $userId]);
$app = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$app) {
    header('Location: /settings/developer/');
    exit;
}

// Delete tokens first, then the app
$db->prepare('DELETE FROM oauth_tokens WHERE client_id = :cid')->execute([':cid' => $app['client_id']]);
$db->prepare('DELETE FROM oauth_codes  WHERE client_id = :cid')->execute([':cid' => $app['client_id']]);
$db->prepare('DELETE FROM oauth_apps   WHERE id = :id')->execute([':id' => $appId]);

header('Location: /settings/developer/');
exit;
