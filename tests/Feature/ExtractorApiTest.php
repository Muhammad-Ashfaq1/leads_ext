<?php

namespace Tests\Feature;

use App\Models\ExtractedLead;
use App\Models\ExtractionJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExtractorApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'extractor.service_url' => 'http://127.0.0.1:8001',
            'extractor.allow_mock' => true,
        ]);
    }

    public function test_start_validates_prompt_and_limit(): void
    {
        $this->postJson('/api/extractor/start', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['prompt']);

        $this->postJson('/api/extractor/start', [
            'prompt' => 'Find dentists in Lahore',
            'limit' => 5000,
        ])->assertStatus(422)->assertJsonValidationErrors(['limit']);
    }

    public function test_start_creates_job_and_returns_job_id(): void
    {
        Http::fake([
            'http://127.0.0.1:8001/jobs' => Http::response([
                'job_id' => '11111111-1111-1111-1111-111111111111',
                'query' => 'dentists in Lahore',
                'status' => 'starting',
            ], 200),
        ]);

        $this->postJson('/api/extractor/start', [
            'prompt' => 'Find dentists in Lahore',
            'limit' => 100,
            'mode' => 'mock',
        ])->assertOk()->assertJson([
            'job_id' => '11111111-1111-1111-1111-111111111111',
            'query' => 'dentists in Lahore',
        ]);

        $this->assertDatabaseHas('extraction_jobs', [
            'uuid' => '11111111-1111-1111-1111-111111111111',
            'prompt' => 'Find dentists in Lahore',
            'query' => 'dentists in Lahore',
            'limit' => 100,
        ]);
    }

    public function test_start_reports_unavailable_python_service(): void
    {
        Http::fake([
            'http://127.0.0.1:8001/*' => Http::failedConnection(),
        ]);

        $this->postJson('/api/extractor/start', [
            'prompt' => 'Find dentists in Lahore',
        ])->assertStatus(503)->assertJsonFragment([
            'message' => 'Extractor service is unavailable. Please start the Python extractor service.',
        ]);
    }

    public function test_status_and_stop_endpoints(): void
    {
        $job = ExtractionJob::create([
            'uuid' => '22222222-2222-2222-2222-222222222222',
            'prompt' => 'Find dentists in Lahore',
            'query' => 'dentists in Lahore',
            'status' => 'extracting',
            'limit' => 100,
        ]);

        Http::fake([
            'http://127.0.0.1:8001/jobs/22222222-2222-2222-2222-222222222222' => Http::response([
                'job_id' => $job->uuid,
                'status' => 'extracting',
                'leads_extracted' => 4,
                'businesses_seen' => 6,
            ], 200),
            'http://127.0.0.1:8001/jobs/22222222-2222-2222-2222-222222222222/stop' => Http::response([
                'job_id' => $job->uuid,
                'status' => 'cancelled',
                'leads_extracted' => 4,
            ], 200),
        ]);

        $this->getJson("/api/extractor/{$job->uuid}/status")
            ->assertOk()
            ->assertJsonPath('job_id', $job->uuid)
            ->assertJsonPath('leads_extracted', 4);

        $this->postJson("/api/extractor/{$job->uuid}/stop")
            ->assertOk()
            ->assertJsonPath('status', 'cancelled');
    }

    public function test_unknown_job_id_is_rejected(): void
    {
        $this->getJson('/api/extractor/not-a-real-job/status')->assertNotFound();
        $this->postJson('/api/extractor/not-a-real-job/stop')->assertNotFound();
    }

    public function test_stream_emits_error_when_python_is_down(): void
    {
        config(['extractor.service_url' => 'http://127.0.0.1:59999']);

        $job = ExtractionJob::create([
            'uuid' => '33333333-3333-3333-3333-333333333333',
            'prompt' => 'Find dentists in Lahore',
            'query' => 'dentists in Lahore',
            'status' => 'starting',
            'limit' => 10,
        ]);

        $response = $this->get("/api/extractor/{$job->uuid}/stream");
        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/event-stream; charset=UTF-8');
        $this->assertStringContainsString('Extractor service is unavailable', $response->streamedContent());
    }

    public function test_csv_export_uses_extracted_rows_only(): void
    {
        $job = ExtractionJob::create([
            'uuid' => '44444444-4444-4444-4444-444444444444',
            'prompt' => 'Find dentists in Lahore',
            'query' => 'dentists in Lahore',
            'status' => 'completed',
            'limit' => 10,
        ]);

        ExtractedLead::create([
            'extraction_job_id' => $job->id,
            'business_name' => 'Example Dental Clinic',
            'address' => 'Lahore, Pakistan',
            'phone' => '+92 42 1111111',
            'emails' => ['hello@clinic.example'],
            'website' => 'https://clinic.example',
            'google_maps_url' => 'https://maps.google.com/?cid=1',
            'category' => 'Dentist',
            'rating' => 4.7,
            'review_count' => 12,
            'source' => 'Google Maps',
        ]);

        $response = $this->get("/api/extractor/{$job->uuid}/export");
        $response->assertOk();
        $csv = $response->streamedContent();
        $this->assertStringContainsString('Business Name', $csv);
        $this->assertStringContainsString('Example Dental Clinic', $csv);
        $this->assertStringContainsString('hello@clinic.example', $csv);
        $this->assertStringNotContainsString('invented', $csv);
    }

    public function test_csv_export_can_filter_by_lead_ids(): void
    {
        $job = ExtractionJob::create([
            'uuid' => '55555555-5555-5555-5555-555555555555',
            'prompt' => 'Find clinics in Lahore',
            'query' => 'clinics in Lahore',
            'status' => 'completed',
            'limit' => 10,
        ]);

        $lead1 = ExtractedLead::create([
            'extraction_job_id' => $job->id,
            'business_name' => 'Clinic Alpha',
            'address' => 'Lahore, Pakistan',
            'phone' => '+92 42 1111111',
            'source' => 'Google Maps',
        ]);

        $lead2 = ExtractedLead::create([
            'extraction_job_id' => $job->id,
            'business_name' => 'Clinic Beta',
            'address' => 'Lahore, Pakistan',
            'phone' => '+92 42 2222222',
            'source' => 'Google Maps',
        ]);

        $response = $this->get("/api/extractor/{$job->uuid}/export?ids={$lead1->id}");
        $response->assertOk();
        $csv = $response->streamedContent();
        $this->assertStringContainsString('Clinic Alpha', $csv);
        $this->assertStringNotContainsString('Clinic Beta', $csv);
    }

    public function test_mock_mode_is_hidden_in_production(): void
    {
        config(['extractor.allow_mock' => false]);

        $this->postJson('/api/extractor/start', [
            'prompt' => 'Find dentists in Lahore',
            'mode' => 'mock',
        ])->assertForbidden();
    }

    public function test_google_api_mode_validates_api_key(): void
    {
        config(['services.google.maps_api_key' => null]);

        $this->postJson('/api/extractor/start', [
            'prompt' => 'Dentists',
            'location' => '90210',
            'mode' => 'google_api',
        ])->assertStatus(422)->assertJsonFragment([
            'message' => 'Google Maps API key is required. Please provide an API key or configure GOOGLE_MAPS_API_KEY in .env.',
        ]);
    }

    public function test_google_api_mode_creates_job_with_location(): void
    {
        config(['services.google.maps_api_key' => 'test-api-key']);

        $response = $this->postJson('/api/extractor/start', [
            'prompt' => 'Dentists',
            'location' => '90210',
            'mode' => 'google_api',
            'limit' => 50,
        ]);

        $response->assertOk()
            ->assertJsonPath('query', 'Dentists in 90210')
            ->assertJsonPath('status', 'starting');

        $this->assertDatabaseHas('extraction_jobs', [
            'query' => 'Dentists in 90210',
            'mode' => 'google_api',
            'limit' => 50,
        ]);
    }

    public function test_google_api_stream_extracts_and_saves_leads(): void
    {
        config(['services.google.maps_api_key' => 'test-api-key']);

        Http::fake([
            'https://places.googleapis.com/v1/places:searchText' => Http::response([
                'places' => [
                    [
                        'id' => 'place_123',
                        'displayName' => ['text' => 'Beverly Hills Dental'],
                        'formattedAddress' => '90210 Beverly Hills, CA',
                        'nationalPhoneNumber' => '(310) 555-0199',
                        'websiteUri' => 'https://beverlydental.example',
                        'rating' => 4.9,
                        'userRatingCount' => 88,
                        'googleMapsUri' => 'https://maps.google.com/?cid=123',
                        'primaryTypeDisplayName' => ['text' => 'Dentist'],
                    ],
                ],
            ], 200),
            'https://beverlydental.example' => Http::response('<html>Contact us at info@beverlydental.example</html>', 200),
        ]);

        $job = ExtractionJob::create([
            'uuid' => '66666666-6666-6666-6666-666666666666',
            'prompt' => 'Dentists (90210)',
            'query' => 'Dentists in 90210',
            'status' => 'starting',
            'limit' => 10,
            'mode' => 'google_api',
        ]);

        $response = $this->get("/api/extractor/{$job->uuid}/stream");
        $response->assertOk();
        $streamContent = $response->streamedContent();

        $this->assertStringContainsString('Beverly Hills Dental', $streamContent);
        $this->assertStringContainsString('info@beverlydental.example', $streamContent);

        $this->assertDatabaseHas('extracted_leads', [
            'extraction_job_id' => $job->id,
            'business_name' => 'Beverly Hills Dental',
            'phone' => '(310) 555-0199',
            'source' => 'Google Places API',
        ]);
    }
}
