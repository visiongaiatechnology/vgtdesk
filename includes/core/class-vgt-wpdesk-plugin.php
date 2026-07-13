<?php
declare(strict_types=1);

namespace VisionGaia\WPDesk;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * MODULE CONTROLLER: WPDeskPlugin
 * STATUS: DIAMANT VGT SUPREME
 * Zentraler Boot-Controller und Hook-Manager fuer das Slim Desktop-System.
 */
final class WPDeskPlugin
{
    use WPDeskAJAXTrait;

    private static ?self $instance = null;
    private array $apps = [];

    private const INTEGRATED_MODULES = [
        'sentinel_ce' => [
            'label' => 'Sentinel CE',
            'description' => 'Open-Source Security Core mit WAF, Scanner und Hardening.',
            'option' => 'vgt_sentinel_enabled',
            'default' => true,
            'reload' => true,
        ],
        'throne_guard' => [
            'label' => 'Throne Guard',
            'description' => 'Master-Rollen, Superkey und Backend-Hardening.',
            'option' => 'vgt_module_throne_guard_enabled',
            'default' => true,
            'reload' => true,
        ],
        'dattrack' => [
            'label' => 'Dattrack',
            'description' => 'Privacy-fokussierte lokale Analytics und Rollups.',
            'option' => 'vgt_dattrack_enabled',
            'default' => false,
            'reload' => true,
        ],
        'omega_vault' => [
            'label' => 'Omega Vault',
            'description' => 'Build Center, verschluesselte Formulare und Com-Link Vault.',
            'option' => 'vgt_module_omega_vault_enabled',
            'default' => true,
            'reload' => true,
        ],
        'book_reader' => [
            'label' => 'Book Reader',
            'description' => 'PDF-/Book-Reader Engine als integriertes Modul.',
            'option' => 'vgt_module_book_reader_enabled',
            'default' => true,
            'reload' => true,
        ],
        'chronos' => [
            'label' => 'Chronos',
            'description' => 'Countdown- und Timing-Engine fuer Kampagnen und Seiten.',
            'option' => 'vgt_module_chronos_enabled',
            'default' => true,
            'reload' => true,
        ],
        'loginpager' => [
            'label' => 'LoginPager',
            'description' => 'Login-Oberflaechen und Admin-Zugangsanpassungen.',
            'option' => 'vgt_module_loginpager_enabled',
            'default' => true,
            'reload' => true,
        ],
        'astra' => [
            'label' => 'VGTAstra',
            'description' => 'Zero-dependency WordPress AI assistant system with Groq reasoning pipelines.',
            'option' => 'vgt_module_astra_enabled',
            // Default on so the Operator OS ships the Gutenberg assist module unless disabled.
            'default' => true,
            'reload' => true,
        ],
    ];

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        // No global set_error_handler — WordPress and sibling modules own error reporting.
        // Log-only preference for desk PHP; never throw ErrorException across the request.
        if (function_exists('ini_set')) {
            @ini_set('display_errors', '0');
        }

        self::remove_legacy_integrated_plugins_from_registry();

        WPDeskModuleRegistry::boot_all(static function (string $module): bool {
            // Always-on services are not option-gated; option_gated keys map to INTEGRATED_MODULES.
            if ($module === 'iframe_transformer' || $module === 'security_center') {
                return true;
            }
            return self::is_integrated_module_enabled($module);
        });

        $this->init_hooks();

