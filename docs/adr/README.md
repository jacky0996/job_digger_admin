# Architecture Decision Records (ADR)

本目錄收錄 Job Digger Admin 的關鍵架構決策。

## 索引

| # | 標題 | 狀態 | 影響範圍 |
|---|---|---|---|
| [0001](./0001-sso-web-mode.md) | SSO 採 Web Mode 而非 API Mode | Accepted | SSO 整合機制 / UX |
| [0002](./0002-shared-mariadb-with-job-digger.md) | 跟 job-digger 共用 MariaDB | Accepted | 資料 ownership / 部署 |
| [0003](./0003-app-key-vs-jwt-secret.md) | APP_KEY 跟 SSO_JWT_SECRET 拆兩把(踩過的雷) | Accepted | 安全模型 / 部署 |

> 模板與寫作公約見 [Middle Platform docs/adr/README.md](../../../Middle_Platform/docs/adr/README.md) — 跨 repo 共用同一個 ADR 模板。
