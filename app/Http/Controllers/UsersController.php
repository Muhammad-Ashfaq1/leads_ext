<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UsersController extends Controller
{
    public const MAX_STAFF_PER_TENANT = 5;

    public function index(Request $request): RedirectResponse
    {
        return redirect()->route('settings.index', ['tab' => 'team']);
    }

    public function store(Request $request): RedirectResponse
    {
        $currentUser = Auth::user();
        $isSuperAdmin = $currentUser->isSuperAdmin();
        $tenant = $currentUser->tenant;

        if ($isSuperAdmin) {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:6'],
                'role' => ['required', Rule::in(['super_admin', 'admin', 'user'])],
                'tenant_id' => ['nullable', 'exists:tenants,id'],
                'phone' => ['nullable', 'string', 'max:30'],
                'is_active' => ['sometimes', 'boolean'],
            ]);

            if ($validated['role'] === 'super_admin') {
                $validated['tenant_id'] = null;
            } elseif ($validated['role'] === 'user' && ! empty($validated['tenant_id'])) {
                $targetTenant = Tenant::find($validated['tenant_id']);
                if ($targetTenant && $targetTenant->staffMembersCount() >= self::MAX_STAFF_PER_TENANT) {
                    throw ValidationException::withMessages([
                        'tenant_id' => 'This organization has already reached the maximum allowance of '.self::MAX_STAFF_PER_TENANT.' staff members.',
                    ]);
                }
            }
        } else {
            if (! $tenant || ! $currentUser->isAdmin()) {
                abort(403, 'Only organization administrators can add team members.');
            }

            if (! $tenant->canAddStaffMember()) {
                return redirect()->route('settings.index', ['tab' => 'team'])
                    ->withErrors(['team' => 'Your organization has reached the maximum allowance of '.self::MAX_STAFF_PER_TENANT.' staff members. Remove an existing member to add new staff.']);
            }

            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:6'],
                'phone' => ['nullable', 'string', 'max:30'],
                'is_active' => ['sometimes', 'boolean'],
            ]);

            $validated['tenant_id'] = $tenant->id;
            $validated['role'] = 'user'; // Organization admins can strictly only create staff members
        }

        $validated['password'] = Hash::make($validated['password']);
        $validated['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;

        User::create($validated);

        $returnUrl = $request->input('redirect_to', route('settings.index', ['tab' => 'team']));

        return redirect($returnUrl)->with('success', "Staff member '{$validated['name']}' registered successfully.");
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $currentUser = Auth::user();
        $isSuperAdmin = $currentUser->isSuperAdmin();

        if (! $isSuperAdmin && $user->tenant_id !== $currentUser->tenant_id) {
            abort(403);
        }

        if (! $isSuperAdmin && ! $currentUser->isAdmin()) {
            abort(403, 'Only administrators can update team members.');
        }

        if ($isSuperAdmin) {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
                'role' => ['required', Rule::in(['super_admin', 'admin', 'user'])],
                'tenant_id' => ['nullable', 'exists:tenants,id'],
                'phone' => ['nullable', 'string', 'max:30'],
                'is_active' => ['sometimes', 'boolean'],
                'password' => ['nullable', 'string', 'min:6'],
            ]);

            if ($validated['role'] === 'super_admin') {
                $validated['tenant_id'] = null;
            } elseif (array_key_exists('tenant_id', $validated)) {
                $validated['tenant_id'] = $validated['tenant_id'] ?: null;
            }
        } else {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
                'phone' => ['nullable', 'string', 'max:30'],
                'is_active' => ['sometimes', 'boolean'],
                'password' => ['nullable', 'string', 'min:6'],
            ]);

            if ($user->id !== $currentUser->id) {
                $validated['role'] = 'user'; // Ensure staff members cannot be promoted to admin by org admin
            }
        }

        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $validated['is_active'] = $request->boolean('is_active');
        $user->update($validated);

        $returnUrl = $request->input('redirect_to', route('settings.index', ['tab' => 'team']));

        return redirect($returnUrl)->with('success', "Member '{$user->name}' updated successfully.");
    }

    public function destroy(User $user): RedirectResponse
    {
        $currentUser = Auth::user();
        $isSuperAdmin = $currentUser->isSuperAdmin();

        if ($user->id === $currentUser->id) {
            return back()->withErrors(['team' => 'You cannot remove your own account.']);
        }

        if (! $isSuperAdmin) {
            if ($user->tenant_id !== $currentUser->tenant_id || ! $currentUser->isAdmin()) {
                abort(403);
            }
            if ($user->role !== 'user') {
                return back()->withErrors(['team' => 'Organization admins cannot be removed through this action.']);
            }
        }

        $userName = $user->name;
        $user->delete();

        return redirect()->route('settings.index', ['tab' => 'team'])->with('success', "Staff member '{$userName}' removed successfully.");
    }
}

