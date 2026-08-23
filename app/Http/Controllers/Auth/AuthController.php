<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            $user = Auth::user();

            if (! $user->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors(['email' => 'Your account has been deactivated. Please contact your administrator.']);
            }

            if ($user->tenant_id && ! $user->tenant?->is_active && ! $user->isSuperAdmin()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors(['email' => 'Your organization subscription is inactive.']);
            }

            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function demoLogin(Request $request, string $account): RedirectResponse
    {
        $email = match ($account) {
            'superadmin' => 'superadmin@leads.test',
            'nexus' => 'admin@nexus.com',
            default => 'admin@acme.com',
        };

        $user = User::where('email', $email)->first();
        if ($user) {
            Auth::login($user, true);
            $request->session()->regenerate();

            return redirect()->route('dashboard')->with('success', "Logged in as {$user->name} ({$user->getRoleLabel()})");
        }

        return redirect()->route('login')->withErrors(['email' => 'Demo account not found. Please run seeders.']);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}

