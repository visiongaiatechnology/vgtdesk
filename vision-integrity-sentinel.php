<?php
/**
 * Plugin Name: VGT Sentinel CE
 * Description: A zero-trust Web Application Firewall (WAF) and security framework featuring robust brute-force protection, file integrity monitoring, and kernel-level system hardening.
 * Version: 1.7.0
 * Author: VisionGaiaTechnology
 * Author URI: https://visiongaiatechnology.de
 * License: AGPLv3
 * Requires PHP: 7.4
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

// --- SYSTEM KONSTANTEN ---
define('VGTS_VERSION', '1.7.0');
define('VGTS_PATH', plugin_dir_path(__FILE__));
define('VGTS_URL', plugin_dir_url(__FILE__));
define('VGTS_SENTINEL_ICON', VGTS_URL . 'Sentinel.png');

// VAULT ARCHITEKTUR Constants
function vgts_init_constants(): void {
    if (!defined('VGTS_VAULT_DIR')) {
        $upload_dir = wp_upload_dir();
        define('VGTS_VAULT_DIR', $upload_dir['basedir'] . '/vgts-vault-omega');
    }
    if (!defined('VGTS_MANIFEST_FILE')) {
        define('VGTS_MANIFEST_FILE', VGTS_VAULT_DIR . '/integrity_matrix.json');
    }
}

define('VGTS_TABLE_BANS', 'vgts_apex_bans');
define('VGTS_TABLE_LOGS', 'vgts_omega_logs');

// --- INTELLIGENT & HARDENED AUTOLOADER ---
spl_autoload_register(function ($class) {
    if (strpos($class, 'VGTS_') !== 0) {
        return;
    }

    // Streng definierte Whitelist-Map (Kills any dynamic file inclusion / LFI)
    $map = [
        // Core Logic
        'VGTS_Network'               => 'includes/core/class-vis-network.php',
        
        // Security Modules
        'VGTS_Scanner_Engine'        => 'includes/scanner/class-vis-scanner-engine.php',
        'VGTS_Aegis'                 => 'includes/modules/aegis/class-vis-aegis.php',
        'VGTS_Titan'                 => 'includes/modules/titan/class-vis-titan.php',
        'VGTS_Hades'                 => 'includes/modules/hades/class-vis-hades.php',
        'VGTS_Oracle'                => 'includes/modules/oracle/class-vis-oracle.php',
        'VGTS_Chronos'               => 'includes/modules/chronos/class-vis-chronos.php',
        'VGTS_Ghost_Trap'            => 'includes/modules/trap/class-vis-ghost-trap.php',
        'VGTS_Airlock'               => 'includes/modules/airlock/class-vis-airlock.php',
        'VGTS_Cerberus'              => 'includes/modules/cerberus/class-vis-cerberus.php',
        'VGTS_Styx_Lite'             => 'includes/modules/styx/class-vis-styx-lite.php',
        'VGTS_Filesystem_Guard'      => 'includes/modules/filesystem/class-vis-filesystem-guard.php',
        'VGTS_Antibot'               => 'includes/modules/antibot/class-vis-antibot.php',
        
        // UI
        'VGTS_Dashboard_Core'        => 'includes/dashboard/class-vis-dashboard-core.php',
        'VGTS_Dashboard_View'        => 'includes/dashboard/class-vis-dashboard-view.php',
        
        // Compatibility Layer
        'VGTS_Compatibility_Manager' => 'includes/compatibility/class-vis-compatibility-manager.php',
    ];

    if (isset($map[$class])) {
        $file_path = VGTS_PATH . $map[$class];
        
        // Verhindert Directory-Traversal (Doppelte Absicherung)
        $real_path = realpath($file_path);
        if ($real_path !== false && strpos($real_path, realpath(VGTS_PATH)) === 0) {
            require_once $file_path;
        }
    }
});

// --- CENTRAL SECURITY GUARD (CSRF & PRIVILEGE PROTECTION) ---
class VGTS_Security_Guard {

    /**
     * Prüft AJAX- und Standard-Requests auf CSRF (Nonces) und Administratorrechte.
     * Schützt vor unbefugten API-Aufrufen und Einstellungsänderungen.
     */
    public static function verify_privileges(string $action_nonce, string $capability = 'manage_options'): void {
        if (!is_user_logged_in()) {
            wp_die('VISIONGAIATECHNOLOGY SENTINEL: Unauthenticated session blocked.', 'Access Denied', 403);
        }

        if (!current_user_can($capability)) {
            wp_die('VISIONGAIATECHNOLOGY SENTINEL: Insufficient administrative privileges.', 'Access Denied', 403);
        }

        // Suche Nonce in POST, GET oder Request-Header
        $nonce = $_POST['_wpnonce'] ?? $_GET['_wpnonce'] ?? $_SERVER['HTTP_X_WP_NONCE'] ?? '';
        if (empty($nonce) || !wp_verify_nonce((string)$nonce, $action_nonce)) {
            wp_die('VISIONGAIATECHNOLOGY SENTINEL: CSRF Security Token Validation failed. Action rejected.', 'Access Denied', 403);
        }
    }
}

