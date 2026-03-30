<?php

namespace App\Services;

use App\Models\ShortenerUrl;
use Illuminate\Support\Facades\DB;

class ShortenerUrlService {
    private const CHARS = '23456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    public function generateShortenerUrlCode($originUrl, $source)
    {
        $code = $this->generateCode();
        $baseUrl = config('app.url');

        DB::beginTransaction();
        try {
            ShortenerUrl::create([
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

            $isCodeExists = ShortenerUrl::where('code', '=', $code)->exists();

            if (!$isCodeExists) {
                break;
            }
        }

        return $code;
    }
}
