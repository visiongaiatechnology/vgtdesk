<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * CORE: DASHBOARD ENGINE
 * STATUS: DIAMANT VGT SUPREME
 * Architecture: Clean SoC, Deterministic Asset Routing & Recursive Scan Engine.
 */
class VGTS_Dashboard_Core {

    private ?string $page_hook = null;

    public function __construct() {
        add_action('admin_menu', [$this, 'menu']);
        add_action('admin_init', [$this, 'save_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        
        add_action('wp_ajax_vgts_run_scan', [$this, 'ajax_scan']);
        add_action('wp_ajax_vgts_approve_changes', [$this, 'ajax_approve']);
        add_action('wp_ajax_vgts_dashboard_unban_ip', [self::class, 'handle_unban_ip']);
    }

    public function menu(): void {
        if (!class_exists('VGTS_Dashboard_View')) {
            require_once VGTS_PATH . 'includes/dashboard/class-vis-dashboard-view.php';
        }

        $this->page_hook = add_menu_page(
            'Sentinel', 
            'Sentinel', 
            'manage_options', 
            'vgts-sentinel', 
            [new VGTS_Dashboard_View(), 'render'], 
            defined('VGTS_SENTINEL_ICON') ? VGTS_SENTINEL_ICON : '', 
            99
        );
    }

    public static function handle_unban_ip(): void {
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'vgts_nonce')) {
            wp_send_json_error(esc_html__('VGT SECURITY: Invalid security token.', 'vgt-sentinel-ce'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(esc_html__('VGT SECURITY ALERT: Unauthorized access.', 'vgt-sentinel-ce'));
        }

        $ip = isset($_POST['ip']) ? sanitize_text_field(wp_unslash($_POST['ip'])) : '';
        if (empty($ip) || !filter_var($ip, FILTER_VALIDATE_IP)) {
            wp_send_json_error(esc_html__('VGT KERNEL ERROR: Invalid IP format.', 'vgt-sentinel-ce'));
        }

        global $wpdb;
        $table_name = defined('VGTS_TABLE_BANS') ? VGTS_TABLE_BANS : 'vgts_apex_bans';
        $table_bans = $wpdb->prefix . $table_name;
        
        $deleted = $wpdb->delete($table_bans, ['ip' => $ip]);
        
        if ($deleted !== false) {
            wp_send_json_success(esc_html__('IP successfully unbanned.', 'vgt-sentinel-ce'));
        } else {
            wp_send_json_error(esc_html__('VGT DB ERROR: Unban operation failed.', 'vgt-sentinel-ce'));
        }
    }

    public function enqueue_assets(string $hook): void {
        if ($hook !== $this->page_hook) {
            return;
        }

        // 1. BASE ASSETS
        wp_enqueue_style('vgts-dashboard-css', VGTS_URL . 'assets/css/vgts-dashboard.css', [], VGTS_VERSION);
        wp_enqueue_script('vgts-dashboard-js', VGTS_URL . 'assets/js/vgts-dashboard.js', ['jquery'], VGTS_VERSION, true);
        
        wp_enqueue_style('vgts-sidebar-css', VGTS_URL . 'assets/css/vgts-sidebar.css', ['vgts-dashboard-css'], VGTS_VERSION);
        wp_enqueue_script('vgts-sidebar-js', VGTS_URL . 'assets/js/vgts-sidebar.js', ['vgts-dashboard-js'], VGTS_VERSION, true);
        
        wp_localize_script('vgts-dashboard-js', 'vgtsConfig', [
            'nonce'   => wp_create_nonce('vgts_nonce'),
            'ajaxUrl' => admin_url('admin-ajax.php')
        ]);

        // 2. MODULAR TAB ASSETS
        $active_tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'overview';
        
        $css_file = 'assets/css/vgts-' . $active_tab . '.css';
        $js_file  = 'assets/js/vgts-' . $active_tab . '.js';

        if (file_exists(VGTS_PATH . $css_file)) {
            wp_enqueue_style('vgts-' . $active_tab . '-css', VGTS_URL . $css_file, ['vgts-dashboard-css', 'vgts-sidebar-css'], VGTS_VERSION);
        }

        if (file_exists(VGTS_PATH . $js_file)) {
            wp_enqueue_script('vgts-' . $active_tab . '-js', VGTS_URL . $js_file, ['vgts-dashboard-js', 'vgts-sidebar-js'], VGTS_VERSION, true);
        }
    }

    public function save_settings(): void {
        if (isset($_POST['vgts_save_config'])) {
            $nonce = isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '';
            if (!wp_verify_nonce($nonce, 'vgts_save_config')) {
                return;
            }

            if (!current_user_can('manage_options')) {
                return;
            }

            $current = (array) get_option('vgts_config', []);
            $raw_new = isset($_POST['vgts_config']) && is_array($_POST['vgts_config']) ? wp_unslash($_POST['vgts_config']) : [];
            $new     = map_deep($raw_new, 'sanitize_text_field');
            
            $context = isset($_POST['vgts_context']) ? sanitize_key(wp_unslash($_POST['vgts_context'])) : 'all';

            $scope_map = [
                'aegis'   => ['aegis_enabled'],
                'titan'   => ['titan_enabled', 'titan_disallow_file_edit', 'titan_block_xmlrpc', 'titan_block_rest', 'titan_disable_feeds', 'titan_cleanup_emojis', 'titan_cleanup_embeds'],
                'hades'   => ['hades_enabled'],
                'styx'    => ['styx_kill_telemetry'],
                'airlock' => ['airlock_enabled'],
                'antibot' => ['antibot_enabled', 'antibot_comments', 'antibot_cf7', 'antibot_woo', 'antibot_wpforms', 'antibot_gform']
            ];

            $checkboxes_to_check = $scope_map[$context] ?? [];
            if ($context === 'all') {
                $checkboxes_to_check = array_merge(...array_values($scope_map));
            }

            foreach ($checkboxes_to_check as $cb) {
                $new[$cb] = isset($new[$cb]) ? 1 : 0;
            }

            if (isset($new['aegis_mode'])) $new['aegis_mode'] = sanitize_key($new['aegis_mode']); 
            if (isset($new['aegis_whitelist_ips'])) $new['aegis_whitelist_ips'] = sanitize_textarea_field($new['aegis_whitelist_ips']);
            if (isset($new['aegis_whitelist_uas'])) $new['aegis_whitelist_uas'] = sanitize_textarea_field($new['aegis_whitelist_uas']);

            update_option('vgts_config', array_merge($current, $new));
            
            $request_uri = isset($_SERVER['REQUEST_URI']) ? esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])) : admin_url('admin.php?page=vgts-sentinel');
            wp_redirect(add_query_arg('settings-updated', 'true', $request_uri));
            exit;
        }
    }

    public function ajax_scan(): void {
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'vgts_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error(esc_html__('Unauthorized.', 'vgt-sentinel-ce'));
        }

        $offset = isset($_POST['offset']) ? (int)$_POST['offset'] : 0;
        
        $raw_state = isset($_POST['current_state']) ? wp_unslash($_POST['current_state']) : '{}';
        $decoded_state = json_decode($raw_state, true);
        if (!is_array($decoded_state)) $decoded_state = [];
        $state = map_deep($decoded_state, 'sanitize_text_field');
        
        if (!class_exists('VGTS_Scanner_Engine')) require_once VGTS_PATH . 'includes/scanner/class-vis-scanner-engine.php';
        
        $scanner = new VGTS_Scanner_Engine();
        wp_send_json_success($scanner->perform_scan_batch($offset, $state));
    }

    public function ajax_approve(): void {
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'vgts_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error(esc_html__('Unauthorized.', 'vgt-sentinel-ce'));
        }

        if (!class_exists('VGTS_Scanner_Engine')) require_once VGTS_PATH . 'includes/scanner/class-vis-scanner-engine.php';

        $scanner = new VGTS_Scanner_Engine();
        if ($scanner->regenerate_baseline()) {
            wp_send_json_success(['message' => esc_html__('System Baseline re-indexed.', 'vgt-sentinel-ce')]);
        } else {
            wp_send_json_error(['message' => esc_html__('Re-Index failed.', 'vgt-sentinel-ce')]);
        }
    }
}