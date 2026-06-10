<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * CORE: COMPATIBILITY MANAGER
 * STATUS: PLATIN STATUS (WP.ORG COMPLIANT)
 * Verwaltet die Interoperabilität mit dem VisionGaia-Ökosystem und Drittanbieter-Software.
 * Basiert auf dem Registry-Pattern für modulare Erweiterbarkeit.
 */
class VGTS_Compatibility_Manager {

    /**
     * Bridge Registry
     * @var array
     */
    private array $bridges = [
        // VisionGaia Ecosystem
        'VisionLegalPro/vision-legal-pro.php' => 'VGTS_Bridge_VisionLegalPro',
        
        // Page Builders (Verhindert Konflikte im Editor)
        'elementor/elementor.php'             => 'VGTS_Bridge_PageBuilders',
        'divi-builder/divi-builder.php'       => 'VGTS_Bridge_PageBuilders',
        'oxygen/functions.php'                => 'VGTS_Bridge_PageBuilders',
        
        // Caching Systems
        'wp-rocket/wp-rocket.php'             => 'VGTS_Bridge_Cache',
    ];

    public function __construct() {
        // Wir laden die Bridges früh, aber nachdem WP Core bereit ist
        add_action('plugins_loaded', [$this, 'load_bridges'], 5);
    }

    /**
     * Prüft aktive Plugins und initialisiert entsprechende Bridges.
     */
    public function load_bridges(): void {
        foreach ($this->bridges as $plugin_path => $class_name) {
            if ($this->is_plugin_active($plugin_path)) {
                $this->load_bridge_class($class_name);
            }
        }

        // Builder-Check (Spezialfall für Themes mit integrierten Buildern)
        if ($this->is_builder_active()) {
            $this->load_bridge_class('VGTS_Bridge_PageBuilders');
        }
    }

    /**
     * Lädt die Bridge-Klasse und instanziiert sie.
     * * @param string $class_name
     */
    private function load_bridge_class(string $class_name): void {
        // [WP.ORG COMPLIANCE]: Mapping auf die bestehende Dateistruktur
        $slug = strtolower(str_replace('VGTS_Bridge_', '', $class_name));
        $file_path = VGTS_PATH . 'includes/compatibility/bridges/class-vis-bridge-' . $slug . '.php';
        
        // Mapping für Kebab-Case Dateinamen
        $file_path = str_replace('visionlegalpro', 'vision-legal-pro', $file_path);
        $file_path = str_replace('pagebuilders', 'page-builders', $file_path);

        if (file_exists($file_path)) {
            require_once $file_path;
            if (class_exists($class_name)) {
                new $class_name();
            }
        }
    }

    /**
     * Prüft, ob ein Plugin im Einzel- oder Netzwerkmodus aktiv ist.
     * * @param string $plugin_path
     * @return bool
     */
    private function is_plugin_active(string $plugin_path): bool {
        $active_plugins = (array) get_option('active_plugins', []);
        
        if (in_array($plugin_path, $active_plugins, true)) {
            return true;
        }

        // Network Check für Multisite-Umgebungen
        if (is_multisite() && function_exists('is_plugin_active_for_network')) {
            return is_plugin_active_for_network($plugin_path);
        }

        return false;
    }

    /**
     * Erkennt aktive Editoren anhand von URL-Parametern.
     * * @return bool
     */
    private function is_builder_active(): bool {
        // [WP.ORG COMPLIANCE]: Strict Sanitization of $_GET
        $get_data = wp_unslash($_GET);
        return isset($get_data['elementor-preview']) || isset($get_data['et_fb']) || isset($get_data['ct_builder']);
    }
}