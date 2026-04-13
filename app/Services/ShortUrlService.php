<?php
namespace App\Services;

use App\Models\ShortUrl;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class ShortUrlService {
    private const CHARS = '23456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    private const SHORT_URL_CACHE_PREFIX = 'redirect_code_';
    private const NOT_FOUND_CODE_PREFIX = 'not_found_code_';
    private const DEFAULT_TTL = 3600;
    private const SEARCH_SHORT_URL_INFO_PREFIX = 'search_short_url_';

    public function generateShortenerUrlCode($originUrl, $source)
    {
        $code = $this->generateCode();
        $baseUrl = config('app.url');

        DB::beginTransaction();
        try {
            ShortUrl::create([
                'origin_url' => $originUrl,
                'code' => $code,
                'source' => $source
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }

        $result = [
            'code' => $code,
            'baseUrl' => $baseUrl
        ];

        return $result;
    }

    private function generateCode($length = 6)
    {
        while (true) {
            $max = strlen(self::CHARS) - 1;
            $code = '';

            for ($i = 0; $i < $length; $i ++) {
                $code .= self::CHARS[random_int(0, $max)];
            }

            $isCodeExists = ShortUrl::where('code', '=', $code)->exists();

            if (!$isCodeExists) {
                break;
            }
        }

        return $code;
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

    public function getShortUrlInfo($code)
    {
        $shortUrl = Redis::get(self::SEARCH_SHORT_URL_INFO_PREFIX . $code);

        if (!$shortUrl) {
            $shortUrl = ShortUrl::select('code', 'origin_url', 'source')
                ->where('code', '=', $code)
                ->first();

            $shortUrlJson = json_encode($shortUrl->toArray());
            $this->cacheShortUrlInfo($code, $shortUrlJson);
        }

        return $shortUrl;
    }

    private function cacheShortUrlInfo($code, $info, $ttl = self::DEFAULT_TTL): void
    {
        Redis::set($this->getShortUrlInfoKey($code), $info, 'EX', $ttl);
    }

    private function getShortUrlInfoKey($code)
    {
        return self::SEARCH_SHORT_URL_INFO_PREFIX . $code;
    }

    public function updateShortUrl($code, $originUrl)
    {
        ShortUrl::where('code', '=', $code)
            ->update([
                'origin_url' => $originUrl
            ]);
    }
}
