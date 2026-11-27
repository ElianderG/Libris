#!/bin/sh
set -eu

if [ -z "${APP_KEY:-}" ]; then
    export APP_KEY="base64:$(php -r 'echo base64_encode(random_bytes(32));')"
fi

php artisan migrate --force --no-interaction

exec php artisan serve --host=0.0.0.0 --port=8000 --no-reload
