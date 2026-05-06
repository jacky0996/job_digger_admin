# User Guide

本文件寫給**第一次用 Job Digger Admin** 的使用者(我自己 / 內部行銷)看。不談 JWT、不談架構,只講你會看到什麼、應該按哪裡。

---

## 1. 這是什麼?

Job Digger Admin 是「**104 職缺爬蟲後台**」,讓你可以:

- **設定爬蟲關鍵字**(例如「PHP 後端」)+ 過濾標籤
- **檢視抓回的職缺**(列表 + 搜尋 + 分頁)
- 系統會根據你設的關鍵字定期自動爬 104,過濾後存進 DB

**你不需要記密碼** — 系統登入是透過中台 SSO,輸入 Email 收信點連結即可。

---

## 2. 第一次登入

### 2.1 進入系統

打開瀏覽器,輸入:

```
http://localhost:8084/      ← 本機開發
```

### 2.2 自動跳轉到中台登入

第一次進站,系統會自動把你帶到**中台登入頁**:

```
Job Digger Admin (你看不到內容)
      ↓
中台登入頁 ─── 輸入 Email
      ↓
信箱收到「Middle Platform 登入連結」信
      ↓
點信中的「登入」按鈕(連結 15 分鐘有效)
      ↓
返回 Admin 看到主畫面
```

> 若收不到信:
> - 檢查垃圾信夾
> - 在 dev 環境信件是印到中台 docker logs(不真的寄)— 跑這個指令撈:
>   ```bash
>   docker logs middle_platform_web 2>&1 | grep "/sso/magic/" | tail -1
>   ```

### 2.3 進入後看到的畫面

成功登入後預設會被導到 **關鍵字設定頁**(`/search-configs`):

- 頂部:你的姓名(顯示登入身分)
- 主區域:關鍵字列表(若無,顯示「請新增關鍵字」)

---

## 3. 關鍵字管理(SearchConfig)

### 3.1 看關鍵字列表

側邊選單 → **關鍵字管理**

你會看到:
- **Keyword**(104 搜尋字,例如 "PHP")
- **Filter Tags**(逗號分隔的過濾標籤,例如 "php,後端,軟體")
- **Created At**(建立時間)
- **動作**:編輯 / 刪除

### 3.2 新增關鍵字

列表頁右上角 → **新增關鍵字**

填寫:

| 欄位 | 說明 | 範例 |
|---|---|---|
| **Keyword**(必填) | 104 搜尋框會輸入的字 | `PHP 後端` |
| **Filter Tags**(必填) | 二次過濾用,**標題需包含其一**才會被收進 DB | `php,PHP,後端,軟體,資訊` |

> **過濾規則**:`filter_tags` 是 OR 邏輯。例如 `"php,後端"` 代表標題只要含 "php" 或 "後端" 就保留,兩者都沒有就丟掉。
> **不要設太寬**(全部關鍵字爬下來)也不要太窄(可能漏好職缺)— 我自己是設 5-7 個常見替代詞。

### 3.3 編輯 / 刪除

每筆右側有「編輯」「刪除」按鈕。

**注意**:
- **編輯**會立即生效 — 下次跑爬蟲就用新關鍵字
- **刪除**會永久移除 search_config(沒有 soft delete),但**已抓回的 vacancies 不受影響**(它們有自己的 `keyword` 欄位 snapshot)

### 3.4 (Roadmap)執行爬蟲

每筆右側未來會有「**執行**」按鈕,點下去:
- 系統會呼叫 job-digger FastAPI(`http://localhost:85`)
- 觸發背景爬蟲(三階段:list / content / company)
- **不會立即看到結果** — 爬蟲跑完通常 5-30 分鐘,要去職缺搜尋頁刷新

> 目前(2026 Q2)此按鈕是 **stub**,會跳 alert「尚未實作」。要手動跑爬蟲請見 [job-digger README](../../job-digger/README.md)。

---

## 4. 職缺檢視(Vacancy)

### 4.1 看職缺列表

側邊選單 → **職缺搜尋**

你會看到爬蟲抓回的所有職缺,**預設 20 筆/頁**。每筆顯示:

