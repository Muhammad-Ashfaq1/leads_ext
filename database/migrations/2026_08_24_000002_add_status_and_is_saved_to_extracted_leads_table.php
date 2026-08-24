<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('extracted_leads', function (Blueprint $table) {
            $table->string('status')->default('saved')->after('source')->index();
            $table->boolean('is_saved')->default(true)->after('status')->index();
        });
    }

    public function down(): void
    {
        Schema::table('extracted_leads', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['is_saved']);
            $table->dropColumn(['status', 'is_saved']);
        });
    }
};

