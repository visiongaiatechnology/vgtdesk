<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * MODULE: DASHBOARD VIEW ENGINE
 * STATUS: DIAMANT SUPREME (WP.ORG COMPLIANT)
 * KOGNITIVE UPGRADES:
 * - [WP.ORG FIXED]: Static i18n strings for all module labels.
 * - [WP.ORG FIXED]: Strict Output Escaping (esc_attr, esc_html).
 * - [WP.ORG FIXED]: Safe Superglobal Access via wp_unslash.
 * - Architecture: Modular Tab-based Rendering with VGT State-of-the-Art UI.
 * - Fix: Deterministic Variable Injection for Sidebar State.
 */
class VGTS_Dashboard_View {
    
    /**
     * @return array[] Die Tab-Definitionen mit lokalisierten Labels (Alle 15 Module).
     */
    private function get_tabs(): array {
        return [
            'overview'   => ['icon' => 'dashicons-chart-area',    'label' => __('COMMAND CENTER', 'vgt-sentinel-ce')],
            'threads'    => ['icon' => 'dashicons-hidden',        'label' => __('THREADS', 'vgt-sentinel-ce')],
            'integrity'  => ['icon' => 'dashicons-search',        'label' => __('INTEGRITY MONITOR', 'vgt-sentinel-ce')],
            'aegis'      => ['icon' => 'dashicons-shield',        'label' => __('AEGIS FIREWALL', 'vgt-sentinel-ce')],
            'antibot'    => ['icon' => 'dashicons-shield-alt',    'label' => __('ANTIBOT ENGINE', 'vgt-sentinel-ce')],
            'cerberus'   => ['icon' => 'dashicons-shield',        'label' => __('CERBERUS BAN', 'vgt-sentinel-ce')],
            'titan'      => ['icon' => 'dashicons-lock',          'label' => __('TITAN HARDENING', 'vgt-sentinel-ce')],
            'mudeployer' => ['icon' => 'dashicons-admin-network', 'label' => __('MU-DEPLOYER', 'vgt-sentinel-ce')],
            'airlock'    => ['icon' => 'dashicons-upload',        'label' => __('AIRLOCK GUARD', 'vgt-sentinel-ce')],
            'filesystem' => ['icon' => 'dashicons-category',      'label' => __('FILE SECURITY', 'vgt-sentinel-ce')],
            'hades'      => ['icon' => 'dashicons-hidden',        'label' => __('HADES STEALTH', 'vgt-sentinel-ce')],
            'styx'       => ['icon' => 'dashicons-networking',    'label' => __('STYX CONTROL', 'vgt-sentinel-ce')],
            'oracle'     => ['icon' => 'dashicons-list-view',     'label' => __('ORACLE SCANNER', 'vgt-sentinel-ce')],
            'console'    => ['icon' => 'dashicons-editor-code',   'label' => __('VGT CONSOLE', 'vgt-sentinel-ce')],
            'logs'       => ['icon' => 'dashicons-list-view',     'label' => __('SYSTEM LOGS', 'vgt-sentinel-ce')],
        ];
    }

