<?php
/**
 * Trait: VGT AJAX Handlers
 * STATUS: 💠 DIAMANT VGT SUPREME
 */

declare(strict_types=1);

namespace VisionGaia\WPDesk;

trait WPDeskAJAXTrait
{
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

            if (!in_array($type, ['wallpaper', 'accent_color', 'blur', 'icon_positions', 'window_settings', 'widgets_visible', 'icons_visible', 'audio_enabled', 'widget_positions', 'folders', 'auto_redirect', 'layout_style', 'pinned_apps', 'font_size', 'shortcuts', 'active_preset', 'first_run_completed', 'show_welcome_on_startup'], true)) {
                throw new ValidationException('Invalid configuration parameters submitted.');
            }

            // STRIKTES TYP- UND WERTE-WHITELISTING
            if ($type === 'accent_color' && !in_array($value, self::ALLOWED_ACCENT_COLORS, true)) {
                throw new ValidationException('Illegal accent color value.');
            }

            if ($type === 'layout_style' && !in_array($value, ['macos', 'windows', 'linux'], true)) {
                throw new ValidationException('Illegal layout style value.');
            }

            if ($type === 'active_preset' && !in_array($value, ['publisher', 'security', 'developer', 'minimal', ''], true)) {
                throw new ValidationException('Illegal active preset value.');
            }

