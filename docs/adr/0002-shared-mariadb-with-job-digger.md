# ADR-0002: 跟 job-digger 共用 MariaDB(不開自己的 DB)

- **狀態**: Accepted
- **日期**: 2026-04-19
- **決策者**: Shane (SA / 開發者)

## Context — 我們在解決什麼問題?

Job Digger 生態有兩個服務:

- [job-digger](../../../job-digger):Python FastAPI 爬蟲,**寫** vacancies、**讀寫** search_configs
- Job Digger Admin(本系統):Laravel 後台,**讀** vacancies、**讀寫** search_configs

兩個服務都會碰 `search_configs` 跟 `vacancies` 兩張表。技術上有三種選擇:

1. **共用同一個 DB**:兩個服務連同一個 MariaDB,讀同一張表
2. **各自開 DB,用同步機制**:Admin 自己開 DB,job-digger 寫完用 webhook / queue 同步過來
3. **各自開 DB,Admin 走 API**:Admin 不直接讀 DB,改打 job-digger 的 FastAPI 拿資料

選哪個影響:延遲、資料一致性、部署複雜度、ownership。

## Decision — 我們選了什麼?

**共用同一個 MariaDB**:

- 由 [job-digger 的 docker-compose](../../../job-digger/docker-compose.yml) 啟 MariaDB(host port 3308)
- Admin 從容器內透過 `host.docker.internal:3308` 連
- Schema 由 job-digger 的 [`init.sql`](../../../job-digger/init.sql) 建立,Admin **不跑業務 migration**(只跑自己的 `users` 表)

```
                ┌─── job-digger (Python) ──── 寫 + 讀
                │
[ MariaDB ] ────┤
                │
                └─── Admin (Laravel) ──────── 讀(vacancies)+ 讀寫(search_configs)
```

## Considered Options — 還評估過哪些?

### 選項 1 — 共用 MariaDB【選中】

- ✅ **單一真相來源**(single source of truth):沒有「Admin 看到的 vacancy 跟 job-digger 寫的不一致」
- ✅ **零同步成本**:job-digger UPSERT 寫入,Admin SELECT 立即讀到
- ✅ **部署簡單**:一份 DB schema,一份備份策略
- ⚠ **耦合**:兩個服務必須 schema 兼容,改一邊要評估另一邊
- ⚠ **權限劃分模糊**:理論上 Admin 不該寫 vacancies,但 DB 層沒擋,只能靠 code review / convention

### 選項 2 — 各自 DB + 同步(webhook / queue)

- ✅ 完全解耦,服務獨立演進
- ❌ **同步延遲**:job-digger 寫完到 Admin 看到可能延遲秒~分鐘
- ❌ **同步機制複雜**:要建 message queue / webhook,失敗重試 / 順序保證 / 重複事件...
- ❌ **資料分裂風險**:同步 fail 後兩邊不一致
- 對這個規模(我自己用)是過度工程

### 選項 3 — 各自 DB + Admin 走 API

- ✅ Admin 完全跟 DB 解耦
- ❌ Admin 列表 / 分頁 / 過濾 都要靠 job-digger 提供 API,job-digger 變胖
- ❌ N+1 問題:Admin UI 每查一次列表都要打 job-digger
- ❌ job-digger 是 Python FastAPI,做後台用的「複雜查詢 endpoint」吃力不討好(Laravel Eloquent 比較適合)

## Consequences — 這個決定帶來什麼?

### ✅ 正面

- **部署模型簡單**:DB 由 job-digger compose 啟動,Admin 只要連得到就好
- **零延遲**:寫完讀得到
- **Eloquent 直接用**:Admin 用 Laravel 慣用的 Eloquent + Repository pattern,寫起來很快
- **Backup 集中**:備份 / restore 一份就能保留兩個服務的資料

### ⚠ 負面 / Trade-off

- **Schema 耦合**:job-digger 改 `vacancies` schema 一定要同步通知 Admin。緩解:
  - 文件明寫「schema 主檔在 [`job-digger/init.sql`](../../../job-digger/init.sql),Admin 跟著走」
  - 加 schema 變更 review checklist

- **沒 DB 層權限劃分**:理論上 Admin 不該 INSERT vacancies,但 MariaDB 層沒擋。緩解:
  - 程式碼 convention:`Vacancy` model **沒寫** create / update / delete method,只有 query
  - Code review 把關
  - 真要嚴格:給 Admin 一個 read-only DB user,只對 `vacancies` 有 SELECT 權限,只對 `search_configs` 有完整權限

- **耦合部署順序**:Admin 起來時 job-digger 的 DB 必須在(否則 migrate 失敗)。緩解:
  - entrypoint 用 `nc` 等 DB ready
  - 實務上「先起 job-digger 才起 admin」這個順序不算負擔(README 寫清楚)

- **如果未來 admin 要部署到不同 host**:DB 共用就難了,要改成 cross-host 連線(網路 / 安全考量)。緩解:那時再評估選項 2 / 3。

### 🔁 後續追蹤

- 監控 schema 變更是否造成 Admin 炸(實務上 4 個月過去沒發生)
- 若使用者 / vacancy 量明顯成長(>10 萬筆),評估 read replica
- 若要對外開放(讓 client 也能查 vacancies),那時 Admin 不該直連 DB,要走 API

## References

- Code:
  - [`config/database.php`](../../config/database.php) — `mariadb` connection 設定
  - [`docker-compose.yml`](../../docker-compose.yml) — `extra_hosts: host.docker.internal:host-gateway`
  - [`entrypoint.sh`](../../entrypoint.sh) — 等 DB ready 的 `nc` 邏輯
- 文件:
  - [`docs/data-model.md`](../data-model.md) — 共用表 vs 自有表的劃分
  - [`docs/deployment.md`](../deployment.md) — 三系統啟動順序
- 業界對照:
  - [Single Database for Multiple Services anti-pattern (Microsoft)](https://learn.microsoft.com/en-us/azure/architecture/microservices/design/data-considerations) — 微服務情境下的 anti-pattern,但對 monolith / 小規模情境是合理選擇
