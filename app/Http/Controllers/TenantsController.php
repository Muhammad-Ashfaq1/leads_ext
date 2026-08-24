<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $tenants = Tenant::withCount(['users', 'jobs', 'leads'])
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('tenants.index', [
            'tenants' => $tenants,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'plan' => ['required', 'string', 'in:starter,pro,enterprise'],
            'lead_quota' => ['required', 'integer', 'min:100'],
            'domain' => ['nullable', 'string', 'max:255'],
            'google_maps_api_key' => ['nullable', 'string', 'max:255'],
        ]);

        $slug = Str::slug($validated['name']);
        $count = Tenant::where('slug', 'like', "{$slug}%")->count();
        if ($count > 0) {
            $slug .= '-'.($count + 1);
        }
        $validated['slug'] = $slug;

        Tenant::create($validated);

        return redirect()->route('tenants.index')->with('success', "Tenant '{$validated['name']}' created successfully.");
    }

    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'plan' => ['required', 'string', 'in:starter,pro,enterprise'],
            'lead_quota' => ['required', 'integer', 'min:100'],
            'domain' => ['nullable', 'string', 'max:255'],
            'google_maps_api_key' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $tenant->update($validated);

        return redirect()->route('tenants.index')->with('success', "Tenant '{$tenant->name}' updated.");
    }
}

