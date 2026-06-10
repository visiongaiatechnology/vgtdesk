<?php 
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VIEW: FILESYSTEM PERMISSIONS GUARD
 * STATUS: PLATIN VGT STATUS (Hardened & i18n)
 * MODULE: SECURE FILE SYSTEM PERMISSION AUDIT
 * TEXTDOMAIN: vgt-sentinel-ce
 */

if (!class_exists('VGTS_Filesystem_Guard')) {
    $guard_path = VGTS_PATH . 'includes/modules/filesystem/class-vis-filesystem-guard.php';
    if (is_readable($guard_path)) {
        require_once $guard_path;
    }
}

if (class_exists('VGTS_Filesystem_Guard')) {
    $guard = new VGTS_Filesystem_Guard();
    $files = $guard->scan_permissions();
} else {
    $files = [];
}
?>

<div id="vgts-fs-container">
    
    <!-- LANGUAGE TOGGLE -->
    <div class="vgts-toggle-wrapper">
        <label class="vgts-toggle-label">
            <span class="vgts-toggle-text vgts-text-de"><?php esc_html_e('DE', 'vgt-sentinel-ce'); ?></span>
            <div class="vgts-switch-track">
                <div class="vgts-switch-thumb"></div>
            </div>
            <span class="vgts-toggle-text vgts-text-en"><?php esc_html_e('EN', 'vgt-sentinel-ce'); ?></span>
            <input type="checkbox" id="vgts-fs-lang-toggle" style="display: none;">
        </label>
    </div>

    <div class="vgts-card">
        <h3>
            <span class="dashicons dashicons-category"></span> 
            <span class="vgts-lang-de" style="display:inline;"><?php esc_html_e('DATEISYSTEM SICHERHEIT (RECHTE)', 'vgt-sentinel-ce'); ?></span>
            <span class="vgts-lang-en" style="display:inline;"><?php esc_html_e('FILE SYSTEM SECURITY (PERMISSIONS)', 'vgt-sentinel-ce'); ?></span>
        </h3>
        
        <p class="vgts-lang-de" style="color:var(--vgts-text-secondary); margin-bottom:20px;">
            <?php esc_html_e('Prüft kritische WordPress-Dateien auf korrekte chmod-Rechte (Linux/Unix Standard).', 'vgt-sentinel-ce'); ?><br>
            <?php echo wp_kses_post(__('Empfehlung: Ordner <code>0755</code>, Dateien <code>0644</code>, wp-config.php <code>0400</code> oder <code>0600</code>.', 'vgt-sentinel-ce')); ?>
        </p>
        <p class="vgts-lang-en" style="color:var(--vgts-text-secondary); margin-bottom:20px;">
            <?php esc_html_e('Verifies critical WordPress files for correct chmod permissions (Linux/Unix Standard).', 'vgt-sentinel-ce'); ?><br>
            <?php echo wp_kses_post(__('Recommendation: Directories <code>0755</code>, Files <code>0644</code>, wp-config.php <code>0400</code> or <code>0600</code>.', 'vgt-sentinel-ce')); ?>
        </p>

        <table class="vgts-table vgts-fs-table">
            <thead>
                <tr>
                    <th>
                        <span class="vgts-lang-de"><?php esc_html_e('DATEI / ORDNER', 'vgt-sentinel-ce'); ?></span>
                        <span class="vgts-lang-en"><?php esc_html_e('FILE / DIRECTORY', 'vgt-sentinel-ce'); ?></span>
                    </th>
                    <th>
                        <span class="vgts-lang-de"><?php esc_html_e('PFAD (ABSOLUT)', 'vgt-sentinel-ce'); ?></span>
                        <span class="vgts-lang-en"><?php esc_html_e('PATH (ABSOLUTE)', 'vgt-sentinel-ce'); ?></span>
                    </th>
                    <th>
                        <span class="vgts-lang-de"><?php esc_html_e('AKTUELL', 'vgt-sentinel-ce'); ?></span>
                        <span class="vgts-lang-en"><?php esc_html_e('CURRENT', 'vgt-sentinel-ce'); ?></span>
                    </th>
                    <th>
                        <span class="vgts-lang-de"><?php esc_html_e('SOLL', 'vgt-sentinel-ce'); ?></span>
                        <span class="vgts-lang-en"><?php esc_html_e('REQUIRED', 'vgt-sentinel-ce'); ?></span>
                    </th>
                    <th>
                        <span class="vgts-lang-de"><?php esc_html_e('STATUS', 'vgt-sentinel-ce'); ?></span>
                        <span class="vgts-lang-en"><?php esc_html_e('STATUS', 'vgt-sentinel-ce'); ?></span>
                    </th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($files)): ?>
                <tr>
                    <td colspan="5" style="text-align:center; padding:30px; color:var(--vgts-text-secondary);">
                        <?php esc_html_e('No filesystem data available.', 'vgt-sentinel-ce'); ?>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach($files as $f): 
                    $color = 'var(--vgts-text-secondary)';
                    if ($f['status'] === 'warning') {
                        $color = 'var(--vgts-danger)';
                    } elseif ($f['status'] === 'missing') {
                        $color = 'var(--vgts-warning)';
                    }
                ?>
                    <tr>
                        <td style="font-weight:600; color:#fff;">
                            <span class="dashicons dashicons-media-default" style="font-size:14px; margin-right:5px;"></span>
                            <?php echo esc_html((string)$f['label']); ?>
                        </td>
                        <td class="text-mono" style="font-size:11px; color:var(--vgts-text-secondary); word-break:break-all;">
                            <?php echo esc_html((string)$f['path']); ?>
                        </td>
                        <td class="text-mono" style="color:<?php echo esc_attr($color); ?>; font-weight:bold;">
                            <?php echo esc_html((string)$f['perms']); ?>
                        </td>
                        <td class="text-mono" style="color:var(--vgts-text-secondary);">
                            <?php echo esc_html((string)$f['rec']); ?>
                        </td>
                        <td>
                            <?php if($f['status'] === 'secure'): ?>
                                <span class="vgts-badge-status bg-green"><?php esc_html_e('SECURE', 'vgt-sentinel-ce'); ?></span>
                            <?php else: ?>
                                <span class="vgts-badge-status bg-red"><?php echo esc_html((string)$f['msg']); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
        
        <div style="margin-top:20px; padding:15px; border-left:3px solid var(--vgts-accent); background:rgba(6,182,212,0.05);">
            <strong class="vgts-lang-de" style="color:var(--vgts-accent); display:inline;"><?php esc_html_e('HINWEIS:', 'vgt-sentinel-ce'); ?></strong>
            <strong class="vgts-lang-en" style="color:var(--vgts-accent); display:inline;"><?php esc_html_e('NOTICE:', 'vgt-sentinel-ce'); ?></strong>
            
            <span class="vgts-lang-de">
                <?php esc_html_e('Wenn Rechte als "Warning" angezeigt werden, ändern Sie diese bitte über Ihren FTP-Client (FileZilla) oder das Hosting-Panel (Plesk/cPanel). Das Plugin ändert Dateirechte aus Sicherheitsgründen nicht automatisch.', 'vgt-sentinel-ce'); ?>
            </span>
            <span class="vgts-lang-en">
                <?php esc_html_e('If permissions trigger a "Warning", please adjust them manually via your FTP client (FileZilla) or hosting panel (Plesk/cPanel). For security reasons, this plugin does not mutate file permissions automatically.', 'vgt-sentinel-ce'); ?>
            </span>
        </div>
    </div>
</div>
