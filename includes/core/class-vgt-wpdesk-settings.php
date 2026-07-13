<?php
declare(strict_types=1);

namespace VisionGaia\WPDesk;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * SERVICE: WPDeskSettings
 * STATUS: DIAMANT VGT SUPREME
 * Single-channel settings store for the desk runtime.
 */
final class WPDeskSettings
{
    public const TABLE_SUFFIX = 'vgt_desk_settings';

    /** Keys that are stored as JSON and full-replaced (no recursive merge zombies). */
    public const FULL_REPLACE_JSON_KEYS = [
        'pinned_apps',
        'shortcuts',
        'folders',
        'icon_positions',
        'classic_apps',
        // Full replace — delta-merge caused zombie coords / failed position saves.
        'widget_positions',
    ];

    /** Keys that may delta-merge existing JSON objects. */
    public const MERGE_JSON_KEYS = [
        'window_settings',
    ];

    public const ALLOWED_SETTING_TYPES = [
        'wallpaper',
        'accent_color',
        'blur',
        'icon_positions',
        'window_settings',
        'widgets_visible',
        'icons_visible',
        'audio_enabled',
        'widget_positions',
        'folders',
        'auto_redirect',
        'layout_style',
        'pinned_apps',
        'font_size',
        'shortcuts',
        'active_preset',
        'first_run_completed',
        'show_welcome_on_startup',
        'sound_pack',
        'classic_apps',
    ];

    public const ALLOWED_ACCENT_COLORS = [
        'indigo', 'emerald', 'cyan', 'amber', 'rose', 'gold', 'purple', 'violet', 'neon',
    ];

    public static function table_name(): string
    {
        global $wpdb;
        return $wpdb->prefix . self::TABLE_SUFFIX;
    }

    public static function maybe_create_table(): void
    {
        global $wpdb;
        $table_name = self::table_name();
        $db_version = '1.0.0';

        $table_exists = WPDeskSecurity::table_exists($table_name);

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

            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            dbDelta($sql);
            update_option('vgt_desk_db_version', $db_version);
        }

