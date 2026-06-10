<?php 
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit; 
}

/**
 * VIEW: ORACLE SYSTEM AUDIT REPORT
 * STATUS: PLATIN VGT STATUS (Hardened & i18n)
 * MODULE: REAL-TIME ENVIRONMENT DIAGNOSTICS (Community Edition)
 * TEXTDOMAIN: vgt-sentinel-ce
 */

// Initialisiere die Oracle Engine (Community Edition Namespace)
if (!class_exists('VGTS_Oracle')) {
    $oracle_path = VGTS_PATH . 'includes/modules/oracle/class-vis-oracle.php';
    if (is_readable($oracle_path)) {
        require_once $oracle_path;
    }
}

if (class_exists('VGTS_Oracle')) {
    $oracle = new VGTS_Oracle();
    $results = $oracle->run_prophecy();
} else {
    $results = [];
}
?>

<div class="vgts-card">
    <h3 class="vgts-card-title-icon">
        <span class="dashicons dashicons-visibility"></span> 
        <?php esc_html_e('SYSTEM AUDIT REPORT', 'vgt-sentinel-ce'); ?>
    </h3>

    <div class="vgts-table-wrapper" style="margin-top: 20px;">
        <table class="vgts-table">
            <thead>
                <tr>
                    <th width="30%"><?php esc_html_e('SECURITY CHECK', 'vgt-sentinel-ce'); ?></th>
                    <th width="15%"><?php esc_html_e('STATUS', 'vgt-sentinel-ce'); ?></th>
                    <th><?php esc_html_e('ANALYSIS RESULT', 'vgt-sentinel-ce'); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($results)) : ?>
                <tr>
                    <td colspan="3" style="text-align:center; padding:40px; color:var(--vgts-text-secondary);">
                        <?php esc_html_e('Diagnostic engine offline or no data returned.', 'vgt-sentinel-ce'); ?>
                    </td>
                </tr>
            <?php else : ?>
                <?php foreach ($results as $item) : 
                    // Bestimme die Badge-Farbe basierend auf dem Status-String
                    $is_pass = (isset($item['status']) && $item['status'] === 'PASS');
                    $badge_class = $is_pass ? 'bg-green' : 'bg-red';
                ?>
                    <tr>
                        <td style="font-weight: 600; color: #fff;">
                            <?php echo esc_html((string)$item['check']); ?>
                        </td>
                        <td>
                            <span class="vgts-badge-status <?php echo esc_attr($badge_class); ?>">
                                <?php echo esc_html((string)$item['status']); ?>
                            </span>
                        </td>
                        <td style="color: var(--vgts-text-secondary); line-height: 1.4;">
                            <?php 
                            // Strikte Neutralisierung potenzieller System-Anomalien im Berichtstext
                            echo esc_html((string)$item['msg']); 
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- HINWEIS ZUR AUDIT-LOGIK -->
    <div style="margin-top: 25px; padding: 15px; background: rgba(6, 182, 212, 0.03); border-radius: 6px; border: 1px solid rgba(6, 182, 212, 0.1);">
        <p style="margin: 0; font-size: 12px; color: var(--vgts-text-secondary); display: flex; align-items: center; gap: 8px;">
            <span class="dashicons dashicons-info" style="font-size: 16px; width: 16px; height: 16px;"></span>
            <?php esc_html_e('The Oracle engine performs real-time diagnostics of your environment. Red entries indicate potential security risks that should be addressed immediately.', 'vgt-sentinel-ce'); ?>
        </p>
    </div>
</div>
