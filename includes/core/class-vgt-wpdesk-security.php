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

    public static function is_sentinel_ce_enabled(): bool
    {
        return get_option('vgt_sentinel_enabled') === 'true' && !self::is_sentinel_v7_active();
    }

    public static function sentinel_state(): array
    {
        $v7 = self::is_sentinel_v7_active();
        $ce = !$v7 && get_option('vgt_sentinel_enabled') === 'true';

        if ($v7 && get_option('vgt_sentinel_enabled') === 'true') {
            update_option('vgt_sentinel_enabled', 'false', false);
            $ce = false;
        }

        return [
            'v7_active' => $v7,
            'ce_enabled' => $ce,
            'active' => $v7 || $ce,
            'mode' => $v7 ? 'v7' : ($ce ? 'ce' : 'off'),
        ];
    }

    public static function throne_guard_active(): bool
    {
        if (class_exists('VisionGaia\\ThroneGuard\\MasterUserControlPlugin', false)) {
            $plugin = \VisionGaia\ThroneGuard\MasterUserControlPlugin::get_instance();
            if ($plugin !== null) {
                return true;
            }
        }

        $hashes = [
            (string)get_option('mcp_superkey_hash', ''),
            (string)get_user_meta(get_current_user_id(), 'mcp_superkey_hash', true),
        ];

        foreach ($hashes as $hash) {
            if ($hash !== '') {
                return true;
            }
        }

        $master = get_role('master_user');
        if ($master !== null && !empty($master->capabilities['mcp_master_access'])) {
            return true;
        }

        return current_user_can('mcp_master_access');
    }

    public static function table_exists(string $table): bool
    {
        global $wpdb;
        $like = $wpdb->esc_like($table);
        return $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $like)) === $table;
    }

    public static function quote_identifier(string $identifier): string
    {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $identifier)) {
            throw new SecurityException('SQL identifier validation failed.');
        }

        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    public static function require_operational_control(string $action): void
    {
        if (current_user_can('mcp_master_access')) {
            return;
        }

        if (!self::throne_guard_active() && current_user_can('manage_options')) {
            return;
        }

        throw new SecurityException('Operational control gate rejected action token.');
    }

    public static function audit_control_action(string $action, array $context = []): void
    {
        $entry = [
            'timestamp' => current_time('mysql'),
            'user_id' => get_current_user_id(),
            'action' => sanitize_key($action),
            'ip' => self::client_ip(),
            'context' => array_map(static fn($value) => is_scalar($value) ? sanitize_text_field((string)$value) : '[structured]', $context),
        ];

        $log = get_option('vgt_operational_audit_log', []);
        if (!is_array($log)) {
            $log = [];
        }
        $log[] = $entry;
        $log = array_slice($log, -200);
        update_option('vgt_operational_audit_log', $log, false);
        error_log('[VGT OP-AUDIT] ' . wp_json_encode($entry, JSON_UNESCAPED_SLASHES));
    }

    public static function normalize_ip(string $ip): string
    {
        $ip = trim(sanitize_text_field(wp_unslash($ip)));
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new ValidationException('Ungueltige IP-Adresse.');
        }

        return $ip;
    }

    public static function is_safe_wallpaper_url(string $value): bool
    {
        if ($value === '') {
            return true;
        }

        if (preg_match('#^data:image/(png|jpe?g|webp|gif);base64,[A-Za-z0-9+/=]+$#i', $value) === 1) {
            return true;
        }

        if (str_starts_with($value, 'data:')) {
            return false;
        }

        $site_host = parse_url(site_url(), PHP_URL_HOST);
        $value_host = parse_url($value, PHP_URL_HOST);
        $upload_dir = wp_upload_dir();
        $upload_host = parse_url($upload_dir['baseurl'] ?? '', PHP_URL_HOST);

        return empty($value_host)
            || $value_host === $site_host
            || $value_host === $upload_host
            || str_starts_with($value, '/')
            || str_contains($value, '/wp-content/uploads/');
    }

    public static function csp_policy(string $surface, string $nonce = ''): string
    {
        $mode = sanitize_key((string)get_option('vgt_csp_mode', 'compatibility'));
        $mode = in_array($mode, ['compatibility', 'hardened', 'strict', 'report_only'], true) ? $mode : 'compatibility';
        $nonceSource = $nonce !== '' ? " 'nonce-" . $nonce . "'" : '';
        $isAdmin = $surface === 'admin';

        if ($mode === 'strict') {
            return "default-src 'self'; script-src 'self'" . $nonceSource . "; style-src 'self'" . $nonceSource . "; img-src 'self' data:; font-src 'self' data:; frame-ancestors 'self'; object-src 'none'; base-uri 'self'; form-action 'self';";
        }

        if ($mode === 'hardened') {
            return $isAdmin
                ? "default-src 'self' data: https:; script-src 'self' 'unsafe-inline'" . $nonceSource . "; style-src 'self' 'unsafe-inline'" . $nonceSource . "; img-src 'self' data: https: blob:; font-src 'self' data:; frame-ancestors 'self'; object-src 'none'; base-uri 'self';"
                : "default-src 'self' data: https:; script-src 'self' 'unsafe-inline'" . $nonceSource . "; style-src 'self' 'unsafe-inline' https:; img-src 'self' data: https: blob:; font-src 'self' data: https:; frame-ancestors 'self'; object-src 'none'; base-uri 'self';";
        }

        return $isAdmin
            ? "default-src 'self' data: https:; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; object-src 'none'; base-uri 'self';"
            : "default-src 'self' data: https: blob:; script-src 'self' 'unsafe-inline' 'unsafe-eval' https: blob:; style-src 'self' 'unsafe-inline' https:; img-src 'self' data: https: blob:; font-src 'self' data: https:; frame-ancestors 'self'; object-src 'none'; base-uri 'self';";
    }

    public static function jailed_path(string $base_dir, string $relative): string
    {
        $base = realpath($base_dir);
        if ($base === false || !is_dir($base)) {
            throw new SecurityException('path jail base unavailable');
        }

        $relative = str_replace(['\\', "\0"], ['/', ''], $relative);
        $relative = ltrim($relative, '/');
        if ($relative === '' || str_contains($relative, '../') || str_contains($relative, '..\\') || preg_match('/^[a-zA-Z]:/', $relative)) {
            throw new SecurityException('path traversal attempt rejected');
        }

        $target = $base . DIRECTORY_SEPARATOR . $relative;
        $normalizedBase = wp_normalize_path($base) . '/';
        $normalizedTarget = wp_normalize_path($target);
        if (!str_starts_with($normalizedTarget, $normalizedBase)) {
            throw new SecurityException('path escaped jail');
        }

        return $target;
    }

    private static function is_cloudflare_ipv4(string $ip): bool
    {
        if (strpos($ip, ':') !== false) {
            return false;
        }

        $ipLong = ip2long($ip);
        if ($ipLong === false) {
            return false;
        }

        foreach (self::CF_IPV4 as $cidr) {
            [$subnet, $bits] = explode('/', $cidr);
            $subnetLong = ip2long($subnet);
            if ($subnetLong === false) {
                continue;
            }
            $mask = -1 << (32 - (int)$bits);
            if (($ipLong & $mask) === ($subnetLong & $mask)) {
                return true;
            }
        }

        return false;
    }
}