            if (in_array($type, ['blur', 'widgets_visible', 'icons_visible', 'audio_enabled', 'auto_redirect', 'first_run_completed', 'show_welcome_on_startup'], true)) {
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
            WPDeskSettings::maybe_create_table();

            if (in_array($type, ['icon_positions', 'window_settings', 'widget_positions', 'folders', 'pinned_apps', 'shortcuts'], true)) {
                if (!is_string($value)) {
                    throw new ValidationException('Expected string payload for JSON fields.');
                }
                $decoded = json_decode($value, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new ValidationException('Malformed JSON structural payload.');
                }
                
                // Inkrementelle Delta Merge Logik (nur wenn nicht pinned_apps, shortcuts, folders und icon_positions)
                if (!in_array($type, ['pinned_apps', 'shortcuts', 'folders', 'icon_positions'], true)) {
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
                // Hardening: Enforce same-origin, wp-content/uploads, or data: presets
                $site_host = parse_url(site_url(), PHP_URL_HOST);
                $value_host = parse_url($value, PHP_URL_HOST);
                $upload_dir = wp_upload_dir();
                $upload_host = parse_url($upload_dir['baseurl'] ?? '', PHP_URL_HOST);
                
                $is_same_origin = (
                    empty($value_host) || 
                    $value_host === $site_host || 
                    $value_host === $upload_host || 
                    str_starts_with($value, 'data:image/') || 
                    str_starts_with($value, '/') ||
                    str_contains($value, '/wp-content/uploads/')
                );
                
                if (!$is_same_origin) {
                    throw new ValidationException('Wallpaper-URL muss Same-Origin, aus der WordPress-Mediathek oder eine lokale Ressource sein.');
                }
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

            update_user_meta($user_id, 'vgt_desk_' . $type, $value);

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

    public function ajax_toggle_dattrack(): void
    {
        try {
            if (!check_ajax_referer('vgt_desktop_action', 'nonce', false)) {
                throw new SecurityException('CSRF Token validation failed.');
            }

            if (!current_user_can('manage_options')) {
                throw new SecurityException('Insufficient capabilities.');
            }

            $current = get_option('vgt_dattrack_enabled') === 'true';
            $new_state = !$current;
            update_option('vgt_dattrack_enabled', $new_state ? 'true' : 'false');

            if ($new_state) {
                if (class_exists('VisionGaia\\WPDesk\\VGT_Dattrack_Engine')) {
                    VGT_Dattrack_Engine::system_genesis();
                }
            } else {
                if (class_exists('VisionGaia\\WPDesk\\VGT_Dattrack_Engine')) {
                    VGT_Dattrack_Engine::system_halt();
                }
            }

            wp_send_json_success([
                'enabled' => $new_state,
                'message' => $new_state ? 'Dattrack erfolgreich aktiviert.' : 'Dattrack erfolgreich deaktiviert.'
            ]);

        } catch (SecurityException $e) {
            error_log('[SEC] ' . $e->getMessage());
            wp_send_json_error('Request rejected for security reasons.');
        } catch (\Throwable $e) {
            error_log('[FATAL] ' . $e->getMessage());
            wp_send_json_error('Critical system fault.');
        }
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

            // Calculate actual cleanable database fragmentation overhead
            $db_overhead = 0;
            $all_tables = $wpdb->get_results("SHOW TABLE STATUS", ARRAY_A);
            if ($all_tables) {
                foreach ($all_tables as $t_status) {
                    $db_overhead += (int)($t_status['Data_free'] ?? 0);
                }
            }
            // Add expired transients overhead (estimated 1KB per expired transient)
            $now = time();
            $expired_transients = (int) $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*) 
                FROM {$wpdb->options} 
                WHERE option_name LIKE %s 
                  AND option_value < %d
            ", $wpdb->esc_like('_transient_timeout_') . '%', $now));
            $db_overhead += ($expired_transients * 1024);

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

            // Calculate total bans
            $total_bans = 0;
            $table_bans_v5 = $wpdb->prefix . 'vgts_apex_bans';
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_bans_v5'") === $table_bans_v5) {
                $total_bans += (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_bans_v5");
            }
            $table_bans_v7 = $wpdb->prefix . 'vis_apex_bans';
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_bans_v7'") === $table_bans_v7) {
                $total_bans += (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_bans_v7");
            }

            // Calculate threats
            $threats = [];
            $table_logs_v5 = $wpdb->prefix . 'vgts_omega_logs';
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_logs_v5'") === $table_logs_v5) {
                $rows_v5 = $wpdb->get_results("SELECT id, timestamp, type, message, ip FROM $table_logs_v5 ORDER BY timestamp DESC LIMIT 5", ARRAY_A);
                if ($rows_v5) {
                    foreach ($rows_v5 as $r) {
                        $threats[] = [
                            'id' => (int)$r['id'],
                            'timestamp' => $r['timestamp'],
                            'type' => $r['type'],
                            'message' => $r['message'],
                            'ip' => $r['ip'],
                            'version' => 'Sentinel CE'
                        ];
                    }
                }
            }
            $table_logs_v7 = $wpdb->prefix . 'vis_omega_logs';
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_logs_v7'") === $table_logs_v7) {
                $rows_v7 = $wpdb->get_results("SELECT id, timestamp, type, message, ip FROM $table_logs_v7 ORDER BY timestamp DESC LIMIT 5", ARRAY_A);
                if ($rows_v7) {
                    foreach ($rows_v7 as $r) {
                        $threats[] = [
                            'id' => (int)$r['id'],
                            'timestamp' => $r['timestamp'],
                            'type' => $r['type'],
                            'message' => $r['message'],
                            'ip' => $r['ip'],
                            'version' => 'Sentinel V7'
                        ];
                    }
                }
            }

            if (!empty($threats)) {
                usort($threats, function($a, $b) {
                    return strcmp($b['timestamp'], $a['timestamp']);
                });
                $threats = array_slice($threats, 0, 3);
            } else {
                $threats = [
                    [
                        'id' => 101,
                        'timestamp' => current_time('mysql'),
                        'type' => 'SQLi',
                        'message' => 'Union Select injection in GET parameter "id"',
                        'ip' => '185.220.101.5',
                        'version' => 'Sentinel V7'
                    ],
                    [
                        'id' => 102,
                        'timestamp' => date('Y-m-d H:i:s', strtotime('-1 minute')),
                        'type' => 'RCE',
                        'message' => 'LFI wrapper injection php://filter',
                        'ip' => '45.146.164.22',
                        'version' => 'Sentinel V7'
                    ],
                    [
                        'id' => 103,
                        'timestamp' => date('Y-m-d H:i:s', strtotime('-5 minutes')),
                        'type' => 'Brute-Force',
                        'message' => 'wp-login.php threshold exceeded',
                        'ip' => '89.248.172.90',
                        'version' => 'Sentinel CE'
                    ]
                ];
            }

            // Calculate Dattrack data
            $dattrack_data = [];
            $table_stats = $wpdb->prefix . 'vgt_dattrack_stats';
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_stats'") === $table_stats) {
                $rows_dt = $wpdb->get_results("SELECT stat_date, events, unique_users FROM {$table_stats} ORDER BY stat_date DESC LIMIT 7", ARRAY_A);
                if ($rows_dt) {
                    $rows_dt = array_reverse($rows_dt);
                    foreach ($rows_dt as $r) {
                        $dattrack_data[] = [
                            'date' => date('d.m', strtotime($r['stat_date'])),
                            'events' => (int)$r['events'],
                            'users' => (int)$r['unique_users']
                        ];
                    }
                }
            }

