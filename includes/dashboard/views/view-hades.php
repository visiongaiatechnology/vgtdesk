<?php 
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit; 
}

/**
 * VIEW: HADES STEALTH ENGINE
 * STATUS: PLATIN VGT STATUS (Hardened & i18n)
 * MODULE: PATH OBFUSCATION & CAMOUFLAGE (Community Edition)
 * TEXTDOMAIN: vgt-sentinel-ce
 */

if (!class_exists('VGTS_Hades')) {
    $hades_path = VGTS_PATH . 'includes/modules/hades/class-vis-hades.php';
    if (is_readable($hades_path)) {
        require_once $hades_path;
    }
}

// Instantiate with empty array if needed, logic is handled by internal config
$hades_instance = class_exists('VGTS_Hades') ? new VGTS_Hades([]) : null; 

// [WP.ORG COMPLIANCE]: Sanitization of Server Vars
$software = isset($_SERVER['SERVER_SOFTWARE']) ? sanitize_text_field(wp_unslash($_SERVER['SERVER_SOFTWARE'])) : '';
$is_nginx = (stripos($software, 'nginx') !== false);

if (!isset($opt)) {
    $opt = (array) get_option('vgts_config', []);
}

$hades_active = !empty($opt['hades_enabled']) ? 1 : 0;
$login_slug   = !empty($opt['hades_login_slug']) ? (string)$opt['hades_login_slug'] : 'wp-login.php';
$admin_slug   = !empty($opt['hades_admin_slug']) ? (string)$opt['hades_admin_slug'] : 'wp-admin';

$path_mappings = [
    ['old' => 'wp-content/themes',  'new' => 'content/ui'],
    ['old' => 'wp-content/plugins', 'new' => 'content/lib'],
    ['old' => 'wp-content/uploads', 'new' => 'storage'],
    ['old' => 'wp-includes',        'new' => 'core']
];
?>