        if (class_exists(__NAMESPACE__ . '\\IframeTransformer')) {
            IframeTransformer::getInstance();
        }
        $this->register_current_worker();
    }

    public static function integrated_module_definitions(): array
    {
        return self::INTEGRATED_MODULES;
    }

    public static function is_integrated_module_enabled(string $module): bool
    {
        $definitions = self::integrated_module_definitions();
        if (!isset($definitions[$module])) {
            return false;
        }

        $definition = $definitions[$module];
        $default = !empty($definition['default']);
        $raw = get_option($definition['option'], $default ? 'true' : 'false');
        return $raw === true || $raw === 'true' || $raw === '1' || $raw === 1;
    }

    public static function integrated_module_statuses(): array
    {
        $modules = [];
        $sentinel_v7_active = class_exists('VIS_Kernel') || defined('VIS_VERSION');

        foreach (self::integrated_module_definitions() as $key => $definition) {
            $available = true;
            $locked = false;
            $locked_reason = '';
            $enabled = self::is_integrated_module_enabled($key);

            if ($key === 'sentinel_ce' && $sentinel_v7_active) {
                $enabled = false;
                $locked = true;
                $locked_reason = 'Sentinel V7 ist aktiv. Sentinel CE bleibt als Konfliktschutz deaktiviert.';
            }

            $health = null;
            if ($key === 'astra' && \class_exists('VGTAstra\\AgentSystem\\AgenticOrchestrator') && \method_exists('VGTAstra\\AgentSystem\\AgenticOrchestrator', 'healthSnapshot')) {
                $health = \VGTAstra\AgentSystem\AgenticOrchestrator::healthSnapshot();
            }

            $modules[] = [
                'key' => $key,
                'label' => $definition['label'],
                'description' => $definition['description'],
                'enabled' => $enabled,
                'available' => $available,
                'locked' => $locked,
                'lockedReason' => $locked_reason,
                'reload' => !empty($definition['reload']),
                'health' => $health,
            ];
        }

        return $modules;
    }

    public static function set_integrated_module_enabled(string $module, bool $enabled): array
    {
        $definitions = self::integrated_module_definitions();
        if (!isset($definitions[$module])) {
            throw new ValidationException('Invalid module identifier.');
        }

        if ($module === 'sentinel_ce' && $enabled && (class_exists('VIS_Kernel') || defined('VIS_VERSION'))) {
            throw new ValidationException('Sentinel CE kann nicht aktiviert werden, solange Sentinel V7 aktiv ist.');
        }

        $definition = $definitions[$module];
        update_option($definition['option'], $enabled ? 'true' : 'false', false);

        if ($module === 'dattrack' && class_exists('VisionGaia\WPDesk\VGT_Dattrack_Engine')) {
            if ($enabled) {
                VGT_Dattrack_Engine::system_genesis();
            } else {
                VGT_Dattrack_Engine::system_halt();
            }
        }

        if ($module === 'sentinel_ce' && !$enabled && function_exists('vgts_deactivate_module')) {
            vgts_deactivate_module();
        }

        if ($module === 'throne_guard' && !$enabled && class_exists('VisionGaia\ThroneGuard\MasterUserControlPlugin')) {
            $instance = \VisionGaia\ThroneGuard\MasterUserControlPlugin::get_instance();
            if ($instance && current_user_can('mcp_master_access')) {
                $instance->deactivate();
            }
        }

        return [
            'module' => $module,
            'label' => $definition['label'],
            'enabled' => $enabled,
            'reload' => !empty($definition['reload']),
        ];
    }

    private static function remove_legacy_integrated_plugins_from_registry(): void
    {
        $legacy_plugins = [
            plugin_basename(VGT_WPDESK_PATH . 'vision-integrity-sentinel.php'),
            plugin_basename(VGT_WPDESK_PATH . 'includes/class-vgt-throne-guard.php'),
            plugin_basename(VGT_WPDESK_PATH . 'includes/build-center/vault.php'),
            plugin_basename(VGT_WPDESK_PATH . 'includes/book-reader/bookreader.php'),
            plugin_basename(VGT_WPDESK_PATH . 'includes/chronos/Chronosloader.php'),
            plugin_basename(VGT_WPDESK_PATH . 'includes/modules/dattrack/class-dattrack-engine.php'),
            plugin_basename(VGT_WPDESK_PATH . 'includes/modules/astra/ki.php'),
            'vgtastra/ki.php',
            'vgtastra-main/ki.php',
        ];
        $active_plugins = get_option('active_plugins', []);
        if (is_array($active_plugins)) {
            $filtered_plugins = array_values(array_diff($active_plugins, $legacy_plugins));
            if ($filtered_plugins !== $active_plugins) {
                update_option('active_plugins', $filtered_plugins, false);
            }
        }

        if (is_multisite()) {
            $network_plugins = get_site_option('active_sitewide_plugins', []);
            if (is_array($network_plugins)) {
                $filtered_network_plugins = array_diff_key($network_plugins, array_flip($legacy_plugins));
                if ($filtered_network_plugins !== $network_plugins) {
                    update_site_option('active_sitewide_plugins', $filtered_network_plugins);
                }
            }
        }
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
        add_action('wp_ajax_vgt_toggle_integrated_module', [$this, 'ajax_toggle_integrated_module']);
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
                WPDeskSettings::set_user_setting($user_id, 'auto_redirect', 'true');

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
        // Recovery must remain reachable outside the multi-window desktop shell.
        // Register under Tools (classic admin) AND under Security Center when present.
        add_management_page(
            'VGT Recovery Center',
            'VGT Recovery',
            WPDeskRecovery::CAPABILITY,
            'vgt-recovery-center',
            [$this, 'render_recovery_center']
        );
        add_submenu_page(
            'vgt-security-center',
            'VGT Recovery Center',
            'Recovery Center',
            WPDeskRecovery::CAPABILITY,
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

        // Shared Operator OS tokens (desk shell + iframe portals share one cast).
        if (class_exists(WPDeskDesignSystem::class)) {
            WPDeskDesignSystem::enqueue('desk');
        }

        // filemtime cache-bust — static Beta versions hid CSS/JS redesigns in browser cache.
        $asset_ver = static function (string $rel): string {
            $path = VGT_WPDESK_PATH . ltrim($rel, '/\\');
            $mt = is_readable($path) ? @filemtime($path) : false;
            return $mt ? ('p8.' . $mt) : '1.0.0-Portal-8';
        };

        wp_enqueue_style('vgt-desktop-core-css', VGT_WPDESK_URL . 'assets/css/desktop-core.css', [], $asset_ver('assets/css/desktop-core.css'));
        wp_enqueue_style('vgt-desktop-windows-css', VGT_WPDESK_URL . 'assets/css/desktop-windows.css', ['vgt-desktop-core-css'], $asset_ver('assets/css/desktop-windows.css'));
        wp_enqueue_style('vgt-desktop-icons-css', VGT_WPDESK_URL . 'assets/css/desktop-icons.css', ['vgt-desktop-core-css'], $asset_ver('assets/css/desktop-icons.css'));
        wp_enqueue_style('vgt-desktop-widgets-css', VGT_WPDESK_URL . 'assets/css/desktop-widgets.css', ['vgt-desktop-core-css'], $asset_ver('assets/css/desktop-widgets.css'));
        wp_enqueue_style('vgt-desktop-apps-css', VGT_WPDESK_URL . 'assets/css/desktop-apps.css', ['vgt-desktop-core-css'], $asset_ver('assets/css/desktop-apps.css'));

        // Register JS components (split windows/menus keep Object.assign engine)
        wp_register_script('vgt-desktop-core', VGT_WPDESK_URL . 'assets/js/modules/desktop-core.js', [], $asset_ver('assets/js/modules/desktop-core.js'), false);
        wp_register_script('vgt-desktop-windows-lifecycle', VGT_WPDESK_URL . 'assets/js/modules/desktop-windows-lifecycle.js', ['vgt-desktop-core'], $asset_ver('assets/js/modules/desktop-windows-lifecycle.js'), false);
        wp_register_script('vgt-desktop-windows', VGT_WPDESK_URL . 'assets/js/modules/desktop-windows.js', ['vgt-desktop-core', 'vgt-desktop-windows-lifecycle'], $asset_ver('assets/js/modules/desktop-windows.js'), false);
        wp_register_script('vgt-desktop-draggable', VGT_WPDESK_URL . 'assets/js/modules/desktop-draggable.js', ['vgt-desktop-core'], $asset_ver('assets/js/modules/desktop-draggable.js'), false);
        wp_register_script('vgt-desktop-icons', VGT_WPDESK_URL . 'assets/js/modules/desktop-icons.js', ['vgt-desktop-core'], $asset_ver('assets/js/modules/desktop-icons.js'), false);
        wp_register_script('vgt-desktop-menus-dock', VGT_WPDESK_URL . 'assets/js/modules/desktop-menus-dock.js', ['vgt-desktop-core'], $asset_ver('assets/js/modules/desktop-menus-dock.js'), false);
        wp_register_script('vgt-desktop-menus', VGT_WPDESK_URL . 'assets/js/modules/desktop-menus.js', ['vgt-desktop-core', 'vgt-desktop-menus-dock'], $asset_ver('assets/js/modules/desktop-menus.js'), false);
        wp_register_script('vgt-desktop-widgets', VGT_WPDESK_URL . 'assets/js/modules/desktop-widgets.js', ['vgt-desktop-core'], $asset_ver('assets/js/modules/desktop-widgets.js'), false);
        wp_register_script('vgt-desktop-spotlight', VGT_WPDESK_URL . 'assets/js/modules/desktop-spotlight.js', ['vgt-desktop-core'], $asset_ver('assets/js/modules/desktop-spotlight.js'), false);
        wp_register_script('vgt-desktop-modals', VGT_WPDESK_URL . 'assets/js/modules/desktop-modals.js', ['vgt-desktop-core'], $asset_ver('assets/js/modules/desktop-modals.js'), false);
        wp_register_script('vgt-desktop-folders', VGT_WPDESK_URL . 'assets/js/modules/desktop-folders.js', ['vgt-desktop-core'], $asset_ver('assets/js/modules/desktop-folders.js'), false);
        wp_register_script('vgt-desktop-wizard', VGT_WPDESK_URL . 'assets/js/modules/desktop-wizard.js', ['vgt-desktop-core'], $asset_ver('assets/js/modules/desktop-wizard.js'), false);
        wp_register_script('vgt-desktop-taskmanager', VGT_WPDESK_URL . 'assets/js/modules/desktop-taskmanager.js', ['vgt-desktop-core'], $asset_ver('assets/js/modules/desktop-taskmanager.js'), false);

        // Enqueue the primary orchestrator that depends on all sub-modules
        wp_enqueue_script('vgt-desktop-js', VGT_WPDESK_URL . 'assets/js/desktop.js', [
            'vgt-desktop-core',
            'vgt-desktop-windows-lifecycle',
            'vgt-desktop-windows',
            'vgt-desktop-draggable',
            'vgt-desktop-icons',
            'vgt-desktop-menus-dock',
            'vgt-desktop-menus',
            'vgt-desktop-widgets',
            'vgt-desktop-spotlight',
            'vgt-desktop-modals',
            'vgt-desktop-folders',
            'vgt-desktop-wizard',
            'vgt-desktop-taskmanager'
        ], $asset_ver('assets/js/desktop.js'), false);

        $user_id       = get_current_user_id();
        $user_settings = WPDeskSettings::get_user_settings($user_id);

        $bans_count = WPDeskBanStore::count_all();

        $sentinel_state = WPDeskSecurity::sentinel_state();
        $sentinel_v7_active = $sentinel_state['v7_active'];
        $sentinel_active = $sentinel_state['active'];

        wp_localize_script('vgt-desktop-core', 'vgtConfig', [
            'ajaxUrl'      => admin_url('admin-ajax.php'),
            'adminUrl'     => admin_url(),
            'version'      => defined('VGT_WPDESK_VERSION') ? VGT_WPDESK_VERSION : '2.0.0-beta.1',
            'versionLabel' => defined('VGT_WPDESK_VERSION_LABEL') ? VGT_WPDESK_VERSION_LABEL : 'V2.0 Beta v1',
            'nonce'        => wp_create_nonce('vgt_desktop_action'),
            'toggleNonce'  => wp_create_nonce('vgt_toggle_desktop'),
            'userSettings' => $user_settings,
            'sentinelEnabled' => $sentinel_active,
            'sentinelBans'    => $bans_count,
            'isSentinelV7'    => $sentinel_v7_active,
            'sentinelMode'    => $sentinel_state['mode'],
            'superkeyActive'  => WPDeskSecurity::throne_guard_active(),
            'dattrackEnabled' => self::is_integrated_module_enabled('dattrack') && !$sentinel_v7_active && (get_option('vgt_dattrack_enabled') === 'true'),
            'integratedModules' => self::integrated_module_statuses(),
            'apps'            => $this->apps
        ]);
    }

    /**
     * REDIRECT & BYPASS SAFETY CONTROLLER
     */
    public function handle_iframe_restrictions(): void
    {
        // Recovery must remain reachable outside the multi-window desktop shell.
        $page_slug = isset($_GET['page']) ? sanitize_key((string) $_GET['page']) : '';
        $view_slug = isset($_GET['view']) ? sanitize_key((string) $_GET['view']) : '';
        if ($page_slug === 'vgt-recovery-center') {
            return;
        }
        if ($page_slug === 'vgt-security-center' && $view_slug === 'recovery') {
            return;
        }

        $user_id = get_current_user_id();

        if (isset($_GET['vgt_action']) && $user_id) {
            $nonce = $_GET['_wpnonce'] ?? '';
            if (!wp_verify_nonce($nonce, 'vgt_toggle_desktop')) {
                wp_die(esc_html__('Sicherheitsueberpruefung (CSRF-Schutz) fehlgeschlagen.', 'vgt-wp-desk'), '', ['response' => 403]);
            }

            $cookie_options = [
                'path'     => COOKIEPATH,
                'domain'   => COOKIE_DOMAIN,
                'secure'   => is_ssl(),
                'httponly' => true,
                'samesite' => 'Strict'
            ];

            if ($_GET['vgt_action'] === 'disable_desk') {
                WPDeskSettings::set_user_setting($user_id, 'auto_redirect', 'false');

                $cookie_options['expires'] = time() + (86400 * 30);
                setcookie('vgt_desk_bypass', '1', $cookie_options);
                wp_safe_redirect(admin_url('index.php?vgt_bypass=1'));
                exit;
            }
            if ($_GET['vgt_action'] === 'enable_desk') {
                WPDeskSettings::set_user_setting($user_id, 'auto_redirect', 'true');

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

        // Accent tokens + chromeless chrome. Screen layouts live in vgt-portal-screens.css
        // (enqueued by IframeTransformer with filemtime cache-bust).
        echo '<style nonce="' . (function_exists('vgt_get_csp_nonce') ? esc_attr(vgt_get_csp_nonce()) : '') . '" id="vgt-chromeless-accent">
            :root {
                --vgt-accent: ' . esc_html($color['main']) . ';
                --vgt-accent-hover: ' . esc_html($color['hover']) . ';
                --vgt-accent-rgba15: ' . esc_html($color['rgba15']) . ';
                --vgt-accent-rgba8: ' . esc_html($color['rgba8']) . ';
            }
            #adminmenumain, #adminmenuback, #adminmenuwrap, #wpadminbar, #wpfooter,
            .update-nag, #screen-meta-links, #contextual-help-link-wrap, #wp-admin-bar-root-default {
                display: none !important;
            }
            html, html.wp-toolbar { padding-top: 0 !important; margin-top: 0 !important; height: 100vh !important; background: #070b14 !important; }
            body { background: #070b14 !important; }
            body.admin-bar #wpcontent, #wpcontent, #wpbody, .wrap {
                margin-left: 0 !important; margin-right: 0 !important; padding: 16px !important;
                background: #070b14 !important; color: #cbd5e1 !important; min-height: 100vh !important;
                box-sizing: border-box !important;
            }
            a { color: var(--vgt-accent) !important; }
            a:hover { color: var(--vgt-accent-hover) !important; }
            ::-webkit-scrollbar { width: 8px; height: 8px; }
            ::-webkit-scrollbar-track { background: #070b14; }
            ::-webkit-scrollbar-thumb { background: #1e293b; border-radius: 99px; border: 2px solid #070b14; }
            .postbox, .card, .welcome-panel {
                background: #0f172a !important; border: 1px solid #1e293b !important; border-radius: 12px !important;
            }
            input[type="text"], input[type="search"], input[type="number"], input[type="password"],
            input[type="email"], textarea, select {
                background-color: #0f172a !important; border: 1px solid #334155 !important;
                color: #f1f5f9 !important; border-radius: 6px !important;
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

        $page = isset($_GET['page']) ? sanitize_key((string) $_GET['page']) : 'vgt-recovery-center';
        $view = isset($_GET['view']) ? sanitize_key((string) $_GET['view']) : '';

        $redirect_url = admin_url('admin.php');
        $redirect_url = add_query_arg('page', $page !== '' ? $page : 'vgt-recovery-center', $redirect_url);
        if ($view !== '') {
            $redirect_url = add_query_arg('view', $view, $redirect_url);
        }

        $action = sanitize_key((string) ($_POST['recovery_action'] ?? ''));
        $nonce_ok = isset($_POST[WPDeskRecovery::NONCE_FIELD])
            && (bool) wp_verify_nonce((string) $_POST[WPDeskRecovery::NONCE_FIELD], WPDeskRecovery::NONCE_ACTION);
        $auth = WPDeskRecovery::authorize_action(
            $action,
            current_user_can(WPDeskRecovery::CAPABILITY),
            $nonce_ok
        );

        if (!$auth['ok']) {
            if ($auth['code'] === 'capability') {
                wp_die(esc_html__('Zu dieser Aktion besitzen Sie nicht genuegend Rechte.', 'vgt-wp-desk'));
            }
            $redirect_url = add_query_arg('vgt_recovery_err', $auth['code'] === 'invalid_nonce' ? 'invalid_nonce' : 'invalid_action', $redirect_url);
            wp_safe_redirect($redirect_url);
            exit;
        }

        $message = '';

        if ($action === WPDeskRecovery::ACTION_FORCE_CLASSIC) {
            $spec = WPDeskRecovery::force_classic_cookie_spec();
            $cookie_options = [
                'expires'  => time() + (int) $spec['ttl_seconds'],
                'path'     => COOKIEPATH,
                'domain'   => COOKIE_DOMAIN,
                'secure'   => is_ssl(),
                'httponly' => true,
                'samesite' => 'Strict',
            ];
            setcookie($spec['name'], $spec['value'], $cookie_options);
            $_COOKIE[$spec['name']] = $spec['value'];
            WPDeskSecurity::audit_control_action('recovery_force_classic', ['ttl' => (string) $spec['ttl_seconds']]);
            $message = WPDeskRecovery::ACTION_FORCE_CLASSIC;
        } elseif ($action === WPDeskRecovery::ACTION_DISABLE_REDIRECT) {
            $user_id = get_current_user_id();
            WPDeskSettings::set_user_setting($user_id, 'auto_redirect', 'false');

            $cookie_options = [
                'expires'  => time() - 3600,
                'path'     => COOKIEPATH,
                'domain'   => COOKIE_DOMAIN,
                'secure'   => is_ssl(),
                'httponly' => true,
                'samesite' => 'Strict',
            ];
            setcookie('vgt_desk_bypass', '', $cookie_options);
            unset($_COOKIE['vgt_desk_bypass']);

            WPDeskSecurity::audit_control_action('recovery_disable_redirect', ['user_id' => (string) $user_id]);
            $message = WPDeskRecovery::ACTION_DISABLE_REDIRECT;
        } elseif ($action === WPDeskRecovery::ACTION_DISABLE_DATTRACK) {
            update_option('vgt_dattrack_enabled', 'false');
            WPDeskSecurity::audit_control_action('recovery_disable_dattrack', []);
            $message = WPDeskRecovery::ACTION_DISABLE_DATTRACK;
        } elseif ($action === WPDeskRecovery::ACTION_EXPORT_DIAGNOSTICS) {
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
        if (!current_user_can(WPDeskRecovery::CAPABILITY)) {
            wp_die(esc_html__('Zu dieser Aktion besitzen Sie nicht genuegend Rechte.', 'vgt-wp-desk'));
        }

        if (class_exists(WPDeskDesignSystem::class)) {
            WPDeskDesignSystem::enqueue('recovery');
        }

        $message = '';
        $error = '';

        if (isset($_GET['vgt_recovery_msg'])) {
            $msg_key = sanitize_key($_GET['vgt_recovery_msg']);
            if ($msg_key === 'force_classic') {
                $message = 'Klassische Ansicht wurde erzwungen (Bypass Cookie fuer 30 Tage gesetzt).';
            } elseif ($msg_key === 'disable_redirect') {
                $message = 'Auto-Redirect wurde fuer Ihren Account deaktiviert.';
            } elseif ($msg_key === 'disable_dattrack') {
                $message = 'Dattrack Telemetrie wurde global deaktiviert.';
            }
        }

        if (isset($_GET['vgt_recovery_err'])) {
            $err_key = sanitize_key($_GET['vgt_recovery_err']);
            if ($err_key === 'invalid_nonce') {
                $error = 'Ungueltiger CSRF Token.';
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
        $redacted = empty($_POST['diagnostics_redacted']) || sanitize_key((string)wp_unslash($_POST['diagnostics_redacted'])) !== '0';
        $active_plugins = get_option('active_plugins', []);
        $theme = wp_get_theme();
        $user_id = get_current_user_id();
        $superkey_hash = get_user_meta($user_id, 'mcp_superkey_hash', true);
        if (empty($superkey_hash)) {
            $superkey_hash = get_option('mcp_superkey_hash', '');
        }
        $sentinel_state = WPDeskSecurity::sentinel_state();
        $sentinel_v7_active = $sentinel_state['v7_active'];

        $diagnostics = [
            'sensitivity' => 'confidential-security-diagnostics',
            'redacted' => $redacted,
            'timestamp' => current_time('mysql'),
            'wp_version' => $redacted ? 'redacted' : get_bloginfo('version'),
            'php_version' => $redacted ? 'redacted' : PHP_VERSION,
            'server_software' => $redacted ? 'redacted' : sanitize_text_field((string)($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown')),
            'theme' => [
                'name' => $theme->get('Name'),
                'version' => $redacted ? 'redacted' : $theme->get('Version')
            ],
            'plugins' => $redacted ? ['count' => is_array($active_plugins) ? count($active_plugins) : 0] : $active_plugins,
            'throne_guard' => [
                'active' => WPDeskSecurity::throne_guard_active(),
            ],
            'sentinel' => [
                'enabled' => $sentinel_state['active'],
                'mode' => $sentinel_state['mode'],
                'v7_active' => $sentinel_v7_active
            ],
            'dattrack' => [
                'enabled' => get_option('vgt_dattrack_enabled') === 'true',
                'has_keys' => file_exists(wp_upload_dir()['basedir'] . '/.vgt-keys/master.php')
            ]
        ];
        
        nocache_headers();
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('Content-Disposition: attachment; filename="vgt-diagnostics-' . date('Y-m-d-His') . '.json"');
        echo wp_json_encode($diagnostics, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
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
