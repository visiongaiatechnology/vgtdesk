<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * MODULE: ORACLE (The Sight)
 * STATUS: PLATIN STATUS (WP.ORG COMPLIANT)
 * Führt On-Demand System-Audits durch.
 * Optimiert auf maximale Informationsdichte und Erweiterbarkeit.
 */
class VGTS_Oracle {

    /**
     * Startet den System-Audit-Prozess.
     * * @return array[] Liste der Audit-Ergebnisse
     */
    public function run_prophecy(): array {
        $results = [];

        // 1. CONFIGURATION & FILESYSTEM
        $config_path = ABSPATH . 'wp-config.php';
        $results[] = $this->check(
            is_writable($config_path),
            __('Config Writable', 'vgt-sentinel-ce'),
            __('Critical: wp-config.php is writable!', 'vgt-sentinel-ce'),
            __('Secured (Read-Only).', 'vgt-sentinel-ce')
        );

        $results[] = $this->check(
            file_exists(WP_CONTENT_DIR . '/debug.log'),
            __('Debug Log Exposure', 'vgt-sentinel-ce'),
            __('debug.log is publicly accessible.', 'vgt-sentinel-ce'),
            __('No log file found.', 'vgt-sentinel-ce')
        );

        $results[] = $this->check(
            defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT,
            __('File Editor Status', 'vgt-sentinel-ce'),
            __('Editor deactivated (Secure).', 'vgt-sentinel-ce'),
            __('Editor active (RCE Risk).', 'vgt-sentinel-ce'),
            true
        );

        // 2. DATABASE & USERS
        global $wpdb;
        $results[] = $this->check(
            $wpdb->prefix === 'wp_',
            __('DB Prefix Hardening', 'vgt-sentinel-ce'),
            __('Custom Prefix active.', 'vgt-sentinel-ce'),
            __('Standard "wp_" prefix found (Risk).', 'vgt-sentinel-ce'),
            true
        );
        
        $admin_exists = get_user_by('login', 'admin');
        $results[] = $this->check(
            (bool) $admin_exists,
            __('Default Admin User', 'vgt-sentinel-ce'),
            __('User "admin" exists (Brute-Force Target).', 'vgt-sentinel-ce'),
            __('No standard admin user found.', 'vgt-sentinel-ce')
        );

        // 3. NETWORK & ENVIRONMENT
        $results[] = $this->check(
            is_ssl(),
            __('SSL/TLS Encryption', 'vgt-sentinel-ce'),
            __('Connection encrypted.', 'vgt-sentinel-ce'),
            __('Unencrypted (HTTP).', 'vgt-sentinel-ce'),
            true
        );

        // 4. DIRECTORY LISTING CHECK
        $results[] = $this->check_directory_listing();

        // 5. REST API EXPOSURE
        $auth_header = isset($_SERVER['HTTP_AUTHORIZATION']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_AUTHORIZATION'])) : '';
        $results[] = $this->check(
            !empty($auth_header),
            __('Auth Header Protection', 'vgt-sentinel-ce'),
            __('Authorization Headers detected.', 'vgt-sentinel-ce'),
            __('Headers missing (Potential API restriction).', 'vgt-sentinel-ce'),
            true
        );

        return $results;
    }

    /**
     * Prüft das Vorhandensein von Schutzdateien gegen Directory Listing.
     * * @return array
     */
    private function check_directory_listing(): array {
        $has_protection = file_exists(WPMU_PLUGIN_DIR . '/index.php') || file_exists(WP_CONTENT_DIR . '/index.php');
        return $this->check(
            $has_protection,
            __('Directory Browsing', 'vgt-sentinel-ce'),
            __('Base protection active.', 'vgt-sentinel-ce'),
            __('Potential directory listing (index.php missing).', 'vgt-sentinel-ce'),
            true
        );
    }

    /**
     * Zentraler Logic-Kernel für die Validierung.
     * * @param bool   $condition Die zu prüfende Bedingung
     * @param string $name      Name des Checks
     * @param string $msg_a     Nachricht für Fall A
     * @param string $msg_b     Nachricht für Fall B
     * @param bool   $reverse   Invertiert die Logik (True = Condition ist gut)
     * @return array Formatiertes Ergebnis
     */
    private function check(bool $condition, string $name, string $msg_a, string $msg_b, bool $reverse = false): array {
        $fail = $reverse ? !$condition : $condition;
        return [
            'check'  => $name,
            'status' => $fail ? 'FAIL' : 'PASS',
            'msg'    => $fail ? $msg_a : $msg_b
        ];
    }
}