<?php

namespace App\Services;

use App\Models\ExtractedLead;
use App\Models\GmailAccount;
use App\Models\GmailMessage;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use Throwable;

class HostingerEmailService
{
    /**
     * Test IMAP and SMTP credentials.
     */
    public function testConnection(
        string $email,
        string $password,
        string $imapHost = 'imap.hostinger.com',
        int $imapPort = 993,
        string $smtpHost = 'smtp.hostinger.com',
        int $smtpPort = 465,
    ): array {
        // 1. Test IMAP Connection
        try {
            $socket = $this->connectImapSocket($imapHost, $imapPort);
            $this->loginImap($socket, $email, $password);
            $this->sendCommand($socket, 'A999 LOGOUT');
            fclose($socket);
        } catch (Throwable $e) {
            return [
                'success' => false,
                'error' => 'IMAP Connection Failed: ' . $e->getMessage(),
            ];
        }

        // 2. Test SMTP Connection
        try {
            $this->testSmtpAuth($smtpHost, $smtpPort, $email, $password);
        } catch (Throwable $e) {
            return [
                'success' => false,
                'error' => 'SMTP Authentication Failed: ' . $e->getMessage(),
            ];
        }

        return [
            'success' => true,
            'message' => 'Successfully verified Hostinger IMAP & SMTP credentials!',
        ];
    }

