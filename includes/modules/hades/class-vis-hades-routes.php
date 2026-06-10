<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

/**
 * MODULE: HADES ROUTES (The Maze)
 * Status: DIAMANT VGT SUPREME (WP.ORG COMPLIANT)
 * Logic: Hides wp-login.php and wp-admin via URL Rewrite & Filtering.
 * Fix: Safe template-include checking to prevent PHP 8+ "Path cannot be empty" ValueErrors.
 */
class VGTS_Hades_Routes {

    private string $login_slug;
    private string $admin_slug;
    private bool $enabled;

    public function __construct(array $options) {
        $this->login_slug = !empty($options['hades_login_slug']) ? sanitize_title($options['hades_login_slug']) : 'wp-login.php';
        $this->admin_slug = !empty($options['hades_admin_slug']) ? sanitize_title($options['hades_admin_slug']) : 'wp-admin';
        $this->enabled    = !empty($options['hades_enabled']);

        if ($this->enabled) {
            // 1. URL Filters (Output)
            add_filter('site_url', [$this, 'rewrite_login_url'], 10, 4);
            add_filter('network_site_url', [$this, 'rewrite_login_url'], 10, 3);
            add_filter('wp_redirect', [$this, 'filter_redirect'], 10, 2);
            
            // 2. Admin URL Rewrite (Optional & Dangerous)
            if ($this->admin_slug !== 'wp-admin') {
                add_filter('admin_url', [$this, 'rewrite_admin_url'], 10, 3);
                add_filter('url_to_postid', [$this, 'fix_url_to_postid']); // Fix für Permalinks
            }

            // 3. Block Access to Old Paths (Active Defense)
            add_action('init', [$this, 'guard_paths']);
        }
    }

    /**
     * Ändert wp-login.php Links global in den neuen Slug.
     * VGT FIX: Nullable Types (?string) um WP Core Null-Injections abzufangen.
     *
     * @param mixed $url
     * @param string|null $path
     * @param string|null $scheme
     * @param int|null $blog_id
     * @return mixed
     */
    public function rewrite_login_url($url, ?string $path = null, ?string $scheme = null, ?int $blog_id = null) {
        if (!is_string($url) || $this->login_slug === 'wp-login.php') return $url;

        if (strpos($url, 'wp-login.php') !== false) {
            // Verhindere Doppel-Ersetzungen und Loop-Fehler
            $query = parse_url($url, PHP_URL_QUERY);
            $base  = str_replace('wp-login.php', $this->login_slug, $url);
            
            // Query String wieder anhängen falls verloren
            if ($query && strpos($base, (string)$query) === false) {
                $base .= '?' . $query;
            }
            return $base;
        }
        return $url;
    }

    /**
     * Ändert wp-admin Links global.
     * VGT FIX: Nullable Types (?string) integriert.
     *
     * @param mixed $url
     * @param string|null $path
     * @param int|null $blog_id
     * @return mixed
     */
    public function rewrite_admin_url($url, ?string $path = null, ?int $blog_id = null) {
        if (!is_string($url) || $this->admin_slug === 'wp-admin') return $url;

        return str_replace('wp-admin/', $this->admin_slug . '/', $url);
    }

    /**
     * Verhindert Redirect-Schleifen (Loop Check).
     * Wenn WP versucht, zurück auf wp-login.php zu leiten, zwingen wir den neuen Slug.
     *
     * @param mixed $location
     * @param int $status
     * @return mixed
     */
    public function filter_redirect($location, int $status = 302) {
        if (!is_string($location)) return $location;

        if ($this->login_slug !== 'wp-login.php' && strpos($location, 'wp-login.php') !== false) {
            return str_replace('wp-login.php', $this->login_slug, $location);
        }
        if ($this->admin_slug !== 'wp-admin' && strpos($location, 'wp-admin/') !== false) {
            return str_replace('wp-admin/', $this->admin_slug . '/', $location);
        }
        return $location;
    }

    /**
     * BLOCKT Zugriff auf die alten Pfade (Security).
     */
    public function guard_paths(): void {
        if (is_admin() || defined('DOING_AJAX')) return;

        // [WP.ORG COMPLIANCE]: Strict Sanitization of Superglobals
        $request = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';
        $action  = isset($_GET['action']) ? sanitize_text_field(wp_unslash($_GET['action'])) : '';

        // Login Block
        if ($this->login_slug !== 'wp-login.php' && strpos($request, 'wp-login.php') !== false) {
            // Ausnahme: Logout Action
            if ($action !== 'logout') {
                $this->deny_access();
            }
        }

        // Admin Block (Nur wenn nicht eingeloggt oder strikt)
        if ($this->admin_slug !== 'wp-admin' && strpos($request, '/wp-admin/') !== false) {
            // AJAX Ausnahmen zulassen
            if (strpos($request, 'admin-ajax.php') === false) {
                $this->deny_access();
            }
        }
    }

    /**
     * Verweigert den Zugriff und liefert eine 404-Seite aus.
     * VGT FIX: Robustes Fallback eingebaut, um ValueError (Empty Path) unter PHP 8 zu verhindern.
     */
    private function deny_access(): void {
        global $wp_query;
        if (isset($wp_query)) {
            $wp_query->set_404();
        }
        status_header(404);
        nocache_headers();

        $template = get_query_template('404');

        // Prüfen, ob das Template existiert und geladen werden kann
        if (is_string($template) && $template !== '' && file_exists($template)) {
            include($template);
        } else {
            // Fallback 1: Versuche die index.php des Themes zu laden
            $index_template = get_index_template();
            if (is_string($index_template) && $index_template !== '' && file_exists($index_template)) {
                include($index_template);
            } else {
                // Fallback 2: Wenn gar nichts verfügbar ist, nutze eine wp_die Fehlermeldung
                wp_die(
                    __('Page not found.', 'sentinelcom-main'),
                    __('Page not found', 'sentinelcom-main'),
                    ['response' => 404]
                );
            }
        }
        exit;
    }

    /**
     * @param mixed $url
     * @return mixed
     */
    public function fix_url_to_postid($url) {
        if (!is_string($url) || $this->admin_slug === 'wp-admin') return $url;
        return str_replace($this->admin_slug, 'wp-admin', $url);
    }

    /**
     * Generiert die .htaccess Regeln für VGTS_Hades
     */
    public function get_apache_rules(): string {
        $rules = "";

        // LOGIN REWRITE
        if ($this->login_slug !== 'wp-login.php') {
            $slug = $this->login_slug;
            $rules .= "RewriteRule ^{$slug}/?$ wp-login.php [QSA,L]\n";
        }

        // ADMIN REWRITE
        if ($this->admin_slug !== 'wp-admin') {
            $slug = $this->admin_slug;
            $rules .= "RewriteRule ^{$slug}/(.*) wp-admin/$1 [QSA,L]\n";
            $rules .= "RewriteRule ^{$slug}$ wp-admin/index.php [QSA,L]\n";
        }

        return $rules;
    }

    public function get_nginx_rules(): string {
        $rules = "";
        
        if ($this->login_slug !== 'wp-login.php') {
            $rules .= "rewrite ^/{$this->login_slug}/?$ /wp-login.php last;\n";
        }
        
        if ($this->admin_slug !== 'wp-admin') {
            $rules .= "rewrite ^/{$this->admin_slug}/(.*) /wp-admin/$1 last;\n";
            $rules .= "rewrite ^/{$this->admin_slug}$ /wp-admin/index.php last;\n";
        }

        return $rules;
    }
}
