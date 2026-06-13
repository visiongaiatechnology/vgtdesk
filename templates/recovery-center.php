<?php
/**
 * Template part: Recovery Center
 * STATUS: 💠 DIAMANT VGT SUPREME
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

// Ensure user has capability
if (!current_user_can('manage_options')) {
    wp_die(esc_html__('Zu dieser Aktion besitzen Sie nicht genügend Rechte.', 'vgt-wp-desk'));
}

// Fetch states
$user_id = get_current_user_id();
$user_settings = \VisionGaia\WPDesk\WPDeskSettings::get_user_settings($user_id);
$auto_redirect_active = $user_settings['auto_redirect'] ?? false;
$bypass_active = isset($_COOKIE['vgt_desk_bypass']) && $_COOKIE['vgt_desk_bypass'] === '1';

$dattrack_active = get_option('vgt_dattrack_enabled') === 'true';
$sentinel_active = (get_option('vgt_sentinel_enabled') === 'true') || defined('VIS_VERSION');
$throne_guard_active = !empty(get_option('mcp_superkey_hash', ''));
?>
<div class="wrap" style="max-width: 900px; margin: 30px auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;">
    
    <div style="background: #0f172a; border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.08); padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); color: #e2e8f0;">
        
        <!-- Header -->
        <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid rgba(255, 255, 255, 0.08); padding-bottom: 20px; margin-bottom: 25px;">
            <div>
                <span style="font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; color: #6366f1; font-weight: 700;">VisionGaia WP-Desk</span>
                <h1 style="color: #ffffff; margin: 4px 0 0 0; font-size: 24px; font-weight: 800; display: flex; align-items: center; gap: 8px;">
                    🛡️ Recovery & Diagnostics Center
                </h1>
            </div>
            <span style="background: rgba(99, 102, 241, 0.15); color: #818cf8; border: 1px solid rgba(99, 102, 241, 0.3); border-radius: 20px; padding: 4px 12px; font-size: 11px; font-weight: 600;">
                Beta v4 Stable Candidate
            </span>
        </div>

        <?php if (!empty($message)): ?>
            <div style="background: rgba(16, 185, 129, 0.1); border-left: 4px solid #10b981; border-radius: 4px; padding: 12px; margin-bottom: 20px; color: #a7f3d0; font-size: 13px;">
                ✓ <?php echo esc_html($message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div style="background: rgba(239, 68, 68, 0.1); border-left: 4px solid #ef4444; border-radius: 4px; padding: 12px; margin-bottom: 20px; color: #fecaca; font-size: 13px;">
                ⚠️ <?php echo esc_html($error); ?>
            </div>
        <?php endif; ?>

        <p style="color: #94a3b8; font-size: 14px; line-height: 1.5; margin-bottom: 25px;">
            Sollte es durch Fehlkonfigurationen oder Loop-Redirects zu Problemen im WordPress-Backend kommen, können Sie über diese Weboberfläche administrative Notfall-Aktionen durchführen und den klassischen Modus erzwingen.
        </p>

        <!-- Status Grid -->
        <h2 style="color: #f8fafc; font-size: 16px; font-weight: 700; margin-bottom: 15px;">System-Sicherheitsstatus</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; margin-bottom: 30px;">
            
            <!-- Sentinel Card -->
            <div style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); border-radius: 8px; padding: 15px;">
                <span style="font-size: 11px; color: #94a3b8; text-transform: uppercase;">Sentinel WAF</span>
                <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 8px;">
                    <span style="font-size: 16px; font-weight: 700; color: #ffffff;">Status</span>
                    <span style="background: <?php echo $sentinel_active ? 'rgba(16, 185, 129, 0.15)' : 'rgba(239, 68, 68, 0.15)'; ?>; color: <?php echo $sentinel_active ? '#34d399' : '#f87171'; ?>; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600;">
                        <?php echo $sentinel_active ? 'AKTIV' : 'INAKTIV'; ?>
                    </span>
                </div>
            </div>

            <!-- Throne Guard Card -->
            <div style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); border-radius: 8px; padding: 15px;">
                <span style="font-size: 11px; color: #94a3b8; text-transform: uppercase;">Throne Guard</span>
                <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 8px;">
                    <span style="font-size: 16px; font-weight: 700; color: #ffffff;">Kernel Lock</span>
                    <span style="background: <?php echo $throne_guard_active ? 'rgba(16, 185, 129, 0.15)' : 'rgba(239, 68, 68, 0.15)'; ?>; color: <?php echo $throne_guard_active ? '#34d399' : '#f87171'; ?>; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600;">
                        <?php echo $throne_guard_active ? 'SCHLÜSSEL DA' : 'OFFEN'; ?>
                    </span>
                </div>
            </div>

            <!-- Auto-Redirect Card -->
            <div style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); border-radius: 8px; padding: 15px;">
                <span style="font-size: 11px; color: #94a3b8; text-transform: uppercase;">Desktop Redirect</span>
                <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 8px;">
                    <span style="font-size: 16px; font-weight: 700; color: #ffffff;">Auto-Umlenkung</span>
                    <span style="background: <?php echo $auto_redirect_active ? 'rgba(16, 185, 129, 0.15)' : 'rgba(255, 255, 255, 0.08)'; ?>; color: <?php echo $auto_redirect_active ? '#34d399' : '#94a3b8'; ?>; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600;">
                        <?php echo $auto_redirect_active ? 'AKTIVIERT' : 'DEAKTIVIERT'; ?>
                    </span>
                </div>
            </div>

            <!-- Dattrack Card -->
            <div style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); border-radius: 8px; padding: 15px;">
                <span style="font-size: 11px; color: #94a3b8; text-transform: uppercase;">Dattrack Telemetry</span>
                <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 8px;">
                    <span style="font-size: 16px; font-weight: 700; color: #ffffff;">Sovereign Tracker</span>
                    <span style="background: <?php echo $dattrack_active ? 'rgba(6, 182, 212, 0.15)' : 'rgba(255, 255, 255, 0.08)'; ?>; color: <?php echo $dattrack_active ? '#22d3ee' : '#94a3b8'; ?>; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600;">
                        <?php echo $dattrack_active ? 'AKTIVIERT' : 'DEAKTIVIERT'; ?>
                    </span>
                </div>
            </div>

        </div>

        <!-- Recovery Operations -->
        <h2 style="color: #f8fafc; font-size: 16px; font-weight: 700; margin-bottom: 15px; border-top: 1px solid rgba(255, 255, 255, 0.08); padding-top: 25px;">Notfall-Interventionen</h2>
        
        <div style="display: flex; flex-direction: column; gap: 15px;">
            
            <!-- Option 1: Force Classic View -->
            <div style="background: rgba(255, 255, 255, 0.01); border: 1px solid rgba(255, 255, 255, 0.03); border-radius: 8px; padding: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                <div style="max-width: 550px;">
                    <h3 style="margin: 0; color: #ffffff; font-size: 14px; font-weight: 600;">Klassische Ansicht erzwingen</h3>
                    <p style="margin: 4px 0 0 0; font-size: 12px; color: #94a3b8; line-height: 1.4;">
                        Setzt ein Cookie in Ihrem Browser, das den Desktop-Modus vollständig umgeht und Sie auf das klassische WordPress-Backend leitet (Bypass aktiv: <strong><?php echo $bypass_active ? 'JA' : 'NEIN'; ?></strong>).
                    </p>
                </div>
                <form method="POST" action="">
                    <?php wp_nonce_field('vgt_recovery_action', 'vgt_recovery_nonce'); ?>
                    <input type="hidden" name="recovery_action" value="force_classic">
                    <button type="submit" style="background: #4f46e5; color: #ffffff; border: none; padding: 8px 16px; font-size: 12px; font-weight: 600; border-radius: 6px; cursor: pointer; transition: background 0.2s;">
                        Classic erzwingen
                    </button>
                </form>
            </div>

            <!-- Option 2: Disable Auto-Redirect -->
            <div style="background: rgba(255, 255, 255, 0.01); border: 1px solid rgba(255, 255, 255, 0.03); border-radius: 8px; padding: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                <div style="max-width: 550px;">
                    <h3 style="margin: 0; color: #ffffff; font-size: 14px; font-weight: 600;">Desktop Auto-Redirect deaktivieren</h3>
                    <p style="margin: 4px 0 0 0; font-size: 12px; color: #94a3b8; line-height: 1.4;">
                        Schaltet die automatische Backend-Umlenkung auf die Desktop-Oberfläche für Ihren aktuellen Administrator-Account dauerhaft ab.
                    </p>
                </div>
                <form method="POST" action="">
                    <?php wp_nonce_field('vgt_recovery_action', 'vgt_recovery_nonce'); ?>
                    <input type="hidden" name="recovery_action" value="disable_redirect">
                    <button type="submit" style="background: #374151; color: #ffffff; border: 1px solid #4b5563; padding: 8px 16px; font-size: 12px; font-weight: 600; border-radius: 6px; cursor: pointer; transition: background 0.2s;">
                        Redirect abschalten
                    </button>
                </form>
            </div>

            <!-- Option 3: Disable Dattrack Global -->
            <div style="background: rgba(255, 255, 255, 0.01); border: 1px solid rgba(255, 255, 255, 0.03); border-radius: 8px; padding: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                <div style="max-width: 550px;">
                    <h3 style="margin: 0; color: #ffffff; font-size: 14px; font-weight: 600;">Dattrack Telemetrie global abschalten</h3>
                    <p style="margin: 4px 0 0 0; font-size: 12px; color: #94a3b8; line-height: 1.4;">
                        Deaktiviert die Dattrack-Erfassung systemweit und stoppt alle API-Anfragen an den Collector-Endpunkt.
                    </p>
                </div>
                <form method="POST" action="">
                    <?php wp_nonce_field('vgt_recovery_action', 'vgt_recovery_nonce'); ?>
                    <input type="hidden" name="recovery_action" value="disable_dattrack">
                    <button type="submit" style="background: #374151; color: #ffffff; border: 1px solid #4b5563; padding: 8px 16px; font-size: 12px; font-weight: 600; border-radius: 6px; cursor: pointer; transition: background 0.2s;">
                        Telemetry stoppen
                    </button>
                </form>
            </div>

            <!-- Option 4: Export Diagnostics -->
            <div style="background: rgba(255, 255, 255, 0.01); border: 1px solid rgba(255, 255, 255, 0.03); border-radius: 8px; padding: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                <div style="max-width: 550px;">
                    <h3 style="margin: 0; color: #ffffff; font-size: 14px; font-weight: 600;">Fehlerdiagnose exportieren</h3>
                    <p style="margin: 4px 0 0 0; font-size: 12px; color: #94a3b8; line-height: 1.4;">
                        Generiert eine JSON-Datei mit Serverumgebungsdaten, aktiven Plugins und Konfigurationswerten zur externen Fehlerbehebung.
                    </p>
                </div>
                <form method="POST" action="">
                    <?php wp_nonce_field('vgt_recovery_action', 'vgt_recovery_nonce'); ?>
                    <input type="hidden" name="recovery_action" value="export_diagnostics">
                    <button type="submit" style="background: #0891b2; color: #ffffff; border: none; padding: 8px 16px; font-size: 12px; font-weight: 600; border-radius: 6px; cursor: pointer; transition: background 0.2s;">
                        Diagnose herunterladen
                    </button>
                </form>
            </div>

        </div>

    </div>
</div>
