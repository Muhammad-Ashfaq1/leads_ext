<?php

namespace Tests\Feature;

use App\Jobs\GenerateLeadsExportJob;
use App\Models\ExtractedLead;
use App\Models\ExtractionJob;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class GenerateLeadsExportJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_leads_export_job_creates_export_file_and_maintains_tenant_isolation(): void
    {
        $tenant1 = Tenant::create([
            'name' => 'Acme Corp',
            'slug' => 'acme-corp',
            'lead_quota' => 1000,
        ]);

        $tenant2 = Tenant::create([
            'name' => 'Beta Industries',
            'slug' => 'beta-ind',
            'lead_quota' => 1000,
        ]);

        $job1 = ExtractionJob::create([
            'tenant_id' => $tenant1->id,
            'uuid' => '11111111-2222-3333-4444-555555555555',
            'prompt' => 'Dentists in Dallas',
            'query' => 'Dentists in Dallas',
            'status' => 'completed',
            'limit' => 10,
        ]);

        $job2 = ExtractionJob::create([
            'tenant_id' => $tenant2->id,
            'uuid' => '99999999-8888-7777-6666-555555555555',
            'prompt' => 'Lawyers in Dallas',
            'query' => 'Lawyers in Dallas',
            'status' => 'completed',
            'limit' => 10,
        ]);

        $lead1 = ExtractedLead::create([
            'tenant_id' => $tenant1->id,
            'extraction_job_id' => $job1->id,
            'business_name' => 'Acme Dental Care',
            'address' => '123 Main St, Dallas, TX',
            'phone' => '(214) 555-0101',
            'emails' => ['info@acmedental.example'],
            'social_links' => [
                'linkedin' => 'https://www.linkedin.com/company/acme-dental',
                'facebook' => 'https://www.facebook.com/acmedental',
            ],
            'email_verification_status' => [
                'info@acmedental.example' => [
                    'email' => 'info@acmedental.example',
                    'is_valid' => true,
                    'is_rfc_valid' => true,
                    'is_disposable' => false,
                    'has_mx' => true,
                ],
            ],
            'website' => 'https://acmedental.example',
            'category' => 'Dentist',
            'rating' => 4.9,
            'review_count' => 150,
            'source' => 'Google Places API',
        ]);

        $lead2 = ExtractedLead::create([
            'tenant_id' => $tenant2->id,
            'extraction_job_id' => $job2->id,
            'business_name' => 'Beta Legal Firm',
            'address' => '456 Oak St, Dallas, TX',
            'phone' => '(214) 555-0202',
            'emails' => ['contact@betalegal.example'],
            'source' => 'Google Places API',
        ]);

        $exportJob = new GenerateLeadsExportJob(
            tenantId: $tenant1->id,
            format: 'csv'
        );

        $filePath = $exportJob->handle();

        $this->assertFileExists($filePath);
        $content = File::get($filePath);

        $this->assertStringContainsString('Acme Dental Care', $content);
        $this->assertStringContainsString('info@acmedental.example', $content);
        $this->assertStringContainsString('https://www.linkedin.com/company/acme-dental', $content);
        $this->assertStringContainsString('VALID (MX Verified)', $content);

        // Crucial: Tenant 2's data must NOT be present in Tenant 1's export
        $this->assertStringNotContainsString('Beta Legal Firm', $content);
        $this->assertStringNotContainsString('contact@betalegal.example', $content);

        // Clean up exported file
        if (File::exists($filePath)) {
            File::delete($filePath);
        }
    }
}

