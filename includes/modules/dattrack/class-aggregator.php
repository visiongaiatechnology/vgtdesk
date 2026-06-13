<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 3. BACKGROUND AGGREGATOR (Cron Engine)
 * PLATINUM -> DIAMOND UPGRADE: O(1) Keyset Pagination Memory Chunking.
 * STATUS: 💠 DIAMANT VGT SUPREME
 */
if (!class_exists('VGT_Aggregator_Desk')) {
final class VGT_Aggregator_Desk {

    public static function run_rollup(): void {
        if (get_option('vgt_dattrack_enabled') !== 'true') {
            return;
        }

        global $wpdb;
        $vault_table = $wpdb->prefix . 'vgt_dattrack_vault';
        $stats_table = $wpdb->prefix . 'vgt_dattrack_stats';

        // VGT SUPREME FIX: O(1) Keyset Pagination (Seek Method) statt O(N) OFFSET.
        $generator = function() use ($wpdb, $vault_table) {
            $last_id = 0; 
            $limit = 2000;
            while(true) {
                // Index-Scan über ID ist instantan. Kein Verwerfen von Rows mehr.
                $query = $wpdb->prepare(
                    "SELECT id, ip_hash, payload, iv, auth_tag, timestamp 
                     FROM {$vault_table} 
                     WHERE id > %d 
                     ORDER BY id ASC 
                     LIMIT %d",
                    $last_id,
                    $limit
                );
                
                $rows = $wpdb->get_results($query, ARRAY_A);
                if(empty($rows)) break;
                
                foreach($rows as $r) {
                    $last_id = (int)$r['id'];
                    yield $r;
                }
            }
        };

        $daily_data = [];

        foreach($generator() as $row) {
            $decrypted = VGT_Crypto_Desk::decrypt_payload($row['payload'], $row['iv'], $row['auth_tag'], $row['ip_hash']);
            if(!$decrypted) continue;

            $date = substr($row['timestamp'], 0, 10);
            $path = $decrypted['p'] ?? '/';
            $path = substr($path, 0, 150); 

            if(!isset($daily_data[$date])) {
                $daily_data[$date] = ['events' => 0, 'users' => [], 'paths' => []];
            }

            if (count($daily_data[$date]['paths']) > 200 && !isset($daily_data[$date]['paths'][$path])) {
                $path = '/[OVERFLOW_TRUNCATED]';
            }

            $daily_data[$date]['events']++;
            $daily_data[$date]['users'][$row['ip_hash']] = true;
            $daily_data[$date]['paths'][$path] = ($daily_data[$date]['paths'][$path] ?? 0) + 1;
        }

        foreach($daily_data as $date => $data) {
            $users_count = count($data['users']);
            arsort($data['paths']);
            $top_paths = array_slice($data['paths'], 0, 50, true); 
            $paths_json = wp_json_encode($top_paths);

            // VGT CORE FIX: MySQL 8.0+ Kompatibilität (VALUES(col) is deprecated). Explizite Variablen.
            $wpdb->query($wpdb->prepare(
                "INSERT INTO {$stats_table} (stat_date, events, unique_users, paths)
                VALUES (%s, %d, %d, %s)
                ON DUPLICATE KEY UPDATE events = %d, unique_users = %d, paths = %s",
                $date, $data['events'], $users_count, $paths_json,
                $data['events'], $users_count, $paths_json
            ));
        }

        // VGT SUPREME FIX: Auto-Purge optimiert. Blockiert nicht die Tabelle.
        $wpdb->query("DELETE FROM {$vault_table} WHERE timestamp < DATE_SUB(NOW(), INTERVAL 7 DAY) LIMIT 10000");
    }
}
}