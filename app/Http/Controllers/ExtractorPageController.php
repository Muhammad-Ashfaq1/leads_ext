<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class ExtractorPageController extends Controller
{
    public function __invoke(): View
    {
        return view('extractor.index', [
            'allowMock' => (bool) config('extractor.allow_mock'),
            'defaultLimit' => (int) config('extractor.default_limit'),
            'allowedLimits' => config('extractor.allowed_limits'),
            'hasGoogleApiKey' => ! empty(config('services.google.maps_api_key') ?: env('GOOGLE_MAPS_API_KEY')),
        ]);
    }
}