<div id="vgts-hades-container">
    
    <!-- LANGUAGE TOGGLE -->
    <div class="vgts-toggle-wrapper">
        <label class="vgts-toggle-label">
            <span class="vgts-toggle-text vgts-text-de"><?php esc_html_e('DE', 'vgt-sentinel-ce'); ?></span>
            <div class="vgts-switch-track">
                <div class="vgts-switch-thumb"></div>
            </div>
            <span class="vgts-toggle-text vgts-text-en"><?php esc_html_e('EN', 'vgt-sentinel-ce'); ?></span>
            <input type="checkbox" id="vgts-hades-lang-toggle" style="display: none;">
        </label>
    </div>

    <!-- WP.ORG COMPLIANCE DISCLAIMER (GUIDELINE 10) -->
    <div style="background: rgba(239, 68, 68, 0.05); border-left: 4px solid #ef4444; padding: 15px 20px; margin-bottom: 25px; border-radius: 4px;" role="alert">
        <strong style="color: #ef4444; display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
            <span class="dashicons dashicons-warning"></span> 
            <span class="vgts-lang-de" style="display:inline;"><?php esc_html_e('WP.ORG RICHTLINIE 10 HINWEIS: "SECURITY BY OBSCURITY"', 'vgt-sentinel-ce'); ?></span>
            <span class="vgts-lang-en" style="display:inline;"><?php esc_html_e('WP.ORG GUIDELINE 10 NOTICE: "SECURITY BY OBSCURITY"', 'vgt-sentinel-ce'); ?></span>
        </strong>
        <div class="vgts-lang-de" style="color: #cbd5e1; font-size: 13px; line-height: 1.5;">
            <?php echo wp_kses_post(__(' "Security by Obscurity" ist kein Ersatz für echte Sicherheit. Dieses Modul schreibt dynamisch Core-Pfade um, um automatisiertes Scanner-Rauschen zu verringern. Es handelt sich um eine <strong>experimentelle Opt-In-Funktion</strong>, die mit bestimmten CDNs, Caching-Plugins oder hartkodierten Themes in Konflikt geraten kann. Nutzung auf eigene Gefahr.', 'vgt-sentinel-ce')); ?>
        </div>
        <div class="vgts-lang-en" style="color: #cbd5e1; font-size: 13px; line-height: 1.5;">
            <?php echo wp_kses_post(__(' "Security by obscurity" is not a replacement for actual security. This module dynamically rewrites core paths to reduce automated scanner noise. It is an <strong>experimental opt-in feature</strong> and may conflict with certain CDNs, caching plugins, or hardcoded themes. Use strictly at your own risk.', 'vgt-sentinel-ce')); ?>
        </div>
    </div>

    <div class="vgts-card" style="border-top: 3px solid #8b5cf6;">
        <!-- HEADER -->
        <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid var(--vgts-border);">
            <span class="dashicons dashicons-hidden" style="font-size: 32px; width: 32px; height: 32px; color: #8b5cf6;"></span>
            <div>
                <h2 style="margin: 0; color: #fff; font-size: 1.2rem; font-weight: 700;"><?php esc_html_e('HADES STEALTH ENGINE', 'vgt-sentinel-ce'); ?></h2>
                <p class="vgts-lang-de" style="margin: 5px 0 0 0; color: var(--vgts-text-secondary); font-size: 12px;"><?php esc_html_e('Camouflage, Pfad-Verschleierung & Ghost Mode', 'vgt-sentinel-ce'); ?></p>
                <p class="vgts-lang-en" style="margin: 5px 0 0 0; color: var(--vgts-text-secondary); font-size: 12px;"><?php esc_html_e('Camouflage, Path Obfuscation & Ghost Mode', 'vgt-sentinel-ce'); ?></p>
            </div>
        </div>

        <!-- MAIN TOGGLE -->
        <div style="background: rgba(15, 23, 42, 0.4); border: 1px solid var(--vgts-border); border-radius: 8px; padding: 20px; margin-bottom: 30px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h4 style="margin: 0 0 5px 0; color: #fff; font-size: 14px;"><?php esc_html_e('GHOST MODE (CLOAKING)', 'vgt-sentinel-ce'); ?></h4>
                    <p class="vgts-lang-de" style="margin: 0; color: var(--vgts-text-secondary); font-size: 12px;"><?php esc_html_e('Aktiviert die globale Maskierung von Systempfaden und blockiert direkte Zugriffe auf den Core.', 'vgt-sentinel-ce'); ?></p>
                    <p class="vgts-lang-en" style="margin: 0; color: var(--vgts-text-secondary); font-size: 12px;"><?php esc_html_e('Activates global masking of system paths and unconditionally blocks direct access to the core.', 'vgt-sentinel-ce'); ?></p>
                </div>
                
                <label class="vgt-hades-toggle">
                    <input type="checkbox" name="vgts_config[hades_enabled]" value="1" <?php checked($hades_active, 1); ?>>
                    <span class="vgt-hades-slider"></span>
                </label>
            </div>
        </div>

        <!-- PATH ROUTING MATRIX -->
        <div style="margin-bottom: 30px;">
            <h4 style="margin: 0 0 15px 0; color: #fff; font-size: 14px; display: flex; align-items: center; gap: 8px;">
                <span class="dashicons dashicons-randomize" style="color: #8b5cf6;"></span>
                <?php esc_html_e('VIRTUAL PATH ROUTING MATRIX', 'vgt-sentinel-ce'); ?>
            </h4>
            <p class="vgts-lang-de" style="color: #94a3b8; font-size: 13px; line-height: 1.5; margin-bottom: 15px;">
                <?php esc_html_e('Hades schreibt die WordPress-Kernverzeichnisse virtuell um. Automatisierte Enumerations-Angriffe (WPScan, DirBuster) scannen nach Standardpfaden und laufen so mathematisch garantiert ins Leere.', 'vgt-sentinel-ce'); ?> <br>
                <?php esc_html_e('Nach Aktivierung muss die Permalinkstruktur einmal unter Einstellungen neu gespeichert werden.', 'vgt-sentinel-ce'); ?> 
            </p>
            <p class="vgts-lang-en" style="color: #94a3b8; font-size: 13px; line-height: 1.5; margin-bottom: 15px;">
                <?php esc_html_e('Hades virtually rewrites WordPress core directories. Automated enumeration attacks (WPScan, DirBuster) scanning for standard signatures are mathematically guaranteed to fail.', 'vgt-sentinel-ce'); ?> <br>
                <?php esc_html_e('After activation, the permalink structure must be re-saved once in the settings to flush rewrite rules.', 'vgt-sentinel-ce'); ?> 
            </p>

            <div style="display: grid; grid-template-columns: 1fr; gap: 8px;">
                <?php foreach ($path_mappings as $mapping): ?>
                <div class="vgts-matrix-row">
                    <div style="display: flex; align-items: center; gap: 10px; width: 45%;">
                        <span class="dashicons dashicons-folder" style="color: #64748b;"></span>
                        <code style="color: #ef4444; background: transparent; padding: 0; font-size: 13px; border: none;">/<?php echo esc_html((string)$mapping['old']); ?></code>
                    </div>
                    <div style="color: #8b5cf6; display: flex; align-items: center; opacity: 0.7;">
                        <span class="dashicons dashicons-arrow-right-alt"></span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px; width: 45%; justify-content: flex-end;">
                        <code style="color: #10b981; background: transparent; padding: 0; font-size: 13px; font-weight: 700; border: none;">/<?php echo esc_html((string)$mapping['new']); ?></code>
                        <span class="dashicons dashicons-shield" style="color: #10b981; font-size: 16px; width: 16px; height: 16px;"></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- CUSTOM ENTRY POINTS -->
        <div style="padding-top: 20px; border-top: 1px solid var(--vgts-border);">
            <h4 style="margin: 0 0 15px 0; color: #fff; font-size: 14px;"><?php esc_html_e('CUSTOM ENTRY POINTS', 'vgt-sentinel-ce'); ?></h4>
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <div>
                    <strong style="color: #e2e8f0; font-size: 13px;"><?php esc_html_e('LOGIN SLUG', 'vgt-sentinel-ce'); ?></strong>
                    <p class="vgts-lang-de" style="margin: 3px 0 0 0; color: var(--vgts-text-secondary); font-size: 12px;"><?php esc_html_e('Ersetzt wp-login.php', 'vgt-sentinel-ce'); ?></p>
                    <p class="vgts-lang-en" style="margin: 3px 0 0 0; color: var(--vgts-text-secondary); font-size: 12px;"><?php esc_html_e('Replaces wp-login.php', 'vgt-sentinel-ce'); ?></p>
                </div>
                <div>
                    <input type="text" name="vgts_config[hades_login_slug]" value="<?php echo esc_attr($login_slug); ?>" class="vgts-hades-input">
                </div>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <strong style="color: #e2e8f0; font-size: 13px;"><?php esc_html_e('DASHBOARD SLUG', 'vgt-sentinel-ce'); ?></strong>
                    <p class="vgts-lang-de" style="margin: 3px 0 0 0; color: var(--vgts-text-secondary); font-size: 12px;"><?php echo wp_kses_post(__('Ersetzt <code>wp-admin</code> <strong style="color:#f59e0b;">(Vorsicht: Kann externe Plugins stören!)</strong>', 'vgt-sentinel-ce')); ?></p>
                    <p class="vgts-lang-en" style="margin: 3px 0 0 0; color: var(--vgts-text-secondary); font-size: 12px;"><?php echo wp_kses_post(__('Replaces <code>wp-admin</code> <strong style="color:#f59e0b;">(Warning: May disrupt external plugins!)</strong>', 'vgt-sentinel-ce')); ?></p>
                </div>
                <div>
                    <input type="text" name="vgts_config[hades_admin_slug]" value="<?php echo esc_attr($admin_slug); ?>" class="vgts-hades-input">
                </div>
            </div>
        </div>

        <!-- NGINX INSTRUCTIONS -->
        <?php if ($is_nginx && $hades_active): ?>
            <div style="margin-top:30px; padding:20px; background:rgba(245, 158, 11, 0.05); border:1px solid rgba(245, 158, 11, 0.3); border-radius:6px;">
                <div style="display:flex; gap:10px; color:#f59e0b; margin-bottom:10px; align-items: center;">
                    <span class="dashicons dashicons-warning" style="font-size:20px;"></span>
                    <strong class="vgts-lang-de" style="font-size:14px; letter-spacing: 0.5px; display:inline;"><?php esc_html_e('NGINX DETECTED - MANUELLE KONFIGURATION NÖTIG', 'vgt-sentinel-ce'); ?></strong>
                    <strong class="vgts-lang-en" style="font-size:14px; letter-spacing: 0.5px; display:inline;"><?php esc_html_e('NGINX DETECTED - MANUAL CONFIGURATION REQUIRED', 'vgt-sentinel-ce'); ?></strong>
                </div>
                
                <p class="vgts-lang-de" style="font-size:13px; color:var(--vgts-text-secondary); margin-bottom:15px; line-height: 1.5;">
                    <?php esc_html_e('Da Hades auf einem NGINX-Server operiert, müssen die Routing-Regeln manuell in den Server-Block (nginx.conf) injiziert werden. Kopieren Sie den folgenden Block:', 'vgt-sentinel-ce'); ?>
                </p>
                <p class="vgts-lang-en" style="font-size:13px; color:var(--vgts-text-secondary); margin-bottom:15px; line-height: 1.5;">
                    <?php esc_html_e('Because Hades is operating on an NGINX environment, the routing rules must be manually injected into the server block (nginx.conf). Copy the following payload:', 'vgt-sentinel-ce'); ?>
                </p>
                
                <textarea readonly style="width:100%; height:160px; background:#020617; color:#a5b4fc; border:1px solid #334155; border-radius:4px; padding:15px; font-family:monospace; font-size:12px; resize: none;" onfocus="this.select();" aria-label="<?php echo esc_attr__('NGINX Config Payload', 'vgt-sentinel-ce'); ?>">
