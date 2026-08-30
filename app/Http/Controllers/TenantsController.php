<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TenantsController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = (int) $request->input('per_page', 10);
        if (! in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $tenants = Tenant::with(['subscriptionPlan', 'users' => fn ($q) => $q->where('role', 'admin')])
            ->withCount(['users', 'jobs', 'leads'])
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        $plans = Plan::where('is_active', true)->orderBy('price')->get();

        return view('tenants.index', [
            'tenants' => $tenants,
            'plans' => $plans,
        ]);
    }

    public function show(Tenant $tenant): JsonResponse
    {
        $tenant->loadMissing('subscriptionPlan');

        return response()->json([
            'id' => $tenant->id,
            'name' => $tenant->name,
            'domain' => $tenant->domain,
            'plan_id' => $tenant->plan_id,
            'plan_name' => $tenant->subscriptionPlan?->name,
            'lead_quota' => $tenant->lead_quota,
            'google_maps_api_key' => $tenant->google_maps_api_key,
            'is_active' => (bool) $tenant->is_active,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'plan_id' => ['nullable', 'exists:plans,id'],
            'plan' => ['nullable', 'string', 'max:100'],
            'lead_quota' => ['required', 'integer', 'min:100'],
            'domain' => ['nullable', 'string', 'max:255'],
            'google_maps_api_key' => ['nullable', 'string', 'max:255'],
            // Optional Organization Admin credentials
            'admin_name' => ['nullable', 'string', 'max:255'],
            'admin_email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'admin_password' => ['nullable', 'string', 'min:6'],
            'admin_phone' => ['nullable', 'string', 'max:30'],
        ]);

        $selectedPlan = null;
        if (! empty($validated['plan_id'])) {
            $selectedPlan = Plan::find($validated['plan_id']);
        } elseif (! empty($validated['plan'])) {
            $selectedPlan = Plan::where('slug', $validated['plan'])->first();
        }

        $planSlug = $selectedPlan ? $selectedPlan->slug : ($validated['plan'] ?? 'starter');
        $planId = $selectedPlan ? $selectedPlan->id : null;

        $slug = Str::slug($validated['name']);
        $count = Tenant::where('slug', 'like', "{$slug}%")->count();
        if ($count > 0) {
            $slug .= '-'.($count + 1);
        }

        $tenant = Tenant::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'plan' => $planSlug,
            'plan_id' => $planId,
            'lead_quota' => $validated['lead_quota'],
            'domain' => $validated['domain'] ?? null,
            'google_maps_api_key' => $validated['google_maps_api_key'] ?? null,
            'is_active' => true,
        ]);

        // If organization admin credentials provided, create the admin user
        if (! empty($validated['admin_email']) && ! empty($validated['admin_password'])) {
            User::create([
                'tenant_id' => $tenant->id,
                'name' => $validated['admin_name'] ?: $validated['name'].' Admin',
                'email' => $validated['admin_email'],
                'password' => Hash::make($validated['admin_password']),
                'role' => 'admin',
                'phone' => $validated['admin_phone'] ?? null,
                'is_active' => true,
            ]);
        }

        return redirect()->route('tenants.index')->with('success', "Organization workspace '{$validated['name']}' created successfully.");
    }

    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        if ($request->input('plan_id') === '') {
            $request->merge(['plan_id' => null]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'plan_id' => ['nullable', 'integer', 'exists:plans,id'],
            'plan' => ['nullable', 'string', 'max:100'],
            'lead_quota' => ['required', 'integer', 'min:100'],
            'domain' => ['nullable', 'string', 'max:255'],
            'google_maps_api_key' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (! empty($validated['plan_id'])) {
            $selectedPlan = Plan::find($validated['plan_id']);
            if ($selectedPlan) {
                $validated['plan'] = $selectedPlan->slug;
            }
        }

        $validated['is_active'] = $request->boolean('is_active');
        $tenant->update($validated);

        return redirect()->route('tenants.index')->with('success', "Organization workspace '{$tenant->name}' updated.");
    }
}

