<?php
declare(strict_types=1);

namespace VisionGaia\WPDesk;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Pure iframe vs classic-mode classification for desk apps.
 */
final class WPDeskIframePolicy
{
    public const MODE_IFRAME_OK = 'iframe-ok';
    public const MODE_CLASSIC_REQUIRED = 'classic-required';

    /**
     * Deterministic URL/path fragments that break or escape iframes.
     *
     * @return list<string>
     */
    public static function classic_path_markers(): array
    {
        // Intentionally does NOT include generic list screens (edit.php, plugins.php, themes.php).
        return [
            'customize.php',
            'theme-editor.php',
            'plugin-editor.php',
            'site-editor.php',
            'widgets.php',
            'nav-menus.php',
            'update-core.php',
            'update.php',
            'setup-config.php',
            'action=elementor',
            'elementor-app',
            'post.php?action=elementor',
            'brizy-edit',
            'ct_builder=true',
            'fl_builder',
            'oxygen_iframe',
            'vcv-action',
            'et_fb=1',
            'gutenberg-mobile',
        ];
    }

    /**
     * Classify an admin URL and optional app id.
     *
     * @param array<string,bool> $classic_overrides Map of app_id => true to force classic
     */
    public static function classify(string $url_or_path, string $app_id = '', array $classic_overrides = []): string
    {
        $app_id = self::pure_key($app_id);
        if ($app_id !== '' && !empty($classic_overrides[$app_id])) {
            return self::MODE_CLASSIC_REQUIRED;
        }

        $haystack = strtolower(trim($url_or_path));
        if ($haystack === '') {
            return self::MODE_IFRAME_OK;
        }

        // Strip host if present for marker matching.
        $path = $haystack;
        if (str_contains($path, '://')) {
            $parts = parse_url($path);
            $path = strtolower(($parts['path'] ?? '') . '?' . ($parts['query'] ?? ''));
        }

        foreach (self::classic_path_markers() as $marker) {
            if (str_contains($path, strtolower($marker)) || str_contains($haystack, strtolower($marker))) {
                return self::MODE_CLASSIC_REQUIRED;
            }
        }

        if ($app_id !== '') {
            foreach (self::classic_path_markers() as $marker) {
                $m = strtolower(str_replace(['.php', '='], '', $marker));
                if ($m !== '' && str_contains($app_id, $m)) {
                    return self::MODE_CLASSIC_REQUIRED;
                }
            }
        }

        return self::MODE_IFRAME_OK;
    }

    /**
     * Whether the open path should skip iframe embedding.
     *
     * @param array<string,bool> $classic_overrides
     */
    public static function should_open_classic(string $url_or_path, string $app_id = '', array $classic_overrides = []): bool
    {
        return self::classify($url_or_path, $app_id, $classic_overrides) === self::MODE_CLASSIC_REQUIRED;
    }

    /**
     * Normalize classic_apps setting payload (app_id => true only).
     *
     * @param mixed $raw
     * @return array<string,bool>
     */
    public static function normalize_classic_apps($raw): array
    {
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $key => $val) {
            $id = self::pure_key((string) $key);
            if ($id === '') {
                continue;
            }
            if ($val === true || $val === 1 || $val === '1' || $val === 'true') {
                $out[$id] = true;
            }
        }
        return $out;
    }

    public static function pure_key(string $key): string
    {
        if (function_exists('sanitize_key')) {
            return sanitize_key($key);
        }
        $key = strtolower($key);
        return (string) preg_replace('/[^a-z0-9_\-]/', '', $key);
    }
}
