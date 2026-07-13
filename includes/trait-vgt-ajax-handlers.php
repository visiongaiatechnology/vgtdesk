<?php
/**
 * Trait: VGT AJAX Handlers
 * STATUS: DIAMANT VGT SUPREME
 */

declare(strict_types=1);

namespace VisionGaia\WPDesk;

trait WPDeskAJAXTrait
{
    public function ajax_save_user_settings(): void
    {
        WPDeskAjaxGuard::run('read', function (): void {
            $user_id = (int) get_current_user_id();
            $type  = isset($_POST['setting_type']) ? sanitize_key((string) $_POST['setting_type']) : '';
            $value = isset($_POST['value']) ? wp_unslash($_POST['value']) : '';

            $existing = null;
            if (in_array($type, WPDeskSettings::MERGE_JSON_KEYS, true)) {
                global $wpdb;
                $table_name = WPDeskSettings::table_name();
                WPDeskSettings::maybe_create_table();
                $existing = $wpdb->get_var($wpdb->prepare(
                    "SELECT setting_value FROM {$table_name} WHERE user_id = %d AND setting_key = %s",
                    $user_id,
                    $type
                ));
                $existing = is_string($existing) ? $existing : null;
            }

            $normalized = WPDeskSettings::normalize_setting_value($type, $value, $existing);
            WPDeskSettings::set_user_setting($user_id, $type, $normalized);

            WPDeskAjaxGuard::send_success(['message' => 'Configuration persisted successfully.', 'type' => $type]);
        });
    }

    public function ajax_toggle_sentinel(): void
    {
        WPDeskAjaxGuard::run('manage_options', function (): void {
            $current = get_option('vgt_sentinel_enabled') === 'true';
            $new_state = !$current;
            update_option('vgt_sentinel_enabled', $new_state ? 'true' : 'false');

            WPDeskSecurity::audit_control_action(
                $new_state ? 'sentinel_enable' : 'sentinel_soft_disable',
                ['previous' => $current ? 'true' : 'false', 'enabled' => $new_state ? 'true' : 'false']
            );

            WPDeskAjaxGuard::send_success([
                'enabled' => $new_state,
                'message' => $new_state ? 'Sentinel erfolgreich aktiviert.' : 'Sentinel soft-disabled (audit recorded).',
            ]);
        });
    }

    public function ajax_toggle_dattrack(): void
    {
        WPDeskAjaxGuard::run('manage_options', function (): void {
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

            WPDeskAjaxGuard::send_success([
                'enabled' => $new_state,
                'message' => $new_state ? 'Dattrack erfolgreich aktiviert.' : 'Dattrack erfolgreich deaktiviert.',
            ]);
        });
    }

    public function ajax_toggle_integrated_module(): void
    {
        WPDeskAjaxGuard::run('manage_options', function (): void {
            $module = isset($_POST['module']) ? sanitize_key((string) wp_unslash($_POST['module'])) : '';
            $enabled_raw = isset($_POST['enabled']) ? sanitize_key((string) wp_unslash($_POST['enabled'])) : '';

            if ($module === '' || !in_array($enabled_raw, ['true', 'false', '1', '0'], true)) {
                throw new ValidationException('Invalid module toggle payload.');
            }

            $enabled = $enabled_raw === 'true' || $enabled_raw === '1';
            $result = self::set_integrated_module_enabled($module, $enabled);

            WPDeskSecurity::audit_control_action(
                $enabled ? 'module_enable' : 'module_soft_disable',
                ['module' => $module, 'enabled' => $enabled ? 'true' : 'false']
            );

            WPDeskAjaxGuard::send_success([
                'module'  => $result['module'],
                'label'   => $result['label'],
                'enabled' => $result['enabled'],
                'reload'  => $result['reload'],
                'modules' => self::integrated_module_statuses(),
                'message' => $result['label'] . ($result['enabled'] ? ' aktiviert.' : ' deaktiviert.'),
            ]);
        });
    }

