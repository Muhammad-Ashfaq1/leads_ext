<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();
        $tenant = $user->tenant;

        return view('settings.index', [
            'user' => $user,
            'tenant' => $tenant,
            'hasGlobalGoogleKey' => ! empty(config('services.google.maps_api_key')),
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $user->update($validated);

        return back()->with('success', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Password changed successfully.');
    }

    public function updateTenantSettings(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $tenant = $user->tenant;

        if (! $tenant || (! $user->isAdmin() && ! $user->isSuperAdmin())) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'google_maps_api_key' => ['nullable', 'string', 'max:255'],
        ]);

        $tenant->update($validated);

        return back()->with('success', 'Organization settings updated successfully.');
    }
}
