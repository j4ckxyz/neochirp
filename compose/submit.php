<?php
session_start();
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

try {
    // Check if the POST data is set
    if (!isset($_POST['chirpComposeText'])) {
        echo json_encode(['error' => "No data was sent with the POST request."]);
        exit;
    }

    // Check if the host is allowed (skipped in dev mode)
    if (!DEV_MODE) {
        $host = $_SERVER['HTTP_HOST'] ?? 'none';
        if ($host === 'none' || $host !== APP_DOMAIN) {
            echo json_encode(['error' => "Invalid host."]);
            exit;
        }
    }

    // Check if the user is logged in
    if (!isset($_SESSION['username'])) {
        echo json_encode(['error' => "You need to be logged in to post."]);
        exit;
    }

    $db = new PDO('sqlite:' . __DIR__ . '/../../chirp.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get the user ID from the users table
    $username = $_SESSION['username'];
    $userStmt = $db->prepare('SELECT id FROM users WHERE username = :username');
    $userStmt->bindParam(':username', $username, PDO::PARAM_STR);
    $userStmt->execute();
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['error' => "User not found."]);
        exit;
    }

    $userId = $user['id'];

    define('MAX_CHARS', 510);

    $currentTime = time();

    if (!DEV_MODE) {
        // Rate limiting (disabled in dev mode)
        $lastSubmissionTime = isset($_SESSION['last_submission_time']) ? $_SESSION['last_submission_time'] : 0;
        $attemptCount = isset($_SESSION['attempt_count']) ? $_SESSION['attempt_count'] : 0;
        $cooldownSeconds = min(10 + ($attemptCount * 10), 1800);

        if ($currentTime - $lastSubmissionTime < $cooldownSeconds) {
            $_SESSION['attempt_count'] = ++$attemptCount;
            echo json_encode(['error' => "You are posting too quickly. Slow down!"]);
            exit;
        }
        $_SESSION['attempt_count'] = 0;
    }

    // Check if chirp text is empty or exceeds maximum allowed characters
    $chirpText = trim($_POST['chirpComposeText']);
    if (empty($chirpText)) {
        echo json_encode(['error' => "Chirp cannot be empty."]);
        exit;
    }

    if (strlen($chirpText) > MAX_CHARS) {
        echo json_encode(['error' => "Chirp exceeds maximum character limit of " . MAX_CHARS . " characters."]);
        exit;
    }

    // Use prepared statements to prevent SQL injection
    $sql = "INSERT INTO chirps (chirp, user, type, parent, timestamp) VALUES (:chirp, :user, 'post', NULL, :timestamp)";
    $stmt = $db->prepare($sql);

    // Bind parameters
    $timestamp = time();
    $stmt->bindParam(':chirp', $chirpText);
    $stmt->bindParam(':user', $userId);
    $stmt->bindParam(':timestamp', $timestamp);

    if ($stmt->execute()) {
        // Introduce a slight delay (e.g., 100 milliseconds)
        usleep(100000); // 100 milliseconds (100,000 microseconds)
    
        // Store the ID of the newly inserted chirp
        $chirpId = $db->lastInsertId();
    
        // Update last submission time in session
        $_SESSION['last_submission_time'] = $currentTime;
    
        // Ensure the location header is set properly
        header('Location: /chirp/index.php?id=' . $chirpId, true, 303);
        exit;
    } else {
        // Execution failed
        echo json_encode(['error' => 'Failed to post chirp.']);
        exit;
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    exit();
} catch (Exception $e) {
    echo json_encode(['error' => 'General error: ' . $e->getMessage()]);
    exit();
}
?>
