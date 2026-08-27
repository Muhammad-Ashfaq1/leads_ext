<?php

namespace Tests\Feature;

use App\Models\ExtractedLead;
use App\Models\ExtractionJob;
use App\Models\Tenant;
use App\Models\User;
use App\Services\EmailOutreachService;
use App\Services\GeminiWebsiteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class GeminiWebsiteGeneratorTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $user;
    private ExtractionJob $job;
    private ExtractedLead $lead;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.gemini.api_key' => 'test-gemini-key']);

        $this->tenant = Tenant::create([
            'name' => 'Demo Agency',
            'slug' => 'demo-agency',
            'plan' => 'growth',
            'lead_quota' => 1000,
            'is_active' => true,
        ]);

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role' => 'admin',
            'email' => 'admin@demoagency.com',
            'name' => 'Agency Admin',
            'is_active' => true,
        ]);

        $this->job = ExtractionJob::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'uuid' => (string) Str::uuid(),
            'prompt' => 'Roofing in Austin',
            'query' => 'Roofing in Austin',
            'status' => ExtractionJob::STATUS_COMPLETED,
            'limit' => 20,
            'mode' => 'live',
        ]);

        $this->lead = ExtractedLead::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'extraction_job_id' => $this->job->id,
            'uuid' => (string) Str::uuid(),
            'business_name' => 'Austin Premier Roofing',
            'category' => 'Roofing Contractor',
            'address' => '700 Congress Ave, Austin, TX',
            'city' => 'Austin',
            'phone' => '+1 (512) 555-7890',
            'emails' => ['info@austinpremierroofing.com'],
            'website' => 'https://austinpremierroofing.com',
            'rating' => 4.9,
            'review_count' => 88,
        ]);
    }

    public function test_gemini_service_generates_and_saves_spec_website_content(): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'hero_headline' => 'Austin’s #1 Trusted Roofing Specialists',
                                        'hero_subheadline' => 'Reliable, residential and commercial roof repairs and replacements built to withstand Texas weather.',
                                        'about_text' => 'For over a decade, Austin Premier Roofing has provided superior craftsmanship, premium materials, and honest service.',
                                        'services' => [
                                            [
                                                'title' => 'Residential Roof Replacement',
                                                'description' => 'Complete tear-off and installation of high-grade asphalt shingles, metal, and tile roofing.',
                                            ],
                                            [
                                                'title' => 'Emergency Storm Damage Repair',
                                                'description' => 'Rapid response teams for hail and wind damage inspection, tarping, and insurance claims.',
                                            ],
                                            [
                                                'title' => 'Commercial Flat Roofing',
                                                'description' => 'TPO, EPDM, and coating systems designed for longevity and energy efficiency.',
                                            ],
                                        ],
                                    ]),
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new GeminiWebsiteService();
        $result = $service->generateSite($this->lead);

        $this->assertEquals('Austin’s #1 Trusted Roofing Specialists', $result['hero_headline']);
        $this->assertCount(3, $result['services']);

        $freshLead = $this->lead->fresh();
        $this->assertNotNull($freshLead->generated_website_content);
        $this->assertEquals('Austin’s #1 Trusted Roofing Specialists', $freshLead->generated_website_content['hero_headline']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'gemini-2.5-flash:generateContent')
                && str_contains($request->url(), 'key=test-gemini-key')
                && $request['systemInstruction']['parts'][0]['text'] === 'You are an expert copywriter. The user will provide local business data. Generate JSON for a landing page containing: hero_headline, hero_subheadline, about_text, and a services array (3 items with title/description). Output valid JSON only.';
        });
    }

    public function test_api_generate_demo_route_returns_200_and_preview_url(): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'hero_headline' => 'Elevate Your Home with Austin Premier Roofing',
                                        'hero_subheadline' => 'Austin’s highest rated roofing team.',
                                        'about_text' => 'Quality roofing service for homeowners.',
                                        'services' => [
                                            ['title' => 'Service 1', 'description' => 'Desc 1'],
                                            ['title' => 'Service 2', 'description' => 'Desc 2'],
                                            ['title' => 'Service 3', 'description' => 'Desc 3'],
                                        ],
                                    ]),
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->user)->postJson(route('leads.generate-demo', $this->lead->id));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'preview_url' => route('leads.preview', $this->lead->uuid),
            'lead_id' => $this->lead->id,
        ]);

        $this->assertNotNull($this->lead->fresh()->generated_website_content);
    }

    public function test_api_generate_demo_handles_api_failure_gracefully(): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent*' => Http::response([
                'error' => [
                    'message' => 'Quota exceeded for Gemini Flash model',
                ],
            ], 429),
        ]);

        $response = $this->actingAs($this->user)->postJson(route('leads.generate-demo', $this->lead->id));

        $response->assertStatus(500);
        $response->assertJson([
            'success' => false,
        ]);
        $response->assertJsonFragment([
            'message' => 'Failed to generate demo website: Quota exceeded for Gemini Flash model',
        ]);
    }

    public function test_public_preview_route_renders_spec_website_without_authentication(): void
    {
        $this->lead->update([
            'generated_website_content' => [
                'hero_headline' => 'Masterful Roofing in Central Texas',
                'hero_subheadline' => 'Precision roofing solutions tailored for Austin properties.',
                'about_text' => 'Award-winning roofing company serving Austin homeowners.',
                'services' => [
                    ['title' => 'Tile & Shingle Repair', 'description' => 'Fast repairs with guaranteed waterproofing.'],
                    ['title' => 'Full Roof Overhauls', 'description' => 'Long-lasting materials installed by certified crews.'],
                    ['title' => 'Gutter & Flashing', 'description' => 'Full drainage protection for your home structure.'],
                ],
            ],
        ]);

        // Unauthenticated GET request to /preview/{uuid}
        $response = $this->get(route('leads.preview', $this->lead->uuid));

        $response->assertStatus(200);
        $response->assertSee('Masterful Roofing in Central Texas');
        $response->assertSee('Austin Premier Roofing');
        $response->assertSee('Tile &amp; Shingle Repair', false);
        $response->assertSee('+1 (512) 555-7890');
        $response->assertSee('info@austinpremierroofing.com');
    }

    public function test_email_outreach_service_replaces_demo_website_url_tag(): void
    {
        $service = new EmailOutreachService();

        $emailTemplate = '<p>Hi {{business_name}}, check your custom spec website here: {{demo_website_url}}</p>';
        $rendered = $service->renderVariables($emailTemplate, $this->lead, $this->user);

        $expectedUrl = route('leads.preview', $this->lead->uuid);
        $this->assertStringContainsString($expectedUrl, $rendered);
        $this->assertStringContainsString('Austin Premier Roofing', $rendered);
        $this->assertStringNotContainsString('{{demo_website_url}}', $rendered);
    }
}

