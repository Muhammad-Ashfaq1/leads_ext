<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('extracted_leads', function (Blueprint $table) {
            if (! Schema::hasColumn('extracted_leads', 'uuid')) {
                $table->uuid('uuid')->nullable()->unique()->after('id');
            }
            if (! Schema::hasColumn('extracted_leads', 'generated_website_content')) {
                $table->json('generated_website_content')->nullable()->after('metadata');
            }
        });

        // Backfill UUID for existing records if null
        $leadsWithoutUuid = DB::table('extracted_leads')->whereNull('uuid')->get(['id']);
        foreach ($leadsWithoutUuid as $lead) {
            DB::table('extracted_leads')
                ->where('id', $lead->id)
                ->update(['uuid' => (string) Str::uuid()]);
        }
    }

    public function down(): void
    {
        Schema::table('extracted_leads', function (Blueprint $table) {
            if (Schema::hasColumn('extracted_leads', 'generated_website_content')) {
                $table->dropColumn('generated_website_content');
            }
            if (Schema::hasColumn('extracted_leads', 'uuid')) {
                $table->dropColumn('uuid');
            }
        });
    }
};

