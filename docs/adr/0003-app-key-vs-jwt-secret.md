# ADR-0003: APP_KEY 跟 SSO_JWT_SECRET 拆兩把(踩過的雷)

- **狀態**: Accepted
- **日期**: 2026-05-05
- **決策者**: Shane (SA / 開發者)

## Context — 我們在解決什麼問題?

> ⚠ 這份 ADR 是 **「踩坑後寫的反思」** — 整個 SSO 整合過程最大的時間消耗點就在這。

Job Digger Admin 接 Middle Platform SSO 時,初版設計是「**Laravel APP_KEY 直接當 JWT 共用 secret**」(這也是 EDM 後端 [ADR-0001](../../../edm_backend/docs/adr/0001-jwt-shared-secret.md) 的做法,看似可以照搬)。

部署上去後發現 admin 100% 回 HTTP 500,Laravel error log:

```
RuntimeException: Unsupported cipher or incorrect key length.
Supported ciphers are: aes-128-cbc, aes-256-cbc, aes-128-gcm, aes-256-gcm.
at Illuminate\Encryption\Encrypter->__construct('dev-secret-key-...', 'AES-256-CBC')
```

### 為什麼炸?

Laravel 啟動時會用 `APP_KEY` 初始化 `Encrypter`(給 cookie / session 加密用),要求 **AES-256-CBC** 的 key 必須 **exactly 32 bytes**。

中台的 `DJANGO_SECRET_KEY` 是 `dev-secret-key-please-change-in-production-0123456789abcdef`(64 chars ASCII),Django/SimpleJWT 用它簽 JWT 完全沒問題(HS256 對 key 長度寬鬆,< 64 bytes 直接用,>= 64 bytes 會 hash 過)。

**但塞給 Laravel `Encrypter` 就炸**,因為 64 bytes ≠ 32 bytes。

### 為什麼 EDM 後端能跑?

EDM 後端 ADR-0001 看起來也是「APP_KEY = JWT secret」共用,沒踩到雷。原因:**它的 `APP_KEY` 剛好是 `base64:<32-byte>`** —Laravel 內部用沒問題,JWT 簽用也是同樣的 32 bytes binary。

但這只在「中台願意把 SECRET_KEY 改成 32-byte 字串」的前提下成立。如果中台已經是「人類可讀的長字串」(像 Django default),你就會踩雷。

## Decision — 我們選了什麼?

**把 Laravel 加密用的 `APP_KEY` 跟 SSO 驗 JWT 用的 `SSO_JWT_SECRET` 完全拆開**:

```env
# Laravel 內部加密 (AES-256-CBC,要求 base64:32-byte)
APP_KEY=base64:ZfH/pmpgr0yMFtXCvOH12X7+tYFRISaeBaVR5lPnbVk=

# SSO JWT 共用密鑰 (HS256),跟著中台走
SSO_JWT_SECRET=dev-secret-key-please-change-in-production-0123456789abcdef
```

對應 [`config/sso.php`](../../config/sso.php):

```php
'jwt_secret' => env('SSO_JWT_SECRET', env('APP_KEY')),  // fallback to APP_KEY for backward compat
```

對應 [`AuthorizeJwtSso::decodeJwt()`](../../app/Http/Middleware/AuthorizeJwtSso.php):

```php
$key = config('sso.jwt_secret');   // ← 不是 config('app.key')
```

## Considered Options — 還評估過哪些?

### 選項 1 — 共用 APP_KEY = JWT secret(EDM 後端做法,被踩)

- ✅ 一個 env var 搞定
- ❌ **跟中台 SECRET_KEY 格式 / 長度耦合** — 中台必須剛好是 base64:32-byte
- ❌ Laravel 加密 / SSO 驗證**意義不同的東西用同一把 key**,洩漏風險合併

### 選項 2 — 拆兩把 secret【選中】

- ✅ Laravel 加密 / SSO 驗證**獨立** — APP_KEY 永遠是 base64:32-byte(Laravel default),SSO_JWT_SECRET 跟著中台走
- ✅ 安全模型清楚:APP_KEY 洩漏不影響 SSO 偽造,反之亦然
- ✅ 部署彈性:中台改 SECRET_KEY 不影響 APP_KEY
- ⚠ 多一個 env var 要管

### 選項 3 — 中台改用 base64:32-byte 的 SECRET_KEY

