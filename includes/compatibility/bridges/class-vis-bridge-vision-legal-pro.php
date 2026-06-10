<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * BRIDGE: VISION LEGAL PRO
 * STATUS: PLATIN STATUS (WP.ORG COMPLIANT)
 * Stellt sicher, dass Privacy-Banner und Shadow-Net-Assets
 * die von Hades maskierten Pfade nutzen.
 * Behebt den WP.org Review-Fehler "Unclosed ob_start()".
 */
class VGTS_Bridge_VisionLegalPro {

    public function __construct() {
        // [WP.ORG COMPLIANCE]: Sync with rekalibrated prefix
        $opt = get_option('vgts_config', []);
        
        if (!empty($opt['hades_enabled'])) {
            add_action('template_redirect', [$this, 'start_buffer_patch'], 999);
        }
    }

    /**
     * Startet den Output Buffer für die Pfad-Maskierung.
     */
    public function start_buffer_patch(): void {
        // Ausschluss-Kriterien für Admin, AJAX und REST
        if (is_admin() || wp_doing_ajax() || defined('REST_REQUEST')) {
            return;
        }

        // [COMPATIBILITY]: Respektiert den globalen Skip-Filter (z.B. von PageBuildern)
        if (apply_filters('vgts_hades_skip_buffer', false)) {
            return;
        }

        // [WP.ORG FIXED]: ob_start wird nun mit einem Shutdown-Flush gepaart
        ob_start([$this, 'rewrite_vlp_paths']);
        add_action('shutdown', [$this, 'end_buffer_patch'], 0);
    }

    /**
     * Schließt den Output Buffer explizit am Ende des Requests.
     */
    public function end_buffer_patch(): void {
        if (ob_get_level() > 0) {
            ob_end_flush();
        }
    }

    /**
     * Callback für die Buffer-Manipulation.
     * Ersetzt die physischen Upload-Pfade von VLP durch Hades-Aliase.
     * * @param string $buffer Der gesamte HTML-Output
     * @return string Manipulierter Output
     */
    public function rewrite_vlp_paths(string $buffer): string {
        if (empty($buffer)) {
            return $buffer;
        }

        // Hole die echten Pfade (VLP nutzt Standard WP Uploads)
        $upload_dir = wp_upload_dir();
        $base_url   = (string) $upload_dir['baseurl']; // z.B. .../wp-content/uploads
        
        // Definiere Hades Mapping (Standard-Alias aus dem Hades-Modul)
        $hades_upload_alias = 'storage'; 
        
        // Suche: .../wp-content/uploads/vgt-shadow-net
        $search = $base_url . '/vgt-shadow-net';
        
        // Erreplace mit maskiertem Pfad: .../storage/vgt-shadow-net
        $replace = str_replace('wp-content/uploads', $hades_upload_alias, $search);

        // Führe Replacement durch (O(n) String Replacement)
        return str_replace($search, $replace, $buffer);
    }
}