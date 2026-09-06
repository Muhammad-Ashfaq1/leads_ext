<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonationController extends Controller
{
    public function impersonateTenant(Tenant $tenant): RedirectResponse
    {
        $currentUser = Auth::user();
        if (! $currentUser || ! $currentUser->isSuperAdmin()) {
            abort(403, 'Only Super Administrators can impersonate organization workspaces.');
        }

        if (session()->has('impersonator_id')) {
            return back()->with('error', 'Please stop your current impersonation session before starting another.');
        }

        if (! $tenant->is_active) {
            return back()->with('error', 'Cannot impersonate a suspended or inactive organization.');
        }

        $targetUser = $tenant->users()->where('role', 'admin')->first()
            ?? $tenant->users()->first();

        if (! $targetUser) {
            return back()->with('error', 'No administrator or member found for this organization to impersonate.');
        }

        session([
            'impersonator_id' => $currentUser->id,
            'impersonator_return_url' => route('tenants.index'),
        ]);

        Auth::login($targetUser);

        return redirect()->route('dashboard')
            ->with('info', "You are now impersonating {$targetUser->name} ({$tenant->name}).");
    }

    public function impersonateUser(User $user): RedirectResponse
    {
        $currentUser = Auth::user();
        if (! $currentUser) {
            abort(403);
        }

        if (session()->has('impersonator_id')) {
            return back()->with('error', 'Please stop your current impersonation session before starting another.');
        }

        if ($user->id === $currentUser->id) {
            return back()->with('error', 'You cannot impersonate yourself.');
        }

        if (! $currentUser->isSuperAdmin()) {
            if (! $currentUser->isAdmin()) {
                abort(403, 'Only administrators can impersonate team members.');
            }

            if ($user->tenant_id !== $currentUser->tenant_id) {
                return back()->with('error', 'User does not belong to your organization.');
            }

            if ($user->isSuperAdmin() || $user->isAdmin()) {
                return back()->with('error', 'You can only impersonate staff members.');
            }
        }

        if (! $user->is_active) {
            return back()->with('error', 'Cannot impersonate an inactive user account.');
        }

        session([
            'impersonator_id' => $currentUser->id,
            'impersonator_return_url' => request()->header('Referer') ?: route('settings.index', ['tab' => 'team']),
        ]);

        Auth::login($user);

        return redirect()->route('dashboard')
            ->with('info', "You are now impersonating {$user->name}.");
    }

    public function stop(): RedirectResponse
    {
        $impersonatorId = session('impersonator_id');
        $returnUrl = session('impersonator_return_url');

        if ($impersonatorId) {
            Auth::loginUsingId($impersonatorId);
            session()->forget(['impersonator_id', 'impersonator_return_url']);
        }

        return redirect($returnUrl ?: route('dashboard'))
            ->with('success', 'Impersonation ended. You have returned to your original account.');
    }
}
