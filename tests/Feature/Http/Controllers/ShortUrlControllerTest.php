<?php

namespace Tests\Feature\Http\Controllers;

use App\Model\HmacCredential;
use App\Models\ShortUrl;
use Tests\TestCase;

class ShortUrlControllerTest extends TestCase
{
    protected $seeders = [
        \HmacCredentialSeeder::class
    ];
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_generate_shortener_url_success()
    {
        $hmacCredential = HmacCredential::find(1);
        $secret = $hmacCredential->secret_key;
        $clientId = $hmacCredential->client_id;

        $originUrl = 'https://example.com';
        $source = 'crm';
        $params = [
            'origin_url' => $originUrl,
            'source' => $source
        ];

        $method = 'POST';
        $path = '/api/v1/shortUrl';
        $timestamp = time();
        $nonce = bin2hex(random_bytes(16));;
        $body = json_encode($params);

        $signature = $this->makeSignature(
            $method,
            $path,
            $timestamp,
            $nonce,
            $body,
            $secret
        );

        $response = $this->withHeaders([
            'X-Client-Id' => $clientId,
            'X-Timestamp' => $timestamp,
            'X-Nonce' => $nonce,
            'X-Signature' => $signature,
        ])->postJson('/api/v1/shortUrl', $params);
        $response->assertStatus(200);

        $result = $response->json()['result'];
        $code = $result['code'];

        $shortenerUrl = ShortUrl::where('code', '=', $code)->first();
        $this->assertEquals($originUrl, $shortenerUrl->origin_url);
        $this->assertEquals($source, $shortenerUrl->source);
    }
}
