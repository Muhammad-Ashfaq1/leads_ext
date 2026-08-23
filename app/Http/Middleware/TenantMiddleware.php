<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        if (! $user) {
            return redirect()->route('login');
        }

        if (! $user->is_active) {
            Auth::logout();

            return redirect()->route('login')->withErrors(['email' => 'Your user account is inactive.']);
        }

        // For regular tenant admins and users, ensure tenant is active
        if ($user->tenant_id && ! $user->isSuperAdmin()) {
            $tenant = $user->tenant;
            if (! $tenant || ! $tenant->is_active) {
                Auth::logout();

                return redirect()->route('login')->withErrors(['email' => 'Your organization subscription is suspended or inactive.']);
            }
        }

        return $next($request);
    }
}

