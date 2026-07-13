<?php
/**
 * Template: Recovery Center — VGT Design System
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (!current_user_can(\VisionGaia\WPDesk\WPDeskRecovery::CAPABILITY)) {
    wp_die(esc_html__('Zu dieser Aktion besitzen Sie nicht genügend Rechte.', 'vgt-wp-desk'));
}

$user_id = get_current_user_id();
$user_settings = \VisionGaia\WPDesk\WPDeskSettings::get_user_settings($user_id);
$auto_redirect_active = !empty($user_settings['auto_redirect']) && ($user_settings['auto_redirect'] === true || $user_settings['auto_redirect'] === 'true');
$bypass_active = isset($_COOKIE['vgt_desk_bypass']) && $_COOKIE['vgt_desk_bypass'] === '1';

$dattrack_active = get_option('vgt_dattrack_enabled') === 'true';
$sentinel_active = (get_option('vgt_sentinel_enabled') === 'true') || defined('VIS_VERSION');
$throne_guard_active = \VisionGaia\WPDesk\WPDeskSecurity::throne_guard_active();
?>
<div class="vgt-recovery-shell vgt-ds-root">
    <div class="vgt-ds-card">
        <div class="vgt-ds-page-header">
            <div>
                <span class="vgt-ds-eyebrow">VisionGaia WP-Desk</span>
                <h1 class="vgt-ds-page-title">Recovery &amp; Diagnostics Center</h1>
            </div>
            <span class="vgt-ds-badge"><?php echo esc_html(defined('VGT_WPDESK_VERSION_LABEL') ? VGT_WPDESK_VERSION_LABEL : 'V2.0 Beta v1'); ?></span>
        </div>

        <?php if (!empty($message)): ?>
            <div class="vgt-ds-notice" style="border-left: 4px solid var(--vgt-ds-success); margin-bottom: 16px; padding: 12px 14px; color: #a7f3d0;">
                ✓ <?php echo esc_html($message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="vgt-ds-notice" style="border-left: 4px solid var(--vgt-ds-danger); margin-bottom: 16px; padding: 12px 14px; color: #fecaca;">
                ⚠ <?php echo esc_html($error); ?>
            </div>
        <?php endif; ?>

        <p style="color: var(--vgt-ds-muted); font-size: 14px; line-height: 1.55; margin: 0 0 24px;">
            Sollte es durch Fehlkonfigurationen oder Loop-Redirects zu Problemen im WordPress-Backend kommen, können Sie über diese Oberfläche Notfall-Aktionen ausführen und den klassischen Modus erzwingen.
        </p>

        <h2 style="color: var(--vgt-ds-text-primary); font-size: 15px; font-weight: 700; margin: 0 0 14px;">System-Sicherheitsstatus</h2>
        <div class="vgt-ds-stat-grid" style="margin-bottom: 28px;">
            <div class="vgt-ds-stat-card">
                <span class="vgt-ds-eyebrow">Sentinel WAF</span>
                <div style="display:flex;align-items:center;justify-content:space-between;margin-top:8px;">
                    <strong style="color:#fff;">Status</strong>
                    <span class="<?php echo $sentinel_active ? 'vgt-ds-badge-success' : 'vgt-ds-badge-danger'; ?> vgt-ds-badge">
                        <?php echo $sentinel_active ? 'AKTIV' : 'INAKTIV'; ?>
                    </span>
                </div>
            </div>
            <div class="vgt-ds-stat-card">
                <span class="vgt-ds-eyebrow">Throne Guard</span>
                <div style="display:flex;align-items:center;justify-content:space-between;margin-top:8px;">
                    <strong style="color:#fff;">Kernel Lock</strong>
                    <span class="<?php echo $throne_guard_active ? 'vgt-ds-badge-success' : 'vgt-ds-badge-danger'; ?> vgt-ds-badge">
                        <?php echo $throne_guard_active ? 'SCHLÜSSEL DA' : 'OFFEN'; ?>
                    </span>
                </div>
            </div>
            <div class="vgt-ds-stat-card">
                <span class="vgt-ds-eyebrow">Desktop Redirect</span>
                <div style="display:flex;align-items:center;justify-content:space-between;margin-top:8px;">
                    <strong style="color:#fff;">Auto-Umlenkung</strong>
                    <span class="<?php echo $auto_redirect_active ? 'vgt-ds-badge-success' : 'vgt-ds-badge'; ?> vgt-ds-badge">
                        <?php echo $auto_redirect_active ? 'AKTIVIERT' : 'DEAKTIVIERT'; ?>
                    </span>
                </div>
            </div>
            <div class="vgt-ds-stat-card">
                <span class="vgt-ds-eyebrow">Dattrack</span>
                <div style="display:flex;align-items:center;justify-content:space-between;margin-top:8px;">
                    <strong style="color:#fff;">Telemetry</strong>
                    <span class="<?php echo $dattrack_active ? 'vgt-ds-badge-success' : 'vgt-ds-badge'; ?> vgt-ds-badge">
                        <?php echo $dattrack_active ? 'AKTIVIERT' : 'DEAKTIVIERT'; ?>
                    </span>
                </div>
            </div>
        </div>

        <h2 style="color: var(--vgt-ds-text-primary); font-size: 15px; font-weight: 700; margin: 0 0 14px; border-top: 1px solid var(--vgt-ds-border); padding-top: 22px;">
            Notfall-Interventionen
        </h2>

        <div style="display:flex;flex-direction:column;gap:14px;">
            <div class="vgt-ds-stat-card" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:14px;">
                <div style="max-width:560px;">
                    <h3 style="margin:0;color:#fff;font-size:14px;">Klassische Ansicht erzwingen</h3>
                    <p style="margin:6px 0 0;font-size:12px;color:var(--vgt-ds-muted);line-height:1.45;">
                        Setzt ein Bypass-Cookie (30 Tage). Bypass aktiv: <strong><?php echo $bypass_active ? 'JA' : 'NEIN'; ?></strong>
                    </p>
                </div>
                <form method="POST" action="">
                    <?php wp_nonce_field('vgt_recovery_action', 'vgt_recovery_nonce'); ?>
                    <input type="hidden" name="recovery_action" value="force_classic">
                    <button type="submit" class="vgt-ds-btn vgt-ds-btn-primary">Classic erzwingen</button>
                </form>
            </div>

            <div class="vgt-ds-stat-card" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:14px;">
                <div style="max-width:560px;">
                    <h3 style="margin:0;color:#fff;font-size:14px;">Desktop Auto-Redirect deaktivieren</h3>
                    <p style="margin:6px 0 0;font-size:12px;color:var(--vgt-ds-muted);line-height:1.45;">
                        Schaltet die automatische Umlenkung auf den Desktop für Ihren Account ab.
                    </p>
                </div>
                <form method="POST" action="">
                    <?php wp_nonce_field('vgt_recovery_action', 'vgt_recovery_nonce'); ?>
                    <input type="hidden" name="recovery_action" value="disable_redirect">
                    <button type="submit" class="vgt-ds-btn vgt-ds-btn-ghost">Redirect abschalten</button>
                </form>
            </div>

            <div class="vgt-ds-stat-card" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:14px;">
                <div style="max-width:560px;">
                    <h3 style="margin:0;color:#fff;font-size:14px;">Dattrack Telemetrie global abschalten</h3>
                    <p style="margin:6px 0 0;font-size:12px;color:var(--vgt-ds-muted);line-height:1.45;">
                        Stoppt die Dattrack-Erfassung systemweit.
                    </p>
                </div>
                <form method="POST" action="">
                    <?php wp_nonce_field('vgt_recovery_action', 'vgt_recovery_nonce'); ?>
                    <input type="hidden" name="recovery_action" value="disable_dattrack">
                    <button type="submit" class="vgt-ds-btn vgt-ds-btn-ghost">Telemetry stoppen</button>
                </form>
            </div>

            <div class="vgt-ds-stat-card" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:14px;">
                <div style="max-width:560px;">
                    <h3 style="margin:0;color:#fff;font-size:14px;">Fehlerdiagnose exportieren</h3>
                    <p style="margin:6px 0 0;font-size:12px;color:var(--vgt-ds-muted);line-height:1.45;">
                        JSON mit Serverumgebung, Plugins und Konfiguration.
                    </p>
                </div>
                <form method="POST" action="">
                    <?php wp_nonce_field('vgt_recovery_action', 'vgt_recovery_nonce'); ?>
                    <input type="hidden" name="recovery_action" value="export_diagnostics">
                    <button type="submit" class="vgt-ds-btn vgt-ds-btn-primary">Diagnose herunterladen</button>
                </form>
            </div>
        </div>
    </div>
</div>
