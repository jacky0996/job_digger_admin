#!/bin/sh
set -e

cd /var/www

# 1. 若沒 .env 就 copy 範本
if [ ! -f .env ]; then
  cp .env.example .env
fi

# 2. 修權限(volume mount 進來時 host 權限可能不對)
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

# 3. composer install(若 vendor/ 不存在或被 mount 蓋掉)
if [ ! -d vendor ] || [ ! -f vendor/autoload.php ]; then
  composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# 4. APP_KEY 為空就產一個
if ! grep -q "^APP_KEY=base64" .env 2>/dev/null && ! grep -q "^APP_KEY=." .env 2>/dev/null; then
  php artisan key:generate --ansi
fi

# 5. 等 DB 起來(host.docker.internal:3308 是 job-digger 的 MariaDB)
DB_HOST="${DB_HOST:-host.docker.internal}"
DB_PORT="${DB_PORT:-3308}"
echo "Waiting for DB at ${DB_HOST}:${DB_PORT}..."
for i in $(seq 1 30); do
  if nc -z "$DB_HOST" "$DB_PORT" 2>/dev/null; then
    echo "DB is up"
    break
  fi
  sleep 1
done

# 6. migrate(只建 users 表給 SSO 用,不動 job-digger 的業務 schema)
php artisan migrate --force || echo "migrate failed — continuing"

# 7. 啟動 PHP-FPM
exec php-fpm
