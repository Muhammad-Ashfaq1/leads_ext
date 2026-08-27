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
            'https://generativelanguage.googleapis.com/v1beta/models/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'design_tokens' => [
                                            'primary_color' => 'bg-red-600',
                                            'text_color' => 'text-red-600',
                                            'font_family' => 'font-sans',
                                            'hero_layout' => 'split-with-form',
                                        ],
                                        'copy' => [
                                            'hero_badge' => 'Austin’s #1 Rated',
                                            'hero_headline' => 'Austin’s #1 Trusted Roofing Specialists',
                                            'hero_subheadline' => 'Reliable, residential and commercial roof repairs and replacements built to withstand Texas weather.',
                                            'primary_cta' => 'Book Inspection',
                                            'about_text' => 'For over a decade, Austin Premier Roofing has provided superior craftsmanship, premium materials, and honest service.',
                                            'niche_features' => [
                                                [
                                                    'title' => 'Residential Roof Replacement',
                                                    'description' => 'Complete tear-off and installation of high-grade asphalt shingles, metal, and tile roofing.',
                                                    'icon_name' => 'home',
                                                ],
                                                [
                                                    'title' => 'Emergency Storm Damage Repair',
                                                    'description' => 'Rapid response teams for hail and wind damage inspection, tarping, and insurance claims.',
                                                    'icon_name' => 'shield',
                                                ],
                                                [
                                                    'title' => 'Commercial Flat Roofing',
                                                    'description' => 'TPO, EPDM, and coating systems designed for longevity and energy efficiency.',
                                                    'icon_name' => 'wrench',
                                                ],
                                            ],
                                            'trust_indicators' => [
                                                'Licensed & Insured in Texas',
                                                'Over 500+ Roofs Completed',
                                                '10-Year Craftsmanship Guarantee',
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

        $this->assertEquals('Austin’s #1 Trusted Roofing Specialists', $result['copy']['hero_headline']);
        $this->assertEquals('bg-red-600', $result['design_tokens']['primary_color']);
        $this->assertEquals('split-with-form', $result['design_tokens']['hero_layout']);
        $this->assertCount(3, $result['copy']['niche_features']);
        $this->assertCount(3, $result['copy']['trust_indicators']);

        $freshLead = $this->lead->fresh();
        $this->assertNotNull($freshLead->generated_website_content);
        $this->assertEquals('Austin’s #1 Trusted Roofing Specialists', $freshLead->generated_website_content['copy']['hero_headline']);
    }

    public function test_category_based_design_token_defaults(): void
    {
        $service = new GeminiWebsiteService();

        // Auto Garage
        $autoTokens = $service->getDefaultDesignTokensForCategory('Auto Repair & Garage');
        $this->assertEquals('bg-red-600', $autoTokens['primary_color']);
        $this->assertEquals('split-with-form', $autoTokens['hero_layout']);

        // Real Estate
        $reTokens = $service->getDefaultDesignTokensForCategory('Real Estate Agency');
        $this->assertEquals('bg-slate-900', $reTokens['primary_color']);
        $this->assertEquals('font-serif', $reTokens['font_family']);
        $this->assertEquals('gallery-grid', $reTokens['hero_layout']);

        // Law Firm
        $lawTokens = $service->getDefaultDesignTokensForCategory('Law Firm & Legal Attorney');
        $this->assertEquals('bg-amber-800', $lawTokens['primary_color']);
        $this->assertEquals('font-serif', $lawTokens['font_family']);
        $this->assertEquals('centered-bold', $lawTokens['hero_layout']);
    }

    public function test_api_generate_demo_route_returns_200_and_preview_url(): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/models/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'design_tokens' => [
                                            'primary_color' => 'bg-blue-600',
                                            'text_color' => 'text-blue-600',
                                            'font_family' => 'font-sans',
                                            'hero_layout' => 'split-with-form',
                                        ],
                                        'copy' => [
                                            'hero_badge' => 'Top Rated',
                                            'hero_headline' => 'Elevate Your Home with Austin Premier Roofing',
                                            'hero_subheadline' => 'Austin’s highest rated roofing team.',
                                            'primary_cta' => 'Schedule Today',
                                            'about_text' => 'Quality roofing service for homeowners.',
                                            'niche_features' => [
                                                ['title' => 'Service 1', 'description' => 'Desc 1', 'icon_name' => 'check'],
                                                ['title' => 'Service 2', 'description' => 'Desc 2', 'icon_name' => 'star'],
                                                ['title' => 'Service 3', 'description' => 'Desc 3', 'icon_name' => 'wrench'],
                                            ],
                                            'trust_indicators' => ['Trust 1', 'Trust 2', 'Trust 3'],
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
            'https://generativelanguage.googleapis.com/v1beta/models/*' => Http::response([
                'error' => [
                    'message' => 'Quota exceeded for Gemini model',
                ],
            ], 429),
        ]);

        $response = $this->actingAs($this->user)->postJson(route('leads.generate-demo', $this->lead->id));

        $response->assertStatus(500);
        $response->assertJson([
            'success' => false,
        ]);
        $response->assertJsonFragment([
            'message' => 'Failed to generate demo website: Quota exceeded for Gemini model',
        ]);
    }

    public function test_public_preview_renders_split_with_form_layout_and_claim_header(): void
    {
        $this->lead->update([
            'generated_website_content' => [
                'design_tokens' => [
                    'primary_color' => 'bg-red-600',
                    'text_color' => 'text-red-600',
                    'font_family' => 'font-sans',
                    'hero_layout' => 'split-with-form',
                ],
                'copy' => [
                    'hero_badge' => 'Family Owned',
                    'hero_headline' => 'Masterful Auto Diagnostics & Repair',
                    'hero_subheadline' => 'Precision auto care serving local motorists.',
                    'primary_cta' => 'Book Repair',
                    'about_text' => 'Award-winning garage serving the community with transparency.',
                    'niche_features' => [
                        ['title' => 'Brake Replacement', 'description' => 'Fast brake inspections and pad changes.', 'icon_name' => 'wrench'],
                        ['title' => 'Engine Tune-Up', 'description' => 'Computerized diagnostics and tuning.', 'icon_name' => 'tool'],
                        ['title' => 'Oil & Fluids', 'description' => 'Full synthetic fluid flushes and filter changes.', 'icon_name' => 'car'],
                    ],
                    'trust_indicators' => [
                        'ASE Certified Techs',
                        'Same-Day Service',
                        '12-Month Warranty',
                    ],
                ],
            ],
        ]);

        $response = $this->get(route('leads.preview', $this->lead->uuid));

        $response->assertStatus(200);
        $response->assertSee('Claim This Website');
        $response->assertSee('Masterful Auto Diagnostics &amp; Repair', false);
        $response->assertSee('Book Repair');
        $response->assertSee('Request Service & Quote');
        $response->assertSee('bg-red-600');
        $response->assertSee('Brake Replacement');
        $response->assertSee('ASE Certified Techs');
    }

    public function test_public_preview_renders_gallery_grid_layout(): void
    {
        $this->lead->update([
            'generated_website_content' => [
                'design_tokens' => [
                    'primary_color' => 'bg-slate-900',
                    'text_color' => 'text-slate-900',
                    'font_family' => 'font-serif',
                    'hero_layout' => 'gallery-grid',
                ],
                'copy' => [
                    'hero_badge' => 'Exclusive Luxury Living',
                    'hero_headline' => 'Prestigious Estates & Penthouse Residences',
                    'hero_subheadline' => 'Curated properties in the most coveted neighborhoods.',
                    'primary_cta' => 'View Listings',
                    'about_text' => 'Boutique real estate brokerage delivering exceptional results.',
                    'niche_features' => [
                        ['title' => 'Waterfront Villas', 'description' => 'Panoramic lake and ocean residences.', 'icon_name' => 'home'],
                        ['title' => 'Modern Mansions', 'description' => 'Architectural marvels with smart tech.', 'icon_name' => 'sparkles'],
                        ['title' => 'Downtown Penthouses', 'description' => 'Sky-high luxury living with private terraces.', 'icon_name' => 'star'],
                    ],
                    'trust_indicators' => [
                        'Over $250M in Sales',
                        'Top 1% Nationwide Brokerage',
                        'Discreet & Confidential',
                    ],
                ],
            ],
        ]);

        $response = $this->get(route('leads.preview', $this->lead->uuid));

        $response->assertStatus(200);
        $response->assertSee('font-serif');
        $response->assertSee('Prestigious Estates &amp; Penthouse Residences', false);
        $response->assertSee('Featured #01');
        $response->assertSee('Waterfront Villas');
        $response->assertSee('View Listings');
    }

    public function test_fallback_configuration_when_design_tokens_omitted(): void
    {
        $this->lead->update([
            'generated_website_content' => [
                'hero_headline' => 'Basic Fallback Headline',
                'hero_subheadline' => 'Fallback subheadline description.',
                'about_text' => 'Fallback about text.',
                'services' => [
                    ['title' => 'Basic Service', 'description' => 'Basic description.'],
                ],
            ],
        ]);

        $response = $this->get(route('leads.preview', $this->lead->uuid));

        $response->assertStatus(200);
        $response->assertSee('Basic Fallback Headline');
        $response->assertSee('Claim This Website');
        $response->assertSee('bg-blue-600');
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

    public function test_generate_demo_button_is_only_visible_for_leads_without_a_website(): void
    {
        // Lead without website
        $leadWithoutWebsite = ExtractedLead::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'extraction_job_id' => $this->job->id,
            'uuid' => (string) Str::uuid(),
            'business_name' => 'No Website Garage',
            'category' => 'Auto Repair',
            'address' => '123 Main St, Austin, TX',
            'city' => 'Austin',
            'website' => null,
        ]);

        // Lead with existing website
        $leadWithWebsite = ExtractedLead::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'extraction_job_id' => $this->job->id,
            'uuid' => (string) Str::uuid(),
            'business_name' => 'Has Website Bakery',
            'category' => 'Bakery',
            'address' => '456 Bakery Ave, Austin, TX',
            'city' => 'Austin',
            'website' => 'https://haswebsitebakery.com',
        ]);

        $response = $this->actingAs($this->user)->get(route('leads.index'));

        $response->assertStatus(200);

        // Assert button and action exist for lead without website
        $response->assertSee('id="btn-demo-' . $leadWithoutWebsite->id . '"', false);
        $response->assertSee('generateDemo(' . $leadWithoutWebsite->id . ')', false);

        // Assert button and action DO NOT exist for lead with website
        $response->assertDontSee('id="btn-demo-' . $leadWithWebsite->id . '"', false);
        $response->assertDontSee('generateDemo(' . $leadWithWebsite->id . ')', false);
    }
}