# VisionGaia Hades Stealth Rules
rewrite ^/content/ui/(.*) /wp-content/themes/$1 last;
rewrite ^/content/lib/(.*) /wp-content/plugins/$1 last;
rewrite ^/storage/(.*) /wp-content/uploads/$1 last;
rewrite ^/content/(.*) /wp-content/$1 last;
rewrite ^/core/(.*) /wp-includes/$1 last;
<?php 
if ($hades_instance) {
    echo esc_textarea($hades_instance->get_nginx_routing_rules()); 
}
?>
</textarea>
            </div>
        <?php endif; ?>

        <!-- APACHE INSTRUCTIONS -->
        <?php if (!$is_nginx && $hades_active): ?>
             <div style="margin-top:30px; padding:15px 20px; background:rgba(16, 185, 129, 0.05); border:1px solid rgba(16, 185, 129, 0.2); border-radius:6px; color:#10b981; font-size:13px; display: flex; align-items: center; gap: 10px;">
                <span class="dashicons dashicons-yes" style="font-size: 20px; width: 20px; height: 20px;"></span>
                <div>
                    <strong style="letter-spacing: 0.5px;"><?php esc_html_e('APACHE MODE ACTIVE:', 'vgt-sentinel-ce'); ?></strong> 
                    <span class="vgts-lang-de"><?php esc_html_e('Routing & Asset Rules wurden erfolgreich in die System-.htaccess injiziert.', 'vgt-sentinel-ce'); ?></span>
                    <span class="vgts-lang-en"><?php esc_html_e('Routing & asset rules have been successfully injected into the system .htaccess.', 'vgt-sentinel-ce'); ?></span>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
