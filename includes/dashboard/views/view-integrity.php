<?php 
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit; 
}

/**
 * VIEW: SYSTEM INTEGRITY MONITOR
 * STATUS: PLATIN VGT STATUS (Hardened & i18n)
 * MODULE: FILE SYSTEM HASHING & BASELINE AUDIT
 * TEXTDOMAIN: vgt-sentinel-ce
 */

$report     = get_option('vgts_scan_report', []);
$has_report = !empty($report) && is_array($report);
$status     = $has_report ? (string)$report['status'] : 'unknown';
$changes    = $has_report ? (array)$report['changes'] : [];
$last_scan  = $has_report ? esc_html((string)$report['timestamp']) : esc_html__('Never', 'vgt-sentinel-ce');

$status_color = 'var(--vgts-text-secondary)';
$status_icon  = 'dashicons-minus';

if ($status === 'clean') {
    $status_color = 'var(--vgts-success)';
    $status_icon  = 'dashicons-yes-alt';
} elseif ($status === 'warning') {
    $status_color = 'var(--vgts-danger)';
    $status_icon  = 'dashicons-warning';
}
?>

<div class="vgts-card">
    <!-- HEADER BAR -->
    <div class="vgts-integrity-header">
        <div style="display:flex; align-items:center; gap:15px;">
            <div class="vgts-integrity-status-icon" style="color:<?php echo esc_attr($status_color); ?>;">
                <span class="dashicons <?php echo esc_attr($status_icon); ?>" style="font-size:30px; width:30px; height:30px;"></span>
            </div>
            <div>
                <h2 style="margin:0; font-size:18px; color:#fff;"><?php esc_html_e('SYSTEM INTEGRITY REPORT', 'vgt-sentinel-ce'); ?></h2>
                <div style="font-size:12px; color:var(--vgts-text-secondary); margin-top:4px;">
                    <?php esc_html_e('Last Scan:', 'vgt-sentinel-ce'); ?> 
                    <span class="text-mono" style="color:#fff;"><?php echo $last_scan; ?></span>
                </div>
            </div>
        </div>
        
        <div>
            <button type="button" id="vgts-btn-scan" class="vgts-btn vgts-btn-primary">
                <span class="dashicons dashicons-search"></span> <?php esc_html_e('RUN DEEP SCAN', 'vgt-sentinel-ce'); ?>
            </button>
        </div>
    </div>

    <!-- REPORT CONTENT -->
    <?php if(!$has_report): ?>
        <div style="text-align:center; padding:40px; color:var(--vgts-text-secondary);">
            <p><?php esc_html_e('No report available. Please initiate a manual system scan.', 'vgt-sentinel-ce'); ?></p>
        </div>
    <?php elseif($status === 'clean' || $status === 'init'): ?>
        <div style="text-align:center; padding:40px;">
            <span class="dashicons dashicons-shield-alt" style="font-size:64px; color:var(--vgts-success); width:auto; height:auto; display:block; margin-bottom:20px;"></span>
            <h3 style="color:#fff; margin-bottom:10px;"><?php esc_html_e('SYSTEM SECURE', 'vgt-sentinel-ce'); ?></h3>
            <p style="color:var(--vgts-text-secondary); max-width:500px; margin:0 auto;">
                <?php esc_html_e('All monitored files match the system baseline (manifest). No unauthorized modifications detected.', 'vgt-sentinel-ce'); ?>
            </p>
        </div>
    <?php else: ?>
        <!-- ANOMALY ALERT -->
        <div class="vgts-integrity-alert-box">
            <div style="display:flex; align-items:center; gap:10px; color:var(--vgts-danger);">
                <span class="dashicons dashicons-warning" style="font-size:20px;"></span>
                <strong style="font-size:14px;">
                    <?php 
                    $change_count = count($changes);
                    printf(
                        esc_html(
                            /* translators: %d: count of changed files */
                            _n('ANOMALY DETECTED: %d FILE MODIFIED', 'ANOMALIES DETECTED: %d FILES MODIFIED', $change_count, 'vgt-sentinel-ce')
                        ),
                        (int)$change_count
                    ); 
                    ?>
                </strong>
            </div>
            <button type="button" id="vgts-btn-approve" class="vgts-btn" style="border: 1px solid var(--vgts-danger); color:var(--vgts-danger); background:transparent;">
                <span class="dashicons dashicons-yes"></span> <?php esc_html_e('APPROVE CHANGES', 'vgt-sentinel-ce'); ?>
            </button>
        </div>

        <table class="vgts-table">
            <thead>
                <tr>
                    <th width="100"><?php esc_html_e('TYPE', 'vgt-sentinel-ce'); ?></th>
                    <th><?php esc_html_e('FILE PATH', 'vgt-sentinel-ce'); ?></th>
                    <th><?php esc_html_e('DETAILS', 'vgt-sentinel-ce'); ?></th>
                    <th width="120"><?php esc_html_e('ACTION', 'vgt-sentinel-ce'); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($changes as $change): 
                $type = isset($change['type']) ? (string)$change['type'] : 'MODIFIED';
                $badge_class = 'bg-red';
                if ($type === 'NEW') { $badge_class = 'bg-green'; }
                ?>
                <tr>
                    <td><span class="vgts-badge-integrity <?php echo esc_attr($badge_class); ?>"><?php echo esc_html($type); ?></span></td>
                    <td class="text-mono" style="color:#fff; font-size:12px; word-break:break-all;">
                        <?php echo esc_html((string)$change['file']); ?>
                    </td>
                    <td style="color:var(--vgts-text-secondary); font-size:11px;">
                        <?php echo esc_html((string)$change['desc']); ?>
                    </td>
                    <td>
                        <button type="button" class="vgts-btn-link" style="color:var(--vgts-accent); font-size:11px; background:none; border:none; cursor:pointer;">
                            <?php esc_html_e('VIEW LOGS', 'vgt-sentinel-ce'); ?>
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- SCAN PROGRESS OVERLAY -->
<div id="vgts-scan-progress">
    <span class="dashicons dashicons-update vgts-spin" style="font-size:32px; width:32px; height:32px; margin-bottom:10px; display:inline-block;"></span>
    <div id="vgts-scan-status-text"><?php esc_html_e('INITIALIZING DEEP SCAN...', 'vgt-sentinel-ce'); ?></div>
