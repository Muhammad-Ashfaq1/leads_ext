<?php

namespace Tests\Feature;

use App\Models\ExtractionJob;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SseSessionSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_places_sse_stream_handles_session_and_streams_events(): void
    {
        config(['services.google.maps_api_key' => 'test-key']);

        Http::fake([
            'https://places.googleapis.com/v1/places:searchText' => Http::response([
                'places' => [
                    [
                        'id' => 'place_sse_1',
                        'displayName' => ['text' => 'SSE Test Business'],
                        'formattedAddress' => 'Dallas, TX',
                        'websiteUri' => 'https://clinic.example',
                    ],
                ],
            ], 200),
            'https://clinic.example' => Http::response('<html>Contact us at sse@clinic.example</html>', 200),
        ]);

        $job = ExtractionJob::create([
            'uuid' => 'eeeeeeee-eeee-eeee-eeee-eeeeeeeeeeee',
            'prompt' => 'Dentists in Dallas',
            'query' => 'Dentists in Dallas',
            'status' => 'starting',
            'limit' => 1,
            'mode' => 'google_api',
        ]);

        $response = $this->get("/api/extractor/{$job->uuid}/stream");

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/event-stream; charset=UTF-8');
        $this->assertStringContainsString('no-cache', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-transform', (string) $response->headers->get('Cache-Control'));
        $response->assertHeader('X-Accel-Buffering', 'no');

        $streamContent = $response->streamedContent();
        $this->assertStringContainsString('SSE Test Business', $streamContent);
        $this->assertStringContainsString('sse@clinic.example', $streamContent);
    }
}
