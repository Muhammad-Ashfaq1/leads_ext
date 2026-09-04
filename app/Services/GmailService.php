<?php

namespace App\Services;

use App\Models\ExtractedLead;
use App\Models\GmailAccount;
use App\Models\GmailMessage;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class GmailService
{
    protected string $clientId;
    protected string $clientSecret;
    protected string $redirectUri;

    public function __construct()
    {
        $this->clientId = config('services.google.client_id', '') ?? '';
        $this->clientSecret = config('services.google.client_secret', '') ?? '';
        $this->redirectUri = config('services.google.redirect_uri', '') ?? '';
    }

    public function isConfigured(): bool
    {
        return ! empty($this->clientId) && ! empty($this->clientSecret);
    }

    public function getAuthUrl(?string $state = null): string
    {
        $scopes = [
            'openid',
            'https://www.googleapis.com/auth/userinfo.email',
            'https://www.googleapis.com/auth/userinfo.profile',
            'https://www.googleapis.com/auth/gmail.readonly',
            'https://www.googleapis.com/auth/gmail.modify',
        ];

        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => implode(' ', $scopes),
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state ?? csrf_token(),
        ];

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    }

    public function handleCallback(string $code, User $user): GmailAccount
    {
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
            'grant_type' => 'authorization_code',
        ]);

        if (! $response->successful()) {
            Log::error('Gmail OAuth token exchange failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new Exception('Failed to authorize with Google: ' . ($response->json('error_description') ?? 'Invalid token response'));
        }

        $tokenData = $response->json();
        $accessToken = $tokenData['access_token'] ?? null;
        $refreshToken = $tokenData['refresh_token'] ?? null;
        $expiresIn = (int) ($tokenData['expires_in'] ?? 3600);

        if (! $accessToken) {
            throw new Exception('No access token returned from Google.');
        }

        // Fetch User Info (Google ID, Email, Name, Avatar)
        $userInfoResponse = Http::withToken($accessToken)->get('https://www.googleapis.com/oauth2/v2/userinfo');
        $userInfo = $userInfoResponse->json();
        $email = $userInfo['email'] ?? null;

        if (! $email) {
            throw new Exception('Could not determine Gmail address from Google account.');
        }

        $expiresAt = Carbon::now()->addSeconds($expiresIn);

        // Find or create account record
        $account = GmailAccount::where('tenant_id', $user->tenant_id)
            ->where('email', $email)
            ->first();

        if (! $account) {
            $account = new GmailAccount();
            $account->tenant_id = $user->tenant_id;
            $account->user_id = $user->id;
            $account->email = $email;
        }

        $account->google_id = $userInfo['id'] ?? null;
        $account->name = $userInfo['name'] ?? null;
        $account->avatar_url = $userInfo['picture'] ?? null;
        $account->access_token = $accessToken;
        if ($refreshToken) {
            $account->refresh_token = $refreshToken;
        }
        $account->token_expires_at = $expiresAt;
        $account->is_active = true;
        $account->sync_status = 'idle';
        $account->error_message = null;
        $account->save();

        return $account;
    }

    public function ensureValidToken(GmailAccount $account): string
    {
        if (! $account->isTokenExpired() && ! empty($account->access_token)) {
            return $account->access_token;
        }

        if (empty($account->refresh_token)) {
            $account->update([
                'sync_status' => 'error',
                'error_message' => 'Google account session expired. Please reconnect your Gmail account.',
            ]);
            throw new Exception('Gmail refresh token missing. Re-authentication required.');
        }

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $account->refresh_token,
            'grant_type' => 'refresh_token',
        ]);

        if (! $response->successful()) {
            $account->update([
                'sync_status' => 'error',
                'error_message' => 'Token refresh failed: ' . ($response->json('error_description') ?? 'Unknown error'),
            ]);
            throw new Exception('Failed to refresh Gmail access token.');
        }

        $tokenData = $response->json();
        $account->access_token = $tokenData['access_token'];
        $account->token_expires_at = Carbon::now()->addSeconds((int) ($tokenData['expires_in'] ?? 3600));
        $account->error_message = null;
        $account->save();

        return $account->access_token;
    }

    public function syncMessages(GmailAccount $account, int $maxResults = 30): array
    {
        $account->update(['sync_status' => 'syncing']);

        try {
            $token = $this->ensureValidToken($account);

            // Fetch recent messages
            $listResponse = Http::withToken($token)
                ->get('https://gmail.googleapis.com/gmail/v1/users/me/messages', [
                    'maxResults' => $maxResults,
                    'q' => 'in:inbox OR in:sent',
                ]);

            if (! $listResponse->successful()) {
                throw new Exception('Failed to fetch messages list: ' . $listResponse->body());
            }

            $messagesList = $listResponse->json('messages') ?? [];
            $syncedCount = 0;
            $newCount = 0;

            foreach ($messagesList as $msgItem) {
                $msgId = $msgItem['id'] ?? null;
                if (! $msgId) {
                    continue;
                }

                $detailResponse = Http::withToken($token)
                    ->get("https://gmail.googleapis.com/gmail/v1/users/me/messages/{$msgId}", [
                        'format' => 'full',
                    ]);

                if (! $detailResponse->successful()) {
                    continue;
                }

                $msgData = $detailResponse->json();
                $isNew = $this->processAndStoreMessage($account, $msgData);
                $syncedCount++;
                if ($isNew) {
                    $newCount++;
                }
            }

            $account->update([
                'sync_status' => 'idle',
                'error_message' => null,
                'last_synced_at' => now(),
            ]);

            return [
                'success' => true,
                'synced_count' => $syncedCount,
                'new_count' => $newCount,
                'account' => $account,
            ];
        } catch (Throwable $e) {
            Log::error('Gmail sync failed', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            $account->update([
                'sync_status' => 'error',
                'error_message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'synced_count' => 0,
                'new_count' => 0,
            ];
        }
    }

    public function processAndStoreMessage(GmailAccount $account, array $data): bool
    {
        $gmailMsgId = $data['id'] ?? '';
        $threadId = $data['threadId'] ?? null;
        $snippet = html_entity_decode($data['snippet'] ?? '', ENT_QUOTES, 'UTF-8');
        $labelIds = $data['labelIds'] ?? [];
        $isRead = ! in_array('UNREAD', $labelIds, true);
        $isStarred = in_array('STARRED', $labelIds, true);

        // Parse headers
        $headers = [];
        foreach ($data['payload']['headers'] ?? [] as $header) {
            $headers[strtolower($header['name'])] = $header['value'];
        }

        $subject = $headers['subject'] ?? '(No Subject)';
        $fromRaw = $headers['from'] ?? '';
        $toRaw = $headers['to'] ?? '';
        $dateRaw = $headers['date'] ?? null;

        $senderInfo = $this->parseEmailHeader($fromRaw);
        $senderName = $senderInfo['name'];
        $senderEmail = $senderInfo['email'];

        $recipientInfo = $this->parseEmailHeader($toRaw);
        $recipientEmail = $recipientInfo['email'];

        $receivedAt = null;
        if (! empty($data['internalDate'])) {
            $receivedAt = Carbon::createFromTimestampMs((int) $data['internalDate']);
        } elseif ($dateRaw) {
            try {
                $receivedAt = Carbon::parse($dateRaw);
            } catch (Throwable) {
                $receivedAt = now();
            }
        }

        // Parse Body Parts (text and HTML)
        $bodies = $this->extractBodyParts($data['payload'] ?? []);
        $bodyText = $bodies['text'] ?? '';
        $bodyHtml = $bodies['html'] ?? '';

        // If body text is empty, fall back to cleaned HTML
        if (empty($bodyText) && ! empty($bodyHtml)) {
            $bodyText = $this->cleanHtmlToText($bodyHtml);
        }

        $hasAttachments = $this->checkHasAttachments($data['payload'] ?? []);

        // Link with ExtractedLead if sender email exists in leads
        $leadId = $this->findMatchingLeadId($senderEmail, $account->tenant_id);

        $existing = GmailMessage::where('gmail_account_id', $account->id)
            ->where('gmail_message_id', $gmailMsgId)
            ->first();

        $isNew = ! $existing;

        if (! $existing) {
            $existing = new GmailMessage();
            $existing->tenant_id = $account->tenant_id;
            $existing->gmail_account_id = $account->id;
            $existing->gmail_message_id = $gmailMsgId;
        }

        $existing->gmail_thread_id = $threadId;
        $existing->extracted_lead_id = $leadId ?: $existing->extracted_lead_id;
        $existing->sender_name = $senderName;
        $existing->sender_email = $senderEmail;
        $existing->recipient_email = $recipientEmail;
        $existing->subject = $subject;
        $existing->snippet = $snippet;
        $existing->body_text = $bodyText;
        $existing->body_html = $bodyHtml;
        $existing->received_at = $receivedAt;
        $existing->is_read = $isRead;
        $existing->is_starred = $isStarred;
        $existing->labels = $labelIds;
        $existing->has_attachments = $hasAttachments;
        $existing->save();

        return $isNew;
    }

    public function parseEmailHeader(string $header): array
    {
        $header = trim($header);
        if (empty($header)) {
            return ['name' => '', 'email' => ''];
        }

        if (preg_match('/^(.*?)\s*<([^>]+)>$/u', $header, $matches)) {
            $name = trim($matches[1], " \t\n\r\0\x0B\"'");
            $email = strtolower(trim($matches[2]));
            return [
                'name' => $name ?: explode('@', $email)[0],
                'email' => $email,
            ];
        }

        if (filter_var($header, FILTER_VALIDATE_EMAIL)) {
            $email = strtolower($header);
            return [
                'name' => explode('@', $email)[0],
                'email' => $email,
            ];
        }

        return ['name' => $header, 'email' => strtolower($header)];
    }

    public function extractBodyParts(array $payload): array
    {
        $text = '';
        $html = '';

        $mimeType = $payload['mimeType'] ?? '';
        $parts = $payload['parts'] ?? [];

        if (empty($parts)) {
            $data = $payload['body']['data'] ?? '';
            $decoded = $this->decodeBase64Url($data);
            if (str_contains(strtolower($mimeType), 'text/html')) {
                $html = $decoded;
            } elseif (str_contains(strtolower($mimeType), 'text/plain')) {
                $text = $decoded;
            }
        } else {
            foreach ($parts as $part) {
                $partMime = strtolower($part['mimeType'] ?? '');
                $partData = $part['body']['data'] ?? '';

                if ($partMime === 'text/plain' && ! empty($partData)) {
                    $text .= $this->decodeBase64Url($partData);
                } elseif ($partMime === 'text/html' && ! empty($partData)) {
                    $html .= $this->decodeBase64Url($partData);
                } elseif (! empty($part['parts'])) {
                    $subBodies = $this->extractBodyParts($part);
                    if (! empty($subBodies['text'])) {
                        $text .= $subBodies['text'];
                    }
                    if (! empty($subBodies['html'])) {
                        $html .= $subBodies['html'];
                    }
                }
            }
        }

        return [
            'text' => trim($text),
            'html' => trim($html),
        ];
    }

    public function checkHasAttachments(array $payload): bool
    {
        $parts = $payload['parts'] ?? [];
        foreach ($parts as $part) {
            if (! empty($part['filename'])) {
                return true;
            }
            if (! empty($part['parts']) && $this->checkHasAttachments($part)) {
                return true;
            }
        }
        return false;
    }

    public function decodeBase64Url(string $data): string
    {
        if (empty($data)) {
            return '';
        }

        $sanitized = strtr($data, '-_', '+/');
        $decoded = base64_decode($sanitized, true);

        return $decoded !== false ? $decoded : '';
    }

    public function findMatchingLeadId(string $email, ?int $tenantId = null): ?int
    {
        if (empty($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        $email = strtolower(trim($email));

        $query = ExtractedLead::query();
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        // Search in emails JSON column or plain matches
        $lead = $query->where(function ($q) use ($email) {
            $q->whereJsonContains('emails', $email)
                ->orWhereJsonContains('emails', strtolower($email))
                ->orWhere('emails', 'LIKE', '%' . $email . '%');
        })->first();

        return $lead?->id;
    }

    public function toggleStar(GmailMessage $message): bool
    {
        $message->is_starred = ! $message->is_starred;
        $message->save();

        // Async or background update to Gmail API if active account
        try {
            if ($message->gmailAccount && ! empty($message->gmail_message_id)) {
                $token = $this->ensureValidToken($message->gmailAccount);
                $action = $message->is_starred ? 'addLabelIds' : 'removeLabelIds';
                Http::withToken($token)->post("https://gmail.googleapis.com/gmail/v1/users/me/messages/{$message->gmail_message_id}/modify", [
                    $action => ['STARRED'],
                ]);
            }
        } catch (Throwable $e) {
            Log::warning('Failed to sync star status to Gmail API', ['error' => $e->getMessage()]);
        }

        return $message->is_starred;
    }

    public function markAsRead(GmailMessage $message): void
    {
        if ($message->is_read) {
            return;
        }

        $message->is_read = true;
        $message->save();

        try {
            if ($message->gmailAccount && ! empty($message->gmail_message_id)) {
                $token = $this->ensureValidToken($message->gmailAccount);
                Http::withToken($token)->post("https://gmail.googleapis.com/gmail/v1/users/me/messages/{$message->gmail_message_id}/modify", [
                    'removeLabelIds' => ['UNREAD'],
                ]);
            }
        } catch (Throwable $e) {
            Log::warning('Failed to mark message as read on Gmail API', ['error' => $e->getMessage()]);
        }
    }

    public function cleanHtmlToText(string $html): string
    {
        if (empty($html)) {
            return '';
        }

        $html = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $html);
        $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
        $html = preg_replace('/<head\b[^>]*>(.*?)<\/head>/is', '', $html);
        $html = preg_replace('/<br\s*\/?>/i', "\n", $html);
        $html = preg_replace('/<\/p>/i', "\n\n", $html);
        $html = preg_replace('/<\/div>/i', "\n", $html);
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8');
        return trim(preg_replace("/[\r\n]{2,}/", "\n\n", $text));
    }
}
