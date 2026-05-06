# Sequence Diagrams

本文件用 UML Sequence Diagram 描述 Job Digger Admin 的關鍵互動流程。目標讀者:**SA、開發者、想理解跨系統時序的 Reviewer**。

涵蓋三個流程:

1. SSO 進站(Web Mode)— 從沒登入到看見業務頁
2. Search Config CRUD(常見業務操作)
3. (Roadmap)觸發爬蟲 — Admin → job-digger FastAPI

---

## 1. SSO 進站流程

「使用者第一次點 Admin URL,系統如何處理沒登入這件事?」

```mermaid
sequenceDiagram
    autonumber
    actor U as 使用者 (Browser)
    participant N as Nginx :8084
    participant A as Laravel App
    participant MW as AuthorizeJwtSso<br/>middleware
    participant DB as MariaDB<br/>(users 表)
    participant MP as Middle Platform :80

    Note over U: 第一次進站,沒 Laravel session
    U->>N: GET http://localhost:8084/
    N->>A: fastcgi_pass app:9000
    A->>MW: Pipeline 進入 middleware

    MW->>MW: Auth::check() → false
    MW->>MW: ?token= → 無
    MW->>MW: session['url.intended'] = '/'
    MW-->>U: 302 → http://localhost/sso/login/?redirect=http://localhost:8084/sso/callback

    U->>MP: GET /sso/login/?redirect=...
    Note over MP: Magic Link 流程<br/>(詳見中台 user-flow.md)
    MP-->>U: 302 → http://localhost:8084/sso/callback?token=<JWT>

    U->>N: GET /sso/callback?token=<JWT>
    N->>A: fastcgi_pass
    A->>MW: middleware 攔截

    MW->>MW: Auth::check() → false
    MW->>MW: $request->query('token') → <JWT>
    MW->>MW: decodeJwt(token) using SSO_JWT_SECRET (HS256)

    alt JWT valid
        MW->>DB: User::firstOrCreate(['email' => $payload->email], [...])
        DB-->>MW: User instance
        MW->>MW: Auth::login($user, true)
        MW->>MW: session()->regenerate()
        MW-->>U: 302 → redirect()->intended('/')
        U->>N: GET /
        N->>A: fastcgi_pass
        A->>MW: middleware
        MW->>MW: Auth::check() → true ✓
        MW->>A: next() → 業務 controller
        A-->>U: 渲染業務頁(redirect 到 /search-configs)
    else JWT invalid (過期 / 簽章錯)
        MW->>MW: Log::warning(失敗原因)
        MW-->>U: 302 → 中台 /sso/login/(重來)
    end
```

**關鍵設計細節**

| 步驟 | 為什麼這樣做 |
|---|---|
| `redirect URL = APP_URL/sso/callback`(固定) | 中台白名單比較容易管,且永遠回到固定接點 |
| `session['url.intended']` | Laravel 內建機制,登入完後 `intended('/')` 自動跳回 |
| `firstOrCreate by email` | email 是中台真實識別,本地 user 只是 mirror |
| `Auth::login + session::regenerate` | 防 session fixation attack |

詳細的 middleware code 見 [`app/Http/Middleware/AuthorizeJwtSso.php`](../app/Http/Middleware/AuthorizeJwtSso.php)。

---

## 2. Search Config CRUD 流程

「我登入後新增一個關鍵字,發生什麼?」

```mermaid
sequenceDiagram
    autonumber
    actor U as 行銷人員
    participant V as Blade view<br/>(search_configs/create)
    participant C as SearchConfigController
    participant M as SearchConfig Model
    participant DB as MariaDB<br/>(search_configs 表)

    U->>V: 進「關鍵字 → 新增」頁
    V-->>U: 渲染表單

    U->>C: POST /search-configs<br/>{keyword: "PHP", filter_tags: "php,後端"}
    C->>C: middleware (AuthorizeJwtSso) 通過 ✓
    C->>C: validate (keyword required, unique)

    alt validation 通過
        C->>M: SearchConfig::create(...)
        M->>DB: INSERT INTO search_configs (keyword, filter_tags, created_at) VALUES (?, ?, NOW())
        DB-->>M: id
        M-->>C: SearchConfig instance
        C-->>U: 302 → /search-configs (列表頁)
        Note over U: with success flash message
    else validation 失敗(例如 keyword 重複)
        C-->>U: 302 → 上一頁 + errors
    end
```

**重點**:
- middleware 只在 **request 進來時驗一次**,過了之後 controller 用 `Auth::user()` 取目前 user 不必再驗 JWT
- `unique:search_configs,keyword` 在 FormRequest validation 已經擋了重複,DB 的 UNIQUE INDEX 是 last line of defense

---

## 3. (Roadmap)觸發爬蟲流程

「Admin 點『執行爬蟲』按鈕,系統如何串到 job-digger?」

