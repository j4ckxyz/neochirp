<?php
/**
 * API authentication middleware.
 * Include this file at the top of any API endpoint.
 * Defines: $apiUser (array with user_id, username, app_name), $apiDb (PDO)
 * Rate limits: 300 req/hour reads, 100 req/hour writes (define API_WRITE before including)
 */

function chirp_api_db(): PDO {
    static $db = null;
    if ($db === null) {
        $path = defined('DB_PATH') ? DB_PATH : __DIR__ . '/../../../chirp.db';
        $db = new PDO('sqlite:' . $path);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    return $db;
}

function chirp_api_cors(): void {
    $domain = defined('APP_DOMAIN') ? APP_DOMAIN : 'localhost:8080';
    $https  = defined('APP_HTTPS') && APP_HTTPS;
    $allowed = [
        ($https ? 'https' : 'http') . '://' . $domain,
        'https://' . $domain,
        'http://' . $domain,
    ];
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if ($origin && in_array($origin, $allowed, true)) {
        header("Access-Control-Allow-Origin: $origin");
    }
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
}

function chirp_api_error(int $code, string $msg): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode(['error' => $msg]);
    exit;
}

function chirp_api_auth(): array {
    chirp_api_cors();
    header('Content-Type: application/json');
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

    $now = time();
    $hourBucket = (int)($now / 3600) * 3600;
    $isWrite = defined('API_WRITE') && API_WRITE;
    $limit = $isWrite ? 100 : 300;
    $db = chirp_api_db();

    // chirp_ = API key
    if (preg_match('/^Bearer (chirp_[a-f0-9]{40})$/', $auth, $m)) {
        $hash = hash('sha256', $m[1]);
        $stmt = $db->prepare('SELECT k.*, u.username, u.name, u.profilePic FROM api_keys k JOIN users u ON u.id = k.user_id WHERE k.key_hash = :h');
        $stmt->execute([':h' => $hash]);
        $key = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$key) chirp_api_error(401, 'Invalid API key');

        if ((int)$key['reqs_hour_reset'] < $hourBucket) {
            $db->prepare('UPDATE api_keys SET reqs_hour = 1, reqs_hour_reset = :r, last_used = :n WHERE id = :id')
               ->execute([':r' => $hourBucket, ':n' => $now, ':id' => $key['id']]);
        } else {
            if ((int)$key['reqs_hour'] >= $limit) {
                chirp_api_error(429, "Rate limit exceeded ({$limit} req/hour). Resets at " . date('H:i', $hourBucket + 3600) . ' UTC');
            }
            $db->prepare('UPDATE api_keys SET reqs_hour = reqs_hour + 1, last_used = :n WHERE id = :id')
               ->execute([':n' => $now, ':id' => $key['id']]);
        }

        $key['app_name'] = $key['name'] ?? 'Chirp API';
        return $key;
    }

    // chirpoa_ = OAuth token
    if (preg_match('/^Bearer (chirpoa_[a-f0-9]{40})$/', $auth, $m)) {
        $hash = hash('sha256', $m[1]);
        $stmt = $db->prepare(
            'SELECT t.*, u.username, u.name, u.profilePic FROM oauth_tokens t JOIN users u ON u.id = t.user_id WHERE t.token_hash = :h AND t.revoked = 0'
        );
        $stmt->execute([':h' => $hash]);
        $token = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$token) chirp_api_error(401, 'Invalid or revoked OAuth token');

        if ((int)$token['reqs_hour_reset'] < $hourBucket) {
            $db->prepare('UPDATE oauth_tokens SET reqs_hour = 1, reqs_hour_reset = :r, last_used = :n WHERE id = :id')
               ->execute([':r' => $hourBucket, ':n' => $now, ':id' => $token['id']]);
        } else {
            if ((int)$token['reqs_hour'] >= $limit) {
                chirp_api_error(429, "Rate limit exceeded ({$limit} req/hour). Resets at " . date('H:i', $hourBucket + 3600) . ' UTC');
            }
            $db->prepare('UPDATE oauth_tokens SET reqs_hour = reqs_hour + 1, last_used = :n WHERE id = :id')
               ->execute([':n' => $now, ':id' => $token['id']]);
        }

        return $token;
    }

    chirp_api_error(401, 'Missing or invalid token. Use Authorization: Bearer chirp_... (API key) or chirpoa_... (OAuth token)');
}
