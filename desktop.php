<?php
/**
 * Plugin Name:       VGT WP-Desk — Premium Slim Desktop (Modular)
 * Description:       Ein eleganter, modularer Desktop-Mode für das WordPress-Backend. Schlank, unzerstörbar und hochkompatibel.
 * Version:           1.0.0-Beta (Hardened Edition)
 * Author:            VisionGaiaTechnology
 * Text Domain:       vgt-wp-desk
 */

declare(strict_types=1);

namespace VisionGaia\WPDesk;

if (!defined('ABSPATH')) {
    exit;
}

// Pfad-Definitionen
define('VGT_WPDESK_PATH', plugin_dir_path(__FILE__));
define('VGT_WPDESK_URL', plugin_dir_url(__FILE__));

// KERNEL EXCEPTION HIERARCHY (PATTERN 1.5.A)
class WPDeskException     extends \Exception {}
class ValidationException extends WPDeskException {} // User-Facing
class SecurityException   extends WPDeskException {} // Internal Opaque Log, Generic Client Response
class StorageException    extends WPDeskException {} // Infrastructure Failure

/**
 * MODULE CONTROLLER: WPDeskPlugin
 * STATUS: 💠 DIAMANT VGT SUPREME
 */
final class WPDeskPlugin
{
    private static ?self $instance = null;
    private array $apps = [];

