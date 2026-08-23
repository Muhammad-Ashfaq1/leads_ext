<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('extracted_leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('extraction_job_id')->constrained('extraction_jobs')->cascadeOnDelete();
            $table->string('business_name')->nullable();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->json('emails')->nullable();
            $table->string('website')->nullable();
            $table->text('google_maps_url')->nullable();
            $table->string('place_id')->nullable();
            $table->string('category')->nullable();
            $table->decimal('rating', 3, 2)->nullable();
            $table->unsignedInteger('review_count')->nullable();
            $table->text('business_hours')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->string('source')->default('Google Maps');
            $table->json('metadata')->nullable();
            $table->timestamp('extracted_at')->nullable();
            $table->timestamps();

            $table->index(['extraction_job_id', 'place_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extracted_leads');
    }
};
