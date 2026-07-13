<?php
/**
 * Minimal bootstrap for pure unit tests (no WordPress).
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . DIRECTORY_SEPARATOR);
}
if (!defined('VGT_WPDESK_PATH')) {
    define('VGT_WPDESK_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
}

if (!function_exists('sanitize_key')) {
    function sanitize_key($key): string
    {
        $key = strtolower((string) $key);
        return (string) preg_replace('/[^a-z0-9_\-]/', '', $key);
    }
}
if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str): string
    {
        return trim(strip_tags((string) $str));
    }
}
if (!function_exists('wp_unslash')) {
    function wp_unslash($value)
    {
        return is_string($value) ? stripslashes($value) : $value;
    }
}
if (!function_exists('esc_url_raw')) {
    function esc_url_raw($url): string
    {
        $url = (string) $url;
        if (str_starts_with($url, 'data:image/')) {
            return $url;
        }
        return filter_var($url, FILTER_SANITIZE_URL) ?: $url;
    }
}

// Exception hierarchy matching desktop.php
namespace VisionGaia\WPDesk {
    if (!class_exists(__NAMESPACE__ . '\\WPDeskException', false)) {
        class WPDeskException extends \Exception {}
        class ValidationException extends WPDeskException {}
        class SecurityException extends WPDeskException {}
        class StorageException extends WPDeskException {}
    }
}

namespace {
    $root = dirname(__DIR__);
    require_once $root . '/includes/core/class-vgt-wpdesk-security.php';
    require_once $root . '/includes/core/class-vgt-wpdesk-settings.php';
    require_once $root . '/includes/core/class-vgt-wpdesk-ban-store.php';
    require_once $root . '/includes/core/class-vgt-wpdesk-ajax-guard.php';
    require_once $root . '/includes/core/class-vgt-wpdesk-module-registry.php';
    require_once $root . '/includes/core/class-vgt-wpdesk-recovery.php';
    require_once $root . '/includes/core/class-vgt-wpdesk-iframe-policy.php';
    require_once $root . '/includes/core/class-vgt-wpdesk-audit.php';
}