// --- ACTIVATION / SCHEMA (WITH DIRECTORY HARDENING) ---
function vgts_activate_standalone(): void {
    vgts_init_constants();
    global $wpdb;
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    // Erstellung und Härtung des Sicherheits-Tresors
    if (!file_exists(VGTS_VAULT_DIR)) {
        mkdir(VGTS_VAULT_DIR, 0755, true);
    }

    // 1. Silent Guard (Index-Verzeichnisschutz)
    file_put_contents(VGTS_VAULT_DIR . '/index.php', '<?php // SILENCE IS GOLDEN ?>');

    // 2. Apache Hardening (.htaccess mit Cross-Version Support)
    $htaccess_rules = "<Files *>\n" .
                      "    <IfModule mod_authz_core.c>\n" .
                      "        Require all denied\n" .
                      "    </IfModule>\n" .
                      "    <IfModule !mod_authz_core.c>\n" .
                      "        Order Deny,Allow\n" .
                      "        Deny from all\n" .
                      "    </IfModule>\n" .
                      "</Files>";
    file_put_contents(VGTS_VAULT_DIR . '/.htaccess', $htaccess_rules);

    // 3. IIS Hardening (web.config Schutz gegen Datei-Auslesung auf Windows-Servern)
    $iis_config = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
                  '<configuration>' . "\n" .
                  '  <system.webServer>' . "\n" .
                  '    <authorization>' . "\n" .
                  '      <deny users="*" />' . "\n" .
                  '    </authorization>' . "\n" .
                  '  </system.webServer>' . "\n" .
                  '</configuration>';
    file_put_contents(VGTS_VAULT_DIR . '/web.config', $iis_config);

    // Datenbanktabellen initialisieren
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql_bans = "CREATE TABLE " . $wpdb->prefix . VGTS_TABLE_BANS . " (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        ip varchar(45) NOT NULL,
        reason text NOT NULL,
        banned_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        request_uri varchar(255) DEFAULT '' NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY ip (ip),
        KEY banned_at (banned_at)
    ) $charset_collate;";

    $sql_logs = "CREATE TABLE " . $wpdb->prefix . VGTS_TABLE_LOGS . " (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        timestamp datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        module varchar(32) NOT NULL,
        type varchar(32) NOT NULL,
        message text NOT NULL,
        ip varchar(45) NOT NULL,
        severity tinyint(1) DEFAULT 1,
        PRIMARY KEY  (id),
        KEY module_timestamp (module, timestamp)
    ) $charset_collate;";

    dbDelta($sql_bans);
    dbDelta($sql_logs);

    update_option('vgts_db_version', VGTS_VERSION);
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'vgts_activate_standalone');

// Hook for dynamic toggle synchronization (triggers DB/vault creation when activated)
add_action('update_option_vgt_sentinel_enabled', function($old_value, $value) {
    if ($value === 'true') {
        vgts_activate_standalone();
    }
}, 10, 2);

add_action('add_option_vgt_sentinel_enabled', function($option, $value) {
    if ($value === 'true') {
        vgts_activate_standalone();
    }
}, 10, 2);

register_deactivation_hook(__FILE__, function() {
    wp_clear_scheduled_hook('vgts_hourly_scan_event');
    flush_rewrite_rules();
});

