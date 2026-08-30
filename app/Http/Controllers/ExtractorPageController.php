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

        $allowedLimits = [50, 100, 250, 500];
        $email = strtolower((string) ($user?->email ?? ''));
        if ($user?->isAdmin() && (str_contains($email, 'obtainsolutions') || str_contains($email, 'obtain-solutions'))) {
            $allowedLimits = [...$allowedLimits, 1000, 1500, 2000, 2500];
        }

        return view('extractor.index', [
            'allowMock' => (bool) config('extractor.allow_mock'),
            'defaultLimit' => (int) ($tenant?->settings['default_limit'] ?? config('extractor.default_limit', 50)),
            'allowedLimits' => $allowedLimits,
            'hasGoogleApiKey' => $hasKey,
            'tenant' => $tenant,
            'user' => $user,
        ]);
    }
}
