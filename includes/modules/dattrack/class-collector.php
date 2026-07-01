<?php
/**
 * VGT Dattrack: Sovereign Analytics Collector Module
 * STATUS: DIAMANT VGT SUPREME
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('VGT_Collector_Desk')) {
final class VGT_Collector_Desk {

    private const MAX_PAYLOAD_BYTES = 8192;
    private const RATE_LIMIT_PER_MINUTE = 60;

    public static function intercept(): void {
        if (get_option('vgt_dattrack_enabled') !== 'true') {
            wp_send_json_error(['status' => 'dattrack_disabled'], 403);
        }

        $site_host = wp_parse_url(site_url(), PHP_URL_HOST);
        $origin = isset($_SERVER['HTTP_ORIGIN']) ? (string)$_SERVER['HTTP_ORIGIN'] : '';
        $referer = isset($_SERVER['HTTP_REFERER']) ? (string)$_SERVER['HTTP_REFERER'] : '';
        $origin_host = $origin !== '' ? wp_parse_url($origin, PHP_URL_HOST) : '';
        $referer_host = $referer !== '' ? wp_parse_url($referer, PHP_URL_HOST) : '';

        if ($origin_host !== '' && strcasecmp((string)$origin_host, (string)$site_host) !== 0) {
            wp_send_json_error(['status' => 'origin_mismatch'], 403);
        }
        if ($referer_host !== '' && strcasecmp((string)$referer_host, (string)$site_host) !== 0) {
            wp_send_json_error(['status' => 'referer_mismatch'], 403);
        }
        if ($origin_host === '' && $referer_host === '') {
            wp_send_json_error(['status' => 'invalid_source'], 403);
        }

        $allowed_origin = $origin !== '' ? $origin : site_url();
        header('Access-Control-Allow-Origin: ' . esc_url_raw($allowed_origin));
        header('Access-Control-Allow-Methods: POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        header('Access-Control-Allow-Credentials: true');

        $method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper((string)$_SERVER['REQUEST_METHOD']) : 'GET';
        if ($method === 'OPTIONS') {
            status_header(200);
            exit;
        }
        if ($method !== 'POST') {
            wp_send_json_error(['status' => 'method_not_allowed'], 405);
        }

        $content_length = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
        if ($content_length > self::MAX_PAYLOAD_BYTES) {
            wp_send_json_error(['status' => 'payload_too_large'], 413);
        }

        $raw_input = (string)file_get_contents('php://input');
        if ($raw_input === '' || strlen($raw_input) > self::MAX_PAYLOAD_BYTES) {
            wp_send_json_error(['status' => 'payload_too_large'], 413);
        }

        $client_ip = class_exists('VisionGaia\\WPDesk\\WPDeskSecurity')
            ? \VisionGaia\WPDesk\WPDeskSecurity::client_ip()
            : sanitize_text_field((string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'));
        $rate_transient = 'vgt_dt_rate_' . md5($client_ip);
        $req_count = get_transient($rate_transient);
        if ($req_count === false) {
            set_transient($rate_transient, 1, 60);
        } else {
            $req_count = (int)$req_count;
            if ($req_count >= self::RATE_LIMIT_PER_MINUTE) {
                wp_send_json_error(['status' => 'too_many_requests'], 429);
            }
            set_transient($rate_transient, $req_count + 1, 60);
        }

        $decoded_json = json_decode($raw_input, true);
        if (!is_array($decoded_json) || !isset($decoded_json['d'], $decoded_json['tkn'])) {
            wp_send_json_error(['status' => 'missing_parameters'], 400);
        }

        $token = is_string($decoded_json['tkn']) ? $decoded_json['tkn'] : '';
        $secret = VGT_Crypto_Desk::get_master_key();
        if (empty($secret)) {
            $secret = wp_salt('nonce');
        }

        $action = 'vgt_dt_pulse';
        $tick = (int)ceil(time() / (12 * HOUR_IN_SECONDS));
        $expected_current = hash_hmac('sha256', $action . '|' . $tick . '|' . home_url(), $secret);
        $expected_previous = hash_hmac('sha256', $action . '|' . ($tick - 1) . '|' . home_url(), $secret);
        if (!hash_equals($expected_current, $token) && !hash_equals($expected_previous, $token)) {
            wp_send_json_error(['status' => 'invalid_token'], 403);
        }

        $encoded_payload = is_string($decoded_json['d']) ? $decoded_json['d'] : '';
        $obfuscated_payload = base64_decode($encoded_payload, true);
        if ($obfuscated_payload === false || strlen($obfuscated_payload) > self::MAX_PAYLOAD_BYTES) {
            wp_send_json_error(['status' => 'invalid_encoding'], 400);
        }

        $event_data = json_decode($obfuscated_payload, true);
        if (!is_array($event_data) || !isset($event_data['ts'])) {
            wp_send_json_error(['status' => 'invalid_encoding_or_missing_timestamp'], 400);
        }

        $client_time = (int)$event_data['ts'];
        $server_time = time() * 1000;
        if (abs($server_time - $client_time) > 300000) {
            wp_send_json_error(['status' => 'temporal_anomaly_detected'], 403);
        }

        $master_key = VGT_Crypto_Desk::get_master_key();
        $salt = VGT_Crypto_Desk::get_dynamic_salt();
        if (empty($master_key) || empty($salt)) {
            wp_send_json_error(['status' => 'vault_offline'], 503);
        }

        $ip_hash = hash_hmac('sha256', $client_ip . $salt, $master_key);
        $crypto_packet = VGT_Crypto_Desk::encrypt_payload($event_data, $ip_hash);
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
                'timestamp' => current_time('mysql'),
            ],
            ['%s', '%s', '%s', '%s', '%s']
        );

        if ($inserted === false) {
            error_log('[VGT DATTRACK] Storage failure: ' . $wpdb->last_error);
            wp_send_json_error(['status' => 'storage_unavailable'], 503);
        }

        wp_send_json_success();
    }
}
}