    /**
     * Synchronize messages from Hostinger via IMAP.
     */
    public function syncMessages(GmailAccount $account, int $limit = 40): array
    {
        $account->update(['sync_status' => 'syncing']);

        try {
            $imapHost = $account->imap_host ?: 'imap.hostinger.com';
            $imapPort = $account->imap_port ?: 993;
            $email = $account->email;
            $password = $account->password;

            if (empty($password)) {
                throw new Exception('Email password not configured.');
            }

            $socket = $this->connectImapSocket($imapHost, $imapPort);
            $this->loginImap($socket, $email, $password);

            // Select INBOX
            $selectRes = $this->sendCommand($socket, 'A002 SELECT "INBOX"');
            if (! str_contains($selectRes, 'A002 OK')) {
                throw new Exception('Could not select INBOX: ' . $selectRes);
            }

            // Search all messages
            $searchRes = $this->sendCommand($socket, 'A003 SEARCH ALL');
            $msgIds = $this->parseSearchResponse($searchRes);

            // Fetch newest messages first
            $msgIds = array_reverse($msgIds);
            $msgIdsToFetch = array_slice($msgIds, 0, $limit);

            $syncedCount = 0;
            $newCount = 0;

            foreach ($msgIdsToFetch as $idx => $msgSeqId) {
                $tag = 'F' . str_pad((string) ($idx + 10), 4, '0', STR_PAD_LEFT);
                $rawEmail = $this->fetchRawMessage($socket, $tag, $msgSeqId);

                if (! empty($rawEmail)) {
                    $isNew = $this->processAndStoreRawEmail($account, $rawEmail, (string) $msgSeqId);
                    $syncedCount++;
                    if ($isNew) {
                        $newCount++;
                    }
                }
            }

            $this->sendCommand($socket, 'A999 LOGOUT');
            fclose($socket);

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
            Log::error('Hostinger IMAP sync error', [
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

    /**
     * Process and store a parsed email message.
     */
    public function processAndStoreRawEmail(GmailAccount $account, string $rawEmail, string $seqId): bool
    {
        $parsed = $this->parseRfc822Email($rawEmail);

        $messageId = $parsed['message_id'] ?: 'hostinger_' . md5($parsed['subject'] . $parsed['from_email'] . $parsed['date_raw'] . $seqId);
        $subject = $parsed['subject'] ?: '(No Subject)';
        $senderName = $parsed['from_name'];
        $senderEmail = $parsed['from_email'];
        $recipientEmail = $parsed['to_email'] ?: $account->email;
        $receivedAt = $parsed['date'];
        $bodyText = $parsed['body_text'];
        $bodyHtml = $parsed['body_html'];
        $cleanSnippetText = $bodyText ?: $this->cleanHtmlToText($bodyHtml);
        $snippet = mb_substr(trim(preg_replace('/\s+/', ' ', $cleanSnippetText)), 0, 150);

        // Match with extracted prospects
        $leadId = $this->findMatchingLeadId($senderEmail, $account->tenant_id);

        $existing = GmailMessage::where('gmail_account_id', $account->id)
            ->where('gmail_message_id', $messageId)
            ->first();

        $isNew = ! $existing;

        if (! $existing) {
            $existing = new GmailMessage();
            $existing->tenant_id = $account->tenant_id;
            $existing->gmail_account_id = $account->id;
            $existing->gmail_message_id = $messageId;
        }

        $existing->gmail_thread_id = $parsed['in_reply_to'] ?: $messageId;
        $existing->extracted_lead_id = $leadId ?: $existing->extracted_lead_id;
        $existing->sender_name = $senderName;
        $existing->sender_email = $senderEmail;
        $existing->recipient_email = $recipientEmail;
        $existing->subject = $subject;
        $existing->snippet = $snippet;
        $existing->body_text = $bodyText;
        $existing->body_html = $bodyHtml;
        $existing->received_at = $receivedAt;
        $existing->is_read = $existing->exists ? $existing->is_read : false;
        $existing->save();

        return $isNew;
    }

    /**
     * Parses RFC822 raw email into clean components.
     */
    public function parseRfc822Email(string $raw): array
    {
        $raw = str_replace("\r\n", "\n", $raw);
        $splitPos = strpos($raw, "\n\n");

        if ($splitPos === false) {
            $headersRaw = $raw;
            $bodyRaw = '';
        } else {
            $headersRaw = substr($raw, 0, $splitPos);
            $bodyRaw = substr($raw, $splitPos + 2);
        }

        // Parse Header Fields with folding support
        $headers = [];
        $currentHeader = '';

        foreach (explode("\n", $headersRaw) as $line) {
            if (preg_match('/^([A-Za-z0-9\-]+):\s*(.*)$/', $line, $matches)) {
                $currentHeader = strtolower($matches[1]);
                $headers[$currentHeader] = $matches[2];
            } elseif ($currentHeader !== '' && (str_starts_with($line, " ") || str_starts_with($line, "\t"))) {
                $headers[$currentHeader] .= ' ' . trim($line);
            }
        }

        // Subject
        $subject = $this->decodeMimeHeader($headers['subject'] ?? '');

        // From Header
        $fromRaw = $this->decodeMimeHeader($headers['from'] ?? '');
        $fromInfo = $this->parseEmailAddress($fromRaw);

        // To Header
        $toRaw = $this->decodeMimeHeader($headers['to'] ?? '');
        $toInfo = $this->parseEmailAddress($toRaw);

        // Date
        $dateRaw = $headers['date'] ?? null;
        $date = null;
        if ($dateRaw) {
            try {
                $date = Carbon::parse($dateRaw);
            } catch (Throwable) {
                $date = now();
            }
        } else {
            $date = now();
        }

        // Message-ID, In-Reply-To
        $messageId = trim($headers['message-id'] ?? '', '<> ');
        $inReplyTo = trim($headers['in-reply-to'] ?? '', '<> ');

        // Body Content & MIME multipart parsing
        $contentType = $headers['content-type'] ?? 'text/plain';
        $contentTransferEncoding = strtolower($headers['content-transfer-encoding'] ?? '7bit');

        $bodyParts = $this->parseBodyParts($bodyRaw, $contentType, $contentTransferEncoding);

        return [
            'message_id' => $messageId,
            'in_reply_to' => $inReplyTo,
            'subject' => $subject,
            'from_name' => $fromInfo['name'],
            'from_email' => $fromInfo['email'],
            'to_email' => $toInfo['email'],
            'date' => $date,
            'date_raw' => $dateRaw,
            'body_text' => $bodyParts['text'],
            'body_html' => $bodyParts['html'],
        ];
    }

    /**
     * Parses MIME multipart and single part bodies.
     */
    protected function parseBodyParts(string $body, string $contentType, string $encoding): array
    {
        $text = '';
        $html = '';

        if (preg_match('/boundary="?([^";]+)"?/i', $contentType, $matches)) {
            $boundary = $matches[1];
            $parts = explode('--' . $boundary, $body);

            foreach ($parts as $part) {
                $part = trim($part);
                if (empty($part) || $part === '--') {
                    continue;
                }

                $partSplit = strpos($part, "\n\n");
                if ($partSplit === false) {
                    continue;
                }

                $partHeadersRaw = substr($part, 0, $partSplit);
                $partBodyRaw = substr($part, $partSplit + 2);

                $partContentType = 'text/plain';
                $partEncoding = '7bit';

                if (preg_match('/content-type:\s*([^;\n]+)/i', $partHeadersRaw, $ctMatch)) {
                    $partContentType = strtolower(trim($ctMatch[1]));
                }
                if (preg_match('/content-transfer-encoding:\s*([^\n]+)/i', $partHeadersRaw, $cteMatch)) {
                    $partEncoding = strtolower(trim($cteMatch[1]));
                }

                // Check nested multipart
                if (str_contains($partContentType, 'multipart/')) {
                    $nested = $this->parseBodyParts($partBodyRaw, $partHeadersRaw, $partEncoding);
                    if (! empty($nested['text'])) $text .= "\n" . $nested['text'];
                    if (! empty($nested['html'])) $html .= "\n" . $nested['html'];
                    continue;
                }

                $decodedPart = $this->decodeTransferEncoding($partBodyRaw, $partEncoding);

                if (str_contains($partContentType, 'text/html')) {
                    $html .= "\n" . $decodedPart;
                } elseif (str_contains($partContentType, 'text/plain')) {
                    $text .= "\n" . $decodedPart;
                }
            }
        } else {
            $decoded = $this->decodeTransferEncoding($body, $encoding);
            if (str_contains(strtolower($contentType), 'text/html')) {
                $html = $decoded;
            } else {
                $text = $decoded;
            }
        }

        $text = trim($text);
        $html = trim($html);

        if (empty($text) && ! empty($html)) {
            $text = $this->cleanHtmlToText($html);
        }

        return [
            'text' => $text,
            'html' => $html,
        ];
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

    protected function decodeTransferEncoding(string $data, string $encoding): string
    {
        $encoding = strtolower(trim($encoding));
        if ($encoding === 'base64') {
            $data = base64_decode(str_replace(["\r", "\n", " "], '', $data));
        } elseif ($encoding === 'quoted-printable') {
            $data = quoted_printable_decode($data);
        }

        // Convert encoding to UTF-8
        if (! mb_check_encoding($data, 'UTF-8')) {
            $data = mb_convert_encoding($data, 'UTF-8', 'ISO-8859-1, Windows-1252, ASCII');
        }

        return $data;
    }

    public function decodeMimeHeader(string $text): string
    {
        if (empty($text)) {
            return '';
        }

        if (function_exists('mb_decode_mimeheader')) {
            return mb_decode_mimeheader($text);
        }

        return iconv_mime_decode($text, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8');
    }

    public function parseEmailAddress(string $raw): array
    {
        $raw = trim($raw);
        if (empty($raw)) {
            return ['name' => '', 'email' => ''];
        }

        if (preg_match('/^(.*?)\s*<([^>]+)>$/u', $raw, $matches)) {
            $name = trim($matches[1], " \t\n\r\0\x0B\"'");
            $email = strtolower(trim($matches[2]));
            return [
                'name' => $name ?: explode('@', $email)[0],
                'email' => $email,
            ];
        }

        if (filter_var($raw, FILTER_VALIDATE_EMAIL)) {
            return [
                'name' => explode('@', $raw)[0],
                'email' => strtolower($raw),
            ];
        }

        return ['name' => $raw, 'email' => strtolower($raw)];
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

        $lead = $query->where(function ($q) use ($email) {
            $q->whereJsonContains('emails', $email)
                ->orWhereJsonContains('emails', strtolower($email))
                ->orWhere('emails', 'LIKE', '%' . $email . '%');
        })->first();

        return $lead?->id;
    }

    /**
     * Internal IMAP Socket Connection helpers
     */
    protected function connectImapSocket(string $host, int $port)
    {
        $prefix = ($port === 993) ? 'ssl://' : '';
        $target = $prefix . $host . ':' . $port;

        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);

        $socket = @stream_socket_client($target, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $context);

        if (! $socket) {
            throw new Exception("Could not connect to IMAP server [{$host}:{$port}]: {$errstr} ({$errno})");
        }

        stream_set_timeout($socket, 15);

        // Read greeting
        $greeting = fgets($socket);
        if (! $greeting || ! str_contains($greeting, '* OK')) {
            fclose($socket);
            throw new Exception('Invalid IMAP greeting response: ' . $greeting);
        }

        return $socket;
    }

    protected function loginImap($socket, string $email, string $password): void
    {
        $cleanEmail = addcslashes($email, '"\\');
        $cleanPass = addcslashes($password, '"\\');

        $response = $this->sendCommand($socket, "A001 LOGIN \"{$cleanEmail}\" \"{$cleanPass}\"");

        if (! str_contains($response, 'A001 OK')) {
            throw new Exception('IMAP Login Failed: ' . trim($response));
        }
    }

    protected function sendCommand($socket, string $command): string
    {
        fwrite($socket, $command . "\r\n");

        $response = '';
        $tag = explode(' ', $command)[0];

        while (! feof($socket)) {
            $line = fgets($socket);
            if ($line === false) {
                break;
            }
            $response .= $line;

            if (str_starts_with($line, $tag . ' OK') || str_starts_with($line, $tag . ' NO') || str_starts_with($line, $tag . ' BAD')) {
                break;
            }
        }

        return $response;
    }

    protected function parseSearchResponse(string $response): array
    {
        $ids = [];
        foreach (explode("\n", $response) as $line) {
            if (str_starts_with($line, '* SEARCH')) {
                $parts = explode(' ', trim(substr($line, 8)));
                foreach ($parts as $p) {
                    if (is_numeric($p)) {
                        $ids[] = (int) $p;
                    }
                }
            }
        }
        return $ids;
    }

    protected function fetchRawMessage($socket, string $tag, int $seqId): string
    {
        fwrite($socket, "{$tag} FETCH {$seqId} (BODY.PEEK[])\r\n");

        $raw = '';
        $inLiteral = false;
        $literalBytesRemaining = 0;

        while (! feof($socket)) {
            if ($inLiteral && $literalBytesRemaining > 0) {
                $chunk = fread($socket, min($literalBytesRemaining, 8192));
                if ($chunk === false || strlen($chunk) === 0) {
                    break;
                }
                $raw .= $chunk;
                $literalBytesRemaining -= strlen($chunk);
                if ($literalBytesRemaining <= 0) {
                    $inLiteral = false;
                }
                continue;
            }

            $line = fgets($socket);
            if ($line === false) {
                break;
            }

            // Check if literal size is announced: e.g. {1234}
            if (preg_match('/\{(\d+)\}\r?\n?$/', $line, $matches)) {
                $inLiteral = true;
                $literalBytesRemaining = (int) $matches[1];
                continue;
            }

            if (str_starts_with($line, $tag . ' OK') || str_starts_with($line, $tag . ' NO')) {
                break;
            }
        }

        return $raw;
    }

    /**
     * Test SMTP Authentication handshake directly via socket.
     */
    protected function testSmtpAuth(string $host, int $port, string $email, string $password): void
    {
        $prefix = ($port === 465) ? 'ssl://' : '';
        $target = $prefix . $host . ':' . $port;

        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);

        $socket = @stream_socket_client($target, $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $context);
        if (! $socket) {
            throw new Exception("Could not connect to SMTP server [{$host}:{$port}]: {$errstr}");
        }

        stream_set_timeout($socket, 10);
        $greeting = fgets($socket);

        if (! str_starts_with($greeting, '220')) {
            fclose($socket);
            throw new Exception("Invalid SMTP greeting: {$greeting}");
        }

        // EHLO
        fwrite($socket, "EHLO [127.0.0.1]\r\n");
        $ehloRes = '';
        while ($line = fgets($socket)) {
            $ehloRes .= $line;
            if (substr($line, 3, 1) === ' ') break;
        }

        // AUTH LOGIN
        fwrite($socket, "AUTH LOGIN\r\n");
        $authPrompt = fgets($socket);
        if (! str_starts_with($authPrompt, '334')) {
            fclose($socket);
            throw new Exception("SMTP did not accept AUTH LOGIN: {$authPrompt}");
        }

        // Send Base64 Username
        fwrite($socket, base64_encode($email) . "\r\n");
        $userPrompt = fgets($socket);
        if (! str_starts_with($userPrompt, '334')) {
            fclose($socket);
            throw new Exception("SMTP rejected username: {$userPrompt}");
        }

        // Send Base64 Password
        fwrite($socket, base64_encode($password) . "\r\n");
        $authResult = fgets($socket);

        fwrite($socket, "QUIT\r\n");
        fclose($socket);

        if (! str_starts_with($authResult, '235')) {
            throw new Exception("SMTP Authentication failed: {$authResult}");
        }
    }
}
