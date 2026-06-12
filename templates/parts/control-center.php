<?php
/**
 * Template part: Control Center
 * STATUS: 💠 DIAMANT VGT SUPREME
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}
?>
    <!-- PREMIUM CONTROL CENTER -->
    <div id="vgt-control-center" class="vgt-control-center hidden glassmorphism">
        <div class="vgt-cc-header">
            <span class="vgt-cc-title">Kontrollzentrum</span>
            <span class="vgt-cc-status">Online</span>
        </div>
        <div class="vgt-cc-toggles">
            <div class="vgt-cc-toggle" onclick="VGTDeskEngine.toggleCCToggle('sound')">
                <div class="vgt-cc-toggle-icon" id="cc-toggle-sound">🔊</div>
                <div class="vgt-cc-toggle-label">Sound</div>
            </div>
            <div class="vgt-cc-toggle" onclick="VGTDeskEngine.toggleCCToggle('widgets')">
                <div class="vgt-cc-toggle-icon" id="cc-toggle-widgets">🧩</div>
                <div class="vgt-cc-toggle-label">Widgets</div>
            </div>
            <div class="vgt-cc-toggle" onclick="VGTDeskEngine.toggleCCToggle('icons')">
                <div class="vgt-cc-toggle-icon" id="cc-toggle-icons">🖥️</div>
                <div class="vgt-cc-toggle-label">Symbole</div>
            </div>
            <div class="vgt-cc-toggle" onclick="VGTDeskEngine.toggleCCToggle('blur')">
                <div class="vgt-cc-toggle-icon" id="cc-toggle-blur">✨</div>
                <div class="vgt-cc-toggle-label">Blur</div>
            </div>
        </div>
        
        <div class="vgt-cc-section">
            <span class="vgt-cc-section-title">Widget-Sichtbarkeit</span>
            <div class="vgt-cc-widget-toggles">
                <div class="vgt-cc-widget-toggle active" id="cc-widget-toggle-clock" onclick="VGTDeskEngine.toggleCCWidget('clock')">
                    <span class="vgt-cc-wt-icon">🕐</span>
                    <span class="vgt-cc-wt-label">Uhr</span>
                </div>
                <div class="vgt-cc-widget-toggle active" id="cc-widget-toggle-system" onclick="VGTDeskEngine.toggleCCWidget('system')">
                    <span class="vgt-cc-wt-icon">📊</span>
                    <span class="vgt-cc-wt-label">System</span>
                </div>
                <div class="vgt-cc-widget-toggle active" id="cc-widget-toggle-notes" onclick="VGTDeskEngine.toggleCCWidget('notes')">
                    <span class="vgt-cc-wt-icon">📝</span>
                    <span class="vgt-cc-wt-label">Notizen</span>
                </div>
                <div class="vgt-cc-widget-toggle active" id="cc-widget-toggle-threat-ticker" onclick="VGTDeskEngine.toggleCCWidget('threat-ticker')">
                    <span class="vgt-cc-wt-icon">🛡️</span>
                    <span class="vgt-cc-wt-label">Threat-Stream</span>
                </div>
                <?php if (!defined('VIS_VERSION')): ?>
                <div class="vgt-cc-widget-toggle active" id="cc-widget-toggle-dattrack" onclick="VGTDeskEngine.toggleCCWidget('dattrack')">
                    <span class="vgt-cc-wt-icon">📊</span>
                    <span class="vgt-cc-wt-label">Dattrack</span>
                </div>
                <?php endif; ?>
                <div class="vgt-cc-widget-toggle active" id="cc-widget-toggle-optimizer" onclick="VGTDeskEngine.toggleCCWidget('optimizer')">
                    <span class="vgt-cc-wt-icon">⚡</span>
                    <span class="vgt-cc-wt-label">Optimizer</span>
                </div>
            </div>
        </div>

        <div class="vgt-cc-section">
            <span class="vgt-cc-section-title">System-Latenz (Live)</span>
            <canvas id="vgt-cc-graph" width="230" height="70" class="vgt-cc-canvas"></canvas>
        </div>

        <div class="vgt-cc-section">
            <span class="vgt-cc-section-title">Schnell-Bypässe</span>
            <div class="vgt-cc-row-buttons">
                <button class="vgt-btn-secondary" style="font-size:10px; padding:6px 10px;" onclick="VGTDeskEngine.resetIconGrid()">Grid reset</button>
                <a href="<?php echo esc_url(wp_nonce_url(admin_url('index.php?vgt_action=disable_desk'), 'vgt_toggle_desktop')); ?>" class="vgt-btn-danger" style="font-size:10px; padding:6px 10px; line-height:1.2;">Bypass</a>
            </div>
        </div>
    </div>
