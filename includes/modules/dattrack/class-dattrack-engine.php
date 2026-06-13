<?php
/**
 * VGT Dattrack: Sovereign Analytics Engine Integration
 * STATUS: 💠 DIAMANT VGT SUPREME
 */

declare(strict_types=1);

namespace VisionGaia\WPDesk;

if (!defined('ABSPATH')) {
    exit;
}

// Require module files conditionally to prevent name clashes with other active plugins
if (!class_exists('VGT_Crypto_Desk')) {
    require_once VGT_WPDESK_PATH . 'includes/modules/dattrack/class-crypto.php';
}
if (!class_exists('VGT_Collector_Desk')) {
    require_once VGT_WPDESK_PATH . 'includes/modules/dattrack/class-collector.php';
}
if (!class_exists('VGT_Dashboard_Desk')) {
    require_once VGT_WPDESK_PATH . 'includes/modules/dattrack/class-dashboard.php';
}
if (!class_exists('VGT_Aggregator_Desk')) {
    require_once VGT_WPDESK_PATH . 'includes/modules/dattrack/class-aggregator.php';
}

if (!class_exists('VisionGaia\\WPDesk\\VGT_Dattrack_Engine')) {
final class VGT_Dattrack_Engine {

    public static function boot(): void {
        // Tracking Endpoints
        add_action('wp_ajax_vgt_sync_pulse', [\VGT_Collector_Desk::class, 'intercept']);
        add_action('wp_ajax_nopriv_vgt_sync_pulse', [\VGT_Collector_Desk::class, 'intercept']);
        
        // Cron Engines
        add_action('vgt_dt_hourly_rollup', [\VGT_Aggregator_Desk::class, 'run_rollup']);
        add_action('vgt_dt_aegis_rotation', [\VGT_Crypto_Desk::class, 'execute_aegis_protocol']);
        
        // VGT SUPREME: Dedicated Backend Routes
        add_action('admin_post_vgt_sync', [\VGT_Dashboard_Desk::class, 'process_live_sync']);
        add_action('admin_post_vgt_export_csv', [\VGT_Dashboard_Desk::class, 'stream_csv']);
        add_action('admin_post_vgt_export_pdf', [\VGT_Dashboard_Desk::class, 'render_print_view']);
        
        // UI Endpoints
        add_action('admin_menu', [self::class, 'construct_command_center']);
        add_action('wp_enqueue_scripts', [self::class, 'enqueue_frontend_assets']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_backend_assets']);
        add_action('wp_footer', [self::class, 'inject_micro_consent_ui'], 9999);
        
        // DSGVO Opt-Out Node
        add_shortcode('vgt_dattrack_optout', [self::class, 'render_privacy_control_node']);

        // Self-Genesis of tables if enabled and not yet created
        if (get_option('vgt_dattrack_enabled') === 'true') {
            if (!get_option('vgt_dattrack_tables_created')) {
                self::system_genesis();
                update_option('vgt_dattrack_tables_created', 'true');
            }
        }
    }

    public static function system_genesis(): void {
        global $wpdb;
        \VGT_Crypto_Desk::init_vault();

        $table_vault = $wpdb->prefix . 'vgt_dattrack_vault';
        $table_stats = $wpdb->prefix . 'vgt_dattrack_stats';
        $charset_collate = $wpdb->get_charset_collate();

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $sql_vault = "CREATE TABLE IF NOT EXISTS {$table_vault} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            ip_hash VARCHAR(64) NOT NULL,
            payload LONGTEXT NOT NULL,
            iv VARCHAR(64) NOT NULL,
            auth_tag VARCHAR(64) NOT NULL,
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY time_index (timestamp)
        ) $charset_collate;";

        $sql_stats = "CREATE TABLE IF NOT EXISTS {$table_stats} (
            stat_date DATE NOT NULL,
            events INT UNSIGNED NOT NULL DEFAULT 0,
            unique_users INT UNSIGNED NOT NULL DEFAULT 0,
            paths LONGTEXT NOT NULL,
            PRIMARY KEY  (stat_date)
        ) $charset_collate;";

        dbDelta($sql_vault);
        dbDelta($sql_stats);

        if (!wp_next_scheduled('vgt_dt_hourly_rollup')) {
            wp_schedule_event(time(), 'hourly', 'vgt_dt_hourly_rollup');
        }
        
        if (!wp_next_scheduled('vgt_dt_aegis_rotation')) {
            wp_schedule_event(time() + (30 * DAY_IN_SECONDS), 'monthly', 'vgt_dt_aegis_rotation');
        }
    }

    public static function system_halt(): void {
        wp_clear_scheduled_hook('vgt_dt_hourly_rollup');
        wp_clear_scheduled_hook('vgt_dt_aegis_rotation');
    }

    public static function construct_command_center(): void {
        add_submenu_page(
            'vgt-security-center',
            'VGT Dattrack Vault',
            'Dattrack',
            'manage_options',
            'vgt-dattrack',
            [\VGT_Dashboard_Desk::class, 'render_sovereign_dashboard']
        );
    }

    public static function generate_site_token(): string {
        $secret = \VGT_Crypto_Desk::get_master_key();
        if (empty($secret)) {
            $secret = wp_salt('nonce');
        }
        $action = 'vgt_dt_pulse';
        $tick = ceil(time() / (12 * HOUR_IN_SECONDS));
        return hash_hmac('sha256', $action . '|' . $tick . '|' . home_url(), $secret);
    }

    public static function enqueue_frontend_assets(): void {
        if (is_admin()) return;
        if (get_option('vgt_dattrack_enabled') !== 'true') return;
        
        wp_enqueue_style('vgt-dt-consent-css', VGT_WPDESK_URL . 'assets/css/dattrack-consent.css', [], '1.4.0');
        wp_enqueue_script('vgt-dt-consent-js', VGT_WPDESK_URL . 'assets/js/dattrack-consent.js', [], '1.4.0', true);

        wp_localize_script('vgt-dt-consent-js', 'vgtConfig', [
            'endpoint' => admin_url('admin-ajax.php'),
            'token'    => self::generate_site_token()
        ]);
    }

    public static function enqueue_backend_assets(string $hook): void {
        $is_dt_page = ($hook === 'toplevel_page_vgt-dattrack') || 
                      ($hook === 'toplevel_page_vgt-security-center' && isset($_GET['view']) && $_GET['view'] === 'dattrack');
        if (!$is_dt_page) return;
        
        wp_enqueue_style('vgt-dt-dashboard-css', VGT_WPDESK_URL . 'assets/css/dattrack-dashboard.css', [], '1.4.0');
        wp_enqueue_script('vgt-dt-dashboard-js', VGT_WPDESK_URL . 'assets/js/dattrack-dashboard.js', [], '1.4.0', true);
        
        $metrics = [
            'today'     => ['events' => 0, 'unique_users' => 0, 'paths' => []],
            'yesterday' => ['events' => 0, 'unique_users' => 0, 'paths' => []],
            'last7'     => ['events' => 0, 'unique_users' => 0, 'paths' => []],
            'all'       => ['events' => 0, 'unique_users' => 0, 'paths' => [], 'timeline_chart' => []]
        ];
        if (class_exists('\\VGT_Dashboard_Desk') && method_exists('\\VGT_Dashboard_Desk', 'get_vault_metrics')) {
            $metrics = \VGT_Dashboard_Desk::get_vault_metrics();
        }
        wp_localize_script('vgt-dt-dashboard-js', 'vgtDashboardData', [
            'metrics' => $metrics
        ]);
    }

    public static function inject_micro_consent_ui(): void {
        if (is_admin()) return;
        if (get_option('vgt_dattrack_enabled') !== 'true') return;
        ?>
        <div id="vgt-dt-vault">
            <h4>VGT DATTRACK SENSOR</h4>
            <p>Wollen Sie helfen, anonyme Leistungsstatistiken zur Systemoptimierung zu sammeln? (End-to-End verschlüsselt)</p>
            <div class="vgt-dt-actions">
                <button class="vgt-btn vgt-btn-accept" id="vgt-dt-accept">Helfen</button>
                <button class="vgt-btn vgt-btn-deny" id="vgt-dt-deny">Nein danke</button>
            </div>
        </div>
        <?php
    }

    public static function render_privacy_control_node(): string {
        return '
        <div class="vgt-privacy-node" id="vgt-privacy-node">
            <div class="vgt-pn-header">
                <div class="vgt-pn-indicator" id="vgt-pn-indicator"></div>
                <div class="vgt-pn-info">
                    <span class="vgt-pn-title">VGT Telemetry Control</span>
                    <span class="vgt-pn-status" id="vgt-pn-status">Initialisiere Krypto-State...</span>
                </div>
            </div>
            <button class="vgt-pn-btn" id="vgt-pn-toggle-btn" disabled>Bitte warten</button>
        </div>';
    }
}
}