> ⚠ 此流程**目前是 stub**,UI 會跳 alert「尚未實作」。本節描述 Roadmap 設計。

```mermaid
sequenceDiagram
    autonumber
    actor U as 使用者
    participant V as 關鍵字列表頁
    participant C as SearchConfigController
    participant J as JobDiggerService<br/>(待寫)
    participant API as job-digger FastAPI :85
    participant DB as MariaDB<br/>(vacancies)

    U->>V: 點某 search_config 的「執行爬蟲」
    V->>C: POST /search-configs/{id}/scrape
    C->>J: triggerScrape($id)
    J->>API: POST http://host.docker.internal:85/api/scrape/{id}

    API->>API: BackgroundTasks.add_task(start_scraping_task, $id)
    API-->>J: HTTP 200 {"status": "accepted", ...}
    J-->>C: ok
    C-->>U: 顯示「爬蟲已啟動」+ 按鈕變灰

    Note over API: --- 背景非同步進行 ---
    API->>API: Stage A: run_list_scraper
    API->>API: Stage C: run_content_scraper (filter)
    API->>API: Stage B: run_company_scraper
    API->>DB: UPSERT vacancies

    Note over U: 使用者可去其他頁,稍後回來看
    U->>V: 重新進「職缺搜尋」頁
    V->>DB: SELECT * FROM vacancies WHERE keyword = ?
    DB-->>V: 結果
    V-->>U: 顯示新抓回的職缺

    Note over U,V: (進階)輪詢狀態:
    U->>V: 自動每 5 秒
    V->>API: GET http://host.docker.internal:85/api/scrape/status/{id}
    API-->>V: {"is_running": true/false}
    V-->>U: 更新按鈕狀態
```

**Roadmap 實作要點**

| 項 | 說明 |
|---|---|
| `JobDiggerService` | 新建 `app/Services/JobDiggerService.php`,封裝 HTTP 呼叫(Guzzle) |
| `JOB_DIGGER_API_URL` env | 預設 `http://host.docker.internal:85`,可改 `http://localhost:85`(本機 dev) |
| 「執行中」狀態追蹤 | UI 用輪詢 `GET /api/scrape/status/{id}`,或 server-side flash session 暫存最近啟動的 id |
| 失敗處理 | job-digger API 不會回失敗(內部 task 失敗只在 log),Admin 端用 timeout(例如 10 分鐘沒新資料就提示「可能失敗,請看 docker log」) |
| Rate limit | 同一 keyword 連按多次:job-digger 已用 `active_tasks` set 擋了,Admin 也應該 disable 按鈕 |

---

## 4. 登出流程

```mermaid
sequenceDiagram
    actor U as 使用者
    participant A as Laravel App
    participant MP as 中台

    U->>A: GET /sso/logout
    A->>A: SsoController::logout()
    A->>A: Auth::logout()
    A->>A: session::invalidate() + regenerateToken()
    A-->>U: 302 → http://localhost/sso/logout/

    U->>MP: GET /sso/logout/
    MP->>MP: 清中台 session
    MP-->>U: 302 → /sso/login/(回到中台登入頁)
```

**注意**:中台 logout 不會撤銷已簽出去的 JWT(JWT 是無狀態的)。在 Admin 端的 `Auth::logout` 只清本地 session,但 30 分鐘內如果有人複製了 token 還能再進來(走 SSO callback 路徑驗 JWT 通過後重建 session)。要立即撤銷需要 token blacklist,目前 SSO 流程未實作。

---

## 5. 跨系統 overview

整合三個流程的整體 view:

```mermaid
sequenceDiagram
    actor U as 使用者
    participant A as Admin
    participant MP as Middle Platform
    participant JD as job-digger
    participant DB as 共用 MariaDB

    Note over U,DB: --- 第一次登入 ---
    U->>A: 進站
    A-->>U: redirect 中台
    U->>MP: Magic Link 登入
    MP-->>U: redirect 回 Admin /sso/callback?token=...
    U->>A: callback
    A->>DB: User::firstOrCreate
    A-->>U: 進業務頁

    Note over U,DB: --- 業務操作 ---
    U->>A: 設關鍵字
    A->>DB: INSERT search_configs
    U->>A: 看職缺
    A->>DB: SELECT vacancies

    Note over U,DB: --- (Roadmap) 觸發爬蟲 ---
    U->>A: 點「執行爬蟲」
    A->>JD: POST /api/scrape/{id}
    JD->>JD: background task: list/content/company
    JD->>DB: UPSERT vacancies
```

完整中台側的 SSO 細節見 [Middle Platform user-flow.md](../../Middle_Platform/docs/user-flow.md)。
完整 job-digger 側的爬蟲細節見 [job-digger sequence-diagrams.md](../../job-digger/docs/sequence-diagrams.md)。
