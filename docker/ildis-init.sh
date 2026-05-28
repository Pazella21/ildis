#!/bin/sh -e
# ILDIS database initialization script
# Runs as a oneshot s6-overlay service before php-fpm starts.
# Waits for MySQL to be ready, then applies any pending Yii2 database migrations.

s6-echo "[ildis-init] Waiting for MySQL to be ready..."

MAX_RETRIES=30
RETRY_COUNT=0

while [ $RETRY_COUNT -lt $MAX_RETRIES ]; do
    if php /var/www/yii migrate/history 1 --interactive=0 >/dev/null 2>&1; then
        s6-echo "[ildis-init] MySQL is ready."
        break
    fi
    RETRY_COUNT=$((RETRY_COUNT + 1))
    s6-echo "[ildis-init] MySQL not ready, retrying in 2s... ($RETRY_COUNT/$MAX_RETRIES)"
    sleep 2
done

if [ $RETRY_COUNT -ge $MAX_RETRIES ]; then
    s6-echo "[ildis-init] WARNING: MySQL not ready after $MAX_RETRIES attempts."
    s6-echo "[ildis-init] Application will start without running migrations."
    s6-echo "[ildis-init] Run 'php /var/www/yii migrate' manually when MySQL is available."
    exit 0
fi

s6-echo "[ildis-init] Applying database migrations..."

if php /var/www/yii migrate/up --interactive=0; then
    s6-echo "[ildis-init] Database migrations applied successfully."
else
    s6-echo "[ildis-init] WARNING: Database migrations failed."
    s6-echo "[ildis-init] Application will start. Run migrate manually when DB is ready:"
    s6-echo "[ildis-init]   php /var/www/yii migrate/up --interactive=0"
fi