#!/bin/sh
set -e

DB_FILE="${DB_PATH:-/var/www/chirp.db}"

# Initialise the database from the sample schema if it doesn't exist yet
if [ ! -f "$DB_FILE" ]; then
    echo "[neochirp] Database not found at $DB_FILE — initialising from schema..."
    if [ -f /var/www/html/chirp.db.sample ]; then
        cp /var/www/html/chirp.db.sample "$DB_FILE"
    else
        # Create minimal schema from scratch
        sqlite3 "$DB_FILE" "$(cat /var/www/html/schema.sql 2>/dev/null || echo '')"
    fi
    chown www-data:www-data "$DB_FILE"
    echo "[neochirp] Database initialised."
fi

# Fix permissions
chown -R www-data:www-data /var/www

exec "$@"
