# Deployment View

本文件描述 Job Digger Admin 的**部署單元**(nginx + php-fpm 兩個 container)、build 流程、與跨容器網路。

目標讀者:**Ops、Architect、想理解「跑起來長什麼樣」的 Reviewer**。

---

## 1. Deployment Diagram

```mermaid
flowchart TB
    user["👤 內部使用者<br/>(Browser)"]

    subgraph host["🖥 Host Machine"]
        direction TB

        subgraph admin_compose["docker-compose (admin-net)"]
            direction TB
            nginx["📦 job_digger_admin_nginx<br/>nginx:alpine<br/>:8084 → :80<br/>config: docker-compose/nginx/default.conf"]
            app["📦 job_digger_admin_app<br/>job-digger-admin-image<br/>php:8.2-fpm :9000<br/>volume: ./:/var/www"]
            nginx -- "fastcgi_pass app:9000" --> app
        end

        subgraph mp_compose["Middle Platform (鄰居 compose)"]
            mp["📦 middle_platform_web :80"]
        end

        subgraph digger_compose["job-digger (鄰居 compose)"]
            digger_db[("🗄 job_digger_db :3308")]
            digger_api["📦 job_digger_api :85"]
        end
    end

    user -- "http://localhost:8084/" --> nginx
    app -. "Auth: 經 host.docker.internal" .-> mp
    app -- "PDO: host.docker.internal:3308" --> digger_db
    app -. "(Roadmap) HTTP" .-> digger_api
```

**重點**

- 三個系統各有自己的 docker-compose,**獨立啟停**
- Admin 透過 `host.docker.internal` 訪問 host 上的 中台 / job-digger
- Production 的 admin container 內 **只有 nginx + php-fpm**,沒有 Node、沒有 Composer(已在 build 階段裝好)

---

## 2. 容器規格

### 2.1 `job_digger_admin_app`

| 項目 | 值 | 出處 |
|---|---|---|
| Base image | `php:8.2-fpm` | [Dockerfile](../Dockerfile) |
| PHP 擴充 | `pdo_mysql`、`mbstring`、`zip`、`pcntl` | Dockerfile |
| 系統依賴 | `git`、`curl`、`zip`、`unzip`、`libzip-dev`、`libonig-dev`、`netcat-openbsd` | Dockerfile |
| Composer | 從官方 `composer:latest` image COPY 進來 | Dockerfile |
| Entry | `entrypoint.sh`(設權限 → composer install → wait DB → migrate → 起 fpm) | [entrypoint.sh](../entrypoint.sh) |
| 對外 port | — (內部 9000,nginx fastcgi_pass) | docker-compose |
| Volume(dev) | `.:/var/www` (bind mount,熱更新) | docker-compose.yml |
| extra_hosts | `host.docker.internal:host-gateway`(連 host 的 MariaDB / 中台) | docker-compose.yml |
| Restart policy | `unless-stopped` | docker-compose |

### 2.2 `job_digger_admin_nginx`

| 項目 | 值 |
|---|---|
| Image | `nginx:alpine` |
| 對外 port | **8084** → 80(原本想用 84,被 macOS / Docker Desktop 內部 reserve) |
| Config | `docker-compose/nginx/default.conf` |
| Volume | `.:/var/www`(讀 Laravel public/) |

> **8084 而非 84 的原因**:host port 84 在 macOS 上 docker daemon 會報 `address already in use`,但 `lsof` / `ps` 都看不到誰佔(可能是 Docker Desktop 內部 reserve 或 macOS 系統服務)。改用 8084 一勞永逸。

---

## 3. 環境變數

完整模板見 [`.env.example`](../.env.example)。**SSO 相關欄位最關鍵**:

| 變數 | 用途 | 預設(dev) |
|---|---|---|
| `APP_KEY` | **Laravel 內部加密用**(AES-256-CBC) | `base64:<32-byte>` (`php artisan key:generate` 產) |
| `APP_URL` | Admin 對外 URL,組 `/sso/callback` 給中台用 | `http://localhost:8084` |
| `MIDDLE_PLATFORM_URL` | 中台對外 URL(瀏覽器 redirect 過去) | `http://localhost` |
| `SSO_JWT_SECRET` | **SSO JWT 共用密鑰**,必須跟中台 `DJANGO_SECRET_KEY` 完全一致 | (不設) |
| `DB_HOST` | MariaDB host(透過 `host.docker.internal` 連) | `host.docker.internal` |
| `DB_PORT` | MariaDB port | `3308` |
| `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` | DB 連線 | (跟 job-digger 一致) |
| `SESSION_DRIVER` / `CACHE_STORE` / `QUEUE_CONNECTION` | 用 file/sync 避免汙染共用 DB | `file` / `file` / `sync` |

> **`APP_KEY` 跟 `SSO_JWT_SECRET` 的差別**:
> - `APP_KEY` 是 Laravel 內部用,**Laravel 強制要求 32 bytes**(AES-256-CBC),格式 `base64:<32-byte>`
> - `SSO_JWT_SECRET` 是 HS256 共用密鑰,跟著中台 `DJANGO_SECRET_KEY` 走,**長度寬鬆**
> - 兩者**不能合併**:中台的 SECRET_KEY 通常是長字串(不是 32 bytes),硬塞給 Laravel 當 APP_KEY 會炸
> - 詳見 [adr/0003-app-key-vs-jwt-secret.md](./adr/0003-app-key-vs-jwt-secret.md)