- **Title**(職缺標題)
- **Company**(公司名稱)+ 連到 104 公司頁的連結
- **Salary**(原始薪資文字)
- **Capital / Employee Count**(公司資本額 / 員工數,Stage B 補進來的)
- **Keyword**(對應的搜尋字,看是哪次爬蟲抓的)
- **Check Type**(過濾分類)
- **Job Link**(連到 104 職缺頁)
- **Created**(抓取時間)

### 4.2 過濾與搜尋

頁面頂部有 **Keyword 下拉選單**,選一個關鍵字就只看該關鍵字抓回的職缺。

> Roadmap:加更多過濾條件(資本額 > X、員工數區間、status 等)

### 4.3 注意事項

- **職缺是唯讀的** — Admin 不能在 UI 改職缺資料(那是 job-digger 的責任)
- **重複職缺不會出現兩次** — `job_link` 是 unique,job-digger 用 UPSERT
- **職缺可能變舊** — 沒有自動更新,得手動跑爬蟲

---

## 5. 系統內查文件

側邊選單 → **/help**(若有)

或直接看本目錄的 GitHub 渲染版本:

- [overview.md](./overview.md) — 為什麼有這個系統
- [architecture.md](./architecture.md) — 架構
- [adr/](./adr/) — 重大決策

---

## 6. 常見問題

### Q1: 我登入後過一陣子又被踢出去要重登?

A: 系統 Session **預設 120 分鐘**(由 `SESSION_LIFETIME` 控制)。120 分鐘內沒操作會自動登出,要重新走 SSO。如果你想長一點,改 `.env` 的 `SESSION_LIFETIME=480`(8 小時)再 restart。

### Q2: 我手滑刪錯一個關鍵字,救得回來嗎?

A: **目前救不回來** — 關鍵字沒做 soft delete(因為一般不會誤刪),只能重新建一次。但**已抓回的 vacancies 不受影響**(它們有 keyword snapshot)。

### Q3: 我可以同時開兩個分頁操作嗎?

A: **可以**,Laravel session 會共享。但避免兩個分頁同時編輯**同一筆**關鍵字。

### Q4: 為什麼有些 vacancies 的 Capital / Employee Count 是空的?

A: 那些是**剛抓回但還沒跑 Stage B(公司資料補完)**。下次 job-digger 跑爬蟲時 Stage B 會補進去。

### Q5: 為什麼我登入時跳到一個叫「Middle Platform」的網頁?

A: 那是**集中式登入服務**(SSO)。Middle Platform / EDM / Job Digger Admin 共用同一個登入,你只要登一次就能進所有系統。詳見中台的 [overview.md](../../Middle_Platform/docs/overview.md)。

### Q6: 我看不到「執行爬蟲」按鈕?

A: 因為**還沒做** — 目前要跑爬蟲只能手動進 job-digger 容器或打 FastAPI:
```bash
curl -X POST http://localhost:85/api/scrape/1
# 1 是 search_config 的 id
```

---

## 7. 進階:手動跑爬蟲(暫時用)

在「執行爬蟲」按鈕做出來之前,觸發爬蟲的方式:

```bash
# 1. 看你要跑哪個 search_config
docker exec job_digger_admin_app php artisan tinker --execute="
  echo App\Models\SearchConfig::all()->map(fn(\$c) => \$c->id . ': ' . \$c->keyword)->implode(\"\n\");
"
# 輸出:
# 1: php
# 2: 後端工程師
# ...

# 2. 啟動 id=1 的爬蟲(背景跑)
curl -X POST http://localhost:85/api/scrape/1

# 3. 看狀態
curl http://localhost:85/api/scrape/status/1
# {"config_id": 1, "is_running": true}

# 4. 看 log
docker logs -f job_digger_api
```

通常 5-30 分鐘跑完,然後回 Admin 的「職缺搜尋」頁刷新就能看到新職缺。

---

## 8. 聯絡誰?

| 問題 | 找誰 |
|---|---|
| 登入收不到信 | 看中台 docker logs(自己) |
| 系統 bug / 想加新功能 | 自己 fork 改 |
| 爬蟲爬不到 / 爬錯 | 看 job-digger docker logs |
