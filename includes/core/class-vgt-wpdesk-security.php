<?php
declare(strict_types=1);

namespace VisionGaia\WPDesk;

if (!defined('ABSPATH')) {
    exit;
}

final class WPDeskSecurity
{
    private const CF_IPV4 = [
        '173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22',
        '141.101.64.0/18', '108.162.192.0/18', '190.93.240.0/20', '188.114.96.0/20',
        '197.234.240.0/22', '198.41.128.0/17', '162.158.0.0/15', '104.16.0.0/13',
        '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22'
    ];

    public static function client_ip(): string
    {
        $remote = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash((string)$_SERVER['REMOTE_ADDR'])) : '0.0.0.0';
        $remote = filter_var($remote, FILTER_VALIDATE_IP) ? $remote : '0.0.0.0';

        if ($remote !== '0.0.0.0' && self::is_cloudflare_ipv4($remote) && !empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $cf = sanitize_text_field(wp_unslash((string)$_SERVER['HTTP_CF_CONNECTING_IP']));
            return filter_var($cf, FILTER_VALIDATE_IP) ? $cf : $remote;
        }

        return $remote;
    }

    public static function is_sentinel_v7_active(): bool
    {
        return defined('VIS_VERSION') || class_exists('VIS_Bootstrapper', false) || class_exists('VIS_Aegis', false);
    }

    public static function is_gedefense_active(): bool
    {
        return defined('VIS_VERSION') || class_exists('VIS_Bootstrapper', false);
    }

    public static function is_sentinel_ce_enabled(): bool
    {
        return get_option('vgt_sentinel_enabled') === 'true';
    }

    public static function sentinel_state(): array
    {
        $active = self::is_gedefense_active() || get_option('vgt_sentinel_enabled') === 'true';
        return [
            'v7_active' => self::is_gedefense_active(),
            'ce_enabled' => get_option('vgt_sentinel_enabled') === 'true',
            'active' => $active,
            'mode' => $active ? 'v8' : 'off',
        ];
    }

    /**
     * Throne Guard is active when master accounts exist or hardening is configured.
     */
    public static function throne_guard_active(): bool
    {
        if (class_exists('VIS_Throne_Guard')) {
            $status = \VIS_Throne_Guard::status();
            return !empty($status['is_master']) || !empty($status['superkey_set']) || !empty($status['harden_admin']);
        }
        return self::throne_guard_superkey_configured() || self::throne_guard_master_role_active();
    }

    public static function throne_guard_superkey_configured(?int $user_id = null): bool
    {
        if (class_exists('VIS_Throne_Guard')) {
            $status = \VIS_Throne_Guard::status();
            return !empty($status['superkey_set']);
        }
        $uid = $user_id ?? (function_exists('get_current_user_id') ? (int) get_current_user_id() : 0);
        $hashes = [(string) get_option('mcp_superkey_hash', '')];
        if ($uid > 0 && function_exists('get_user_meta')) {
            $hashes[] = (string) get_user_meta($uid, 'mcp_superkey_hash', true);
        }
        foreach ($hashes as $hash) {
            $hash = trim($hash);
            if ($hash !== '' && $hash !== '0' && $hash !== 'false') {
                return true;
            }
        }
        return false;
    }

    public static function throne_guard_master_role_active(): bool
    {
        if (function_exists('current_user_can') && (current_user_can('manage_options') || current_user_can('mcp_master_access'))) {
            return true;
        }
        if (function_exists('get_role')) {
            $master = get_role('master');
            if ($master !== null) {
                return true;
            }
        }
        return false;
    }

    public static function audit_control_action(string $action, array $context = []): void
    {
        if (class_exists(WPDeskAudit::class)) {
            WPDeskAudit::log('security_control', $action, $context);
        }
    }

    public static function is_safe_wallpaper_url(string $url): bool
    {
        $hosts = [];
        if (function_exists('home_url')) {
            $h = parse_url((string)home_url(), PHP_URL_HOST);
            if (is_string($h) && $h !== '') $hosts[] = $h;
        }
        if (function_exists('site_url')) {
            $s = parse_url((string)site_url(), PHP_URL_HOST);
            if (is_string($s) && $s !== '') $hosts[] = $s;
        }
        return self::is_safe_wallpaper_url_with_hosts($url, ...$hosts);
    }

    public static function is_safe_wallpaper_url_with_hosts(string $url, ?string ...$allowed_hosts): bool
    {
        $url = trim($url);
        if ($url === '' || str_starts_with($url, '//')) {
            return false;
        }
        if (str_starts_with($url, 'data:image/')) {
            return true;
        }
        if (str_starts_with($url, '/')) {
            return true;
        }
        $parts = parse_url($url);
        if ($parts === false || !isset($parts['scheme'])) {
            return false;
        }
        if (!in_array(strtolower((string)$parts['scheme']), ['http', 'https'], true)) {
            return false;
        }
        $host = isset($parts['host']) ? strtolower((string)$parts['host']) : '';
        if ($host === '') {
            return false;
        }
        foreach ($allowed_hosts as $ah) {
            if ($ah !== null && $host === strtolower((string)$ah)) {
                return true;
            }
        }
        return false;
    }


    public static function cpu_load_percent(): ?int
    {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            if (is_array($load) && isset($load[0])) {
                return (int) round($load[0] * 10);
            }
        }
        return null;
    }

    public static function optimizable_table_suffixes(): array
    {
        return [
            'options', 'postmeta', 'posts', 'commentmeta', 'comments',
            'termmeta', 'terms', 'term_taxonomy', 'term_relationships',
            'usermeta', 'users', 'vis_logs', 'vis_bans'
        ];
    }

    private static function is_cloudflare_ipv4(string $ip): bool
    {
        $long = ip2long($ip);
        if ($long === false) return false;
        foreach (self::CF_IPV4 as $cidr) {
            [$net, $mask] = explode('/', $cidr);
            $netLong = ip2long($net);
            $maskLong = ~((1 << (32 - (int)$mask)) - 1);
            if (($long & $maskLong) === ($netLong & $maskLong)) {
                return true;
            }
        }
        return false;
    }

    public static function table_exists(string $table_name): bool
    {
        global $wpdb;
        if (!isset($wpdb) || !is_object($wpdb)) {
            return false;
        }
        $escaped = esc_sql($table_name);
        $found = $wpdb->get_var("SHOW TABLES LIKE '{$escaped}'");
        return !empty($found);
    }

    public static function normalize_ip(string $ip): string
    {
        $ip = trim($ip);
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $ip;
        }
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return strtolower((string) inet_ntop((string) inet_pton($ip)));
        }
        return '0.0.0.0';
    }

    public static function quote_identifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', preg_replace('/[^a-zA-Z0-9_]/', '', $identifier)) . '`';
    }

    public static function require_operational_control(string $capability = 'manage_options'): void
    {
        if (!function_exists('current_user_can') || !current_user_can($capability)) {
            throw new SecurityException('Insufficient privileges for operator control.');
        }
    }

}
