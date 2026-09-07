<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\UserInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserInvitationTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Acme Outreach Corp',
            'slug' => 'acme-outreach',
            'lead_quota' => 1000,
            'leads_extracted_count' => 0,
            'is_active' => true,
        ]);

        $this->admin = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Alice Admin',
            'email' => 'alice@acme.test',
            'password' => Hash::make('secret123'),
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    public function test_workspace_admin_can_send_invitation_to_new_staff(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $response = $this->actingAs($this->admin)->post(route('invitations.store'), [
            'email' => 'bob@acme.test',
            'name' => 'Bob Member',
        ]);

        $response->assertRedirect(route('settings.index', ['tab' => 'team']));
        $response->assertSessionHas('success');
        $response->assertSessionHas('invited_url');

        $this->assertDatabaseHas('user_invitations', [
            'tenant_id' => $this->tenant->id,
            'email' => 'bob@acme.test',
            'name' => 'Bob Member',
            'role' => 'user',
        ]);

        $createdInvite = UserInvitation::where('email', 'bob@acme.test')->firstOrFail();
        $this->assertTrue($createdInvite->expires_at->isFuture());
        $this->assertLessThanOrEqual(24 * 3600, $createdInvite->expires_at->diffInSeconds(now()));

        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\UserInvitationMail::class, function ($mail) {
            return $mail->hasTo('bob@acme.test');
        });
    }

    public function test_admin_cannot_invite_existing_registered_user(): void
    {
        User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Existing Person',
            'email' => 'existing@acme.test',
            'password' => Hash::make('secret123'),
            'role' => 'user',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->post(route('invitations.store'), [
            'email' => 'existing@acme.test',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertDatabaseMissing('user_invitations', [
            'email' => 'existing@acme.test',
        ]);
    }

    public function test_admin_cannot_duplicate_pending_invitation(): void
    {
        UserInvitation::create([
            'tenant_id' => $this->tenant->id,
            'invited_by_user_id' => $this->admin->id,
            'email' => 'pending@acme.test',
            'token' => UserInvitation::generateToken(),
            'expires_at' => now()->addHours(24),
        ]);

        $response = $this->actingAs($this->admin)->post(route('invitations.store'), [
            'email' => 'pending@acme.test',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_admin_cannot_exceed_max_staff_quota_with_invitations(): void
    {
        // Add 3 active staff members
        for ($i = 1; $i <= 3; $i++) {
            User::create([
                'tenant_id' => $this->tenant->id,
                'name' => "Staff {$i}",
                'email' => "staff{$i}@acme.test",
                'password' => Hash::make('secret123'),
                'role' => 'user',
                'is_active' => true,
            ]);
        }

        // Add 2 pending invitations (3 + 2 = 5 slots)
        for ($j = 1; $j <= 2; $j++) {
            UserInvitation::create([
                'tenant_id' => $this->tenant->id,
                'invited_by_user_id' => $this->admin->id,
                'email' => "invitee{$j}@acme.test",
                'token' => UserInvitation::generateToken(),
                'expires_at' => now()->addHours(24),
            ]);
        }

        $this->assertFalse($this->tenant->canAddStaffMember());

        // Attempt to invite 6th staff member
        $response = $this->actingAs($this->admin)->post(route('invitations.store'), [
            'email' => 'overflow@acme.test',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertDatabaseMissing('user_invitations', ['email' => 'overflow@acme.test']);
    }

    public function test_admin_can_revoke_invitation_to_free_slot(): void
    {
        $invitation = UserInvitation::create([
            'tenant_id' => $this->tenant->id,
            'invited_by_user_id' => $this->admin->id,
            'email' => 'revoke_me@acme.test',
            'token' => UserInvitation::generateToken(),
            'expires_at' => now()->addHours(24),
        ]);

        $response = $this->actingAs($this->admin)->delete(route('invitations.destroy', $invitation->id));

        $response->assertRedirect(route('settings.index', ['tab' => 'team']));
        $this->assertDatabaseMissing('user_invitations', ['id' => $invitation->id]);
    }

    public function test_guest_can_view_valid_invitation_form(): void
    {
        $invitation = UserInvitation::create([
            'tenant_id' => $this->tenant->id,
            'invited_by_user_id' => $this->admin->id,
            'email' => 'newbie@acme.test',
            'name' => 'Newbie Dev',
            'token' => UserInvitation::generateToken(),
            'expires_at' => now()->addHours(24),
        ]);

        $response = $this->get(route('invitations.accept.form', ['token' => $invitation->token]));

        $response->assertStatus(200);
        $response->assertSee('Join Acme Outreach Corp');
        $response->assertSee('newbie@acme.test');
    }

    public function test_guest_cannot_view_expired_invitation(): void
    {
        $invitation = UserInvitation::create([
            'tenant_id' => $this->tenant->id,
            'invited_by_user_id' => $this->admin->id,
            'email' => 'expired@acme.test',
            'token' => UserInvitation::generateToken(),
            'expires_at' => now()->subDay(),
        ]);

        $response = $this->get(route('invitations.accept.form', ['token' => $invitation->token]));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
    }

    public function test_guest_can_accept_invitation_and_sign_up(): void
    {
        $invitation = UserInvitation::create([
            'tenant_id' => $this->tenant->id,
            'invited_by_user_id' => $this->admin->id,
            'email' => 'joiner@acme.test',
            'token' => UserInvitation::generateToken(),
            'expires_at' => now()->addHours(24),
        ]);

        $response = $this->post(route('invitations.accept.post', ['token' => $invitation->token]), [
            'name' => 'Charlie Joiner',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '+1 555 123 4567',
        ]);

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('success');

        $this->assertAuthenticated();
        $this->assertEquals('joiner@acme.test', Auth::user()->email);
        $this->assertEquals('Charlie Joiner', Auth::user()->name);
        $this->assertEquals('user', Auth::user()->role);
        $this->assertEquals($this->tenant->id, Auth::user()->tenant_id);

        $invitation->refresh();
        $this->assertNotNull($invitation->accepted_at);
    }

    public function test_regular_staff_cannot_send_invitations(): void
    {
        $staff = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Regular Staff',
            'email' => 'staff@acme.test',
            'password' => Hash::make('secret123'),
            'role' => 'user',
            'is_active' => true,
        ]);

        $response = $this->actingAs($staff)->post(route('invitations.store'), [
            'email' => 'hacker@acme.test',
        ]);

        $response->assertStatus(403);
    }

    public function test_impersonate_buttons_have_confirmation_attributes(): void
    {
        $staff = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Target Member',
            'email' => 'target@acme.test',
            'password' => Hash::make('secret123'),
            'role' => 'user',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('settings.index', ['tab' => 'team']));

        $response->assertStatus(200);
        $response->assertSee('impersonate-btn');
        $response->assertSee('data-name="Target Member"', false);
    }

    public function test_user_invitation_email_renders_pos_branding_and_app_url(): void
    {
        $invitation = UserInvitation::create([
            'tenant_id' => $this->tenant->id,
            'invited_by_user_id' => $this->admin->id,
            'email' => 'designer@acme.test',
            'token' => UserInvitation::generateToken(),
            'expires_at' => now()->addHours(24),
        ]);

        $mailable = new \App\Mail\UserInvitationMail($invitation);
        $rendered = $mailable->render();

        $this->assertStringContainsString('https://pos.obtainsolutions.com/', $rendered);
        $this->assertStringContainsString(config('app.url'), $rendered);
        $this->assertStringContainsString('Accept Invitation', $rendered);
        $this->assertStringContainsString('Obtain Solutions', $rendered);
    }
}

