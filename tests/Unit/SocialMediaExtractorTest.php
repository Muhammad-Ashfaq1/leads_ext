<?php

namespace Tests\Unit;

use App\Services\SocialMediaExtractor;
use Tests\TestCase;

class SocialMediaExtractorTest extends TestCase
{
    private SocialMediaExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extractor = new SocialMediaExtractor();
    }

    public function test_extracts_all_major_social_platforms_from_html(): void
    {
        $html = <<<HTML
        <html>
        <head><title>Test Business</title></head>
        <body>
            <footer>
                <a href="https://www.linkedin.com/company/acme-corp?trk=public_profile">LinkedIn</a>
                <a href="https://facebook.com/acmeplumbing">Facebook</a>
                <a href="https://instagram.com/acme_plumbing_official">Instagram</a>
                <a href="https://twitter.com/acme_plumbing">Twitter</a>
                <a href="https://www.youtube.com/@acmeplumbing">YouTube</a>
            </footer>
        </body>
        </html>
        HTML;

        $results = $this->extractor->extract($html);

        $this->assertArrayHasKey('linkedin', $results);
        $this->assertSame('https://www.linkedin.com/company/acme-corp', $results['linkedin']);

        $this->assertArrayHasKey('facebook', $results);
        $this->assertSame('https://www.facebook.com/acmeplumbing', $results['facebook']);

        $this->assertArrayHasKey('instagram', $results);
        $this->assertSame('https://www.instagram.com/acme_plumbing_official', $results['instagram']);

        $this->assertArrayHasKey('twitter', $results);
        $this->assertSame('https://x.com/acme_plumbing', $results['twitter']);

        $this->assertArrayHasKey('youtube', $results);
        $this->assertSame('https://www.youtube.com/@acmeplumbing', $results['youtube']);
    }

    public function test_filters_out_sharing_and_post_links(): void
    {
        $html = <<<HTML
        <html>
        <body>
            <a href="https://facebook.com/sharer/sharer.php?u=https://example.com">Share on Facebook</a>
            <a href="https://twitter.com/intent/tweet?text=Hello">Tweet this</a>
            <a href="https://instagram.com/p/C123456789/">Post</a>
            <a href="https://instagram.com/reel/C987654321/">Reel</a>
            <a href="https://linkedin.com/shareArticle?mini=true">Share on LinkedIn</a>
        </body>
        </html>
        HTML;

        $results = $this->extractor->extract($html);

        $this->assertEmpty($results);
    }

    public function test_extracts_x_com_and_youtube_channel_variations(): void
    {
        $html = <<<HTML
        <div>
            <a href="https://x.com/tech_corp">X Corp</a>
            <a href="https://youtube.com/c/TechCorpOfficial">YouTube</a>
        </div>
        HTML;

        $results = $this->extractor->extract($html);

        $this->assertSame('https://x.com/tech_corp', $results['twitter']);
        $this->assertSame('https://www.youtube.com/c/TechCorpOfficial', $results['youtube']);
    }

    public function test_handles_empty_html_safely(): void
    {
        $this->assertSame([], $this->extractor->extract(''));
        $this->assertSame([], $this->extractor->extract('<html><body>No links here</body></html>'));
    }
}

