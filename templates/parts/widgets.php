<?php
/**
 * Template part: Desktop Widgets — Powered by GeDefense v8.0.0
 * STATUS: 💠 DIAMANT VGT SUPREME
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$vis_config = get_option('vis_config', get_option('vgts_config', []));
$vis_config = is_array($vis_config) ? $vis_config : [];
$raw_whitelist = $vis_config['aegis_whitelist_ips'] ?? ($vis_config['whitelist_ips'] ?? '');
$whitelist_ips = array_filter(array_map('trim', explode("\n", (string)$raw_whitelist)));

$current_ip = \VisionGaia\WPDesk\WPDeskSecurity::client_ip();
$is_ip_whitelisted = in_array($current_ip, $whitelist_ips, true);
?>
            <!-- DESKTOP WIDGETS LAYER -->
            <div id="vgt-widgets-container" class="vgt-widgets-container">
                <!-- Clock Widget -->
                <div id="widget-clock" class="vgt-widget widget-clock glassmorphism absolute" style="z-index: 15; width: 260px;">
                    <div id="vgt-widget-clock-time">00:00</div>
                    <div id="vgt-widget-clock-date">Montag, 1. Januar</div>
                </div>
                <!-- System Status Widget -->
                <div id="widget-system" class="vgt-widget widget-system glassmorphism absolute" style="z-index: 15; width: 260px; display: flex; flex-direction: column; gap: 8px;">
                    <h4 class="vgt-widget-title" style="color: var(--vgt-accent-color); margin: 0 0 4px 0;">🛡️ System & GeDefense</h4>
                    
                    <!-- CPU Meter -->
                    <div class="vgt-widget-row-col">
                        <div class="vgt-widget-row-label">
                            <span>CPU-Last:</span>
                            <strong id="vgt-widget-cpu-val">--%</strong>
                        </div>
                        <div class="vgt-widget-meter-track">
                            <div class="vgt-widget-meter-bar" id="vgt-widget-cpu-bar" style="width: 0%;"></div>
                        </div>
                    </div>

                    <!-- RAM Meter -->
                    <div class="vgt-widget-row-col">
                        <div class="vgt-widget-row-label">
                            <span>Arbeitsspeicher:</span>
                            <strong id="vgt-widget-ram-val">-- MB</strong>
                        </div>
                        <div class="vgt-widget-meter-track">
                            <div class="vgt-widget-meter-bar" id="vgt-widget-ram-bar" style="width: 0%;"></div>
                        </div>
                    </div>

                    <div class="vgt-widget-divider" style="height: 1px; background: rgba(255, 255, 255, 0.05); margin: 4px 0;"></div>

                    <!-- Security Statuses -->
                    <div class="vgt-widget-row">
                        <span>ThroneGuard:</span>
                        <strong id="vgt-widget-tg-status" style="color: #10b981;">Bereit</strong>
                    </div>
                    <div class="vgt-widget-row">
                        <span>GeDefense Core:</span>
                        <strong id="vgt-widget-sentinel-status" style="color: #00f0ff;">19 Module aktiv</strong>
                    </div>
                    <div class="vgt-widget-row">
                        <span>Aegis Whitelist:</span>
                        <strong style="color: <?php echo $is_ip_whitelisted ? '#10b981' : '#f43f5e'; ?>; font-size: 11px;">
                            <?php echo $is_ip_whitelisted ? 'Gelistet (Sicher)' : 'Nicht gelistet ⚠️'; ?>
                        </strong>
                    </div>
                    <div class="vgt-widget-row">
                        <span>Cerberus Banns:</span>
                        <strong id="vgt-widget-bans-status">-- IPs</strong>
                    </div>
                </div>
                <!-- Notes Widget -->
                <div id="widget-notes" class="vgt-widget widget-notes glassmorphism absolute" style="z-index: 15; width: 260px; height: 160px; display: flex; flex-direction: column;">
                    <h4 class="vgt-widget-title">Notizen</h4>
                    <textarea id="vgt-widget-notes-text" class="vgt-widget-textarea" placeholder="Schnelle Gedanken aufschreiben..."></textarea>
                </div>
                <!-- Live Attack Stream Widget -->
                <div id="widget-threat-ticker" class="vgt-widget widget-threat-ticker glassmorphism absolute" style="z-index: 15; width: 260px; display: flex; flex-direction: column; gap: 8px;">
                    <h4 class="vgt-widget-title" style="color: #f43f5e; display: flex; align-items: center; justify-content: space-between; margin: 0 0 6px 0;">
                        <span>🛡️ Live Threat-Stream</span>
                        <span class="vgt-widget-pulse-dot" style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #ef4444; box-shadow: 0 0 8px #ef4444;"></span>
                    </h4>
                    <div id="vgt-threat-stream-list" style="display: flex; flex-direction: column; gap: 6px;">
                        <!-- Populate dynamically by JS -->
                    </div>
                </div>

                <!-- Sovereign Optimizer Widget -->
                <div id="widget-optimizer" class="vgt-widget widget-optimizer glassmorphism absolute" style="z-index: 15; width: 260px; display: flex; flex-direction: column; gap: 8px;">
                    <h4 class="vgt-widget-title" style="color: #10b981; margin: 0 0 6px 0;">⚡ Transient Optimizer</h4>
                    <div class="vgt-widget-row" style="font-size: 11px; margin-bottom: 6px;">
                        <span>Bereinigbar:</span>
                        <strong id="vgt-opt-overhead" style="color: #ffffff;">-- KB</strong>
                    </div>
                    <div class="vgt-widget-row" style="font-size: 11px; margin-bottom: 6px;">
                        <span>Transients:</span>
                        <strong id="vgt-opt-transients" style="color: #ffffff;">--</strong>
                    </div>
                    <button onclick="VGTDeskEngine.optimizeDatabase(this)" class="vgt-btn-primary vgt-opt-btn" style="margin-top: 6px; width: 100%; padding: 6px 12px; font-size: 11px; border-radius: 8px; background: linear-gradient(135deg, #10b981, #059669); border: none; cursor: pointer; color: #fff; font-weight: 700; transition: all 0.2s; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);">
                        Bereinigung starten
                    </button>
                </div>
            </div>