<?php 
declare(strict_types=1);
if (!defined('ABSPATH')) exit; 
/**
 * SIDEBAR VIEW: COMMUNITY CORE EDITION
 * STATUS: PLATIN VGT STATUS (Hardened & i18n)
 * MODULE: GLOBAL NAVIGATION & SYSTEM TELEMETRY
 * TEXTDOMAIN: vgt-sentinel-ce
 */
?>

<aside class="vgts-sidebar">
    <!-- BRANDING SECTION -->
    <div class="vgts-brand">
        <img src="<?php echo esc_url(defined('VGTS_SENTINEL_ICON') ? VGTS_SENTINEL_ICON : ''); ?>" 
             alt="<?php echo esc_attr__('Sentinel Icon', 'vgt-sentinel-ce'); ?>" 
             class="vgts-logo-glitch" 
             style="width: 24px; height: 24px; object-fit: contain; filter: drop-shadow(0 0 8px rgba(212, 175, 55, 0.4));">
        
        <div>
            <h2 style="margin:0; font-size:16px; color:#fff; font-weight:700; letter-spacing:0.5px;">
                <?php esc_html_e('VGT', 'vgt-sentinel-ce'); ?><span style="color:var(--vgts-accent);"><?php esc_html_e('SENTINEL', 'vgt-sentinel-ce'); ?></span>
            </h2>
            <small style="font-size:10px; color:var(--vgts-text-secondary); text-transform:uppercase; letter-spacing:1px; font-weight:600;">
                <?php esc_html_e('COMMUNITY EDITION', 'vgt-sentinel-ce'); ?>
            </small>
        </div>
    </div>

    <!-- MAIN NAVIGATION -->
    <nav class="vgts-nav">
        <?php 
        // Dependency Injection Check: Ensure $tabs is populated by controller
        if (!isset($tabs) || !is_array($tabs)) {
            $tabs = [];
        }
        
        foreach ($tabs as $slug => $data): 
            $is_active = (isset($active_tab) && $active_tab === $slug);
            
            // Heuristik: Oracle visuell abtrennen (Logical Tier Separation)
            if ($slug === 'oracle') {
                echo '<div style="height:1px; background:var(--vgts-border); margin:10px 15px; opacity:0.5;"></div>';
            }
        ?>
            <a href="?page=vgts-sentinel&tab=<?php echo esc_attr((string)$slug); ?>" 
               class="vgts-nav-item <?php echo $is_active ? 'active' : ''; ?>">
                <span class="dashicons <?php echo esc_attr($data['icon'] ?? 'dashicons-shield'); ?>"></span>
                <span class="vgts-nav-label"><?php echo esc_html($data['label'] ?? $slug); ?></span>
                
                <?php if ($is_active): ?>
                    <span class="vgts-active-indicator"></span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>
    
    <!-- SYSTEM STATUS FOOTER -->
    <div class="vgts-sidebar-footer">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; font-size: 11px; font-family: monospace;">
            <span style="color: #64748b; font-weight: 700; letter-spacing: 0.5px;"><?php esc_html_e('STATUS', 'vgt-sentinel-ce'); ?></span>
            <span style="color: #10b981; font-weight: 800; display: flex; align-items: center; gap: 6px;">
                <span style="display: block; width: 6px; height: 6px; background: #10b981; border-radius: 50%; box-shadow: 0 0 8px rgba(16, 185, 129, 0.6);"></span>
                <?php esc_html_e('ONLINE', 'vgt-sentinel-ce'); ?>
            </span>
        </div>
        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 11px; font-family: monospace;">
            <span style="color: #64748b; font-weight: 700; letter-spacing: 0.5px;"><?php esc_html_e('CORE', 'vgt-sentinel-ce'); ?></span>
            <span style="color: #94a3b8; font-weight: 700;"><?php echo defined('VGTS_VERSION') ? esc_html(VGTS_VERSION) : '1.5.0'; ?></span>
        </div>
        
        <div style="margin-top: 20px; padding: 10px; background: rgba(6, 182, 212, 0.05); border-radius: 4px; border: 1px solid rgba(6, 182, 212, 0.1);">
            <p style="margin: 0; font-size: 9px; color: #06b6d4; text-align: center; font-weight: 800; letter-spacing: 0.5px;">
                <?php esc_html_e('VGT OMEGA PROTOCOL', 'vgt-sentinel-ce'); ?>
            </p>
        </div>
    </div>
</aside>
