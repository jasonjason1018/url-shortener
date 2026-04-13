<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Services\HmacVerifierService;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['prefix' => 'v1', 'middleware' => 'hmac'], function () {
    Route::group(['prefix' => 'shortUrl'], function () {
        Route::post('/', 'ShortUrlController@urlShortener');
        Route::patch('/', 'ShortUrlController@updateShortUrl');
        Route::post('/info', 'ShortUrlController@getShortUrlInfo');
    });

    Route::post('/ping', function () {
        return response()->json(['result' => 'pong']);
    });
});
