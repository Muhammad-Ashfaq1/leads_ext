<?php

namespace App\Services;

use App\Models\GmailAccount;
use App\Models\GmailMessage;
use App\Models\LeadEmailLog;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Throwable;

class EmailReplyService
{
    public function __construct(
        protected GmailService $gmailService
    ) {}

    /**
     * Send a reply to an existing email message.
     */
    public function sendReply(
        GmailAccount $account,
        GmailMessage $originalMessage,
        string $replyBody,
        ?string $customSubject = null,
        ?User $sender = null
    ): array {
        $recipientEmail = trim($originalMessage->sender_email);
        if (empty($recipientEmail)) {
            throw new Exception('Original message does not have a valid sender email address.');
        }

        $subject = $customSubject ?: $originalMessage->subject;
        if (! str_starts_with(strtolower(trim($subject)), 're:')) {
            $subject = 'Re: ' . $subject;
        }

        $fromEmail = $account->email;
        $fromName = $account->name ?: ($sender?->name ?: 'Support');

        // Prepare quoted thread content
        $quotedOriginal = "\n\n--- Original Message ---\n" .
            "From: " . ($originalMessage->sender_name ?: $originalMessage->sender_email) . " <{$originalMessage->sender_email}>\n" .
            "Date: " . ($originalMessage->received_at ? $originalMessage->received_at->format('M d, Y h:i A') : '') . "\n" .
            "Subject: {$originalMessage->subject}\n\n" .
            ($originalMessage->body_text ?: strip_tags($originalMessage->body_html));

        $fullPlainBody = trim($replyBody) . $quotedOriginal;
        $fullHtmlBody = nl2br(e(trim($replyBody))) .
            '<br><br><div style="border-left: 2px solid #ccc; padding-left: 10px; color: #666; margin-top: 15px;">' .
            '<strong>From:</strong> ' . e($originalMessage->sender_name ?: $originalMessage->sender_email) . ' &lt;' . e($originalMessage->sender_email) . '&gt;<br>' .
            '<strong>Date:</strong> ' . ($originalMessage->received_at ? $originalMessage->received_at->format('M d, Y h:i A') : '') . '<br>' .
            '<strong>Subject:</strong> ' . e($originalMessage->subject) . '<br><br>' .
            ($originalMessage->body_html ?: nl2br(e($originalMessage->body_text))) .
            '</div>';

        // Dispatch based on provider
        if ($account->isHostinger() || $account->isImap()) {
            $this->sendViaSmtp($account, $recipientEmail, $subject, $fullPlainBody, $fullHtmlBody, $fromEmail, $fromName, $originalMessage);
        } elseif ($account->isGmail()) {
            $this->sendViaGmailApi($account, $recipientEmail, $subject, $fullPlainBody, $fullHtmlBody, $fromEmail, $fromName, $originalMessage);
        } else {
            // Fallback to SMTP
            $this->sendViaSmtp($account, $recipientEmail, $subject, $fullPlainBody, $fullHtmlBody, $fromEmail, $fromName, $originalMessage);
        }

        // Record in LeadEmailLog if matched lead
        if ($originalMessage->extracted_lead_id) {
            try {
                LeadEmailLog::create([
                    'tenant_id' => $account->tenant_id,
                    'user_id' => $sender?->id,
                    'extracted_lead_id' => $originalMessage->extracted_lead_id,
                    'recipient_email' => $recipientEmail,
                    'recipient_name' => $originalMessage->sender_name,
                    'subject' => $subject,
                    'body' => $fullPlainBody,
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);
            } catch (Throwable $e) {
                Log::warning('Could not write to lead_email_logs for reply', ['error' => $e->getMessage()]);
            }
        }

        return [
            'success' => true,
            'message' => "Reply successfully sent to {$recipientEmail}!",
            'recipient' => $recipientEmail,
            'subject' => $subject,
        ];
    }

    /**
     * Sends email directly using Hostinger/Custom SMTP.
     */
    protected function sendViaSmtp(
        GmailAccount $account,
        string $recipientEmail,
        string $subject,
        string $plainBody,
        string $htmlBody,
        string $fromEmail,
        string $fromName,
        GmailMessage $originalMessage
    ): void {
        $host = $account->smtp_host ?: 'smtp.hostinger.com';
        $port = $account->smtp_port ?: 465;
        $isTls = ($port === 465 || $account->smtp_encryption === 'ssl');

        $transport = new EsmtpTransport($host, $port, $isTls);
        $transport->setUsername($account->email);
        $transport->setPassword($account->password);

        $email = (new Email())
            ->from(new Address($fromEmail, $fromName))
            ->to(new Address($recipientEmail))
            ->subject($subject)
            ->text($plainBody)
            ->html($htmlBody);

        // Threading headers
        if (! empty($originalMessage->gmail_message_id)) {
            $refId = str_contains($originalMessage->gmail_message_id, '@')
                ? "<{$originalMessage->gmail_message_id}>"
                : "<{$originalMessage->gmail_message_id}@hostinger>";

            $email->getHeaders()->addTextHeader('In-Reply-To', $refId);
            $email->getHeaders()->addTextHeader('References', $refId);
        }

        $mailer = new \Symfony\Component\Mailer\Mailer($transport);
        $mailer->send($email);
    }

    /**
     * Sends email using Gmail API.
     */
    protected function sendViaGmailApi(
        GmailAccount $account,
        string $recipientEmail,
        string $subject,
        string $plainBody,
        string $htmlBody,
        string $fromEmail,
        string $fromName,
        GmailMessage $originalMessage
    ): void {
        $token = $this->gmailService->ensureValidToken($account);

        $email = (new Email())
            ->from(new Address($fromEmail, $fromName))
            ->to(new Address($recipientEmail))
            ->subject($subject)
            ->text($plainBody)
            ->html($htmlBody);

        if (! empty($originalMessage->gmail_message_id)) {
            $email->getHeaders()->addTextHeader('In-Reply-To', "<{$originalMessage->gmail_message_id}>");
            $email->getHeaders()->addTextHeader('References', "<{$originalMessage->gmail_message_id}>");
        }

        $rawRfc = $email->toString();
        $base64Raw = strtr(base64_encode($rawRfc), '+/', '-_');

        $response = Http::withToken($token)->post('https://gmail.googleapis.com/gmail/v1/users/me/messages/send', [
            'raw' => $base64Raw,
            'threadId' => $originalMessage->gmail_thread_id ?: null,
        ]);

        if (! $response->successful()) {
            throw new Exception('Failed to dispatch reply via Gmail API: ' . $response->body());
        }
    }
}
