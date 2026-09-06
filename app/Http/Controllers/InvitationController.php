<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\User;
use App\Models\UserInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InvitationController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $currentUser = Auth::user();
        $isSuperAdmin = $currentUser->isSuperAdmin();

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'tenant_id' => ['nullable', 'exists:tenants,id'],
        ]);

        if ($isSuperAdmin && ! empty($validated['tenant_id'])) {
            $tenant = Tenant::findOrFail($validated['tenant_id']);
        } else {
            if (! $currentUser->isAdmin() && ! $isSuperAdmin) {
                abort(403, 'Only organization administrators can send invitations.');
            }
            $tenant = $currentUser->tenant;
        }

        if (! $tenant) {
            abort(400, 'No organization associated with this request.');
        }

        $email = strtolower(trim($validated['email']));

        if (User::where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'A registered user with this email address already exists.',
            ]);
        }

        if ($tenant->pendingInvitations()->where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'An active invitation has already been sent to this email address.',
            ]);
        }

        if (! $tenant->canAddStaffMember()) {
            throw ValidationException::withMessages([
                'email' => 'Your organization has reached the maximum allowance of '.UsersController::MAX_STAFF_PER_TENANT.' staff members (active accounts + pending invitations).',
            ]);
        }

        $invitation = UserInvitation::create([
            'tenant_id' => $tenant->id,
            'invited_by_user_id' => $currentUser->id,
            'email' => $email,
            'name' => $validated['name'] ?? null,
            'role' => 'user',
            'token' => UserInvitation::generateToken(),
            'expires_at' => now()->addHours(UserInvitation::EXPIRATION_HOURS),
        ]);

        try {
            \Illuminate\Support\Facades\Mail::to($invitation->email)->send(new \App\Mail\UserInvitationMail($invitation));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to send invitation email: ' . $e->getMessage());
        }

        return redirect()->route('settings.index', ['tab' => 'team'])
            ->with('success', "Invitation sent to {$invitation->email}.")
            ->with('invited_url', $invitation->accept_url);
    }

    public function destroy(UserInvitation $invitation): RedirectResponse
    {
        $currentUser = Auth::user();
        $isSuperAdmin = $currentUser->isSuperAdmin();

        if (! $isSuperAdmin) {
            if ($invitation->tenant_id !== $currentUser->tenant_id || ! $currentUser->isAdmin()) {
                abort(403, 'You do not have permission to revoke this invitation.');
            }
        }

        $email = $invitation->email;
        $invitation->delete();

        return redirect()->route('settings.index', ['tab' => 'team'])
            ->with('success', "Invitation for '{$email}' has been revoked.");
    }

    public function acceptForm(string $token): View|RedirectResponse
    {
        $invitation = UserInvitation::with('tenant', 'invitedBy')->where('token', $token)->first();

        if (! $invitation) {
            return redirect()->route('login')->withErrors(['email' => 'This invitation link is invalid or has expired.']);
        }

        if ($invitation->isAccepted()) {
            return redirect()->route('login')->with('info', 'This invitation has already been accepted. Please sign in with your credentials.');
        }

        if ($invitation->isExpired()) {
            return redirect()->route('login')->withErrors(['email' => 'This invitation link has expired. Please contact your administrator for a new invitation.']);
        }

        return view('auth.accept-invitation', [
            'invitation' => $invitation,
            'tenant' => $invitation->tenant,
        ]);
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        $invitation = UserInvitation::with('tenant')->where('token', $token)->firstOrFail();

        if (! $invitation->isValid()) {
            return redirect()->route('login')->withErrors(['email' => 'This invitation is no longer valid or has expired.']);
        }

        $tenant = $invitation->tenant;
        if (! $tenant || ! $tenant->is_active) {
            return redirect()->route('login')->withErrors(['email' => 'This organization is currently inactive. Please contact support.']);
        }

        if (User::where('email', $invitation->email)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'An account with this email address already exists. Please log in.',
            ]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $user = User::create([
            'tenant_id' => $invitation->tenant_id,
            'name' => $validated['name'],
            'email' => $invitation->email,
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'role' => 'user',
            'is_active' => true,
        ]);

        $invitation->update([
            'accepted_at' => now(),
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('success', "Welcome to {$tenant->name}, {$user->name}! Your account setup is complete.");
    }
}
