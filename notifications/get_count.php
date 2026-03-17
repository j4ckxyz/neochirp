<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) { echo json_encode(['count' => 0]); exit; }
$db = new PDO('sqlite:' . __DIR__ . '/../../chirp.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$stmt = $db->prepare("SELECT COUNT(DISTINCT type || '_' || chirp_id) FROM notifications WHERE user_id = :uid AND read = 0");
$stmt->execute([':uid' => (int)$_SESSION['user_id']]);
echo json_encode(['count' => (int)$stmt->fetchColumn()]);
