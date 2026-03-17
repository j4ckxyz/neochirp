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

$name         = trim($_POST['name']          ?? '');
$description  = trim($_POST['description']   ?? '');
$website      = trim($_POST['website']       ?? '');
$redirectUris = trim($_POST['redirect_uris'] ?? '');

if (empty($name) || empty($redirectUris)) {
    header('Location: /settings/developer/?error=missing_fields');
    exit;
}

$name = substr($name, 0, 60);
$description = substr($description, 0, 200);

$db = new PDO('sqlite:' . __DIR__ . '/../../../chirp.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$clientId  = bin2hex(random_bytes(16));
$rawSecret = 'chirpapp_' . bin2hex(random_bytes(24));
$secretHash = hash('sha256', $rawSecret);
$now = time();

$stmt = $db->prepare(
    'INSERT INTO oauth_apps (client_id, client_secret_hash, name, description, website, redirect_uris, owner_user_id, created_at)
     VALUES (:cid, :csh, :name, :desc, :web, :ruris, :uid, :now)'
);
$stmt->execute([
    ':cid'  => $clientId,
    ':csh'  => $secretHash,
    ':name' => $name,
    ':desc' => $description,
    ':web'  => $website,
    ':ruris'=> $redirectUris,
    ':uid'  => (int)$_SESSION['user_id'],
    ':now'  => $now,
]);

$_SESSION['new_oauth_app'] = [
    'client_id'     => $clientId,
    'client_secret' => $rawSecret,
    'name'          => $name,
];

header('Location: /settings/developer/');
exit;
