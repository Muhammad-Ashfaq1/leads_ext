<?php

namespace Tests\Feature;

use App\Models\EmailTemplate;
use App\Models\ExtractedLead;
use App\Models\Tenant;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_three_admins_without_dummy_records(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('users', [
            'email' => 'superadmin@obtainsolutions.com',
            'role' => 'super_admin',
            'tenant_id' => null,
        ]);

        $obtainTenantId = Tenant::where('slug', 'obtain-solutions')->value('id');
        $generalTenantId = Tenant::where('slug', 'general')->value('id');

        $this->assertNotNull($obtainTenantId);
        $this->assertNotNull($generalTenantId);

        $this->assertDatabaseHas('users', [
            'email' => 'admin@obtainsolutions.com',
            'role' => 'admin',
            'tenant_id' => $obtainTenantId,
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'admin@general.test',
            'role' => 'admin',
            'tenant_id' => $generalTenantId,
        ]);

        $this->assertSame(0, EmailTemplate::count());
        $this->assertSame(0, ExtractedLead::where('tenant_id', $generalTenantId)->count());
    }
}