    public function ajax_get_diagnostics(): void
    {
        WPDeskAjaxGuard::run('manage_options', function (): void {
            $mem_usage = memory_get_usage(true);
            $mem_limit_str = ini_get('memory_limit');
            if ($mem_limit_str === '-1' || (int) $mem_limit_str === -1) {
                $mem_limit = -1;
            } else {
                $mem_limit = wp_convert_hr_to_bytes($mem_limit_str);
            }

            $cpu_load = WPDeskSecurity::cpu_load_percent();

            global $wpdb;
            $vgt_tables = [];
            foreach (WPDeskSecurity::optimizable_table_suffixes() as $suffix) {
                $vgt_tables[] = $wpdb->prefix . $suffix;
            }
            $db_size = 0;
            $db_overhead = 0;
            foreach ($vgt_tables as $table) {
                if (WPDeskSecurity::table_exists($table)) {
                    $status = $wpdb->get_row($wpdb->prepare('SHOW TABLE STATUS LIKE %s', $wpdb->esc_like($table)));
                    if ($status) {
                        $db_size += ((int) ($status->Data_length ?? 0) + (int) ($status->Index_length ?? 0));
                        $db_overhead += (int) ($status->Data_free ?? 0);
                    }
                }
            }

            $now = time();
            $expired_transients = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->options}
                 WHERE option_name LIKE %s AND option_value < %d",
                $wpdb->esc_like('_transient_timeout_') . '%',
                $now
            ));
            $db_overhead += ($expired_transients * 1024);

            $throne_guard_active = WPDeskSecurity::throne_guard_active();
            $throne_guard_mode = current_user_can('mcp_master_access')
                ? 'Master User Mode'
                : ($throne_guard_active ? 'Superkey configured' : 'Standard Admin Mode');

            $sentinel_state = WPDeskSecurity::sentinel_state();
            $bans = WPDeskBanStore::list_recent(50);
            $total_bans = WPDeskBanStore::count_all();

            $threats = [];
            $table_logs_v5 = $wpdb->prefix . 'vgts_omega_logs';
            if (WPDeskSecurity::table_exists($table_logs_v5)) {
                $rows_v5 = $wpdb->get_results(
                    "SELECT id, timestamp, type, message, ip FROM {$table_logs_v5} ORDER BY timestamp DESC LIMIT 5",
                    ARRAY_A
                );
                if ($rows_v5) {
                    foreach ($rows_v5 as $r) {
                        $threats[] = [
                            'id'        => (int) $r['id'],
                            'timestamp' => $r['timestamp'],
                            'type'      => $r['type'],
                            'message'   => $r['message'],
                            'ip'        => $r['ip'],
                            'version'   => 'Sentinel CE',
                        ];
                    }
                }
            }
            $table_logs_v7 = $wpdb->prefix . 'vis_omega_logs';
            if (WPDeskSecurity::table_exists($table_logs_v7)) {
                $rows_v7 = $wpdb->get_results(
                    "SELECT id, timestamp, type, message, ip FROM {$table_logs_v7} ORDER BY timestamp DESC LIMIT 5",
                    ARRAY_A
                );
                if ($rows_v7) {
                    foreach ($rows_v7 as $r) {
                        $threats[] = [
                            'id'        => (int) $r['id'],
                            'timestamp' => $r['timestamp'],
                            'type'      => $r['type'],
                            'message'   => $r['message'],
                            'ip'        => $r['ip'],
                            'version'   => 'Sentinel V7',
                        ];
                    }
                }
            }

            if (!empty($threats)) {
                usort($threats, static fn($a, $b) => strcmp($b['timestamp'], $a['timestamp']));
                $threats = array_slice($threats, 0, 3);
            }

            $dattrack_data = [];
            $table_stats = $wpdb->prefix . 'vgt_dattrack_stats';
            if (WPDeskSecurity::table_exists($table_stats)) {
                $rows_dt = $wpdb->get_results(
                    "SELECT stat_date, events, unique_users FROM {$table_stats} ORDER BY stat_date DESC LIMIT 7",
                    ARRAY_A
                );
                if ($rows_dt) {
                    $rows_dt = array_reverse($rows_dt);
                    foreach ($rows_dt as $r) {
                        $dattrack_data[] = [
                            'date'   => date('d.m', strtotime($r['stat_date'])),
                            'events' => (int) $r['events'],
                            'users'  => (int) $r['unique_users'],
                        ];
                    }
                }
            }

