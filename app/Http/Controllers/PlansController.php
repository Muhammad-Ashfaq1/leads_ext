<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PlansController extends Controller
{
    public function index(): View
    {
        $plans = Plan::withCount('tenants')
            ->orderByDesc('is_default')
            ->orderBy('price')
            ->get();

        $stats = [
            'total' => $plans->count(),
            'active' => $plans->where('is_active', true)->count(),
            'total_subscribers' => $plans->sum('tenants_count'),
        ];

        return view('plans.index', [
            'plans' => $plans,
            'stats' => $stats,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'billing_interval' => ['required', 'string', 'in:monthly,yearly'],
            'lead_quota' => ['required', 'integer', 'min:100'],
            'max_staff_members' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string', 'max:1000'],
            'features' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['sometimes', 'boolean'],
            'is_default' => ['sometimes', 'boolean'],
        ]);

        $slug = Str::slug($validated['name']);
        $count = Plan::where('slug', 'like', "{$slug}%")->count();
        if ($count > 0) {
            $slug .= '-'.($count + 1);
        }
        $validated['slug'] = $slug;

        if (! empty($validated['features'])) {
            $validated['features'] = array_values(array_filter(array_map('trim', explode("\n", $validated['features']))));
        } else {
            $validated['features'] = null;
        }

        $validated['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;
        $isDefault = $request->boolean('is_default');
        $validated['is_default'] = $isDefault;

        if ($isDefault) {
            Plan::query()->update(['is_default' => false]);
        }

        Plan::create($validated);

        return redirect()->route('plans.index')->with('success', "Subscription plan '{$validated['name']}' created successfully.");
    }

    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'billing_interval' => ['required', 'string', 'in:monthly,yearly'],
            'lead_quota' => ['required', 'integer', 'min:100'],
            'max_staff_members' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string', 'max:1000'],
            'features' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['sometimes', 'boolean'],
            'is_default' => ['sometimes', 'boolean'],
        ]);

        if (! empty($validated['features'])) {
            $validated['features'] = array_values(array_filter(array_map('trim', explode("\n", $validated['features']))));
        } else {
            $validated['features'] = null;
        }

        $validated['is_active'] = $request->boolean('is_active');
        $isDefault = $request->boolean('is_default');
        $validated['is_default'] = $isDefault;

        if ($isDefault) {
            Plan::where('id', '!=', $plan->id)->update(['is_default' => false]);
        }

        $plan->update($validated);

        return redirect()->route('plans.index')->with('success', "Subscription plan '{$plan->name}' updated successfully.");
    }

    public function destroy(Plan $plan): RedirectResponse
    {
        if ($plan->tenants()->count() > 0) {
            // Safe deactivation
            $plan->update(['is_active' => false]);

            return redirect()->route('plans.index')->with('success', "Plan '{$plan->name}' has active workspaces and was marked as inactive instead of deleting.");
        }

        $planName = $plan->name;
        $plan->delete();

        return redirect()->route('plans.index')->with('success', "Plan '{$planName}' deleted successfully.");
    }
}
