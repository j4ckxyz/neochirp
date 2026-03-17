<?php
/**
 * NeoChirp Configuration
 * All sensitive values are read from environment variables.
 * Copy .env.example → .env and fill in your values.
 * In production (Docker) these are injected by docker-compose.
 */

// ── Core ──────────────────────────────────────────────────────────────────────
// Your public domain (no trailing slash, no scheme)
define('APP_DOMAIN',   getenv('APP_DOMAIN')   ?: 'localhost:8080');
define('APP_HTTPS',    getenv('APP_HTTPS')    === 'true');
define('APP_URL',      (APP_HTTPS ? 'https' : 'http') . '://' . APP_DOMAIN);

// ── Dev Mode ──────────────────────────────────────────────────────────────────
// When true: no invite codes, no rate limits, dev banner, /dev/ console.
// NEVER set true in production.
define('DEV_MODE', getenv('DEV_MODE') === 'true');

// ── Database ──────────────────────────────────────────────────────────────────
// Path to the SQLite DB. In Docker this is mounted at /var/www/chirp.db.
// Default: one directory above the project root (legacy local dev layout).
define('DB_PATH', getenv('DB_PATH') ?: __DIR__ . '/../chirp.db');

// ── Ollama (semantic trends) ──────────────────────────────────────────────────
// Pull the model first: ollama pull qwen3-embedding:0.6b
// In Docker on Linux, Ollama on the host is reachable at http://172.17.0.1:11434
define('OLLAMA_HOST',        getenv('OLLAMA_HOST')        ?: 'http://localhost:11434');
define('OLLAMA_EMBED_MODEL', getenv('OLLAMA_EMBED_MODEL') ?: 'qwen3-embedding:0.6b');
define('OLLAMA_TIMEOUT',     (int)(getenv('OLLAMA_TIMEOUT') ?: 10));

// ── Error reporting ───────────────────────────────────────────────────────────
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);
