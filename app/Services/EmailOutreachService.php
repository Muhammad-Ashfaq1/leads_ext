<?php

namespace App\Services;

use App\Models\EmailTemplate;
use App\Models\ExtractedLead;
use App\Models\LeadEmailLog;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class EmailOutreachService
{
    public function renderVariables(string $text, ExtractedLead $lead, ?User $sender = null): string
    {
        $emails = is_array($lead->emails) ? $lead->emails : (array) $lead->emails;
        $primaryEmail = $emails[0] ?? '';

        $vars = [
            '{{business_name}}' => $lead->business_name ?? 'Business Owner',
            '{{email}}' => $primaryEmail,
            '{{phone}}' => $lead->phone ?? '',
            '{{website}}' => $lead->website ?? '',
            '{{category}}' => $lead->category ?? 'your business',
            '{{address}}' => $lead->address ?? '',
            '{{city}}' => $lead->city ?: (explode(',', $lead->address ?? '')[0] ?? ''),
            '{{rating}}' => $lead->rating ? (string) $lead->rating : '',
            '{{reviews}}' => $lead->review_count ? (string) $lead->review_count : '',
            '{{sender_name}}' => $sender?->name ?? 'Our Team',
            '{{sender_company}}' => $sender?->tenant?->name ?? config('app.name', 'VektorLeads'),
        ];

        return str_replace(array_keys($vars), array_values($vars), $text);
    }

    public function sendSingle(
        ExtractedLead $lead,
        string $subjectTemplate,
        string $bodyTemplate,
        ?int $templateId = null,
        ?User $sender = null,
    ): LeadEmailLog {
        $emails = is_array($lead->emails) ? $lead->emails : (array) $lead->emails;
        $recipientEmail = trim($emails[0] ?? '');

        if (empty($recipientEmail)) {
            return LeadEmailLog::create([
                'tenant_id' => $lead->tenant_id ?? $sender?->tenant_id,
                'user_id' => $sender?->id,
                'extracted_lead_id' => $lead->id,
                'email_template_id' => $templateId,
                'recipient_email' => 'unknown@example.com',
                'recipient_name' => $lead->business_name,
                'subject' => $this->renderVariables($subjectTemplate, $lead, $sender),
                'body' => $this->renderVariables($bodyTemplate, $lead, $sender),
                'status' => 'failed',
                'error_message' => 'No valid email address available for this lead.',
            ]);
        }

        $renderedSubject = $this->renderVariables($subjectTemplate, $lead, $sender);
        $renderedBody = $this->renderVariables($bodyTemplate, $lead, $sender);
        $recipientName = $lead->business_name;

        $tenantId = $lead->tenant_id ?? $sender?->tenant_id;
        $userId = $sender?->id;

        $status = 'sent';
        $errorMessage = null;

        try {
            Mail::html($renderedBody, function ($message) use ($recipientEmail, $recipientName, $renderedSubject, $sender): void {
                $fromEmail = config('mail.from.address', 'hello@vektorleads.io');
                $fromName = $sender?->tenant?->name ?? config('mail.from.name', 'VektorLeads');

                $message->to($recipientEmail, $recipientName)
                    ->from($fromEmail, $fromName)
                    ->subject($renderedSubject);

                if ($sender && ! empty($sender->email)) {
                    $message->replyTo($sender->email, $sender->name);
                }
            });
        } catch (Throwable $e) {
            $status = 'failed';
            $errorMessage = $e->getMessage();
            Log::error('Lead email dispatch failed', [
                'lead_id' => $lead->id,
                'recipient' => $recipientEmail,
                'error' => $e->getMessage(),
            ]);
        }

        return LeadEmailLog::create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'extracted_lead_id' => $lead->id,
            'email_template_id' => $templateId,
            'recipient_email' => $recipientEmail,
            'recipient_name' => $recipientName,
            'subject' => $renderedSubject,
            'body' => $renderedBody,
            'status' => $status,
            'error_message' => $errorMessage,
            'sent_at' => $status === 'sent' ? now() : null,
        ]);
    }

    public function sendBulk(
        array $leadIds,
        string $subjectTemplate,
        string $bodyTemplate,
        ?int $templateId = null,
        ?User $sender = null,
        ?int $tenantId = null,
        bool $isSuperAdmin = false,
    ): array {
        $leads = ExtractedLead::query()
            ->when(! $isSuperAdmin && $tenantId, function ($q) use ($tenantId): void {
                $q->where(function ($sub) use ($tenantId): void {
                    $sub->where('tenant_id', $tenantId)->orWhereNull('tenant_id');
                });
            })
            ->whereIn('id', $leadIds)
            ->get();

        $sentCount = 0;
        $failedCount = 0;
        $skippedCount = 0;
        $logs = [];

        foreach ($leads as $lead) {
            $emails = is_array($lead->emails) ? $lead->emails : (array) $lead->emails;
            $email = trim($emails[0] ?? '');

            if (empty($email)) {
                $skippedCount++;
                continue;
            }

            $log = $this->sendSingle($lead, $subjectTemplate, $bodyTemplate, $templateId, $sender);
            $logs[] = $log;

            if ($log->status === 'sent') {
                $sentCount++;
            } else {
                $failedCount++;
            }
        }

        return [
            'total_requested' => count($leadIds),
            'sent_count' => $sentCount,
            'failed_count' => $failedCount,
            'skipped_count' => $skippedCount,
            'logs' => $logs,
        ];
    }
}

