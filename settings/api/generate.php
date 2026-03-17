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
$name = trim($_POST['name'] ?? 'Default');
if (empty($name)) $name = 'Default';
$name = substr($name, 0, 100);

$raw = 'chirp_' . bin2hex(random_bytes(20));
$hash = hash('sha256', $raw);

$db = new PDO('sqlite:' . __DIR__ . '/../../../chirp.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $db->prepare('INSERT INTO api_keys (user_id, key_hash, name, created_at) VALUES (:uid, :hash, :name, :ts)');
$stmt->execute([':uid' => $userId, ':hash' => $hash, ':name' => $name, ':ts' => time()]);

$_SESSION['new_api_key'] = $raw;
header('Location: /settings/api/');
exit;
