<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();
        $tenant = $user->tenant;
        $isSuperAdmin = $user->isSuperAdmin();

        $activeTab = $request->query('tab', 'general');

        // Team members for this workspace
        $teamMembers = collect();
        if ($tenant) {
            $teamMembers = $tenant->users()
                ->orderByRaw("CASE WHEN role = 'admin' THEN 1 ELSE 2 END")
                ->latest('id')
                ->get();
        }

        $staffCount = $tenant ? $tenant->staffMembersCount() : 0;
        $maxStaff = \App\Http\Controllers\UsersController::MAX_STAFF_PER_TENANT;
        $canAddStaff = $tenant ? $tenant->canAddStaffMember() : false;

        return view('settings.index', [
            'user' => $user,
            'tenant' => $tenant,
            'settings' => $tenant?->settings ?? [],
            'hasGlobalGoogleKey' => ! empty(config('services.google.maps_api_key')),
            'activeTab' => $activeTab,
            'teamMembers' => $teamMembers,
            'staffCount' => $staffCount,
            'maxStaff' => $maxStaff,
            'canAddStaff' => $canAddStaff,
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $tenant = $user->tenant;

        if (! $tenant || (! $user->isAdmin() && ! $user->isSuperAdmin())) {
            abort(403, 'Only administrators can modify organization settings.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'google_maps_api_key' => ['nullable', 'string', 'max:255'],
            'default_engine' => ['required', 'string', 'in:google_api,browser'],
            'default_limit' => ['required', 'integer', 'in:10,25,50,100,200'],
            'auto_email_enrichment' => ['sometimes', 'boolean'],
        ]);

        $settings = $tenant->settings ?? [];
        $settings['default_engine'] = $validated['default_engine'];
        $settings['default_limit'] = (int) $validated['default_limit'];
        $settings['auto_email_enrichment'] = $request->boolean('auto_email_enrichment');

        $tenant->update([
            'name' => $validated['name'],
            'google_maps_api_key' => $validated['google_maps_api_key'] ? trim($validated['google_maps_api_key']) : null,
            'settings' => $settings,
        ]);

        return back()->with('success', 'Lead Extractor settings updated successfully.');
    }
}
