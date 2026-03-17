<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) { echo json_encode(['ok' => false]); exit; }
$db = new PDO('sqlite:' . __DIR__ . '/../../chirp.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->prepare("UPDATE notifications SET read = 1 WHERE user_id = :uid")
   ->execute([':uid' => (int)$_SESSION['user_id']]);
echo json_encode(['ok' => true]);
