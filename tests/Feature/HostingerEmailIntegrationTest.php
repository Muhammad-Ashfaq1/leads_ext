<?php

namespace Tests\Feature;

use App\Models\ExtractedLead;
use App\Models\ExtractionJob;
use App\Models\GmailAccount;
use App\Models\GmailMessage;
use App\Models\Tenant;
use App\Models\User;
use App\Services\EmailReplyService;
use App\Services\HostingerEmailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class HostingerEmailIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $user;
    protected ExtractionJob $job;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Hostinger Outreach HQ',
            'slug' => 'hostinger-outreach-hq',
            'plan' => 'enterprise',
            'lead_quota' => 5000,
            'is_active' => true,
        ]);

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role' => 'admin',
            'email' => 'admin@obtainsolutions.test',
            'is_active' => true,
        ]);

        $this->job = ExtractionJob::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'uuid' => (string) Str::uuid(),
            'prompt' => 'Roofing contractors',
            'query' => 'Roofing contractors',
            'status' => ExtractionJob::STATUS_COMPLETED,
            'limit' => 50,
            'mode' => 'live',
        ]);
    }

    public function test_user_can_connect_hostinger_email_account(): void
    {
        $mockHostinger = Mockery::mock(HostingerEmailService::class);
        $mockHostinger->shouldReceive('testConnection')
            ->once()
            ->andReturn(['success' => true, 'message' => 'Connected']);

        $mockHostinger->shouldReceive('syncMessages')
            ->once()
            ->andReturn(['success' => true, 'synced_count' => 5, 'new_count' => 5]);

        $this->app->instance(HostingerEmailService::class, $mockHostinger);

        $response = $this->actingAs($this->user)->post(route('gmail.connect-hostinger'), [
            'email' => 'support@obtainsolutions.com',
            'password' => 'SuperSecretPass123!',
            'name' => 'Support Desk',
            'imap_host' => 'imap.hostinger.com',
            'imap_port' => 993,
            'smtp_host' => 'smtp.hostinger.com',
            'smtp_port' => 465,
        ]);

        $response->assertRedirect(route('gmail.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('gmail_accounts', [
            'tenant_id' => $this->tenant->id,
            'email' => 'support@obtainsolutions.com',
            'provider' => 'hostinger',
            'name' => 'Support Desk',
            'imap_host' => 'imap.hostinger.com',
            'smtp_host' => 'smtp.hostinger.com',
            'is_active' => true,
        ]);
    }

    public function test_hostinger_service_parses_rfc822_raw_email_correctly(): void
    {
        $lead = ExtractedLead::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'extraction_job_id' => $this->job->id,
            'business_name' => 'Metro Plumbing Pros',
            'category' => 'Plumber',
            'phone' => '+1 555-9000',
            'emails' => ['contact@metroplumbing.test'],
            'city' => 'Houston',
        ]);

        $service = app(HostingerEmailService::class);

        $rawRfc822 = "From: Metro Plumbing <contact@metroplumbing.test>\r\n" .
                     "To: support@obtainsolutions.com\r\n" .
                     "Subject: =?UTF-8?B?UmU6IFdlYnNpdGUgT3V0cmVhY2ggRGVtbw==?=\r\n" . // "Re: Website Outreach Demo"
                     "Date: Fri, 04 Sep 2026 14:30:00 +0000\r\n" .
                     "Message-ID: <metro-12345@metroplumbing.test>\r\n" .
                     "Content-Type: multipart/alternative; boundary=\"BOUNDARY123\"\r\n\r\n" .
                     "--BOUNDARY123\r\n" .
                     "Content-Type: text/plain; charset=UTF-8\r\n" .
                     "Content-Transfer-Encoding: 7bit\r\n\r\n" .
                     "Hello! We saw the demo website and loved it. When can we talk?\r\n" .
                     "--BOUNDARY123\r\n" .
                     "Content-Type: text/html; charset=UTF-8\r\n" .
                     "Content-Transfer-Encoding: 7bit\r\n\r\n" .
                     "<p>Hello! We saw the <strong>demo website</strong> and loved it. When can we talk?</p>\r\n" .
                     "--BOUNDARY123--\r\n";

        $account = GmailAccount::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'email' => 'support@obtainsolutions.com',
            'provider' => 'hostinger',
            'is_active' => true,
        ]);

        $isNew = $service->processAndStoreRawEmail($account, $rawRfc822, '1');

        $this->assertTrue($isNew);
        $this->assertDatabaseHas('gmail_messages', [
            'tenant_id' => $this->tenant->id,
            'gmail_account_id' => $account->id,
            'sender_email' => 'contact@metroplumbing.test',
            'sender_name' => 'Metro Plumbing',
            'subject' => 'Re: Website Outreach Demo',
            'extracted_lead_id' => $lead->id,
            'is_read' => false,
        ]);

        $msg = GmailMessage::where('sender_email', 'contact@metroplumbing.test')->first();
        $this->assertStringContainsString('When can we talk?', $msg->body_text);
        $this->assertStringContainsString('<strong>demo website</strong>', $msg->body_html);
    }

    public function test_user_can_send_reply_via_smtp(): void
    {
        $lead = ExtractedLead::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'extraction_job_id' => $this->job->id,
            'business_name' => 'Apex Legal Services',
            'category' => 'Lawyer',
            'phone' => '+1 555-4321',
            'emails' => ['partner@apexlegal.test'],
            'city' => 'Dallas',
        ]);

        $account = GmailAccount::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'email' => 'hello@obtainsolutions.com',
            'provider' => 'hostinger',
            'password' => 'SecretPassword',
            'smtp_host' => 'smtp.hostinger.com',
            'smtp_port' => 465,
            'is_active' => true,
        ]);

        $message = GmailMessage::create([
            'tenant_id' => $this->tenant->id,
            'gmail_account_id' => $account->id,
            'extracted_lead_id' => $lead->id,
            'gmail_message_id' => 'apex_msg_999',
            'sender_name' => 'Sarah Apex',
            'sender_email' => 'partner@apexlegal.test',
            'subject' => 'Pricing Inquiry',
            'snippet' => 'What is the pricing for your software?',
            'body_text' => 'What is the pricing for your software?',
            'received_at' => now(),
        ]);

        // Mock EmailReplyService to avoid real SMTP socket connection in unit tests
        $mockReplyService = Mockery::mock(EmailReplyService::class);
        $mockReplyService->shouldReceive('sendReply')
            ->once()
            ->andReturn([
                'success' => true,
                'message' => 'Reply successfully sent to partner@apexlegal.test!',
                'recipient' => 'partner@apexlegal.test',
                'subject' => 'Re: Pricing Inquiry',
            ]);

        $this->app->instance(EmailReplyService::class, $mockReplyService);

        $response = $this->actingAs($this->user)->postJson(route('gmail.messages.reply', $message->id), [
            'body' => "Hi Sarah,\nThanks for reaching out! Our pricing is $49/month.\nBest regards,\nSupport",
            'subject' => 'Re: Pricing Inquiry',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('message', 'Reply successfully sent to partner@apexlegal.test!');
    }
}
