<?php

namespace Tests\Feature;

use App\Models\ExtractedLead;
use App\Models\ExtractionJob;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadsBulkActionTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant1;
    private Tenant $tenant2;
    private User $user1;
    private User $user2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant1 = Tenant::create([
            'name' => 'Acme Corp',
            'slug' => 'acme-corp',
        ]);

        $this->tenant2 = Tenant::create([
            'name' => 'Globex Inc',
            'slug' => 'globex-inc',
        ]);

        $this->user1 = User::create([
            'tenant_id' => $this->tenant1->id,
            'name' => 'Alice Admin',
            'email' => 'alice@acme.com',
            'password' => bcrypt('secret123'),
            'role' => 'tenant_admin',
            'is_active' => true,
        ]);

        $this->user2 = User::create([
            'tenant_id' => $this->tenant2->id,
            'name' => 'Bob Admin',
            'email' => 'bob@globex.com',
            'password' => bcrypt('secret123'),
            'role' => 'tenant_admin',
            'is_active' => true,
        ]);
    }

    private function makeJob(Tenant $tenant, User $user, string $prompt = 'Sample Query'): ExtractionJob
    {
        return ExtractionJob::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'prompt' => $prompt,
            'query' => $prompt,
            'limit' => 10,
            'status' => 'completed',
        ]);
    }

    public function test_bulk_save_updates_status_and_is_saved_for_selected_leads(): void
    {
        $job = $this->makeJob($this->tenant1, $this->user1, 'Dentists in Austin');

        $lead1 = ExtractedLead::create([
            'tenant_id' => $this->tenant1->id,
            'user_id' => $this->user1->id,
            'extraction_job_id' => $job->id,
            'business_name' => 'Dental Care One',
            'status' => 'draft',
            'is_saved' => false,
        ]);

        $lead2 = ExtractedLead::create([
            'tenant_id' => $this->tenant1->id,
            'user_id' => $this->user1->id,
            'extraction_job_id' => $job->id,
            'business_name' => 'Dental Care Two',
            'status' => 'draft',
            'is_saved' => false,
        ]);

        $response = $this->actingAs($this->user1)->postJson('/api/leads/bulk-action', [
            'lead_ids' => [$lead1->id, $lead2->id],
            'action' => 'save',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'action' => 'save',
                'affected' => 2,
            ]);

        $this->assertDatabaseHas('extracted_leads', [
            'id' => $lead1->id,
            'status' => 'saved',
            'is_saved' => true,
        ]);

        $this->assertDatabaseHas('extracted_leads', [
            'id' => $lead2->id,
            'status' => 'saved',
            'is_saved' => true,
        ]);
    }

    public function test_bulk_discard_marks_leads_as_discarded(): void
    {
        $job = $this->makeJob($this->tenant1, $this->user1, 'Plumbers in Dallas');

        $lead = ExtractedLead::create([
            'tenant_id' => $this->tenant1->id,
            'user_id' => $this->user1->id,
            'extraction_job_id' => $job->id,
            'business_name' => 'Dallas Plumbing Co',
            'status' => 'saved',
            'is_saved' => true,
        ]);

        $response = $this->actingAs($this->user1)->postJson('/api/leads/bulk-action', [
            'lead_ids' => [$lead->id],
            'action' => 'discard',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'action' => 'discard',
                'affected' => 1,
            ]);

        $this->assertDatabaseHas('extracted_leads', [
            'id' => $lead->id,
            'status' => 'discarded',
            'is_saved' => false,
        ]);
    }

    public function test_bulk_delete_removes_selected_leads(): void
    {
        $job = $this->makeJob($this->tenant1, $this->user1, 'Roofers in Miami');

        $lead = ExtractedLead::create([
            'tenant_id' => $this->tenant1->id,
            'user_id' => $this->user1->id,
            'extraction_job_id' => $job->id,
            'business_name' => 'Miami Roofing Pros',
            'status' => 'saved',
            'is_saved' => true,
        ]);

        $response = $this->actingAs($this->user1)->postJson('/api/leads/bulk-action', [
            'lead_ids' => [$lead->id],
            'action' => 'delete',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'action' => 'delete',
                'affected' => 1,
            ]);

        $this->assertDatabaseMissing('extracted_leads', [
            'id' => $lead->id,
        ]);
    }

    public function test_bulk_save_all_by_job_id_saves_all_leads_in_job(): void
    {
        $job = $this->makeJob($this->tenant1, $this->user1, 'Cafes in Seattle');

        $lead1 = ExtractedLead::create([
            'tenant_id' => $this->tenant1->id,
            'user_id' => $this->user1->id,
            'extraction_job_id' => $job->id,
            'business_name' => 'Seattle Espresso 1',
            'status' => 'draft',
            'is_saved' => false,
        ]);

        $lead2 = ExtractedLead::create([
            'tenant_id' => $this->tenant1->id,
            'user_id' => $this->user1->id,
            'extraction_job_id' => $job->id,
            'business_name' => 'Seattle Espresso 2',
            'status' => 'draft',
            'is_saved' => false,
        ]);

        $response = $this->actingAs($this->user1)->postJson('/api/leads/bulk-action', [
            'job_id' => $job->uuid,
            'action' => 'save_all',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'action' => 'save_all',
                'affected' => 2,
            ]);

        $this->assertDatabaseHas('extracted_leads', [
            'id' => $lead1->id,
            'status' => 'saved',
            'is_saved' => true,
        ]);

        $this->assertDatabaseHas('extracted_leads', [
            'id' => $lead2->id,
            'status' => 'saved',
            'is_saved' => true,
        ]);
    }

    public function test_tenant_isolation_prevents_bulk_action_on_other_tenant_leads(): void
    {
        $otherJob = $this->makeJob($this->tenant2, $this->user2, 'Lawyers in Chicago');

        $foreignLead = ExtractedLead::create([
            'tenant_id' => $this->tenant2->id,
            'user_id' => $this->user2->id,
            'extraction_job_id' => $otherJob->id,
            'business_name' => 'Chicago Legal Group',
            'status' => 'draft',
            'is_saved' => false,
        ]);

        $response = $this->actingAs($this->user1)->postJson('/api/leads/bulk-action', [
            'lead_ids' => [$foreignLead->id],
            'action' => 'save',
        ]);

        $response->assertStatus(403);

        $this->assertDatabaseHas('extracted_leads', [
            'id' => $foreignLead->id,
            'status' => 'draft',
            'is_saved' => false,
        ]);
    }

    public function test_tenant_isolation_prevents_save_all_on_other_tenant_job(): void
    {
        $otherJob = $this->makeJob($this->tenant2, $this->user2, 'Gyms in Denver');

        $response = $this->actingAs($this->user1)->postJson('/api/leads/bulk-action', [
            'job_id' => $otherJob->uuid,
            'action' => 'save_all',
        ]);

        $response->assertStatus(403);
    }

    public function test_export_selected_as_excel_returns_streamed_spreadsheet(): void
    {
        $job = $this->makeJob($this->tenant1, $this->user1, 'Hotels in Boston');

        $lead = ExtractedLead::create([
            'tenant_id' => $this->tenant1->id,
            'user_id' => $this->user1->id,
            'extraction_job_id' => $job->id,
            'business_name' => 'Boston Grand Hotel',
            'phone' => '+1-617-555-0100',
            'emails' => ['info@bostongrand.com'],
            'rating' => 4.8,
            'review_count' => 120,
        ]);

        $response = $this->actingAs($this->user1)->postJson('/api/leads/export-selected', [
            'lead_ids' => [$lead->id],
            'format' => 'excel',
        ]);

        $response->assertOk();
        $this->assertStringContainsString('application/vnd.ms-excel', (string) $response->headers->get('content-type'));

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertStringContainsString('Boston Grand Hotel', $content);
        $this->assertStringContainsString('info@bostongrand.com', $content);
        $this->assertStringContainsString('+1-617-555-0100', $content);
    }

    public function test_export_selected_as_csv_returns_streamed_csv(): void
    {
        $job = $this->makeJob($this->tenant1, $this->user1, 'Bakeries in Portland');

        $lead = ExtractedLead::create([
            'tenant_id' => $this->tenant1->id,
            'user_id' => $this->user1->id,
            'extraction_job_id' => $job->id,
            'business_name' => 'Portland Sourdough Co',
            'phone' => '+1-503-555-0199',
            'emails' => ['bread@portlandsourdough.com'],
        ]);

        $response = $this->actingAs($this->user1)->postJson('/api/leads/export-selected', [
            'lead_ids' => [$lead->id],
            'format' => 'csv',
        ]);

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertStringContainsString('Portland Sourdough Co', $content);
        $this->assertStringContainsString('bread@portlandsourdough.com', $content);
    }

    public function test_export_selected_as_json_returns_json_array(): void
    {
        $job = $this->makeJob($this->tenant1, $this->user1, 'Accountants in Phoenix');

        $lead = ExtractedLead::create([
            'tenant_id' => $this->tenant1->id,
            'user_id' => $this->user1->id,
            'extraction_job_id' => $job->id,
            'business_name' => 'Phoenix CPA & Tax LLC',
            'emails' => ['contact@phoenixcpa.com'],
        ]);

        $response = $this->actingAs($this->user1)->postJson('/api/leads/export-selected', [
            'lead_ids' => [$lead->id],
            'format' => 'json',
        ]);

        $response->assertOk()
            ->assertJsonFragment([
                'business_name' => 'Phoenix CPA & Tax LLC',
            ]);
    }

    public function test_tenant_isolation_prevents_exporting_other_tenant_leads(): void
    {
        $otherJob = $this->makeJob($this->tenant2, $this->user2, 'Lawyers in Chicago');

        $foreignLead = ExtractedLead::create([
            'tenant_id' => $this->tenant2->id,
            'user_id' => $this->user2->id,
            'extraction_job_id' => $otherJob->id,
            'business_name' => 'Foreign Tenant Lead',
        ]);

        $response = $this->actingAs($this->user1)->postJson('/api/leads/export-selected', [
            'lead_ids' => [$foreignLead->id],
            'format' => 'excel',
        ]);

        $response->assertStatus(403);
    }

    public function test_leads_can_be_filtered_by_website_presence_and_absence(): void
    {
        $job = $this->makeJob($this->tenant1, $this->user1, 'Dental Clinics');

        $leadWithSite = ExtractedLead::create([
            'tenant_id' => $this->tenant1->id,
            'user_id' => $this->user1->id,
            'extraction_job_id' => $job->id,
            'business_name' => 'Smile Dental Clinic',
            'website' => 'https://smiledental.example',
        ]);

        $leadWithoutSite = ExtractedLead::create([
            'tenant_id' => $this->tenant1->id,
            'user_id' => $this->user1->id,
            'extraction_job_id' => $job->id,
            'business_name' => 'Quick Fix Garage',
            'website' => null,
        ]);

        // Filter: Without Website (has_website=no)
        $responseNo = $this->actingAs($this->user1)->get('/leads?has_website=no');
        $responseNo->assertOk()
            ->assertSee('Quick Fix Garage')
            ->assertDontSee('Smile Dental Clinic');

        // Filter: With Website (has_website=yes)
        $responseYes = $this->actingAs($this->user1)->get('/leads?has_website=yes');
        $responseYes->assertOk()
            ->assertSee('Smile Dental Clinic')
            ->assertDontSee('Quick Fix Garage');
    }
}

