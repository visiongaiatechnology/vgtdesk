<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VIEW: PRE-BOOT MU-DEPLOYER (MANUAL)
 * STATUS: PLATIN VGT STATUS (Hardened & i18n)
 * MODULE: O(1) KERNEL LOADER GENERATOR
 * TEXTDOMAIN: vgt-sentinel-ce
 */

$mu_dir  = defined('WPMU_PLUGIN_DIR') ? WPMU_PLUGIN_DIR : wp_normalize_path(WP_CONTENT_DIR . '/mu-plugins');
$mu_file = $mu_dir . '/0-vgts-sentinel-loader.php';

// Dynamische Pfad-Generierung für die korrekte Plugin-Basis
$vgts_target = wp_normalize_path(VGTS_PATH . 'vision-integrity-sentinel.php');
$is_deployed = file_exists($mu_file);

// Generierung des Payloads für die manuelle Anlage (Hardened Template)
$mu_content = "<?php\n" .
"/**\n" .
" * Plugin Name: VGT Sentinel MU-Loader (Hardened)\n" .
" * Description: O(1) Pre-Boot Interception. Lädt Sentinel isoliert vor allen anderen Plugins.\n" .
" * Version: 2.1.0\n" .
" * Author: VisionGaiaTechnology\n" .
" */\n\n" .
"// VGT HARDENING: Strict Access Protocol\n" .
"if (!defined('ABSPATH')) { header('HTTP/1.0 403 Forbidden'); exit('VGT: Protocol Violation'); }\n\n" .
"// Kognitive Boot-Signatur setzen\n" .
"if (!defined('VGTS_SENTINEL_MU_BOOT')) {\n" .
"    define('VGTS_SENTINEL_MU_BOOT', true);\n" .
"}\n\n" .
"\$vgts_core = '" . esc_html($vgts_target) . "';\n\n" .
"// Memory Safe & Anti-Crash Validation\n" .
"if (file_exists(\$vgts_core) && is_readable(\$vgts_core)) {\n" .
"    require_once \$vgts_core;\n" .
"}\n";
?>

