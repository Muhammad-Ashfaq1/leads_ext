<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureImpersonatingSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->has('impersonator_id')) {
            abort(403, 'No active impersonation session found.');
        }

        return $next($request);
    }
}
