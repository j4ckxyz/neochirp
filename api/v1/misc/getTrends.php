<?php

header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../include/trends.php';

try {
    $db = new PDO('sqlite:' . __DIR__ . '/../../../../chirp.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $maxResults = isset($_GET['limit']) ? min((int)$_GET['limit'], 25) : 10;
    $trends = compute_trends($db, $maxResults);

    header('Content-Type: application/json');
    echo json_encode(['trends' => $trends, 'period' => '7 days', 'ollama' => OLLAMA_EMBED_MODEL]);

} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to compute trends.']);
}
