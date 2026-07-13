<?php
declare(strict_types=1);

namespace VisionGaia\WPDesk;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Single coherent frame-embedding policy for desk portals.
 * Prevents DENY + SAMEORIGIN multi-header stacks on embed targets.
 *
 * Rule of ownership:
 *  - Exactly ONE X-Frame-Options value per response (never stack).
 *  - Admin + desk embeds → SAMEORIGIN
 *  - Public front → DENY
 *  - Apache/htaccess must NOT emit XFO (Titan generates without it; we scrub legacy).
 */
final class WPDeskFramePolicy
{
    public const ALLOW_SAMEORIGIN = 'SAMEORIGIN';
    public const DENY = 'DENY';
    public const HTACCESS_SCRUB_OPTION = 'vgt_xfo_htaccess_scrubbed_v2';

    /**
     * Pure decision: which X-Frame-Options value to emit (exactly one).
     *
     * @param bool $is_admin WordPress is_admin()
     * @param bool $is_desk_embed Request is desk iframe embed (vgt_iframe=true or Sec-Fetch-Dest iframe from admin)
     * @param bool $is_front_public Public frontend document
     */
    public static function x_frame_options_value(bool $is_admin, bool $is_desk_embed, bool $is_front_public = false): string
    {
        // Desk embeds and all admin screens used as portals: same-origin only.
        if ($is_admin || $is_desk_embed) {
            return self::ALLOW_SAMEORIGIN;
        }
        // Public front: do not allow arbitrary framing (single DENY — never stack with SAMEORIGIN).
        if ($is_front_public) {
            return self::DENY;
        }
        return self::ALLOW_SAMEORIGIN;
    }

    /**
     * Pure: whether a URL is a same-origin wp-admin path safe for desk portal iframes.
     */
    public static function is_admin_portal_url(string $url, string $admin_base = ''): bool
    {
        $url = trim($url);
        if ($url === '' || str_starts_with($url, 'about:')) {
            return false;
        }
        $path = $url;
        $host = '';
        if (str_contains($url, '://')) {
            $parts = parse_url($url);
            if (!is_array($parts)) {
                return false;
            }
            $path = ($parts['path'] ?? '') . (isset($parts['query']) ? '?' . $parts['query'] : '');
            $host = strtolower((string) ($parts['host'] ?? ''));
        }
        $path = strtolower($path);

        // Explicitly reject bare site root / front paths used as portal targets.
        if ($path === '/' || $path === '' || $path === '/index.php' || $path === 'index.php') {
            // Allow only when under wp-admin (handled below) — bare front is never a portal.
            if (!str_contains($path, 'wp-admin')) {
                return false;
            }
        }

        if (str_contains($path, '/wp-admin/') || str_starts_with(ltrim($path, '/'), 'wp-admin/')) {
            return true;
        }
        // Bare admin files (edit.php, plugins.php) without directory when already under admin base.
        if ($admin_base !== '' && str_starts_with(strtolower($url), strtolower($admin_base))) {
            return true;
        }
        if (preg_match('#(^|/)(edit|post|plugins|themes|upload|users|options-|tools|admin|media-new|nav-menus|comment|profile|update-core)\.php#', $path) === 1) {
            return true;
        }
        // Ignore unused host for pure path check (caller may validate origin).
        unset($host);
        return false;
    }

    /**
     * Pure: force a portal target into an admin URL using a known admin base.
     * Returns empty string if unresolvable.
     */
    public static function force_admin_portal_url(string $url, string $admin_base): string
    {
        $url = trim($url);
        $admin_base = rtrim(trim($admin_base), '/') . '/';
        if ($url === '' || str_starts_with($url, 'about:')) {
            return '';
        }
        if (self::is_admin_portal_url($url, $admin_base)) {
            // If absolute front-looking but still admin path, keep.
            if (str_contains($url, '://') && !str_contains(strtolower($url), '/wp-admin')) {
                // Relative admin file resolved against wrong base earlier — rebuild.
                $parts = parse_url($url);
                $path = is_array($parts) ? (string) ($parts['path'] ?? '') : '';
                $query = is_array($parts) && !empty($parts['query']) ? (string) $parts['query'] : '';
                $file = basename($path);
                if ($file !== '' && str_ends_with($file, '.php')) {
                    $rebuilt = $admin_base . $file;
                    if ($query !== '') {
                        $rebuilt .= '?' . $query;
                    }
                    return $rebuilt;
                }
            }
            return $url;
        }

        // Relative admin file: edit.php?post_type=page
        if (preg_match('#^([a-z0-9_\-]+\.php)(\?.*)?$#i', $url, $m) === 1) {
            return $admin_base . $m[1] . ($m[2] ?? '');
        }

        // Path without scheme but with wp-admin
        if (str_contains(strtolower($url), 'wp-admin')) {
            if (str_starts_with($url, '/')) {
                // Keep path; caller prefixes origin.
                return $url;
            }
        }

        return '';
    }

