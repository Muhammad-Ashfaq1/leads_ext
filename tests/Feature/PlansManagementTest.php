<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlansManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_plans_page(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'tenant_id' => null,
            'is_active' => true,
        ]);

        Plan::create([
            'name' => 'Growth Pro',
            'slug' => 'growth-pro',
            'price' => 49.00,
            'billing_interval' => 'monthly',
            'lead_quota' => 15000,
            'max_staff_members' => 5,
            'is_active' => true,
        ]);

        $response = $this->actingAs($superAdmin)->get('/plans');

        $response->assertOk()
            ->assertSee('Subscription &amp; Pricing Plans', false)
            ->assertSee('Growth Pro')
            ->assertSee('$49/mo')
            ->assertSee('15,000');
    }

    public function test_super_admin_can_create_new_plan(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'tenant_id' => null,
            'is_active' => true,
        ]);

        $response = $this->actingAs($superAdmin)->post('/plans', [
            'name' => 'Scale Tier',
            'price' => 129.00,
            'billing_interval' => 'monthly',
            'lead_quota' => 40000,
            'max_staff_members' => 8,
            'description' => 'For fast growing businesses',
            'features' => "Feature 1\nFeature 2",
            'is_active' => '1',
            'is_default' => '1',
        ]);

        $response->assertRedirect('/plans');

        $this->assertDatabaseHas('plans', [
            'name' => 'Scale Tier',
            'slug' => 'scale-tier',
            'price' => 129.00,
            'lead_quota' => 40000,
            'max_staff_members' => 8,
            'is_default' => true,
        ]);
    }

    public function test_super_admin_can_update_plan(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'tenant_id' => null,
            'is_active' => true,
        ]);

        $plan = Plan::create([
            'name' => 'Old Plan Name',
            'slug' => 'old-plan-name',
            'price' => 20.00,
            'billing_interval' => 'monthly',
            'lead_quota' => 2000,
            'max_staff_members' => 3,
            'is_active' => true,
        ]);

        $response = $this->actingAs($superAdmin)->put("/plans/{$plan->id}", [
            'name' => 'Updated Plan Name',
            'price' => 35.00,
            'billing_interval' => 'monthly',
            'lead_quota' => 6000,
            'max_staff_members' => 5,
            'is_active' => '1',
        ]);

        $response->assertRedirect('/plans');

        $this->assertDatabaseHas('plans', [
            'id' => $plan->id,
            'name' => 'Updated Plan Name',
            'price' => 35.00,
            'lead_quota' => 6000,
        ]);
    }

    public function test_super_admin_can_delete_unused_plan(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'tenant_id' => null,
            'is_active' => true,
        ]);

        $plan = Plan::create([
            'name' => 'Temporary Plan',
            'slug' => 'temp-plan',
            'price' => 10.00,
            'billing_interval' => 'monthly',
            'lead_quota' => 1000,
            'max_staff_members' => 2,
            'is_active' => true,
        ]);

        $response = $this->actingAs($superAdmin)->delete("/plans/{$plan->id}");
        $response->assertRedirect('/plans');

        $this->assertDatabaseMissing('plans', ['id' => $plan->id]);
    }

    public function test_plan_with_workspaces_is_deactivated_instead_of_hard_deleted(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'tenant_id' => null,
            'is_active' => true,
        ]);

        $plan = Plan::create([
            'name' => 'Active Business Plan',
            'slug' => 'active-biz',
            'price' => 99.00,
            'billing_interval' => 'monthly',
            'lead_quota' => 30000,
            'max_staff_members' => 5,
            'is_active' => true,
        ]);

        Tenant::create([
            'name' => 'Subscribed Org',
            'slug' => 'subscribed-org',
            'plan' => 'active-biz',
            'plan_id' => $plan->id,
            'lead_quota' => 30000,
        ]);

        $response = $this->actingAs($superAdmin)->delete("/plans/{$plan->id}");
        $response->assertRedirect('/plans');

        // Still exists in database but is marked inactive
        $this->assertDatabaseHas('plans', [
            'id' => $plan->id,
            'is_active' => false,
        ]);
    }

    public function test_workspace_admin_cannot_access_plans_crud(): void
    {
        $tenant = Tenant::create(['name' => 'Test Tenant', 'slug' => 'test-tenant', 'plan' => 'pro']);
        $admin = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get('/plans');
        $response->assertForbidden();
    }
}
