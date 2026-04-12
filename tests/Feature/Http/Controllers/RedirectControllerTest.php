<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\ShortenerUrl;
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
        $originUrl = 'https://google.com';
        $code = 'testCode';

        ShortenerUrl::create([
            'origin_url' => $originUrl,
            'code' => $code,
            'source' => 'test'
        ]);

        $response = $this->call('GET', "/$code");
        $response->assertRedirect($originUrl);
    }

    public function test_redirects_to_404_route_when_code_does_not_exist()
    {
        $code = 'fakeCode';
        $response = $this->call('GET', "/$code");
        $response->assertRedirect(route('404'));
    }
}
