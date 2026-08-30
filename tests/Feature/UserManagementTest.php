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

    public function test_super_admin_can_view_users_page_with_stats_and_filters(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'tenant_id' => null,
            'is_active' => true,
        ]);

        $tenant = Tenant::create([
            'name' => 'Acme Corp',
            'slug' => 'acme-corp',
            'plan' => 'pro',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'admin',
            'name' => 'Acme Admin',
            'email' => 'admin@acme.com',
            'is_active' => true,
        ]);

        $response = $this->actingAs($superAdmin)->get('/users');

        $response->assertOk()
            ->assertSee('Team &amp; User Accounts', false)
            ->assertSee('Register New User')
            ->assertSee('Acme Admin')
            ->assertSee('admin@acme.com')
            ->assertSee('Global Platform');
    }

    public function test_super_admin_can_register_new_super_admin(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'tenant_id' => null,
            'is_active' => true,
        ]);

        $response = $this->actingAs($superAdmin)->post('/users', [
            'name' => 'New Super Admin',
            'email' => 'super@platform.com',
            'password' => 'secret123',
            'role' => 'super_admin',
            'tenant_id' => '',
            'phone' => '+1 555-1111',
            'is_active' => '1',
        ]);

        $response->assertRedirect('/users');
        $this->assertDatabaseHas('users', [
            'name' => 'New Super Admin',
            'email' => 'super@platform.com',
            'role' => 'super_admin',
            'tenant_id' => null,
            'phone' => '+1 555-1111',
            'is_active' => true,
        ]);

        $created = User::where('email', 'super@platform.com')->first();
        $this->assertTrue(Hash::check('secret123', $created->password));
    }

    public function test_super_admin_can_register_workspace_admin_for_tenant(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'tenant_id' => null,
            'is_active' => true,
        ]);

        $tenant = Tenant::create([
            'name' => 'Starlight Media',
            'slug' => 'starlight-media',
            'plan' => 'enterprise',
        ]);

        $response = $this->actingAs($superAdmin)->post('/users', [
            'name' => 'Starlight Lead',
            'email' => 'lead@starlight.com',
            'password' => 'pass123456',
            'role' => 'admin',
            'tenant_id' => $tenant->id,
            'phone' => '+1 555-2222',
            'is_active' => '1',
        ]);

        $response->assertRedirect('/users');
        $this->assertDatabaseHas('users', [
            'name' => 'Starlight Lead',
            'email' => 'lead@starlight.com',
            'role' => 'admin',
            'tenant_id' => $tenant->id,
        ]);
    }

    public function test_workspace_admin_can_register_team_member_for_own_workspace_only(): void
    {
        $tenant1 = Tenant::create(['name' => 'Tenant One', 'slug' => 't1', 'plan' => 'pro']);
        $tenant2 = Tenant::create(['name' => 'Tenant Two', 'slug' => 't2', 'plan' => 'pro']);

        $admin1 = User::factory()->create([
            'tenant_id' => $tenant1->id,
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin1)->post('/users', [
            'name' => 'Member One',
            'email' => 'member1@t1.com',
            'password' => 'mypassword',
            'role' => 'user',
            'tenant_id' => $tenant2->id,
        ]);

        $response->assertRedirect('/users');
        $this->assertDatabaseHas('users', [
            'name' => 'Member One',
            'email' => 'member1@t1.com',
            'role' => 'user',
            'tenant_id' => $tenant1->id,
        ]);
    }

    public function test_super_admin_can_filter_users_by_role_and_search(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'tenant_id' => null,
            'is_active' => true,
        ]);

        $tenant = Tenant::create(['name' => 'Apex Agency', 'slug' => 'apex', 'plan' => 'pro']);

        User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'admin',
            'name' => 'Apex Manager',
            'email' => 'manager@apex.com',
        ]);

        User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'user',
            'name' => 'Apex Hunter',
            'email' => 'hunter@apex.com',
        ]);

        $resRole = $this->actingAs($superAdmin)->get('/users?role=admin');
        $resRole->assertOk()
            ->assertSee('Apex Manager')
            ->assertDontSee('Apex Hunter');

        $resSearch = $this->actingAs($superAdmin)->get('/users?search=Hunter');
        $resSearch->assertOk()
            ->assertSee('Apex Hunter')
            ->assertDontSee('Apex Manager');
    }
}
