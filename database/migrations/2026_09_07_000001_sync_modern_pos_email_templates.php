<?php

use App\Models\EmailTemplate;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        EmailTemplate::syncAllDefaultTemplates(force: true);
    }

    public function down(): void
    {
        // Non-destructive: existing templates remain in place
    }
};
