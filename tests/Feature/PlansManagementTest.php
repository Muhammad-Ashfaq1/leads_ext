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

    public function test_updating_a_plan_does_not_change_workspace_records(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'tenant_id' => null,
            'is_active' => true,
        ]);

        $plan = Plan::create([
            'name' => 'Pro',
            'slug' => 'pro',
            'price' => 79.00,
            'billing_interval' => 'monthly',
            'lead_quota' => 25000,
            'max_staff_members' => 5,
            'is_active' => true,
        ]);

        $tenant = Tenant::create([
            'name' => 'General Workspace',
            'slug' => 'general',
            'plan' => 'pro',
            'plan_id' => $plan->id,
            'lead_quota' => 10000,
            'is_active' => true,
        ]);

        $this->actingAs($superAdmin)->put("/plans/{$plan->id}", [
            'name' => 'Pro Plus',
            'price' => 89.00,
            'billing_interval' => 'monthly',
            'lead_quota' => 30000,
            'max_staff_members' => 6,
            'is_active' => '1',
        ])->assertRedirect('/plans');

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'name' => 'General Workspace',
            'slug' => 'general',
            'plan_id' => $plan->id,
            'lead_quota' => 10000,
        ]);
    }

    public function test_workspaces_and_plans_pages_use_a_single_shared_form_modal(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'tenant_id' => null,
            'is_active' => true,
        ]);

        $plan = Plan::create([
            'name' => 'Starter',
            'slug' => 'starter',
            'price' => 29.00,
            'billing_interval' => 'monthly',
            'lead_quota' => 5000,
            'max_staff_members' => 5,
            'is_active' => true,
        ]);

        $tenant = Tenant::create([
            'name' => 'General Workspace',
            'slug' => 'general',
            'plan' => 'starter',
            'plan_id' => $plan->id,
            'lead_quota' => 10000,
            'is_active' => true,
        ]);

        $plansHtml = $this->actingAs($superAdmin)->get('/plans')->assertOk()->getContent();
        $this->assertSame(1, substr_count($plansHtml, 'id="planFormModal"'));
        $this->assertStringNotContainsString('id="editPlanModal'.$plan->id.'"', $plansHtml);
        $this->assertStringNotContainsString('id="createPlanModal"', $plansHtml);
        $this->assertTrue(strpos($plansHtml, 'id="planFormModal"') > strpos($plansHtml, '</table>'));
        $this->assertStringContainsString('data-id="'.$plan->id.'"', $plansHtml);

        $tenantsHtml = $this->actingAs($superAdmin)->get('/tenants')->assertOk()->getContent();
        $this->assertSame(1, substr_count($tenantsHtml, 'id="tenantFormModal"'));
        $this->assertStringNotContainsString('id="editTenantModal'.$tenant->id.'"', $tenantsHtml);
        $this->assertStringNotContainsString('id="createTenantModal"', $tenantsHtml);
        $this->assertTrue(strpos($tenantsHtml, 'id="tenantFormModal"') > strpos($tenantsHtml, '</table>'));
        $this->assertStringContainsString('data-id="'.$tenant->id.'"', $tenantsHtml);
    }

    public function test_super_admin_can_fetch_plan_json_for_the_edit_modal(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'tenant_id' => null,
            'is_active' => true,
        ]);

        $plan = Plan::create([
            'name' => 'Starter',
            'slug' => 'starter',
            'price' => 29.00,
            'billing_interval' => 'monthly',
            'lead_quota' => 5000,
            'max_staff_members' => 5,
            'description' => 'Entry tier',
            'features' => ['Cloud Lead Finder', 'CSV Export'],
            'is_active' => true,
            'is_default' => true,
        ]);

        $this->actingAs($superAdmin)
            ->getJson("/plans/{$plan->id}")
            ->assertOk()
            ->assertJson([
                'id' => $plan->id,
                'name' => 'Starter',
                'price' => '29.00',
                'billing_interval' => 'monthly',
                'lead_quota' => 5000,
                'max_staff_members' => 5,
                'description' => 'Entry tier',
                'features' => "Cloud Lead Finder\nCSV Export",
                'is_active' => true,
                'is_default' => true,
            ]);
    }

    public function test_super_admin_can_fetch_workspace_json_for_the_edit_modal(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'tenant_id' => null,
            'is_active' => true,
        ]);

        $plan = Plan::create([
            'name' => 'Starter',
            'slug' => 'starter',
            'price' => 29.00,
            'billing_interval' => 'monthly',
            'lead_quota' => 5000,
            'max_staff_members' => 5,
            'is_active' => true,
        ]);

        $tenant = Tenant::create([
            'name' => 'General Workspace',
            'slug' => 'general',
            'domain' => 'general.test',
            'plan' => 'starter',
            'plan_id' => $plan->id,
            'lead_quota' => 10000,
            'google_maps_api_key' => 'AIza-test-key',
            'is_active' => true,
        ]);

        $this->actingAs($superAdmin)
            ->getJson("/tenants/{$tenant->id}")
            ->assertOk()
            ->assertJson([
                'id' => $tenant->id,
                'name' => 'General Workspace',
                'domain' => 'general.test',
                'plan_id' => $plan->id,
                'plan_name' => 'Starter',
                'lead_quota' => 10000,
                'google_maps_api_key' => 'AIza-test-key',
                'is_active' => true,
            ]);
    }

    public function test_super_admin_can_update_workspace_plan_without_breaking_other_fields(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'tenant_id' => null,
            'is_active' => true,
        ]);

        $starter = Plan::create([
            'name' => 'Starter',
            'slug' => 'starter',
            'price' => 29.00,
            'billing_interval' => 'monthly',
            'lead_quota' => 5000,
            'max_staff_members' => 5,
            'is_active' => true,
        ]);

        $pro = Plan::create([
            'name' => 'Pro',
            'slug' => 'pro',
            'price' => 79.00,
            'billing_interval' => 'monthly',
            'lead_quota' => 25000,
            'max_staff_members' => 5,
            'is_active' => true,
        ]);

        $tenant = Tenant::create([
            'name' => 'General Workspace',
            'slug' => 'general',
            'plan' => 'starter',
            'plan_id' => $starter->id,
            'lead_quota' => 10000,
            'is_active' => true,
        ]);

        $this->actingAs($superAdmin)->put("/tenants/{$tenant->id}", [
            'name' => 'General Workspace',
            'plan_id' => $pro->id,
            'lead_quota' => 10000,
            'is_active' => '1',
        ])->assertRedirect('/tenants');

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'name' => 'General Workspace',
            'slug' => 'general',
            'plan' => 'pro',
            'plan_id' => $pro->id,
            'lead_quota' => 10000,
            'is_active' => true,
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

        $plan = Plan::create([
            'name' => 'Hidden Plan',
            'slug' => 'hidden-plan',
            'price' => 10.00,
            'billing_interval' => 'monthly',
            'lead_quota' => 1000,
            'max_staff_members' => 2,
            'is_active' => true,
        ]);

        $this->actingAs($admin)->getJson("/plans/{$plan->id}")->assertForbidden();
        $this->actingAs($admin)->getJson('/tenants/'.$tenant->id)->assertForbidden();
    }
}
