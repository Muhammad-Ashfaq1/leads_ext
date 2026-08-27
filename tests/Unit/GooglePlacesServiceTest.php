<?php

namespace Tests\Unit;

use App\Services\EmailVerifier;
use App\Services\GeospatialGridService;
use App\Services\GooglePlacesService;
use App\Services\SocialMediaExtractor;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GooglePlacesServiceTest extends TestCase
{
    private GooglePlacesService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new GooglePlacesService(
            new GeospatialGridService(),
            new SocialMediaExtractor(),
            new EmailVerifier()
        );
    }

    public function test_quick_enrich_website_rejects_ssrf_unsafe_urls(): void
    {
        $metadataResult = $this->service->quickEnrichWebsite('http://169.254.169.254/latest/meta-data');
        $this->assertEmpty($metadataResult['emails']);
        $this->assertEmpty($metadataResult['social_links']);

        $invalidResult = $this->service->quickEnrichWebsite('not-a-valid-url');
        $this->assertEmpty($invalidResult['emails']);
    }

    public function test_quick_enrich_website_enforces_512kb_limit_and_strips_scripts(): void
    {
        // 800KB of content: email inside first 512KB is kept, script is stripped, email beyond 512KB is truncated
        $largeNoise = str_repeat('<div>A lot of content to fill space.</div>', 20000);
        $payload = "<html><head><script>var leaked = 'admin@scriptnoise.example';</script></head><body><a href='mailto:contact@clinic.example'>Contact</a> <a href='https://www.linkedin.com/company/clinic-dental'>LinkedIn</a> {$largeNoise} <a href='mailto:truncated@clinic.example'>Truncated</a></body></html>";

        Http::fake([
            'https://clinic.example' => Http::response($payload, 200),
        ]);

        $result = $this->service->quickEnrichWebsite('https://clinic.example');

        $this->assertContains('contact@clinic.example', $result['emails']);
        $this->assertNotContains('admin@scriptnoise.example', $result['emails']);
        $this->assertNotContains('truncated@clinic.example', $result['emails']);
        $this->assertArrayHasKey('linkedin', $result['social_links']);
        $this->assertEquals('https://www.linkedin.com/company/clinic-dental', $result['social_links']['linkedin']);
        $this->assertArrayHasKey('contact@clinic.example', $result['email_verification_status']);
    }
}
