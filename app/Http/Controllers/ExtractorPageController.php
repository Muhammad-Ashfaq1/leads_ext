<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ExtractorPageController extends Controller
{
    public function __invoke(): View
    {
        $user = Auth::user();
        $tenant = $user?->tenant;
        $tenantKey = $tenant?->google_maps_api_key;
        $hasKey = ! empty($tenantKey) || ! empty(config('services.google.maps_api_key'));

        return view('extractor.index', [
            'allowMock' => (bool) config('extractor.allow_mock'),
            'defaultLimit' => (int) config('extractor.default_limit'),
            'allowedLimits' => config('extractor.allowed_limits'),
            'hasGoogleApiKey' => $hasKey,
            'tenant' => $tenant,
            'user' => $user,
        ]);
    }
}
