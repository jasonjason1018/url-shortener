<?php

namespace App\Http\Middleware;

use App\Model\HmacCredential;
use Carbon\Carbon;
use Closure;
use Illuminate\Support\Facades\Redis;

class HmacVerify
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $clientId = $request->header('X-Client-Id');
        $timestamp = $request->header('X-Timestamp');
        $nonce = $request->header('X-Nonce');
        $signature = $request->header('X-Signature');

        $isValid = Carbon::createFromTimestamp($timestamp)
            ->greaterThanOrEqualTo(now()->subMinute(5));

        if (!$isValid) {
            throw new \Exception('Request has expired.', 401);
        }

        $secretMap = HmacCredential::select('secret_key')
            ->where('client_id', '=', $clientId)
            ->first();

        if (!$secretMap) {
            throw new \Exception('Unauthorized client.', 401);
        }

        $secret = $secretMap->secret_key;

        $valid = $this->verify(
            $request->method(),
            '/' . $request->path(),
            $timestamp,
            $nonce,
            $request->getContent(),
            $signature,
            $secret
        );

        if (!$valid) {
            throw new \Exception('Invalid request signature.', 401);
        }

        return $next($request);
    }

    private function verify(
        string $method,
        string $path,
        string $timestamp,
        string $nonce,
        string $body,
        string $signature,
        string $secret
    ): bool {
        $bodyHash = hash('sha256', $body);

        $payload = implode("\n", [
            strtoupper($method),
            $path,
            $timestamp,
            $nonce,
            $bodyHash,
        ]);

        $expected = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expected, $signature);
    }
}
