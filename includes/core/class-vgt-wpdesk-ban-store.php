<?php
declare(strict_types=1);

namespace VisionGaia\WPDesk;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Unified CE + V7 ban access for the desk control plane.
 */
final class WPDeskBanStore
{
    public const VERSION_CE = 'Sentinel CE';
    public const VERSION_V7 = 'Sentinel V7';

    /**
     * Pure merge of ban rows from CE/V7 tables for tests and UI.
     *
     * @param list<array<string,mixed>> $ce_rows
     * @param list<array<string,mixed>> $v7_rows
     * @return list<array{id:int,ip:string,reason:string,banned_at:string,version:string}>
     */
    public static function merge_ban_rows(array $ce_rows, array $v7_rows, int $limit = 50): array
    {
        $bans = [];
        foreach ($ce_rows as $r) {
            $bans[] = self::normalize_row($r, self::VERSION_CE);
        }
        foreach ($v7_rows as $r) {
            $bans[] = self::normalize_row($r, self::VERSION_V7);
        }
        usort($bans, static function (array $a, array $b): int {
            return strcmp($b['banned_at'], $a['banned_at']);
        });
        if ($limit > 0) {
            $bans = array_slice($bans, 0, $limit);
        }
        return $bans;
    }

    /**
     * @param array<string,mixed> $r
     * @return array{id:int,ip:string,reason:string,banned_at:string,version:string}
     */
    public static function normalize_row(array $r, string $version): array
    {
        $ip = (string) ($r['ip'] ?? '');
        $reason = (string) ($r['reason'] ?? '');
        $banned_at = (string) ($r['banned_at'] ?? '');
        if (function_exists('sanitize_text_field')) {
            $ip = sanitize_text_field($ip);
            $reason = sanitize_text_field($reason);
            $banned_at = sanitize_text_field($banned_at);
        }
        return [
            'id'        => (int) ($r['id'] ?? 0),
            'ip'        => $ip,
            'reason'    => $reason,
            'banned_at' => $banned_at,
            'version'   => $version,
        ];
    }

    public static function ce_table(): string
    {
        global $wpdb;
        return $wpdb->prefix . 'vgts_apex_bans';
    }

    public static function v7_table(): string
    {
        global $wpdb;
        return $wpdb->prefix . 'vis_apex_bans';
    }

    public static function count_all(): int
    {
        global $wpdb;
        $total = 0;
        $ce = self::ce_table();
        if (WPDeskSecurity::table_exists($ce)) {
            $total += (int) $wpdb->get_var("SELECT COUNT(*) FROM {$ce}");
        }
        $v7 = self::v7_table();
        if (WPDeskSecurity::table_exists($v7)) {
            $total += (int) $wpdb->get_var("SELECT COUNT(*) FROM {$v7}");
        }
        return $total;
    }

    /**
     * @return list<array{id:int,ip:string,reason:string,banned_at:string,version:string}>
     */
    public static function list_recent(int $limit = 50): array
    {
        global $wpdb;
        $ce_rows = [];
        $v7_rows = [];
        $ce = self::ce_table();
        if (WPDeskSecurity::table_exists($ce)) {
            $rows = $wpdb->get_results(
                "SELECT id, ip, reason, banned_at FROM {$ce} ORDER BY banned_at DESC LIMIT " . (int) $limit,
                ARRAY_A
            );
            if (is_array($rows)) {
                $ce_rows = $rows;
            }
        }
        $v7 = self::v7_table();
        if (WPDeskSecurity::table_exists($v7)) {
            $rows = $wpdb->get_results(
                "SELECT id, ip, reason, banned_at FROM {$v7} ORDER BY banned_at DESC LIMIT " . (int) $limit,
                ARRAY_A
            );
            if (is_array($rows)) {
                $v7_rows = $rows;
            }
        }
        return self::merge_ban_rows($ce_rows, $v7_rows, $limit);
    }

    public static function ban(string $ip, string $reason = 'Permanent ban via WP-Desk'): void
    {
        global $wpdb;
        $ip = WPDeskSecurity::normalize_ip($ip);
        $reason = function_exists('sanitize_text_field') ? sanitize_text_field($reason) : $reason;
        $now = function_exists('current_time') ? current_time('mysql') : gmdate('Y-m-d H:i:s');
        $success = false;

        $ce = self::ce_table();
        if (WPDeskSecurity::table_exists($ce)) {
            $wpdb->replace($ce, [
                'ip'        => $ip,
                'reason'    => $reason,
                'banned_at' => $now,
            ]);
            $success = true;
        }

        $v7 = self::v7_table();
        if (WPDeskSecurity::table_exists($v7)) {
            $wpdb->replace($v7, [
                'ip'          => $ip,
                'reason'      => $reason,
                'banned_at'   => $now,
                'request_uri' => '/wp-admin/',
            ]);
            $success = true;
        }

        if (!$success) {
            throw new StorageException('Keine Sentinel-Bannliste gefunden.');
        }
    }

    public static function unban(string $ip, string $version = ''): void
    {
        global $wpdb;
        $ip = WPDeskSecurity::normalize_ip($ip);

        if ($version === self::VERSION_V7) {
            $table = self::v7_table();
            if (!WPDeskSecurity::table_exists($table)) {
                throw new StorageException('Datenbanktabelle fuer Sperren nicht gefunden.');
            }
            $wpdb->delete($table, ['ip' => $ip]);
            return;
        }

        if ($version === self::VERSION_CE || $version === '') {
            $table = self::ce_table();
            if ($version === self::VERSION_CE) {
                if (!WPDeskSecurity::table_exists($table)) {
                    throw new StorageException('Datenbanktabelle fuer Sperren nicht gefunden.');
                }
                $wpdb->delete($table, ['ip' => $ip]);
                return;
            }
        }

        // Empty version: remove from whichever tables exist.
        $removed = false;
        $ce = self::ce_table();
        if (WPDeskSecurity::table_exists($ce)) {
            $wpdb->delete($ce, ['ip' => $ip]);
            $removed = true;
        }
        $v7 = self::v7_table();
        if (WPDeskSecurity::table_exists($v7)) {
            $wpdb->delete($v7, ['ip' => $ip]);
            $removed = true;
        }
        if (!$removed) {
            throw new StorageException('Datenbanktabelle fuer Sperren nicht gefunden.');
        }
    }
}