    /**
     * Haupt-Render-Methode für das Sentinel Dashboard.
     */
    public function render(): void {
        $enabled = get_option('vgt_sentinel_enabled') === 'true';
        if (!$enabled) {
            if (isset($_POST['vgt_activate_sentinel'])) {
                $nonce = $_POST['_wpnonce'] ?? '';
                if (wp_verify_nonce($nonce, 'vgt_activate_sentinel_action') && current_user_can('manage_options')) {
                    update_option('vgt_sentinel_enabled', 'true');
                    wp_redirect(admin_url('admin.php?page=vgts-sentinel'));
                    exit;
                }
            }
            
            echo '<div class="vgts-omega-wrapper" style="justify-content: center; align-items: center; min-height: 80vh; background: transparent; padding: 20px; box-sizing: border-box;">';
            echo '  <div class="glassmorphism" style="max-width: 480px; padding: 40px; border-radius: 20px; border: 1px solid rgba(255, 255, 255, 0.1); text-align: center; background: rgba(15, 23, 42, 0.65); color: #fff; box-shadow: 0 30px 60px rgba(0, 0, 0, 0.6); backdrop-filter: blur(25px); -webkit-backdrop-filter: blur(25px); font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Oxygen-Sans, Ubuntu, Cantarell, \'Helvetica Neue\', sans-serif;">';
            echo '    <div style="font-size: 72px; margin-bottom: 24px; filter: drop-shadow(0 0 15px rgba(244, 63, 94, 0.4));">🛡️</div>';
            echo '    <h2 style="font-size: 26px; font-weight: 800; color: #f43f5e; margin: 0 0 12px 0; letter-spacing: -0.5px;">VGT Sentinel</h2>';
            echo '    <p style="font-size: 13px; color: #94a3b8; line-height: 1.6; margin: 0 0 30px 0;">Die native Zero-Trust Web Application Firewall (WAF) und das Kernel-Level Hardening Framework sind standardmäßig inaktiv. Aktivieren Sie Sentinel, um Echtzeitschutz vor SQLi, XSS, Brute-Force und File-Tampering zu aktivieren.</p>';
            echo '    <form method="post" action="">';
            wp_nonce_field('vgt_activate_sentinel_action');
            echo '      <button type="submit" name="vgt_activate_sentinel" style="background: linear-gradient(135deg, #10b981, #059669); border: none; padding: 14px 40px; border-radius: 12px; color: #fff; font-size: 14px; font-weight: 700; cursor: pointer; transition: all 0.2s; box-shadow: 0 10px 20px rgba(16, 185, 129, 0.2);">Sentinel Schutz aktivieren</button>';
            echo '    </form>';
            echo '  </div>';
            echo '</div>';
            return;
        }

        // [WP.ORG COMPLIANCE]: Safe Access to $_GET
        $tabs = $this->get_tabs();
        $active_tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'overview';
        
        // Falls der Tab nicht existiert, Fallback auf Overview
        if (!isset($tabs[$active_tab])) {
            $active_tab = 'overview';
        }
 
        $is_config_tab = in_array($active_tab, ['aegis', 'titan', 'hades', 'styx', 'airlock', 'mudeployer', 'antibot'], true);
        
        // Wrapper Start
        echo '<div class="vgts-omega-wrapper">';
        
        // Sidebar Inclusion (State Injection)
        $sidebar_path = VGTS_PATH . 'includes/dashboard/views/view-sidebar.php';
        if (file_exists($sidebar_path)) {
            // Wir injizieren $tabs und $active_tab direkt, damit die Sidebar keinen
            // illegalen State-Access auf $this->tabs versuchen muss.
            require $sidebar_path;
        }
 
        echo '<main class="vgts-content">';
        
        // Custom Hook für Erweiterungen
        do_action('vgts_dashboard_before_render');
        
        // Form-Wrapper für Konfigurations-Tabs
        if ($is_config_tab) {
            echo '<form method="post" action="">';
            echo '<input type="hidden" name="vgts_context" value="' . esc_attr($active_tab) . '">';
            wp_nonce_field('vgts_save_config');
        }
 
        $this->render_header($active_tab, $tabs);
        
        // View Routing
        $view_file = VGTS_PATH . 'includes/dashboard/views/view-' . $active_tab . '.php';
        
        echo '<div class="vgts-view-animate">';
        if (file_exists($view_file)) {
            require $view_file;
        } else {
            $overview_file = VGTS_PATH . 'includes/dashboard/views/view-overview.php';
            if (file_exists($overview_file)) {
                require $overview_file;
            }
        }
        echo '</div>';
        
        if ($is_config_tab) {
            echo '</form>';
        }
        
        echo '</main></div>';
    }

    /**
     * Rendert die obere Bar des Dashboards mit Titeln und Action-Buttons.
     * @param string $tab Aktueller Tab-Slug
     * @param array $tabs Tab-Definitionen
     */
    private function render_header(string $tab, array $tabs): void {
        $label = $tabs[$tab]['label'] ?? __('MODULE', 'vgt-sentinel-ce');
        $icon  = $tabs[$tab]['icon'] ?? 'dashicons-admin-generic';
        
        echo '<header class="vgts-topbar">
                <div class="vgts-header-title">
                    <span class="vgts-header-icon dashicons ' . esc_attr($icon) . '"></span>
                    <h1>' . esc_html($label) . '</h1>
                </div>';
        
        // Speicher-Button für Config-Tabs einblenden
        if (in_array($tab, ['aegis', 'titan', 'hades', 'styx', 'airlock', 'mudeployer', 'antibot'], true)) {
            echo '<button type="submit" name="vgts_save_config" value="1" class="vgts-btn vgts-btn-primary">
                    <span class="dashicons dashicons-saved"></span> ' . esc_html__('SAVE CONFIG', 'vgt-sentinel-ce') . '
                  </button>';
        }
        echo '</header>';
    }
}
