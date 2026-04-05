<h1>URL Shortener</h1>

一個使用 Laravel 開發的短網址服務（URL Shortener），提供建立短網址、轉址、基本點擊統計等功能。

此專案主要用於練習與展示後端系統設計，包括：

 - RESTful API 設計

 - 短碼生成策略

 - Redis 快取

 - Docker 開發環境

 - 單元測試 / 功能測試

 - 短網址轉址系統設計

---
<h1>系統架構</h1>

```plain text
Client
|
v
Laravel Router
|
v
HMAC Auth Middleware
|
+----------------------+
|                      |
v                      v
API Controller      Redirect Controller
|                      |
+----------+-----------+
           |
           v
    ShortUrl Service
           |
           v
    ShortUrl Model
           |
           v
    MySQL Database

HMAC Auth Middleware
|
v
Redis (store nonce for 5 minutes)
```
<h1>功能</h1>

目前系統規劃包含以下功能：

<h2>核心功能</h2>

 - 建立短網址

 - 透過短網址轉址

 - 查詢短網址資訊

 - 更新短網址

- 刪除短網址

<h2>短網址管理</h2>

 - 啟用 / 停用短網址

 - 設定過期時間

 - 點擊次數統計

<h2>進階功能（規劃中）</h2>

 - Redis 快取加速轉址

 - 訪問紀錄（IP / User Agent / Referer）

- Analytics

- 自訂 alias

- Rate limit

---

<h1>技術棧</h1>
<h3>Backend</h3>

 - Laravel 7

- PHP

- RESTful API

<h3>Database</h3>

- MySQL

<h3>Cache</h3>

- Redis

<h3>DevOps</h3>

- Docker

- Docker Compose

<h3>Testing</h3>

- PHPUnit

---

<h1>專案結構</h1>

``` plain text
app
├─ Http
│   ├─ Controllers
│   │   ├─ Api
│   │   │   └─ ShortUrlController.php
│   │   └─ RedirectController.php
│   │
│   └─ Requests
│       ├─ StoreShortUrlRequest.php
│       └─ UpdateShortUrlRequest.php
│
├─ Services
│   └─ ShortUrl
│       ├─ CreateShortUrlService.php
│       ├─ RedirectShortUrlService.php
│       └─ ShortCodeGenerator.php
│
├─ Repositories
│   └─ ShortUrlRepository.php
│
└─ Models
├─ ShortUrl.php
└─ ShortUrlVisit.php
```

---

# 本地開發

## 1. 下載專案

```bash
git clone https://github.com/jasonjason1018/url-shortener.git
cd url-shortener
```
## 2. 設定環境變數
``` plain text
cp .env.example .env
```
## 3. 啟動 Docker 容器
``` plain text
docker-compose up -d --build
```
## 4. 產生 Laravel 金鑰
``` plain text
docker-compose exec php-fpm php artisan key:generate
```
## 5. 執行資料庫 Migration
``` plain text
docker-compose exec php-fpm php artisan migrate
```
## 6. 執行測試
``` plain text
docker-compose exec php-fpm php artisan test
```
## 7. 存取服務
請依照 Docker / Web Server 設定，透過瀏覽器或 API 工具存取：
```plain text
http://localhost
```
