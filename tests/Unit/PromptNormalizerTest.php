<?php

namespace Tests\Unit;

use App\Support\PromptNormalizer;
use PHPUnit\Framework\TestCase;

class PromptNormalizerTest extends TestCase
{
    public function test_it_strips_find_prefix(): void
    {
        $this->assertSame(
            'dentists in Lahore',
            PromptNormalizer::toSearchQuery('Find dentists in Lahore')
        );
    }

    public function test_it_keeps_plain_queries(): void
    {
        $this->assertSame(
            'roofing companies in Houston Texas',
            PromptNormalizer::toSearchQuery('roofing companies in Houston Texas')
        );
    }
}
