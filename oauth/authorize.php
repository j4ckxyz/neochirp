<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config.php';

$db = new PDO('sqlite:' . __DIR__ . '/../../chirp.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$clientId     = $_GET['client_id']     ?? '';
$redirectUri  = $_GET['redirect_uri']  ?? '';
$state        = $_GET['state']         ?? '';
$responseType = $_GET['response_type'] ?? '';

// Helper: show a plain error page without redirecting
function showError(string $title, string $msg): void {
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Error – Chirp OAuth</title>
<style>body{background:#0e0e10;color:#e4e4e7;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;}
.card{background:#18181b;border:1px solid #2a2a2f;border-radius:16px;padding:36px;width:100%;max-width:420px;text-align:center;}
h2{margin-top:0;color:#f87171;}a{color:#1AD063;}</style></head><body>
<div class="card"><h2>' . htmlspecialchars($title) . '</h2><p>' . htmlspecialchars($msg) . '</p>
<p><a href="/">Back to Chirp</a></p></div></body></html>';
    exit;
}

// 1. Validate client_id
$appStmt = $db->prepare('SELECT * FROM oauth_apps WHERE client_id = :cid');
$appStmt->execute([':cid' => $clientId]);
$app = $appStmt->fetch(PDO::FETCH_ASSOC);
if (!$app) {
    showError('Unknown Application', 'The client_id provided does not match any registered application.');
}

// 2. Validate redirect_uri
$allowedUris = array_filter(array_map('trim', explode("\n", $app['redirect_uris'])));
if (!in_array($redirectUri, $allowedUris, true)) {
    showError('Invalid Redirect URI', 'The redirect_uri provided is not registered for this application.');
}

// 3. response_type must be "code"
if ($responseType !== 'code') {
    header('Location: ' . $redirectUri . '?error=unsupported_response_type&state=' . urlencode($state));
    exit;
}

// 4. User must be logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /signin/?return=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// 5. Handle POST (approve / deny)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'deny') {
        header('Location: ' . $redirectUri . '?error=access_denied&state=' . urlencode($state));
        exit;
    }
    if ($action === 'approve') {
        $code = bin2hex(random_bytes(32));
        $expiresAt = time() + 600;
        $ins = $db->prepare('INSERT INTO oauth_codes (code, client_id, user_id, redirect_uri, expires_at, used) VALUES (:code, :cid, :uid, :ruri, :exp, 0)');
        $ins->execute([
            ':code' => $code,
            ':cid'  => $clientId,
            ':uid'  => (int)$_SESSION['user_id'],
            ':ruri' => $redirectUri,
            ':exp'  => $expiresAt,
        ]);
        header('Location: ' . $redirectUri . '?code=' . urlencode($code) . '&state=' . urlencode($state));
        exit;
    }
}

// 6. Show authorization screen (GET)
$username   = htmlspecialchars($_SESSION['username']   ?? '');
$name       = htmlspecialchars($_SESSION['name']       ?? $username);
$profilePic = htmlspecialchars($_SESSION['profile_pic'] ?? '/src/images/users/guest/user.svg');
$appName    = htmlspecialchars($app['name']);
$appDesc    = htmlspecialchars($app['description'] ?? '');
$appWebsite = htmlspecialchars($app['website'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $appName; ?> – Authorization – Chirp</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body { background: #0e0e10; color: #e4e4e7; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 16px; }
        .card { background: #18181b; border: 1px solid #2a2a2f; border-radius: 16px; padding: 36px; width: 100%; max-width: 420px; }
        .logo { display: block; margin: 0 auto 20px; width: 48px; height: 48px; }
        h1 { font-size: 1.25rem; margin: 0 0 4px; text-align: center; }
        .subtitle { font-size: 0.9rem; color: #a1a1aa; text-align: center; margin: 0 0 24px; }
        .subtitle a { color: #1AD063; text-decoration: none; }
        .subtitle a:hover { text-decoration: underline; }
        .divider { border: none; border-top: 1px solid #2a2a2f; margin: 20px 0; }
        .user-row { display: flex; align-items: center; gap: 12px; background: #0e0e10; border-radius: 10px; padding: 10px 14px; margin-bottom: 20px; }
        .user-row img { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
        .user-row .uname { font-size: 0.9rem; color: #a1a1aa; }
        .permissions { margin: 0 0 24px; padding: 0; list-style: none; }
        .permissions li { padding: 7px 0; font-size: 0.93rem; display: flex; align-items: center; gap: 8px; }
        .permissions li::before { content: '✓'; color: #1AD063; font-weight: bold; flex-shrink: 0; }
        .actions { display: flex; gap: 12px; }
        .btn { flex: 1; padding: 11px; border-radius: 10px; border: none; font-size: 1rem; font-weight: 600; cursor: pointer; transition: opacity 0.15s; }
        .btn:hover { opacity: 0.85; }
        .btn-approve { background: #1AD063; color: #000; }
        .btn-deny { background: #27272a; color: #e4e4e7; border: 1px solid #3f3f46; }
        .section-label { font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.05em; color: #71717a; margin-bottom: 10px; }
    </style>
</head>
<body>
<div class="card">
    <img class="logo" src="/src/images/icons/chirp.svg" alt="Chirp">
    <h1><?php echo $appName; ?> wants access</h1>
    <p class="subtitle">
        <?php if ($appWebsite): ?>
            <a href="<?php echo $appWebsite; ?>" target="_blank" rel="noopener noreferrer"><?php echo $appWebsite; ?></a>
        <?php endif; ?>
        <?php if ($appDesc): ?>
            <?php if ($appWebsite): ?><br><?php endif; ?>
            <?php echo $appDesc; ?>
        <?php endif; ?>
    </p>

    <p class="section-label">Authorizing as</p>
    <div class="user-row">
        <img src="<?php echo $profilePic; ?>" alt="<?php echo $username; ?>">
        <div>
            <div><strong><?php echo $name; ?></strong></div>
            <div class="uname">@<?php echo $username; ?></div>
        </div>
    </div>

    <p class="section-label">What this app can do:</p>
    <ul class="permissions">
        <li>Read your timeline</li>
        <li>Post chirps on your behalf</li>
        <li>Like and rechirp posts</li>
        <li>Read your notifications</li>
    </ul>

    <form method="POST">
        <input type="hidden" name="client_id"    value="<?php echo htmlspecialchars($clientId); ?>">
        <input type="hidden" name="redirect_uri" value="<?php echo htmlspecialchars($redirectUri); ?>">
        <input type="hidden" name="state"        value="<?php echo htmlspecialchars($state); ?>">
        <div class="actions">
            <button type="submit" name="action" value="approve" class="btn btn-approve">Authorize</button>
            <button type="submit" name="action" value="deny"    class="btn btn-deny">Cancel</button>
        </div>
    </form>
</div>
</body>
</html>
