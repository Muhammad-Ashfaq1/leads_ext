<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExtractorPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/extractor')
            ->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_extractor_page(): void
    {
        $tenant = Tenant::create([
            'name' => 'Acme Corporation',
            'slug' => 'acme',
            'plan' => 'enterprise',
            'lead_quota' => 10000,
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get('/extractor')
            ->assertOk()
            ->assertSee('VektorLeads')
            ->assertSee('Lead Extractor')
            ->assertSee('Industry / Business Category')
            ->assertSee('Start Extraction')
            ->assertSee('Extraction Status')
            ->assertDontSee('AWT Phone');
    }

    public function test_dashboard_renders_metrics(): void
    {
        $tenant = Tenant::create([
            'name' => 'Acme Corporation',
            'slug' => 'acme',
            'plan' => 'enterprise',
            'lead_quota' => 10000,
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Total Leads')
            ->assertSee('Email Discovery')
            ->assertSee('Phone Coverage')
            ->assertSee('VektorLeads');
    }
}
