<?php
/**
 * Template part: Topbar System Header
 * STATUS: 💠 DIAMANT VGT SUPREME
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}
?>
        <!-- STATUS BAR (MENÜOBEN) -->
        <header class="vgt-topbar" id="top-bar">
            <div class="vgt-topbar-left">
                <div class="vgt-logo-group" onclick="VGTDeskEngine.toggleStartMenu(event)">
                    <span class="vgt-topbar-dot" id="vgt-topbar-dot"></span>
                    <span class="vgt-topbar-title" id="vgt-topbar-title">VGT</span>
                    <span class="vgt-topbar-subtitle">WP-Desk</span>
                </div>
                <span class="vgt-topbar-separator">|</span>
                <span class="vgt-topbar-branding">Powered by <a href="https://visiongaiatechnology.de" target="_blank" style="color: inherit; text-decoration: underline; transition: color 0.2s;">VisionGaiaTechnology</a></span>
                <span class="vgt-topbar-version-badge"><?php echo esc_html(defined('VGT_WPDESK_VERSION_LABEL') ? VGT_WPDESK_VERSION_LABEL : 'V2.0 Beta v1'); ?></span>
                <span class="vgt-topbar-separator">|</span>
                <div class="vgt-topbar-nav">
                    <button class="vgt-topbar-btn" onclick="VGTDeskEngine.openWindow('welcome')">Home</button>
                    <button class="vgt-topbar-btn" onclick="VGTDeskEngine.openWindow('vgt-security-center')">Sicherheits-Center</button>
                    <button class="vgt-topbar-btn" onclick="VGTDeskEngine.openWindow('settings')">Command Center</button>
                </div>
            </div>

            <!-- Uhrzeit und Status -->
            <div class="vgt-topbar-right" style="display: flex; align-items: center; gap: 8px;">
                <div class="vgt-profile-badge" onclick="VGTDeskEngine.toggleControlCenter(event)">
                    <span class="vgt-profile-icon" id="vgt-accent-badge">⚙️</span>
                    <span class="vgt-profile-name"><?php echo esc_html($current_user->display_name); ?></span>
                </div>
                <a href="<?php echo esc_url(wp_logout_url(admin_url('admin.php?page=vgt-wp-desk'))); ?>" class="vgt-topbar-logout-btn" title="Abmelden">🚪 Logout</a>
                <div id="vgt-clock" class="vgt-clock-badge" onclick="VGTDeskEngine.openWindow('calendar')" style="cursor:pointer;">00:00</div>
            </div>
        </header>
