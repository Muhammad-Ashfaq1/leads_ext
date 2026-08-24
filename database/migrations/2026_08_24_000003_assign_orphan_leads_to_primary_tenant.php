<?php

use App\Models\ExtractedLead;
use App\Models\ExtractionJob;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tenant = Tenant::first();
        if (! $tenant) {
            $tenant = Tenant::create([
                'name' => 'Obtain Solutions',
                'slug' => 'obtain-solutions',
                'domain' => 'obtainsolutions.com',
                'plan' => 'enterprise',
                'lead_quota' => 100000,
                'leads_extracted_count' => 0,
                'is_active' => true,
            ]);
        }

        $admin = User::where('email', 'admin@obtainsolutions.com')->first();
        if ($admin && ! $admin->tenant_id) {
            $admin->update(['tenant_id' => $tenant->id]);
        }

        // Assign any orphan jobs & leads to this primary tenant
        DB::table('extraction_jobs')
            ->whereNull('tenant_id')
            ->update([
                'tenant_id' => $tenant->id,
                'user_id' => $admin?->id ?? DB::raw('user_id'),
            ]);

        DB::table('extracted_leads')
            ->whereNull('tenant_id')
            ->update([
                'tenant_id' => $tenant->id,
                'user_id' => $admin?->id ?? DB::raw('user_id'),
                'is_saved' => true,
                'status' => 'saved',
            ]);

        DB::table('extracted_leads')
            ->whereNull('status')
            ->orWhere('status', '')
            ->update([
                'status' => 'saved',
                'is_saved' => true,
            ]);
    }

    public function down(): void
    {
        // No down needed for data hygiene migration
    }
};

