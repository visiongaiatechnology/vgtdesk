<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * CORE: NETWORK UTILITIES
 * Status: PLATIN STATUS (WP.ORG COMPLIANT)
 * Zentralisierte IP-Resolution mit Cloudflare IPv4/IPv6 und optionalen Trusted Proxy CIDRs.
 */
class VGTS_Network {

    private static array $cf_ipv4 = [
        '173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22',
        '141.101.64.0/18', '108.162.192.0/18', '190.93.240.0/20', '188.114.96.0/20',
        '197.234.240.0/22', '198.41.128.0/17', '162.158.0.0/15', '104.16.0.0/13',
        '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22',
    ];

    private static array $cf_ipv6 = [
        '2400:cb00::/32', '2606:4700::/32', '2803:f800::/32', '2405:b500::/32',
        '2405:8100::/32', '2a06:98c0::/29', '2c0f:f248::/32',
    ];

    public static function resolve_true_ip(): string {
        $remote = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash((string)$_SERVER['REMOTE_ADDR'])) : '0.0.0.0';
        $remote = filter_var($remote, FILTER_VALIDATE_IP) ? $remote : '0.0.0.0';

        if (self::is_trusted_proxy($remote)) {
            $candidate = self::first_valid_forwarded_ip();
            if ($candidate !== '') {
                return $candidate;
            }
        }

        return $remote;
    }

    public static function is_cloudflare_ip(string $ip): bool {
        $ranges = strpos($ip, ':') !== false ? self::$cf_ipv6 : self::$cf_ipv4;
        foreach ($ranges as $cidr) {
            if (self::cidr_match($ip, $cidr)) {
                return true;
            }
        }
        return false;
    }

    private static function is_trusted_proxy(string $ip): bool {
        if ($ip === '0.0.0.0') {
            return false;
        }
        if (self::is_cloudflare_ip($ip)) {
            return true;
        }

        $raw = (string)get_option('vgts_trusted_proxy_cidrs', '');
        foreach (preg_split('/\r\n|\r|\n/', $raw) ?: [] as $cidr) {
            $cidr = trim($cidr);
            if ($cidr !== '' && self::cidr_match($ip, $cidr)) {
                return true;
            }
        }
        return false;
    }

    private static function first_valid_forwarded_ip(): string {
        $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR'];
        foreach ($headers as $header) {
            if (empty($_SERVER[$header])) {
                continue;
            }
            $parts = explode(',', sanitize_text_field(wp_unslash((string)$_SERVER[$header])));
            $candidate = trim((string)($parts[0] ?? ''));
            if (filter_var($candidate, FILTER_VALIDATE_IP)) {
                return $candidate;
            }
        }
        return '';
    }

    private static function cidr_match(string $ip, string $cidr): bool {
        if (strpos($cidr, '/') === false) {
            return hash_equals($ip, $cidr);
        }

        [$subnet, $bits] = explode('/', $cidr, 2);
        $ip_bin = @inet_pton($ip);
        $subnet_bin = @inet_pton($subnet);
        if ($ip_bin === false || $subnet_bin === false || strlen($ip_bin) !== strlen($subnet_bin)) {
            return false;
        }

        $bits = (int)$bits;
        $bytes = intdiv($bits, 8);
        $remainder = $bits % 8;

        if ($bytes > 0 && substr($ip_bin, 0, $bytes) !== substr($subnet_bin, 0, $bytes)) {
            return false;
        }

        if ($remainder === 0) {
            return true;
        }

        $mask = (0xff << (8 - $remainder)) & 0xff;
        return (ord($ip_bin[$bytes]) & $mask) === (ord($subnet_bin[$bytes]) & $mask);
    }
}

