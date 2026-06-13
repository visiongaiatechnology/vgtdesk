<?php
declare(strict_types=1);

namespace VisionGaia\WPDesk;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * SERVICE: WPDeskSettings
 * STATUS: 💠 DIAMANT VGT SUPREME
 * Verwalter für Benutzer-Einstellungen und Datenbank-Schemata.
 */
final class WPDeskSettings
{
    /**
     * Erstellt die SQL-Einstellungen-Tabelle falls nicht vorhanden.
     */
    public static function maybe_create_table(): void
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vgt_desk_settings';
        $db_version = '1.0.0';
        
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        if (get_option('vgt_desk_db_version') !== $db_version || !$table_exists) {
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

        // Bereinige Duplikate falls vorhanden
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
            $duplicates = $wpdb->get_results("
                SELECT user_id, setting_key, COUNT(*) as cnt 
                FROM $table_name 
                GROUP BY user_id, setting_key 
                HAVING cnt > 1
            ");
            if (!empty($duplicates)) {
                foreach ($duplicates as $dup) {
                    $max_id = $wpdb->get_var($wpdb->prepare(
                        "SELECT MAX(id) FROM $table_name WHERE user_id = %d AND setting_key = %s",
                        $dup->user_id, $dup->setting_key
                    ));
                    if ($max_id) {
                        $wpdb->query($wpdb->prepare(
                            "DELETE FROM $table_name WHERE user_id = %d AND setting_key = %s AND id != %d",
                            $dup->user_id, $dup->setting_key, $max_id
                        ));
                    }
                }
            }
            
            // Einzigartigen Schlüssel nachträglich hinzufügen falls er fehlt
            $index_exists = $wpdb->get_results("SHOW INDEX FROM $table_name WHERE Key_name = 'user_setting'");
            if (empty($index_exists)) {
                $wpdb->query("ALTER TABLE $table_name ADD UNIQUE KEY user_setting (user_id, setting_key)");
            }
        }
    }

    /**
     * Ruft die benutzerdefinierten Einstellungen für einen User ab.
     */
    public static function get_user_settings(int $user_id): array
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vgt_desk_settings';
        
        self::maybe_create_table();
        
        $db_settings = [];
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
            $rows = $wpdb->get_results(
                $wpdb->prepare("SELECT setting_key, setting_value FROM $table_name WHERE user_id = %d ORDER BY id ASC", $user_id),
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
            'sound_pack'       => 'synth_default',
            'widget_positions' => '{}',
            'folders'          => '{}',
            'auto_redirect'    => 'false',
            'layout_style'     => 'macos',
            'pinned_apps'      => '["index_php", "options_general_php", "upload_php", "plugins_php", "users_php", "tools_php", "themes_php", "edit_php", "edit_comments_php"]',
            'font_size'        => '14',
            'shortcuts'        => '{"window_switch":"Alt+KeyQ","show_desktop":"Alt+KeyD","spotlight":"Control+Space","control_center":"Alt+KeyC","start_menu":"Alt+KeyS"}',
            'first_run_completed' => 'false',
            'show_welcome_on_startup' => 'true'
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
        
        file_put_contents(
            VGT_WPDESK_PATH . 'vgt_debug.log',
            sprintf("[%s] GET: folders=%s, db_settings_keys=%s\n", date('Y-m-d H:i:s'), var_export($settings['folders'] ?? null, true), implode(',', array_keys($db_settings))),
            FILE_APPEND
        );

        return [
            'wallpaper'        => esc_url_raw($settings['wallpaper']),
            'accent_color'     => sanitize_key($settings['accent_color']),
            'blur'             => $settings['blur'] !== 'false',
            'icon_positions'   => json_decode($settings['icon_positions'], true) ?: [],
            'window_settings'  => json_decode($settings['window_settings'], true) ?: [],
            'widgets_visible'  => $settings['widgets_visible'] !== 'false',
            'icons_visible'    => $settings['icons_visible'] !== 'false',
            'audio_enabled'    => $settings['audio_enabled'] !== 'false',
            'sound_pack'       => sanitize_key($settings['sound_pack']),
            'widget_positions' => json_decode($settings['widget_positions'], true) ?: [],
            'folders'          => json_decode($settings['folders'], true) ?: [],
            'auto_redirect'    => $settings['auto_redirect'] !== 'false',
            'layout_style'     => sanitize_key($settings['layout_style']),
            'pinned_apps'      => is_array(json_decode($settings['pinned_apps'], true)) ? json_decode($settings['pinned_apps'], true) : ['index_php', 'options_general_php', 'upload_php', 'plugins_php', 'users_php', 'tools_php', 'themes_php', 'edit_php', 'edit_comments_php'],
            'font_size'        => $font_size_val,
            'first_run_completed' => $settings['first_run_completed'] !== 'false',
            'show_welcome_on_startup' => $settings['show_welcome_on_startup'] !== 'false',
            'shortcuts'        => json_decode($settings['shortcuts'], true) ?: [
                'window_switch'  => 'Alt+KeyQ',
                'show_desktop'   => 'Alt+KeyD',
                'spotlight'      => 'Control+Space',
                'control_center' => 'Alt+KeyC',
                'start_menu'     => 'Alt+KeyS'
            ]
        ];
    }

    /**
     * Zeigt den Opt-In Banner für den Desktop-Modus im WordPress-Backend.
     */
    public static function show_optin_admin_notice(): void
    {
        $is_iframe = false;
        if (isset($_GET['vgt_iframe']) && $_GET['vgt_iframe'] === 'true') {
            $is_iframe = true;
        } elseif (isset($_SERVER['HTTP_SEC_FETCH_DEST']) && $_SERVER['HTTP_SEC_FETCH_DEST'] === 'iframe') {
            $is_iframe = true;
        } elseif (isset($_SERVER['HTTP_REFERER'])) {
            $referer_query = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_QUERY);
            if ($referer_query && str_contains($referer_query, 'vgt_iframe=true')) {
                $is_iframe = true;
            }
        }

        if ($is_iframe) {
            return;
        }

        $user_id = get_current_user_id();
        if (!$user_id || !current_user_can('read')) {
            return;
        }

        $settings = self::get_user_settings($user_id);
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
}
