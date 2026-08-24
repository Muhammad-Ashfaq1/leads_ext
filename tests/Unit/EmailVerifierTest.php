<?php

namespace Tests\Unit;

use App\Services\EmailVerifier;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class EmailVerifierTest extends TestCase
{
    private EmailVerifier $verifier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->verifier = new EmailVerifier();
        Cache::flush();
    }

    public function test_validates_rfc_syntax(): void
    {
        $valid = $this->verifier->verify('contact@example.com');
        $this->assertTrue($valid['is_rfc_valid']);

        $invalid = $this->verifier->verify('not-an-email');
        $this->assertFalse($invalid['is_rfc_valid']);
        $this->assertFalse($invalid['is_valid']);
    }

    public function test_detects_disposable_domains(): void
    {
        $disposable = $this->verifier->verify('user@mailinator.com');
        $this->assertTrue($disposable['is_rfc_valid']);
        $this->assertTrue($disposable['is_disposable']);
        $this->assertFalse($disposable['is_valid']);

        $temp = $this->verifier->verify('random@tempmail.com');
        $this->assertTrue($temp['is_disposable']);
        $this->assertFalse($temp['is_valid']);

        $real = $this->verifier->verify('sales@example.com');
        $this->assertFalse($real['is_disposable']);
    }

    public function test_verifies_mx_record_and_uses_cache(): void
    {
        $result = $this->verifier->verify('contact@example.com');
        $this->assertTrue($result['has_mx']);
        $this->assertTrue($result['is_valid']);

        // Check that result is cached in Laravel Cache
        $cacheKey = 'email_mx_check:'.md5('example.com');
        $this->assertTrue(Cache::has($cacheKey));
    }

    public function test_verify_batch_validates_multiple_emails(): void
    {
        $emails = [
            'valid@example.com',
            'junk@mailinator.com',
            'bad-format',
        ];

        $batch = $this->verifier->verifyBatch($emails);

        $this->assertArrayHasKey('valid@example.com', $batch);
        $this->assertTrue($batch['valid@example.com']['is_valid']);

        $this->assertArrayHasKey('junk@mailinator.com', $batch);
        $this->assertTrue($batch['junk@mailinator.com']['is_disposable']);
        $this->assertFalse($batch['junk@mailinator.com']['is_valid']);

        $this->assertArrayHasKey('bad-format', $batch);
        $this->assertFalse($batch['bad-format']['is_rfc_valid']);
    }
}

