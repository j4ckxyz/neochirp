<?php
session_start();
require_once __DIR__ . '/../../config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /signin/');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /settings/api/');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$keyId = (int)($_POST['key_id'] ?? 0);

if ($keyId > 0) {
    $db = new PDO('sqlite:' . DB_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $db->prepare('DELETE FROM api_keys WHERE id = :id AND user_id = :uid');
    $stmt->execute([':id' => $keyId, ':uid' => $userId]);
}

header('Location: /settings/api/');
exit;
