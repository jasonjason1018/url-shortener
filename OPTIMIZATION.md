# URL Shortener 專案優化報告

> 產生日期：2026-04-15

---

## 目錄

1. [安全性問題](#1-安全性問題-security)
2. [效能問題](#2-效能問題-performance)
3. [程式碼品質](#3-程式碼品質-code-quality)
4. [資料庫 Schema](#4-資料庫-schema)
5. [錯誤處理](#5-錯誤處理)
6. [測試覆蓋率](#6-測試覆蓋率)
7. [基礎設施 / Docker](#7-基礎設施--docker)

---

## 1. 安全性問題 (Security)

### CRITICAL — Nonce 未驗證（Replay Attack 漏洞）

- **檔案**：`app/Http/Middleware/HmacVerify.php:23`
- **問題**：Nonce 有從 header 取出，但從未驗證是否已使用過，攻擊者可在 5 分鐘時間窗內無限重放同一個合法請求。
- **修正方向**：
  ```php
  $nonceKey = "nonce:{$clientId}:{$nonce}";
  if (Redis::exists($nonceKey)) {
      throw new \Exception('Nonce already used.', 401);
  }
  Redis::setex($nonceKey, 300, 1);
  ```

### CRITICAL — 更新後未清除 Redis Cache

- **檔案**：`app/Services/ShortUrlService.php:120-126`
- **問題**：`updateShortUrl()` 更新資料庫後，未對應的 Redis key 未被清除，導致客戶端在 TTL 到期前（最多 3600 秒）持續拿到舊資料。
- **修正方向**：update 成功後呼叫 cache delete for the corresponding code key。

### HIGH — 短碼生成 Race Condition

- **檔案**：`app/Services/ShortUrlService.php:42-60`
- **問題**：`generateCode()` 先生成短碼再查 DB 確認是否存在，在高並發下兩個請求可能同時通過 `exists()` 檢查，造成 unique 衝突。
- **修正方向**：
  1. 在 `short_url.code` 欄位加上資料庫層 unique constraint。
  2. 搭配 try/catch 重試邏輯，並設定最大重試次數（避免無限迴圈）。

### HIGH — 潛在 Null Pointer Exception

- **檔案**：`app/Services/ShortUrlService.php:103`
- **問題**：`getShortUrlInfo()` 在 Redis miss 後直接呼叫 `$shortUrl->toArray()`，若 code 不存在於 DB，`$shortUrl` 為 null 會拋出錯誤。
- **修正方向**：加上 null check，並回傳明確的 404。

### MEDIUM — URL 欄位缺乏驗證

- **檔案**：`app/Http/Controllers/ShortUrlController.php:16-19`
- **問題**：`origin_url` 只驗證 `required`，未驗證格式、長度或惡意內容，可能儲存 XSS payload 或超長字串。
- **修正方向**：
  ```php
  'origin_url' => 'required|url|max:2048'
  ```

### MEDIUM — HmacCredentialSeeder 硬編碼 Secret

- **檔案**：`database/seeders/HmacCredentialSeeder.php:18-19`
- **問題**：測試用的 client secret 直接寫在程式碼內，不應提交至版本控制。
- **修正方向**：改從 `.env` 讀取，並在 `.env.example` 提供佔位符。

### MEDIUM — DetectShortCodeScanner Redis 操作非原子性

- **檔案**：`app/Http/Middleware/DetectShortCodeScanner.php:29-32, 41-44`
- **問題**：`incr()` 之後才呼叫 `expire()`，兩個操作之間若 key 已過期，TTL 將不會被正確設定，可能導致計數永不重置。
- **修正方向**：改用 Lua script 或 Redis 的 `SET key value EX seconds` 原子命令。

---

## 2. 效能問題 (Performance)

### HIGH — Cache Stampede（快取雪崩）

- **檔案**：`app/Services/ShortUrlService.php:96-107`
- **問題**：大量請求同時 cache miss 時，會全部打到資料庫，造成 DB 瞬間高負載。
- **修正方向**：使用 `Cache::lock()` 或 probabilistic early expiration 防止同時重建快取。

### MEDIUM — 短碼生成字串效率

- **檔案**：`app/Services/ShortUrlService.php:49`
- **問題**：`$code .= self::CHARS[...]` 在迴圈中逐字拼接字串，為 O(n²) 複雜度。
- **修正方向**：
  ```php
  $chars = [];
  for ($i = 0; $i < $length; $i++) {
      $chars[] = self::CHARS[random_int(0, strlen(self::CHARS) - 1)];
  }
  return implode('', $chars);
  ```

### MEDIUM — 掃描偵測效率

- **檔案**：`app/Http/Middleware/DetectShortCodeScanner.php:49`
- **問題**：每次請求都呼叫 `Redis::scard()` 計算 set 大小，可改為早期終止避免不必要的計算。

### LOW — HMAC 驗證每次查 DB

- **檔案**：`app/Http/Middleware/HmacVerify.php:33-35`
- **問題**：每個 API 請求都查詢 `HmacCredential` 資料表，應將 credential 結果快取進 Redis（TTL 可設較長，如 10 分鐘）。

---

## 3. 程式碼品質 (Code Quality)

### 直接 new Service，未使用依賴注入

- **檔案**：`app/Http/Controllers/ShortUrlController.php:28, 40, 56`
- **問題**：`new ShortUrlService()` 硬耦合，無法在測試中替換假實作。
- **修正方向**：改用建構子注入或 `app()->make(ShortUrlService::class)`。

### 缺乏自訂 Exception Class

- **多個檔案**：Controllers、Middleware 全部 `throw new \Exception(...)`
- **問題**：無法在統一的 handler 中精確區分錯誤類型。
- **修正方向**：建立 `InvalidRequestException`、`UnauthorizedException`、`ResourceNotFoundException` 等自訂例外類別。

### 硬編碼 Magic Numbers / Strings

| 位置 | 值 | 說明 |
|------|----|------|
| `ShortUrlService.php:12,79,110` | `3600` | Cache TTL |
| `DetectShortCodeScanner.php:54-56` | `25, 20, 0.7` | 掃描偵測閾值 |
| `HmacVerify.php:26-27` | `300` | Timestamp 容忍秒數 |

**修正方向**：統一移至 `config/` 目錄下的設定檔或常數類別。

### 缺少 Return Type Hints

- 全域問題：Service 和 Controller 方法幾乎都沒有宣告回傳型別，建議補上 `: string`、`: bool`、`: ?array`、`: void` 等。

### 目錄命名不一致

- `app/Models/ShortUrl.php` vs `app/Model/HmacCredential.php`（少了 `s`）
- 建議統一使用複數 `app/Models/`。

### 未使用 Form Request

- **檔案**：`ShortUrlController.php`
- 建議改用 Laravel Form Request class 處理驗證，讓 controller 更精簡。

---

## 4. 資料庫 Schema

### HIGH — `code` 欄位缺少 Unique Constraint

- **檔案**：`database/migrations/2026_03_30_032439_create_shortener_url_table.php:19`
- 應在資料庫層加上 `$table->unique('code')` 作為最後防線。

### HIGH — `down()` 方法 Drop 錯誤表名

- **檔案**：同上，Line 34
- `down()` 中 drop 的表名與 `up()` 創建的表名不同，rollback 會失敗。

### MEDIUM — Secret Key 欄位長度不足

- **檔案**：`database/migrations/2026_04_02_073626_create_hmac_credentials_table.php:20`
- 目前限制 50 字元，HMAC secret 建議至少 64 字元（256-bit）。

### 功能缺口（Feature Gap）

以下 README 提及的功能，Schema 中尚未支援：
- 短網址過期時間（expiration）：缺少 `expires_at` 欄位
- 啟用/停用短網址：缺少 `is_active` 欄位
- 軟刪除：缺少 `deleted_at`（`SoftDeletes`）

---

## 5. 錯誤處理

### ExceptionDecorator 問題

- **檔案**：`app/Exceptions/ExceptionDecorator.php`
- **問題 1**（Line 21）：直接回傳原始 Exception message，可能洩露內部錯誤細節給客戶端。
- **問題 2**（Lines 45-49）：狀態碼邏輯混亂，999 特殊值硬編碼為 HTTP 500 邏輯不清晰。
- **問題 3**：例外被 render 但從未 log，線上無法追蹤錯誤。
- **修正方向**：
  - 生產環境回傳通用錯誤訊息，僅 debug 模式顯示細節。
  - 整合 `Log::error()` 或 Sentry/Bugsnag 等錯誤追蹤服務。

---

## 6. 測試覆蓋率

### 缺少 Unit Tests

目前只有 Feature Test（整合測試），沒有針對 Service 層的 Unit Test。

### 缺少測試場景

| 場景 | 狀態 |
|------|------|
| Nonce replay attack | 缺少（功能本身也缺少） |
| 更新後 cache 是否清除 | 缺少 |
| 短碼碰撞 / 高並發 | 缺少 |
| 非法 URL 格式 | 缺少 |
| 超長 URL | 缺少 |
| 短碼不存在時的 404 | 缺少 |

---

## 7. 基礎設施 / Docker

- **Redis 無密碼保護**（`docker-compose.yaml:36`）：應設定 `requirepass`。
- **MySQL port 對外暴露**（port 3308）：在非開發環境應移除 port mapping。
- **無 health check**：各 service 應加上 `healthcheck` 設定，避免依賴服務未啟動時應用程式就啟動。
- **無 resource limits**：應設定 CPU / Memory 限制，避免單一容器吃光資源。

---

## 優先修正順序

| 優先級 | 項目 |
|--------|------|
| P0（立刻修） | Nonce replay 漏洞、Cache 未清除、Short code unique constraint |
| P1（本週修） | Race condition 加重試、Null pointer check、URL 格式驗證 |
| P2（本月修） | 依賴注入、自訂 Exception、Magic number 移至設定 |
| P3（長期優化） | Unit tests、Cache stampede、Schema 功能補齊 |
