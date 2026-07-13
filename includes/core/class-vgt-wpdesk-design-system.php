<?php
declare(strict_types=1);

namespace VisionGaia\WPDesk;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Unified VGT Design System enqueue + body hooks.
 * One cast for Security Center, Sentinel, product modules, recovery, throne.
 */
final class WPDeskDesignSystem
{
    public const HANDLE_TOKENS = 'vgt-ds-tokens';
    public const HANDLE_BASE = 'vgt-ds-base';
    public const HANDLE_COMPONENTS = 'vgt-ds-components';
    public const HANDLE_COMPAT = 'vgt-ds-compat';

    /** @var bool */
    private static bool $registered = false;

    /** @var bool */
    private static bool $enqueued = false;

    /**
     * Relative paths under VGT_WPDESK_PATH / URL.
     *
     * @return list<array{handle:string,path:string,deps:list<string>}>
     */
    public static function asset_map(): array
    {
        return [
            [
                'handle' => self::HANDLE_TOKENS,
                'path'   => 'assets/css/design-system/vgt-ds-tokens.css',
                'deps'   => [],
            ],
            [
                'handle' => self::HANDLE_BASE,
                'path'   => 'assets/css/design-system/vgt-ds-base.css',
                'deps'   => [self::HANDLE_TOKENS],
            ],
            [
                'handle' => self::HANDLE_COMPONENTS,
                'path'   => 'assets/css/design-system/vgt-ds-components.css',
                'deps'   => [self::HANDLE_BASE],
            ],
            [
                'handle' => self::HANDLE_COMPAT,
                'path'   => 'assets/css/design-system/vgt-ds-compat.css',
                'deps'   => [self::HANDLE_COMPONENTS],
            ],
        ];
    }

    public static function register(): void
    {
        if (self::$registered) {
            return;
        }
        if (!defined('VGT_WPDESK_URL') || !defined('VGT_WPDESK_PATH')) {
            return;
        }

        foreach (self::asset_map() as $asset) {
            $abs = VGT_WPDESK_PATH . $asset['path'];
            if (!is_readable($abs)) {
                continue;
            }
            $ver = (string) (@filemtime($abs) ?: '2.0.0-ds');
            wp_register_style(
                $asset['handle'],
                VGT_WPDESK_URL . $asset['path'],
                $asset['deps'],
                $ver
            );
        }

        self::$registered = true;
    }

    /**
     * Enqueue full design system (idempotent per request).
     *
     * @param string $surface Logical surface key for future filtering (admin|desk|portal|…)
     */
    public static function enqueue(string $surface = 'admin'): void
    {
        self::register();
        if (self::$enqueued) {
            return;
        }

        foreach (self::asset_map() as $asset) {
            if (wp_style_is($asset['handle'], 'registered') || wp_style_is($asset['handle'], 'enqueued')) {
                wp_enqueue_style($asset['handle']);
            }
        }

        // Ensure dashicons available for nav icons across modules.
        wp_enqueue_style('dashicons');

        self::$enqueued = true;

        // Body class for CSS hooks (admin only).
        if (!has_filter('admin_body_class', [self::class, 'filter_admin_body_class'])) {
            add_filter('admin_body_class', [self::class, 'filter_admin_body_class']);
        }

        unset($surface); // reserved for surface-specific deltas later
    }

    /**
     * @param string $classes
     */
    public static function filter_admin_body_class($classes): string
    {
        $classes = is_string($classes) ? $classes : '';
        if (!str_contains($classes, 'vgt-ds-active')) {
            $classes .= ' vgt-ds-active';
        }
        return $classes;
    }

    /**
     * Resolve tab CSS relative path (fixes mudeployer → mu-deployer).
     */
    public static function sentinel_tab_css_rel(string $tab): string
    {
        $tab = preg_replace('/[^a-z0-9_\-]/', '', strtolower($tab)) ?? '';
        if ($tab === '') {
            return '';
        }
        $aliases = [
            'mudeployer' => 'mu-deployer',
            'mu_deployer' => 'mu-deployer',
        ];
        $fileTab = $aliases[$tab] ?? $tab;
        return 'assets/css/vgts-' . $fileTab . '.css';
    }

    /**
     * Pure: list of DS relative paths (for tests / evidence).
     *
     * @return list<string>
     */
    public static function expected_paths(): array
    {
        $out = [];
        foreach (self::asset_map() as $asset) {
            $out[] = $asset['path'];
        }
        return $out;
    }
}
