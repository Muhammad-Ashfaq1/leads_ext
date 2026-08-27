<?php

namespace Tests\Unit;

use App\Support\SsrfGuard;
use Tests\TestCase;

class SsrfGuardTest extends TestCase
{
    public function test_blocks_empty_and_invalid_urls(): void
    {
        $this->assertFalse(SsrfGuard::isSafeUrl(''));
        $this->assertFalse(SsrfGuard::isSafeUrl('not a url'));
        $this->assertFalse(SsrfGuard::isSafeUrl('ftp://example.com/file'));
        $this->assertFalse(SsrfGuard::isSafeUrl('file:///etc/passwd'));
        $this->assertFalse(SsrfGuard::isSafeUrl('gopher://127.0.0.1:6379/'));
    }

    public function test_blocks_localhost_and_loopback(): void
    {
        $this->assertFalse(SsrfGuard::isSafeUrl('http://localhost', allowTestDomains: false));
        $this->assertFalse(SsrfGuard::isSafeUrl('http://127.0.0.1/admin', allowTestDomains: false));
        $this->assertFalse(SsrfGuard::isSafeUrl('http://127.0.0.2:8080', allowTestDomains: false));
        $this->assertFalse(SsrfGuard::isSafeUrl('http://0.0.0.0', allowTestDomains: false));
    }

    public function test_blocks_cloud_metadata_ip(): void
    {
        $this->assertFalse(SsrfGuard::isSafeUrl('http://169.254.169.254/latest/meta-data/'));
        $this->assertFalse(SsrfGuard::isSafeIp('169.254.169.254'));
        $this->assertFalse(SsrfGuard::isSafeIp('169.254.1.1'));
    }

    public function test_blocks_private_ip_ranges(): void
    {
        // 10.0.0.0/8
        $this->assertFalse(SsrfGuard::isSafeIp('10.0.0.1'));
        $this->assertFalse(SsrfGuard::isSafeIp('10.254.0.1'));

        // 172.16.0.0/12
        $this->assertFalse(SsrfGuard::isSafeIp('172.16.0.1'));
        $this->assertFalse(SsrfGuard::isSafeIp('172.31.255.255'));

        // 192.168.0.0/16
        $this->assertFalse(SsrfGuard::isSafeIp('192.168.1.1'));
        $this->assertFalse(SsrfGuard::isSafeIp('192.168.0.100'));
    }

    public function test_allows_public_ips(): void
    {
        // Google Public DNS / Cloudflare DNS
        $this->assertTrue(SsrfGuard::isSafeIp('8.8.8.8'));
        $this->assertTrue(SsrfGuard::isSafeIp('1.1.1.1'));
        $this->assertTrue(SsrfGuard::isSafeIp('142.250.190.46'));
    }

    public function test_allows_valid_test_domains_in_testing_environment(): void
    {
        $this->assertTrue(SsrfGuard::isSafeUrl('https://beverlydental.example'));
        $this->assertTrue(SsrfGuard::isSafeUrl('https://clinic.example/about'));
        $this->assertTrue(SsrfGuard::isSafeUrl('https://example.com'));
        $this->assertTrue(SsrfGuard::isSafeUrl('https://example.org'));
    }
}

