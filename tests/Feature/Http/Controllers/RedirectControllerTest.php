<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\ShortUrl;
use App\Services\RedirectService;
use App\Services\RedisService;
use App\Services\ShortUrlService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class RedirectControllerTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_redirects_to_origin_url_when_code_exists()
    {
        $shortUrlService = new ShortUrlService();

        $originUrl = 'https://google.com';
        $code = 'testCode';

        ShortUrl::create([
            'origin_url' => $originUrl,
            'code' => $code,
            'source' => 'test'
        ]);

        $response = $this->call('GET', "/$code");
        $response->assertRedirect($originUrl);

        $this->assertEquals($originUrl, $shortUrlService->getOriginUrl($code));
    }

    public function test_redirects_to_404_route_when_code_does_not_exist()
    {
        $shortUrlService = new ShortUrlService();

        $code = 'fakeCode';
        $response = $this->call('GET', "/$code");
        $response->assertRedirect(route('404'));

        $this->assertTrue((boolean)$shortUrlService->isNotFoundCode($code));
    }
}
