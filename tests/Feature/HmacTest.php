<?php

namespace Tests\Feature;

use App\Model\HmacCredential;
use Carbon\Carbon;
use Tests\TestCase;

class HmacTest extends TestCase
{
    protected $seeders = [
        \HmacCredentialSeeder::class
    ];

    public function test_valid_hmac_request()
    {
        $hmacCredential = HmacCredential::find(1);
        $secret = $hmacCredential->secret_key;
        $clientId = $hmacCredential->client_id;

        $method = 'POST';
        $path = '/api/v1/ping';
        $timestamp = time();
        $nonce = bin2hex(random_bytes(16));
        $body = json_encode(['order_id' => 1]);

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
        ])->postJson('/api/v1/ping', [
            'order_id' => 1
        ]);

        $response->assertStatus(200)
            ->assertJson(['result' => 'pong']);
    }

    public function test_invalid_signature()
    {
        $timestamp = time();
        $hmacCredential = HmacCredential::find(1);
        $clientId = $hmacCredential->client_id;

        $response = $this->withHeaders([
            'X-Client-Id' => $clientId,
            'X-Timestamp' => $timestamp,
            'X-Nonce' => bin2hex(random_bytes(16)),
            'X-Signature' => 'fake-signature',
        ])->postJson('/api/v1/ping', [
            'order_id' => 1
        ]);

        $response->assertStatus(401);
        $message = $response->json()['message'];
        $expectedMessage = 'Invalid request signature.';
        $this->assertEquals($expectedMessage, $message);
    }

    public function test_modified_body_should_fail()
    {
        $hmacCredential = HmacCredential::find(1);
        $secret = $hmacCredential->secret_key;
        $clientId = $hmacCredential->client_id;

        $method = 'POST';
        $path = '/api/v1/ping';
        $timestamp = time();
        $nonce = bin2hex(random_bytes(16));

        $originalBody = json_encode(['order_id' => 1]);

        $signature = $this->makeSignature(
            $method,
            $path,
            $timestamp,
            $nonce,
            $originalBody,
            $secret
        );

        $response = $this->withHeaders([
            'X-Client-Id' => $clientId,
            'X-Timestamp' => $timestamp,
            'X-Nonce' => $nonce,
            'X-Signature' => $signature,
        ])->postJson('/api/v1/ping', [
            'order_id' => 999
        ]);

        $response->assertStatus(401);
        $message = $response->json()['message'];
        $expectedMessage = 'Invalid request signature.';
        $this->assertEquals($expectedMessage, $message);
    }

    public function test_hmac_fails_when_timestamp_is_expired()
    {
        $this->freezeTime('2026-04-04 21:00:00');
        $hmacCredential = HmacCredential::find(1);
        $secret = $hmacCredential->secret_key;
        $clientId = $hmacCredential->client_id;

        $method = 'POST';
        $path = '/api/v1/ping';
        $timestamp = Carbon::parse('2026-04-04 20:54:59')->timestamp;
        $nonce = bin2hex(random_bytes(16));
        $body = json_encode(['order_id' => 1]);

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
        ])->postJson('/api/v1/ping', [
            'order_id' => 1
        ]);

        $response->assertStatus(401);
        $message = $response->json()['message'];
        $expectedMessage = 'Request has expired.';
        $this->assertEquals($expectedMessage, $message);
    }
}