---

## 4. 啟動 / 停止

### 4.1 推薦:Docker

```bash
# 0. 先把鄰居都起來
cd ../Middle_Platform && docker compose up -d
cd ../job-digger && docker compose up -d   # 提供 MariaDB :3308 + FastAPI :85

# 1. 起 admin
cd ../job_digger_admin
cp .env.example .env       # 首次才需要

# 2. 必要設定:
#    - APP_KEY 跑 docker exec 後 php artisan key:generate
#    - SSO_JWT_SECRET 設成跟中台 DJANGO_SECRET_KEY 一樣
nano .env

# 3. up
docker compose up -d --build
```

啟動序(由 `entrypoint.sh` 主導):

1. 若無 `.env` 從範本複製
2. 修 `storage` / `bootstrap/cache` 權限
3. `composer install`(若 vendor 不存在)
4. 若 `APP_KEY` 為空就 `key:generate`
5. `nc -z host.docker.internal 3308` 等 DB 起來
6. `php artisan migrate --force`(只建 `users` 表)
7. 啟 PHP-FPM

### 4.2 訪問

| 服務 | 網址 |
|---|---|
| Job Digger Admin | http://localhost:8084 |
| Middle Platform(SSO 中台) | http://localhost |
| job-digger FastAPI | http://localhost:85 |
| MariaDB(用 DBeaver 連) | `localhost:3308` |

### 4.3 停止 / 清理

```bash
docker compose down            # 停止 + 移除 container(留 volume)
docker compose down -v         # 完全清除
```

### 4.4 重置流程

如果 SSO 整合改 secret 後出現「Unsupported cipher / 401 / 500」:

```bash
# 1. 砍 bootstrap cache 殘留
docker exec job_digger_admin_app sh -c "rm -f /var/www/bootstrap/cache/*.php"

# 2. 強制砍 container 重來(restart 不夠,因為有 volume mount cache)
docker compose down && docker compose up -d
```

這個技巧是在我們踩 [adr/0003 雷](./adr/0003-app-key-vs-jwt-secret.md)時學到的:Laravel `bootstrap/cache/services.php` 在 image build 時會生成,即使 .env 改了也不會自動重新編譯。

---

## 5. Nginx 設定要點

`docker-compose/nginx/default.conf`:

```nginx
server {
    listen 80;
    server_name _;
    root /var/www/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;   # Laravel routing
    }

    location ~ \.php$ {
        fastcgi_pass app:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_param HTTP_AUTHORIZATION $http_authorization;   # SSO JWT 用
    }

    location ~ /\.(?!well-known).* {
        deny all;     # 防止 .env / .git 外洩
    }
}
```

---

## 6. 健康檢查

| 檢查 | 方式 | 預期 |
|---|---|---|
| 整體 alive | `curl -I http://localhost:8084/` | HTTP **302**(redirect 到中台) |
| App 容器 | `docker exec job_digger_admin_app php -v` | PHP 8.2.x |
| DB 連得到 | `docker exec job_digger_admin_app nc -zv host.docker.internal 3308` | open |
| Laravel config OK | `docker exec job_digger_admin_app php artisan tinker --execute="echo config('sso.jwt_secret');"` | 跟中台 SECRET_KEY 一樣 |

> 整體 alive 看 **302** 才正確 — 200 反而異常(代表 SSO middleware 沒套到)。

---

## 7. 常用維運指令

```bash
# 進容器
docker exec -it job_digger_admin_app bash

# Artisan
docker exec job_digger_admin_app php artisan route:list
docker exec job_digger_admin_app php artisan migrate:status
docker exec job_digger_admin_app php artisan tinker

# 清 cache(.env / config 改完必跑)
docker exec job_digger_admin_app php artisan config:clear
docker exec job_digger_admin_app php artisan cache:clear

# 看 Laravel error log
docker exec job_digger_admin_app tail -50 /var/www/storage/logs/laravel.log

# nginx log
docker logs -f job_digger_admin_nginx

# 進 MariaDB(共用 job-digger 的)
docker exec -it job_digger_db mariadb -udeveloper -p job_digger
```

---

## 8. 已知部署限制

| 限制 | 影響 | 緩解 |
|---|---|---|
| Port 8084 不是「乾淨」port 84 | 不重要,只是 URL 不漂亮 | 接受,寫進文件 |
| `APP_KEY` 跟 `SSO_JWT_SECRET` 容易設錯 | SSO 直接炸(401 或 500) | `.env.example` 註解清楚 + ADR-0003 |
| `bootstrap/cache/*.php` 不會自動重新生成 | 改 .env 後 `restart` 不夠,要 `down/up` | 寫進 4.4「重置流程」 |
| Composer install 卡 npm registry timeout | build 失敗 | Dockerfile 加 retry(已加) |
| 無 healthcheck | docker-compose `depends_on` 不能保證 ready | 加 healthcheck endpoint(Roadmap) |
| 沒 TLS | 純 :8084 HTTP | 部署到 prod 時加 reverse proxy(Cloudflare / nginx 上層) |
| 沒 production compose | 所有設定都 dev | 寫 `docker-compose.prod.yml` overlay |
