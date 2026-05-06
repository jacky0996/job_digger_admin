# ADR-0001: SSO 採 Web Mode 而非 API Mode

- **狀態**: Accepted
- **日期**: 2026-04-19
- **決策者**: Shane (SA / 開發者)

## Context — 我們在解決什麼問題?

Job Digger Admin 是 [Middle Platform](../../../Middle_Platform) SSO 的下游消費者。中台簽完 JWT 後 redirect 到 admin,admin 要決定**JWT 之後怎麼維持登入狀態**。技術上有兩個典型模式:

### API Mode(EDM 後端的做法)

每個 request 都帶 `Authorization: Bearer <JWT>`,後端 stateless 驗 JWT。

```
Browser ──[Bearer JWT]──→ Server  (每個 request 都驗)
```

### Web Mode

第一次帶 JWT 進來時 → 驗 → 換成 server session(寫進 Laravel session,瀏覽器收到 session cookie)→ 後續 request 用 cookie。

```
Browser ──[?token=<JWT>]──→ Server  (第一次,驗 JWT,寫 session)
Browser ──[Cookie]────────→ Server  (之後,用 session)
```

兩種模式對「web 後台」這個場景的差異很大。

## Decision — 我們選了什麼?

**採 Web Mode**:

- `AuthorizeJwtSso` middleware 第一次收到 `?token=` → 驗 JWT → `User::firstOrCreate(['email' => ...])` → `Auth::login($user)` → `redirect()->intended('/')`
- 之後所有 request 透過 Laravel session 驗證(`Auth::check()`)
- Session 過期(預設 120 分鐘)後重新進站 → 又 redirect 中台 → 重來一次

## Considered Options — 還評估過哪些?

### 選項 1 — Web Mode【選中】

- ✅ Server-render 後台的天然選擇:每個頁面都是新 request,使用者操作完全不感知 token
- ✅ Laravel `Auth::user()` / `@auth` blade directive 等內建設施直接用,不必每次手動處理 JWT
- ✅ Refresh / 重開 tab 仍維持登入(session 在 server side)
- ⚠ Laravel session 跟 SSO 過期不同步 — 中台 access token 過期(30 分鐘)但 Laravel session 還沒過期(120 分鐘)時,使用者可以繼續操作(JWT 已失效但本地 session 還活著)

### 選項 2 — API Mode

- ✅ 完全 stateless,multi-instance 部署不黏 server
- ❌ Server-render 後台超彆扭:每個頁面 form submit 都要帶 token(放哪?cookie / hidden field?)
- ❌ Refresh 後 URL 沒 token 就被踢
- ❌ Laravel `Auth` 設施全失效,要自己包一套

### 選項 3 — JWT in HttpOnly Cookie

- ✅ 第一次驗 JWT 後寫 cookie,後續每個 request browser 自動帶
- ✅ Stateless 但 UX 接近 Web Mode
- ❌ CSRF 風險(cookie 自動帶過去)
- ❌ Laravel 還是不能用 `Auth::user()`,要自己解 JWT

### 選項 4 — Hybrid(API Mode + Cookie)

- 過度工程,作品集場景不需要

## Consequences — 這個決定帶來什麼?

### ✅ 正面

- **UX 自然**:使用者登入一次,在 admin 內想點哪就點哪,完全不感知 SSO
- **整合 Laravel Auth**:`Auth::user()` / `auth()->user()` / `@auth` blade / `Auth::check()` middleware 全都可用
- **多 tab 共享**:Laravel session cookie 跨 tab 共用
- **實作簡單**:middleware 一個檔搞定,不必改 controller / view

### ⚠ 負面 / Trade-off

- **Session 跟 SSO 過期不同步**:中台 access token TTL = 30 分鐘,Laravel session = 120 分鐘。在第 31 ~ 120 分鐘之間,使用者**已經被中台登出**但本地 session 還活著。緩解:
  - 對「需要重新驗證」的敏感操作(目前沒有),可加 `auth.basic` 之類的二次認證
  - 把 Laravel `SESSION_LIFETIME` 設成跟中台 access TTL 一樣(改 30 分鐘) — 但 UX 會變差
  - 真要嚴格,要在每個 request 重新打中台驗 JWT — 退化成 API Mode

- **Stateful 部署**:Session 存 file,multi-instance 部署需要 sticky session 或共享 session store。緩解:
  - 作品集單機跑,沒問題
  - 真要 prod,改 SESSION_DRIVER=redis 或共用 file mount

- **無法主動撤銷**:中台登出(/sso/logout)清中台 session 但**不會主動通知 admin**,admin 的 Laravel session 還活著直到 TTL 過期或使用者主動登出。緩解:
  - 提供 `/sso/logout` 路由連動清 admin session(已實作)
  - 對撤銷需求高的場景(改密碼、被駭),evaluate 加 webhook 或 token version

### 🔁 後續追蹤

- 監控「session 還活但中台 token 過期」的情況是否造成困擾
- 評估改用 Redis session(若 multi-instance)
- 評估 SSO logout webhook(若中台支援)

## References

- Code:
  - [`app/Http/Middleware/AuthorizeJwtSso.php`](../../app/Http/Middleware/AuthorizeJwtSso.php)
  - [`app/Http/Controllers/SsoController.php`](../../app/Http/Controllers/SsoController.php)
- 文件:
  - [`docs/sequence-diagrams.md` 第 1 節](../sequence-diagrams.md#1-sso-進站流程)
  - [`docs/architecture.md` Level 5](../architecture.md#level-5--sso-整合web-mode-重點)
- 對比:
  - [EDM 後端 ADR-0001 (API Mode)](../../../edm_backend/docs/adr/0001-jwt-shared-secret.md) — 同樣是 SSO 下游,但選 API Mode 因為它是純 API 不是 web 後台
- 外部:
  - [Laravel Authentication](https://laravel.com/docs/11.x/authentication)
  - [OWASP Session Management Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Session_Management_Cheat_Sheet.html)
