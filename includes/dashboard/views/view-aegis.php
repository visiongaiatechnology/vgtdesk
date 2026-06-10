<?php 
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit; 
}

/**
 * VIEW: AEGIS FIREWALL SETTINGS
 * STATUS: PLATIN VGT STATUS (Hardened & i18n)
 * FIX: DOM Namespace Sync (vgts-slider, vgts-input-whitelist, vgts-pattern-grid)
 */

// [WP.ORG COMPLIANCE]: Sync with rekalibrated prefix
$opt = (array) get_option('vgts_config', []);
?>

<div class="vgts-card vgts-card-aegis">
    <h3 style="color:var(--vgts-accent); display: flex; align-items: center; justify-content: space-between;">
        <span><?php esc_html_e('AEGIS FIREWALL MATRIX', 'vgt-sentinel-ce'); ?></span>
        <div class="vgts-matrix-status">
            <div class="vgts-pulse-dot"></div>
            <?php esc_html_e('SYSTEM ACTIVE', 'vgt-sentinel-ce'); ?>
        </div>
    </h3>
    
    <div class="vgts-switch-row">
        <div class="vgts-label-group">
            <strong><?php esc_html_e('ENABLE FIREWALL ENGINE', 'vgt-sentinel-ce'); ?></strong>
            <p><?php esc_html_e('Deep Packet Inspection for SQLi, XSS, RCE, and LFI vectors.', 'vgt-sentinel-ce'); ?></p>
        </div>
        <label class="vgts-switch">
            <input type="checkbox" name="vgts_config[aegis_enabled]" value="1" <?php checked(!empty($opt['aegis_enabled'])); ?>>
            <span class="vgts-slider"></span>
        </label>
    </div>

    <div class="vgts-switch-row">
        <div class="vgts-label-group">
            <strong><?php esc_html_e('PROTECTION PROTOCOL', 'vgt-sentinel-ce'); ?></strong>
            <p><?php esc_html_e('Define how Aegis reacts to positive threat signatures.', 'vgt-sentinel-ce'); ?></p>
        </div>
        <div>
            <select name="vgts_config[aegis_mode]" class="vgts-input-select">
                <option value="strict" <?php selected($opt['aegis_mode'] ?? '', 'strict'); ?>>
                    <?php esc_html_e('STRICT (Instant Ban)', 'vgt-sentinel-ce'); ?>
                </option>
                <option value="learning" <?php selected($opt['aegis_mode'] ?? '', 'learning'); ?>>
                    <?php esc_html_e('LEARNING (Log & Observe)', 'vgt-sentinel-ce'); ?>
                </option>
            </select>
        </div>
    </div>
</div>

<div class="vgts-card vgts-whitelist-container">
    <h3 class="vgts-card-title-icon" style="color: #10b981; border-bottom: 1px solid rgba(16, 185, 129, 0.2); padding-bottom: 15px; margin-bottom: 20px;">
        <span class="dashicons dashicons-shield"></span> 
        <?php esc_html_e('ZERO-TRUST WHITELIST', 'vgt-sentinel-ce'); ?>
    </h3>
    <p class="vgts-card-description" style="color: var(--vgts-text-secondary); font-size: 13px; margin-bottom: 25px;">
        <?php esc_html_e("Bypasses are system failures. Configure explicit IPs and User-Agents here to grant immunity from AEGIS and Cerberus. Local server loopbacks and WP Cron are immune by default.", 'vgt-sentinel-ce'); ?>
    </p>

    <div style="margin-bottom: 20px;">
        <label style="display: block; font-size: 12px; font-weight: 700; color: #10b981; margin-bottom: 8px; letter-spacing: 0.5px;"><?php esc_html_e('IMMUNE IP ADDRESSES', 'vgt-sentinel'); ?></label>
        <textarea name="vgts_config[aegis_whitelist_ips]" rows="4" 
                  placeholder="<?php echo esc_attr__("192.168.1.100\n203.0.113.5", 'vgt-sentinel-ce'); ?>" 
                  style="width: 100%; padding: 15px; border-radius: 8px; resize: vertical;"
                  class="vgts-input-whitelist"><?php echo esc_textarea($opt['aegis_whitelist_ips'] ?? ''); ?></textarea>
        <span style="font-size: 11px; color: #64748b; margin-top: 6px; display: block;"><?php esc_html_e('One IP per line. Exact match.', 'vgt-sentinel-ce'); ?></span>
    </div>

    <div>
        <label style="display: block; font-size: 12px; font-weight: 700; color: #10b981; margin-bottom: 8px; letter-spacing: 0.5px;"><?php esc_html_e('IMMUNE USER-AGENTS (PARTIAL MATCH)', 'vgt-sentinel-ce'); ?></label>
        <textarea name="vgts_config[aegis_whitelist_uas]" rows="4" 
                  placeholder="<?php echo esc_attr__("MyCustomAdminApp/1.0\nSpecificBot", 'vgt-sentinel-ce'); ?>" 
                  style="width: 100%; padding: 15px; border-radius: 8px; resize: vertical;"
                  class="vgts-input-whitelist"><?php echo esc_textarea($opt['aegis_whitelist_uas'] ?? ''); ?></textarea>
        <span style="font-size: 11px; color: #64748b; margin-top: 6px; display: block;"><?php esc_html_e('One phrase per line. If the User-Agent contains this phrase, it bypasses the WAF.', 'vgt-sentinel-ce'); ?></span>
    </div>
</div>

<div class="vgts-card">
    <h3 class="vgts-card-title-icon" style="color: var(--vgts-accent); border-bottom: 1px solid var(--vgts-border); padding-bottom: 15px; margin-bottom: 10px;">
        <span class="dashicons dashicons-info"></span> 
        <?php esc_html_e('ACTIVE PATTERNS', 'vgt-sentinel-ce'); ?>
    </h3>
    
    <div class="vgts-pattern-grid">
        <div class="vgts-pattern-badge"><?php esc_html_e('SQL INJECTION', 'vgt-sentinel-ce'); ?></div>
        <div class="vgts-pattern-badge"><?php esc_html_e('CROSS-SITE SCRIPTING', 'vgt-sentinel-ce'); ?></div>
        <div class="vgts-pattern-badge"><?php esc_html_e('REMOTE CODE EXECUTION', 'vgt-sentinel-ce'); ?></div>
        <div class="vgts-pattern-badge"><?php esc_html_e('BAD USER AGENTS', 'vgt-sentinel-ce'); ?></div>
        <div class="vgts-pattern-badge"><?php esc_html_e('LOCAL FILE INCLUSION', 'vgt-sentinel-ce'); ?></div>
        <div class="vgts-pattern-badge"><?php esc_html_e('GQL RECONNAISSANCE', 'vgt-sentinel-ce'); ?></div>
    </div>
</div>
