<?php

namespace App\Http\Controllers;

use App\Models\ShortUrl;
use App\Services\RedirectService;
use App\Services\RedisService;
use Illuminate\Support\Facades\Redis;

class RedirectController extends Controller
{
    public function redirect($code)
    {
        $redirectService = new RedirectService();
        return $redirectService->redirectToOriginUrl($code);
    }
}
