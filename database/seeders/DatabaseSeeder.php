<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PlanSeeder::class);

        $starterPlan = Plan::where('slug', 'starter')->first();
        $enterprisePlan = Plan::where('slug', 'enterprise')->first();

        EmailTemplate::query()
            ->whereIn('name', [
                'B2B Partnership / Introduction',
                'Local Business Growth Proposal',
                'Follow-up & Digital Audit',
            ])
            ->delete();

        User::updateOrCreate(
            ['email' => 'superadmin@obtainsolutions.com'],
            [
                'name' => 'Super Administrator',
                'password' => Hash::make('Obtain@2026!'),
                'role' => 'super_admin',
                'tenant_id' => null,
                'phone' => '+1 (555) 010-0001',
                'is_active' => true,
            ]
        );

        $obtainTenant = Tenant::updateOrCreate(
            ['slug' => 'obtain-solutions'],
            [
                'name' => 'Obtain Solutions',
                'domain' => 'obtainsolutions.com',
                'plan' => 'enterprise',
                'plan_id' => $enterprisePlan->id,
                'lead_quota' => 100000,
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'admin@obtainsolutions.com'],
            [
                'tenant_id' => $obtainTenant->id,
                'name' => 'Obtain Admin',
                'password' => Hash::make('Obtain@2026!'),
                'role' => 'admin',
                'phone' => '+1 (555) 019-2834',
                'is_active' => true,
            ]
        );

        $generalTenant = Tenant::updateOrCreate(
            ['slug' => 'general'],
            [
                'name' => 'General Workspace',
                'domain' => 'general.test',
                'plan' => 'starter',
                'plan_id' => $starterPlan->id,
                'lead_quota' => 10000,
                'leads_extracted_count' => 0,
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'admin@general.test'],
            [
                'tenant_id' => $generalTenant->id,
                'name' => 'General Admin',
                'password' => Hash::make('Obtain@2026!'),
                'role' => 'admin',
                'phone' => null,
                'is_active' => true,
            ]
        );
    }
}