<div id="vgts-mu-container" class="vgts-view-animate">
    
    <!-- MAIN DEPLOYER CARD -->
    <div class="vgts-card" style="border-left: 4px solid var(--vgts-accent);">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 25px; border-bottom: 1px solid var(--vgts-border); padding-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 15px;">
                <span class="dashicons dashicons-admin-network" style="font-size: 32px; width: 32px; height: 32px; color: var(--vgts-accent); filter: drop-shadow(0 0 8px var(--vgts-accent-glow));"></span>
                <div>
                    <h2 style="margin: 0; color: #fff; font-size: 18px; font-weight: 800; letter-spacing: -0.5px;"><?php esc_html_e('MU-DEPLOYER', 'vgt-sentinel-ce'); ?></h2>
                    <p style="margin: 2px 0 0 0; color: var(--vgts-text-secondary); font-size: 11px; text-transform: uppercase; letter-spacing: 1px;"><?php esc_html_e('Pre-Boot Security Layer', 'vgt-sentinel-ce'); ?></p>
                </div>
            </div>
            
            <!-- DEPLOYMENT STATUS BADGE -->
            <div style="display: flex; align-items: center; gap: 10px; background: rgba(0,0,0,0.3); padding: 8px 15px; border-radius: 6px; border: 1px solid var(--vgts-border);">
                <div class="vgts-mu-status-indicator <?php echo $is_deployed ? 'vgts-mu-active' : 'vgts-mu-inactive'; ?>"></div>
                <span style="font-size: 11px; font-weight: 800; color: <?php echo $is_deployed ? 'var(--vgts-success)' : 'var(--vgts-danger)'; ?>; letter-spacing: 0.5px;">
                    <?php echo $is_deployed ? esc_html__('DEPLOYED', 'vgt-sentinel-ce') : esc_html__('NOT DEPLOYED', 'vgt-sentinel-ce'); ?>
                </span>
            </div>
        </div>

        <div style="color: var(--vgts-text-secondary); font-size: 14px; line-height: 1.6; margin-bottom: 25px;">
            <p class="vgts-lang-de"><?php esc_html_e('Der MU-Deployer ermöglicht eine extrem frühe Filterung von Angriffen. Durch die Platzierung in den \'mu-plugins\' wird Sentinel geladen, noch bevor WordPress die regulären Plugins initialisiert.', 'vgt-sentinel-ce'); ?></p>
            <p class="vgts-lang-en"><?php esc_html_e('The MU-Deployer enables extremely early threat filtering. By placing it in \'mu-plugins\', Sentinel is loaded even before WordPress initializes regular plugins.', 'vgt-sentinel-ce'); ?></p>
        </div>

        <?php if (!$is_deployed): ?>
            <!-- MANUAL INSTALLATION INSTRUCTIONS -->
            <div style="margin-top: 30px; padding: 25px; background: rgba(0,0,0,0.4); border-radius: 8px; border: 1px solid var(--vgts-border);">
                <h4 style="color: #fff; font-size: 13px; margin-bottom: 12px; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                    <span class="dashicons dashicons-clipboard" style="font-size: 18px; width: 18px; height: 18px; color: var(--vgts-accent);"></span>
                    <?php esc_html_e('MANUELLE INSTALLATION (WP.ORG COMPLIANT):', 'vgt-sentinel-ce'); ?>
                </h4>
                
                <p class="vgts-lang-de" style="font-size: 13px; color: var(--vgts-text-secondary); margin-bottom: 15px;">
                    <?php 
                    printf(
                        esc_html__('Kopieren Sie den folgenden Code in eine neue Datei namens %1$s im Ordner %2$s.', 'vgt-sentinel-ce'),
                        '<code>0-vgts-sentinel-loader.php</code>',
                        '<code>wp-content/mu-plugins/</code>'
                    ); 
                    ?>
                </p>
                <p class="vgts-lang-en" style="font-size: 13px; color: var(--vgts-text-secondary); margin-bottom: 15px;">
                    <?php 
                    printf(
                        esc_html__('Copy the following code into a new file named %1$s inside the %2$s folder.', 'vgt-sentinel-ce'),
                        '<code>0-vgts-sentinel-loader.php</code>',
                        '<code>wp-content/mu-plugins/</code>'
                    ); 
                    ?>
                </p>
                
                <textarea class="vgts-mu-code-block" readonly 
                          style="width: 100%; height: 220px; font-family: var(--vgts-font-mono); font-size: 12px; background: #020617; color: var(--vgts-accent); border: 1px solid var(--vgts-border); padding: 15px; border-radius: 4px; resize: none;"
                          aria-label="<?php echo esc_attr__('MU-Loader PHP Payload', 'vgt-sentinel-ce'); ?>"><?php echo esc_textarea($mu_content); ?></textarea>
                
                <div style="margin-top: 15px; display: flex; align-items: center; gap: 8px; color: var(--vgts-warning); font-size: 11px; font-weight: 700;">
                    <span class="dashicons dashicons-warning" style="font-size: 16px; width: 16px; height: 16px;"></span>
                    <span class="vgts-lang-de"><?php esc_html_e('HINWEIS: Erstellen Sie den Ordner \'mu-plugins\' manuell, falls dieser fehlt.', 'vgt-sentinel-ce'); ?></span>
                    <span class="vgts-lang-en"><?php esc_html_e('NOTE: Create the \'mu-plugins\' folder manually if it is missing.', 'vgt-sentinel-ce'); ?></span>
                </div>
            </div>
        <?php else: ?>
            <!-- ACTIVE STATUS BOX -->
            <div style="padding: 20px; background: var(--vgts-success-bg); border: 1px solid var(--vgts-success); border-radius: 8px; display: flex; align-items: center; gap: 15px;">
                <span class="dashicons dashicons-yes-alt" style="color: var(--vgts-success); font-size: 24px; width: 24px; height: 24px;"></span>
                <p style="margin: 0; color: var(--vgts-success); font-weight: 700; font-size: 13px;">
                    <?php esc_html_e('MU-Loader aktiv. Sentinel operiert im High-Priority Interception Modus.', 'vgt-sentinel-ce'); ?>
                </p>
            </div>
        <?php endif; ?>
    </div>

    <!-- SYSTEM ARCHITECTURE EXPLANATION -->
    <div class="vgts-card" style="background: rgba(6, 182, 212, 0.02); border: 1px dashed rgba(6, 182, 212, 0.2);">
        <h5 style="margin: 0 0 10px 0; color: var(--vgts-accent); font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px;">
            <?php esc_html_e('SYSTEM ARCHITECTURE:', 'vgt-sentinel-ce'); ?>
        </h5>
        <p class="vgts-lang-de" style="margin: 0; color: var(--vgts-text-secondary); font-size: 12px; line-height: 1.6;">
            <?php esc_html_e('Der MU-Loader ermöglicht es Sentinel, HTTP-Requests zu analysieren, bevor andere Plugins geladen werden. Dies verhindert, dass anfällige Plugins (z.B. mit SQLi-Lücken) ausgeführt werden, bevor Aegis den bösartigen Payload neutralisiert hat. Dies ist das Fundament für ein echtes Zero-Trust Environment.', 'vgt-sentinel-ce'); ?>
        </p>
        <p class="vgts-lang-en" style="margin: 0; color: var(--vgts-text-secondary); font-size: 12px; line-height: 1.6;">
            <?php esc_html_e('The MU-Loader allows Sentinel to analyze HTTP requests before other plugins are loaded. This prevents vulnerable plugins (e.g., with SQLi gaps) from being executed before Aegis has neutralized the malicious payload. This is the foundation for a true zero-trust environment.', 'vgt-sentinel-ce'); ?>
        </p>
    </div>
</div>
