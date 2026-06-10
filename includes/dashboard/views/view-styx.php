<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VIEW: STYX LITE (OUTBOUND CONTROL)
 * STATUS: PLATIN VGT STATUS (Hardened & i18n)
 * MODULE: OUTBOUND TELEMETRY & SUPPLY-CHAIN SHIELD
 * TEXTDOMAIN: vgt-sentinel-ce
 */

$opt       = (array) get_option('vgts_config', []);
$styx_kill = isset($opt['styx_kill_telemetry']) ? (bool) $opt['styx_kill_telemetry'] : true;
?>

<div id="vgts-styx-container" class="vgts-view-animate">
    
    <!-- LANGUAGE TOGGLE -->
    <div class="vgts-toggle-wrapper">
        <label class="vgts-toggle-label">
            <span class="vgts-toggle-text vgts-text-de"><?php esc_html_e('DE', 'vgt-sentinel-ce'); ?></span>
            <div class="vgts-switch-track">
                <div class="vgts-switch-thumb"></div>
            </div>
            <span class="vgts-toggle-text vgts-text-en"><?php esc_html_e('EN', 'vgt-sentinel-ce'); ?></span>
            <input type="checkbox" id="vgts-styx-lang-toggle" style="display: none;">
        </label>
    </div>

    <div class="vgts-card vgts-styx-card">
        
        <!-- HEADER -->
        <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid var(--vgts-border);">
            <span class="dashicons dashicons-networking" style="font-size: 32px; width: 32px; height: 32px; color: #6366f1;"></span>
            <div>
                <h2 style="margin: 0; color: #fff; font-size: 1.2rem; font-weight: 700;"><?php esc_html_e('STYX LITE: OUTBOUND CONTROL', 'vgt-sentinel-ce'); ?></h2>
                <p style="margin: 5px 0 0 0; color: var(--vgts-text-secondary); font-size: 12px;"><?php esc_html_e('Data Exfiltration & Telemetry Shield', 'vgt-sentinel-ce'); ?></p>
            </div>
        </div>

        <!-- DESCRIPTION MATRIX -->
        <p class="vgts-lang-de" style="color: #94a3b8; font-size: 13px; line-height: 1.6; margin-bottom: 30px;">
            <?php esc_html_e('STYX operiert auf Netzwerkebene und überwacht ausgehende HTTP-Anfragen des WordPress-Kernels. Nach der Aktivierung kappt das System native Verbindungen zur wp.org API (Telemetrie, Core-Updates, Statistiken). Dies verhindert Supply-Chain-Lecks und blockiert kompromittierte Plugins bei dem Versuch, Daten an externe C&C-Server zu exfiltrieren.', 'vgt-sentinel-ce'); ?>
        </p>
        <p class="vgts-lang-en" style="color: #94a3b8; font-size: 13px; line-height: 1.6; margin-bottom: 30px;">
            <?php esc_html_e('STYX operates at the network level and monitors outgoing HTTP requests from the WordPress kernel. When activated, the system severs native connections to the wp.org API (Telemetry, Core-Updates, Stats). This blocks supply-chain leaks and prevents compromised plugins from exfiltrating data to external C&C servers.', 'vgt-sentinel-ce'); ?>
        </p>

        <!-- CONFIGURATION PANEL -->
        <div style="background: rgba(15, 23, 42, 0.4); border: 1px solid var(--vgts-border); border-radius: 8px; padding: 20px; margin-bottom: 25px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h4 style="margin: 0 0 5px 0; color: #fff; font-size: 14px;"><?php esc_html_e('WP Telemetry Kill Switch', 'vgt-sentinel-ce'); ?></h4>
                    <p style="margin: 0; color: var(--vgts-text-secondary); font-size: 12px;">
                        <?php 
                        echo wp_kses_post(
                            sprintf(
                                /* translators: %s: api.wordpress.org code tag */
                                esc_html__('Blocks %s and associated trackers.', 'vgt-sentinel-ce'),
                                '<code>api.wordpress.org</code>'
                            )
                        );
                        ?>
                    </p>
                </div>
                
                <label class="vgt-styx-toggle">
                    <input type="checkbox" name="vgts_config[styx_kill_telemetry]" value="1" <?php checked($styx_kill, true); ?>>
                    <span class="vgt-styx-slider"></span>
                </label>
            </div>
        </div>

        <!-- ARCHITECTURE INFO -->
        <div style="padding: 15px; background: rgba(99, 102, 241, 0.05); border: 1px solid rgba(99, 102, 241, 0.1); border-radius: 6px;">
            <h5 style="margin: 0 0 10px 0; color: #6366f1; font-size: 12px; font-weight: 700; letter-spacing: 0.5px;"><?php esc_html_e('STYX ARCHITECTURE:', 'vgt-sentinel-ce'); ?></h5>
            <p class="vgts-lang-de" style="margin: 0; color: #94a3b8; font-size: 12px; line-height: 1.5;">
                <?php esc_html_e('STYX LITE nutzt strukturierte Phantom-Antworten. WordPress "denkt" weiterhin, es sei mit dem Internet verbunden, erhält jedoch lokal generierte, leere Datenmodelle. Dies verhindert Fehlfunktionen im Core, während die Datensparsamkeit auf Maximum gehärtet wird.', 'vgt-sentinel-ce'); ?>
            </p>
            <p class="vgts-lang-en" style="margin: 0; color: #94a3b8; font-size: 12px; line-height: 1.5;">
                <?php esc_html_e('STYX LITE utilizes structured phantom responses. WordPress "thinks" it is still connected to the internet but receives locally generated, empty data models instead. This prevents core malfunctions while hardening data privacy to the maximum level.', 'vgt-sentinel-ce'); ?>
            </p>
        </div>
    </div>
</div>
