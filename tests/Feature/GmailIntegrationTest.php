<?php

namespace Tests\Feature;

use App\Models\ExtractedLead;
use App\Models\ExtractionJob;
use App\Models\GmailAccount;
use App\Models\GmailMessage;
use App\Models\Tenant;
use App\Models\User;
use App\Services\GmailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class GmailIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $user;
    protected ExtractionJob $job;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Acme Outreach HQ',
            'slug' => 'acme-outreach-hq',
            'plan' => 'pro',
            'lead_quota' => 1000,
            'is_active' => true,
        ]);

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role' => 'admin',
            'email' => 'admin@acme.test',
            'is_active' => true,
        ]);

        $this->job = ExtractionJob::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'uuid' => (string) Str::uuid(),
            'prompt' => 'Dentists in Austin',
            'query' => 'Dentists in Austin',
            'status' => ExtractionJob::STATUS_COMPLETED,
            'limit' => 50,
            'mode' => 'live',
        ]);
    }

    public function test_guest_cannot_access_gmail_inbox(): void
    {
        $response = $this->get(route('gmail.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_gmail_inbox_empty_state(): void
    {
        $response = $this->actingAs($this->user)->get(route('gmail.index'));

        $response->assertStatus(200);
        $response->assertSee('Email Inbox &amp; Outreach Hub', false);
        $response->assertSee('No Email Account Connected');
    }

    public function test_user_can_view_synced_messages_and_filters(): void
    {
        $account = GmailAccount::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'email' => 'sales@acme.test',
            'access_token' => 'mock_token',
            'refresh_token' => 'mock_refresh',
            'is_active' => true,
        ]);

        $message = GmailMessage::create([
            'tenant_id' => $this->tenant->id,
            'gmail_account_id' => $account->id,
            'gmail_message_id' => 'msg_123',
            'sender_name' => 'Dr. Robert Smith',
            'sender_email' => 'robert@dentalsmiles.test',
            'recipient_email' => 'sales@acme.test',
            'subject' => 'Inquiry regarding your outreach',
            'snippet' => 'We received your email and would like to schedule a call.',
            'body_text' => "Hi team,\nWe received your email and would like to schedule a call next Tuesday.\nThanks,\nRobert",
            'body_html' => '<p>Hi team,<br>We received your email and would like to schedule a call next Tuesday.</p>',
            'received_at' => now()->subMinutes(10),
            'is_read' => false,
            'is_starred' => false,
        ]);

        $response = $this->actingAs($this->user)->get(route('gmail.index'));

        $response->assertStatus(200);
        $response->assertSee('sales@acme.test');
        $response->assertSee('Dr. Robert Smith');
        $response->assertSee('Inquiry regarding your outreach');
    }

    public function test_user_can_fetch_message_detail_via_json_and_auto_marks_as_read(): void
    {
        $lead = ExtractedLead::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'extraction_job_id' => $this->job->id,
            'business_name' => 'Dental Smiles Clinic',
            'category' => 'Dentist',
            'phone' => '+1 555-0192',
            'emails' => ['robert@dentalsmiles.test'],
            'city' => 'Austin',
            'rating' => 4.9,
            'review_count' => 120,
        ]);

        $account = GmailAccount::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'email' => 'sales@acme.test',
            'access_token' => 'mock_token',
            'is_active' => true,
        ]);

        $message = GmailMessage::create([
            'tenant_id' => $this->tenant->id,
            'gmail_account_id' => $account->id,
            'extracted_lead_id' => $lead->id,
            'gmail_message_id' => 'msg_456',
            'sender_name' => 'Robert',
            'sender_email' => 'robert@dentalsmiles.test',
            'recipient_email' => 'sales@acme.test',
            'subject' => 'Re: Demo Request',
            'snippet' => 'Looks great, please send details.',
            'body_text' => 'Looks great, please send details.',
            'body_html' => '<p>Looks great, please send details.</p>',
            'received_at' => now(),
            'is_read' => false,
        ]);

        $this->assertFalse($message->is_read);

        $response = $this->actingAs($this->user)->getJson(route('gmail.messages.show', $message->id));

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('message.subject', 'Re: Demo Request');
        $response->assertJsonPath('message.sender_email', 'robert@dentalsmiles.test');
        $response->assertJsonPath('message.extracted_lead.business_name', 'Dental Smiles Clinic');

        $message->refresh();
        $this->assertTrue($message->is_read);
    }

    public function test_user_can_toggle_star_and_delete_message(): void
    {
        $account = GmailAccount::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'email' => 'sales@acme.test',
            'access_token' => 'mock_token',
            'is_active' => true,
        ]);

        $message = GmailMessage::create([
            'tenant_id' => $this->tenant->id,
            'gmail_account_id' => $account->id,
            'gmail_message_id' => 'msg_789',
            'sender_name' => 'Alice',
            'sender_email' => 'alice@partner.test',
            'subject' => 'Partnership',
            'is_starred' => false,
        ]);

        // Toggle Star
        $starRes = $this->actingAs($this->user)->postJson(route('gmail.messages.star', $message->id));
        $starRes->assertStatus(200);
        $starRes->assertJsonPath('is_starred', true);
        $this->assertTrue($message->fresh()->is_starred);

        // Delete message
        $delRes = $this->actingAs($this->user)->deleteJson(route('gmail.messages.destroy', $message->id));
        $delRes->assertStatus(200);
        $this->assertDatabaseMissing('gmail_messages', ['id' => $message->id]);
    }

    public function test_gmail_service_processes_and_links_message_to_matching_extracted_lead(): void
    {
        $lead = ExtractedLead::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'extraction_job_id' => $this->job->id,
            'business_name' => 'Austin Roofing Experts',
            'category' => 'Roofing Contractor',
            'phone' => '+1 555-8833',
            'emails' => ['info@austinroofing.test'],
            'city' => 'Austin',
        ]);

        $account = GmailAccount::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'email' => 'outreach@acme.test',
            'access_token' => 'mock_token',
            'is_active' => true,
        ]);

        $service = app(GmailService::class);

        $mockPayload = [
            'id' => 'gmail_raw_123',
            'threadId' => 'thread_456',
            'snippet' => 'We are interested in getting a quote.',
            'labelIds' => ['INBOX', 'UNREAD'],
            'internalDate' => (string) (now()->timestamp * 1000),
            'payload' => [
                'mimeType' => 'text/plain',
                'headers' => [
                    ['name' => 'From', 'value' => 'Austin Roofing <info@austinroofing.test>'],
                    ['name' => 'To', 'value' => 'outreach@acme.test'],
                    ['name' => 'Subject', 'value' => 'Quote Request from Austin Roofing'],
                ],
                'body' => [
                    'data' => strtr(base64_encode('Hello! We are interested in getting a quote for lead services.'), '+/', '-_'),
                ],
            ],
        ];

        $isNew = $service->processAndStoreMessage($account, $mockPayload);

        $this->assertTrue($isNew);
        $this->assertDatabaseHas('gmail_messages', [
            'tenant_id' => $this->tenant->id,
            'gmail_account_id' => $account->id,
            'gmail_message_id' => 'gmail_raw_123',
            'sender_email' => 'info@austinroofing.test',
            'sender_name' => 'Austin Roofing',
            'extracted_lead_id' => $lead->id,
            'is_read' => false,
        ]);

        $created = GmailMessage::where('gmail_message_id', 'gmail_raw_123')->first();
        $this->assertEquals('Hello! We are interested in getting a quote for lead services.', $created->body_text);
    }

    public function test_gmail_oauth_callback_flow(): void
    {
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'mock_access_token_123',
                'refresh_token' => 'mock_refresh_token_456',
                'expires_in' => 3600,
            ], 200),
            'https://www.googleapis.com/oauth2/v2/userinfo' => Http::response([
                'id' => 'google_user_789',
                'email' => 'founder@acme.test',
                'name' => 'Founder User',
                'picture' => 'https://lh3.googleusercontent.com/avatar',
            ], 200),
            'https://gmail.googleapis.com/gmail/v1/users/me/messages*' => Http::response([
                'messages' => [],
            ], 200),
        ]);

        config([
            'services.google.client_id' => 'test_client_id',
            'services.google.client_secret' => 'test_client_secret',
        ]);

        $response = $this->actingAs($this->user)->get(route('gmail.callback', ['code' => 'auth_code_xyz']));

        $response->assertRedirect(route('gmail.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('gmail_accounts', [
            'tenant_id' => $this->tenant->id,
            'email' => 'founder@acme.test',
            'google_id' => 'google_user_789',
            'name' => 'Founder User',
            'is_active' => true,
        ]);
    }
}
