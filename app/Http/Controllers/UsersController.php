<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UsersController extends Controller
{
    public function index(Request $request): View
    {
        $currentUser = Auth::user();
        $isSuperAdmin = $currentUser->isSuperAdmin();
        $tenantId = $currentUser->tenant_id;

        $perPage = (int) $request->input('per_page', 10);
        if (! in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $search = trim((string) $request->input('search', ''));
        $roleFilter = $request->input('role');
        $statusFilter = $request->input('status');
        $tenantFilter = $request->input('tenant_id');

        $baseQuery = User::query()
            ->with('tenant')
            ->when(! $isSuperAdmin && $tenantId, fn ($q) => $q->where('tenant_id', $tenantId));

        // Aggregate stats
        $statsQuery = clone $baseQuery;
        $stats = [
            'total' => $statsQuery->count(),
            'admins' => (clone $baseQuery)->whereIn('role', ['admin', 'super_admin'])->count(),
            'super_admins' => (clone $baseQuery)->where('role', 'super_admin')->count(),
            'members' => (clone $baseQuery)->where('role', 'user')->count(),
            'active' => (clone $baseQuery)->where('is_active', true)->count(),
        ];

        $users = $baseQuery
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($roleFilter && in_array($roleFilter, ['super_admin', 'admin', 'user'], true), fn ($q) => $q->where('role', $roleFilter))
            ->when($statusFilter !== null && $statusFilter !== '', function ($q) use ($statusFilter) {
                if ($statusFilter === 'active') {
                    $q->where('is_active', true);
                } elseif ($statusFilter === 'inactive') {
                    $q->where('is_active', false);
                }
            })
            ->when($isSuperAdmin && $tenantFilter !== null && $tenantFilter !== '', function ($q) use ($tenantFilter) {
                if ($tenantFilter === 'global') {
                    $q->whereNull('tenant_id');
                } else {
                    $q->where('tenant_id', $tenantFilter);
                }
            })
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        $tenants = $isSuperAdmin ? Tenant::where('is_active', true)->orderBy('name')->get() : collect();

        return view('users.index', [
            'users' => $users,
            'tenants' => $tenants,
            'stats' => $stats,
            'isSuperAdmin' => $isSuperAdmin,
            'filters' => [
                'search' => $search,
                'role' => $roleFilter,
                'status' => $statusFilter,
                'tenant_id' => $tenantFilter,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $currentUser = Auth::user();
        $isSuperAdmin = $currentUser->isSuperAdmin();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['required', Rule::in($isSuperAdmin ? ['super_admin', 'admin', 'user'] : ['admin', 'user'])],
            'tenant_id' => [$isSuperAdmin ? 'nullable' : 'required', 'exists:tenants,id'],
            'phone' => ['nullable', 'string', 'max:30'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (! $isSuperAdmin) {
            $validated['tenant_id'] = $currentUser->tenant_id;
        } elseif ($validated['role'] === 'super_admin') {
            $validated['tenant_id'] = null;
        }

        $validated['password'] = Hash::make($validated['password']);
        $validated['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;

        User::create($validated);

        return redirect()->route('users.index')->with('success', "User account '{$validated['name']}' registered successfully.");
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $currentUser = Auth::user();
        $isSuperAdmin = $currentUser->isSuperAdmin();

        if (! $isSuperAdmin && $user->tenant_id !== $currentUser->tenant_id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'role' => ['required', Rule::in($isSuperAdmin ? ['super_admin', 'admin', 'user'] : ['admin', 'user'])],
            'tenant_id' => [$isSuperAdmin ? 'nullable' : 'sometimes', 'exists:tenants,id'],
            'phone' => ['nullable', 'string', 'max:30'],
            'is_active' => ['sometimes', 'boolean'],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        if ($isSuperAdmin) {
            if ($validated['role'] === 'super_admin') {
                $validated['tenant_id'] = null;
            } elseif (array_key_exists('tenant_id', $validated)) {
                $validated['tenant_id'] = $validated['tenant_id'] ?: null;
            }
        }

        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $validated['is_active'] = $request->boolean('is_active');
        $user->update($validated);

        return redirect()->route('users.index')->with('success', "User account '{$user->name}' updated successfully.");
    }
}

