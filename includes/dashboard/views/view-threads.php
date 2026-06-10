<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VIEW: THREADS (THREAT RADAR & PSYCHOMETRICS)
 * STATUS: PLATIN VGT STATUS (Hardened & i18n)
 * MODULE: AGGREGATED THREAT TELEMETRY & LIVE STREAM
 * TEXTDOMAIN: vgt-sentinel-ce
 */

global $wpdb;

// 1. Daten-Akquise & Sanitization (Pre-calculated for UI Performance)
$table_logs = defined('VGTS_TABLE_LOGS') ? $wpdb->prefix . VGTS_TABLE_LOGS : $wpdb->prefix . 'vgts_omega_logs';
$table_bans = defined('VGTS_TABLE_BANS') ? $wpdb->prefix . VGTS_TABLE_BANS : $wpdb->prefix . 'vgts_apex_bans';

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$count_aegis = (int) $wpdb->get_var("SELECT COUNT(id) FROM {$table_logs} WHERE module LIKE '%AEGIS%'");
$count_bans  = (int) $wpdb->get_var("SELECT COUNT(id) FROM {$table_bans}");
$count_trap  = (int) $wpdb->get_var("SELECT COUNT(id) FROM {$table_bans} WHERE reason LIKE '%GHOST TRAP%'");

// Gauge Logik: Dynamische Skalierung der Max-Werte
$max_val = max(100, $count_aegis * 1.2, $count_bans * 1.2, $count_trap * 1.5);

$pct_aegis = min(100, (int) round(($count_aegis / $max_val) * 100));
$pct_bans  = min(100, (int) round(($count_bans / $max_val) * 100));
$pct_trap  = min(100, (int) round(($count_trap / $max_val) * 100));

// 2. Data Fusion: Merging Logs and Bans
$query_logs = "SELECT timestamp, module as event_type, message, ip FROM {$table_logs} ORDER BY id DESC LIMIT 6";
$query_bans = "SELECT banned_at as timestamp, 'CERBERUS BAN' as event_type, reason as message, ip FROM {$table_bans} ORDER BY id DESC LIMIT 6";

$raw_logs = $wpdb->get_results($query_logs, ARRAY_A);
$raw_bans = $wpdb->get_results($query_bans, ARRAY_A);
// phpcs:enable

$threat_stream = array_merge($raw_logs ?: [], $raw_bans ?: []);
usort($threat_stream, function($a, $b) {
    return strtotime((string)$b['timestamp']) <=> strtotime((string)$a['timestamp']);
});
$threat_stream = array_slice($threat_stream, 0, 6);

$date_format = get_option('date_format') . ' ' . get_option('time_format');
?>

