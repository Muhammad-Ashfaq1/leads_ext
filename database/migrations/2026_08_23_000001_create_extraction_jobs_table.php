<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('extraction_jobs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('prompt');
            $table->string('query');
            $table->string('status')->default('idle');
            $table->unsignedInteger('limit')->default(100);
            $table->string('mode')->default('live');
            $table->unsignedInteger('businesses_seen')->default(0);
            $table->unsignedInteger('leads_extracted')->default(0);
            $table->unsignedInteger('emails_found')->default(0);
            $table->unsignedInteger('websites_found')->default(0);
            $table->string('current_activity')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extraction_jobs');
    }
};
