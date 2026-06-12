<?php
/**
 * VGT Dattrack: Sovereign Analytics Collector Module
 * STATUS: 💠 DIAMANT VGT SUPREME
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('VGT_Collector')) {
final class VGT_Collector {
    
    public static function intercept(): void {
        if (get_option('vgt_dattrack_enabled') !== 'true') {
            wp_send_json_error(['status' => 'dattrack_disabled'], 403);
        }

        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            status_header(200);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            wp_send_json_error(['status' => 'method_not_allowed'], 405);
        }
            
        $raw_input = file_get_contents('php://input');
        $decoded_json = json_decode($raw_input, true);
        
        if (!isset($decoded_json['d'])) {
            wp_send_json_error(['status' => 'malformed_payload'], 400);
        }

        $obfuscated_payload = base64_decode($decoded_json['d']);
        $event_data = json_decode($obfuscated_payload, true);

        // VGT SUPREME FIX: O(1) Replay Attack & DDoS Mitigation (Zeitfenster: 5 Minuten)
        if (!$event_data || !isset($event_data['ts'])) {
            wp_send_json_error(['status' => 'invalid_encoding_or_missing_timestamp'], 400);
        }

        $client_time = (int)$event_data['ts'];
        $server_time = time() * 1000;
        
        if (abs($server_time - $client_time) > 300000) {
            wp_send_json_error(['status' => 'temporal_anomaly_detected'], 403);
        }

        // VGT SUPREME UPGRADE: HMAC-SHA256 unter Nutzung des Master-Keys als Pepper
        // Dies verhindert Length-Extension-Attacks und sorgt für maximale kryptographische Isolation.
        $master_key = VGT_Crypto::get_master_key();
        $salt = VGT_Crypto::get_dynamic_salt();

        if (empty($master_key) || empty($salt)) {
            wp_send_json_error(['status' => 'vault_offline'], 503);
        }

        $raw_ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $raw_ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $raw_ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        }

        // HMAC bietet Schutz gegen Length-Extension-Attacks und bindet die IP an den Master-Key.
        // Die IP wird mit dem Master-Key verperlt, der dynamische Salt dient als Kontext-Entropie.
        $ip_hash = hash_hmac('sha256', trim($raw_ip) . $salt, $master_key); 

        $crypto_packet = VGT_Crypto::encrypt_payload($event_data, $ip_hash);

        if (empty($crypto_packet['payload'])) {
            wp_send_json_error(['status' => 'crypto_failure'], 500);
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'vgt_dattrack_vault';
        
        $inserted = $wpdb->insert(
            $table_name,
            [
                'ip_hash'   => $ip_hash,
                'payload'   => $crypto_packet['payload'],
                'iv'        => $crypto_packet['iv'],
                'auth_tag'  => $crypto_packet['tag'],
                'timestamp' => current_time('mysql')
            ],
            ['%s', '%s', '%s', '%s', '%s']
        );

        if ($inserted === false && !empty($wpdb->last_error)) {
            // Self-Healing-Mechanismus für die Vault-Tabelle
            $charset_collate = $wpdb->get_charset_collate();
            $wpdb->query("CREATE TABLE IF NOT EXISTS {$table_name} (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                ip_hash VARCHAR(64) NOT NULL,
                payload LONGTEXT NOT NULL,
                iv VARCHAR(64) NOT NULL,
                auth_tag VARCHAR(64) NOT NULL,
                timestamp DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
                PRIMARY KEY  (id),
                KEY time_index (timestamp)
            ) {$charset_collate}");

            $wpdb->insert($table_name, [
                'ip_hash'   => $ip_hash,
                'payload'   => $crypto_packet['payload'],
                'iv'        => $crypto_packet['iv'],
                'auth_tag'  => $crypto_packet['tag'],
                'timestamp' => current_time('mysql')
            ], ['%s', '%s', '%s', '%s', '%s']);
        }

        wp_send_json_success();
    }
}
}