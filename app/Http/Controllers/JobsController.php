<?php

namespace App\Http\Controllers;

use App\Models\ExtractionJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class JobsController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();
        $isSuperAdmin = $user->isSuperAdmin();
        $tenantId = $user->tenant_id;

        $perPage = (int) $request->input('per_page', 10);
        if (! in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $jobs = ExtractionJob::query()
            ->with(['user', 'tenant'])
            ->withCount('leads')
            ->when(! $isSuperAdmin && $tenantId, function ($q) use ($tenantId): void {
                $q->where(function ($sub) use ($tenantId): void {
                    $sub->where('tenant_id', $tenantId)
                        ->orWhereNull('tenant_id');
                });
            })
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('jobs.index', [
            'jobs' => $jobs,
        ]);
    }
}

