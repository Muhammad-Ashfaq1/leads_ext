<?php

namespace Tests\Feature;

use App\Models\EmailTemplate;
use App\Models\ExtractedLead;
use App\Models\ExtractionJob;
use App\Models\LeadEmailLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class EmailOutreachTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $user;
    private ExtractionJob $job;
    private Tenant $otherTenant;
    private User $otherUser;
    private ExtractionJob $otherJob;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Acme Corp',
            'slug' => 'acme-corp',
            'plan' => 'growth',
            'lead_quota' => 1000,
            'is_active' => true,
        ]);

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role' => 'admin',
            'email' => 'admin@acme.com',
            'name' => 'Acme Admin',
            'is_active' => true,
        ]);

        $this->job = ExtractionJob::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'uuid' => (string) Str::uuid(),
            'prompt' => 'Dentists in NY',
            'query' => 'Dentists in NY',
            'status' => ExtractionJob::STATUS_COMPLETED,
            'limit' => 50,
            'mode' => 'live',
        ]);

        $this->otherTenant = Tenant::create([
            'name' => 'Beta Industries',
            'slug' => 'beta-industries',
            'plan' => 'starter',
            'is_active' => true,
        ]);

        $this->otherUser = User::factory()->create([
            'tenant_id' => $this->otherTenant->id,
            'role' => 'admin',
            'email' => 'admin@beta.com',
            'is_active' => true,
        ]);

        $this->otherJob = ExtractionJob::create([
            'tenant_id' => $this->otherTenant->id,
            'user_id' => $this->otherUser->id,
            'uuid' => (string) Str::uuid(),
            'prompt' => 'Lawyers in Chicago',
            'query' => 'Lawyers in Chicago',
            'status' => ExtractionJob::STATUS_COMPLETED,
            'limit' => 50,
            'mode' => 'live',
        ]);
    }

    public function test_authenticated_user_can_view_email_templates_page(): void
    {
        $response = $this->actingAs($this->user)->get(route('email-templates.index'));

        $response->assertStatus(200);
        $response->assertSee('Email Templates &amp; Outreach', false);
        $response->assertSee('Template Library');
        $response->assertSee('Template Builder');
    }

    public function test_can_create_new_email_template(): void
    {
        $response = $this->actingAs($this->user)->post(route('email-templates.store'), [
            'name' => 'Q3 Special Proposal',
            'category' => 'Proposal',
            'subject' => 'Exclusive offer for {{business_name}}',
            'body' => '<p>Hello {{business_name}}, we want to work with you.</p>',
            'is_default' => 1,
        ]);

        $response->assertRedirect(route('email-templates.index'));

        $this->assertDatabaseHas('email_templates', [
            'tenant_id' => $this->tenant->id,
            'name' => 'Q3 Special Proposal',
            'is_default' => 1,
        ]);
    }

    public function test_can_update_and_delete_email_template(): void
    {
        $template = EmailTemplate::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'name' => 'Old Template',
            'subject' => 'Old Subject',
            'body' => '<p>Old body</p>',
        ]);

        $updateResp = $this->actingAs($this->user)->put(route('email-templates.update', $template), [
            'name' => 'Updated Template Name',
            'category' => 'Outreach',
            'subject' => 'Updated Subject',
            'body' => '<p>Updated body content</p>',
        ]);

        $updateResp->assertRedirect(route('email-templates.index'));
        $this->assertDatabaseHas('email_templates', [
            'id' => $template->id,
            'name' => 'Updated Template Name',
        ]);

        $deleteResp = $this->actingAs($this->user)->delete(route('email-templates.destroy', $template));
        $deleteResp->assertRedirect(route('email-templates.index'));
        $this->assertDatabaseMissing('email_templates', [
            'id' => $template->id,
        ]);
    }

    public function test_can_set_default_email_template(): void
    {
        $template1 = EmailTemplate::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'name' => 'Template 1',
            'subject' => 'Sub 1',
            'body' => '<p>Body 1</p>',
            'is_default' => true,
        ]);

        $template2 = EmailTemplate::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'name' => 'Template 2',
            'subject' => 'Sub 2',
            'body' => '<p>Body 2</p>',
            'is_default' => false,
        ]);

        $resp = $this->actingAs($this->user)->post(route('email-templates.default', $template2));
        $resp->assertRedirect(route('email-templates.index'));

        $this->assertFalse((bool) $template1->fresh()->is_default);
        $this->assertTrue((bool) $template2->fresh()->is_default);
    }

    public function test_tenant_isolation_on_email_templates(): void
    {
        $otherTemplate = EmailTemplate::create([
            'tenant_id' => $this->otherTenant->id,
            'user_id' => $this->otherUser->id,
            'name' => 'Secret Template',
            'subject' => 'Secret',
            'body' => '<p>Secret</p>',
        ]);

        // Attempting to delete another tenant's template should return 403
        $resp = $this->actingAs($this->user)->delete(route('email-templates.destroy', $otherTemplate));
        $resp->assertStatus(403);

        $this->assertDatabaseHas('email_templates', ['id' => $otherTemplate->id]);
    }

    public function test_api_can_list_email_templates_json(): void
    {
        EmailTemplate::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'name' => 'API Template',
            'subject' => 'Inquiry',
            'body' => '<p>Content</p>',
        ]);

        $response = $this->actingAs($this->user)->getJson(route('email-templates.list'));

        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'API Template']);
    }

    public function test_can_send_single_email_to_lead_with_placeholders(): void
    {
        Mail::fake();

        $lead = ExtractedLead::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'extraction_job_id' => $this->job->id,
            'business_name' => 'Apex Dental Care',
            'city' => 'Beverly Hills',
            'emails' => ['contact@apexdental.com'],
            'phone' => '+1555123456',
            'category' => 'Dentistry',
        ]);

        $response = $this->actingAs($this->user)->postJson(route('leads.send-email'), [
            'lead_id' => $lead->id,
            'subject' => 'Hello {{business_name}} in {{city}}',
            'body' => '<p>Hi {{business_name}}, we noticed your {{category}} clinic.</p>',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'sent_count' => 1,
            'failed_count' => 0,
        ]);

        $this->assertDatabaseHas('lead_email_logs', [
            'tenant_id' => $this->tenant->id,
            'extracted_lead_id' => $lead->id,
            'recipient_email' => 'contact@apexdental.com',
            'subject' => 'Hello Apex Dental Care in Beverly Hills',
            'status' => 'sent',
        ]);
    }

    public function test_can_bulk_send_emails_to_selected_leads(): void
    {
        Mail::fake();

        $lead1 = ExtractedLead::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'extraction_job_id' => $this->job->id,
            'business_name' => 'Lead One',
            'emails' => ['lead1@example.com'],
            'city' => 'New York',
        ]);

        $lead2 = ExtractedLead::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'extraction_job_id' => $this->job->id,
            'business_name' => 'Lead Two',
            'emails' => ['lead2@example.com'],
            'city' => 'Chicago',
        ]);

        $response = $this->actingAs($this->user)->postJson(route('leads.send-email'), [
            'lead_ids' => [$lead1->id, $lead2->id],
            'subject' => 'Special notice for {{business_name}}',
            'body' => '<p>Greetings from our team to {{business_name}}.</p>',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'sent_count' => 2,
            'failed_count' => 0,
            'skipped_count' => 0,
        ]);

        $this->assertDatabaseCount('lead_email_logs', 2);
    }

    public function test_leads_without_email_are_skipped_gracefully(): void
    {
        Mail::fake();

        $leadWithEmail = ExtractedLead::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'extraction_job_id' => $this->job->id,
            'business_name' => 'With Email',
            'emails' => ['valid@example.com'],
        ]);

        $leadWithoutEmail = ExtractedLead::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'extraction_job_id' => $this->job->id,
            'business_name' => 'Without Email',
            'emails' => [],
        ]);

        $response = $this->actingAs($this->user)->postJson(route('leads.send-email'), [
            'lead_ids' => [$leadWithEmail->id, $leadWithoutEmail->id],
            'subject' => 'Notice for {{business_name}}',
            'body' => '<p>Content</p>',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'sent_count' => 1,
            'skipped_count' => 1,
        ]);
    }

    public function test_tenant_isolation_prevents_sending_email_to_other_tenant_leads(): void
    {
        $otherLead = ExtractedLead::create([
            'tenant_id' => $this->otherTenant->id,
            'user_id' => $this->otherUser->id,
            'extraction_job_id' => $this->otherJob->id,
            'business_name' => 'Other Lead',
            'emails' => ['other@example.com'],
        ]);

        $response = $this->actingAs($this->user)->postJson(route('leads.send-email'), [
            'lead_id' => $otherLead->id,
            'subject' => 'Unauthorized Email',
            'body' => '<p>Forbidden</p>',
        ]);

        $response->assertStatus(403);
    }
}
