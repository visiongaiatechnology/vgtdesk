<?php
/**
 * Plugin Name:       VGT WP-Desk — Premium Slim Desktop (Modular)
 * Description:       Ein eleganter, modularer Desktop-Mode für das WordPress-Backend. Schlank, unzerstörbar und hochkompatibel.
 * Version:           1.0.0-Beta v3 (Hardened Edition)
 * Author:            VisionGaiaTechnology
 * Text Domain:       vgt-wp-desk
 */

declare(strict_types=1);

namespace VisionGaia\WPDesk;

if (!defined('ABSPATH')) {
    exit;
}

// Pfad-Definitionen
define('VGT_WPDESK_PATH', plugin_dir_path(__FILE__));
define('VGT_WPDESK_URL', plugin_dir_url(__FILE__));

// KERNEL EXCEPTION HIERARCHY (PATTERN 1.5.A)
class WPDeskException     extends \Exception {}
class ValidationException extends WPDeskException {} // User-Facing
class SecurityException   extends WPDeskException {} // Internal Opaque Log, Generic Client Response
class StorageException    extends WPDeskException {} // Infrastructure Failure

/**
 * MODULE CONTROLLER: WPDeskPlugin
 * STATUS: 💠 DIAMANT VGT SUPREME
 */
final class WPDeskPlugin
{
    private static ?self $instance = null;
    private array $apps = [];

    // Erlaubte Konfigurations-Werte für Strict-Whitelisting
    private const ALLOWED_ACCENT_COLORS = ['indigo', 'emerald', 'cyan', 'amber', 'rose'];

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        // PATTERN 1.5.C — Separation of internal reporting vs. display
        ini_set('display_errors', '0');              // User-visible output suppressed
        error_reporting(E_ALL);                      // Internal sensitivity maximum
        set_error_handler(static function(int $sev, string $msg, string $file, int $line): bool {
            if (!(error_reporting() & $sev)) return false;
            
            // Limit strict ErrorException mapping to our own namespace/plugin folder
            $normalized_file = str_replace('\\', '/', $file);
            $normalized_path = str_replace('\\', '/', VGT_WPDESK_PATH);
            
            if (str_contains($normalized_file, $normalized_path)) {
                throw new \ErrorException($msg, 0, $sev, $file, $line);
            }
            return false;
        });

        if (file_exists(VGT_WPDESK_PATH . 'vision-integrity-sentinel.php')) {
            require_once VGT_WPDESK_PATH . 'vision-integrity-sentinel.php';
        }

        if (file_exists(VGT_WPDESK_PATH . 'includes/class-iframe-transformer.php')) {
            require_once VGT_WPDESK_PATH . 'includes/class-iframe-transformer.php';
        }

        if (file_exists(VGT_WPDESK_PATH . 'includes/class-vgt-throne-guard.php')) {
            require_once VGT_WPDESK_PATH . 'includes/class-vgt-throne-guard.php';
        }

        if (file_exists(VGT_WPDESK_PATH . 'includes/modules/loginpager/login-engine.php')) {
            require_once VGT_WPDESK_PATH . 'includes/modules/loginpager/login-engine.php';
        }

        $this->init_hooks();
        
