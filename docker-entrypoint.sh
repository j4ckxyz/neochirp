#!/bin/sh
set -e

DB_FILE="${DB_PATH:-/var/www/data/chirp.db}"
DATA_DIR="$(dirname "$DB_FILE")"

# Ensure data directory exists
mkdir -p "$DATA_DIR"

# Initialise the database from the sample schema if it doesn't exist yet
if [ ! -f "$DB_FILE" ]; then
    echo "[neochirp] Database not found at $DB_FILE — initialising from schema..."
    if [ -f /var/www/html/chirp.db.sample ]; then
        cp /var/www/html/chirp.db.sample "$DB_FILE"
    fi
    chown www-data:www-data "$DB_FILE"
    echo "[neochirp] Database initialised."
fi

# Run migrations — inlined so there is no external file dependency.
# All statements use IF NOT EXISTS / OR IGNORE and are safe to repeat.
echo "[neochirp] Running migrations..."
sqlite3 "$DB_FILE" <<'SQL'
CREATE TABLE IF NOT EXISTS api_keys (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id         INTEGER NOT NULL REFERENCES users(id),
    key_hash        TEXT    NOT NULL UNIQUE,
    name            TEXT    NOT NULL DEFAULT 'My Key',
    created_at      INTEGER NOT NULL DEFAULT (strftime('%s','now')),
    last_used       INTEGER,
    reqs_hour       INTEGER NOT NULL DEFAULT 0,
    reqs_hour_reset INTEGER NOT NULL DEFAULT 0
);
CREATE TABLE IF NOT EXISTS oauth_apps (
    id                 INTEGER PRIMARY KEY AUTOINCREMENT,
    client_id          TEXT    NOT NULL UNIQUE,
    client_secret_hash TEXT    NOT NULL,
    name               TEXT    NOT NULL,
    description        TEXT,
    website            TEXT,
    redirect_uris      TEXT    NOT NULL,
    owner_user_id      INTEGER NOT NULL REFERENCES users(id),
    created_at         INTEGER NOT NULL DEFAULT (strftime('%s','now'))
);
CREATE TABLE IF NOT EXISTS oauth_codes (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    code         TEXT    NOT NULL UNIQUE,
    client_id    TEXT    NOT NULL,
    user_id      INTEGER NOT NULL REFERENCES users(id),
    redirect_uri TEXT    NOT NULL,
    expires_at   INTEGER NOT NULL,
    used         INTEGER NOT NULL DEFAULT 0
);
CREATE TABLE IF NOT EXISTS oauth_tokens (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    token_hash      TEXT    NOT NULL UNIQUE,
    client_id       TEXT    NOT NULL,
    user_id         INTEGER NOT NULL REFERENCES users(id),
    app_name        TEXT    NOT NULL DEFAULT 'OAuth App',
    created_at      INTEGER NOT NULL DEFAULT (strftime('%s','now')),
    last_used       INTEGER,
    revoked         INTEGER NOT NULL DEFAULT 0,
    reqs_hour       INTEGER NOT NULL DEFAULT 0,
    reqs_hour_reset INTEGER NOT NULL DEFAULT 0
);
CREATE TABLE IF NOT EXISTS notifications (
    id        INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id   INTEGER NOT NULL REFERENCES users(id),
    actor_id  INTEGER NOT NULL REFERENCES users(id),
    type      TEXT    NOT NULL,
    chirp_id  INTEGER REFERENCES chirps(id),
    timestamp INTEGER NOT NULL DEFAULT (strftime('%s','now')),
    read      INTEGER NOT NULL DEFAULT 0
);
CREATE INDEX IF NOT EXISTS idx_notif_user    ON notifications(user_id, timestamp DESC);
CREATE INDEX IF NOT EXISTS idx_notif_chirp   ON notifications(chirp_id);
CREATE INDEX IF NOT EXISTS idx_chirps_ts     ON chirps(timestamp DESC);
CREATE INDEX IF NOT EXISTS idx_chirps_parent ON chirps(parent);
CREATE INDEX IF NOT EXISTS idx_likes_chirp   ON likes(chirp_id);
CREATE INDEX IF NOT EXISTS idx_rechirps_chirp ON rechirps(chirp_id);
CREATE INDEX IF NOT EXISTS idx_api_keys_user ON api_keys(user_id);
SQL
echo "[neochirp] Migrations done."

# Fix permissions on data dir only
chown -R www-data:www-data "$DATA_DIR"

exec "$@"
