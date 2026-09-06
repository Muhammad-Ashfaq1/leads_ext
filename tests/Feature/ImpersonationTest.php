<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImpersonationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $superAdmin;
    private User $orgAdmin;
    private User $staffMember;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Acme Corporation',
            'slug' => 'acme',
            'plan' => 'starter',
            'lead_quota' => 5000,
            'is_active' => true,
        ]);

        $this->superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'password' => bcrypt('secret123'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->orgAdmin = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Org Admin',
            'email' => 'admin@acme.com',
            'password' => bcrypt('secret123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->staffMember = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Staff Member',
            'email' => 'staff@acme.com',
            'password' => bcrypt('secret123'),
            'role' => 'user',
            'is_active' => true,
        ]);
    }

    public function test_super_admin_can_impersonate_tenant_admin(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('tenants.impersonate', $this->tenant));

        $response->assertRedirect(route('dashboard'));
        $this->assertEquals($this->orgAdmin->id, auth()->id());
        $this->assertEquals($this->superAdmin->id, session('impersonator_id'));

        // Follow redirect and assert impersonating banner is rendered
        $dashboardResponse = $this->get(route('dashboard'));
        $dashboardResponse->assertOk()
            ->assertSee('Impersonating as')
            ->assertSee('Stop');
    }

    public function test_super_admin_cannot_impersonate_suspended_tenant(): void
    {
        $this->tenant->update(['is_active' => false]);

        $response = $this->actingAs($this->superAdmin)->get(route('tenants.impersonate', $this->tenant));

        $response->assertSessionHas('error');
        $this->assertEquals($this->superAdmin->id, auth()->id());
        $this->assertFalse(session()->has('impersonator_id'));
    }

    public function test_org_admin_can_impersonate_their_staff_member(): void
    {
        $response = $this->actingAs($this->orgAdmin)->get(route('users.impersonate', $this->staffMember));

        $response->assertRedirect(route('dashboard'));
        $this->assertEquals($this->staffMember->id, auth()->id());
        $this->assertEquals($this->orgAdmin->id, session('impersonator_id'));

        $dashboardResponse = $this->get(route('dashboard'));
        $dashboardResponse->assertOk()
            ->assertSee('Impersonating as')
            ->assertSee($this->staffMember->name);
    }

    public function test_org_admin_cannot_impersonate_staff_of_another_tenant(): void
    {
        $otherTenant = Tenant::create([
            'name' => 'Beta Corp',
            'slug' => 'beta',
            'plan' => 'starter',
            'lead_quota' => 5000,
            'is_active' => true,
        ]);

        $otherStaff = User::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Other Staff',
            'email' => 'otherstaff@beta.com',
            'password' => bcrypt('secret123'),
            'role' => 'user',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->orgAdmin)->get(route('users.impersonate', $otherStaff));

        $response->assertSessionHas('error', 'User does not belong to your organization.');
        $this->assertEquals($this->orgAdmin->id, auth()->id());
    }

    public function test_org_admin_cannot_impersonate_themselves_or_other_admin(): void
    {
        // Cannot impersonate self
        $responseSelf = $this->actingAs($this->orgAdmin)->get(route('users.impersonate', $this->orgAdmin));
        $responseSelf->assertSessionHas('error', 'You cannot impersonate yourself.');

        // Cannot impersonate super admin
        $responseSuper = $this->actingAs($this->orgAdmin)->get(route('users.impersonate', $this->superAdmin));
        $responseSuper->assertSessionHas('error');
    }

    public function test_regular_staff_cannot_impersonate_anyone(): void
    {
        $response = $this->actingAs($this->staffMember)->get(route('users.impersonate', $this->orgAdmin));
        $response->assertStatus(403);
    }

    public function test_impersonator_can_stop_impersonation_and_return_to_original_account(): void
    {
        // Super admin impersonates org admin
        $this->actingAs($this->superAdmin)->get(route('tenants.impersonate', $this->tenant));
        $this->assertEquals($this->orgAdmin->id, auth()->id());

        // Stop impersonation
        $stopResponse = $this->get(route('impersonate.stop'));

        $stopResponse->assertRedirect(route('tenants.index'));
        $this->assertEquals($this->superAdmin->id, auth()->id());
        $this->assertFalse(session()->has('impersonator_id'));
    }

    public function test_stopping_without_active_impersonation_session_is_forbidden(): void
    {
        $response = $this->actingAs($this->orgAdmin)->get(route('impersonate.stop'));
        $response->assertStatus(403);
    }
}