    // Erlaubte Konfigurations-Werte für Strict-Whitelisting
    private const ALLOWED_ACCENT_COLORS = ['indigo', 'emerald', 'cyan', 'amber', 'rose'];

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        // PATTERN 1.5.C — Globale Fehlersensitivität erzwingen
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_reporting(E_ALL);
        }

        $this->init_hooks();
        
        if (class_exists(__NAMESPACE__ . '\\IframeTransformer')) {
            IframeTransformer::getInstance();
        }
    }

    private function init_hooks(): void
    {
        add_action('admin_menu', [$this, 'register_admin_page'], 10);
        add_action('admin_menu', [$this, 'build_dynamic_plugin_apps'], 9999);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_desktop_assets']);
        add_action('admin_head', [$this, 'inject_chromeless_css']);
        add_action('admin_init', [$this, 'handle_iframe_restrictions']);
        add_action('wp_ajax_vgt_save_user_settings', [$this, 'ajax_save_user_settings']);
    }

    public function register_admin_page(): void
    {
        if (isset($_GET['page']) && $_GET['page'] === 'vgt-wp-desk') {
            setcookie('vgt_desk_bypass', '', [
                'expires'  => time() - 3600,
                'path'     => COOKIEPATH,
                'domain'   => COOKIE_DOMAIN,
                'secure'   => is_ssl(),
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
            if (isset($_COOKIE['vgt_desk_bypass'])) {
                unset($_COOKIE['vgt_desk_bypass']);
            }
        }

        add_menu_page(
            'VGT WP-Desk',
            'VGT WP-Desk',
            'read',
            'vgt-wp-desk',
            [$this, 'render_desktop_shell'],
            'dashicons-desktop',
            2
        );
    }

    public function build_dynamic_plugin_apps(): void
    {
        global $menu;
        if (empty($menu)) {
            return;
        }

        $parsed_apps = [];
        $exclusions  = ['vgt-wp-desk', 'separator', 'wp-logo'];

        foreach ($menu as $item) {
            if (empty($item[0]) || empty($item[2])) {
                continue;
            }

            $title = wp_strip_all_tags($item[0]);
            $slug  = $item[2];

            $should_exclude = false;
            foreach ($exclusions as $ex) {
                if (stripos($slug, $ex) !== false) {
                    $should_exclude = true;
                    break;
                }
            }
            if ($should_exclude) {
                continue;
            }

            if (preg_match('/^https?:\/\//', $slug)) {
                $url = $slug;
            } elseif (stripos($slug, '.php') !== false) {
                $url = admin_url($slug);
            } else {
                $url = admin_url('admin.php?page=' . $slug);
            }

            $icon_type = 'dashicons';
            $icon_val  = 'dashicons-admin-generic';

            if (!empty($item[6])) {
                $raw_icon = $item[6];
                if (str_starts_with($raw_icon, 'dashicons-')) {
                    $icon_val  = $raw_icon;
                } elseif (str_starts_with($raw_icon, 'data:image/svg+xml') || str_starts_with($raw_icon, 'data:image/png')) {
                    $icon_type = 'svg';
                    $icon_val  = $raw_icon;
                } elseif (filter_var($raw_icon, FILTER_VALIDATE_URL)) {
                    $icon_type = 'url';
                    $icon_val  = $raw_icon;
                }
            }

            $color_presets = [
                'from-indigo-500 to-indigo-600',
                'from-emerald-500 to-emerald-600',
                'from-cyan-500 to-cyan-600',
                'from-amber-500 to-amber-600',
                'from-purple-500 to-purple-600',
                'from-rose-500 to-rose-600',
                'from-pink-500 to-pink-600',
                'from-blue-500 to-blue-600'
            ];
            $preset_index = abs(crc32($slug)) % count($color_presets);
            $color        = $color_presets[$preset_index];
            $app_id       = sanitize_key($slug);
            
            $parsed_apps[$app_id] = [
                'title'     => $title,
                'url'       => $url,
                'icon_type' => $icon_type,
                'icon_val'  => $icon_val,
                'color'     => $color
            ];
        }

        $this->apps = apply_filters('vgt_wpdesk_registered_apps', $parsed_apps);
    }

    public function enqueue_desktop_assets(string $hook_suffix): void
    {
        if ($hook_suffix !== 'toplevel_page_vgt-wp-desk') {
            return;
        }

        remove_action('wp_head', '_admin_bar_bump_cb');
        wp_enqueue_style('dashicons');
        wp_enqueue_style('vgt-desktop-css', VGT_WPDESK_URL . 'assets/css/desktop.css', [], '1.0.0-Beta');
        wp_enqueue_script('vgt-desktop-js', VGT_WPDESK_URL . 'assets/js/desktop.js', [], '1.0.0-Beta', false);

        $user_id       = get_current_user_id();
        $user_settings = [
            'wallpaper'       => esc_url_raw(get_user_meta($user_id, 'vgt_desk_wallpaper', true) ?: VGT_WPDESK_URL . 'wallpapers/wall1.webp'),
            'accent_color'    => sanitize_key(get_user_meta($user_id, 'vgt_desk_accent_color', true) ?: 'indigo'),
            'blur'            => get_user_meta($user_id, 'vgt_desk_blur', true) !== 'false',
            'icon_positions'  => json_decode(get_user_meta($user_id, 'vgt_desk_icon_positions', true) ?: '{}', true),
            'window_settings' => json_decode(get_user_meta($user_id, 'vgt_desk_window_settings', true) ?: '{}', true)
        ];

        wp_localize_script('vgt-desktop-js', 'vgtConfig', [
            'ajaxUrl'      => admin_url('admin-ajax.php'),
            'adminUrl'     => admin_url(),
            'nonce'        => wp_create_nonce('vgt_desktop_action'),
            'toggleNonce'  => wp_create_nonce('vgt_toggle_desktop'),
            'userSettings' => $user_settings
        ]);
    }

    /**
     * GEHÄRTETER AJAX PERSISTENZ ENDPUNKT (PATTERN 1.5.A BEFOLGT)
     */
    public function ajax_save_user_settings(): void
    {
        try {
            if (!check_ajax_referer('vgt_desktop_action', 'nonce', false)) {
                throw new SecurityException('CSRF Token validation failed.');
            }

            $user_id = get_current_user_id();
            if (!$user_id || !current_user_can('read')) {
                throw new SecurityException('Insufficient capabilities or unauthenticated session.');
            }

            $type  = isset($_POST['setting_type']) ? sanitize_key($_POST['setting_type']) : '';
            $value = isset($_POST['value']) ? wp_unslash($_POST['value']) : '';

            if (!in_array($type, ['wallpaper', 'accent_color', 'blur', 'icon_positions', 'window_settings'], true)) {
                throw new ValidationException('Invalid configuration parameters submitted.');
            }

            // STRIKTES TYP- UND WERTE-WHITELISTING
            if ($type === 'accent_color' && !in_array($value, self::ALLOWED_ACCENT_COLORS, true)) {
                throw new ValidationException('Illegal accent color value.');
            }

            if ($type === 'blur') {
                $value = ($value === 'true' || $value === '1') ? 'true' : 'false';
            }

            if (in_array($type, ['icon_positions', 'window_settings'], true)) {
                if (!is_string($value)) {
                    throw new ValidationException('Expected string payload for JSON fields.');
                }
                $decoded = json_decode($value, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new ValidationException('Malformed JSON structural payload.');
                }
                $value = json_encode($decoded, JSON_FORCE_OBJECT);
            } elseif ($type === 'wallpaper') {
                $value = esc_url_raw($value);
            } else {
                $value = sanitize_key($value);
            }

            if (update_user_meta($user_id, 'vgt_desk_' . $type, $value) === false) {
                throw new StorageException('Database transaction failure during meta persistence update.');
            }

            wp_send_json_success(['message' => 'Configuration persisted successfully.', 'type' => $type]);

        } catch (ValidationException $e) {
            wp_send_json_error($e->getMessage());
        } catch (SecurityException $e) {
            error_log('[SEC] VGT WP-Desk — ' . $e->getMessage());
            wp_send_json_error('Request rejected for security reasons.');
        } catch (StorageException $e) {
            error_log('[STORAGE] VGT WP-Desk — ' . $e->getMessage());
            wp_send_json_error('A persistent server storage error occurred.');
        } catch (\Throwable $e) {
            error_log('[FATAL] VGT WP-Desk Critical Exception — ' . $e->getMessage());
            wp_send_json_error('Critical system fault execution halted.');
        }
    }

    /**
     * REDIRECT & BYPASS SAFETY CONTROLLER
     */
    public function handle_iframe_restrictions(): void
    {
        if (isset($_GET['vgt_action'])) {
            $nonce = $_GET['_wpnonce'] ?? '';
            if (!wp_verify_nonce($nonce, 'vgt_toggle_desktop')) {
                wp_die(esc_html__('Sicherheitsüberprüfung (CSRF-Schutz) fehlgeschlagen.', 'vgt-wp-desk'), '', ['response' => 403]);
            }

            $cookie_options = [
                'path'     => COOKIEPATH,
                'domain'   => COOKIE_DOMAIN,
                'secure'   => is_ssl(),
                'httponly' => true,
                'samesite' => 'Strict'
            ];

            if ($_GET['vgt_action'] === 'disable_desk') {
                setcookie('vgt_desk_bypass', '1', time() + (86400 * 30), $cookie_options);
                wp_safe_redirect(admin_url('index.php?vgt_bypass=1'));
                exit;
            }
            if ($_GET['vgt_action'] === 'enable_desk') {
                setcookie('vgt_desk_bypass', '', time() - 3600, $cookie_options);
                wp_safe_redirect(admin_url('admin.php?page=vgt-wp-desk'));
                exit;
            }
        }

        if ($this->is_iframe_context()) {
            remove_all_actions('admin_notices');
            remove_all_actions('all_admin_notices');
            return;
        }

        if (
            wp_doing_ajax() || 
            (defined('DOING_CRON') && DOING_CRON) ||
            isset($_GET['vgt_bypass']) ||
            isset($_COOKIE['vgt_desk_bypass'])
        ) {
            return;
        }

        if (!current_user_can('read')) {
            return;
        }

        global $pagenow;
        if ($pagenow === 'admin.php' && isset($_GET['page']) && $_GET['page'] === 'vgt-wp-desk') {
            return;
        }

        $excluded_pages = ['async-upload.php', 'admin-post.php', 'update.php'];
        if (in_array($pagenow, $excluded_pages, true)) {
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return;
        }

        // Lokaler Pfad-Sanitize zur Abwendung von Open-Redirect Schleifen
        $requested_url = esc_url_raw($_SERVER['REQUEST_URI']);
        
        wp_safe_redirect(admin_url('admin.php?page=vgt-wp-desk&vgt_redirect_to=' . urlencode($requested_url)));
        exit;
    }

    public function inject_chromeless_css(): void
    {
        if (!$this->is_iframe_context()) {
            return;
        }

        // HINWEIS: Einbindung eines CSP-Zertifikats/Nonce wird dringend für den Style-Tag empfohlen
        echo '';
        echo '<style nonce="' . (function_exists('vgt_get_csp_nonce') ? esc_attr(vgt_get_csp_nonce()) : '') . '">
            #adminmenumain, #adminmenuback, #adminmenuwrap, #wpadminbar, #wpfooter, 
            .update-nag, #screen-meta-links, .notice, .notice-error, .notice-warning, 
            .notice-info, .notice-success, #contextual-help-link-wrap, #wp-admin-bar-root-default { 
                display: none !important; 
            }
            html, html.wp-toolbar { padding-top: 0 !important; margin-top: 0 !important; height: 100vh !important; background: #090d16 !important; }
            body.admin-bar #wpcontent, #wpcontent, #wpbody, .wrap { margin-left: 0 !important; margin-right: 0 !important; padding: 15px !important; background: #090d16 !important; color: #cbd5e1 !important; min-height: 100vh !important; box-sizing: border-box !important; }
            .wrap h1, .wrap h2, .wrap h3, h1, h2, h3, h4, h5, h6, .title, .postbox-header h2, .wp-heading-inline, .card h2, .form-table th, label, .manage-column, .column-title, strong, td, th, .wp-filter-search { color: #f1f5f9 !important; }
            p, span, .description, .help, .tablenav, .subsubsub a { color: #94a3b8 !important; }
            a { color: #6366f1 !important; } a:hover { color: #818cf8 !important; }
            .widefat, .wp-list-table { background: #0f172a !important; border: 1px solid #1e293b !important; }
            .widefat td, .widefat th { border-bottom: 1px solid #1e293b !important; color: #cbd5e1 !important; }
            .alternate, .striped > tbody > :nth-child(odd) { background-color: #0b0f19 !important; }
            input[type="text"], input[type="search"], input[type="number"], input[type="password"], input[type="email"], textarea, select { background-color: #0f172a !important; border: 1px solid #334155 !important; color: #f1f5f9 !important; border-radius: 6px !important; }
            .postbox, .card, .welcome-panel { background: #0f172a !important; border: 1px solid #1e293b !important; border-radius: 12px !important; }
            ::-webkit-scrollbar { width: 8px; height: 8px; }
            ::-webkit-scrollbar-track { background: #090d16; }
            ::-webkit-scrollbar-thumb { background: #1e293b; border-radius: 99px; border: 2px solid #090d16; }
            ::-webkit-scrollbar-thumb:hover { background: #334155; }
        </style>';
    }

    public function is_iframe_context(): bool
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

    public function render_desktop_shell(): void
    {
        $current_user = wp_get_current_user();
        $apps_data    = $this->apps;
        include VGT_WPDESK_PATH . 'templates/desktop-shell.php';
    }
}

WPDeskPlugin::getInstance();