<div class="vgts-card" style="background: transparent; border: none; padding: 0; box-shadow: none;">
    <h3 style="color: #fff; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
        <span class="dashicons dashicons-performance" style="color: #00e5ff;"></span> 
        <?php esc_html_e('THREAT RADAR & PSYCHOMETRICS', 'vgt-sentinel-ce'); ?>
    </h3>

    <div class="vgts-radar-container">
        
        <!-- AEGIS GAUGE -->
        <div class="vgts-gauge-card vgts-card-aegis">
            <svg viewBox="0 0 36 36" class="vgts-circular-chart">
                <path class="vgts-circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                <path class="vgts-circle" data-pct="<?php echo esc_attr((string)$pct_aegis); ?>" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                <text x="18" y="21.5" class="vgts-percentage" data-count="<?php echo esc_attr((string)$count_aegis); ?>">0</text>
            </svg>
            <div class="vgts-gauge-label"><?php esc_html_e('AEGIS INTERCEPTIONS', 'vgt-sentinel-ce'); ?></div>
            <div class="vgts-gauge-desc"><?php esc_html_e('Neutralized payloads (SQLi, XSS, RCE) at the stream level.', 'vgt-sentinel-ce'); ?></div>
        </div>

        <!-- CERBERUS GAUGE -->
        <div class="vgts-gauge-card vgts-card-cerberus">
            <svg viewBox="0 0 36 36" class="vgts-circular-chart">
                <path class="vgts-circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                <path class="vgts-circle" data-pct="<?php echo esc_attr((string)$pct_bans); ?>" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                <text x="18" y="21.5" class="vgts-percentage" data-count="<?php echo esc_attr((string)$count_bans); ?>">0</text>
            </svg>
            <div class="vgts-gauge-label"><?php esc_html_e('CERBERUS BANS', 'vgt-sentinel-ce'); ?></div>
            <div class="vgts-gauge-desc"><?php esc_html_e('Permanent perimeter blocks (Brute-Force & Attack Vectors).', 'vgt-sentinel-ce'); ?></div>
        </div>

        <!-- GHOST TRAP GAUGE -->
        <div class="vgts-gauge-card vgts-card-trap">
            <svg viewBox="0 0 36 36" class="vgts-circular-chart">
                <path class="vgts-circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                <path class="vgts-circle" data-pct="<?php echo esc_attr((string)$pct_trap); ?>" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                <text x="18" y="21.5" class="vgts-percentage" data-count="<?php echo esc_attr((string)$count_trap); ?>">0</text>
            </svg>
            <div class="vgts-gauge-label"><?php esc_html_e('GHOST TRAP TRIGGERS', 'vgt-sentinel-ce'); ?></div>
            <div class="vgts-gauge-desc"><?php esc_html_e('Successful tarpit traps against automated botnets.', 'vgt-sentinel-ce'); ?></div>
        </div>

        <!-- UPSELL GAUGE (PLATINUM) -->
        <a href="<?php echo esc_url('https://visiongaiatechnology.de/visiongaiadefensehub/'); ?>" target="_blank" style="text-decoration: none;" aria-label="<?php echo esc_attr__('Upgrade to VGT Platinum', 'vgt-sentinel-ce'); ?>">
            <div class="vgts-gauge-card vgts-card-upsell">
                <span class="dashicons dashicons-lock vgts-upsell-lock"></span>
                <svg viewBox="0 0 36 36" class="vgts-circular-chart">
                    <path class="vgts-circle-bg" stroke-dasharray="2,2" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    <path class="vgts-circle" stroke-dasharray="0, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    <text x="18" y="20.5" class="vgts-percentage vgts-glitch-text"><?php esc_html_e('BLIND', 'vgt-sentinel-ce'); ?></text>
                    <text x="18" y="25" class="vgts-percentage" style="font-size:3px; fill:var(--vgts-text-secondary);"><?php esc_html_e('SPOT', 'vgt-sentinel-ce'); ?></text>
                </svg>
                <div class="vgts-gauge-label" style="color: #b026ff;"><?php esc_html_e('POLYMORPHIC INFERENCE', 'vgt-sentinel-ce'); ?></div>
                <div class="vgts-gauge-desc" style="color: #ef4444;"><?php esc_html_e('Zero-Day and L7-DDoS analysis disabled. Requires VGT ORACLE AI.', 'vgt-sentinel-ce'); ?></div>
            </div>
        </a>

    </div>

    <h4 style="color: #fff; margin: 30px 0 15px 0; font-size: 14px; display: flex; align-items: center; gap: 8px;">
        <span class="dashicons dashicons-rss" style="color: var(--vgts-text-secondary);"></span> 
        <?php esc_html_e('LIVE THREAT STREAM', 'vgt-sentinel-ce'); ?>
    </h4>
    
    <div class="vgts-stream-container">
        <?php if (empty($threat_stream)): ?>
            <div style="padding: 30px; text-align: center; color: var(--vgts-text-secondary); font-size: 12px;">
                <span class="dashicons dashicons-shield" style="font-size: 24px; width: 24px; height: 24px; margin-bottom: 10px; opacity: 0.5;"></span><br>
                <?php esc_html_e('System perimeter is completely clean. No incidents in memory.', 'vgt-sentinel-ce'); ?>
            </div>
        <?php else: ?>
            <?php foreach ($threat_stream as $event): 
                $event_type = strtoupper((string)$event['event_type']);
                $event_msg  = (string)$event['message'];
                
                $type_class = 'vgts-stream-type-aegis';
                $type_label = esc_html__('AEGIS BLOCK', 'vgt-sentinel-ce');
                
                if (strpos($event_msg, 'GHOST TRAP') !== false || strpos($event_type, 'TRAP') !== false) {
                    $type_class = 'vgts-stream-type-trap';
                    $type_label = esc_html__('GHOST TRAP', 'vgt-sentinel-ce');
                } elseif (strpos($event_type, 'BAN') !== false) {
                    $type_class = 'vgts-stream-type-ban';
                    $type_label = esc_html__('HARD BAN', 'vgt-sentinel-ce');
                }
                
                $timestamp = wp_date($date_format, strtotime((string)$event['timestamp']));
            ?>
            <div class="vgts-stream-row">
                <div class="vgts-stream-time"><?php echo esc_html($timestamp); ?></div>
                <div class="vgts-badge-stream <?php echo esc_attr($type_class); ?>"><?php echo $type_label; ?></div>
                <div class="vgts-stream-msg" title="<?php echo esc_attr($event_msg); ?>">
                    <?php echo esc_html($event_msg); ?>
                </div>
                <div class="vgts-stream-ip"><?php echo esc_html((string)$event['ip']); ?></div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
