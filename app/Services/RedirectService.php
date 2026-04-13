<?php
namespace App\Services;

use App\Models\ShortUrl;

class RedirectService {
    const SHORT_URL_CACHE_PREFIX = 'redirect_code_';
    const NOT_FOUND_CODE_PREFIX = 'not_found_code_';

    public function redirectToNotFoundPage()
    {
        return redirect()->route('404');
    }

    public function redirectToOriginUrl($code)
    {
        $shortUrlService = new ShortUrlService();

        if ($shortUrlService->isNotFoundCode($code)) {
            return $this->redirectToNotFoundPage();
        }

        $targetUrl = $shortUrlService->getOriginUrl($code);

        if ($targetUrl) {
            return redirect()->away($targetUrl);
        }

        $targetUrl = ShortUrl::where('code', $code)->value('origin_url');

        if (!$targetUrl) {
            $shortUrlService->markCodeAsNotFound($code);
            return $this->redirectToNotFoundPage();
        }

        $shortUrlService->cacheOriginUrl($code, $targetUrl);

        return redirect()->away($targetUrl);
    }
}
