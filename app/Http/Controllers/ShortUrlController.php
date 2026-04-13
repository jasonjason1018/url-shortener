<?php

namespace App\Http\Controllers;

use App\Services\ShortenerUrlService;
use App\Services\ShortUrlService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ShortUrlController extends Controller
{
    public function urlShortener(Request $request)
    {
        $requestData = $request->input();

        $validate = Validator::make($requestData, [
            'origin_url' => 'required | string',
            'source' => 'required | string'
        ]);

        if ($validate->fails()) {
            throw new \Exception('Invalid parameter request.');
        }

        $originUrl = $requestData['origin_url'];
        $source = $requestData['source'];

        $shortUrlService = new ShortUrlService();
        return $shortUrlService->generateShortenerUrlCode($originUrl, $source);
    }
}
