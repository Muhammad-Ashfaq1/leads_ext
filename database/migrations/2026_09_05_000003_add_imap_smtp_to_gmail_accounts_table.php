<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gmail_accounts', function (Blueprint $table): void {
            $table->string('provider')->default('hostinger')->after('user_id'); // hostinger, gmail, custom_imap
            $table->text('password')->nullable()->after('avatar_url');
            $table->string('imap_host')->nullable()->default('imap.hostinger.com')->after('password');
            $table->integer('imap_port')->nullable()->default(993)->after('imap_host');
            $table->string('imap_encryption')->nullable()->default('ssl')->after('imap_port');
            $table->string('smtp_host')->nullable()->default('smtp.hostinger.com')->after('imap_encryption');
            $table->integer('smtp_port')->nullable()->default(465)->after('smtp_host');
            $table->string('smtp_encryption')->nullable()->default('ssl')->after('smtp_port');
            $table->text('access_token')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('gmail_accounts', function (Blueprint $table): void {
            $table->dropColumn([
                'provider',
                'password',
                'imap_host',
                'imap_port',
                'imap_encryption',
                'smtp_host',
                'smtp_port',
                'smtp_encryption',
            ]);
        });
    }
};