    /**
     * Pure: detect desk embed context from request-like flags.
     */
    public static function is_desk_embed_request(bool $vgt_iframe_param, bool $sec_fetch_iframe, bool $referer_has_vgt_iframe): bool
    {
        return $vgt_iframe_param || $sec_fetch_iframe || $referer_has_vgt_iframe;
    }

    /**
     * Install late header consolidation (once).
     */
    public static function boot(): void
    {
        // Late rewrite so Titan/Throne/host cannot leave multi-value stacks.
        add_filter('wp_headers', [self::class, 'filter_wp_headers'], 99999, 1);
        add_action('send_headers', [self::class, 'send_consolidated_headers'], 99999);
        add_action('admin_init', [self::class, 'send_consolidated_headers'], 99999);
        // Scrub legacy Apache multi-header sources once (Titan used Header set XFO).
        add_action('admin_init', [self::class, 'maybe_scrub_htaccess_xfo'], 5);
    }

    /**
     * @param array<string,string> $headers
     * @return array<string,string>
     */
    public static function filter_wp_headers(array $headers): array
    {
        $is_admin = function_exists('is_admin') && is_admin();
        $embed = self::detect_embed_from_globals();
        $front = !$is_admin && !$embed;
        $value = self::x_frame_options_value($is_admin, $embed, $front);
        // Single key only — overwrites any previous multi-value mess in this array.
        // Normalize case variants some plugins inject.
        foreach (array_keys($headers) as $k) {
            if (strtolower((string) $k) === 'x-frame-options') {
                unset($headers[$k]);
            }
        }
        $headers['X-Frame-Options'] = $value;
        return $headers;
    }

    public static function send_consolidated_headers(): void
    {
        if (headers_sent()) {
            return;
        }
        $is_admin = function_exists('is_admin') && is_admin();
        $embed = self::detect_embed_from_globals();
        $front = !$is_admin && !$embed;
        $value = self::x_frame_options_value($is_admin, $embed, $front);

        // Remove ALL prior PHP-emitted X-Frame-Options then set exactly one.
        // (Cannot remove Apache mod_headers values — those are scrubbed from .htaccess separately.)
        if (function_exists('header_remove')) {
            header_remove('X-Frame-Options');
            header_remove('x-frame-options');
        }
        header('X-Frame-Options: ' . $value, true);
    }

    /**
     * One-time scrub of Titan/legacy "Header set X-Frame-Options" lines that stack with PHP.
     */
    public static function maybe_scrub_htaccess_xfo(): void
    {
        if (function_exists('get_option') && get_option(self::HTACCESS_SCRUB_OPTION) === 'done') {
            return;
        }
        if (function_exists('current_user_can') && !current_user_can('manage_options')) {
            return;
        }
        if (!defined('ABSPATH')) {
            return;
        }

        $path = ABSPATH . '.htaccess';
        if (!is_readable($path) || !is_writable($path)) {
            if (function_exists('update_option')) {
                // Don't loop forever on unwritable hosts.
                update_option(self::HTACCESS_SCRUB_OPTION, 'done', false);
            }
            return;
        }

        $content = file_get_contents($path);
        if (!is_string($content) || $content === '') {
            if (function_exists('update_option')) {
                update_option(self::HTACCESS_SCRUB_OPTION, 'done', false);
            }
            return;
        }

        // Comment out any Header set/append/always for X-Frame-Options (case-insensitive).
        $new = preg_replace(
            '/^(\s*)Header\s+(always\s+)?(set|append|edit|merge)\s+X-Frame-Options\b.*$/mi',
            '$1# VGT FramePolicy: X-Frame-Options owned by PHP (removed multi-header stack)',
            $content
        );

        if (is_string($new) && $new !== $content) {
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
            file_put_contents($path, $new);
        }

        if (function_exists('update_option')) {
            update_option(self::HTACCESS_SCRUB_OPTION, 'done', false);
        }
    }

    private static function detect_embed_from_globals(): bool
    {
        $vgt = isset($_GET['vgt_iframe']) && (string) $_GET['vgt_iframe'] === 'true';
        $dest = isset($_SERVER['HTTP_SEC_FETCH_DEST']) && strtolower((string) $_SERVER['HTTP_SEC_FETCH_DEST']) === 'iframe';
        $ref = false;
        if (!empty($_SERVER['HTTP_REFERER'])) {
            $q = parse_url((string) $_SERVER['HTTP_REFERER'], PHP_URL_QUERY);
            $ref = is_string($q) && str_contains($q, 'vgt_iframe=true');
        }
        return self::is_desk_embed_request($vgt, $dest, $ref);
    }
}
