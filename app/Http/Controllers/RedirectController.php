<?php

namespace App\Http\Controllers;

use App\Models\ShortenerUrl;
use Illuminate\Http\Request;

class RedirectController extends Controller
{
    public function redirect($code)
    {
        $shortenerUrl = ShortenerUrl::select('origin_url')
            ->where('code', '=', $code)
            ->first();

        if (!$shortenerUrl) {
            return redirect()->route('404');
        }

        $targetUrl = $shortenerUrl->origin_url;

        return redirect()->away($targetUrl);
    }
}
