# Job Digger Admin

Laravel 11 後台，搭配 [`job-digger`](../job-digger) Python 爬蟲服務使用。
本專案只負責「設定關鍵字」與「檢視抓回的職缺」，不執行爬取。

終端使用者文件請見系統內建的 `/help` 頁面；本檔僅給工程師看。

---

## 系統關係

```
                  ┌──────────────────────┐
                  │  Job Digger Admin    │   ← 本專案 (Laravel 11)
                  │  (PHP 8.3 / Tailwind)│
                  └──────────┬───────────┘
                             │ read / write
                             ▼
        ┌────────────────────────────────────────┐
        │  MariaDB  (job-digger 提供, port 3308) │
        │  - search_configs                      │
        │  - vacancies                           │
        └────────────────────────────────────────┘
                             ▲
                             │ scrape / clean / write
                  ┌──────────┴───────────┐
                  │  job-digger (Python) │   ← 另一個專案
                  │  FastAPI :8000       │
                  └──────────────────────┘
```

DB 由 `job-digger` 的 `docker-compose` 啟動，本專案只是 client，**不擁有 schema、不跑 migration**。

---

## 共用資料表

| 表 | 用途 | 本專案的存取 |
| --- | --- | --- |
| `search_configs` | 搜尋關鍵字 + 清洗用過濾標籤 | 全 CRUD |
| `vacancies` | 爬回的職缺主表 | 唯讀 (依 keyword 列表) |

Schema 主檔在 [`../job-digger/init.sql`](../job-digger/init.sql)；本專案沒有 migration。

---

## 環境需求

- PHP 8.3+ / Composer 2.x
- Node 20+ / npm
- MariaDB 10+ (透過 `job-digger` 的 docker-compose 啟動於 `127.0.0.1:3308`)

---

## 本地啟動

```bash
# 1. 安裝依賴
composer install
npm install

# 2. 設定 .env (DB 區塊已預先指向 job-digger 的 docker DB)
cp .env.example .env   # 首次才需要
php artisan key:generate

# 3. 確認 job-digger 的 DB container 在跑
docker ps | grep job_digger_db   # 應該看到 0.0.0.0:3308->3306

# 4. 跑起來
npm run build           # 或 npm run dev (CSS/JS 熱更新)
php artisan serve --port=8088
```

開瀏覽器 http://127.0.0.1:8088 。

---

## `.env` 重點欄位

```env
DB_CONNECTION=mariadb
DB_HOST=127.0.0.1
DB_PORT=3308
DB_DATABASE=job_digger
DB_USERNAME=digger_user
DB_PASSWORD=digger_pass_456

# 不要改成 database driver — 本專案不擁有 sessions / cache / jobs 表
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

---

## 專案重點檔案

```
app/
├── Http/Controllers/
│   ├── SearchConfigController.php   # 關鍵字 CRUD
│   └── VacancySearchController.php  # 職缺搜尋 + 分頁
├── Models/
│   ├── SearchConfig.php             # search_configs (CRUD)
│   └── Vacancy.php                  # vacancies (唯讀)
└── Providers/AppServiceProvider.php # Paginator::useTailwind()

resources/views/
├── layouts/app.blade.php            # 共用 layout (Tailwind + Alpine 行動選單)
├── search_configs/                  # 關鍵字 CRUD 頁面
├── vacancies/search.blade.php       # 職缺搜尋頁
└── help.blade.php                   # 終端使用者文件 (/help)

routes/web.php                       # 全部路由 (3 個 group)
```

---

## 已知 TODO

- 職缺搜尋頁的「**更新資料**」按鈕目前是 stub，會跳 alert「尚未實作」。
  接 `job-digger` 的 FastAPI (預設 `http://localhost:8000`) 後即可重抓特定關鍵字。
- 沒有登入機制 — 預設以「內網 / 信任環境」執行；要對外開放需自行加上認證。

---

## 不會做的事

- **不跑 migration**：schema 由 `job-digger` 維護。本專案的 `database/migrations/` 已清空。
- **不寫 `vacancies`**：只讀。寫入是 Python 爬蟲的責任。
- **不存 session / cache 到資料庫**：避免汙染共用 DB；改用檔案驅動。