- ✅ 不必動 Laravel 邏輯
- ❌ **動到中台 SECRET_KEY 影響整個生態**(中台、EDM 後端、admin),改錯炸全部
- ❌ 違反「Don't Repeat Yourself across systems」 — 為了配合 admin 改中台

### 選項 4 — 改用 RS256(非對稱密鑰)

- ✅ 完全沒這問題,公鑰 / 私鑰各不同
- ❌ 中台目前是 HS256,要先升級
- 🔁 列為 Roadmap(對應中台 [ADR-0002](../../../Middle_Platform/docs/adr/0002-jwt-vs-session.md))

## Consequences — 這個決定帶來什麼?

### ✅ 正面

- **Laravel 啟動不會再炸** — APP_KEY 永遠是合法的 base64:32-byte
- **安全模型清楚** — 兩把 key 用途獨立
- **跟中台解耦** — 中台改 SECRET_KEY 不影響本系統 Laravel 加密
- **可推廣** — EDM 後端未來可以照同樣模式改,避免將來踩同樣坑

### ⚠ 負面 / Trade-off

- **多一個 env var** — `.env.example` 要寫清楚,部署時容易漏設(但 fallback 機制下會用 APP_KEY,那就回到原問題)
- **Fallback 邏輯有點 tricky** — `env('SSO_JWT_SECRET', env('APP_KEY'))` 沒設 SSO_JWT_SECRET 時會 fallback 到 APP_KEY,可能誤以為「設了就好」實際 SSO 還是用錯 key。緩解:
  - `.env.example` 標明「**必填**」
  - 加 deploy-time 檢查:`SSO_JWT_SECRET` 為空 / 跟 APP_KEY 一樣時警告
  - 啟動時 log 一行「JWT secret loaded from: SSO_JWT_SECRET / APP_KEY (fallback)」方便偵錯

### 🔁 後續追蹤

- **EDM 後端**也應該改用同樣模式,避免未來換中台 SECRET_KEY 時踩坑
- 監控 SSO 驗證失敗率,若飆高可能是 SSO_JWT_SECRET 漂移
- 等中台升 RS256 後,本 ADR 應 supersede 為 0004-rs256-jwks(只用公鑰,不再共用 secret)

## References

- Code:
  - [`config/sso.php`](../../config/sso.php) — `jwt_secret` 設定
  - [`app/Http/Middleware/AuthorizeJwtSso.php`](../../app/Http/Middleware/AuthorizeJwtSso.php) — `decodeJwt()` 用 `config('sso.jwt_secret')`
  - [`.env.example`](../../.env.example) — 兩把 key 分開設
- 相關 ADR:
  - [Middle Platform ADR-0002 (JWT vs Session)](../../../Middle_Platform/docs/adr/0002-jwt-vs-session.md) — 中台選 HS256 + 共用 secret
  - [EDM 後端 ADR-0001 (JWT shared secret)](../../../edm_backend/docs/adr/0001-jwt-shared-secret.md) — **應該被本 ADR 啟發更新**(同樣的雷,只是它剛好沒踩到)
- 外部:
  - [Laravel Encrypter source](https://github.com/laravel/framework/blob/11.x/src/Illuminate/Encryption/Encrypter.php#L62) — 32-byte 限制的源頭
  - [PHP-JWT HS256 spec](https://datatracker.ietf.org/doc/html/rfc7518#section-3.2) — HS256 對 key 長度的處理

## Postmortem(踩坑紀錄)

| 時間 | 動作 | 結果 |
|---|---|---|
| 21:38 | 第一次 docker compose up 後 admin 回 500 | Laravel 啟動失敗 |
| 21:39 | 看 log 發現 Encrypter cipher error | 確認是 APP_KEY 長度問題 |
| 21:40 | 改 .env 把 APP_KEY 改回 base64:32-byte | 仍 500(因為 bootstrap/cache 殘留) |
| 21:43 | 加 SSO_JWT_SECRET + 改 middleware + 改 config/sso.php | 仍 500(同上) |
| 21:48 | 砍 bootstrap/cache + docker compose down/up | ✅ HTTP 302 redirect 到 SSO |

**學到的事**:
1. Laravel 11 dev mode 有 `bootstrap/cache/services.php`,改 .env 後 `restart` 不夠,要 `down/up`
2. 改 config 後優先順序:**`bootstrap/cache` 清乾淨 → 容器重啟 → 才跑 artisan tinker 驗值**
3. ADR 應該記下「踩過的雷」,不只記「正確選擇」