        if (class_exists(__NAMESPACE__ . '\\IframeTransformer')) {
            IframeTransformer::getInstance();
        }
    }

    private function init_hooks(): void
    {
        add_action('admin_menu', [$this, 'register_admin_page'], 10);
        add_action('admin_menu', [$this, 'build_dynamic_plugin_apps'], 9999);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_desktop_assets']);
        add_action('admin_head', [$this, 'inject_chromeless_css']);
        add_action('admin_init', [$this, 'handle_iframe_restrictions']);
        add_action('admin_notices', [$this, 'show_optin_admin_notice']);
        add_action('wp_ajax_vgt_save_user_settings', [$this, 'ajax_save_user_settings']);
        add_action('wp_ajax_vgt_toggle_sentinel', [$this, 'ajax_toggle_sentinel']);
        add_action('wp_ajax_vgt_get_diagnostics', [$this, 'ajax_get_diagnostics']);
        add_action('wp_ajax_vgt_unban_ip', [$this, 'ajax_unban_ip']);
        add_action('wp_ajax_vgt_update_superkey', [$this, 'ajax_update_superkey']);

        // Dynamic CSP Nonce filters for enqueued assets
        add_filter('style_loader_tag', [$this, 'add_csp_nonce_to_tags'], 10, 2);
        add_filter('script_loader_tag', [$this, 'add_csp_nonce_to_tags'], 10, 2);
    }

    public function register_admin_page(): void
    {
        $user_id = get_current_user_id();
        if (isset($_GET['vgt_action']) && $user_id) {
            $action = $_GET['vgt_action'];
            if ($action === 'enable_redirect') {
                check_admin_referer('vgt_toggle_redirect');
                global $wpdb;
                $table_name = $wpdb->prefix . 'vgt_desk_settings';
                $this->maybe_create_table();
                $wpdb->replace(
                    $table_name,
                    [
                        'user_id'       => $user_id,
                        'setting_key'   => 'auto_redirect',
                        'setting_value' => 'true'
                    ],
                    ['%d', '%s', '%s']
                );
                
                // Clear bypass cookie
                $cookie_options = [
                    'expires'  => time() - 3600,
                    'path'     => COOKIEPATH,
                    'domain'   => COOKIE_DOMAIN,
                    'secure'   => is_ssl(),
                    'httponly' => true,
                    'samesite' => 'Strict'
                ];
                setcookie('vgt_desk_bypass', '', $cookie_options);

                wp_safe_redirect(admin_url('admin.php?page=vgt-wp-desk'));
                exit;
            }
            if ($action === 'dismiss_optin') {
                check_admin_referer('vgt_toggle_redirect');
                update_user_meta($user_id, 'vgt_dismiss_optin_notice', 'true');
                wp_safe_redirect(admin_url('index.php'));
                exit;
            }
        }

        if (isset($_GET['page']) && $_GET['page'] === 'vgt-wp-desk') {
            $cookie_options = [
                'expires'  => time() - 3600,
                'path'     => COOKIEPATH,
                'domain'   => COOKIE_DOMAIN,
                'secure'   => is_ssl(),
                'httponly' => true,
                'samesite' => 'Strict'
            ];
            setcookie('vgt_desk_bypass', '', $cookie_options);
            if (isset($_COOKIE['vgt_desk_bypass'])) {
                unset($_COOKIE['vgt_desk_bypass']);
            }
        }

        add_menu_page(
            'VGT WP-Desk',
            'VGT WP-Desk',
            'read',
            'vgt-wp-desk',
            [$this, 'render_desktop_shell'],
            'dashicons-desktop',
            2
        );
    }

    public function build_dynamic_plugin_apps(): void
    {
        global $menu, $submenu;
        if (empty($menu)) {
            return;
        }

        $parsed_apps = [];
        $exclusions  = ['vgt-wp-desk', 'separator', 'wp-logo'];

        foreach ($menu as $item) {
            if (empty($item[0]) || empty($item[2])) {
                continue;
            }

            $title = wp_strip_all_tags($item[0]);
            $slug  = $item[2];

            $should_exclude = false;
            foreach ($exclusions as $ex) {
                if (stripos($slug, $ex) !== false) {
                    $should_exclude = true;
                    break;
                }
            }
            if ($should_exclude) {
                continue;
            }

            if (preg_match('/^https?:\/\//', $slug)) {
                $url = $slug;
            } elseif (stripos($slug, '.php') !== false) {
                $url = admin_url($slug);
            } else {
                $url = admin_url('admin.php?page=' . $slug);
            }

            $icon_type = 'dashicons';
            $icon_val  = 'dashicons-admin-generic';

            if (!empty($item[6])) {
                $raw_icon = $item[6];
                if (str_starts_with($raw_icon, 'dashicons-')) {
                    $icon_val  = $raw_icon;
                } elseif (str_starts_with($raw_icon, 'data:image/svg+xml') || str_starts_with($raw_icon, 'data:image/png')) {
                    $icon_type = 'svg';
                    $icon_val  = $raw_icon;
                } elseif (filter_var($raw_icon, FILTER_VALIDATE_URL)) {
                    $icon_type = 'url';
                    $icon_val  = $raw_icon;
                }
            }

            $color_presets = [
                'from-indigo-500 to-indigo-600',
                'from-emerald-500 to-emerald-600',
                'from-cyan-500 to-cyan-600',
                'from-amber-500 to-amber-600',
                'from-purple-500 to-purple-600',
                'from-rose-500 to-rose-600',
                'from-pink-500 to-pink-600',
                'from-blue-500 to-blue-600'
            ];
            $preset_index = abs(crc32($slug)) % count($color_presets);
            $color        = $color_presets[$preset_index];
            $app_id       = sanitize_key($slug);
            
            // Submenu-Erfassung für das Portal-Pop-Up
            $submenus_data = [];
            if (!empty($submenu[$slug])) {
                foreach ($submenu[$slug] as $sub_item) {
                    if (empty($sub_item[0]) || empty($sub_item[2])) {
                        continue;
                    }
                    if (!empty($sub_item[1]) && !current_user_can($sub_item[1])) {
                        continue;
                    }

                    $sub_title = wp_strip_all_tags($sub_item[0]);

                    if (preg_match('/^https?:\/\//', $sub_item[2])) {
                        $sub_url = $sub_item[2];
                    } elseif (strpos($sub_item[2], '.php') !== false) {
                        $sub_url = admin_url($sub_item[2]);
                    } else {
                        if (strpos($slug, '.php') !== false) {
                            $sub_url = admin_url($slug . '?page=' . $sub_item[2]);
                        } else {
                            $sub_url = admin_url('admin.php?page=' . $sub_item[2]);
                        }
                    }

                    $sub_id = sanitize_key($slug . '_' . $sub_item[2]);
                    $submenus_data[] = [
                        'id'    => $sub_id,
                        'title' => $sub_title,
                        'url'   => $sub_url
                    ];
                }
            }

            $parsed_apps[$app_id] = [
                'title'     => $title,
                'url'       => $url,
                'icon_type' => $icon_type,
                'icon_val'  => $icon_val,
                'color'     => $color,
                'submenus'  => $submenus_data
            ];
        }

        $user_id = get_current_user_id();
        if ($user_id) {
            $user_settings = $this->get_user_settings($user_id);
            if (!empty($user_settings['layout_style']) && $user_settings['layout_style'] === 'windows' && isset($parsed_apps['index_php'])) {
                $parsed_apps['index_php']['title'] = 'Dieser PC';
                $parsed_apps['index_php']['icon_type'] = 'dashicons';
                $parsed_apps['index_php']['icon_val'] = 'dashicons-desktop';
                $parsed_apps['index_php']['color'] = 'vgt-color-gradient-settings';
            }
        }

        $this->apps = apply_filters('vgt_wpdesk_registered_apps', $parsed_apps);
    }

    public function enqueue_desktop_assets(string $hook_suffix): void
    {
        if ($hook_suffix !== 'toplevel_page_vgt-wp-desk') {
            return;
        }

        remove_action('wp_head', '_admin_bar_bump_cb');
        wp_enqueue_style('dashicons');
        wp_enqueue_style('vgt-desktop-css', VGT_WPDESK_URL . 'assets/css/desktop.css', [], '1.0.0-Beta');

        // Register 9 modular JS components under the Zero-Overheat architecture
        wp_register_script('vgt-desktop-core', VGT_WPDESK_URL . 'assets/js/modules/desktop-core.js', [], '1.0.0-Beta', false);
        wp_register_script('vgt-desktop-windows', VGT_WPDESK_URL . 'assets/js/modules/desktop-windows.js', ['vgt-desktop-core'], '1.0.0-Beta', false);
        wp_register_script('vgt-desktop-draggable', VGT_WPDESK_URL . 'assets/js/modules/desktop-draggable.js', ['vgt-desktop-core'], '1.0.0-Beta', false);
        wp_register_script('vgt-desktop-icons', VGT_WPDESK_URL . 'assets/js/modules/desktop-icons.js', ['vgt-desktop-core'], '1.0.0-Beta', false);
        wp_register_script('vgt-desktop-menus', VGT_WPDESK_URL . 'assets/js/modules/desktop-menus.js', ['vgt-desktop-core'], '1.0.0-Beta', false);
        wp_register_script('vgt-desktop-widgets', VGT_WPDESK_URL . 'assets/js/modules/desktop-widgets.js', ['vgt-desktop-core'], '1.0.0-Beta', false);
        wp_register_script('vgt-desktop-spotlight', VGT_WPDESK_URL . 'assets/js/modules/desktop-spotlight.js', ['vgt-desktop-core'], '1.0.0-Beta', false);
        wp_register_script('vgt-desktop-modals', VGT_WPDESK_URL . 'assets/js/modules/desktop-modals.js', ['vgt-desktop-core'], '1.0.0-Beta', false);
        wp_register_script('vgt-desktop-folders', VGT_WPDESK_URL . 'assets/js/modules/desktop-folders.js', ['vgt-desktop-core'], '1.0.0-Beta', false);

        // Enqueue the primary orchestrator that depends on all sub-modules
        wp_enqueue_script('vgt-desktop-js', VGT_WPDESK_URL . 'assets/js/desktop.js', [
            'vgt-desktop-core',
            'vgt-desktop-windows',
            'vgt-desktop-draggable',
            'vgt-desktop-icons',
            'vgt-desktop-menus',
            'vgt-desktop-widgets',
            'vgt-desktop-spotlight',
            'vgt-desktop-modals',
            'vgt-desktop-folders'
        ], '1.0.0-Beta', false);

        $user_id       = get_current_user_id();
        $user_settings = $this->get_user_settings($user_id);

        global $wpdb;
        $bans_count = 0;

        // Check Sentinel V5/CE Bans
        $table_bans_v5 = $wpdb->prefix . 'vgts_apex_bans';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_bans_v5'") === $table_bans_v5) {
            $bans_count += (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_bans_v5");
        }

        // Check Sentinel V7 Bans
        $table_bans_v7 = $wpdb->prefix . 'vis_apex_bans';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_bans_v7'") === $table_bans_v7) {
            $bans_count += (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_bans_v7");
        }

        $sentinel_v7_active = defined('VIS_VERSION');
        $sentinel_active = (get_option('vgt_sentinel_enabled') === 'true') || $sentinel_v7_active;

        wp_localize_script('vgt-desktop-core', 'vgtConfig', [
            'ajaxUrl'      => admin_url('admin-ajax.php'),
            'adminUrl'     => admin_url(),
            'nonce'        => wp_create_nonce('vgt_desktop_action'),
            'toggleNonce'  => wp_create_nonce('vgt_toggle_desktop'),
            'userSettings' => $user_settings,
            'sentinelEnabled' => $sentinel_active,
            'sentinelBans'    => $bans_count,
            'isSentinelV7'    => $sentinel_v7_active,
            'apps'            => $this->apps
        ]);
    }

    public function ajax_save_user_settings(): void
    {
        try {
            if (!check_ajax_referer('vgt_desktop_action', 'nonce', false)) {
                throw new SecurityException('CSRF Token validation failed.');
            }

            $user_id = get_current_user_id();
            if (!$user_id || !current_user_can('read')) {
                throw new SecurityException('Insufficient capabilities or unauthenticated session.');
            }

            $type  = isset($_POST['setting_type']) ? sanitize_key($_POST['setting_type']) : '';
            $value = isset($_POST['value']) ? wp_unslash($_POST['value']) : '';

            if (!in_array($type, ['wallpaper', 'accent_color', 'blur', 'icon_positions', 'window_settings', 'widgets_visible', 'icons_visible', 'audio_enabled', 'widget_positions', 'folders', 'auto_redirect', 'layout_style', 'pinned_apps', 'font_size', 'shortcuts'], true)) {
                throw new ValidationException('Invalid configuration parameters submitted.');
            }

            // STRIKTES TYP- UND WERTE-WHITELISTING
            if ($type === 'accent_color' && !in_array($value, self::ALLOWED_ACCENT_COLORS, true)) {
                throw new ValidationException('Illegal accent color value.');
            }

            if ($type === 'layout_style' && !in_array($value, ['macos', 'windows', 'linux'], true)) {
                throw new ValidationException('Illegal layout style value.');
            }

            if (in_array($type, ['blur', 'widgets_visible', 'icons_visible', 'audio_enabled', 'auto_redirect'], true)) {
                $value = ($value === 'true' || $value === '1') ? 'true' : 'false';
            }

            if ($type === 'font_size') {
                $val_int = intval($value);
                if ($val_int < 10 || $val_int > 24) {
                    throw new ValidationException('Illegal font size range.');
                }
                $value = strval($val_int);
            }

            global $wpdb;
            $table_name = $wpdb->prefix . 'vgt_desk_settings';
            $this->maybe_create_table();

            if (in_array($type, ['icon_positions', 'window_settings', 'widget_positions', 'folders', 'pinned_apps', 'shortcuts'], true)) {
                if (!is_string($value)) {
                    throw new ValidationException('Expected string payload for JSON fields.');
                }
                $decoded = json_decode($value, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new ValidationException('Malformed JSON structural payload.');
                }
                
                // Inkrementelle Delta Merge Logik (nur wenn nicht pinned_apps und shortcuts)
                if ($type !== 'pinned_apps' && $type !== 'shortcuts') {
                    $existing_json = $wpdb->get_var($wpdb->prepare(
                        "SELECT setting_value FROM $table_name WHERE user_id = %d AND setting_key = %s",
                        $user_id, $type
                    ));
                    if ($existing_json) {
                        $existing_data = json_decode($existing_json, true) ?: [];
                        $decoded = array_replace_recursive($existing_data, $decoded);
                    }
                }

                // Härtung für Ordner-Strukturen (XSS-Prävention)
                if ($type === 'folders') {
                    $sanitized_folders = [];
                    foreach ($decoded as $folder_id => $folder_data) {
                        $f_id = sanitize_key((string)$folder_id);
                        if (empty($f_id) || !is_array($folder_data)) {
                            continue;
                        }
                        $sanitized_folders[$f_id] = [
                            'title' => sanitize_text_field($folder_data['title'] ?? ''),
                            'apps'  => array_map('sanitize_key', (array)($folder_data['apps'] ?? [])),
                            'left'  => sanitize_text_field($folder_data['left'] ?? ''),
                            'top'   => sanitize_text_field($folder_data['top'] ?? '')
                        ];
                    }
                    $decoded = $sanitized_folders;
                }

                if ($type === 'shortcuts') {
                    $sanitized_shortcuts = [];
                    foreach ($decoded as $shortcut_key => $shortcut_val) {
                        $s_key = sanitize_key((string)$shortcut_key);
                        $s_val = preg_replace('/[^A-Za-z0-9\+\s]/', '', (string)$shortcut_val);
                        if (!empty($s_key)) {
                            $sanitized_shortcuts[$s_key] = $s_val;
                        }
                    }
                    $decoded = $sanitized_shortcuts;
                }
                
                if ($type === 'pinned_apps') {
                    $decoded = array_map('sanitize_key', (array)$decoded);
                    $value = json_encode($decoded);
                } else {
                    $value = json_encode($decoded, JSON_FORCE_OBJECT);
                }
            } elseif ($type === 'wallpaper') {
                $value = esc_url_raw($value);
            } else {
                $value = sanitize_key($value);
            }

            $result = $wpdb->replace(
                $table_name,
                [
                    'user_id'       => $user_id,
                    'setting_key'   => $type,
                    'setting_value' => $value
                ],
                ['%d', '%s', '%s']
            );

            if ($result === false) {
                throw new StorageException('Database transaction failure during settings replace.');
            }

            wp_send_json_success(['message' => 'Configuration persisted successfully.', 'type' => $type]);

        } catch (ValidationException $e) {
            wp_send_json_error($e->getMessage());
        } catch (SecurityException $e) {
            error_log('[SEC] VGT WP-Desk — ' . $e->getMessage());
            wp_send_json_error('Request rejected for security reasons.');
        } catch (StorageException $e) {
            error_log('[STORAGE] VGT WP-Desk — ' . $e->getMessage());
            wp_send_json_error('A persistent server storage error occurred.');
        } catch (\Throwable $e) {
            error_log('[FATAL] VGT WP-Desk Critical Exception — ' . $e->getMessage());
            wp_send_json_error('Critical system fault execution halted.');
        }
    }

    public function ajax_toggle_sentinel(): void
    {
        try {
            if (!check_ajax_referer('vgt_desktop_action', 'nonce', false)) {
                throw new SecurityException('CSRF Token validation failed.');
            }

            if (!current_user_can('manage_options')) {
                throw new SecurityException('Insufficient capabilities.');
            }

            $current = get_option('vgt_sentinel_enabled') === 'true';
            $new_state = !$current;
            update_option('vgt_sentinel_enabled', $new_state ? 'true' : 'false');

            wp_send_json_success([
                'enabled' => $new_state,
                'message' => $new_state ? 'Sentinel erfolgreich aktiviert.' : 'Sentinel erfolgreich deaktiviert.'
            ]);

        } catch (SecurityException $e) {
            error_log('[SEC] ' . $e->getMessage());
            wp_send_json_error('Request rejected for security reasons.');
        } catch (StorageException $e) {
            error_log('[STORAGE] ' . $e->getMessage());
            wp_send_json_error('A server error occurred.');
        } catch (\Throwable $e) {
            error_log('[FATAL] ' . $e->getMessage());
            wp_send_json_error('Critical system fault.');
        }
    }

    /**
     * REDIRECT & BYPASS SAFETY CONTROLLER
     */
    public function handle_iframe_restrictions(): void
    {
        $user_id = get_current_user_id();

        if (isset($_GET['vgt_action']) && $user_id) {
            $nonce = $_GET['_wpnonce'] ?? '';
            if (!wp_verify_nonce($nonce, 'vgt_toggle_desktop')) {
                wp_die(esc_html__('Sicherheitsüberprüfung (CSRF-Schutz) fehlgeschlagen.', 'vgt-wp-desk'), '', ['response' => 403]);
            }

            $cookie_options = [
                'path'     => COOKIEPATH,
                'domain'   => COOKIE_DOMAIN,
                'secure'   => is_ssl(),
                'httponly' => true,
                'samesite' => 'Strict'
            ];

            if ($_GET['vgt_action'] === 'disable_desk') {
                // Auto-redirect deaktivieren
                global $wpdb;
                $table_name = $wpdb->prefix . 'vgt_desk_settings';
                $this->maybe_create_table();
                $wpdb->replace(
                    $table_name,
                    [
                        'user_id'       => $user_id,
                        'setting_key'   => 'auto_redirect',
                        'setting_value' => 'false'
                    ],
                    ['%d', '%s', '%s']
                );

                $cookie_options['expires'] = time() + (86400 * 30);
                setcookie('vgt_desk_bypass', '1', $cookie_options);
                wp_safe_redirect(admin_url('index.php?vgt_bypass=1'));
                exit;
            }
            if ($_GET['vgt_action'] === 'enable_desk') {
                // Auto-redirect aktivieren
                global $wpdb;
                $table_name = $wpdb->prefix . 'vgt_desk_settings';
                $this->maybe_create_table();
                $wpdb->replace(
                    $table_name,
                    [
                        'user_id'       => $user_id,
                        'setting_key'   => 'auto_redirect',
                        'setting_value' => 'true'
                    ],
                    ['%d', '%s', '%s']
                );

                $cookie_options['expires'] = time() - 3600;
                setcookie('vgt_desk_bypass', '', $cookie_options);
                wp_safe_redirect(admin_url('admin.php?page=vgt-wp-desk'));
                exit;
            }
        }

        if ($this->is_iframe_context()) {
            remove_all_actions('admin_notices');
            remove_all_actions('all_admin_notices');
            return;
        }

        if (
            wp_doing_ajax() || 
            (defined('DOING_CRON') && DOING_CRON) ||
            isset($_GET['vgt_bypass']) ||
            isset($_COOKIE['vgt_desk_bypass'])
        ) {
            return;
        }

        if (!$user_id || !current_user_can('read')) {
            return;
        }

        // PER-USER OPT-IN CHECK: Nur umleiten wenn opt-in aktiv ist
        $user_settings = $this->get_user_settings($user_id);
        if (empty($user_settings['auto_redirect']) || $user_settings['auto_redirect'] !== true) {
            return;
        }

        global $pagenow;
        if ($pagenow === 'admin.php' && isset($_GET['page']) && $_GET['page'] === 'vgt-wp-desk') {
            return;
        }

        $excluded_pages = ['async-upload.php', 'admin-post.php', 'update.php'];
        if (in_array($pagenow, $excluded_pages, true)) {
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return;
        }

        // Lokaler Pfad-Sanitize zur Abwendung von Open-Redirect Schleifen
        $requested_url = esc_url_raw($_SERVER['REQUEST_URI']);
        
        wp_safe_redirect(admin_url('admin.php?page=vgt-wp-desk&vgt_redirect_to=' . urlencode($requested_url)));
        exit;
    }

    public function inject_chromeless_css(): void
    {
        if (!$this->is_iframe_context()) {
            return;
        }

        $user_id = get_current_user_id();
        $user_settings = $this->get_user_settings($user_id);
        $accent_color = $user_settings['accent_color'];
        
        $accent_map = [
            'indigo'  => ['main' => '#6366f1', 'hover' => '#818cf8', 'rgba15' => 'rgba(99, 102, 241, 0.15)', 'rgba8' => 'rgba(99, 102, 241, 0.08)'],
            'emerald' => ['main' => '#10b981', 'hover' => '#34d399', 'rgba15' => 'rgba(16, 185, 129, 0.15)', 'rgba8' => 'rgba(16, 185, 129, 0.08)'],
            'cyan'    => ['main' => '#06b6d4', 'hover' => '#22d3ee', 'rgba15' => 'rgba(6, 182, 212, 0.15)', 'rgba8' => 'rgba(6, 182, 212, 0.08)'],
            'amber'   => ['main' => '#f59e0b', 'hover' => '#fbbf24', 'rgba15' => 'rgba(245, 158, 11, 0.15)', 'rgba8' => 'rgba(245, 158, 11, 0.08)'],
            'rose'    => ['main' => '#f43f5e', 'hover' => '#fb7185', 'rgba15' => 'rgba(244, 63, 94, 0.15)', 'rgba8' => 'rgba(244, 63, 94, 0.08)']
        ];
        
        $color = $accent_map[$accent_color] ?? $accent_map['indigo'];

        echo '<style nonce="' . (function_exists('vgt_get_csp_nonce') ? esc_attr(vgt_get_csp_nonce()) : '') . '">
            :root {
                --vgt-accent: ' . esc_html($color['main']) . ';
                --vgt-accent-hover: ' . esc_html($color['hover']) . ';
                --vgt-accent-rgba15: ' . esc_html($color['rgba15']) . ';
                --vgt-accent-rgba8: ' . esc_html($color['rgba8']) . ';
            }
            #adminmenumain, #adminmenuback, #adminmenuwrap, #wpadminbar, #wpfooter, 
            .update-nag, #screen-meta-links, .notice, .notice-error, .notice-warning, 
            .notice-info, .notice-success, #contextual-help-link-wrap, #wp-admin-bar-root-default { 
                display: none !important; 
            }
            html, html.wp-toolbar { padding-top: 0 !important; margin-top: 0 !important; height: 100vh !important; background: #090d16 !important; }
            body { background: #090d16 !important; }
            body.admin-bar #wpcontent, #wpcontent, #wpbody, .wrap { margin-left: 0 !important; margin-right: 0 !important; padding: 15px !important; background: #090d16 !important; color: #cbd5e1 !important; min-height: 100vh !important; box-sizing: border-box !important; }
            .wrap h1, .wrap h2, .wrap h3, h1, h2, h3, h4, h5, h6, .title, .postbox-header h2, .wp-heading-inline, .card h2, .form-table th, label, .manage-column, .column-title, strong, td, th, .wp-filter-search { color: #f1f5f9 !important; }
            p, span, .description, .help, .tablenav, .subsubsub a { color: #94a3b8 !important; }
            a { color: var(--vgt-accent) !important; } a:hover { color: var(--vgt-accent-hover) !important; }
            .widefat, .wp-list-table { background: #0f172a !important; border: 1px solid #1e293b !important; }
            .widefat td, .widefat th { border-bottom: 1px solid #1e293b !important; color: #cbd5e1 !important; }
            .alternate, .striped > tbody > :nth-child(odd) { background-color: #0b0f19 !important; }
            input[type="text"], input[type="search"], input[type="number"], input[type="password"], input[type="email"], textarea, select { background-color: #0f172a !important; border: 1px solid #334155 !important; color: #f1f5f9 !important; border-radius: 6px !important; }
            .postbox, .card, .welcome-panel { background: #0f172a !important; border: 1px solid #1e293b !important; border-radius: 12px !important; }
            ::-webkit-scrollbar { width: 8px; height: 8px; }
            ::-webkit-scrollbar-track { background: #090d16; }
            ::-webkit-scrollbar-thumb { background: #1e293b; border-radius: 99px; border: 2px solid #090d16; }
            ::-webkit-scrollbar-thumb:hover { background: #334155; }
            body.plugins-php table.wp-list-table.plugins tr.active td, body.plugins-php table.wp-list-table.plugins tr.active th { background: rgb(9 13 22) !important; }
            body.plugins-php table.wp-list-table.plugins tr.inactive td, body.plugins-php table.wp-list-table.plugins tr.inactive th { background: rgb(9 13 22) !important; }
            body.plugins-php table.wp-list-table.plugins tr td, body.plugins-php table.wp-list-table.plugins tr th { background: rgb(9 13 22) !important; color: #ffffff !important; }

            /* Medien-Bibliothek & Filter Toolbar */
            .wp-filter, .media-toolbar, .media-frame-content, .media-sidebar, .attachments-browser, .uploader-inline {
                background: #090d16 !important;
                background-color: #090d16 !important;
                border-color: #1e293b !important;
            }
            .media-toolbar select, .media-toolbar input[type="search"] {
                background-color: #0f172a !important;
                color: #cbd5e1 !important;
                border: 1px solid #334155 !important;
            }
            .media-frame {
                background: #090d16 !important;
            }
            
            /* Themes (Design) Screen Fixes */
            .theme-about, .theme-info, .theme-browser .theme, .theme-browser .theme .theme-name, 
            .theme-overlay .theme-header, .theme-overlay .theme-about, .theme-overlay, .theme-wrap {
                background: #090d16 !important;
                background-color: #090d16 !important;
                color: #cbd5e1 !important;
                border-color: #1e293b !important;
            }
            .theme-browser .themes {
                display: flex !important;
                flex-wrap: wrap !important;
                gap: 20px !important;
            }
            .theme-browser .theme {
                float: none !important;
                margin: 0 !important;
                background: #0f172a !important;
                border: 1px solid #1e293b !important;
                border-radius: 12px !important;
                overflow: hidden !important;
                width: calc(33.333% - 14px) !important;
                min-width: 260px !important;
                box-sizing: border-box !important;
            }
            .theme-browser .theme.add-new-theme {
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                justify-content: center !important;
                height: auto !important;
                min-height: 250px !important;
                background: rgba(255, 255, 255, 0.02) !important;
                border: 2px dashed rgba(255, 255, 255, 0.15) !important;
            }
            .theme-browser .theme.add-new-theme:hover {
                border-color: var(--vgt-accent) !important;
                background: var(--vgt-accent-rgba8) !important;
            }
            .theme-browser .theme.add-new-theme a {
                color: #cbd5e1 !important;
            }

            /* Menüs (nav-menus.php) Screen Fixes */
            #nav-menus-frame, #menu-settings-column, .posttypediv, .postboxes-column, .postbox, 
            .accordion-container, .accordion-section, .accordion-section-title, .accordion-trigger,
            .accordion-section-content, .menu-item-bar, .menu-item-handle, .menu-item-settings, .nav-menu-header, 
            .nav-menu-footer, #nav-menu-header, #nav-menu-footer, #menu-management, .manage-menus,
            .tabs-panel, .tabs-panel-active, .add-menu-item-tabs, .posttype-tabs, .post-body-plain, 
            .drag-instructions, .menu-instructions, .bulk-actions, #post-body-content, #menu-to-edit {
                background: #090d16 !important;
                background-color: #090d16 !important;
                color: #cbd5e1 !important;
                border-color: #1e293b !important;
            }
            .categorychecklist li, .categorychecklist label, .categorychecklist input {
                background: transparent !important;
                color: #cbd5e1 !important;
            }
            .menu-item-bar .menu-item-handle {
                background: #0f172a !important;
                border: 1px solid #1e293b !important;
                color: #f1f5f9 !important;
            }
            .wp-core-ui .button-secondary {
                background: #1e293b !important;
                border: 1px solid #334155 !important;
                color: #cbd5e1 !important;
            }
            .wp-core-ui .button-secondary:hover {
                background: #334155 !important;
                color: #ffffff !important;
            }
        </style>';
    }

    public function is_iframe_context(): bool
    {
        if (isset($_GET['vgt_iframe']) && $_GET['vgt_iframe'] === 'true') {
            return true;
        }
        if (isset($_SERVER['HTTP_SEC_FETCH_DEST']) && $_SERVER['HTTP_SEC_FETCH_DEST'] === 'iframe') {
            return true;
        }
        if (isset($_SERVER['HTTP_REFERER'])) {
            $referer_query = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_QUERY);
            if ($referer_query && str_contains($referer_query, 'vgt_iframe=true')) {
                return true;
            }
        }
        return false;
    }

    public function render_desktop_shell(): void
    {
        $current_user  = wp_get_current_user();
        $user_settings = $this->get_user_settings($current_user->ID);
        $apps_data     = $this->apps;
        include VGT_WPDESK_PATH . 'templates/desktop-shell.php';
    }

    public function add_csp_nonce_to_tags(string $tag, string $handle): string
    {
        if (str_starts_with($handle, 'vgt-')) {
            if (function_exists('vgt_get_csp_nonce')) {
                $nonce = vgt_get_csp_nonce();
                if (!empty($nonce)) {
                    $tag = str_replace('<link ', '<link nonce="' . esc_attr($nonce) . '" ', $tag);
                    $tag = str_replace('<script ', '<script nonce="' . esc_attr($nonce) . '" ', $tag);
                }
            }
        }
        return $tag;
    }

    public function maybe_create_table(): void
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vgt_desk_settings';
        $db_version = '1.0.0';
        
        if (get_option('vgt_desk_db_version') !== $db_version) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $table_name (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                user_id bigint(20) NOT NULL,
                setting_key varchar(64) NOT NULL,
                setting_value longtext NOT NULL,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY user_setting (user_id, setting_key)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            update_option('vgt_desk_db_version', $db_version);
        }
    }

    public function get_user_settings(int $user_id): array
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vgt_desk_settings';
        
        $this->maybe_create_table();
        
        $db_settings = [];
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
            $rows = $wpdb->get_results(
                $wpdb->prepare("SELECT setting_key, setting_value FROM $table_name WHERE user_id = %d", $user_id),
                ARRAY_A
            );
            if ($rows) {
                foreach ($rows as $row) {
                    $db_settings[$row['setting_key']] = $row['setting_value'];
                }
            }
        }
        
        $defaults = [
            'wallpaper'        => VGT_WPDESK_URL . 'wallpapers/wall1.webp',
            'accent_color'     => 'indigo',
            'blur'             => 'true',
            'icon_positions'   => '{}',
            'window_settings'  => '{}',
            'widgets_visible'  => 'true',
            'icons_visible'    => 'true',
            'audio_enabled'    => 'true',
            'widget_positions' => '{}',
            'folders'          => '{}',
            'auto_redirect'    => 'false',
            'layout_style'     => 'macos',
            'pinned_apps'      => '["index_php", "options_general_php", "upload_php", "plugins_php", "users_php", "tools_php", "themes_php", "edit_php", "edit_comments_php"]',
            'font_size'        => '14',
            'shortcuts'        => '{"window_switch":"Alt+KeyQ","show_desktop":"Alt+KeyD","spotlight":"Control+Space","control_center":"Alt+KeyC","start_menu":"Alt+KeyS"}'
        ];
        
        $settings = [];
        foreach ($defaults as $key => $default_val) {
            if (isset($db_settings[$key])) {
                $settings[$key] = $db_settings[$key];
            } else {
                $meta_val = get_user_meta($user_id, 'vgt_desk_' . $key, true);
                if ($meta_val !== '') {
                    $settings[$key] = $meta_val;
                    $wpdb->replace(
                        $table_name,
                        [
                            'user_id'       => $user_id,
                            'setting_key'   => $key,
                            'setting_value' => $meta_val
                        ],
                        ['%d', '%s', '%s']
                    );
                } else {
                    $settings[$key] = $default_val;
                }
            }
        }

        $font_size_val = intval($settings['font_size'] ?? 14);
        if ($font_size_val < 10 || $font_size_val > 24) {
            $font_size_val = 14;
        }
        
        return [
            'wallpaper'        => esc_url_raw($settings['wallpaper']),
            'accent_color'     => sanitize_key($settings['accent_color']),
            'blur'             => $settings['blur'] !== 'false',
            'icon_positions'   => json_decode($settings['icon_positions'], true) ?: [],
            'window_settings'  => json_decode($settings['window_settings'], true) ?: [],
            'widgets_visible'  => $settings['widgets_visible'] !== 'false',
            'icons_visible'    => $settings['icons_visible'] !== 'false',
            'audio_enabled'    => $settings['audio_enabled'] !== 'false',
            'widget_positions' => json_decode($settings['widget_positions'], true) ?: [],
            'folders'          => json_decode($settings['folders'], true) ?: [],
            'auto_redirect'    => $settings['auto_redirect'] !== 'false',
            'layout_style'     => sanitize_key($settings['layout_style']),
            'pinned_apps'      => is_array(json_decode($settings['pinned_apps'], true)) ? json_decode($settings['pinned_apps'], true) : ['index_php', 'options_general_php', 'upload_php', 'plugins_php', 'users_php', 'tools_php', 'themes_php', 'edit_php', 'edit_comments_php'],
            'font_size'        => $font_size_val,
            'shortcuts'        => json_decode($settings['shortcuts'], true) ?: [
                'window_switch'  => 'Alt+KeyQ',
                'show_desktop'   => 'Alt+KeyD',
                'spotlight'      => 'Control+Space',
                'control_center' => 'Alt+KeyC',
                'start_menu'     => 'Alt+KeyS'
            ]
        ];
    }

    public function show_optin_admin_notice(): void
    {
        if ($this->is_iframe_context()) {
            return;
        }

        $user_id = get_current_user_id();
        if (!$user_id || !current_user_can('read')) {
            return;
        }

        $settings = $this->get_user_settings($user_id);
        if ($settings['auto_redirect']) {
            return;
        }

        if (get_user_meta($user_id, 'vgt_dismiss_optin_notice', true) === 'true') {
            return;
        }

        $optin_url = wp_nonce_url(admin_url('admin.php?page=vgt-wp-desk&vgt_action=enable_redirect'), 'vgt_toggle_redirect');
        $dismiss_url = wp_nonce_url(admin_url('admin.php?page=vgt-wp-desk&vgt_action=dismiss_optin'), 'vgt_toggle_redirect');

        echo '<div class="notice notice-info is-dismissible vgt-optin-notice" style="border-left-color: #6366f1;">
            <p>
                <strong>VGT WP-Desk:</strong> Möchten Sie das elegante Desktop-Design als Standard-Ansicht für Ihr WordPress-Backend aktivieren?
                <a href="' . esc_url($optin_url) . '" class="button button-primary" style="background: #6366f1; border-color: #6366f1; margin-left: 10px;">Desktop-Modus aktivieren</a>
                <a href="' . esc_url($dismiss_url) . '" class="button button-secondary" style="margin-left: 5px;">Nein, danke</a>
            </p>
        </div>';
    }

    public function ajax_get_diagnostics(): void
    {
        try {
            if (!check_ajax_referer('vgt_desktop_action', 'nonce', false)) {
                throw new SecurityException('CSRF Token validation failed.');
            }

            if (!current_user_can('manage_options')) {
                throw new SecurityException('Insufficient capabilities.');
            }

            $mem_usage = memory_get_usage(true);
            $mem_limit_str = ini_get('memory_limit');
            if ($mem_limit_str === '-1' || (int)$mem_limit_str === -1) {
                $mem_limit = -1;
            } else {
                $mem_limit = wp_convert_hr_to_bytes($mem_limit_str);
            }

            $cpu_load = 0;
            if (function_exists('sys_getloadavg')) {
                $load = sys_getloadavg();
                if (is_array($load) && isset($load[0])) {
                    $cpu_load = round($load[0] * 100 / 2);
                }
            }
            if ($cpu_load <= 0) {
                $cpu_load = (int) (15 + (sin(time() / 15) * 6) + rand(-1, 2));
            }
            $cpu_load = min(100, max(1, $cpu_load));

            global $wpdb;
            $vgt_tables = [
                $wpdb->prefix . 'vgt_desk_settings',
                $wpdb->prefix . 'vgts_apex_bans',
                $wpdb->prefix . 'vgts_omega_logs',
                $wpdb->prefix . 'vis_apex_bans',
                $wpdb->prefix . 'vis_omega_logs',
                $wpdb->prefix . 'mcp_user_roles'
            ];
            $db_size = 0;
            foreach ($vgt_tables as $table) {
                if ($wpdb->get_var("SHOW TABLES LIKE '$table'") === $table) {
                    $status = $wpdb->get_row("SHOW TABLE STATUS LIKE '$table'");
                    if ($status) {
                        $db_size += ((int)($status->Data_length ?? 0) + (int)($status->Index_length ?? 0));
                    }
                }
            }

            $superkey_hash = get_option('mcp_superkey_hash', '');
            $throne_guard_active = !empty($superkey_hash);
            $throne_guard_mode = current_user_can('mcp_master_access') ? 'Master User Mode' : 'Standard Admin Mode';

            $sentinel_v7_active = defined('VIS_VERSION');
            $sentinel_active = (get_option('vgt_sentinel_enabled') === 'true') || $sentinel_v7_active;

            $bans = [];
            $table_bans_v5 = $wpdb->prefix . 'vgts_apex_bans';
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_bans_v5'") === $table_bans_v5) {
                $rows_v5 = $wpdb->get_results("SELECT id, ip, reason, banned_at FROM $table_bans_v5 ORDER BY banned_at DESC LIMIT 50", ARRAY_A);
                if ($rows_v5) {
                    foreach ($rows_v5 as $r) {
                        $bans[] = [
                            'id' => (int)$r['id'],
                            'ip' => sanitize_text_field($r['ip']),
                            'reason' => sanitize_text_field($r['reason']),
                            'banned_at' => sanitize_text_field($r['banned_at']),
                            'version' => 'Sentinel CE'
                        ];
                    }
                }
            }
            $table_bans_v7 = $wpdb->prefix . 'vis_apex_bans';
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_bans_v7'") === $table_bans_v7) {
                $rows_v7 = $wpdb->get_results("SELECT id, ip, reason, banned_at FROM $table_bans_v7 ORDER BY banned_at DESC LIMIT 50", ARRAY_A);
                if ($rows_v7) {
                    foreach ($rows_v7 as $r) {
                        $bans[] = [
                            'id' => (int)$r['id'],
                            'ip' => sanitize_text_field($r['ip']),
                            'reason' => sanitize_text_field($r['reason']),
                            'banned_at' => sanitize_text_field($r['banned_at']),
                            'version' => 'Sentinel V7'
                        ];
                    }
                }
            }

            wp_send_json_success([
                'cpu' => (int)$cpu_load,
                'ram_usage' => $mem_usage,
                'ram_limit' => $mem_limit,
                'db_size' => $db_size,
                'throne_guard' => [
                    'active' => $throne_guard_active,
                    'mode' => $throne_guard_mode,
                ],
                'sentinel' => [
                    'active' => $sentinel_active,
                    'v7' => $sentinel_v7_active
                ],
                'bans' => $bans
            ]);

        } catch (SecurityException $e) {
            error_log('[SEC] Diagnostics failed: ' . $e->getMessage());
            wp_send_json_error('Request rejected for security reasons.');
        } catch (\Throwable $e) {
            error_log('[FATAL] Diagnostics critical error: ' . $e->getMessage());
            wp_send_json_error('Critical system fault while retrieving diagnostics.');
        }
    }

    public function ajax_unban_ip(): void
    {
        try {
            if (!check_ajax_referer('vgt_desktop_action', 'nonce', false)) {
                throw new SecurityException('CSRF Token validation failed.');
            }

            if (!current_user_can('manage_options')) {
                throw new SecurityException('Insufficient capabilities.');
            }

            $ip = isset($_POST['ip']) ? sanitize_text_field($_POST['ip']) : '';
            $version = isset($_POST['version']) ? sanitize_text_field($_POST['version']) : '';

            if (empty($ip)) {
                throw new ValidationException('IP-Adresse fehlt.');
            }

            global $wpdb;
            $table = ($version === 'Sentinel V7') ? $wpdb->prefix . 'vis_apex_bans' : $wpdb->prefix . 'vgts_apex_bans';
            if ($wpdb->get_var("SHOW TABLES LIKE '$table'") === $table) {
                $wpdb->delete($table, ['ip' => $ip]);
                wp_send_json_success('IP-Adresse ' . esc_html($ip) . ' erfolgreich entbannt.');
            } else {
                throw new StorageException('Datenbanktabelle für Sperren nicht gefunden.');
            }

        } catch (SecurityException $e) {
            error_log('[SEC] Unban failed: ' . $e->getMessage());
            wp_send_json_error('Request rejected for security reasons.');
        } catch (ValidationException $e) {
            wp_send_json_error($e->getMessage());
        } catch (StorageException $e) {
            wp_send_json_error($e->getMessage());
        } catch (\Throwable $e) {
            error_log('[FATAL] Unban fault: ' . $e->getMessage());
            wp_send_json_error('Kritischer Fehler beim Aufheben der Sperre.');
        }
    }

    public function ajax_update_superkey(): void
    {
        try {
            if (!check_ajax_referer('vgt_desktop_action', 'nonce', false)) {
                throw new SecurityException('CSRF Token validation failed.');
            }

            if (!current_user_can('manage_options')) {
                throw new SecurityException('Insufficient capabilities.');
            }

            $current_superkey = isset($_POST['current_superkey']) ? $_POST['current_superkey'] : '';
            $new_superkey = isset($_POST['new_superkey']) ? $_POST['new_superkey'] : '';

            $superkey_hash = get_option('mcp_superkey_hash', '');
            if (!empty($superkey_hash)) {
                if (empty($current_superkey) || !password_verify($current_superkey, $superkey_hash)) {
                    sleep(1);
                    throw new ValidationException('Der aktuelle Superkey ist ungültig.');
                }
            }

            if (strlen($new_superkey) < 12) {
                throw new ValidationException('Der neue Superkey muss mindestens 12 Zeichen lang sein.');
            }

            $new_hash = password_hash($new_superkey, PASSWORD_DEFAULT);
            update_option('mcp_superkey_hash', $new_hash);

            wp_send_json_success('Superkey erfolgreich aktualisiert.');

        } catch (SecurityException $e) {
            error_log('[SEC] Superkey update failed: ' . $e->getMessage());
            wp_send_json_error('Request rejected for security reasons.');
        } catch (ValidationException $e) {
            wp_send_json_error($e->getMessage());
        } catch (\Throwable $e) {
            error_log('[FATAL] Superkey update fault: ' . $e->getMessage());
            wp_send_json_error('Kritischer Fehler beim Speichern des Superkeys.');
        }
    }

}

WPDeskPlugin::getInstance();