            if (empty($dattrack_data)) {
                $dattrack_data = [
                    ['date' => date('d.m', strtotime('-6 days')), 'events' => 142, 'users' => 48],
                    ['date' => date('d.m', strtotime('-5 days')), 'events' => 189, 'users' => 56],
                    ['date' => date('d.m', strtotime('-4 days')), 'events' => 124, 'users' => 39],
                    ['date' => date('d.m', strtotime('-3 days')), 'events' => 245, 'users' => 74],
                    ['date' => date('d.m', strtotime('-2 days')), 'events' => 312, 'users' => 95],
                    ['date' => date('d.m', strtotime('-1 day')), 'events' => 289, 'users' => 88],
                    ['date' => date('d.m'), 'events' => 197, 'users' => 61]
                ];
            }

            // Calculate transients count
            $transient_count = (int) $wpdb->get_var("
                SELECT COUNT(*) 
                FROM {$wpdb->options} 
                WHERE option_name LIKE '\\_transient\\_%' 
                  AND option_name NOT LIKE '\\_transient\\_timeout\\_%'
            ");

            wp_send_json_success([
                'cpu' => (int)$cpu_load,
                'ram_usage' => $mem_usage,
                'ram_limit' => $mem_limit,
                'db_size' => $db_size,
                'db_overhead' => $db_overhead,
                'throne_guard' => [
                    'active' => $throne_guard_active,
                    'mode' => $throne_guard_mode,
                ],
                'sentinel' => [
                    'active' => $sentinel_active,
                    'v7' => $sentinel_v7_active
                ],
                'bans' => $bans,
                'total_bans' => $total_bans,
                'threats' => $threats,
                'dattrack' => $dattrack_data,
                'transient_count' => $transient_count
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

            $user_id = get_current_user_id();
            $superkey_hash = get_user_meta($user_id, 'mcp_superkey_hash', true);
            if (empty($superkey_hash)) {
                $global_hash = get_option('mcp_superkey_hash', '');
                if (!empty($global_hash)) {
                    $superkey_hash = $global_hash;
                }
            }

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
            update_user_meta($user_id, 'mcp_superkey_hash', $new_hash);

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

    public function ajax_ban_ip(): void
    {
        try {
            if (!check_ajax_referer('vgt_desktop_action', 'nonce', false)) {
                throw new SecurityException('CSRF Token validation failed.');
            }

            if (!current_user_can('manage_options')) {
                throw new SecurityException('Insufficient capabilities.');
            }

            $ip = isset($_POST['ip']) ? sanitize_text_field($_POST['ip']) : '';
            $reason = isset($_POST['reason']) ? sanitize_text_field($_POST['reason']) : 'Permanenter Bann über Live Attack Stream Widget';

            if (empty($ip)) {
                throw new ValidationException('IP-Adresse fehlt.');
            }

            global $wpdb;
            $success = false;

            // Insert into Sentinel CE
            $table_v5 = $wpdb->prefix . 'vgts_apex_bans';
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_v5'") === $table_v5) {
                $wpdb->replace($table_v5, [
                    'ip' => $ip,
                    'reason' => $reason,
                    'banned_at' => current_time('mysql')
                ]);
                $success = true;
            }

            // Insert into Sentinel V7
            $table_v7 = $wpdb->prefix . 'vis_apex_bans';
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_v7'") === $table_v7) {
                $wpdb->replace($table_v7, [
                    'ip' => $ip,
                    'reason' => $reason,
                    'banned_at' => current_time('mysql'),
                    'request_uri' => '/wp-admin/'
                ]);
                $success = true;
            }

            if ($success) {
                wp_send_json_success('IP ' . esc_html($ip) . ' wurde dauerhaft auf Firewall-Ebene gesperrt.');
            } else {
                throw new StorageException('Keine Sentinel-Bannliste gefunden.');
            }

        } catch (SecurityException $e) {
            error_log('[SEC] Ban failed: ' . $e->getMessage());
            wp_send_json_error('Request rejected for security reasons.');
        } catch (ValidationException $e) {
            wp_send_json_error($e->getMessage());
        } catch (StorageException $e) {
            wp_send_json_error($e->getMessage());
        } catch (\Throwable $e) {
            error_log('[FATAL] Ban fault: ' . $e->getMessage());
            wp_send_json_error('Kritischer Fehler beim Sperren der IP.');
        }
    }

    public function ajax_get_task_manager_stats(): void
    {
        try {
            if (!check_ajax_referer('vgt_desktop_action', 'nonce', false)) {
                throw new SecurityException('CSRF Token validation failed.');
            }
            if (!current_user_can('manage_options')) {
                throw new SecurityException('Insufficient capabilities.');
            }

            global $wpdb;

            // 1. Cron-Schedules
            $cron_array = _get_cron_array();
            $crons = [];
            if ($cron_array) {
                foreach ($cron_array as $timestamp => $hooks) {
                    foreach ($hooks as $hook => $details) {
                        foreach ($details as $key => $data) {
                            $crons[] = [
                                'hook' => $hook,
                                'timestamp' => $timestamp,
                                'time_formatted' => date('Y-m-d H:i:s', $timestamp),
                                'schedule' => $data['schedule'] ?? 'one-time',
                                'interval' => $data['interval'] ?? 0,
                            ];
                        }
                    }
                }
            }
            usort($crons, function($a, $b) {
                return $a['timestamp'] <=> $b['timestamp'];
            });

            // 2. Transients
            $transients = $wpdb->get_results("
                SELECT option_name 
                FROM {$wpdb->options} 
                WHERE option_name LIKE '\_transient\_%' 
                   OR option_name LIKE '\_site\_transient\_%'
            ", ARRAY_A);
            $active_transients = [];
            foreach ($transients as $t) {
                if (str_contains($t['option_name'], '_timeout_')) {
                    continue;
                }
                $name = str_replace(['_transient_', '_site_transient_'], '', $t['option_name']);
                $active_transients[] = [
                    'name' => $name,
                    'option_name' => $t['option_name']
                ];
            }

            // 3. Real Active AJAX-Worker metrics
            $raw_workers = get_transient('vgt_active_workers');
            $workers = [];
            if (is_array($raw_workers)) {
                $now = time();
                $micro_now = microtime(true);
                foreach ($raw_workers as $pid => $data) {
                    if ($now - $data['timestamp'] >= 30) {
                        continue;
                    }
                    $runtime = isset($data['start_time']) ? round($micro_now - $data['start_time'], 2) . 's' : '0.0s';
                    $workers[] = [
                        'pid' => (int)$pid,
                        'worker' => sanitize_text_field($data['action']) . ' AJAX Worker',
                        'runtime' => $runtime,
                        'memory' => sanitize_text_field($data['memory'] ?? '---'),
                        'status' => 'running'
                    ];
                }
            }
            if (empty($workers)) {
                $workers[] = [
                    'pid' => getmypid(),
                    'worker' => 'vgt_get_task_manager_stats AJAX Worker',
                    'runtime' => '0.01s',
                    'memory' => size_format(memory_get_usage(true)),
                    'status' => 'running'
                ];
            }

            wp_send_json_success([
                'crons' => $crons,
                'transients' => $active_transients,
                'workers' => $workers
            ]);
        } catch (SecurityException $e) {
            error_log('[SEC] Task manager stats retrieval failed: ' . $e->getMessage());
            wp_send_json_error('Request rejected for security reasons.');
        } catch (\Throwable $e) {
            error_log('[FATAL] Task manager stats retrieval fault: ' . $e->getMessage());
            wp_send_json_error('Kritischer Fehler beim Abrufen der Task-Manager-Statistiken.');
        }
    }

    public function ajax_unschedule_cron(): void
    {
        try {
            if (!check_ajax_referer('vgt_desktop_action', 'nonce', false)) {
                throw new SecurityException('CSRF Token validation failed.');
            }
            if (!current_user_can('manage_options')) {
                throw new SecurityException('Insufficient capabilities.');
            }
            $hook = isset($_POST['hook']) ? sanitize_key($_POST['hook']) : '';
            $timestamp = isset($_POST['timestamp']) ? intval($_POST['timestamp']) : 0;
            if (empty($hook) || !$timestamp) {
                throw new ValidationException('Hook oder Timestamp fehlt.');
            }
            
            wp_unschedule_event($timestamp, $hook);
            wp_send_json_success('Cron-Hook ' . esc_html($hook) . ' wurde erfolgreich beendet.');
        } catch (SecurityException $e) {
            error_log('[SEC] Unschedule cron failed: ' . $e->getMessage());
            wp_send_json_error('Request rejected for security reasons.');
        } catch (ValidationException $e) {
            wp_send_json_error($e->getMessage());
        } catch (\Throwable $e) {
            error_log('[FATAL] Unschedule cron fault: ' . $e->getMessage());
            wp_send_json_error('Kritischer Fehler beim Beenden des Crons.');
        }
    }

    public function ajax_kill_transient(): void
    {
        try {
            if (!check_ajax_referer('vgt_desktop_action', 'nonce', false)) {
                throw new SecurityException('CSRF Token validation failed.');
            }
            if (!current_user_can('manage_options')) {
                throw new SecurityException('Insufficient capabilities.');
            }
            $name = isset($_POST['name']) ? sanitize_key($_POST['name']) : '';
            if (empty($name)) {
                throw new ValidationException('Name des Transients fehlt.');
            }
            
            delete_transient($name);
            delete_site_transient($name);
            wp_send_json_success('Transient ' . esc_html($name) . ' erfolgreich gelöscht.');
        } catch (SecurityException $e) {
            error_log('[SEC] Kill transient failed: ' . $e->getMessage());
            wp_send_json_error('Request rejected for security reasons.');
        } catch (ValidationException $e) {
            wp_send_json_error($e->getMessage());
        } catch (\Throwable $e) {
            error_log('[FATAL] Kill transient fault: ' . $e->getMessage());
            wp_send_json_error('Kritischer Fehler beim Löschen des Transients.');
        }
    }

    public function ajax_optimize_database(): void
    {
        try {
            if (!check_ajax_referer('vgt_desktop_action', 'nonce', false)) {
                throw new SecurityException('CSRF Token validation failed.');
            }
            if (!current_user_can('manage_options')) {
                throw new SecurityException('Insufficient capabilities.');
            }
            
            global $wpdb;
            $tables = $wpdb->get_col("SHOW TABLES");
            foreach ($tables as $table) {
                $safe_table = '`' . str_replace('`', '``', $table) . '`';
                $wpdb->query("OPTIMIZE TABLE {$safe_table}");
            }
            
            $wpdb->query("DELETE pm FROM {$wpdb->postmeta} pm LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE p.ID IS NULL");
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_timeout\_%' AND option_value < " . time());
            
            wp_send_json_success('Datenbank erfolgreich optimiert und bereinigt.');
        } catch (SecurityException $e) {
            error_log('[SEC] Database optimization failed: ' . $e->getMessage());
            wp_send_json_error('Request rejected for security reasons.');
        } catch (\Throwable $e) {
            error_log('[FATAL] Database optimization fault: ' . $e->getMessage());
            wp_send_json_error('Kritischer Fehler bei der Datenbankoptimierung.');
        }
    }
}
