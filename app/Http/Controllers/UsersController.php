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

        $users = User::query()
            ->with('tenant')
            ->when(! $isSuperAdmin && $tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->latest('id')
            ->paginate(15);

        $tenants = $isSuperAdmin ? Tenant::where('is_active', true)->get() : collect();

        return view('users.index', [
            'users' => $users,
            'tenants' => $tenants,
            'isSuperAdmin' => $isSuperAdmin,
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
        ]);

        if (! $isSuperAdmin) {
            $validated['tenant_id'] = $currentUser->tenant_id;
        }

        $validated['password'] = Hash::make($validated['password']);
        $validated['is_active'] = true;

        User::create($validated);

        return redirect()->route('users.index')->with('success', "User '{$validated['name']}' created successfully.");
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
            'phone' => ['nullable', 'string', 'max:30'],
            'is_active' => ['sometimes', 'boolean'],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $validated['is_active'] = $request->boolean('is_active');
        $user->update($validated);

        return redirect()->route('users.index')->with('success', "User '{$user->name}' updated.");
    }
}

