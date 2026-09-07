<?php

namespace Tests\Feature;

use App\Models\GmailAccount;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRoleSettingsRestrictionTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $admin;
    protected User $superAdmin;
    protected User $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Acme Growth Labs',
            'slug' => 'acme-growth-labs',
            'plan' => 'pro',
            'lead_quota' => 2000,
            'is_active' => true,
        ]);

        $this->admin = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role' => User::ADMIN,
            'email' => 'admin@acme-growth.test',
            'is_active' => true,
        ]);

        $this->superAdmin = User::factory()->create([
            'tenant_id' => null,
            'role' => User::SUPER_ADMIN,
            'email' => 'super@obtainsolutions.test',
            'is_active' => true,
        ]);

        $this->member = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role' => User::USER,
            'email' => 'member@acme-growth.test',
            'is_active' => true,
        ]);
    }

    public function test_user_has_role_helper_method(): void
    {
        $this->assertTrue($this->admin->hasRole(User::ADMIN));
        $this->assertTrue($this->admin->hasRole([User::ADMIN, User::SUPER_ADMIN]));
        $this->assertFalse($this->admin->hasRole(User::USER));

        // Super admin satisfies any admin role check
        $this->assertTrue($this->superAdmin->hasRole(User::ADMIN));
        $this->assertTrue($this->superAdmin->hasRole(User::SUPER_ADMIN));

        // Regular member is neither admin nor super_admin
        $this->assertFalse($this->member->hasRole(User::ADMIN));
        $this->assertFalse($this->member->hasRole(User::SUPER_ADMIN));
        $this->assertTrue($this->member->hasRole(User::USER));
    }

    public function test_admin_and_super_admin_can_access_settings_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('settings.index'));
        $response->assertOk()
            ->assertSee('General &amp; Limits', false)
            ->assertSee('Discovery Engine API')
            ->assertSee('Gmail Integration')
            ->assertSee('Team Members');

        $superResponse = $this->actingAs($this->superAdmin)->get(route('settings.index'));
        $superResponse->assertOk();
    }

    public function test_regular_member_is_forbidden_from_accessing_settings_page(): void
    {
        $response = $this->actingAs($this->member)->get(route('settings.index'));
        $response->assertForbidden();
    }

    public function test_regular_member_is_forbidden_from_updating_settings(): void
    {
        $response = $this->actingAs($this->member)->put(route('settings.update'), [
            'name' => 'Hacked Organization Name',
            'default_engine' => 'google_api',
            'default_limit' => 50,
        ]);

        $response->assertForbidden();
    }

    public function test_admin_can_update_settings(): void
    {
        $response = $this->actingAs($this->admin)->put(route('settings.update'), [
            'name' => 'Updated Acme Growth',
            'default_engine' => 'google_api',
            'default_limit' => 100,
        ]);

        $response->assertRedirect();
        $this->assertEquals('Updated Acme Growth', $this->tenant->fresh()->name);
    }

    public function test_regular_member_is_forbidden_from_users_and_invitations_routes(): void
    {
        // /users redirect route
        $responseUsers = $this->actingAs($this->member)->get(route('users.index'));
        $responseUsers->assertForbidden();

        // Adding team member
        $responseStoreUser = $this->actingAs($this->member)->post(route('users.store'), [
            'name' => 'New User',
            'email' => 'newuser@acme-growth.test',
            'password' => 'secret123',
        ]);
        $responseStoreUser->assertForbidden();

        // Sending invitation
        $responseInvite = $this->actingAs($this->member)->post(route('invitations.store'), [
            'email' => 'invitee@acme-growth.test',
        ]);
        $responseInvite->assertForbidden();
    }

    public function test_regular_member_is_forbidden_from_email_integration_actions(): void
    {
        // Connect Gmail
        $responseConnectGmail = $this->actingAs($this->member)->get(route('gmail.connect'));
        $responseConnectGmail->assertForbidden();

        // Connect Hostinger
        $responseConnectHostinger = $this->actingAs($this->member)->post(route('gmail.connect-hostinger'), [
            'email' => 'support@acme-growth.test',
            'password' => 'secret',
        ]);
        $responseConnectHostinger->assertForbidden();

        // Disconnect Account
        $account = GmailAccount::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->admin->id,
            'email' => 'outreach@acme-growth.test',
            'provider' => 'hostinger',
            'is_active' => true,
        ]);

        $responseDisconnect = $this->actingAs($this->member)->post(route('gmail.disconnect', $account->id));
        $responseDisconnect->assertForbidden();
    }

    public function test_frontend_hides_settings_and_connect_actions_from_regular_member(): void
    {
        // Member on dashboard should not see Settings link in sidebar
        $dashResponse = $this->actingAs($this->member)->get(route('dashboard'));
        $dashResponse->assertOk()
            ->assertDontSee(route('settings.index'));

        // Admin on dashboard should see Settings link
        $adminDashResponse = $this->actingAs($this->admin)->get(route('dashboard'));
        $adminDashResponse->assertOk()
            ->assertSee(route('settings.index'));

        // Member on Gmail inbox should not see Connect Hostinger modal or connect buttons
        $gmailResponse = $this->actingAs($this->member)->get(route('gmail.index'));
        $gmailResponse->assertOk()
            ->assertDontSee('Connect Hostinger Email')
            ->assertDontSee('id="connectHostingerModal"', false);

        // Admin on Gmail inbox should see Connect Hostinger modal and button
        $adminGmailResponse = $this->actingAs($this->admin)->get(route('gmail.index'));
        $adminGmailResponse->assertOk()
            ->assertSee('Connect Hostinger Email')
            ->assertSee('id="connectHostingerModal"', false);
    }
}
