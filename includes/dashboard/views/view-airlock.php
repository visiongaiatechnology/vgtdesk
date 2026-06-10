<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VIEW: AIRLOCK GUARD SETTINGS
 * STATUS: PLATIN VGT STATUS (Hardened & i18n)
 * MODULE: SECURE UPLINK INGRESS (Community Edition)
 * TEXTDOMAIN: vgt-sentinel-ce
 */

if (!isset($opt)) {
    $opt = (array) get_option('vgts_config', []);
}
?>

<div id="vgts-airlock-container" class="vgts-view-animate">
    
    <!-- MAIN SHIELD CARD -->
    <div class="vgts-card vgts-card-airlock" style="border-left: 4px solid var(--vgts-accent); background: linear-gradient(90deg, rgba(6, 182, 212, 0.05) 0%, var(--vgts-bg-card) 100%);">
        <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 25px; border-bottom: 1px solid var(--vgts-border); padding-bottom: 20px;">
            <span class="dashicons dashicons-upload" style="font-size: 40px; width: 40px; height: 40px; color: var(--vgts-accent); filter: drop-shadow(0 0 10px var(--vgts-accent-glow));"></span>
            <div>
                <h2 style="margin: 0; color: #fff; font-size: 22px; font-weight: 800; letter-spacing: -0.5px;">
                    <?php esc_html_e('AIRLOCK GUARD', 'vgt-sentinel-ce'); ?>
                </h2>
                <p style="margin: 5px 0 0 0; color: var(--vgts-text-secondary); font-size: 13px;">
                    <?php esc_html_e('Real-Time Upload Sanitization & Binary Analysis', 'vgt-sentinel-ce'); ?>
                </p>
            </div>
        </div>

        <div style="color: var(--vgts-text-secondary); font-size: 14px; line-height: 1.6; margin-bottom: 30px;">
            <p>
                <?php esc_html_e('Airlock monitors the entire multipart/form-data stream during file uploads. Before WordPress processes a file, Airlock intercepts it, performs a binary integrity check, and strips malicious metadata or embedded PHP stubs.', 'vgt-sentinel-ce'); ?>
            </p>
        </div>

        <!-- ENGINE TOGGLE -->
        <div class="vgts-switch-row" style="background: rgba(0,0,0,0.2); padding: 20px; border-radius: 8px; border: 1px solid var(--vgts-border);">
            <div class="vgts-label-group">
                <strong style="color: var(--vgts-accent); font-size: 15px;"><?php esc_html_e('Enable Airlock Protection', 'vgt-sentinel-ce'); ?></strong>
                <p><?php esc_html_e('Activates real-time sanitization for all uploads (Strict Allowlisting).', 'vgt-sentinel-ce'); ?></p>
            </div>
            <label class="vgts-switch">
                <input type="checkbox" name="vgts_config[airlock_enabled]" value="1" <?php checked(!empty($opt['airlock_enabled'])); ?>>
                <span class="vgts-slider"></span>
            </label>
        </div>
    </div>

    <!-- PRO-TIP / INFORMATION BOX -->
    <div class="vgts-card" style="border: 1px dashed var(--vgts-accent); background: rgba(6, 182, 212, 0.02);">
        <h5 style="margin: 0 0 10px 0; color: var(--vgts-accent); font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px;">
            <span class="dashicons dashicons-lightbulb" style="font-size: 16px; width: 16px; height: 16px;"></span> 
            <?php esc_html_e('PRO-TIP:', 'vgt-sentinel-ce'); ?>
        </h5>
        <p style="margin: 0; font-size: 13px; color: var(--vgts-text-secondary); line-height: 1.5;">
            <?php 
            echo wp_kses_post(
                sprintf(
                    /* translators: %s: Strong tag for Oracle AI */
                    esc_html__('In the Platinum version of Sentinel, Airlock additionally leverages the %s to heuristically detect even the most complex steganography attacks hidden within image files.', 'vgt-sentinel-ce'),
                    '<strong style="color:#fff;">' . esc_html__('ORACLE AI', 'vgt-sentinel-ce') . '</strong>'
                )
            );
            ?>
        </p>
    </div>
</div>
