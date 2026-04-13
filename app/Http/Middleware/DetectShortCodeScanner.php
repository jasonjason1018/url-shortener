<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class DetectShortCodeScanner
{
    public function handle(Request $request, Closure $next)
    {
        $ip = $request->ip();
        $code = $request->route('code');

        $blockKey = "short:block:{$ip}";
        if (Redis::exists($blockKey)) {
            return response()->json([
                'message' => 'Too many suspicious requests'
            ], 429);
        }

        $minute = now()->format('YmdHi');

        $reqKey = "short:req:{$ip}:{$minute}";
        $missKey = "short:miss:{$ip}:{$minute}";
        $codeSetKey = "short:codes:{$ip}:{$minute}";

        $requests = Redis::incr($reqKey);
        if ($requests === 1) {
            Redis::expire($reqKey, 120);
        }

        Redis::sadd($codeSetKey, $code);
        Redis::expire($codeSetKey, 120);

        $response = $next($request);
        $statusCode = $response->headers->get('REDIRECT_STATUS_CODE');

        if ($statusCode == 404) {
            $misses = Redis::incr($missKey);
            if ($misses === 1) {
                Redis::expire($missKey, 120);
            }
        }

        $requests = (int) Redis::get($reqKey);
        $misses = (int) (Redis::get($missKey) ?: 0);
        $distinctCodes = (int) Redis::scard($codeSetKey);

        $missRatio = $requests > 0 ? $misses / $requests : 0;

        if (
            $distinctCodes > 25 ||
            $misses > 20 ||
            ($distinctCodes > 20 && $missRatio > 0.7)
        ) {
            Redis::setex($blockKey, 900, 1);

            return response()->json([
                'message' => 'Too many suspicious requests'
            ], 429);
        }

        return $response;
    }
}
