<?php

namespace App\Services;

class SocialMediaExtractor
{
    /**
     * Extract social media profile URLs from an HTML document.
     *
     * @param  string  $html  Raw HTML string
     * @return array<string, string> Associative map of platform => profile_url
     */
    public function extract(string $html): array
    {
        if (empty($html)) {
            return [];
        }

        $socials = [
            'linkedin' => $this->extractLinkedIn($html),
            'facebook' => $this->extractFacebook($html),
            'instagram' => $this->extractInstagram($html),
            'twitter' => $this->extractTwitter($html),
            'youtube' => $this->extractYouTube($html),
        ];

        return array_filter($socials);
    }

    /**
     * Extract LinkedIn profile/company URL.
     */
    private function extractLinkedIn(string $html): ?string
    {
        $patterns = [
            '/https?:\/\/(?:[a-zA-Z0-9-]+\.)?linkedin\.com\/(?:company|school|in)\/([a-zA-Z0-9_-]+)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $slug = trim($matches[1], '/');
                if ($slug && ! in_array(strtolower($slug), ['sharearticle', 'sharing', 'share'], true)) {
                    $type = str_contains($matches[0], '/company/') ? 'company' : (str_contains($matches[0], '/school/') ? 'school' : 'in');

                    return "https://www.linkedin.com/{$type}/{$slug}";
                }
            }
        }

        return null;
    }

    /**
     * Extract Facebook profile or page URL.
     */
    private function extractFacebook(string $html): ?string
    {
        $pattern = '/https?:\/\/(?:[a-zA-Z0-9-]+\.)?facebook\.com\/([a-zA-Z0-9._-]+)/i';

        if (preg_match_all($pattern, $html, $matches)) {
            $excluded = [
                'sharer', 'share.php', 'share', 'dialog', 'plugins', 'group', 'groups',
                'events', 'login', 'help', 'policies', 'privacy', 'tr', 'v2.0', 'pages',
                'watch', 'marketplace', 'gaming', 'hashtag', 'photo', 'story',
            ];

            foreach ($matches[1] as $slug) {
                $cleanSlug = trim($slug, '/');
                if ($cleanSlug && ! in_array(strtolower($cleanSlug), $excluded, true) && ! str_starts_with($cleanSlug, 'sharer')) {
                    return "https://www.facebook.com/{$cleanSlug}";
                }
            }
        }

        return null;
    }

    /**
     * Extract Instagram profile URL.
     */
    private function extractInstagram(string $html): ?string
    {
        $pattern = '/https?:\/\/(?:[a-zA-Z0-9-]+\.)?instagram\.com\/([a-zA-Z0-9._-]+)/i';

        if (preg_match_all($pattern, $html, $matches)) {
            $excluded = [
                'p', 'reel', 'reels', 'tv', 'explore', 'stories', 'accounts',
                'developer', 'about', 'legal', 'privacy', 'terms', 'direct',
            ];

            foreach ($matches[1] as $slug) {
                $cleanSlug = trim($slug, '/');
                if ($cleanSlug && ! in_array(strtolower($cleanSlug), $excluded, true)) {
                    return "https://www.instagram.com/{$cleanSlug}";
                }
            }
        }

        return null;
    }

    /**
     * Extract Twitter / X profile URL.
     */
    private function extractTwitter(string $html): ?string
    {
        $pattern = '/https?:\/\/(?:[a-zA-Z0-9-]+\.)?(?:twitter\.com|x\.com)\/([a-zA-Z0-9_]+)/i';

        if (preg_match_all($pattern, $html, $matches)) {
            $excluded = [
                'intent', 'share', 'home', 'explore', 'notifications', 'messages',
                'search', 'hashtag', 'i', 'privacy', 'tos', 'about', 'help', 'login',
            ];

            foreach ($matches[1] as $slug) {
                $cleanSlug = trim($slug, '/');
                if ($cleanSlug && ! in_array(strtolower($cleanSlug), $excluded, true)) {
                    return "https://x.com/{$cleanSlug}";
                }
            }
        }

        return null;
    }

    /**
     * Extract YouTube channel or profile URL.
     */
    private function extractYouTube(string $html): ?string
    {
        // Check @handle format first
        if (preg_match('/https?:\/\/(?:[a-zA-Z0-9-]+\.)?youtube\.com\/@([a-zA-Z0-9_.-]+)/i', $html, $matches)) {
            return 'https://www.youtube.com/@'.trim($matches[1], '/');
        }

        // Check channel / user / c formats
        if (preg_match('/https?:\/\/(?:[a-zA-Z0-9-]+\.)?youtube\.com\/(?:c|channel|user)\/([a-zA-Z0-9_.-]+)/i', $html, $matches)) {
            $path = str_contains($matches[0], '/channel/') ? 'channel' : (str_contains($matches[0], '/user/') ? 'user' : 'c');

            return "https://www.youtube.com/{$path}/".trim($matches[1], '/');
        }

        return null;
    }
}

