<?php

namespace Tests\Feature\Http\Middleware;

use App\Models\ShortUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Facades\Redis;

class DetectShortCodeScannerTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_returns_429_when_ip_is_already_blocked()
    {
        $ip = '1.2.3.5';
        $code = 'd7as8da';

        Redis::setex("short:block:{$ip}", 900, 1);

        $response = $this->withServerVariables([
            'REMOTE_ADDR' => $ip,
        ])->get("/{$code}");

        $response->assertStatus(429);
        $response->assertJson([
            'message' => 'Too many suspicious requests',
        ]);
    }

    public function test_blocks_when_misses_exceed_20()
    {
        $ip = '1.2.3.6';
        $code = 'd7as8da';
        $minute = now()->format('YmdHi');
        $missKey = "short:miss:{$ip}:{$minute}";

        ShortUrl::create([
            'origin_url' => 'https://example.com',
            'code' => $code,
            'source' => 'test'
        ]);

        for ($i = 1; $i <= 20; $i++) {
            $this->withServerVariables([
                'REMOTE_ADDR' => $ip,
            ])->get("/{$code}878")
                ->assertStatus(302);
        }

        $response = $this->withServerVariables([
            'REMOTE_ADDR' => $ip,
        ])->get("/{$code}878");

        $response->assertStatus(429);
        $response->assertJson([
            'message' => 'Too many suspicious requests',
        ]);

        $this->assertEquals(1, Redis::exists("short:block:{$ip}"));
    }
}