// --- DEFENSE-IN-DEPTH: GLOBAL CONFIGURATION SAFEGUARD (CSRF SHIELD) ---
/**
 * Verhindert, dass unbefugte Dritte oder CSRF-Vektoren die globale Konfiguration 
 * des WAF-Systems manipulieren. Agiert als systemweiter Virtual Patching Filter.
 */
add_filter('pre_update_option_vgts_config', function($new_value, $old_value, $option) {
    $is_admin_action = is_admin() || (defined('DOING_AJAX') && DOING_AJAX) || (defined('REST_REQUEST') && REST_REQUEST);

    if ($is_admin_action) {
        // Erfordert administrative Berechtigungen
        if (!current_user_can('manage_options')) {
            wp_die('VISIONGAIATECHNOLOGY SENTINEL: Unauthorized attempt to modify secure core settings.', 'Access Denied', 403);
        }

        $nonce = $_POST['_wpnonce'] ?? $_GET['_wpnonce'] ?? $_SERVER['HTTP_X_WP_NONCE'] ?? '';
        $nonce_valid = false;

        if (!empty($nonce)) {
            // 1. DYNAMISCHES RESOLVING: Prüfe, ob das Nonce zur aktuell gesendeten WP-Einstellungs-Gruppe passt
            if (isset($_POST['option_page'])) {
                $dynamic_action = sanitize_key($_POST['option_page']) . '-options';
                if (wp_verify_nonce((string)$nonce, $dynamic_action)) {
                    $nonce_valid = true;
                }
            }

            // 2. STATISCHER VERIFIER: Fallbacks für AJAX, Custom Pages und direkte POST-Aktionen
            if (!$nonce_valid) {
                $explicit_actions = [
                    'vgts_save_config', // <--- KRITISCH: Erlaubt das Speichern über dein Custom Dashboard!
                    'vgts_secure_settings_update',
                    'vgts_config-options',
                    'vgts_config_group-options',
                    'vgts_option_group-options',
                    'vgts_settings-options',
                    'vgts_settings_group-options',
                    'options-options',
                    'update-options'
                ];
                foreach ($explicit_actions as $action) {
                    if (wp_verify_nonce((string)$nonce, $action)) {
                        $nonce_valid = true;
                        break;
                    }
                }
            }
        }

        // Falls das Nonce auf keinem der Kanäle verifiziert werden konnte -> Blockade!
        if (!$nonce_valid) {
            wp_die('VISIONGAIATECHNOLOGY SENTINEL: Security update blocked. Missing or invalid secure CSRF Nonce.', 'Access Denied', 403);
        }
    }
    return $new_value;
}, 10, 3);

// --- BOOTSTRAP (VGT KERNEL PRIORITY QUEUE) ---
add_action('plugins_loaded', function() {
    vgts_init_constants();
    $enabled = get_option('vgt_sentinel_enabled') === 'true';

    // ==========================================
    // TIER 4: ADMIN DASHBOARD (Immer im Admin laden, damit wir es aktivieren können)
    // ==========================================
    if (is_admin()) {
        new VGTS_Dashboard_Core();
    }

    if (!$enabled) {
        return;
    }

    // Auto-run DB/folder initialization if database version doesn't match
    if (get_option('vgts_db_version') !== VGTS_VERSION) {
        vgts_activate_standalone();
    }
    
    // ==========================================
    // TIER 1: PERIMETER & PAYLOAD DEFENSE (CRITICAL)
    // ==========================================
    new VGTS_Cerberus();
    $options = get_option('vgts_config', []);
    new VGTS_Aegis($options); 

    // ==========================================
    // TIER 2: COMPATIBILITY LAYER
    // ==========================================
    new VGTS_Compatibility_Manager();

    // ==========================================
    // TIER 3: SECONDARY SECURITY MODULES & ENGINE FUSION
    // ==========================================
    new VGTS_Titan($options);
    $hades = new VGTS_Hades($options);
    new VGTS_Styx_Lite($options);
    new VGTS_Airlock();
    new VGTS_Ghost_Trap();
    new VGTS_Chronos();
    
    // V2 ARCHITECTURE: ANTIBOT PROOF-OF-WORK ENGINE
    new VGTS_Antibot($options);
    
}, -9999);
