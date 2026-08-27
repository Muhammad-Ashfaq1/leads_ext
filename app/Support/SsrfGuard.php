<?php

namespace App\Support;

class SsrfGuard
{
    /**
     * Forbidden IP ranges and hosts (CIDR / direct values).
     */
    private const BLOCKED_HOSTS = [
        'localhost',
        '127.0.0.1',
        '::1',
        '0.0.0.0',
        '169.254.169.254',
        'instance-data',
        'metadata.google.internal',
    ];

    /**
     * Determine if a given URL is safe to fetch via outbound HTTP requests.
     *
     * @param  string  $url
     * @param  bool  $allowTestDomains
     * @return bool
     */
    public static function isSafeUrl(string $url, bool $allowTestDomains = true): bool
    {
        if (empty($url)) {
            return false;
        }

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if (! in_array($scheme, ['http', 'https'], true)) {
            return false;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if (empty($host)) {
            return false;
        }

        return self::isSafeHost($host, $allowTestDomains);
    }

    /**
     * Determine if a hostname or IP address is safe (public, non-internal, non-cloud-metadata).
     *
     * @param  string  $host
     * @param  bool  $allowTestDomains
     * @return bool
     */
    public static function isSafeHost(string $host, bool $allowTestDomains = true): bool
    {
        $cleanHost = strtolower(trim($host));

        if (in_array($cleanHost, self::BLOCKED_HOSTS, true)) {
            return false;
        }

        // Allow test domains in testing environment
        if ($allowTestDomains && app()->environment('testing')) {
            if (str_ends_with($cleanHost, '.example') || $cleanHost === 'example.com' || $cleanHost === 'example.org' || $cleanHost === 'test.com') {
                return true;
            }
        }

        // Check if host is already a direct IP address
        if (filter_var($cleanHost, FILTER_VALIDATE_IP)) {
            return self::isSafeIp($cleanHost);
        }

        // Resolve DNS records for the host
        $ips = @gethostbynamel($cleanHost);
        if ($ips === false || empty($ips)) {
            return false;
        }

        foreach ($ips as $ip) {
            if (! self::isSafeIp($ip)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine if an IP address is a safe public IP (not private, loopback, or reserved).
     *
     * @param  string  $ip
     * @return bool
     */
    public static function isSafeIp(string $ip): bool
    {
        // Must be a valid IP
        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }

        // Block private and reserved IPv4 / IPv6 ranges
        $isValidPublic = (bool) filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );

        if (! $isValidPublic) {
            return false;
        }

        // Explicitly block Cloud Metadata & Link-Local (169.254.0.0/16) and localhost/zero
        if (str_starts_with($ip, '169.254.') || str_starts_with($ip, '127.') || str_starts_with($ip, '0.')) {
            return false;
        }

        return true;
    }
}