            $transient_count = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->options}
                 WHERE option_name LIKE '\\_transient\\_%'
                   AND option_name NOT LIKE '\\_transient\\_timeout\\_%'"
            );

            WPDeskAjaxGuard::send_success([
                'cpu'             => $cpu_load,
                'cpu_available'   => $cpu_load !== null,
                'ram_usage'       => $mem_usage,
                'ram_limit'       => $mem_limit,
                'db_size'         => $db_size,
                'db_overhead'     => $db_overhead,
                'throne_guard'    => [
                    'active' => $throne_guard_active,
                    'mode'   => $throne_guard_mode,
                ],
                'sentinel'        => [
                    'active' => $sentinel_state['active'],
                    'v7'     => $sentinel_state['v7_active'],
                ],
                'bans'            => $bans,
                'total_bans'      => $total_bans,
                'threats'         => $threats,
                'dattrack'        => $dattrack_data,
                'integrated_modules' => self::integrated_module_statuses(),
                'transient_count' => $transient_count,
            ]);
        });
    }

    public function ajax_unban_ip(): void
    {
        WPDeskAjaxGuard::run('manage_options', function (): void {
            $ip = isset($_POST['ip']) ? (string) $_POST['ip'] : '';
            $version = isset($_POST['version']) ? sanitize_text_field((string) $_POST['version']) : '';
            if ($ip === '') {
                throw new ValidationException('IP-Adresse fehlt.');
            }
            WPDeskBanStore::unban($ip, $version);
            WPDeskSecurity::audit_control_action('unban_ip', ['ip' => $ip, 'version' => $version]);
            WPDeskAjaxGuard::send_success('IP-Adresse ' . esc_html(WPDeskSecurity::normalize_ip($ip)) . ' erfolgreich entbannt.');
        });
    }

    public function ajax_update_superkey(): void
    {
        WPDeskAjaxGuard::run('manage_options', function (): void {
            $current_superkey = isset($_POST['current_superkey']) ? (string) $_POST['current_superkey'] : '';
            $new_superkey = isset($_POST['new_superkey']) ? (string) $_POST['new_superkey'] : '';

            $user_id = get_current_user_id();
            $superkey_hash = get_user_meta($user_id, 'mcp_superkey_hash', true);
            if (empty($superkey_hash)) {
                $global_hash = get_option('mcp_superkey_hash', '');
                if (!empty($global_hash)) {
                    $superkey_hash = $global_hash;
                }
            }

            if (!empty($superkey_hash)) {
                if ($current_superkey === '' || !password_verify($current_superkey, $superkey_hash)) {
                    sleep(1);
                    throw new ValidationException('Der aktuelle Superkey ist ungueltig.');
                }
            }

            if (strlen($new_superkey) < 12) {
                throw new ValidationException('Der neue Superkey muss mindestens 12 Zeichen lang sein.');
            }

            $new_hash = password_hash($new_superkey, PASSWORD_DEFAULT);
            update_user_meta($user_id, 'mcp_superkey_hash', $new_hash);

            WPDeskAjaxGuard::send_success('Superkey erfolgreich aktualisiert.');
        });
    }

    public function ajax_ban_ip(): void
    {
        WPDeskAjaxGuard::run('manage_options', function (): void {
            $ip = isset($_POST['ip']) ? (string) $_POST['ip'] : '';
            $reason = isset($_POST['reason'])
                ? sanitize_text_field((string) $_POST['reason'])
                : 'Permanenter Bann ueber Live Attack Stream Widget';

            if ($ip === '') {
                throw new ValidationException('IP-Adresse fehlt.');
            }

            $normalized = WPDeskSecurity::normalize_ip($ip);
            WPDeskBanStore::ban($normalized, $reason);
            WPDeskSecurity::audit_control_action('ban_ip', ['ip' => $normalized, 'reason' => $reason]);
            WPDeskAjaxGuard::send_success('IP ' . esc_html($normalized) . ' wurde dauerhaft auf Firewall-Ebene gesperrt.');
        });
    }

    public function ajax_get_task_manager_stats(): void
    {
        WPDeskAjaxGuard::run('manage_options', function (): void {
            global $wpdb;

            $cron_array = _get_cron_array();
            $crons = [];
            if ($cron_array) {
                foreach ($cron_array as $timestamp => $hooks) {
                    foreach ($hooks as $hook => $details) {
                        foreach ($details as $key => $data) {
                            $crons[] = [
                                'hook'           => $hook,
                                'timestamp'      => $timestamp,
                                'time_formatted' => date('Y-m-d H:i:s', $timestamp),
                                'schedule'       => $data['schedule'] ?? 'one-time',
                                'interval'       => $data['interval'] ?? 0,
                            ];
                        }
                    }
                }
            }
            usort($crons, static fn($a, $b) => $a['timestamp'] <=> $b['timestamp']);

            // Bounded transient listing — never dump the entire options table.
            $transient_limit = 100;
            $transients = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT option_name FROM {$wpdb->options}
                     WHERE (option_name LIKE %s OR option_name LIKE %s)
                       AND option_name NOT LIKE %s
                       AND option_name NOT LIKE %s
                     LIMIT %d",
                    $wpdb->esc_like('_transient_') . '%',
                    $wpdb->esc_like('_site_transient_') . '%',
                    '%_timeout_%',
                    $wpdb->esc_like('_transient_timeout_') . '%',
                    $transient_limit
                ),
                ARRAY_A
            );
            $active_transients = [];
            if (is_array($transients)) {
                foreach ($transients as $t) {
                    if (str_contains($t['option_name'], '_timeout_')) {
                        continue;
                    }
                    $name = str_replace(['_transient_', '_site_transient_'], '', $t['option_name']);
                    $active_transients[] = [
                        'name'        => $name,
                        'option_name' => $t['option_name'],
                    ];
                }
            }

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
                        'pid'     => (int) $pid,
                        'worker'  => sanitize_text_field($data['action']) . ' AJAX Worker',
                        'runtime' => $runtime,
                        'memory'  => sanitize_text_field($data['memory'] ?? '---'),
                        'status'  => 'running',
                    ];
                }
            }
            if (empty($workers)) {
                $workers[] = [
                    'pid'     => getmypid(),
                    'worker'  => 'vgt_get_task_manager_stats AJAX Worker',
                    'runtime' => '0.01s',
                    'memory'  => size_format(memory_get_usage(true)),
                    'status'  => 'running',
                ];
            }

            WPDeskAjaxGuard::send_success([
                'crons'      => $crons,
                'transients' => $active_transients,
                'workers'    => $workers,
            ]);
        });
    }

    public function ajax_unschedule_cron(): void
    {
        WPDeskAjaxGuard::run('manage_options', function (): void {
            WPDeskSecurity::require_operational_control('unschedule_cron');
            $hook = isset($_POST['hook']) ? sanitize_key((string) $_POST['hook']) : '';
            $timestamp = isset($_POST['timestamp']) ? (int) $_POST['timestamp'] : 0;
            if ($hook === '' || !$timestamp) {
                throw new ValidationException('Hook oder Timestamp fehlt.');
            }

            wp_unschedule_event($timestamp, $hook);
            WPDeskSecurity::audit_control_action('unschedule_cron', ['hook' => $hook, 'timestamp' => $timestamp]);
            WPDeskAjaxGuard::send_success('Cron-Hook ' . esc_html($hook) . ' wurde erfolgreich beendet.');
        });
    }

    public function ajax_kill_transient(): void
    {
        WPDeskAjaxGuard::run('manage_options', function (): void {
            WPDeskSecurity::require_operational_control('kill_transient');
            $name = isset($_POST['name']) ? sanitize_key((string) $_POST['name']) : '';
            if ($name === '') {
                throw new ValidationException('Name des Transients fehlt.');
            }

            delete_transient($name);
            delete_site_transient($name);
            WPDeskSecurity::audit_control_action('kill_transient', ['name' => $name]);
            WPDeskAjaxGuard::send_success('Transient ' . esc_html($name) . ' erfolgreich geloescht.');
        });
    }

    public function ajax_optimize_database(): void
    {
        WPDeskAjaxGuard::run('manage_options', function (): void {
            WPDeskSecurity::require_operational_control('optimize_database');

            global $wpdb;
            $optimized = 0;
            foreach (WPDeskSecurity::optimizable_table_suffixes() as $suffix) {
                $table = $wpdb->prefix . $suffix;
                if (!WPDeskSecurity::table_exists($table)) {
                    continue;
                }
                $safe_table = WPDeskSecurity::quote_identifier($table);
                $wpdb->query("OPTIMIZE TABLE {$safe_table}");
                $optimized++;
            }

            $wpdb->query(
                "DELETE pm FROM {$wpdb->postmeta} pm LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE p.ID IS NULL"
            );
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value < %d",
                $wpdb->esc_like('_transient_timeout_') . '%',
                time()
            ));
            WPDeskSecurity::audit_control_action('optimize_database', ['tables' => $optimized]);

            WPDeskAjaxGuard::send_success('Datenbank erfolgreich optimiert und bereinigt.');
        });
    }
}
