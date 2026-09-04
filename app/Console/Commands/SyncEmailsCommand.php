<?php

namespace App\Console\Commands;

use App\Models\GmailAccount;
use App\Services\GmailService;
use App\Services\HostingerEmailService;
use Illuminate\Console\Command;
use Throwable;

class SyncEmailsCommand extends Command
{
    protected $signature = 'email:sync {--account= : Specific Gmail/Hostinger Account ID to sync} {--limit=30 : Max messages to sync per account}';
    protected $description = 'Synchronize incoming emails from connected Hostinger (IMAP) and Google Gmail accounts';

    public function handle(GmailService $gmailService, HostingerEmailService $hostingerService): int
    {
        $accountId = $this->option('account');
        $limit = (int) $this->option('limit');

        $query = GmailAccount::query()->where('is_active', true);
        if ($accountId) {
            $query->where('id', $accountId);
        }

        $accounts = $query->get();

        if ($accounts->isEmpty()) {
            $this->info('No active email accounts found to synchronize.');
            return self::SUCCESS;
        }

        $this->info("Found {$accounts->count()} active account(s) to synchronize.");

        foreach ($accounts as $account) {
            $this->line("Syncing [{$account->provider}] {$account->email} (Tenant #{$account->tenant_id})...");

            try {
                if ($account->isHostinger() || $account->isImap()) {
                    $result = $hostingerService->syncMessages($account, $limit);
                } else {
                    $result = $gmailService->syncMessages($account, $limit);
                }

                if ($result['success']) {
                    $this->info("  ✓ Synced {$result['synced_count']} messages ({$result['new_count']} new)");
                } else {
                    $this->error("  ✗ Sync failed: " . ($result['error'] ?? 'Unknown error'));
                }
            } catch (Throwable $e) {
                $this->error("  ✗ Exception: " . $e->getMessage());
            }
        }

        $this->info('Email synchronization routine finished.');
        return self::SUCCESS;
    }
}
