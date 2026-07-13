<?php
declare(strict_types=1);

namespace VisionGaia\WPDesk;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Operational audit trail — pure entry validation + append/read helpers.
 */
final class WPDeskAudit
{
    public const OPTION_KEY = 'vgt_operational_audit_log';
    public const MAX_ENTRIES = 200;
    public const MAX_ACTION_LEN = 64;
    public const MAX_CONTEXT_KEYS = 20;
    public const MAX_CONTEXT_VALUE_LEN = 200;

    /**
     * Pure normalize of a control-plane mutation entry.
     *
     * @param array<string,mixed> $context
     * @return array{timestamp:string,user_id:int,action:string,ip:string,context:array<string,string>}
     */
    public static function normalize_entry(
        string $action,
        int $user_id,
        string $ip,
        array $context = [],
        ?string $timestamp = null
    ): array {
        $action = self::pure_key($action);
        if (strlen($action) > self::MAX_ACTION_LEN) {
            $action = substr($action, 0, self::MAX_ACTION_LEN);
        }
        $ip = trim($ip);
        if (strlen($ip) > 64) {
            $ip = substr($ip, 0, 64);
        }
        $ctx = [];
        $i = 0;
        foreach ($context as $k => $v) {
            if ($i >= self::MAX_CONTEXT_KEYS) {
                break;
            }
            $ck = self::pure_key((string) $k);
            if ($ck === '') {
                continue;
            }
            if (is_scalar($v)) {
                $cv = (string) $v;
            } else {
                $cv = '[structured]';
            }
            if (strlen($cv) > self::MAX_CONTEXT_VALUE_LEN) {
                $cv = substr($cv, 0, self::MAX_CONTEXT_VALUE_LEN);
            }
            $ctx[$ck] = $cv;
            $i++;
        }
        return [
            'timestamp' => $timestamp !== null && $timestamp !== '' ? $timestamp : gmdate('Y-m-d H:i:s'),
            'user_id'   => max(0, $user_id),
            'action'    => $action,
            'ip'        => $ip,
            'context'   => $ctx,
        ];
    }

    /**
     * Pure validation — rejects empty action / oversized payloads.
     *
     * @param array<string,mixed> $entry
     * @return array{ok:bool,code:string,entry?:array}
     */
    public static function validate_entry(array $entry): array
    {
        if (!isset($entry['action']) || !is_string($entry['action']) || trim($entry['action']) === '') {
            return ['ok' => false, 'code' => 'empty_action'];
        }
        if (strlen($entry['action']) > self::MAX_ACTION_LEN) {
            return ['ok' => false, 'code' => 'action_too_long'];
        }
        if (isset($entry['context']) && !is_array($entry['context'])) {
            return ['ok' => false, 'code' => 'bad_context'];
        }
        if (isset($entry['context']) && count($entry['context']) > self::MAX_CONTEXT_KEYS) {
            return ['ok' => false, 'code' => 'context_too_large'];
        }
        if (isset($entry['user_id']) && (!is_int($entry['user_id']) && !is_numeric($entry['user_id']))) {
            return ['ok' => false, 'code' => 'bad_user_id'];
        }
        $encoded = json_encode($entry);
        if ($encoded === false || strlen($encoded) > 8192) {
            return ['ok' => false, 'code' => 'payload_too_large'];
        }
        return ['ok' => true, 'code' => 'ok', 'entry' => $entry];
    }

    /**
     * Pure: append entry to log array with cap (no I/O).
     *
     * @param list<array<string,mixed>> $log
     * @param array<string,mixed> $entry
     * @return list<array<string,mixed>>
     */
    public static function append_to_log(array $log, array $entry, int $max = self::MAX_ENTRIES): array
    {
        $log[] = $entry;
        if (count($log) > $max) {
            $log = array_slice($log, -$max);
        }
        return array_values($log);
    }

    /**
     * Persist via option store (WordPress).
     *
     * @param array<string,mixed> $context
     */
    public static function record(string $action, array $context = []): void
    {
        $user_id = function_exists('get_current_user_id') ? (int) get_current_user_id() : 0;
        $ip = class_exists(WPDeskSecurity::class) ? WPDeskSecurity::client_ip() : '0.0.0.0';
        $ts = function_exists('current_time') ? (string) current_time('mysql') : gmdate('Y-m-d H:i:s');
        $entry = self::normalize_entry($action, $user_id, $ip, $context, $ts);
        $check = self::validate_entry($entry);
        if (!$check['ok']) {
            if (function_exists('error_log')) {
                error_log('[VGT OP-AUDIT] rejected entry: ' . $check['code']);
            }
            return;
        }
        $log = get_option(self::OPTION_KEY, []);
        if (!is_array($log)) {
            $log = [];
        }
        $log = self::append_to_log($log, $entry);
        update_option(self::OPTION_KEY, $log, false);
        if (function_exists('error_log')) {
            error_log('[VGT OP-AUDIT] ' . wp_json_encode($entry, JSON_UNESCAPED_SLASHES));
        }
    }

    /**
     * @return list<array<string,mixed>>
     */
    public static function read_recent(int $limit = 50): array
    {
        $log = get_option(self::OPTION_KEY, []);
        if (!is_array($log)) {
            return [];
        }
        $limit = max(1, min(200, $limit));
        return array_slice(array_values($log), -$limit);
    }

    /**
     * Pure authorize decision for soft-disable / control-plane toggles (injectable).
     *
     * @return array{ok:bool,code:string}
     */
    public static function authorize_control_mutation(bool $has_capability, bool $nonce_valid): array
    {
        if (!$has_capability) {
            return ['ok' => false, 'code' => 'capability'];
        }
        if (!$nonce_valid) {
            return ['ok' => false, 'code' => 'invalid_nonce'];
        }
        return ['ok' => true, 'code' => 'authorized'];
    }

    public static function pure_key(string $key): string
    {
        if (function_exists('sanitize_key')) {
            return sanitize_key($key);
        }
        $key = strtolower($key);
        return (string) preg_replace('/[^a-z0-9_\-]/', '', $key);
    }
}
