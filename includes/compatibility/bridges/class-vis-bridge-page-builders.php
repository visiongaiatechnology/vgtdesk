<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * BRIDGE: PAGE BUILDERS (Elementor, Divi, Oxygen, etc.)
 * STATUS: PLATIN STATUS (WP.ORG COMPLIANT)
 * Deaktiviert aggressive Sicherheitsfeatures während des Editierens,
 * um Konflikte im Frontend-Editor zu vermeiden.
 */
class VGTS_Bridge_PageBuilders {

    public function __construct() {
        // Wenn wir im Editor-Modus sind, entschärfen wir das System
        if ($this->is_editing_mode()) {
            $this->disable_interventions();
        }
    }

    /**
     * Erkennt, ob die aktuelle Anfrage von einem Frontend-Editor stammt.
     * * @return bool
     */
    private function is_editing_mode(): bool {
        // [WP.ORG COMPLIANCE]: Safe Superglobal Access
        $get_data = wp_unslash($_GET);

        // Elementor Preview Check
        if (isset($get_data['elementor-preview'])) {
            return true;
        }
        
        // Divi Visual Builder Check
        if (isset($get_data['et_fb'])) {
            return true;
        }
        
        // Oxygen Builder Check
        if (defined('SHOW_CT_BUILDER') && SHOW_CT_BUILDER) {
            return true;
        }
        
        // WordPress Customizer Preview
        if (is_customize_preview()) {
            return true;
        }

        return false;
    }

    /**
     * Reduziert die Sicherheitsstrenge für eine reibungslose UX im Editor.
     */
    private function disable_interventions(): void {
        // Deaktiviere Hades Output Buffering (Präfix-Synchronisiert)
        add_filter('vgts_hades_skip_buffer', '__return_true');

        // Deaktiviere Aegis Payload Interception (Präfix-Synchronisiert)
        add_filter('vgts_aegis_skip_injection', '__return_true');

        // Headers lockern (X-Frame-Options verhindern oft das Laden des Editors in iFrames)
        if (!headers_sent()) {
            if (function_exists('header_remove')) {
                header_remove('X-Frame-Options');
            }
            header('X-Frame-Options: SAMEORIGIN', true);
        }
    }
}