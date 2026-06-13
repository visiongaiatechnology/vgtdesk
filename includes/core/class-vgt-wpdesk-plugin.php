<?php
declare(strict_types=1);

namespace VisionGaia\WPDesk;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * MODULE CONTROLLER: WPDeskPlugin
 * STATUS: 💠 DIAMANT VGT SUPREME
 * Zentraler Boot-Controller und Hook-Manager für das Slim Desktop-System.
 */
final class WPDeskPlugin
{
    use WPDeskAJAXTrait;

    private static ?self $instance = null;
    private array $apps = [];

    // Erlaubte Konfigurations-Werte für Strict-Whitelisting
    private const ALLOWED_ACCENT_COLORS = ['indigo', 'emerald', 'cyan', 'amber', 'rose', 'gold', 'purple', 'violet', 'neon'];

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        // PATTERN 1.5.C — Separation of internal reporting vs. display
        ini_set('display_errors', '0');              // User-visible output suppressed
        error_reporting(E_ALL);                      // Internal sensitivity maximum
        set_error_handler(static function(int $sev, string $msg, string $file, int $line): bool {
            if (!(error_reporting() & $sev)) return false;
            
            // Limit strict ErrorException mapping to our own namespace/plugin folder
            $normalized_file = str_replace('\\', '/', $file);
            $normalized_path = str_replace('\\', '/', VGT_WPDESK_PATH);
            
            if (str_contains($normalized_file, $normalized_path)) {
                throw new \ErrorException($msg, 0, $sev, $file, $line);
            }
            return false;
        });

        if (file_exists(VGT_WPDESK_PATH . 'vision-integrity-sentinel.php')) {
            require_once VGT_WPDESK_PATH . 'vision-integrity-sentinel.php';
        }

        if (file_exists(VGT_WPDESK_PATH . 'includes/class-iframe-transformer.php')) {
            require_once VGT_WPDESK_PATH . 'includes/class-iframe-transformer.php';
        }

        if (file_exists(VGT_WPDESK_PATH . 'includes/class-vgt-throne-guard.php')) {
            require_once VGT_WPDESK_PATH . 'includes/class-vgt-throne-guard.php';
        }

        if (file_exists(VGT_WPDESK_PATH . 'includes/dashboard/class-vgt-security-center.php')) {
            require_once VGT_WPDESK_PATH . 'includes/dashboard/class-vgt-security-center.php';
            VGTSecurityCenter::get_instance();
        }

        if (file_exists(VGT_WPDESK_PATH . 'includes/modules/loginpager/login-engine.php')) {
            require_once VGT_WPDESK_PATH . 'includes/modules/loginpager/login-engine.php';
        }

        if (file_exists(VGT_WPDESK_PATH . 'includes/build-center/vault.php')) {
            require_once VGT_WPDESK_PATH . 'includes/build-center/vault.php';
        }

        if (file_exists(VGT_WPDESK_PATH . 'includes/book-reader/bookreader.php')) {
            require_once VGT_WPDESK_PATH . 'includes/book-reader/bookreader.php';
        }

        if (file_exists(VGT_WPDESK_PATH . 'includes/chronos/Chronosloader.php')) {
            require_once VGT_WPDESK_PATH . 'includes/chronos/Chronosloader.php';
        }

        add_action('plugins_loaded', function() {
            if (!defined('VIS_VERSION') && !class_exists('VisionGaia\\WPDesk\\VGT_Dattrack_Engine')) {
                if (file_exists(VGT_WPDESK_PATH . 'includes/modules/dattrack/class-dattrack-engine.php')) {
                    require_once VGT_WPDESK_PATH . 'includes/modules/dattrack/class-dattrack-engine.php';
                    VGT_Dattrack_Engine::boot();
                }
            }
        }, 1);

        $this->init_hooks();
        
