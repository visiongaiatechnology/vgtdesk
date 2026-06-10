<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

/**
 * MODULE: HADES (The Unseen) - OMEGA V4.0 (FULL SPECTRUM)
 * Status: ACTIVE / PLATINUM HARDENED (WP.ORG COMPLIANT STRUCTURE)
 * Logic: Kombiniert Asset-Maskierung (Ghost Protocol) mit Routing-Maskierung (Login/Admin).
 * Fix: Type-Guard in URL-Filtern zur Prävention von PHP 8+ TypeError Fatalities.
 * VGT DIAMANT FIX: PCRE Fail-Safe Mechanismus verhindert .htaccess Wipeouts bei Limit Exhaustion.
 * * [WP.ORG GUIDELINE 10 NOTICE]: 
 * Dieses Modul greift tief in Core-Pfade ein (upload_dir, plugins_url). 
 * Es ist strikt OPT-IN und per Default deaktiviert. Der Anwender muss in der GUI 
 * explizit zustimmen, die Systempfade zu maskieren.
 */
class VGTS_Hades {

    private bool $enabled;
    private string $marker = 'VGTS Hades';
    private $routes; // Router Instance
    
    // SAFE MAPPING (Assets)
    private array $map = [
        'content/ui'  => 'wp-content/themes',
        'content/lib' => 'wp-content/plugins',
        'storage'     => 'wp-content/uploads',
        'content'     => 'wp-content',
        'core'        => 'wp-includes',
    ];

    public function __construct(array $options) {
        $this->enabled = !empty($options['hades_enabled']);
        
        // INIT ROUTER (Lädt Login/Admin Logic)
        if (!class_exists('VGTS_Hades_Routes')) {
            require_once VGTS_PATH . 'includes/modules/hades/class-vis-hades-routes.php';
        }
        $this->routes = new VGTS_Hades_Routes($options);

        // AUTO-SYNC: .htaccess aktualisieren beim Speichern
        // [WP.ORG COMPLIANCE]: Sanitization of $_GET
        if (is_admin() && isset($_GET['settings-updated'])) {
            $updated = sanitize_text_field(wp_unslash($_GET['settings-updated']));
            if ($updated === 'true') {
                $this->update_server_rules();
            }
        }

        if ($this->enabled && !is_admin()) {
            $this->init_url_filters();
        }
    }

    /**
     * URL REPLACEMENT ENGINE (Assets Only)
     */
    private function init_url_filters(): void {
        add_filter('plugins_url', function($url) {
            if (!is_string($url)) return $url;
            return str_replace('wp-content/plugins', 'content/lib', $url);
        }, 10, 1);

        add_filter('theme_file_uri', function($url) {
            if (!is_string($url)) return $url;
            return str_replace('wp-content/themes', 'content/ui', $url);
        }, 10, 1);

        add_filter('upload_dir', function($uploads) {
            if (isset($uploads['baseurl']) && is_string($uploads['baseurl'])) {
                $uploads['baseurl'] = str_replace('wp-content/uploads', 'storage', $uploads['baseurl']);
            }
            foreach(['url', 'subdir'] as $k) {
                if(isset($uploads[$k]) && is_string($uploads[$k])) {
                    $uploads[$k] = str_replace('wp-content/uploads', 'storage', $uploads[$k]);
                }
            }
            return $uploads;
        });

        $replacer = [$this, 'replace_base_urls'];
        add_filter('style_loader_src', $replacer, 999);
        add_filter('script_loader_src', $replacer, 999);
        add_filter('includes_url', [$this, 'replace_base_urls'], 999);
    }

    /**
     * Zentraler Asset Replacer (Type-Safe)
     * Verhindert Fatal Errors, wenn WP Core 'false' anstatt eines URL-Strings durch den Filter schiebt.
     *
     * @param mixed $src 
     * @return mixed
     */
    public function replace_base_urls($src) {
        // VGT TYPE-GUARD: Abbruch wenn $src kein String ist
        if (!is_string($src) || empty($src)) {
            return $src;
        }

        foreach ($this->map as $fake => $real) {
            if (strpos($src, $real) !== false) {
                return str_replace($real, $fake, $src);
            }
        }
        return $src;
    }

    /**
     * SERVER RULES ENGINE (.htaccess Writer)
     * Kombiniert Asset-Regeln und Route-Regeln.
     */
    public function update_server_rules(): void {
        if (!$this->is_apache()) return; 

        $htaccess_path = ABSPATH . '.htaccess';
        if (!file_exists($htaccess_path) || !is_writable($htaccess_path)) return;

        $rules = $this->generate_apache_rules();
        
        // Füge Routing Regeln hinzu (Login/Admin)
        if ($this->routes) {
            $rules .= "\n# Hades Routes\n";
            $rules .= $this->routes->get_apache_rules();
        }

        $content = file_get_contents($htaccess_path);
        if ($content === false) $content = '';
        
        $start = "# BEGIN " . $this->marker;
        $end   = "# END " . $this->marker;
        
        $pattern = "/".preg_quote($start, '/').".*?".preg_quote($end, '/')."/s";
        $clean_content = preg_replace($pattern, '', $content);
        
        // VGT SUPREME FIX: PCRE Fail-Safe
        // Wenn preg_replace aufgrund von PCRE-Limits (Backtracking) fehlschlägt,
        // gibt es null zurück. Wir fallen auf den originalen Content zurück, 
        // um einen Wipeout der Datei zu verhindern.
        if ($clean_content === null) {
            $clean_content = $content;
        }
        
        if ($this->enabled) {
            $new_content = $start . "\n" . $rules . "\n" . $end . "\n" . trim((string)$clean_content);
        } else {
            $new_content = trim((string)$clean_content);
        }

        file_put_contents($htaccess_path, $new_content);
    }

    private function generate_apache_rules(): string {
        $rules = "<IfModule mod_rewrite.c>\n";
        $rules .= "RewriteEngine On\n";
        
        foreach ($this->map as $fake => $real) {
            $rules .= "RewriteRule ^{$fake}/(.*) {$real}/$1 [L,QSA]\n";
        }
        
        $rules .= "</IfModule>\n";
        return $rules;
    }

    private function is_apache(): bool {
        // [WP.ORG COMPLIANCE]: Strict Sanitization of Server Variables
        $software = isset($_SERVER['SERVER_SOFTWARE']) ? sanitize_text_field(wp_unslash($_SERVER['SERVER_SOFTWARE'])) : '';
        return (strpos($software, 'Apache') !== false || strpos($software, 'LiteSpeed') !== false);
    }
    
    // Helper für die View um Nginx Rules zu holen
    public function get_nginx_routing_rules(): string {
        return $this->routes ? $this->routes->get_nginx_rules() : '';
    }
}