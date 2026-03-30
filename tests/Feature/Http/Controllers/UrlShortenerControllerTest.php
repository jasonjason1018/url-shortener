<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\ShortenerUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UrlShortenerControllerTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_generate_shortener_url_success()
    {
        $originUrl = 'https://example.com';
        $source = 'crm';
        $params = [
            'origin_url' => $originUrl,
            'source' => $source
        ];
        $response = $this->call('POST', 'api/v1/urlShortener', $params);
        $response->assertStatus(200);

        $result = $response->json()['result'];
        $code = $result['code'];

        $shortenerUrl = ShortenerUrl::where('code', '=', $code)->first();
        $this->assertEquals($originUrl, $shortenerUrl->origin_url);
        $this->assertEquals($source, $shortenerUrl->source);
    }
}
