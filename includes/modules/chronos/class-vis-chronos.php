<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

/**
 * MODULE: CHRONOS (APEX FUSION V3.0)
 * Status: ROBUST / TIME-SLICING (WP.ORG COMPLIANT)
 * Logic: Asynchrone State-Machine. Zerlegt Scans in mikroskopische Batches, 
 * um PHP-Timeouts zu umgehen. Resistent gegen Server-Restarts.
 */
class VGTS_Chronos {

    const EVENT_HOURLY = 'vgts_hourly_scan_event';
    const EVENT_STEP   = 'vgts_scan_step_event';
    const LOCK_NAME    = 'vgts_scan_active_lock';
    const STATE_NAME   = 'vgts_scan_progress_state';
    
    // Maximale Laufzeit pro Cron-Slice in Sekunden (Konservativ)
    private int $time_budget = 20; 

    public function __construct() {
        add_filter('cron_schedules', [$this, 'add_custom_intervals']);
        
        // Der stündliche Trigger (Startschuss)
        add_action(self::EVENT_HOURLY, [$this, 'initiate_scan']);
        
        // Der Worker-Step (Die eigentliche Arbeit)
        add_action(self::EVENT_STEP, [$this, 'process_scan_slice']);
        
        // Selbstheilung bei Initialisierung
        if (!wp_next_scheduled(self::EVENT_HOURLY)) {
            wp_schedule_event(time(), 'vgts_hourly', self::EVENT_HOURLY);
        }
    }

    public function add_custom_intervals(array $schedules): array {
        $schedules['vgts_hourly'] = [
            'interval' => 3600,
            // [WP.ORG FIXED]: Strict i18n Domain & Escaping applied
            'display'  => esc_html__('Every Hour (VGT Sentinel)', 'vgt-sentinel-ce')
        ];
        return $schedules;
    }

    /**
     * INITIALISIERUNG (Trigger)
     * Prüft Locks und startet den Prozess.
     */
    public function initiate_scan(): void {
        // 1. Check Lock: Läuft bereits ein Scan?
        $lock = get_transient(self::LOCK_NAME);
        
        if ($lock) {
            // Selbstheilung: Wenn Lock älter als 30 Minuten, ist der Prozess wohl gestorben.
            if ((time() - (int)$lock) > 1800) {
                error_log('VISIONGAIA CHRONOS: Stale lock detected. Resetting system.');
                $this->reset_system();
            } else {
                return; // Scan läuft noch aktiv, wir brechen ab.
            }
        }

        // 2. Lock setzen & State resetten
        set_transient(self::LOCK_NAME, time(), 3600); // Max 1 Stunde Lock
        delete_option(self::STATE_NAME);

        // 3. Ersten Schritt anstoßen (sofort)
        $this->schedule_next_step();
    }

    /**
     * WORKER (Slice)
     * Arbeitet so viel ab wie möglich und plant sich dann selbst neu.
     */
    public function process_scan_slice(): void {
        // Safety: Timeout Limits anheben, falls möglich (ohne @-Suppression)
        if (function_exists('set_time_limit') && !in_array('set_time_limit', explode(',', ini_get('disable_functions')), true)) {
            set_time_limit(60);
        }
        if (function_exists('ignore_user_abort')) {
            ignore_user_abort(true);
        }

        // Load State
        $state_data = get_option(self::STATE_NAME, [
            'offset'        => 0, 
            'current_state' => []
        ]);

        $offset        = (int) $state_data['offset'];
        $current_state = (array) $state_data['current_state'];

        // Initialisiere Scanner Engine
        if (!class_exists('VGTS_Scanner_Engine')) {
            require_once VGTS_PATH . 'includes/scanner/class-vis-scanner-engine.php';
        }
        $scanner = new VGTS_Scanner_Engine();

        $start_time   = microtime(true);
        $finished     = false;
        $final_result = null;

        // BATCH LOOP innerhalb des Time-Budgets
        while (true) {
            // Check Time Budget
            if ((microtime(true) - $start_time) > $this->time_budget) {
                // Zeit abgelaufen -> Zustand speichern und raus
                break;
            }

            // Update Lock Timestamp (Heartbeat)
            set_transient(self::LOCK_NAME, time(), 3600);

            // Run Batch
            $result = $scanner->perform_scan_batch($offset, $current_state);

            if (isset($result['status']) && $result['status'] === 'processing') {
                $offset        = (int) $result['offset'];
                $current_state = (array) $result['current_state'];
                // Kurze Pause für CPU-Entlastung
                usleep(50000); 
            } else {
                // DONE
                $finished     = true;
                $final_result = $result;
                break;
            }
        }

        if ($finished) {
            // Scan beendet -> Aufräumen
            $this->finalize_process((array) $final_result);
        } else {
            // Scan nicht beendet -> Zustand speichern und Wiedervorlage
            update_option(self::STATE_NAME, [
                'offset'        => $offset,
                'current_state' => $current_state // Hinweis: Bei >100k Dateien sollte dies in eine Temp-DB-Tabelle ausgelagert werden
            ], false); // No Autoload!
            
            $this->schedule_next_step();
        }
    }

    private function schedule_next_step(): void {
        // Schedule single event as soon as possible
        wp_schedule_single_event(time() + 1, self::EVENT_STEP);
    }

    private function finalize_process(array $result): void {
        $this->reset_system();

        // Alarmierung nur bei echten Problemen
        if (isset($result['status']) && $result['status'] === 'warning') {
            $this->trigger_alert($result);
        }
    }

    private function reset_system(): void {
        delete_transient(self::LOCK_NAME);
        delete_option(self::STATE_NAME);
        wp_clear_scheduled_hook(self::EVENT_STEP);
    }

    private function trigger_alert(array $result): void {
        $to      = get_option('admin_email');
        // [WP.ORG COMPLIANCE]: i18n support for email content
        $subject = esc_html__('[ALERT] VGT Sentinel: Integrity Anomaly Detected', 'vgt-sentinel-ce');
        
        $timestamp = isset($result['timestamp']) ? sanitize_text_field($result['timestamp']) : current_time('mysql');
        $count     = isset($result['changes']) ? count($result['changes']) : 0;
        
        // Ensure menu slug is synchronized
        $dashboard_url = admin_url('admin.php?page=vgts-sentinel');

        $body  = esc_html__('WARNING: System integrity violation detected.', 'vgt-sentinel-ce') . "\n";
        $body .= sprintf(esc_html__('Timestamp: %s', 'vgt-sentinel-ce'), $timestamp) . "\n\n";
        $body .= sprintf(esc_html__('Modified files count: %d', 'vgt-sentinel-ce'), $count) . "\n";
        $body .= sprintf(esc_html__('Please verify immediately in the dashboard: %s', 'vgt-sentinel-ce'), $dashboard_url) . "\n";

        wp_mail($to, $subject, $body, ['Content-Type: text/plain; charset=UTF-8', 'X-Priority: 1']);
    }
}