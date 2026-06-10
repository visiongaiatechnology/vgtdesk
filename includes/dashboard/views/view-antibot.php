<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VIEW: ANTIBOT ENGINE
 * STATUS: PLATIN VGT STATUS (Hardened & i18n)
 * MODULE: PROOF-OF-WORK BOT DEFENSE
 * TEXTDOMAIN: vgt-sentinel-ce
 */

$opt            = (array) get_option('vgts_config', []);
$antibot_active = !empty($opt['antibot_enabled']) ? 1 : 0;
$difficulty     = (int) ($opt['antibot_difficulty'] ?? 3);
$custom_hooks   = (array) ($opt['antibot_custom_hooks'] ?? []);

if (!function_exists('get_plugins')) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}
$all_plugins = get_plugins();
?>

<div id="vgts-antibot-container">
    
    <!-- LANGUAGE TOGGLE -->
    <div class="vgts-toggle-wrapper">
        <label class="vgts-toggle-label">
            <span class="vgts-toggle-text vgts-text-de"><?php esc_html_e('DE', 'vgt-sentinel-ce'); ?></span>
            <div class="vgts-switch-track">
                <div class="vgts-switch-thumb"></div>
            </div>
            <span class="vgts-toggle-text vgts-text-en"><?php esc_html_e('EN', 'vgt-sentinel-ce'); ?></span>
            <input type="checkbox" id="vgts-antibot-lang-toggle" style="display: none;">
        </label>
    </div>

    <!-- MAIN CARD -->
    <div class="vgts-card" style="border-top: 3px solid #f59e0b;">
        <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid var(--vgts-border);">
            <span class="dashicons dashicons-shield-alt" style="font-size: 32px; width: 32px; height: 32px; color: #f59e0b;"></span>
            <div>
                <h2 style="margin: 0; color: #fff; font-size: 1.2rem; font-weight: 700;"><?php esc_html_e('ANTIBOT ENGINE (POW)', 'vgt-sentinel-ce'); ?></h2>
                <p class="vgts-lang-de" style="margin: 5px 0 0 0; color: var(--vgts-text-secondary); font-size: 12px;"><?php esc_html_e('DSGVO-konforme Zero-UI Proof-of-Work Bot-Abwehr', 'vgt-sentinel-ce'); ?></p>
                <p class="vgts-lang-en" style="margin: 5px 0 0 0; color: var(--vgts-text-secondary); font-size: 12px;"><?php esc_html_e('GDPR-compliant Zero-UI Proof-of-Work Bot Defense', 'vgt-sentinel-ce'); ?></p>
            </div>
        </div>

        <p class="vgts-lang-de" style="color: #94a3b8; font-size: 13px; line-height: 1.6; margin-bottom: 30px;">
            <?php esc_html_e('VGT Antibot eliminiert die Notwendigkeit für Captchas oder Checkboxen. Legitime Nutzer lösen unsichtbar im Hintergrund eine kryptografische SHA-256 Aufgabe (Proof-of-Work). Bots werden dadurch mathematisch und wirtschaftlich ineffizient blockiert. Die Engine ist tief im TITAN Netzwerk verankert.', 'vgt-sentinel-ce'); ?>
        </p>
        <p class="vgts-lang-en" style="color: #94a3b8; font-size: 13px; line-height: 1.6; margin-bottom: 30px;">
            <?php esc_html_e('VGT Antibot eliminates the need for captchas or checkboxes. Legitimate users invisibly solve a cryptographic SHA-256 challenge (Proof-of-Work) in the background. Bots are blocked because solving these challenges is computationally and economically inefficient for mass attacks. The engine is deeply anchored within the TITAN network.', 'vgt-sentinel-ce'); ?>
        </p>

        <!-- GLOBAL INFILTRATION TOGGLE -->
        <div class="vgts-switch-row" style="background: rgba(15, 23, 42, 0.4); padding: 20px; border-radius: 8px; border: 1px solid var(--vgts-border); margin-bottom: 20px;">
            <div class="vgts-label-group">
                <strong class="vgts-lang-de"><?php esc_html_e('Global Infiltration aktivieren', 'vgt-sentinel-ce'); ?></strong>
                <strong class="vgts-lang-en"><?php esc_html_e('Enable Global Infiltration', 'vgt-sentinel-ce'); ?></strong>
                <p class="vgts-lang-de"><?php esc_html_e('Injiziert Listener für alle DOM-Events und Formulare automatisch.', 'vgt-sentinel-ce'); ?></p>
                <p class="vgts-lang-en"><?php esc_html_e('Automatically injects listeners for all DOM events and forms.', 'vgt-sentinel-ce'); ?></p>
            </div>
            <label class="vgts-switch">
                <input type="checkbox" name="vgts_config[antibot_enabled]" value="1" <?php checked($antibot_active, 1); ?>>
                <span class="vgts-slider" style="background-color: <?php echo $antibot_active ? '#f59e0b' : '#334155'; ?>;"></span>
            </label>
        </div>

        <!-- DIFFICULTY LEVEL -->
        <div class="vgts-switch-row" style="background: rgba(15, 23, 42, 0.4); padding: 20px; border-radius: 8px; border: 1px solid var(--vgts-border); margin-bottom: 30px;">
            <div class="vgts-label-group">
                <strong class="vgts-lang-de"><?php esc_html_e('Kryptografische Komplexität', 'vgt-sentinel-ce'); ?></strong>
                <strong class="vgts-lang-en"><?php esc_html_e('Cryptographic Difficulty', 'vgt-sentinel-ce'); ?></strong>
                <p class="vgts-lang-de"><?php esc_html_e('Höhere Level beanspruchen mehr CPU beim Client (0-Padding Target).', 'vgt-sentinel-ce'); ?></p>
                <p class="vgts-lang-en"><?php esc_html_e('Higher levels demand more CPU capacity from the client (0-padding target).', 'vgt-sentinel-ce'); ?></p>
            </div>
            <div>
                <select name="vgts_config[antibot_difficulty]" class="vgts-input-select" style="background: #020617;">
                    <option value="2" <?php selected($difficulty, 2); ?>><?php esc_html_e('Level 2: Low-Latency Mode', 'vgt-sentinel-ce'); ?></option>
                    <option value="3" <?php selected($difficulty, 3); ?>><?php esc_html_e('Level 3: VGT Standard Protocol', 'vgt-sentinel-ce'); ?></option>
                    <option value="4" <?php selected($difficulty, 4); ?>><?php esc_html_e('Level 4: Maximum Security (High CPU)', 'vgt-sentinel-ce'); ?></option>
                </select>
            </div>
        </div>

        <!-- NATIVE INTEGRATIONS -->
        <div style="margin-bottom: 30px;">
            <h4 style="margin: 0 0 15px 0; color: #fff; font-size: 14px; display: flex; align-items: center; gap: 8px;">
                <span class="dashicons dashicons-admin-plugins" style="color:#f59e0b;"></span> 
                <?php esc_html_e('NATIVE INTEGRATIONS', 'vgt-sentinel-ce'); ?>
            </h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 15px;">
                <?php 
                $native_hooks_data = [
                    'antibot_comments' => ['label' => __('WP Comments', 'vgt-sentinel-ce'), 'desc_de' => __('Schützt den nativen WP Kommentarbereich.', 'vgt-sentinel-ce'), 'desc_en' => __('Protects native WP comments.', 'vgt-sentinel-ce')],
                    'antibot_woo'      => ['label' => __('WooCommerce', 'vgt-sentinel-ce'), 'desc_de' => __('Sichert Login, Registrierung & Checkout.', 'vgt-sentinel-ce'), 'desc_en' => __('Secures login, register & checkout.', 'vgt-sentinel-ce')],
                    'antibot_cf7'      => ['label' => __('Contact Form 7', 'vgt-sentinel-ce'), 'desc_de' => __('Blockiert Spam-Mails über CF7.', 'vgt-sentinel-ce'), 'desc_en' => __('Blocks spam emails via CF7.', 'vgt-sentinel-ce')],
                    'antibot_wpforms'  => ['label' => __('WPForms', 'vgt-sentinel-ce'), 'desc_de' => __('Validiert alle WPForms Einsendungen.', 'vgt-sentinel-ce'), 'desc_en' => __('Validates WPForms submissions.', 'vgt-sentinel-ce')],
                    'antibot_gform'    => ['label' => __('Gravity Forms', 'vgt-sentinel-ce'), 'desc_de' => __('Verhindert Bot-Einsendungen.', 'vgt-sentinel-ce'), 'desc_en' => __('Prevents bot submissions.', 'vgt-sentinel-ce')]
                ];
                foreach($native_hooks_data as $key => $data):
                    $is_active = !empty($opt[$key]) ? 1 : 0;
                ?>
                <div style="background: rgba(15, 23, 42, 0.4); padding: 15px 20px; border-radius: 8px; border: 1px solid var(--vgts-border); display: flex; justify-content: space-between; align-items: center; transition: all 0.2s ease;" onmouseover="this.style.borderColor='#f59e0b';" onmouseout="this.style.borderColor='var(--vgts-border)';" role="group">
                    <div>
                        <strong style="color: #fff; font-size: 13px; display: block; margin-bottom: 4px; letter-spacing: 0.5px;"><?php echo esc_html($data['label']); ?></strong>
                        <span class="vgts-lang-de" style="color: var(--vgts-text-secondary); font-size: 11px;"><?php echo esc_html($data['desc_de']); ?></span>
                        <span class="vgts-lang-en" style="color: var(--vgts-text-secondary); font-size: 11px;"><?php echo esc_html($data['desc_en']); ?></span>
                    </div>
                    <label class="vgts-switch" style="transform: scale(0.85); transform-origin: right center; margin-left: 15px;">
                        <input type="checkbox" name="vgts_config[<?php echo esc_attr($key); ?>]" value="1" <?php checked($is_active, 1); ?>>
                        <span class="vgts-slider" style="background-color: <?php echo $is_active ? '#f59e0b' : '#334155'; ?>;"></span>
                    </label>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- PLUGIN SCANNER & HOOKS -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 30px;">
            <div style="border: 1px solid var(--vgts-border); border-radius: 8px; padding: 25px; background: rgba(255,255,255,0.02);">
                <h4 style="margin: 0 0 15px 0; color: #fff; font-size: 14px;">
                    <span class="dashicons dashicons-search" style="color:#f59e0b;"></span> 
                    <?php esc_html_e('DEEP PLUGIN SCANNER', 'vgt-sentinel-ce'); ?>
                </h4>
                <p class="vgts-lang-de" style="color: var(--vgts-text-secondary); font-size: 12px; margin-bottom: 15px;"><?php esc_html_e('Extrahieren Sie Ausführungspfade (Hooks) aus beliebigen installierten Plugins via AST-Regex Parsing.', 'vgt-sentinel-ce'); ?></p>
                <p class="vgts-lang-en" style="color: var(--vgts-text-secondary); font-size: 12px; margin-bottom: 15px;"><?php esc_html_e('Extract execution pathways (hooks) from any installed module via AST-Regex parsing.', 'vgt-sentinel-ce'); ?></p>
                
                <div style="display: flex; gap: 10px;">
                    <select id="vgts-plugin-select" class="vgts-input-select" style="flex-grow: 1;">
                        <option value=""><?php esc_html_e('Select Target Module...', 'vgt-sentinel-ce'); ?></option>
                        <?php foreach ($all_plugins as $path => $plugin): ?>
                            <option value="<?php echo esc_attr((string)$path); ?>"><?php echo esc_html((string)$plugin['Name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="vgts-btn" id="vgts-scan-hooks-btn" style="color:#f59e0b; border: 1px solid #f59e0b; background: transparent;">
                        <?php esc_html_e('SCAN', 'vgt-sentinel-ce'); ?>
                    </button>
                </div>

                <div id="vgts-scan-results" style="display:none; margin-top: 15px;">
                    <strong style="color: #fff; font-size: 12px;"><?php esc_html_e('Identified Neural Pathways:', 'vgt-sentinel-ce'); ?></strong>
                    <div class="vgts-hook-list" id="vgts-hook-container"></div>
                </div>
            </div>

            <div style="border: 1px solid var(--vgts-border); border-radius: 8px; padding: 25px; background: rgba(255,255,255,0.02);">
                <h4 style="margin: 0 0 15px 0; color: #fff; font-size: 14px;">
                    <span class="dashicons dashicons-admin-links" style="color:#f59e0b;"></span> 
                    <?php esc_html_e('DYNAMIC EXECUTION HOOKS', 'vgt-sentinel-ce'); ?>
                </h4>
                <p class="vgts-lang-de" style="color: var(--vgts-text-secondary); font-size: 12px;"><?php esc_html_e('Hooks, die zusätzlich durch die PoW Matrix geschützt werden.', 'vgt-sentinel-ce'); ?></p>
                <p class="vgts-lang-en" style="color: var(--vgts-text-secondary); font-size: 12px;"><?php esc_html_e('Hooks that are additionally protected by the PoW Matrix.', 'vgt-sentinel-ce'); ?></p>

                <div class="vgts-hook-list" id="vgts-active-hooks" style="margin-top: 0;">
                    <?php if (empty($custom_hooks)): ?>
                        <span class="vgts-lang-de" style="color:var(--vgts-text-secondary); font-size:12px;"><?php esc_html_e('Keine dynamischen Hooks aktiv.', 'vgt-sentinel-ce'); ?></span>
                        <span class="vgts-lang-en" style="color:var(--vgts-text-secondary); font-size:12px;"><?php esc_html_e('No dynamic hooks active.', 'vgt-sentinel-ce'); ?></span>
                    <?php else: ?>
                        <?php foreach ($custom_hooks as $hook): ?>
                            <div class="vgts-hook-item">
                                <input type="checkbox" name="vgts_config[antibot_custom_hooks][]" value="<?php echo esc_attr((string)$hook); ?>" checked>
                                <?php echo esc_html((string)$hook); ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>
