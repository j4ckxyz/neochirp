<?php
// AJAX validation endpoint — returns JSON, no HTML
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

try {
    $db = new PDO('sqlite:' . DB_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($action === 'username') {
        $username = trim($_GET['username'] ?? '');
        if ($username === '') { echo json_encode(['ok' => false, 'msg' => '']); exit; }
        if (!preg_match('/^[A-Za-z0-9_]{1,30}$/', $username)) {
            echo json_encode(['ok' => false, 'msg' => 'Only letters, numbers and underscores. Max 30 chars.']);
            exit;
        }
        $stmt = $db->prepare('SELECT id FROM users WHERE LOWER(username) = LOWER(:u)');
        $stmt->execute(['u' => $username]);
        if ($stmt->fetch()) {
            echo json_encode(['ok' => false, 'msg' => 'Username already taken.']);
        } else {
            echo json_encode(['ok' => true, 'msg' => 'Available!']);
        }
        exit;
    }

    if ($action === 'code' && !DEV_MODE) {
        $code = strtoupper(trim($_GET['code'] ?? ''));
        if ($code === '') { echo json_encode(['ok' => false, 'msg' => '']); exit; }
        $stmt = $db->prepare('SELECT id, reservedFor FROM invites WHERE UPPER(invite) = :c');
        $stmt->execute(['c' => $code]);
        $invite = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$invite) {
            echo json_encode(['ok' => false, 'msg' => 'Invalid invite code.']);
        } else {
            echo json_encode(['ok' => true, 'msg' => 'Valid!', 'reservedFor' => $invite['reservedFor']]);
        }
        exit;
    }

    echo json_encode(['ok' => false, 'msg' => 'Unknown action.']);
} catch (PDOException $e) {
    error_log('Validate error: ' . $e->getMessage());
    echo json_encode(['ok' => false, 'msg' => 'Server error.']);
}
