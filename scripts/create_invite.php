#!/usr/bin/env php
<?php
/**
 * NeoChirp — Invite Code Generator
 * Usage: php scripts/create_invite.php [count]
 *
 * Generates one or more single-use invite codes and stores them in the DB.
 * Share each code with exactly one person you want to let in.
 *
 * Examples:
 *   php scripts/create_invite.php        # generate 1 code
 *   php scripts/create_invite.php 5      # generate 5 codes
 *
 * In Docker:
 *   docker-compose exec app php /var/www/html/scripts/create_invite.php 3
 */

// ── Bootstrap ─────────────────────────────────────────────────────────────────
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('This script must be run from the command line.');
}

$rootDir = dirname(__DIR__);
$cfgPath = $rootDir . '/config.php';
if (file_exists($cfgPath)) {
    require_once $cfgPath;
}

$dbPath = defined('DB_PATH') ? DB_PATH : $rootDir . '/../chirp.db';

if (!file_exists($dbPath)) {
    fwrite(STDERR, "Error: database not found at $dbPath\n");
    fwrite(STDERR, "Tip: run this after the container/server has started at least once.\n");
    exit(1);
}

$count = isset($argv[1]) ? max(1, min(100, (int)$argv[1])) : 1;

// ── Connect ────────────────────────────────────────────────────────────────────
try {
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    fwrite(STDERR, "DB error: " . $e->getMessage() . "\n");
    exit(1);
}

// Ensure invites table exists (it should already, but just in case)
$db->exec("CREATE TABLE IF NOT EXISTS invites (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT UNIQUE NOT NULL,
    created_at INTEGER NOT NULL DEFAULT (strftime('%s','now')),
    used_by TEXT
)");

// ── Generate ───────────────────────────────────────────────────────────────────
$stmt = $db->prepare('INSERT INTO invites (code) VALUES (:code)');

echo "\n  NeoChirp Invite Codes\n";
echo "  ─────────────────────\n";

for ($i = 0; $i < $count; $i++) {
    // Format: XXXX-XXXX-XXXX (human-friendly, uppercase)
    $raw  = strtoupper(bin2hex(random_bytes(6)));
    $code = substr($raw, 0, 4) . '-' . substr($raw, 4, 4) . '-' . substr($raw, 8, 4);

    try {
        $stmt->execute([':code' => $code]);
        echo "  $code\n";
    } catch (PDOException $e) {
        // Collision (astronomically unlikely) — retry
        $i--;
    }
}

echo "\n  Share each code with exactly one person.\n";
echo "  Codes are single-use and are burned on signup.\n\n";
