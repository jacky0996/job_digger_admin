#!/bin/sh
# Cloud Run entrypoint。
#
# 流程:
#   1. nginx config 的 __PORT__ 替換成 Cloud Run 注入的 $PORT
#   2. cache config / route(prod 加速)
#   3. 跑 Laravel migrate(僅建 users 表給 SSO 用)
#   4. exec supervisord(nginx + php-fpm)

set -e

PORT=${PORT:-8080}
echo "[entrypoint] Configuring nginx to listen on ${PORT}..."
sed -i "s/__PORT__/${PORT}/g" /etc/nginx/sites-available/default

echo "[entrypoint] Caching config..."
php artisan config:cache || echo "[entrypoint] config:cache failed — continuing"
php artisan route:cache || echo "[entrypoint] route:cache failed — continuing"

# Cloud SQL 透過 unix socket;偶爾 migrate 撞到 socket 還沒就緒,輕量 retry
echo "[entrypoint] Running migrations..."
for i in $(seq 1 10); do
  if php artisan migrate --force; then
    echo "[entrypoint] Migrations OK"
    break
  fi
  echo "[entrypoint] migrate failed, retrying in 3s ($i/10)..."
  sleep 3
done

chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true

echo "[entrypoint] Starting supervisord (nginx + php-fpm)..."
exec /usr/bin/supervisord -c /etc/supervisor/supervisord.conf -n
