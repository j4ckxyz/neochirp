<?php
header('Content-Type: application/json');

function token_error(int $httpCode, string $error, string $description): void {
    http_response_code($httpCode);
    echo json_encode(['error' => $error, 'error_description' => $description]);
    exit;
}

// Accept both JSON and form-encoded bodies
$rawBody = file_get_contents('php://input');
$json = json_decode($rawBody, true);

if (is_array($json)) {
    $grantType    = $json['grant_type']    ?? '';
    $code         = $json['code']          ?? '';
    $redirectUri  = $json['redirect_uri']  ?? '';
    $clientId     = $json['client_id']     ?? '';
    $clientSecret = $json['client_secret'] ?? '';
} else {
    $grantType    = $_POST['grant_type']    ?? '';
    $code         = $_POST['code']          ?? '';
    $redirectUri  = $_POST['redirect_uri']  ?? '';
    $clientId     = $_POST['client_id']     ?? '';
    $clientSecret = $_POST['client_secret'] ?? '';
}

// Validate all fields present
if (!$grantType || !$code || !$redirectUri || !$clientId || !$clientSecret) {
    token_error(400, 'invalid_request', 'Missing required parameter(s): grant_type, code, redirect_uri, client_id, client_secret');
}

// grant_type must be authorization_code
if ($grantType !== 'authorization_code') {
    token_error(400, 'unsupported_grant_type', 'Only authorization_code grant type is supported');
}

$db = new PDO('sqlite:' . __DIR__ . '/../../chirp.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Look up app
$appStmt = $db->prepare('SELECT * FROM oauth_apps WHERE client_id = :cid');
$appStmt->execute([':cid' => $clientId]);
$app = $appStmt->fetch(PDO::FETCH_ASSOC);
if (!$app) {
    token_error(401, 'invalid_client', 'Unknown client_id');
}

// Validate client_secret
if (hash('sha256', $clientSecret) !== $app['client_secret_hash']) {
    token_error(401, 'invalid_client', 'Invalid client_secret');
}

// Look up code
$codeStmt = $db->prepare(
    "SELECT * FROM oauth_codes WHERE code = :code AND client_id = :cid AND used = 0 AND expires_at > strftime('%s','now')"
);
$codeStmt->execute([':code' => $code, ':cid' => $clientId]);
$codeRow = $codeStmt->fetch(PDO::FETCH_ASSOC);
if (!$codeRow) {
    token_error(400, 'invalid_grant', 'Authorization code is invalid, expired, or already used');
}

// Validate redirect_uri matches
if ($codeRow['redirect_uri'] !== $redirectUri) {
    token_error(400, 'invalid_grant', 'redirect_uri does not match the one used during authorization');
}

// Mark code as used
$db->prepare('UPDATE oauth_codes SET used = 1 WHERE id = :id')->execute([':id' => $codeRow['id']]);

// Generate token
$raw = 'chirpoa_' . bin2hex(random_bytes(20));
$tokenHash = hash('sha256', $raw);
$now = time();

$ins = $db->prepare(
    'INSERT INTO oauth_tokens (token_hash, client_id, user_id, app_name, created_at) VALUES (:th, :cid, :uid, :appname, :now)'
);
$ins->execute([
    ':th'      => $tokenHash,
    ':cid'     => $clientId,
    ':uid'     => (int)$codeRow['user_id'],
    ':appname' => $app['name'],
    ':now'     => $now,
]);

echo json_encode([
    'access_token' => $raw,
    'token_type'   => 'bearer',
    'scope'        => 'read write',
]);
