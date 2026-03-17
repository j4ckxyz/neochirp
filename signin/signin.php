<?php
// Set session cookie parameters
session_set_cookie_params([
    'lifetime' => 3456000, // 40 days in seconds
    'path' => '/', // Will use the same path
    'domain' => '', // Will use the same domain
    'secure' => true, 
    'httponly' => true, // Protects against XSS attacks
    'samesite' => 'Lax'
]);
session_start();

// Send security headers
header('Content-Security-Policy: default-src \'self\'');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

require_once __DIR__ . '/../../config.php';

// Function to connect to the database
function getDatabaseConnection() {
    $db = new PDO('sqlite:' . DB_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $db;
}

// Function to verify the password
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Function to sanitize username input
function sanitizeUsername($input) {
    // Remove leading '@' symbol
    if (substr($input, 0, 1) === '@') {
        $input = substr($input, 1);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function signin_error(string $msg): void {
    http_response_code(401);
    $safe = htmlspecialchars($msg, ENT_QUOTES, 'UTF-8');
    echo <<<HTML
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <link href="/src/styles/styles.css" rel="stylesheet">
        <link href="/src/styles/timeline.css" rel="stylesheet">
        <title>Sign in failed — NeoChirp</title>
    </head>
    <body>
        <main>
            <div id="feed" class="settingsPageContainer">
                <div id="iconChirp">
                    <img src="/src/images/icons/chirp.svg" alt="Chirp">
                </div>
                <div class="title"><p class="selected">Sign in failed</p></div>
                <div id="noMoreChirps">
                    <p class="subText">$safe</p>
                    <a class="followButton following" href="/signin/" style="margin-top:16px;display:inline-block;">Try again</a>
                </div>
            </div>
        </main>
    </body>
    </html>
    HTML;
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Retrieve and sanitize user input
        $username = sanitizeUsername($_POST['username']);
        $password = $_POST['pWord']; // Do not sanitize the password

        // Validate user input
        if (empty($username) || empty($password)) {
            signin_error('Please fill in both fields.');
        }

        // Convert username to lowercase for case-insensitive comparison
        $usernameLower = strtolower($username);

        // Connect to the database
        $db = getDatabaseConnection();

        // Prepare and execute the query with case-insensitive comparison
        $stmt = $db->prepare('SELECT * FROM users WHERE LOWER(username) = :username');
        $stmt->bindParam(':username', $usernameLower, PDO::PARAM_STR);
        $stmt->execute();

        // Fetch user data from the database
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Check if the user exists and if the password is correct
        if ($user && verifyPassword($password, $user['password_hash'])) {
            // Regenerate session ID to prevent session fixation attacks
            session_regenerate_id(true);

            // Password is correct, start a new session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['profile_pic'] = $user['profilePic']; // Fetch profile pic path
            $_SESSION['is_verified'] = $user['isVerified']; // Fetch verification status

            // Extend session cookie lifetime
            setcookie(session_name(), session_id(), [
                'expires' => time() + 604800, 
                'path' => '/', 
                'domain' => '', 
                'secure' => true, 
                'httponly' => true, 
                'samesite' => 'Lax'
            ]);

            // Redirect to the home page or user dashboard
            header('Location: /');
            exit();
        } else {
            signin_error('Wrong username or password. Please try again.');
        }
    } catch (PDOException $e) {
        error_log('Signin DB error: ' . $e->getMessage());
        signin_error('Something went wrong on our end. Please try again in a moment.');
    }
} else {
    // If the request is not a POST request, redirect to the sign-in page
    header('Location: /signin/');
    exit();
}
?>
