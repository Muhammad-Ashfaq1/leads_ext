<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gmail_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('gmail_account_id')->constrained('gmail_accounts')->cascadeOnDelete();
            $table->foreignId('extracted_lead_id')->nullable()->constrained('extracted_leads')->nullOnDelete();
            $table->string('gmail_message_id')->index();
            $table->string('gmail_thread_id')->nullable()->index();
            $table->string('sender_name')->nullable();
            $table->string('sender_email')->index();
            $table->string('recipient_email')->nullable();
            $table->string('subject', 500)->nullable();
            $table->text('snippet')->nullable();
            $table->longText('body_text')->nullable();
            $table->longText('body_html')->nullable();
            $table->timestamp('received_at')->nullable()->index();
            $table->boolean('is_read')->default(false);
            $table->boolean('is_starred')->default(false);
            $table->json('labels')->nullable();
            $table->boolean('has_attachments')->default(false);
            $table->timestamps();

            $table->unique(['gmail_account_id', 'gmail_message_id']);
            $table->index(['tenant_id', 'received_at']);
            $table->index(['tenant_id', 'is_read']);
            $table->index(['tenant_id', 'extracted_lead_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gmail_messages');
    }
};
