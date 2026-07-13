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

    /**
     * Throne Guard is "active" when a Superkey is configured (global and/or per-user)
     * or the Master role is in use — not merely because the PHP class is loaded.
     */
    public static function throne_guard_active(): bool
    {
        return self::throne_guard_superkey_configured()
            || self::throne_guard_master_role_active();
    }

    /**
     * Superkey present in option and/or current user meta (Argon2/bcrypt hashes).
     */
    public static function throne_guard_superkey_configured(?int $user_id = null): bool
    {
        $uid = $user_id ?? (function_exists('get_current_user_id') ? (int) get_current_user_id() : 0);
        $hashes = [
            (string) get_option('mcp_superkey_hash', ''),
        ];
        if ($uid > 0 && function_exists('get_user_meta')) {
            $hashes[] = (string) get_user_meta($uid, 'mcp_superkey_hash', true);
        }
        foreach ($hashes as $hash) {
            $hash = trim($hash);
            // password_hash strings are non-empty and typically start with $
            if ($hash !== '' && $hash !== '0' && $hash !== 'false') {
                return true;
            }
        }
        return false;
    }

    public static function throne_guard_master_role_active(): bool
    {
        if (function_exists('current_user_can') && current_user_can('mcp_master_access')) {
            return true;
        }
        if (function_exists('get_role')) {
            $master = get_role('master_user');
            if ($master !== null && !empty($master->capabilities['mcp_master_access'])) {
                return true;
            }
        }
        return false;
    }

    /** Module PHP class loaded (not the same as "superkey configured"). */
    public static function throne_guard_module_loaded(): bool
    {
        return class_exists('VisionGaia\\ThroneGuard\\MasterUserControlPlugin', false);
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
        // Single operational audit trail (validated + capped).
        if (class_exists(WPDeskAudit::class, false) || class_exists(__NAMESPACE__ . '\\WPDeskAudit')) {
            WPDeskAudit::record($action, $context);
            return;
        }
        // Fallback if audit class not loaded yet during early bootstrap.
        $entry = [
            'timestamp' => function_exists('current_time') ? current_time('mysql') : gmdate('Y-m-d H:i:s'),
            'user_id' => function_exists('get_current_user_id') ? get_current_user_id() : 0,
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

    /**
     * Pure wallpaper safety check (injectable hosts for unit tests).
     *
     * Rules:
     * - data:image/*;base64,... allowed
     * - Absolute / protocol-relative URLs (non-empty host): host must equal site or upload host
     * - Relative paths only: single leading slash (not //) or scheme-less relative path
     * - Never accept a foreign host because the path contains /wp-content/uploads/
     */
    public static function is_safe_wallpaper_url_with_hosts(
        string $value,
        ?string $site_host,
        ?string $upload_host
    ): bool {
        if ($value === '') {
            return true;
        }

        if (preg_match('#^data:image/(png|jpe?g|webp|gif);base64,[A-Za-z0-9+/=]+$#i', $value) === 1) {
            return true;
        }

        if (str_starts_with($value, 'data:')) {
            return false;
        }

        // Protocol-relative //evil.example/... always has a host in parse_url.
        // Absolute URLs with a host must match allowlisted hosts only — path substrings never override.
        $value_host = parse_url($value, PHP_URL_HOST);
        if (is_string($value_host) && $value_host !== '') {
            $value_host = strtolower($value_host);
            $site = $site_host !== null ? strtolower($site_host) : null;
            $upload = $upload_host !== null ? strtolower($upload_host) : null;
            return ($site !== null && $value_host === $site)
                || ($upload !== null && $value_host === $upload);
        }

        // Host-less: reject any leftover protocol-relative form and foreign schemes.
        if (str_starts_with($value, '//')) {
            return false;
        }
        if (preg_match('#^[a-z][a-z0-9+.-]*:#i', $value) === 1) {
            return false;
        }

        // True relative paths: "/media/..." or "wallpapers/wall1.webp"
        return str_starts_with($value, '/') || !str_contains($value, '://');
    }

    public static function is_safe_wallpaper_url(string $value): bool
    {
        $site_host = null;
        $upload_host = null;
        if (function_exists('site_url')) {
            $site_host = parse_url(site_url(), PHP_URL_HOST);
            $site_host = is_string($site_host) ? $site_host : null;
        }
        if (function_exists('wp_upload_dir')) {
            $upload_dir = wp_upload_dir();
            $upload_host = parse_url($upload_dir['baseurl'] ?? '', PHP_URL_HOST);
            $upload_host = is_string($upload_host) ? $upload_host : null;
        }

        return self::is_safe_wallpaper_url_with_hosts($value, $site_host, $upload_host);
    }

    /**
     * Honest CPU metric: real loadavg-derived percentage, or null when unavailable.
     * Never invents values with sin/rand.
     */
    public static function cpu_load_percent(): ?int
    {
        if (!function_exists('sys_getloadavg')) {
            return null;
        }
        $load = sys_getloadavg();
        if (!is_array($load) || !isset($load[0]) || !is_numeric($load[0])) {
            return null;
        }
        $pct = (int) round(((float) $load[0]) * 100 / 2);
        return min(100, max(0, $pct));
    }

    /**
     * VGT-owned tables eligible for OPTIMIZE (prefix-relative suffixes).
     *
     * @return list<string>
     */
    public static function optimizable_table_suffixes(): array
    {
        return [
            'vgt_desk_settings',
            'vgts_apex_bans',
            'vgts_omega_logs',
            'vis_apex_bans',
            'vis_omega_logs',
            'mcp_user_roles',
            'vgt_dattrack_stats',
            'vgt_omega_forms',
            'vgt_omega_submissions',
        ];
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