        if (class_exists(__NAMESPACE__ . '\\IframeTransformer')) {
            IframeTransformer::getInstance();
        }
        $this->register_current_worker();
    }

    public function register_current_worker(): void
    {
        if (!defined('DOING_AJAX') || !DOING_AJAX) {
            return;
        }

        $pid = getmypid();
        if (!$pid) {
            return;
        }

        $action = isset($_REQUEST['action']) ? sanitize_key($_REQUEST['action']) : 'unknown';

        $workers = get_transient('vgt_active_workers');
        if (!is_array($workers)) {
            $workers = [];
        }

        $now = time();
        $workers = array_filter($workers, function($w) use ($now) {
            return isset($w['timestamp']) && ($now - $w['timestamp']) < 30;
        });

        $workers[$pid] = [
            'pid' => $pid,
            'action' => $action,
            'start_time' => microtime(true),
            'timestamp' => $now,
            'memory' => size_format(memory_get_usage(true))
        ];

        set_transient('vgt_active_workers', $workers, 60);

        register_shutdown_function(function() use ($pid) {
            $workers = get_transient('vgt_active_workers');
            if (is_array($workers) && isset($workers[$pid])) {
                unset($workers[$pid]);
                set_transient('vgt_active_workers', $workers, 60);
            }
        });
    }

    private function init_hooks(): void
    {
        add_action('admin_menu', [$this, 'register_admin_page'], 10);
        add_action('admin_menu', [$this, 'build_dynamic_plugin_apps'], 9999);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_desktop_assets']);
        add_action('admin_head', [$this, 'inject_chromeless_css']);
        add_action('admin_init', [$this, 'handle_iframe_restrictions']);
        add_action('admin_init', [$this, 'handle_recovery_actions']);
        add_action('admin_notices', [WPDeskSettings::class, 'show_optin_admin_notice']);
        add_action('wp_ajax_vgt_save_user_settings', [$this, 'ajax_save_user_settings']);
        add_action('wp_ajax_vgt_toggle_sentinel', [$this, 'ajax_toggle_sentinel']);
        add_action('wp_ajax_vgt_toggle_dattrack', [$this, 'ajax_toggle_dattrack']);
        add_action('wp_ajax_vgt_get_diagnostics', [$this, 'ajax_get_diagnostics']);
        add_action('wp_ajax_vgt_unban_ip', [$this, 'ajax_unban_ip']);
        add_action('wp_ajax_vgt_ban_ip', [$this, 'ajax_ban_ip']);
        add_action('wp_ajax_vgt_update_superkey', [$this, 'ajax_update_superkey']);
        add_action('wp_ajax_vgt_get_task_manager_stats', [$this, 'ajax_get_task_manager_stats']);
        add_action('wp_ajax_vgt_unschedule_cron', [$this, 'ajax_unschedule_cron']);
        add_action('wp_ajax_vgt_kill_transient', [$this, 'ajax_kill_transient']);
        add_action('wp_ajax_vgt_optimize_database', [$this, 'ajax_optimize_database']);

        // Dynamic CSP Nonce filters for enqueued assets
        add_filter('style_loader_tag', [$this, 'add_csp_nonce_to_tags'], 10, 2);
        add_filter('script_loader_tag', [$this, 'add_csp_nonce_to_tags'], 10, 2);
    }

    public function register_admin_page(): void
    {
        $user_id = get_current_user_id();
        if (isset($_GET['vgt_action']) && $user_id) {
            $action = $_GET['vgt_action'];
            if ($action === 'enable_redirect') {
                check_admin_referer('vgt_toggle_redirect');
                global $wpdb;
                $table_name = $wpdb->prefix . 'vgt_desk_settings';
                WPDeskSettings::maybe_create_table();
                $wpdb->replace(
                    $table_name,
                    [
                        'user_id'       => $user_id,
                        'setting_key'   => 'auto_redirect',
                        'setting_value' => 'true'
                    ],
                    ['%d', '%s', '%s']
                );
                update_user_meta($user_id, 'vgt_desk_auto_redirect', 'true');
                
                // Clear bypass cookie
                $cookie_options = [
                    'expires'  => time() - 3600,
                    'path'     => COOKIEPATH,
                    'domain'   => COOKIE_DOMAIN,
                    'secure'   => is_ssl(),
                    'httponly' => true,
                    'samesite' => 'Strict'
                ];
                setcookie('vgt_desk_bypass', '', $cookie_options);

                wp_safe_redirect(admin_url('admin.php?page=vgt-wp-desk'));
                exit;
            }
            if ($action === 'dismiss_optin') {
                check_admin_referer('vgt_toggle_redirect');
                update_user_meta($user_id, 'vgt_dismiss_optin_notice', 'true');
                wp_safe_redirect(admin_url('index.php'));
                exit;
            }
        }

        if (isset($_GET['page']) && $_GET['page'] === 'vgt-wp-desk') {
            $cookie_options = [
                'expires'  => time() - 3600,
                'path'     => COOKIEPATH,
                'domain'   => COOKIE_DOMAIN,
                'secure'   => is_ssl(),
                'httponly' => true,
                'samesite' => 'Strict'
            ];
            setcookie('vgt_desk_bypass', '', $cookie_options);
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
        add_submenu_page(
            'vgt-security-center',
            'VGT Recovery Center',
            'Recovery Center',
            'manage_options',
            'vgt-recovery-center',
            [$this, 'render_recovery_center']
        );
    }

    public function build_dynamic_plugin_apps(): void
    {
        $user_id = get_current_user_id();
        $user_settings = $user_id ? WPDeskSettings::get_user_settings($user_id) : [];
        $this->apps = WPDeskAppBuilder::build($user_settings);
    }

    public function enqueue_desktop_assets(string $hook_suffix): void
    {
        if ($hook_suffix !== 'toplevel_page_vgt-wp-desk') {
            return;
        }

        remove_action('wp_head', '_admin_bar_bump_cb');
        wp_enqueue_style('dashicons');
        wp_enqueue_style('vgt-desktop-core-css', VGT_WPDESK_URL . 'assets/css/desktop-core.css', [], '1.0.0-Beta');
        wp_enqueue_style('vgt-desktop-windows-css', VGT_WPDESK_URL . 'assets/css/desktop-windows.css', ['vgt-desktop-core-css'], '1.0.0-Beta');
        wp_enqueue_style('vgt-desktop-icons-css', VGT_WPDESK_URL . 'assets/css/desktop-icons.css', ['vgt-desktop-core-css'], '1.0.0-Beta');
        wp_enqueue_style('vgt-desktop-widgets-css', VGT_WPDESK_URL . 'assets/css/desktop-widgets.css', ['vgt-desktop-core-css'], '1.0.0-Beta');
        wp_enqueue_style('vgt-desktop-apps-css', VGT_WPDESK_URL . 'assets/css/desktop-apps.css', ['vgt-desktop-core-css'], '1.0.0-Beta');

        // Register JS components
        wp_register_script('vgt-desktop-core', VGT_WPDESK_URL . 'assets/js/modules/desktop-core.js', [], '1.0.0-Beta', false);
        wp_register_script('vgt-desktop-windows', VGT_WPDESK_URL . 'assets/js/modules/desktop-windows.js', ['vgt-desktop-core'], '1.0.0-Beta', false);
        wp_register_script('vgt-desktop-draggable', VGT_WPDESK_URL . 'assets/js/modules/desktop-draggable.js', ['vgt-desktop-core'], '1.0.0-Beta', false);
        wp_register_script('vgt-desktop-icons', VGT_WPDESK_URL . 'assets/js/modules/desktop-icons.js', ['vgt-desktop-core'], '1.0.0-Beta', false);
        wp_register_script('vgt-desktop-menus', VGT_WPDESK_URL . 'assets/js/modules/desktop-menus.js', ['vgt-desktop-core'], '1.0.0-Beta', false);
        wp_register_script('vgt-desktop-widgets', VGT_WPDESK_URL . 'assets/js/modules/desktop-widgets.js', ['vgt-desktop-core'], '1.0.0-Beta', false);
        wp_register_script('vgt-desktop-spotlight', VGT_WPDESK_URL . 'assets/js/modules/desktop-spotlight.js', ['vgt-desktop-core'], '1.0.0-Beta', false);
        wp_register_script('vgt-desktop-modals', VGT_WPDESK_URL . 'assets/js/modules/desktop-modals.js', ['vgt-desktop-core'], '1.0.0-Beta', false);
        wp_register_script('vgt-desktop-folders', VGT_WPDESK_URL . 'assets/js/modules/desktop-folders.js', ['vgt-desktop-core'], '1.0.0-Beta', false);
        wp_register_script('vgt-desktop-wizard', VGT_WPDESK_URL . 'assets/js/modules/desktop-wizard.js', ['vgt-desktop-core'], '1.0.0-Beta', false);
        wp_register_script('vgt-desktop-taskmanager', VGT_WPDESK_URL . 'assets/js/modules/desktop-taskmanager.js', ['vgt-desktop-core'], '1.0.0-Beta', false);

        // Enqueue the primary orchestrator that depends on all sub-modules
        wp_enqueue_script('vgt-desktop-js', VGT_WPDESK_URL . 'assets/js/desktop.js', [
            'vgt-desktop-core',
            'vgt-desktop-windows',
            'vgt-desktop-draggable',
            'vgt-desktop-icons',
            'vgt-desktop-menus',
            'vgt-desktop-widgets',
            'vgt-desktop-spotlight',
            'vgt-desktop-modals',
            'vgt-desktop-folders',
            'vgt-desktop-wizard',
            'vgt-desktop-taskmanager'
        ], '1.0.0-Beta', false);

        $user_id       = get_current_user_id();
        $user_settings = WPDeskSettings::get_user_settings($user_id);

        global $wpdb;
        $bans_count = 0;

        // Check Sentinel V5/CE Bans
        $table_bans_v5 = $wpdb->prefix . 'vgts_apex_bans';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_bans_v5'") === $table_bans_v5) {
            $bans_count += (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_bans_v5");
        }

        // Check Sentinel V7 Bans
        $table_bans_v7 = $wpdb->prefix . 'vis_apex_bans';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_bans_v7'") === $table_bans_v7) {
            $bans_count += (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_bans_v7");
        }

        $sentinel_v7_active = defined('VIS_VERSION');
        $sentinel_active = (get_option('vgt_sentinel_enabled') === 'true') || $sentinel_v7_active;

        wp_localize_script('vgt-desktop-core', 'vgtConfig', [
            'ajaxUrl'      => admin_url('admin-ajax.php'),
            'adminUrl'     => admin_url(),
            'nonce'        => wp_create_nonce('vgt_desktop_action'),
            'toggleNonce'  => wp_create_nonce('vgt_toggle_desktop'),
            'userSettings' => $user_settings,
            'sentinelEnabled' => $sentinel_active,
            'sentinelBans'    => $bans_count,
            'isSentinelV7'    => $sentinel_v7_active,
            'superkeyActive'  => !empty(get_user_meta($user_id, 'mcp_superkey_hash', true)) || !empty(get_option('mcp_superkey_hash', '')),
            'dattrackEnabled' => !$sentinel_v7_active && (get_option('vgt_dattrack_enabled') === 'true'),
            'apps'            => $this->apps
        ]);
    }

    /**
     * REDIRECT & BYPASS SAFETY CONTROLLER
     */
    public function handle_iframe_restrictions(): void
    {
        if (isset($_GET['page']) && $_GET['page'] === 'vgt-recovery-center') {
            return;
        }

        $user_id = get_current_user_id();

        if (isset($_GET['vgt_action']) && $user_id) {
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
                // Auto-redirect deaktivieren
                global $wpdb;
                $table_name = $wpdb->prefix . 'vgt_desk_settings';
                WPDeskSettings::maybe_create_table();
                $wpdb->replace(
                    $table_name,
                    [
                        'user_id'       => $user_id,
                        'setting_key'   => 'auto_redirect',
                        'setting_value' => 'false'
                    ],
                    ['%d', '%s', '%s']
                );
                update_user_meta($user_id, 'vgt_desk_auto_redirect', 'false');

                $cookie_options['expires'] = time() + (86400 * 30);
                setcookie('vgt_desk_bypass', '1', $cookie_options);
                wp_safe_redirect(admin_url('index.php?vgt_bypass=1'));
                exit;
            }
            if ($_GET['vgt_action'] === 'enable_desk') {
                // Auto-redirect aktivieren
                global $wpdb;
                $table_name = $wpdb->prefix . 'vgt_desk_settings';
                WPDeskSettings::maybe_create_table();
                $wpdb->replace(
                    $table_name,
                    [
                        'user_id'       => $user_id,
                        'setting_key'   => 'auto_redirect',
                        'setting_value' => 'true'
                    ],
                    ['%d', '%s', '%s']
                );
                update_user_meta($user_id, 'vgt_desk_auto_redirect', 'true');

                $cookie_options['expires'] = time() - 3600;
                setcookie('vgt_desk_bypass', '', $cookie_options);
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

        if (!$user_id || !current_user_can('read')) {
            return;
        }

        // PER-USER OPT-IN CHECK: Nur umleiten wenn opt-in aktiv ist
        $user_settings = WPDeskSettings::get_user_settings($user_id);
        if (empty($user_settings['auto_redirect']) || $user_settings['auto_redirect'] !== true) {
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

        $user_id = get_current_user_id();
        $user_settings = WPDeskSettings::get_user_settings($user_id);
        $accent_color = $user_settings['accent_color'];
        
        $accent_map = [
            'indigo'  => ['main' => '#6366f1', 'hover' => '#818cf8', 'rgba15' => 'rgba(99, 102, 241, 0.15)', 'rgba8' => 'rgba(99, 102, 241, 0.08)'],
            'emerald' => ['main' => '#10b981', 'hover' => '#34d399', 'rgba15' => 'rgba(16, 185, 129, 0.15)', 'rgba8' => 'rgba(16, 185, 129, 0.08)'],
            'cyan'    => ['main' => '#06b6d4', 'hover' => '#22d3ee', 'rgba15' => 'rgba(6, 182, 212, 0.15)', 'rgba8' => 'rgba(6, 182, 212, 0.08)'],
            'amber'   => ['main' => '#f59e0b', 'hover' => '#fbbf24', 'rgba15' => 'rgba(245, 158, 11, 0.15)', 'rgba8' => 'rgba(245, 158, 11, 0.08)'],
            'rose'    => ['main' => '#f43f5e', 'hover' => '#fb7185', 'rgba15' => 'rgba(244, 63, 94, 0.15)', 'rgba8' => 'rgba(244, 63, 94, 0.08)']
        ];
        
        $color = $accent_map[$accent_color] ?? $accent_map['indigo'];

        echo '<style nonce="' . (function_exists('vgt_get_csp_nonce') ? esc_attr(vgt_get_csp_nonce()) : '') . '">
            :root {
                --vgt-accent: ' . esc_html($color['main']) . ';
                --vgt-accent-hover: ' . esc_html($color['hover']) . ';
                --vgt-accent-rgba15: ' . esc_html($color['rgba15']) . ';
                --vgt-accent-rgba8: ' . esc_html($color['rgba8']) . ';
            }
            #adminmenumain, #adminmenuback, #adminmenuwrap, #wpadminbar, #wpfooter, 
            .update-nag, #screen-meta-links, .notice, .notice-error, .notice-warning, 
            .notice-info, .notice-success, #contextual-help-link-wrap, #wp-admin-bar-root-default { 
                display: none !important; 
            }
            html, html.wp-toolbar { padding-top: 0 !important; margin-top: 0 !important; height: 100vh !important; background: #090d16 !important; }
            body { background: #090d16 !important; }
            body.admin-bar #wpcontent, #wpcontent, #wpbody, .wrap { margin-left: 0 !important; margin-right: 0 !important; padding: 15px !important; background: #090d16 !important; color: #cbd5e1 !important; min-height: 100vh !important; box-sizing: border-box !important; }
            .wrap h1, .wrap h2, .wrap h3, h1, h2, h3, h4, h5, h6, .title, .postbox-header h2, .wp-heading-inline, .card h2, .form-table th, label, .manage-column, .column-title, strong, td, th, .wp-filter-search { color: #f1f5f9 !important; }
            p, span, .description, .help, .tablenav, .subsubsub a { color: #94a3b8 !important; }
            a { color: var(--vgt-accent) !important; } a:hover { color: var(--vgt-accent-hover) !important; }
            .widefat, .wp-list-table { background: #0f172a !important; border: 1px solid #1e293b !important; }
            .widefat td, .widefat th { border-bottom: 1px solid #1e293b !important; color: #cbd5e1 !important; }
            .alternate, .striped > tbody > :nth-child(odd) { background-color: #0b0f19 !important; }
            input[type="text"], input[type="search"], input[type="number"], input[type="password"], input[type="email"], textarea, select { background-color: #0f172a !important; border: 1px solid #334155 !important; color: #f1f5f9 !important; border-radius: 6px !important; }
            .postbox, .card, .welcome-panel { background: #0f172a !important; border: 1px solid #1e293b !important; border-radius: 12px !important; }
            ::-webkit-scrollbar { width: 8px; height: 8px; }
            ::-webkit-scrollbar-track { background: #090d16; }
            ::-webkit-scrollbar-thumb { background: #1e293b; border-radius: 99px; border: 2px solid #090d16; }
            ::-webkit-scrollbar-thumb:hover { background: #334155; }
            body.plugins-php table.wp-list-table.plugins tr.active td, body.plugins-php table.wp-list-table.plugins tr.active th { background: rgb(9 13 22) !important; }
            body.plugins-php table.wp-list-table.plugins tr.inactive td, body.plugins-php table.wp-list-table.plugins tr.inactive th { background: rgb(9 13 22) !important; }
            body.plugins-php table.wp-list-table.plugins tr td, body.plugins-php table.wp-list-table.plugins tr th { background: rgb(9 13 22) !important; color: #ffffff !important; }

            /* Medien-Bibliothek & Filter Toolbar */
            .wp-filter, .media-toolbar, .media-frame-content, .media-sidebar, .attachments-browser, .uploader-inline {
                background: #090d16 !important;
                background-color: #090d16 !important;
                border-color: #1e293b !important;
            }
            .media-toolbar select, .media-toolbar input[type="search"] {
                background-color: #0f172a !important;
                color: #cbd5e1 !important;
                border: 1px solid #334155 !important;
            }
            .media-frame {
                background: #090d16 !important;
            }
            
            /* Themes (Design) Screen Fixes */
            .theme-about, .theme-info, .theme-browser .theme, .theme-browser .theme .theme-name, 
            .theme-overlay .theme-header, .theme-overlay .theme-about, .theme-overlay, .theme-wrap {
                background: #090d16 !important;
                background-color: #090d16 !important;
                color: #cbd5e1 !important;
                border-color: #1e293b !important;
            }
            .theme-browser .themes {
                display: flex !important;
                flex-wrap: wrap !important;
                gap: 20px !important;
            }
            .theme-browser .theme {
                float: none !important;
                margin: 0 !important;
                background: #0f172a !important;
                border: 1px solid #1e293b !important;
                border-radius: 12px !important;
                overflow: hidden !important;
                width: calc(33.333% - 14px) !important;
                min-width: 260px !important;
                box-sizing: border-box !important;
            }
            .theme-browser .theme.add-new-theme {
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                justify-content: center !important;
                height: auto !important;
                min-height: 250px !important;
                background: rgba(255, 255, 255, 0.02) !important;
                border: 2px dashed rgba(255, 255, 255, 0.15) !important;
            }
            .theme-browser .theme.add-new-theme:hover {
                border-color: var(--vgt-accent) !important;
                background: var(--vgt-accent-rgba8) !important;
            }
            .theme-browser .theme.add-new-theme a {
                color: #cbd5e1 !important;
            }

            /* Menüs (nav-menus.php) Screen Fixes */
            #nav-menus-frame, #menu-settings-column, .posttypediv, .postboxes-column, .postbox, 
            .accordion-container, .accordion-section, .accordion-section-title, .accordion-trigger,
            .accordion-section-content, .menu-item-bar, .menu-item-handle, .menu-item-settings, .nav-menu-header, 
            .nav-menu-footer, #nav-menu-header, #nav-menu-footer, #menu-management, .manage-menus,
            .tabs-panel, .tabs-panel-active, .add-menu-item-tabs, .posttype-tabs, .post-body-plain, 
            .drag-instructions, .menu-instructions, .bulk-actions, #post-body-content, #menu-to-edit {
                background: #090d16 !important;
                background-color: #090d16 !important;
                color: #cbd5e1 !important;
                border-color: #1e293b !important;
            }
            .categorychecklist li, .categorychecklist label, .categorychecklist input {
                background: transparent !important;
                color: #cbd5e1 !important;
            }
            .menu-item-bar .menu-item-handle {
                background: #0f172a !important;
                border: 1px solid #1e293b !important;
                color: #f1f5f9 !important;
            }
            .wp-core-ui .button-secondary {
                background: #1e293b !important;
                border: 1px solid #334155 !important;
                color: #cbd5e1 !important;
            }
            .wp-core-ui .button-secondary:hover {
                background: #334155 !important;
                color: #ffffff !important;
            }
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
        $current_user  = wp_get_current_user();
        $user_settings = WPDeskSettings::get_user_settings($current_user->ID);
        $apps_data     = $this->apps;
        include VGT_WPDESK_PATH . 'templates/desktop-shell.php';
    }

    public function handle_recovery_actions(): void
    {
        if (empty($_POST['recovery_action'])) {
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Zu dieser Aktion besitzen Sie nicht genügend Rechte.', 'vgt-wp-desk'));
        }

        $page = isset($_GET['page']) ? sanitize_key($_GET['page']) : 'vgt-security-center';
        $view = isset($_GET['view']) ? sanitize_key($_GET['view']) : 'recovery';
        
        $redirect_url = admin_url('admin.php');
        $redirect_url = add_query_arg('page', $page, $redirect_url);
        if ($view) {
            $redirect_url = add_query_arg('view', $view, $redirect_url);
        }

        if (!isset($_POST['vgt_recovery_nonce']) || !wp_verify_nonce($_POST['vgt_recovery_nonce'], 'vgt_recovery_action')) {
            $redirect_url = add_query_arg('vgt_recovery_err', 'invalid_nonce', $redirect_url);
            wp_safe_redirect($redirect_url);
            exit;
        }

        $action = sanitize_key($_POST['recovery_action']);
        $message = '';
        
        if ($action === 'force_classic') {
            $cookie_options = [
                'expires'  => time() + (86400 * 30),
                'path'     => COOKIEPATH,
                'domain'   => COOKIE_DOMAIN,
                'secure'   => is_ssl(),
                'httponly' => true,
                'samesite' => 'Strict'
            ];
            setcookie('vgt_desk_bypass', '1', $cookie_options);
            $_COOKIE['vgt_desk_bypass'] = '1';
            $message = 'force_classic';
        } elseif ($action === 'disable_redirect') {
            global $wpdb;
            $table_name = $wpdb->prefix . 'vgt_desk_settings';
            WPDeskSettings::maybe_create_table();
            $user_id = get_current_user_id();
            $wpdb->replace(
                $table_name,
                [
                    'user_id'       => $user_id,
                    'setting_key'   => 'auto_redirect',
                    'setting_value' => 'false'
                ],
                ['%d', '%s', '%s']
            );
            update_user_meta($user_id, 'vgt_desk_auto_redirect', 'false');
            
            // Clear bypass cookie
            $cookie_options = [
                'expires'  => time() - 3600,
                'path'     => COOKIEPATH,
                'domain'   => COOKIE_DOMAIN,
                'secure'   => is_ssl(),
                'httponly' => true,
                'samesite' => 'Strict'
            ];
            setcookie('vgt_desk_bypass', '', $cookie_options);
            unset($_COOKIE['vgt_desk_bypass']);
            
            $message = 'disable_redirect';
        } elseif ($action === 'disable_dattrack') {
            update_option('vgt_dattrack_enabled', 'false');
            $message = 'disable_dattrack';
        } elseif ($action === 'export_diagnostics') {
            $this->export_diagnostics_file();
            exit;
        }

        if ($message) {
            $redirect_url = add_query_arg('vgt_recovery_msg', $message, $redirect_url);
        }
        
        wp_safe_redirect($redirect_url);
        exit;
    }

    public function render_recovery_center(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Zu dieser Aktion besitzen Sie nicht genügend Rechte.', 'vgt-wp-desk'));
        }

        $message = '';
        $error = '';

        if (isset($_GET['vgt_recovery_msg'])) {
            $msg_key = sanitize_key($_GET['vgt_recovery_msg']);
            if ($msg_key === 'force_classic') {
                $message = 'Klassische Ansicht wurde erzwungen (Bypass Cookie für 30 Tage gesetzt).';
            } elseif ($msg_key === 'disable_redirect') {
                $message = 'Auto-Redirect wurde für Ihren Account deaktiviert.';
            } elseif ($msg_key === 'disable_dattrack') {
                $message = 'Dattrack Telemetrie wurde global deaktiviert.';
            }
        }

        if (isset($_GET['vgt_recovery_err'])) {
            $err_key = sanitize_key($_GET['vgt_recovery_err']);
            if ($err_key === 'invalid_nonce') {
                $error = 'Ungültiger CSRF Token.';
            }
        }

        include VGT_WPDESK_PATH . 'templates/recovery-center.php';
    }

    private function export_diagnostics_file(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('Permission denied');
        }
        
        global $wpdb;
        $active_plugins = get_option('active_plugins', []);
        $theme = wp_get_theme();
        $user_id = get_current_user_id();
        $superkey_hash = get_user_meta($user_id, 'mcp_superkey_hash', true);
        if (empty($superkey_hash)) {
            $superkey_hash = get_option('mcp_superkey_hash', '');
        }
        $sentinel_v7_active = defined('VIS_VERSION');
        
        $diagnostics = [
            'timestamp' => current_time('mysql'),
            'wp_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'theme' => [
                'name' => $theme->get('Name'),
                'version' => $theme->get('Version')
            ],
            'plugins' => $active_plugins,
            'throne_guard' => [
                'active' => !empty($superkey_hash),
            ],
            'sentinel' => [
                'enabled' => get_option('vgt_sentinel_enabled') === 'true',
                'v7_active' => $sentinel_v7_active
            ],
            'dattrack' => [
                'enabled' => get_option('vgt_dattrack_enabled') === 'true',
                'has_keys' => file_exists(wp_upload_dir()['basedir'] . '/.vgt-keys/master.php')
            ]
        ];
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="vgt-diagnostics-' . date('Y-m-d-His') . '.json"');
        echo wp_json_encode($diagnostics, JSON_PRETTY_PRINT);
        exit;
    }

    public function add_csp_nonce_to_tags(string $tag, string $handle): string
    {
        if (str_starts_with($handle, 'vgt-')) {
            if (function_exists('vgt_get_csp_nonce')) {
                $nonce = vgt_get_csp_nonce();
                if (!empty($nonce)) {
                    $tag = str_replace('<link ', '<link nonce="' . esc_attr($nonce) . '" ', $tag);
                    $tag = str_replace('<script ', '<script nonce="' . esc_attr($nonce) . '" ', $tag);
                }
            }
        }
        return $tag;
    }

    public static function activate(): void
    {
        WPDeskSettings::maybe_create_table();
        
        if (class_exists('VisionGaia\\WPDesk\\VGT_Dattrack_Engine')) {
            VGT_Dattrack_Engine::system_genesis();
        }
    }
}
