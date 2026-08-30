<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_route_redirects_to_settings_team_tab(): void
    {
        $tenant = Tenant::create(['name' => 'Acme Corp', 'slug' => 'acme', 'plan' => 'pro']);
        $admin = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get('/users');
        $response->assertRedirect('/settings?tab=team');
    }

    public function test_admin_can_view_team_tab_under_settings(): void
    {
        $tenant = Tenant::create(['name' => 'Acme Corp', 'slug' => 'acme', 'plan' => 'pro']);
        $admin = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'admin',
            'name' => 'Acme Leader',
            'email' => 'leader@acme.com',
            'is_active' => true,
        ]);

        $member = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'user',
            'name' => 'Acme Hunter',
            'email' => 'hunter@acme.com',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get('/settings?tab=team');

        $response->assertOk()
            ->assertSee('Team &amp; Staff Members', false)
            ->assertSee('Acme Leader')
            ->assertSee('Acme Hunter')
            ->assertSee('Staff Allowance: 1 of 5 Slots Used');
    }

    public function test_workspace_admin_can_create_upto_5_staff_members_only(): void
    {
        $tenant = Tenant::create(['name' => 'Starlight Media', 'slug' => 'starlight', 'plan' => 'enterprise']);
        $admin = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'admin',
            'is_active' => true,
        ]);

        // Create 5 staff members successfully
        for ($i = 1; $i <= 5; $i++) {
            $response = $this->actingAs($admin)->post('/users', [
                'name' => "Staff Member {$i}",
                'email' => "staff{$i}@starlight.com",
                'password' => 'password123',
                'phone' => "+1 555-000{$i}",
            ]);
            $response->assertRedirect('/settings?tab=team');
            $this->assertDatabaseHas('users', [
                'email' => "staff{$i}@starlight.com",
                'role' => 'user', // strictly staff member
                'tenant_id' => $tenant->id,
            ]);
        }

        $this->assertEquals(5, $tenant->staffMembersCount());

        // Attempting to create a 6th staff member must be blocked
        $response6 = $this->actingAs($admin)->post('/users', [
            'name' => 'Staff Member 6',
            'email' => 'staff6@starlight.com',
            'password' => 'password123',
        ]);

        $response6->assertSessionHasErrors(['team']);
        $this->assertDatabaseMissing('users', [
            'email' => 'staff6@starlight.com',
        ]);
    }

    public function test_workspace_admin_cannot_create_another_admin_user(): void
    {
        $tenant = Tenant::create(['name' => 'Apex Agency', 'slug' => 'apex', 'plan' => 'pro']);
        $admin = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'admin',
            'is_active' => true,
        ]);

        // Attempting to pass role=admin as org admin must still create a 'user'
        $response = $this->actingAs($admin)->post('/users', [
            'name' => 'Fake Admin',
            'email' => 'fakeadmin@apex.com',
            'password' => 'secret123',
            'role' => 'admin',
        ]);

        $response->assertRedirect('/settings?tab=team');
        $this->assertDatabaseHas('users', [
            'email' => 'fakeadmin@apex.com',
            'role' => 'user', // Forced to user
        ]);
    }

    public function test_workspace_admin_can_remove_staff_member_to_free_slot(): void
    {
        $tenant = Tenant::create(['name' => 'Sol Corp', 'slug' => 'sol', 'plan' => 'pro']);
        $admin = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'admin',
            'is_active' => true,
        ]);

        $staff = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'user',
            'name' => 'Temporary Staff',
            'email' => 'temp@sol.com',
        ]);

        $this->assertEquals(1, $tenant->staffMembersCount());

        $response = $this->actingAs($admin)->delete("/users/{$staff->id}");
        $response->assertRedirect('/settings?tab=team');

        $this->assertDatabaseMissing('users', ['id' => $staff->id]);
        $this->assertEquals(0, $tenant->fresh()->staffMembersCount());
    }

    public function test_super_admin_can_create_organization_with_initial_admin(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'tenant_id' => null,
            'is_active' => true,
        ]);

        $response = $this->actingAs($superAdmin)->post('/tenants', [
            'name' => 'Horizon Holdings',
            'plan' => 'enterprise',
            'lead_quota' => 50000,
            'admin_name' => 'Horizon Lead Admin',
            'admin_email' => 'horizon.lead@horizon.com',
            'admin_password' => 'horizonPass999',
            'admin_phone' => '+1 555-9876',
        ]);

        $response->assertRedirect('/tenants');

        $tenant = Tenant::where('name', 'Horizon Holdings')->first();
        $this->assertNotNull($tenant);

        $this->assertDatabaseHas('users', [
            'tenant_id' => $tenant->id,
            'name' => 'Horizon Lead Admin',
            'email' => 'horizon.lead@horizon.com',
            'role' => 'admin',
            'phone' => '+1 555-9876',
        ]);

        $adminUser = User::where('email', 'horizon.lead@horizon.com')->first();
        $this->assertTrue(Hash::check('horizonPass999', $adminUser->password));
    }
}
