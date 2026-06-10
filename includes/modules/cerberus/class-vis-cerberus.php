<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

/**
 * MODULE: CERBERUS (The Gatekeeper)
 * STATUS: DIAMANT STATUS (WP.ORG COMPLIANT)
 * KOGNITIVE UPGRADES:
 * - [ WP.ORG FIXED ]: Strict Prefixing (VGTS_).
 * - [ WP.ORG FIXED ]: Sanitization of Superglobals via wp_unslash.
 * - [ WP.ORG FIXED ]: Localization (i18n) & Output Escaping via wp_die.
 * - Filesystem State Tracking: Null DB-Hits während einer Brute-Force Welle.
 * - Anti-Timing Attack Sleep Delays bei positiven Ban-Hits.
 * - Global Perimeter Lockdown (Blockiert die gesamte Website, nicht nur Login).
 * - Atomare Dateioperationen (flock) gegen Concurrency/Race-Condition Bypasses.
 */
class VGTS_Cerberus {

    private int $max_retries = 3;
    private int $lockout_time = 3600; // 1 Stunde Lockout
    private string $vault_dir;

    private static ?self $instance = null;

    public function __construct() {
        $this->vault_dir = defined('VGTS_VAULT_DIR') ? VGTS_VAULT_DIR : wp_upload_dir()['basedir'] . '/vgts-vault-omega';

        // VGT KERNEL FIX: GLOBAL PERIMETER GUARD
        // Feuert bei JEDEM Seitenaufruf. Ist die IP in der DB, stirbt der Request sofort.
        add_action('plugins_loaded', [$this, 'enforce_global_perimeter'], 0);

        // Priority 1: Sofortiges Tarpitting/Blocking vor WP-Auth (Für gezielte Login-Angriffe)
        add_filter('authenticate', [$this, 'enforce_access_control'], 1, 3);
        
        // Tracking von fehlgeschlagenen Logins via Disk I/O
        add_action('wp_login_failed', [$this, 'register_auth_failure']);
    }

    public static function instance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * VGT GLOBAL PERIMETER LOCKDOWN
     * Schützt die komplette Website vor IP-Adressen, die von AEGIS oder Cerberus gebannt wurden.
     */
    public function enforce_global_perimeter(): void {
        if (defined('WP_CLI') && WP_CLI) return;
        if (defined('DOING_CRON') && DOING_CRON) return;

        global $wpdb;
        $raw_ip = class_exists('VGTS_Network') && method_exists('VGTS_Network', 'resolve_true_ip') 
              ? VGTS_Network::resolve_true_ip() 
              : ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
              
        $ip = sanitize_text_field(wp_unslash($raw_ip));
        $table = $wpdb->prefix . (defined('VGTS_TABLE_BANS') ? VGTS_TABLE_BANS : 'vgts_apex_bans');

        // O(1) Lookup: Existiert die IP in der Ban-Liste?
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $is_banned = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE ip = %s LIMIT 1", $ip));

        if ($is_banned) {
            // [WP.ORG COMPLIANCE]: Translable, escaped abort sequence instead of hard die()
            wp_die(
                esc_html__('VISIONGAIA CERBERUS: Access Denied. Your IP has been permanently banned from this network.', 'vgt-sentinel-ce'),
                esc_html__('403 Forbidden', 'vgt-sentinel-ce'),
                ['response' => 403]
            );
        }
    }

    /**
     * ACCESS CONTROL & TARPITTING (Login Guard)
     */
    public function enforce_access_control($user, $username, $password) {
        if (is_wp_error($user)) return $user;

        global $wpdb;
        $raw_ip = class_exists('VGTS_Network') && method_exists('VGTS_Network', 'resolve_true_ip') 
              ? VGTS_Network::resolve_true_ip() 
              : ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
              
        $ip = sanitize_text_field(wp_unslash($raw_ip));
        $table = $wpdb->prefix . (defined('VGTS_TABLE_BANS') ? VGTS_TABLE_BANS : 'vgts_apex_bans');

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $is_banned = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE ip = %s LIMIT 1", $ip));

        if ($is_banned) {
            // TARPITTING: Asymmetrische Verzögerung bindet Threads des Angreifers.
            usleep(random_int(400000, 900000)); 

            return new WP_Error(
                'vgts_banned', 
                wp_kses_post(sprintf(
                    /* translators: %s: The banned IP address */
                    __('<strong>VISIONGAIA CERBERUS:</strong> Access Denied. Integrity matrix compromised by origin IP (%s).', 'vgt-sentinel-ce'),
                    esc_html($ip)
                ))
            );
        }

        return $user;
    }

    /**
     * FILESYSTEM-BASED STATE TRACKING
     * Vermeidet den MySQL Max-Connection Tod auf Shared Hostings.
     * VGT DIAMANT FIX: Vollständig atomare Operationen zur Abwehr von Race Conditions.
     */
    public function register_auth_failure(string $username): void {
        $raw_ip = class_exists('VGTS_Network') && method_exists('VGTS_Network', 'resolve_true_ip') 
              ? VGTS_Network::resolve_true_ip() 
              : ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
              
        $ip = sanitize_text_field(wp_unslash($raw_ip));
        
        // Vault Absicherung [WP.ORG COMPLIANCE: Use wp_mkdir_p instead of @mkdir]
        if (!is_dir($this->vault_dir)) {
            wp_mkdir_p($this->vault_dir);
        }

        $state_file = $this->vault_dir . '/cerb_' . md5($ip) . '.dat';
        $current_time = time();
        
        // VGT SUPREME FIX: Echte Atomare Operation mit flock
        $fp = fopen($state_file, 'c+');
        if ($fp && flock($fp, LOCK_EX)) { // Exklusiver Lock VOR dem Lesen
            $content = stream_get_contents($fp);
            $data = explode(':', $content !== false ? $content : '');
            
            $last_attempt = (int)($data[0] ?? 0);
            $retries = (int)($data[1] ?? 0);
            
            // Lockout Zeit-Fenster evaluieren
            if (($current_time - $last_attempt) > $this->lockout_time) {
                $retries = 0;
            }
            
            $retries++;
            
            if ($retries >= $this->max_retries) {
                // Ausführung des Hard-Bans (Die einzige DB-Interaktion)
                $this->ban_ip($ip, "CERBERUS: Authentication threshold exceeded (Target: " . sanitize_user($username) . ")");
                ftruncate($fp, 0);
                
                // File Cleanup
                flock($fp, LOCK_UN);
                fclose($fp);
                unlink($state_file); 
                return; // Early return because file is closed
            } else {
                ftruncate($fp, 0);
                rewind($fp);
                fwrite($fp, $current_time . ':' . $retries);
            }
            
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }

    /**
     * ATOMIC DB INSERT (Nur bei tatsächlichem Ban)
     * Public API für andere Module (z.B. AEGIS)
     */
    public function ban_ip(string $ip, string $reason): void {
        global $wpdb;
        $table = $wpdb->prefix . (defined('VGTS_TABLE_BANS') ? VGTS_TABLE_BANS : 'vgts_apex_bans');

        // [WP.ORG COMPLIANCE]: URL Escaping & Sanitization
        $uri = substr(esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'] ?? '/wp-login.php')), 0, 255);
        $safe_ip = sanitize_text_field($ip);
        $safe_reason = sanitize_text_field($reason);

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->query($wpdb->prepare(
            "INSERT IGNORE INTO {$table} (ip, reason, banned_at, request_uri) VALUES (%s, %s, %s, %s)",
            $safe_ip, 
            $safe_reason, 
            current_time('mysql'), 
            $uri
        ));
    }
}