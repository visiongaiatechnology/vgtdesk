<?php 
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit; 
}

/**
 * VIEW: SYSTEM EVENT LOGS
 * STATUS: PLATIN VGT STATUS (Hardened & i18n)
 * MODULE: SECURITY EVENT TELEMETRY & AUDIT TRAIL
 * TEXTDOMAIN: vgt-sentinel-ce
 */

global $wpdb;

// [WP.ORG COMPLIANCE]: Nutzung der zentralen Kernel-Konstanten für deterministisches Tabellen-Routing
$table_name = defined('VGTS_TABLE_LOGS') ? VGTS_TABLE_LOGS : 'vgts_omega_logs';
$full_table_path = $wpdb->prefix . $table_name;

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$logs = $wpdb->get_results("SELECT * FROM {$full_table_path} ORDER BY id DESC LIMIT 50");

$date_format = get_option('date_format') . ' ' . get_option('time_format');
?>

<div class="vgts-card">
    <h3 class="vgts-card-title-icon">
        <span class="dashicons dashicons-list-view"></span> 
        <?php esc_html_e('SYSTEM EVENT LOGS', 'vgt-sentinel-ce'); ?>
    </h3>
    
    <?php if (empty($logs)) : ?>
        <div class="vgts-logs-empty">
            <span class="dashicons dashicons-media-text"></span>
            <p><?php esc_html_e('The event matrix is currently empty. No security incidents recorded.', 'vgt-sentinel-ce'); ?></p>
        </div>
    <?php else : ?>
        <div class="vgts-table-wrapper">
            <table class="vgts-table vgts-logs-table">
                <thead>
                    <tr>
                        <th width="180"><?php esc_html_e('TIMESTAMP', 'vgt-sentinel-ce'); ?></th>
                        <th width="120"><?php esc_html_e('MODULE', 'vgt-sentinel-ce'); ?></th>
                        <th width="150"><?php esc_html_e('IP ADDRESS', 'vgt-sentinel-ce'); ?></th>
                        <th><?php esc_html_e('EVENT DETAILS', 'vgt-sentinel-ce'); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($logs as $log) : 
                    // Formatierung des Zeitstempels basierend auf WP-Settings und lokaler Zeitzone
                    $timestamp = wp_date($date_format, strtotime((string)$log->timestamp));
                ?>
                    <tr>
                        <td>
                            <span class="vgts-log-timestamp text-mono"><?php echo esc_html($timestamp); ?></span>
                        </td>
                        <td>
                            <span class="vgts-log-module-badge">
                                <?php echo esc_html((string)$log->module); ?>
                            </span>
                        </td>
                        <td>
                            <span class="vgts-log-ip text-mono"><?php echo esc_html((string)$log->ip); ?></span>
                        </td>
                        <td class="vgts-log-message">
                            <?php 
                            // Strikte Neutralisierung potenzieller Schad-Payloads aus dem Log-Stream
                            echo esc_html((string)$log->message); 
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
