<?php

namespace App\Support;

class PromptNormalizer
{
    public static function toSearchQuery(string $prompt): string
    {
        $query = trim(preg_replace('/\s+/', ' ', $prompt) ?? $prompt);

        $query = preg_replace(
            '/^(please|can you|could you|i want to|i\'d like to|id like to|help me)\s+/i',
            '',
            $query
        ) ?? $query;

        $query = preg_replace(
            '/^(find(?:\s+me)?|search(?:\s+for)?|look(?:\s+for)?|get(?:\s+me)?|extract|scrape|list|show(?:\s+me)?|locate)\s+/i',
            '',
            $query
        ) ?? $query;

        $query = preg_replace(
            '/\s+with\s+(phone\s+numbers?|emails?|websites?|contact\s+details?)(?:\s+and\s+(phone\s+numbers?|emails?|websites?|contact\s+details?))*$/i',
            '',
            $query
        ) ?? $query;

        $query = trim($query);

        return $query !== '' ? $query : trim($prompt);
    }
}
