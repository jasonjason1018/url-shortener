<?php
namespace App\Services;

use Illuminate\Support\Facades\Redis;

class ShortUrlService {
    private const SHORT_URL_CACHE_PREFIX = 'redirect_code_';
    private const NOT_FOUND_CODE_PREFIX = 'not_found_code_';
    private const DEFAULT_TTL = 3600;

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
}