        if (WPDeskSecurity::table_exists($table_name)) {
            $duplicates = $wpdb->get_results(
                "SELECT user_id, setting_key, COUNT(*) as cnt
                 FROM {$table_name}
                 GROUP BY user_id, setting_key
                 HAVING cnt > 1"
            );
            if (!empty($duplicates)) {
                foreach ($duplicates as $dup) {
                    $max_id = $wpdb->get_var($wpdb->prepare(
                        "SELECT MAX(id) FROM {$table_name} WHERE user_id = %d AND setting_key = %s",
                        $dup->user_id,
                        $dup->setting_key
                    ));
                    if ($max_id) {
                        $wpdb->query($wpdb->prepare(
                            "DELETE FROM {$table_name} WHERE user_id = %d AND setting_key = %s AND id != %d",
                            $dup->user_id,
                            $dup->setting_key,
                            $max_id
                        ));
                    }
                }
            }

            $index_exists = $wpdb->get_results("SHOW INDEX FROM {$table_name} WHERE Key_name = 'user_setting'");
            if (empty($index_exists)) {
                $wpdb->query("ALTER TABLE {$table_name} ADD UNIQUE KEY user_setting (user_id, setting_key)");
            }
        }
    }

    /**
     * Single write path for desk settings. No dual-write to usermeta on every save.
     * Legacy usermeta is only read once during get_user_settings migration.
     */
    public static function set_user_setting(int $user_id, string $key, string $value): void
    {
        if ($user_id <= 0) {
            throw new ValidationException('Invalid user id for settings write.');
        }
        if (!in_array($key, self::ALLOWED_SETTING_TYPES, true)) {
            throw new ValidationException('Invalid configuration parameters submitted.');
        }

        global $wpdb;
        $table_name = self::table_name();
        self::maybe_create_table();

        $result = $wpdb->replace(
            $table_name,
            [
                'user_id'       => $user_id,
                'setting_key'   => $key,
                'setting_value' => $value,
            ],
            ['%d', '%s', '%s']
        );

        if ($result === false) {
            throw new StorageException('Database transaction failure during settings replace.');
        }
    }

    /**
     * Pure sanitizer for folder structures. Empty apps arrays stay arrays.
     *
     * @param array<string,mixed> $decoded
     * @return array<string,array{title:string,apps:list<string>,left:string,top:string}>
     */
    public static function sanitize_folders(array $decoded): array
    {
        $sanitized = [];
        foreach ($decoded as $folder_id => $folder_data) {
            $f_id = self::pure_sanitize_key((string) $folder_id);
            if ($f_id === '' || !is_array($folder_data)) {
                continue;
            }
            $apps = [];
            foreach ((array) ($folder_data['apps'] ?? []) as $app) {
                $apps[] = self::pure_sanitize_key((string) $app);
            }
            $sanitized[$f_id] = [
                'title' => self::pure_sanitize_text((string) ($folder_data['title'] ?? '')),
                'apps'  => array_values($apps),
                'left'  => self::pure_sanitize_text((string) ($folder_data['left'] ?? '')),
                'top'   => self::pure_sanitize_text((string) ($folder_data['top'] ?? '')),
            ];
        }
        return $sanitized;
    }

    /**
     * Encode JSON settings without forcing empty arrays into objects.
     *
     * @param array<mixed> $decoded
     */
    public static function encode_setting_json(string $type, array $decoded): string
    {
        if ($type === 'pinned_apps') {
            $list = array_values(array_map(
                static fn($v) => self::pure_sanitize_key((string) $v),
                $decoded
            ));
            $encoded = json_encode($list, JSON_UNESCAPED_UNICODE);
        } else {
            // Preserve arrays (e.g. folders.apps: []) — never JSON_FORCE_OBJECT.
            $encoded = json_encode($decoded, JSON_UNESCAPED_UNICODE);
        }

        if ($encoded === false) {
            throw new ValidationException('Failed to encode JSON setting payload.');
        }
        return $encoded;
    }

    /**
     * @param array<string,mixed> $decoded
     * @return array<string,string>
     */
    public static function sanitize_shortcuts(array $decoded): array
    {
        $out = [];
        foreach ($decoded as $shortcut_key => $shortcut_val) {
            $s_key = self::pure_sanitize_key((string) $shortcut_key);
            $s_val = preg_replace('/[^A-Za-z0-9\+\s]/', '', (string) $shortcut_val) ?? '';
            if ($s_key !== '') {
                $out[$s_key] = $s_val;
            }
        }
        return $out;
    }

    /**
     * Normalize a raw setting value for persistence. Pure for scalar rules;
     * wallpaper host checks may use WP when available.
     *
     * @param mixed $value
     * @param string|null $existing_json Existing DB value for merge keys only.
     */
    public static function normalize_setting_value(string $type, $value, ?string $existing_json = null): string
    {
        if (!in_array($type, self::ALLOWED_SETTING_TYPES, true)) {
            throw new ValidationException('Invalid configuration parameters submitted.');
        }

        if ($type === 'accent_color' && !in_array((string) $value, self::ALLOWED_ACCENT_COLORS, true)) {
            throw new ValidationException('Illegal accent color value.');
        }
        if ($type === 'sound_pack' && !in_array((string) $value, ['synth_default', 'cyber_neon', 'classic_bell', 'digital_minimal'], true)) {
            throw new ValidationException('Illegal sound pack value.');
        }
        if ($type === 'layout_style' && !in_array((string) $value, ['macos', 'windows', 'linux'], true)) {
            throw new ValidationException('Illegal layout style value.');
        }
        if ($type === 'active_preset' && !in_array((string) $value, ['publisher', 'security', 'developer', 'minimal', ''], true)) {
            throw new ValidationException('Illegal active preset value.');
        }

        if (in_array($type, ['blur', 'widgets_visible', 'icons_visible', 'audio_enabled', 'auto_redirect', 'first_run_completed', 'show_welcome_on_startup'], true)) {
            return ($value === 'true' || $value === '1' || $value === true) ? 'true' : 'false';
        }

        if ($type === 'font_size') {
            $val_int = (int) $value;
            if ($val_int < 10 || $val_int > 24) {
                throw new ValidationException('Illegal font size range.');
            }
            return (string) $val_int;
        }

        $json_keys = array_merge(self::FULL_REPLACE_JSON_KEYS, self::MERGE_JSON_KEYS);
        if (in_array($type, $json_keys, true)) {
            if (!is_string($value) && !is_array($value)) {
                throw new ValidationException('Expected string or array payload for JSON fields.');
            }
            if (is_string($value)) {
                $decoded = json_decode($value, true);
                if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                    throw new ValidationException('Malformed JSON structural payload.');
                }
            } else {
                $decoded = $value;
            }

            if (in_array($type, self::MERGE_JSON_KEYS, true) && $existing_json !== null && $existing_json !== '') {
                $existing_data = json_decode($existing_json, true);
                if (is_array($existing_data)) {
                    $decoded = array_replace_recursive($existing_data, $decoded);
                }
            }

            if ($type === 'folders') {
                $decoded = self::sanitize_folders($decoded);
            }
            if ($type === 'shortcuts') {
                $decoded = self::sanitize_shortcuts($decoded);
            }
            if ($type === 'pinned_apps') {
                $decoded = array_values(array_map(
                    static fn($v) => self::pure_sanitize_key((string) $v),
                    $decoded
                ));
            }
            if ($type === 'classic_apps') {
                if (class_exists(WPDeskIframePolicy::class, false) || class_exists(__NAMESPACE__ . '\\WPDeskIframePolicy')) {
                    $decoded = WPDeskIframePolicy::normalize_classic_apps($decoded);
                } else {
                    $decoded = array_filter(
                        $decoded,
                        static fn($v) => $v === true || $v === 1 || $v === '1' || $v === 'true'
                    );
                }
            }
            if ($type === 'widget_positions') {
                $norm = WPDeskWidgetLayout::normalize_positions($decoded);
                if (!$norm['ok']) {
                    throw new ValidationException('Invalid widget_positions payload: ' . $norm['code']);
                }
                $decoded = $norm['positions'];
            }

            return self::encode_setting_json($type, $decoded);
        }

        if ($type === 'wallpaper') {
            $raw = is_string($value) ? $value : '';
            if (str_starts_with($raw, 'data:image/')) {
                if (!WPDeskSecurity::is_safe_wallpaper_url($raw)) {
                    throw new ValidationException(
                        'Wallpaper-URL muss Same-Origin, aus der WordPress-Mediathek oder eine lokale Ressource sein.'
                    );
                }
                return $raw;
            }
            $normalized = function_exists('esc_url_raw') ? esc_url_raw($raw) : $raw;
            if (!WPDeskSecurity::is_safe_wallpaper_url($normalized)) {
                throw new ValidationException(
                    'Wallpaper-URL muss Same-Origin, aus der WordPress-Mediathek oder eine lokale Ressource sein.'
                );
            }
            return $normalized;
        }

        return self::pure_sanitize_key((string) $value);
    }

    public static function get_user_settings(int $user_id): array
    {
        global $wpdb;
        $table_name = self::table_name();

        self::maybe_create_table();

        $db_settings = [];
        if (WPDeskSecurity::table_exists($table_name)) {
            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT setting_key, setting_value FROM {$table_name} WHERE user_id = %d ORDER BY id ASC",
                    $user_id
                ),
                ARRAY_A
            );
            if ($rows) {
                foreach ($rows as $row) {
                    $db_settings[$row['setting_key']] = $row['setting_value'];
                }
            }
        }

        $defaults = [
            'wallpaper'               => (defined('VGT_WPDESK_URL') ? VGT_WPDESK_URL : '') . 'wallpapers/wall1.webp',
            'accent_color'            => 'indigo',
            'blur'                    => 'true',
            'icon_positions'          => '{}',
            'window_settings'         => '{}',
            'widgets_visible'         => 'true',
            'icons_visible'           => 'true',
            'audio_enabled'           => 'true',
            'sound_pack'              => 'synth_default',
            'widget_positions'        => '{}',
            'folders'                 => '{}',
            'auto_redirect'           => 'false',
            'layout_style'            => 'macos',
            'pinned_apps'             => '["index_php", "options_general_php", "upload_php", "plugins_php", "users_php", "tools_php", "themes_php", "edit_php", "edit_comments_php"]',
            'font_size'               => '14',
            'shortcuts'               => '{"window_switch":"Alt+KeyQ","show_desktop":"Alt+KeyD","spotlight":"Control+Space","control_center":"Alt+KeyC","start_menu":"Alt+KeyS"}',
            'first_run_completed'     => 'false',
            'show_welcome_on_startup' => 'true',
            'classic_apps'            => '{}',
        ];

        // Merge keys present only in DB (e.g. classic_apps before defaults list expansion).
        foreach ($db_settings as $db_key => $db_val) {
            if (!array_key_exists($db_key, $defaults)) {
                $defaults[$db_key] = $db_val;
            }
        }

        $settings = [];
        foreach ($defaults as $key => $default_val) {
            if (isset($db_settings[$key])) {
                $settings[$key] = $db_settings[$key];
            } else {
                // One-shot migration from legacy usermeta into the relational table.
                $meta_val = get_user_meta($user_id, 'vgt_desk_' . $key, true);
                if ($meta_val !== '' && $meta_val !== false && $meta_val !== null) {
                    $settings[$key] = is_string($meta_val) ? $meta_val : (string) $meta_val;
                    try {
                        self::set_user_setting($user_id, $key, $settings[$key]);
                    } catch (\Throwable $e) {
                        // Migration best-effort; keep in-memory value.
                    }
                } else {
                    $settings[$key] = $default_val;
                }
            }
        }

        $font_size_val = (int) ($settings['font_size'] ?? 14);
        if ($font_size_val < 10 || $font_size_val > 24) {
            $font_size_val = 14;
        }

        $wallpaper = $settings['wallpaper'];
        if (function_exists('esc_url_raw') && !str_starts_with((string) $wallpaper, 'data:image/')) {
            $wallpaper = esc_url_raw((string) $wallpaper);
        }

        return [
            'wallpaper'               => $wallpaper,
            'accent_color'            => self::pure_sanitize_key((string) $settings['accent_color']),
            'blur'                    => $settings['blur'] !== 'false',
            'icon_positions'          => json_decode((string) $settings['icon_positions'], true) ?: [],
            'window_settings'         => json_decode((string) $settings['window_settings'], true) ?: [],
            'widgets_visible'         => $settings['widgets_visible'] !== 'false',
            'icons_visible'           => $settings['icons_visible'] !== 'false',
            'audio_enabled'           => $settings['audio_enabled'] !== 'false',
            'sound_pack'              => self::pure_sanitize_key((string) $settings['sound_pack']),
            'widget_positions'        => json_decode((string) $settings['widget_positions'], true) ?: [],
            'folders'                 => json_decode((string) $settings['folders'], true) ?: [],
            'classic_apps'            => class_exists(WPDeskIframePolicy::class)
                ? WPDeskIframePolicy::normalize_classic_apps(json_decode((string) ($settings['classic_apps'] ?? '{}'), true) ?: [])
                : (json_decode((string) ($settings['classic_apps'] ?? '{}'), true) ?: []),
            'auto_redirect'           => $settings['auto_redirect'] !== 'false',
            'layout_style'            => self::pure_sanitize_key((string) $settings['layout_style']),
            'pinned_apps'             => is_array(json_decode((string) $settings['pinned_apps'], true))
                ? json_decode((string) $settings['pinned_apps'], true)
                : ['index_php', 'options_general_php', 'upload_php', 'plugins_php', 'users_php', 'tools_php', 'themes_php', 'edit_php', 'edit_comments_php'],
            'font_size'               => $font_size_val,
            'first_run_completed'     => $settings['first_run_completed'] !== 'false',
            'show_welcome_on_startup' => $settings['show_welcome_on_startup'] !== 'false',
            'shortcuts'               => json_decode((string) $settings['shortcuts'], true) ?: [
                'window_switch'  => 'Alt+KeyQ',
                'show_desktop'   => 'Alt+KeyD',
                'spotlight'      => 'Control+Space',
                'control_center' => 'Alt+KeyC',
                'start_menu'     => 'Alt+KeyS',
            ],
        ];
    }

    public static function show_optin_admin_notice(): void
    {
        $is_iframe = false;
        if (isset($_GET['vgt_iframe']) && $_GET['vgt_iframe'] === 'true') {
            $is_iframe = true;
        } elseif (isset($_SERVER['HTTP_SEC_FETCH_DEST']) && $_SERVER['HTTP_SEC_FETCH_DEST'] === 'iframe') {
            $is_iframe = true;
        } elseif (isset($_SERVER['HTTP_REFERER'])) {
            $referer_query = parse_url((string) $_SERVER['HTTP_REFERER'], PHP_URL_QUERY);
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
                <strong>VGT WP-Desk:</strong> Moechten Sie das elegante Desktop-Design als Standard-Ansicht fuer Ihr WordPress-Backend aktivieren?
                <a href="' . esc_url($optin_url) . '" class="button button-primary" style="background: #6366f1; border-color: #6366f1; margin-left: 10px;">Desktop-Modus aktivieren</a>
                <a href="' . esc_url($dismiss_url) . '" class="button button-secondary" style="margin-left: 5px;">Nein, danke</a>
            </p>
        </div>';
    }

    /** WP-independent sanitize_key fallback for pure unit tests. */
    public static function pure_sanitize_key(string $key): string
    {
        if (function_exists('sanitize_key')) {
            return sanitize_key($key);
        }
        $key = strtolower($key);
        return (string) preg_replace('/[^a-z0-9_\-]/', '', $key);
    }

    public static function pure_sanitize_text(string $text): string
    {
        if (function_exists('sanitize_text_field')) {
            return sanitize_text_field($text);
        }
        return trim(strip_tags($text));
    }
}
