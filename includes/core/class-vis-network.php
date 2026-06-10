<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * CORE: NETWORK UTILITIES
 * Status: PLATIN STATUS (WP.ORG COMPLIANT)
 * Zentralisierte O(1) IP-Resolution und CIDR-Validation.
 */
class VGTS_Network {
    
    /**
     * KERNEL-CACHE FÜR CLOUDFLARE IPv4 CIDR (Hardcoded für O(1) Lookup Speed)
     * @var array
     */
    private static array $cf_ipv4 = [
        '173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22',
        '141.101.64.0/18', '108.162.192.0/18', '190.93.240.0/20', '188.114.96.0/20',
        '197.234.240.0/22', '198.41.128.0/17', '162.158.0.0/15', '104.16.0.0/13',
        '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22'
    ];

    /**
     * Ermittelt die echte IP-Adresse und verifiziert Cloudflare-Header mathematisch.
     * * @return string Validierte IP-Adresse oder '0.0.0.0'
     */
    public static function resolve_true_ip(): string {
        // [WP.ORG COMPLIANCE]: Strict Sanitization for Server Variables
        $remote_addr = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '0.0.0.0';
        
        if (!isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return (filter_var($remote_addr, FILTER_VALIDATE_IP) !== false) ? $remote_addr : '0.0.0.0';
        }

        // Falls der CF-Header existiert, prüfen wir, ob der Request tatsächlich von Cloudflare stammt
        if (self::is_cloudflare_ip($remote_addr)) {
            $cf_ip = sanitize_text_field(wp_unslash($_SERVER['HTTP_CF_CONNECTING_IP']));
            return (filter_var($cf_ip, FILTER_VALIDATE_IP) !== false) ? $cf_ip : $remote_addr;
        }

        // Spoofing-Versuch: Fallback auf physische Remote-IP, falls Remote-IP nicht in der CF-Whitelist ist
        return (filter_var($remote_addr, FILTER_VALIDATE_IP) !== false) ? $remote_addr : '0.0.0.0';
    }

    /**
     * Verifiziert, ob eine IP zum Cloudflare-Netzwerk gehört via Bitwise CIDR Check.
     * * @param string $ip Zu prüfende IP-Adresse
     * @return bool
     */
    public static function is_cloudflare_ip(string $ip): bool {
        // CE operiert primär auf IPv4 Vektoren für CIDR Matching
        if (strpos($ip, ':') !== false) {
            return false; 
        }

        $ip_long = ip2long($ip);
        if ($ip_long === false) {
            return false;
        }

        foreach (self::$cf_ipv4 as $cidr) {
            [$subnet, $bits] = explode('/', $cidr);
            $subnet_long = ip2long($subnet);
            
            if ($subnet_long === false) {
                continue;
            }

            $mask = -1 << (32 - (int)$bits);
            
            if (($ip_long & $mask) === ($subnet_long & $mask)) {
                return true;
            }
        }
        return false;
    }
}