</div>

<!-- CUSTOM APPROVE MODAL -->
<div class="vgts-modal-overlay" id="vgts-approve-modal" style="display: none;">
    <div class="vgts-modal-box" style="background: #0f172a; border: 1px solid #1e293b; padding: 25px; border-radius: 8px; max-width: 450px; width: 100%; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px; color: var(--vgts-warning);">
            <span class="dashicons dashicons-warning" style="font-size: 24px; width: 24px; height: 24px;"></span>
            <h3 style="margin: 0; color: #fff; font-size: 16px;"><?php esc_html_e('SECURITY ALERT: BASELINE UPDATE', 'vgt-sentinel-ce'); ?></h3>
        </div>
        <p style="color: var(--vgts-text-secondary); font-size: 13px; line-height: 1.5; margin-bottom: 25px;">
            <?php esc_html_e('Are you sure you want to authorize all current changes? This will set a new cryptographic baseline for the entire system.', 'vgt-sentinel-ce'); ?>
        </p>
        <div style="display: flex; justify-content: flex-end; gap: 10px;">
            <button type="button" class="vgts-btn" id="vgts-modal-cancel" style="background: transparent; border: 1px solid var(--vgts-border); color: #fff;">
                <?php esc_html_e('CANCEL', 'vgt-sentinel-ce'); ?>
            </button>
            <button type="button" class="vgts-btn vgts-btn-primary" id="vgts-modal-confirm">
                <span class="dashicons dashicons-yes"></span> <?php esc_html_e('AUTHORIZE', 'vgt-sentinel-ce'); ?>
            </button>
        </div>
    </div>
</div>
