<?php
namespace App\Services;

use App\Models\ShortUrl;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class ShortUrlService {
    private const CHARS = '23456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    private const SHORT_URL_CACHE_PREFIX = 'redirect_code_';
    private const NOT_FOUND_CODE_PREFIX = 'not_found_code_';
    private const DEFAULT_TTL = 3600;
    private const SEARCH_SHORT_URL_INFO_PREFIX = 'search_short_url_';

    private const MAX_CODE_RETRIES = 10;

    public function generateShortenerUrlCode($originUrl, $source)
    {
        $baseUrl = config('app.url');
        $attempts = 0;

        while ($attempts < self::MAX_CODE_RETRIES) {
            $code = $this->generateCode();

            DB::beginTransaction();
            try {
                ShortUrl::create([
                    'origin_url' => $originUrl,
                    'code' => $code,
                    'source' => $source
                ]);

                DB::commit();

                return ['code' => $code, 'baseUrl' => $baseUrl];
            } catch (\Illuminate\Database\QueryException $e) {
                DB::rollback();
                // Unique constraint violation — retry with a new code
                if ($e->errorInfo[1] === 1062) {
                    $attempts++;
                    continue;
                }
                throw $e;
            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }
        }

        throw new \Exception('Failed to generate a unique short code after ' . self::MAX_CODE_RETRIES . ' attempts.', 500);
    }

    private function generateCode($length = 6): string
    {
        $max = strlen(self::CHARS) - 1;
        $chars = [];

        for ($i = 0; $i < $length; $i++) {
            $chars[] = self::CHARS[random_int(0, $max)];
        }

        return implode('', $chars);
    }

    public function getOriginUrl($code)
    {
        $value = Redis::get($this->getOriginUrlKey($code));

        return $value !== null ? (string) $value : null;
    }

    public function cacheOriginUrl($code, $url, $ttl = self::DEFAULT_TTL): void
    {
        Redis::set($this->getOriginUrlKey($code), $url, 'EX', $ttl);
    }

    public function isNotFoundCode($code)
    {
        return Redis::exists($this->getNotFoundKey($code)) > 0;
    }

    public function markCodeAsNotFound($code, $ttl = self::DEFAULT_TTL): void
    {
        Redis::set($this->getNotFoundKey($code), 1, 'EX', $ttl);
    }

    private function getOriginUrlKey($code)
    {
        return self::SHORT_URL_CACHE_PREFIX . $code;
    }

    private function getNotFoundKey($code)
    {
        return self::NOT_FOUND_CODE_PREFIX . $code;
    }

    public function getShortUrlInfo($code): ?array
    {
        $cacheKey = $this->getShortUrlInfoKey($code);
        $cached = Redis::get($cacheKey);

        if ($cached !== null) {
            return json_decode($cached, true);
        }

        $lock = Cache::lock('lock:short_url_info:' . $code, 5);

        try {
            $lock->block(3);

            // Double-check after acquiring lock
            $cached = Redis::get($cacheKey);
            if ($cached !== null) {
                return json_decode($cached, true);
            }

            $shortUrl = ShortUrl::select('code', 'origin_url', 'source')
                ->where('code', '=', $code)
                ->first();

            if (!$shortUrl) {
                return null;
            }

            $data = $shortUrl->toArray();
            $this->cacheShortUrlInfo($code, json_encode($data));

            return $data;
        } finally {
            $lock->release();
        }
    }

    private function cacheShortUrlInfo($code, $info, $ttl = self::DEFAULT_TTL): void
    {
        Redis::set($this->getShortUrlInfoKey($code), $info, 'EX', $ttl);
    }

    private function getShortUrlInfoKey($code)
    {
        return self::SEARCH_SHORT_URL_INFO_PREFIX . $code;
    }

    public function updateShortUrl($code, $originUrl): void
    {
        ShortUrl::where('code', '=', $code)
            ->update([
                'origin_url' => $originUrl
            ]);

        Redis::del($this->getOriginUrlKey($code));
        Redis::del($this->getShortUrlInfoKey($code));
    }
}
