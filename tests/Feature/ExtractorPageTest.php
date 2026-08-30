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

        $response = $this->actingAs($user)
            ->get('/extractor')
            ->assertOk()
            ->assertSee('VektorLeads')
            ->assertSee('Lead Extractor')
            ->assertSee('Industry / Business Category')
            ->assertSee('Start Extraction')
            ->assertSee('Extraction Status')
            ->assertDontSee('AWT Phone');

        $html = $response->getContent();
        $this->assertSame(1, substr_count($html, 'id="leadsClearBtn"'));
        $this->assertSame(1, substr_count($html, 'New Search'));
        $this->assertSame(0, substr_count($html, 'id="summaryCard"'));
        $this->assertSame(0, substr_count($html, 'Download Excel'));
        $this->assertSame(0, substr_count($html, 'id="clearAllResultsBtn"'));
        $this->assertStringContainsString('value="500"', $html);
        $this->assertStringNotContainsString('value="1000"', $html);
        $this->assertStringNotContainsString('value="1500"', $html);
        $this->assertStringNotContainsString('value="2500"', $html);
    }

    public function test_obtain_solutions_tenant_sees_high_extractor_limits(): void
    {
        $tenant = Tenant::query()->firstOrCreate(
            ['slug' => 'obtain-solutions'],
            [
                'name' => 'Obtain Solutions',
                'domain' => 'obtainsolutions.com',
                'plan' => 'enterprise',
                'lead_quota' => 100000,
            ]
        );

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'admin@obtainsolutions.com',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $html = $this->actingAs($user)
            ->get('/extractor')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('value="1000"', $html);
        $this->assertStringContainsString('value="1500"', $html);
        $this->assertStringContainsString('value="2500"', $html);
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
