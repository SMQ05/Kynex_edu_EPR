#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

cp -f .env.docker .env

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache

echo "Waiting for PostgreSQL..."
until php -r '
    $host = getenv("DB_HOST") ?: "db";
    $port = getenv("DB_PORT") ?: "5432";
    $db = getenv("DB_DATABASE") ?: "kynexedu_central";
    $user = getenv("DB_USERNAME") ?: "kynexedu";
    $pass = getenv("DB_PASSWORD") ?: "kynexedu123";
    try {
        new PDO("pgsql:host={$host};port={$port};dbname={$db}", $user, $pass);
        exit(0);
    } catch (Throwable $e) {
        fwrite(STDERR, $e->getMessage() . PHP_EOL);
        exit(1);
    }
'; do
    sleep 2
done

php artisan config:clear --ansi || true

if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
    php artisan key:generate --force
fi

php artisan package:discover --ansi

if [ "${RUN_MIGRATIONS_AND_SEED:-false}" = "true" ]; then
    php artisan migrate --force
    php artisan db:seed --force
    php artisan optimize:clear
    php artisan kynex:ensure-dev-demo
    php artisan storage:link || true
fi

echo "Tooling inside container:"
php --version | head -n 1
composer --version
node --version
npm --version
psql --version
