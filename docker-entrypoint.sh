#!/bin/bash
# Lumen Capital — container entrypoint (Railway / Docker)
#
#   1. Point Apache at Railway's dynamic $PORT (it changes per-deploy; the
#      image can't hardcode it).
#   2. Wait for the database to accept connections.
#   3. Import database/schema.sql — but ONLY the first time the app boots
#      against a given database. schema.sql does DROP TABLE + CREATE TABLE,
#      so re-running it on every redeploy would wipe live user data; we
#      guard it by checking whether the `users` table already exists.
#   4. Hand off to the real CMD (apache2-foreground).

set -euo pipefail

PORT="${PORT:-8080}"
echo "==> Configuring Apache to listen on port ${PORT}"
sed -ri "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-enabled/000-default.conf

if [ -n "${DB_HOST:-}" ]; then
    export MYSQL_PWD="${DB_PASS:-}"
    DB_PORT="${DB_PORT:-3306}"

    echo "==> Waiting for database at ${DB_HOST}:${DB_PORT} ..."
    ATTEMPTS=0
    until mysqladmin ping -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" --silent >/dev/null 2>&1; do
        ATTEMPTS=$((ATTEMPTS + 1))
        if [ "$ATTEMPTS" -ge 30 ]; then
            echo "==> Database did not become reachable in time — starting anyway (the app will show a DB error page)."
            break
        fi
        sleep 2
    done

    if [ "$ATTEMPTS" -lt 30 ]; then
        TABLE_EXISTS=$(mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -N -e \
            "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${DB_NAME:-lumen_capital}' AND table_name='users'" 2>/dev/null || echo "0")

        if [ "$TABLE_EXISTS" = "0" ]; then
            echo "==> First boot against this database — importing database/schema.sql (includes demo seed data)"
            mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" < /var/www/html/database/schema.sql
            echo "==> Schema imported."
        else
            echo "==> Schema already present — skipping import (existing data preserved)."
        fi
    fi
    unset MYSQL_PWD
fi

exec "$@"