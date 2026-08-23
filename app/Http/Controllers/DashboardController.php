<?php

namespace App\Http\Controllers;

use App\Models\ExtractedLead;
use App\Models\ExtractionJob;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = Auth::user();
        $isSuperAdmin = $user->isSuperAdmin();
        $tenantId = $user->tenant_id;

        // Base queries scoped by tenant unless Super Admin
        $jobsQuery = ExtractionJob::query()
            ->when(! $isSuperAdmin && $tenantId, fn ($q) => $q->where('tenant_id', $tenantId));

        $leadsQuery = ExtractedLead::query()
            ->when(! $isSuperAdmin && $tenantId, fn ($q) => $q->where('tenant_id', $tenantId));

        $totalLeads = (clone $leadsQuery)->count();
        $totalJobs = (clone $jobsQuery)->count();
        $totalBusinessesSeen = (clone $jobsQuery)->sum('businesses_seen') ?: $totalLeads;
        $totalEmails = (clone $leadsQuery)->whereNotNull('emails')->where('emails', '!=', '[]')->count();
        $totalWebsites = (clone $leadsQuery)->whereNotNull('website')->where('website', '!=', '')->count();
        $totalPhones = (clone $leadsQuery)->whereNotNull('phone')->where('phone', '!=', '')->count();

        $emailRate = $totalLeads > 0 ? round(($totalEmails / $totalLeads) * 100, 1) : 0;
        $phoneRate = $totalLeads > 0 ? round(($totalPhones / $totalLeads) * 100, 1) : 0;
        $websiteRate = $totalLeads > 0 ? round(($totalWebsites / $totalLeads) * 100, 1) : 0;

        // Top categories
        $topCategories = (clone $leadsQuery)
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->select('category', DB::raw('count(*) as count'))
            ->groupBy('category')
            ->orderByDesc('count')
            ->limit(6)
            ->get();

        // Recent Extraction Jobs
        $recentJobs = (clone $jobsQuery)
            ->with(['user', 'tenant'])
            ->latest('id')
            ->limit(6)
            ->get();

        // Super Admin Platform Stats
        $platformStats = null;
        if ($isSuperAdmin) {
            $platformStats = [
                'total_tenants' => Tenant::count(),
                'active_tenants' => Tenant::where('is_active', true)->count(),
                'total_users' => User::count(),
                'global_leads' => ExtractedLead::count(),
                'global_jobs' => ExtractionJob::count(),
            ];
        }

        $tenant = $user->tenant;

        return view('dashboard.index', [
            'user' => $user,
            'tenant' => $tenant,
            'isSuperAdmin' => $isSuperAdmin,
            'totalLeads' => $totalLeads,
            'totalJobs' => $totalJobs,
            'totalBusinessesSeen' => $totalBusinessesSeen,
            'totalEmails' => $totalEmails,
            'totalWebsites' => $totalWebsites,
            'totalPhones' => $totalPhones,
            'emailRate' => $emailRate,
            'phoneRate' => $phoneRate,
            'websiteRate' => $websiteRate,
            'topCategories' => $topCategories,
            'recentJobs' => $recentJobs,
            'platformStats' => $platformStats,
        ]);
    }
}

