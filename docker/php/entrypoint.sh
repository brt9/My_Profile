#!/bin/sh
set -eu

cd /var/www
mkdir -p storage/logs bootstrap/cache

if [ ! -f vendor/autoload.php ] || [ composer.lock -nt vendor/composer/installed.php ]; then
    composer install --no-interaction --prefer-dist --optimize-autoloader
else
    composer dump-autoload --no-interaction --optimize
fi

if [ -f .env ]; then
    if ! grep -Eq '^APP_KEY=.+$' .env; then
        php artisan key:generate --no-interaction
    fi

    if [ "${APP_ENV:-local}" = "production" ]; then
        php artisan optimize
    else
        php artisan optimize:clear
    fi

    if [ ! -L public/storage ]; then
        php artisan storage:link --no-interaction || true
    fi
fi

chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache

exec "$@"
