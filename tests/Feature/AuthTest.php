<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_renders_successfully(): void
    {
        $response = $this->get('/login');
        $response->assertOk();
        $response->assertSee('Leads Engine');
        $response->assertSee('Sign In');
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $tenant = Tenant::create([
            'name' => 'Acme Corporation',
            'slug' => 'acme',
            'plan' => 'pro',
        ]);

        $user = User::factory()->create([
            'email' => 'admin@acme.com',
            'password' => Hash::make('password'),
            'tenant_id' => $tenant->id,
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@acme.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_cannot_login_with_invalid_password(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@acme.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@acme.com',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_tenant_admin_cannot_access_super_admin_routes(): void
    {
        $tenant = Tenant::create([
            'name' => 'Acme Corporation',
            'slug' => 'acme',
            'plan' => 'pro',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get('/tenants')
            ->assertForbidden();
    }

    public function test_super_admin_can_access_tenants_page(): void
    {
        $user = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get('/tenants')
            ->assertOk()
            ->assertSee('SaaS Tenants & Organizations');
    }
}
