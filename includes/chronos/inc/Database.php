<?php
declare(strict_types=1);

namespace VGT\Chronos;

if (!defined('ABSPATH')) {
    exit('VGT PROTOCOL: Unauthorized Access Terminated.');
}

// STATUS: DIAMANT VGT SUPREME
// ARCHITEKTUR: Custom Table Management. Prepared Statements erzwungen.

final class Database
{
    public static function get_table_name(): string
    {
        global $wpdb;
        return $wpdb->prefix . Bootstrapper::TABLE_NAME;
    }

    public static function activate(): void
    {
        global $wpdb;
        $table_name = self::get_table_name();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            type varchar(50) NOT NULL DEFAULT 'fixed',
            end_datetime varchar(50) NULL,
            duration_seconds int(11) unsigned NULL,
            action_on_expire varchar(50) NOT NULL DEFAULT 'hide',
            redirect_url varchar(2048) NULL,
            design_settings json NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public static function save_countdown(array $data): int
    {
        global $wpdb;
        $table_name = self::get_table_name();

        $format = ['%s', '%s', '%s', '%d', '%s', '%s', '%s'];
        $insert_data = [
            'title'            => sanitize_text_field($data['title']),
            'type'             => in_array($data['type'], ['fixed', 'evergreen'], true) ? $data['type'] : 'fixed',
            'end_datetime'     => !empty($data['end_datetime']) ? sanitize_text_field($data['end_datetime']) : null,
            'duration_seconds' => isset($data['duration_seconds']) ? absint($data['duration_seconds']) : null,
            'action_on_expire' => in_array($data['action_on_expire'], ['hide', 'redirect'], true) ? $data['action_on_expire'] : 'hide',
            'redirect_url'     => esc_url_raw($data['redirect_url'] ?? ''),
            'design_settings'  => wp_json_encode($data['design_settings'] ?? [])
        ];

        if (!empty($data['id'])) {
            $wpdb->update($table_name, $insert_data, ['id' => absint($data['id'])], $format, ['%d']);
            return absint($data['id']);
        }

        $wpdb->insert($table_name, $insert_data, $format);
        return (int)$wpdb->insert_id;
    }

    public static function get_countdowns(): array
    {
        global $wpdb;
        $table_name = self::get_table_name();
        return $wpdb->get_results("SELECT * FROM {$table_name} ORDER BY created_at DESC", ARRAY_A) ?? [];
    }

    public static function get_countdown(int $id): ?array
    {
        global $wpdb;
        $table_name = self::get_table_name();
        $result = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $id), ARRAY_A);
        return $result ?: null;
    }

    public static function delete_countdown(int $id): bool
    {
        global $wpdb;
        $table_name = self::get_table_name();
        $result = $wpdb->delete($table_name, ['id' => $id], ['%d']);
        return $result !== false;
    }
}