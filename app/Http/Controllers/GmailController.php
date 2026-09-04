<?php

namespace App\Http\Controllers;

use App\Models\GmailAccount;
use App\Models\GmailMessage;
use App\Services\EmailReplyService;
use App\Services\GmailService;
use App\Services\HostingerEmailService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Throwable;

class GmailController extends Controller
{
    public function __construct(
        protected GmailService $gmailService,
        protected HostingerEmailService $hostingerService,
        protected EmailReplyService $replyService
    ) {}

    public function index(Request $request): View|JsonResponse
    {
        $user = Auth::user();
        $account = GmailAccount::query()
            ->visibleTo($user)
            ->active()
            ->latest('id')
            ->first();

        $folder = $request->query('folder', 'all');
        $search = trim($request->query('search', ''));
        $leadId = $request->query('lead_id');

        $query = GmailMessage::query()
            ->visibleTo($user)
            ->with(['extractedLead', 'gmailAccount'])
            ->latest('received_at');

        if ($account) {
            $query->where('gmail_account_id', $account->id);
        }

        if ($folder === 'unread') {
            $query->where('is_read', false);
        } elseif ($folder === 'starred') {
            $query->where('is_starred', true);
        } elseif ($folder === 'matched_leads') {
            $query->whereNotNull('extracted_lead_id');
        }

        if (! empty($leadId)) {
            $query->where('extracted_lead_id', (int) $leadId);
        }

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'LIKE', "%{$search}%")
                    ->orWhere('sender_name', 'LIKE', "%{$search}%")
                    ->orWhere('sender_email', 'LIKE', "%{$search}%")
                    ->orWhere('snippet', 'LIKE', "%{$search}%")
                    ->orWhere('body_text', 'LIKE', "%{$search}%");
            });
        }

        $messages = $query->paginate(25)->withQueryString();

        // Stats counts
        $unreadCount = 0;
        $starredCount = 0;
        $matchedLeadsCount = 0;
        $totalCount = 0;

        if ($account) {
            $baseStatsQuery = GmailMessage::query()->visibleTo($user)->where('gmail_account_id', $account->id);
            $totalCount = (clone $baseStatsQuery)->count();
            $unreadCount = (clone $baseStatsQuery)->where('is_read', false)->count();
            $starredCount = (clone $baseStatsQuery)->where('is_starred', true)->count();
            $matchedLeadsCount = (clone $baseStatsQuery)->whereNotNull('extracted_lead_id')->count();
        }

        if ($request->wantsJson()) {
            return response()->json([
                'messages' => $messages,
                'unread_count' => $unreadCount,
            ]);
        }

        return view('gmail.index', [
            'account' => $account,
            'messages' => $messages,
            'folder' => $folder,
            'search' => $search,
            'leadId' => $leadId,
            'unreadCount' => $unreadCount,
            'starredCount' => $starredCount,
            'matchedLeadsCount' => $matchedLeadsCount,
            'totalCount' => $totalCount,
            'isConfigured' => $this->gmailService->isConfigured(),
        ]);
    }

    public function connectHostinger(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string'],
            'name' => ['nullable', 'string', 'max:255'],
            'imap_host' => ['nullable', 'string', 'max:255'],
            'imap_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'smtp_host' => ['nullable', 'string', 'max:255'],
            'smtp_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
        ]);

        $user = Auth::user();
        $email = strtolower(trim($validated['email']));
        $password = $validated['password'];
        $name = trim($validated['name'] ?? '') ?: explode('@', $email)[0];
        $imapHost = trim($validated['imap_host'] ?? '') ?: 'imap.hostinger.com';
        $imapPort = (int) ($validated['imap_port'] ?? 993);
        $smtpHost = trim($validated['smtp_host'] ?? '') ?: 'smtp.hostinger.com';
        $smtpPort = (int) ($validated['smtp_port'] ?? 465);

        // Optional test connection
        $test = $this->hostingerService->testConnection($email, $password, $imapHost, $imapPort, $smtpHost, $smtpPort);

        if (! $test['success']) {
            return back()->withInput()->with('error', $test['error']);
        }

        // Find or create account
        $account = GmailAccount::where('tenant_id', $user->tenant_id)
            ->where('email', $email)
            ->first();

        if (! $account) {
            $account = new GmailAccount();
            $account->tenant_id = $user->tenant_id;
            $account->user_id = $user->id;
            $account->email = $email;
        }

        $account->provider = 'hostinger';
        $account->name = $name;
        $account->password = $password;
        $account->imap_host = $imapHost;
        $account->imap_port = $imapPort;
        $account->imap_encryption = 'ssl';
        $account->smtp_host = $smtpHost;
        $account->smtp_port = $smtpPort;
        $account->smtp_encryption = 'ssl';
        $account->is_active = true;
        $account->sync_status = 'idle';
        $account->error_message = null;
        $account->save();

        // Perform initial sync
        $this->hostingerService->syncMessages($account, 25);

        return redirect()->route('gmail.index')->with('success', "Hostinger email [{$account->email}] successfully connected & synced!");
    }

    public function connect(Request $request): RedirectResponse
    {
        if (! $this->gmailService->isConfigured()) {
            return redirect()->route('settings.index', ['tab' => 'gmail'])->with(
                'error',
                'Google OAuth credentials are not configured yet. Please provide GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET in your .env configuration, or connect with Hostinger Email above.'
            );
        }

        $authUrl = $this->gmailService->getAuthUrl();

        return redirect()->away($authUrl);
    }

    public function callback(Request $request): RedirectResponse
    {
        $code = $request->query('code');
        $error = $request->query('error');

        if ($error) {
            return redirect()->route('gmail.index')->with('error', 'Google authorization was denied or canceled: ' . $error);
        }

        if (empty($code)) {
            return redirect()->route('gmail.index')->with('error', 'Invalid authorization callback received from Google.');
        }

        try {
            $user = Auth::user();
            $account = $this->gmailService->handleCallback($code, $user);
            $account->update(['provider' => 'gmail']);

            // Attempt immediate initial sync
            $this->gmailService->syncMessages($account, 25);

            return redirect()->route('gmail.index')->with('success', "Gmail account [{$account->email}] successfully connected! Initial emails synced.");
        } catch (Throwable $e) {
            return redirect()->route('gmail.index')->with('error', 'Failed to connect Gmail: ' . $e->getMessage());
        }
    }

    public function disconnect(Request $request, GmailAccount $account): RedirectResponse
    {
        $user = Auth::user();

        if ($account->tenant_id !== $user->tenant_id && ! $user->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $account->update(['is_active' => false]);
        $account->delete();

        return redirect()->route('gmail.index')->with('success', 'Email account has been disconnected.');
    }

    public function sync(Request $request, ?GmailAccount $account = null): JsonResponse|RedirectResponse
    {
        $user = Auth::user();

        if (! $account) {
            $account = GmailAccount::query()
                ->visibleTo($user)
                ->active()
                ->latest('id')
                ->first();
        }

        if (! $account) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'No active email account connected.'], 404);
            }
            return back()->with('error', 'No active email account connected.');
        }

        if ($account->tenant_id !== $user->tenant_id && ! $user->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        if ($account->isHostinger() || $account->isImap()) {
            $result = $this->hostingerService->syncMessages($account, 35);
        } else {
            $result = $this->gmailService->syncMessages($account, 35);
        }

        if ($request->wantsJson()) {
            return response()->json($result);
        }

        if ($result['success']) {
            return back()->with('success', "Sync completed! Checked {$result['synced_count']} email(s), {$result['new_count']} new.");
        }

        return back()->with('error', 'Email sync encountered an issue: ' . ($result['error'] ?? 'Unknown error'));
    }

    public function show(Request $request, GmailMessage $message): JsonResponse
    {
        $user = Auth::user();

        if ($message->tenant_id !== $user->tenant_id && ! $user->isSuperAdmin()) {
            abort(403, 'Unauthorized.');
        }

        // Mark as read automatically when viewed
        if (! $message->is_read) {
            $message->update(['is_read' => true]);
            if ($message->gmailAccount && $message->gmailAccount->isGmail()) {
                $this->gmailService->markAsRead($message);
            }
        }

        $message->load(['extractedLead', 'gmailAccount']);

        return response()->json([
            'success' => true,
            'message' => [
                'id' => $message->id,
                'gmail_message_id' => $message->gmail_message_id,
                'gmail_thread_id' => $message->gmail_thread_id,
                'sender_name' => $message->sender_name ?: $message->sender_email,
                'sender_email' => $message->sender_email,
                'recipient_email' => $message->recipient_email,
                'subject' => $message->subject ?: '(No Subject)',
                'snippet' => $message->snippet,
                'body_text' => $message->body_text,
                'body_html' => $message->body_html,
                'received_at' => $message->received_at ? $message->received_at->format('M d, Y h:i A') : '',
                'received_at_diff' => $message->received_at ? $message->received_at->diffForHumans() : '',
                'is_read' => $message->is_read,
                'is_starred' => $message->is_starred,
                'has_attachments' => $message->has_attachments,
                'extracted_lead' => $message->extractedLead ? [
                    'id' => $message->extractedLead->id,
                    'uuid' => $message->extractedLead->uuid,
                    'business_name' => $message->extractedLead->business_name,
                    'category' => $message->extractedLead->category,
                    'phone' => $message->extractedLead->phone,
                    'website' => $message->extractedLead->website,
                    'city' => $message->extractedLead->city,
                    'rating' => $message->extractedLead->rating,
                    'review_count' => $message->extractedLead->review_count,
                    'preview_url' => $message->extractedLead->uuid ? route('leads.preview', $message->extractedLead->uuid) : null,
                ] : null,
            ],
        ]);
    }

    public function sendReply(Request $request, GmailMessage $message): JsonResponse
    {
        $user = Auth::user();

        if ($message->tenant_id !== $user->tenant_id && ! $user->isSuperAdmin()) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'min:2'],
            'subject' => ['nullable', 'string', 'max:500'],
        ]);

        $account = $message->gmailAccount;
        if (! $account || ! $account->is_active) {
            $account = GmailAccount::query()
                ->visibleTo($user)
                ->active()
                ->latest('id')
                ->first();
        }

        if (! $account) {
            return response()->json([
                'success' => false,
                'message' => 'No active connected email account found to send the reply.',
            ], 422);
        }

        try {
            $result = $this->replyService->sendReply(
                $account,
                $message,
                $validated['body'],
                $validated['subject'] ?? null,
                $user
            );

            return response()->json($result);
        } catch (Throwable $e) {
            Log::error('Email reply dispatch failed', [
                'message_id' => $message->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send reply: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function toggleStar(Request $request, GmailMessage $message): JsonResponse
    {
        $user = Auth::user();

        if ($message->tenant_id !== $user->tenant_id && ! $user->isSuperAdmin()) {
            abort(403, 'Unauthorized.');
        }

        $message->is_starred = ! $message->is_starred;
        $message->save();

        if ($message->gmailAccount && $message->gmailAccount->isGmail()) {
            $this->gmailService->toggleStar($message);
        }

        return response()->json([
            'success' => true,
            'is_starred' => $message->is_starred,
        ]);
    }

    public function markRead(Request $request, GmailMessage $message): JsonResponse
    {
        $user = Auth::user();

        if ($message->tenant_id !== $user->tenant_id && ! $user->isSuperAdmin()) {
            abort(403, 'Unauthorized.');
        }

        $message->update(['is_read' => true]);

        if ($message->gmailAccount && $message->gmailAccount->isGmail()) {
            $this->gmailService->markAsRead($message);
        }

        return response()->json([
            'success' => true,
            'is_read' => true,
        ]);
    }

    public function destroy(Request $request, GmailMessage $message): JsonResponse|RedirectResponse
    {
        $user = Auth::user();

        if ($message->tenant_id !== $user->tenant_id && ! $user->isSuperAdmin()) {
            abort(403, 'Unauthorized.');
        }

        $message->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Email removed.']);
        }

        return back()->with('success', 'Email removed from inbox view.');
    }
}
