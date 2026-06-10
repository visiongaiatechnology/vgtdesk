<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * MODULE: GHOST TRAP (The Bait)
 * Status: PLATIN STATUS (WP.ORG COMPLIANT)
 * Architecture: Deception Grid & Tarpitting
 * Logic: Platziert hochattraktive, simulierte Vulnerability-Endpoints.
 * Der Zugriff führt zum atomaren Hard-Ban und einer psychologischen Täuschung (Mimicry).
 */
class VGTS_Ghost_Trap {

    /**
     * VGT DOKTRIN: Die 5 Luxusgüter der Hacker.
     * @var array
     */
    private array $baits = [
        '.env.backup',
        'wp-config.old.php',
        'db-dump-master.sql.php',
        'admin-shell-console.php',
        'debug-logs-temp.php'
    ];

    public function __construct() {
        // Idempotentes Deployment im Admin-Kontext
        add_action('admin_init', [$this, 'deploy_matrix']);
        
        // Priority 1: Sofortige Terminierung, bevor WP Core vollständig bootet
        add_action('plugins_loaded', [$this, 'check_trap_trigger'], 1);
    }

    /**
     * Streut das Deception-Grid in das Dateisystem.
     */
    public function deploy_matrix(): void {
        $root = wp_normalize_path(ABSPATH);

        foreach ($this->baits as $bait) {
            $path = $root . '/' . $bait;

            if (!file_exists($path)) {
                $this->forge_artifact($path, $bait);
            }
        }
    }

    /**
     * Generiert den Honeypot-Payload. Minimalistischer Footprint.
     */
    private function forge_artifact(string $path, string $bait_name): void {
        $wp_load = wp_normalize_path(ABSPATH . 'wp-load.php');
        
        // VGT HARDENING: Payload lädt nur das Nötigste, um den Core-Hook auszulösen
        $content = "<?php\n"
                 . "/** VGT OMEGA ARTIFACT */\n"
                 . "define('VGTS_TRAP_ACTIVE', true);\n"
                 . "define('VGTS_TRAP_VECTOR', '" . addslashes($bait_name) . "');\n"
                 . "require_once('" . addslashes($wp_load) . "');\n";

        // File-Locking um Korruption durch parallele Prozesse zu verhindern
        file_put_contents($path, $content, LOCK_EX);
        chmod($path, 0644);
    }

    /**
     * Der Fang-Mechanismus (The Snap).
     */
    public function check_trap_trigger(): void {
        if (defined('VGTS_TRAP_ACTIVE') && VGTS_TRAP_ACTIVE) {
            
            global $wpdb;
            
            // IP Resolution via gehärtetem Netzwerk-Kernel
            $raw_ip = class_exists('VGTS_Network') ? VGTS_Network::resolve_true_ip() : ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
            $ip = sanitize_text_field(wp_unslash($raw_ip));
            
            $vector = defined('VGTS_TRAP_VECTOR') ? (string) VGTS_TRAP_VECTOR : 'unknown_artifact';
            
            // 1. ATOMIC BAN PROTOCOL
            $table_name = defined('VGTS_TABLE_BANS') ? VGTS_TABLE_BANS : 'vgts_apex_bans';
            $table = $wpdb->prefix . $table_name;
            $reason = sprintf('GHOST TRAP TRIGGERED [VECTOR: %s]', sanitize_text_field($vector));
            $uri = esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'] ?? '/unknown'));

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->query($wpdb->prepare(
                "INSERT IGNORE INTO {$table} (ip, reason, banned_at, request_uri) VALUES (%s, %s, %s, %s)",
                $ip, 
                $reason, 
                current_time('mysql'), 
                $uri
            ));

            // 2. ALERTING (i18n compliant)
            $admin_email = (string) get_option('admin_email');
            if (!empty($admin_email)) {
                $subject = sprintf(
                    /* translators: %s: The attacker's IP address */
                    esc_html__('[VGT APEX] TRAP SNAP: %s', 'vgt-sentinel-ce'),
                    $ip
                );
                $body = sprintf(
                    /* translators: 1: Attacker IP, 2: Trap vector name */
                    esc_html__("A malicious scanner (%1\$s) tried to access the honeypot artifact: %2\$s.\n\nThe IP has been permanently banned from the server.", 'vgt-sentinel-ce'),
                    $ip,
                    $vector
                );
                wp_mail($admin_email, $subject, $body);
            }

            // 3. TARPITTING & MIMICRY (Anti-Forensik)
            usleep(random_int(400000, 900000)); 

            if (!headers_sent()) {
                header('Connection: Close');
                header('X-Powered-By: PHP/5.4.16'); // Desinformation
                http_response_code(500);
                header('Content-Type: text/html; charset=UTF-8');
            }

            // Fake Stack-Trace um den Bot in Sicherheit zu wiegen (Psychologische Mimikry)
            die("<b>Fatal error</b>: Uncaught PDOException: SQLSTATE[HY000] [1040] Too many connections in /var/www/html/core/db.php:42\nStack trace:\n#0 {main}\n  thrown in <b>/var/www/html/core/db.php</b> on line <b>42</b>");
        }
    }
}