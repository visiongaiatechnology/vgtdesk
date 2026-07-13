<?php
/**
 * Module: VGT Iframe Transformer
 * Description: Portal CSS + iframe query param preservation for admin embeds.
 * Version: 2.0.0
 * Author: VisionGaiaTechnology
 */

declare(strict_types=1);

namespace VisionGaia\WPDesk;

if (!defined('ABSPATH')) {
    exit;
}

final class IframeTransformer
{
    private static ?self $instance = null;

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_portal_assets'], 5);
        add_action('admin_head', [$this, 'inject_transform_styles'], 999);
        add_filter('admin_body_class', [$this, 'filter_admin_body_class'], 50);

        // Keep vgt_iframe across admin URLs / redirects.
        add_filter('admin_url', [$this, 'append_iframe_param_to_admin_urls'], 999, 3);
        add_filter('wp_redirect', [$this, 'append_iframe_param_to_redirect_urls'], 999, 1);
    }

    private function is_iframe_context(): bool
    {
        if (isset($_GET['vgt_iframe']) && $_GET['vgt_iframe'] === 'true') {
            return true;
        }

        if (isset($_SERVER['HTTP_SEC_FETCH_DEST']) && $_SERVER['HTTP_SEC_FETCH_DEST'] === 'iframe') {
            return true;
        }

        if (isset($_SERVER['HTTP_REFERER'])) {
            $referer_query = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_QUERY);
            if ($referer_query && str_contains($referer_query, 'vgt_iframe=true')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Enqueue versioned portal CSS so browsers always pick up redesigns.
     */
    public function enqueue_portal_assets(): void
    {
        if (!$this->is_iframe_context()) {
            return;
        }

        $path = defined('VGT_WPDESK_PATH')
            ? VGT_WPDESK_PATH . 'assets/css/vgt-portal-screens.css'
            : '';
        $url = defined('VGT_WPDESK_URL')
            ? VGT_WPDESK_URL . 'assets/css/vgt-portal-screens.css'
            : '';
        if ($path === '' || $url === '' || !is_readable($path)) {
            return;
        }

        if (class_exists(WPDeskDesignSystem::class)) {
            WPDeskDesignSystem::enqueue('portal');
        }

        $ver = (string) (@filemtime($path) ?: '2.0.0');
        wp_enqueue_style('vgt-portal-screens', $url, ['vgt-ds-compat'], $ver);
    }

    /**
     * @param string $classes
     */
    public function filter_admin_body_class($classes): string
    {
        $classes = is_string($classes) ? $classes : '';
        if (!$this->is_iframe_context()) {
            return $classes;
        }
        if (!str_contains($classes, 'vgt-portal-v2')) {
            $classes .= ' vgt-portal-v2 vgt-iframe-portal';
        }
        return $classes;
    }

    public function append_iframe_param_to_admin_urls(string $url, string $path, ?int $blog_id = null): string
    {
        if (!$this->is_iframe_context()) {
            return $url;
        }

        if (str_contains($path, 'admin-ajax.php') || str_contains($path, 'async-upload.php')) {
            return $url;
        }

        return add_query_arg('vgt_iframe', 'true', $url);
    }

    public function append_iframe_param_to_redirect_urls(string $location): string
    {
        if (!$this->is_iframe_context()) {
            return $location;
        }

        if (!str_contains($location, 'wp-admin')) {
            return $location;
        }

        return add_query_arg('vgt_iframe', 'true', $location);
    }

    /**
     * Critical inline fallback + accent tokens (enqueued CSS is primary).
     */
    public function inject_transform_styles(): void
    {
        if (!$this->is_iframe_context()) {
            return;
        }

        $nonce_attr = '';
        if (function_exists('vgt_get_csp_nonce')) {
            $nonce = vgt_get_csp_nonce();
            if (!empty($nonce)) {
                $nonce_attr = ' nonce="' . esc_attr($nonce) . '"';
            }
        }

        echo '<!-- VGT PORTAL TRANSFORMER v2 ACTIVE -->';
        echo '<style' . $nonce_attr . ' id="vgt-portal-critical">';
        echo 'html.wp-toolbar{padding-top:0!important}';
        echo 'body.vgt-portal-v2 #adminmenumain,body.vgt-portal-v2 #adminmenuback,body.vgt-portal-v2 #adminmenuwrap,';
        echo 'body.vgt-portal-v2 #wpadminbar,body.vgt-portal-v2 #wpfooter{display:none!important}';
        echo 'html.wp-toolbar,html.wp-toolbar body{padding-top:0!important;margin-top:0!important}';
        echo 'body.vgt-portal-v2 #wpcontent,body.vgt-portal-v2.wp-admin #wpbody{margin-left:0!important;padding:0!important}';
        /* List screens keep breathing room; full apps are zero-padded via portal CSS :has() */
        echo 'body.vgt-portal-v2.themes-php #wpbody-content,body.vgt-portal-v2.plugins-php #wpbody-content,';
        echo 'body.vgt-portal-v2.edit-php #wpbody-content,body.vgt-portal-v2.upload-php #wpbody-content{padding:12px!important}';
        echo '</style>';
    }
}
