<?php 
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit; 
}

/**
 * VIEW: TITAN HARDENING & .HTACCESS
 * STATUS: PLATIN VGT STATUS (Hardened & i18n)
 * MODULE: KERNEL HARDENING & SERVER-LEVEL PROTECTION (CE)
 * TEXTDOMAIN: vgt-sentinel-ce
 */

$opt = (array) get_option('vgts_config', []);
?>

<div id="vgts-titan-container" class="vgts-view-animate">
    <!-- UI LANGUAGE TOGGLE -->
    <div class="vgts-toggle-wrapper">
        <label class="vgts-toggle-label">
            <span class="vgts-toggle-text vgts-text-de"><?php esc_html_e('DE', 'vgt-sentinel-ce'); ?></span>
            <div class="vgts-switch-track">
                <div class="vgts-switch-thumb"></div>
            </div>
            <span class="vgts-toggle-text vgts-text-en"><?php esc_html_e('EN', 'vgt-sentinel-ce'); ?></span>
            <input type="checkbox" id="vgts-titan-lang-toggle" style="display: none;">
        </label>
    </div>

    <div class="vgts-card">
        <h3 class="vgts-card-title-icon">
            <span class="dashicons dashicons-lock"></span> 
            <span class="vgts-lang-de" style="display:inline;"><?php esc_html_e('KERNEL HÄRTUNG & .HTACCESS', 'vgt-sentinel-ce'); ?></span>
            <span class="vgts-lang-en" style="display:inline;"><?php esc_html_e('KERNEL HARDENING & .HTACCESS', 'vgt-sentinel-ce'); ?></span>
        </h3>
        <p class="vgts-lang-de" style="color:var(--vgts-text-secondary); margin-bottom:20px; font-size:13px;">
            <?php echo wp_kses_post(__('Alle Aktivierungen in diesem Modul werden automatisch in die <code>.htaccess</code> geschrieben (sofern Apache), um maximalen Schutz auf Server-Ebene zu gewährleisten.', 'vgt-sentinel-ce')); ?>
        </p>
        <p class="vgts-lang-en" style="color:var(--vgts-text-secondary); margin-bottom:20px; font-size:13px;">
            <?php echo wp_kses_post(__('All activations within this module are dynamically injected into the <code>.htaccess</code> file (if on Apache) to establish maximum server-level protection.', 'vgt-sentinel-ce')); ?>
        </p>

        <!-- SECTION 1: CAMOUFLAGE -->
        <div class="vgts-switch-row">
            <div class="vgts-label-group">
                <strong class="vgts-lang-de"><?php esc_html_e('HEADER CAMOUFLAGE (TÄUSCHUNG)', 'vgt-sentinel-ce'); ?></strong>
                <strong class="vgts-lang-en"><?php esc_html_e('HEADER CAMOUFLAGE (DECEPTION)', 'vgt-sentinel-ce'); ?></strong>
                <p class="vgts-lang-de"><?php esc_html_e('Verschleiert WordPress und injiziert Fake-Header (z.B. Laravel), um Angreifer zu verwirren.', 'vgt-sentinel-ce'); ?></p>
                <p class="vgts-lang-en"><?php esc_html_e('Obfuscates WordPress footprints and injects forged headers (e.g., Laravel) to disorient attackers.', 'vgt-sentinel-ce'); ?></p>
            </div>
            <div>
                <select name="vgts_config[titan_camouflage_mode]" class="vgts-input-select">
                    <option value="none" <?php selected($opt['titan_camouflage_mode'] ?? '', 'none'); ?>>
                        <?php esc_html_e('Deaktiviert | Disabled (Default)', 'vgt-sentinel-ce'); ?>
                    </option>
                    <option value="drupal" <?php selected($opt['titan_camouflage_mode'] ?? '', 'drupal'); ?>>
                        <?php esc_html_e('Drupal CMS (Recommended)', 'vgt-sentinel-ce'); ?>
                    </option>
                    <option value="laravel" <?php selected($opt['titan_camouflage_mode'] ?? '', 'laravel'); ?>>
                        <?php esc_html_e('Laravel Framework', 'vgt-sentinel-ce'); ?>
                    </option>
                </select>
            </div>
        </div>

        <!-- SECTION 2: API & PROTOCOLS -->
        <div class="vgts-switch-row">
            <div class="vgts-label-group">
                <strong class="vgts-lang-de"><?php esc_html_e('XML-RPC BLOCKIEREN', 'vgt-sentinel-ce'); ?></strong>
                <strong class="vgts-lang-en"><?php esc_html_e('BLOCK XML-RPC', 'vgt-sentinel-ce'); ?></strong>
                <p class="vgts-lang-de"><?php esc_html_e('Schließt die xmlrpc.php Schnittstelle komplett (Stoppt DDoS & Brute Force).', 'vgt-sentinel-ce'); ?></p>
                <p class="vgts-lang-en"><?php esc_html_e('Completely seals the xmlrpc.php interface (Neutralizes DDoS & Brute Force vectors).', 'vgt-sentinel-ce'); ?></p>
            </div>
            <label class="vgts-switch">
                <input type="checkbox" name="vgts_config[titan_block_xmlrpc]" value="1" <?php checked(!empty($opt['titan_block_xmlrpc'])); ?>>
                <span class="vgts-slider"></span>
            </label>
        </div>

        <div class="vgts-switch-row">
            <div class="vgts-label-group">
                <strong class="vgts-lang-de"><?php esc_html_e('REST API EINSCHRÄNKEN', 'vgt-sentinel-ce'); ?></strong>
                <strong class="vgts-lang-en"><?php esc_html_e('RESTRICT REST API', 'vgt-sentinel-ce'); ?></strong>
                <p class="vgts-lang-de"><?php esc_html_e('Erlaubt Zugriff auf die REST API nur für eingeloggte Benutzer.', 'vgt-sentinel-ce'); ?></p>
                <p class="vgts-lang-en"><?php esc_html_e('Restricts REST API access exclusively to authenticated users.', 'vgt-sentinel-ce'); ?></p>
            </div>
            <label class="vgts-switch">
                <input type="checkbox" name="vgts_config[titan_block_rest]" value="1" <?php checked(!empty($opt['titan_block_rest'])); ?>>
                <span class="vgts-slider"></span>
            </label>
        </div>

        <div class="vgts-switch-row">
            <div class="vgts-label-group">
                <strong class="vgts-lang-de"><?php esc_html_e('RSS & ATOM FEEDS DEAKTIVIEREN', 'vgt-sentinel-ce'); ?></strong>
                <strong class="vgts-lang-en"><?php esc_html_e('DISABLE RSS & ATOM FEEDS', 'vgt-sentinel-ce'); ?></strong>
                <p class="vgts-lang-de"><?php esc_html_e('Verhindert Content-Scraping durch Bots. Gibt 403 Forbidden bei Feed-Zugriff.', 'vgt-sentinel-ce'); ?></p>
                <p class="vgts-lang-en"><?php esc_html_e('Prevents automated content scraping. Returns a 403 Forbidden status on feed access.', 'vgt-sentinel-ce'); ?></p>
            </div>
            <label class="vgts-switch">
                <input type="checkbox" name="vgts_config[titan_disable_feeds]" value="1" <?php checked(!empty($opt['titan_disable_feeds'])); ?>>
                <span class="vgts-slider"></span>
            </label>
        </div>

        <!-- GUIDELINE 10 OPT-IN (FILE EDITOR) -->
        <div class="vgts-switch-row">
            <div class="vgts-label-group">
                <strong class="vgts-lang-de"><?php esc_html_e('DATEI-EDITOR DEAKTIVIEREN', 'vgt-sentinel-ce'); ?></strong>
                <strong class="vgts-lang-en"><?php esc_html_e('DISABLE FILE EDITOR', 'vgt-sentinel-ce'); ?></strong>
                <p class="vgts-lang-de"><?php esc_html_e('Schützt vor RCE (Remote Code Execution) durch Sperrung des in WP integrierten Theme/Plugin-Editors.', 'vgt-sentinel-ce'); ?></p>
                <p class="vgts-lang-en"><?php esc_html_e('Protects against RCE by blocking the built-in WP theme/plugin editor.', 'vgt-sentinel-ce'); ?></p>
                <span class="vgts-titan-warning">
                    <span class="dashicons dashicons-warning" style="font-size:12px; width:12px; height:12px; line-height: 12px; margin-right: 2px;"></span> 
                    <?php esc_html_e('WP.org Guideline 10 Opt-In', 'vgt-sentinel-ce'); ?>
                </span>
            </div>
            <label class="vgts-switch">
                <input type="checkbox" name="vgts_config[titan_disallow_file_edit]" value="1" <?php checked(!empty($opt['titan_disallow_file_edit'])); ?>>
                <span class="vgts-slider"></span>
            </label>
        </div>

        <!-- SECTION 3: BASE PROTECTION -->
        <div class="vgts-switch-row">
            <div class="vgts-label-group">
                <strong class="vgts-lang-de"><?php esc_html_e('SECURITY HEADERS INJECTION', 'vgt-sentinel-ce'); ?></strong>
                <strong class="vgts-lang-en"><?php esc_html_e('SECURITY HEADERS INJECTION', 'vgt-sentinel-ce'); ?></strong>
                <p class="vgts-lang-de"><?php esc_html_e('Erzwingt HSTS, X-Frame-Options und XSS-Protection.', 'vgt-sentinel-ce'); ?></p>
                <p class="vgts-lang-en"><?php esc_html_e('Enforces strict HSTS, X-Frame-Options, and XSS-Protection.', 'vgt-sentinel-ce'); ?></p>
            </div>
            <label class="vgts-switch">
                <input type="checkbox" name="vgts_config[titan_enabled]" value="1" <?php checked(!empty($opt['titan_enabled'])); ?>>
                <span class="vgts-slider"></span>
            </label>
        </div>
    </div>
</div>
