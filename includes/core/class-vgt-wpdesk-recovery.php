<?php
declare(strict_types=1);

namespace VisionGaia\WPDesk;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Recovery control plane — pure policy helpers for actions outside the desktop shell.
 */
final class WPDeskRecovery
{
    public const CAPABILITY = 'manage_options';
    public const NONCE_ACTION = 'vgt_recovery_action';
    public const NONCE_FIELD = 'vgt_recovery_nonce';

    public const ACTION_FORCE_CLASSIC = 'force_classic';
    public const ACTION_DISABLE_REDIRECT = 'disable_redirect';
    public const ACTION_DISABLE_DATTRACK = 'disable_dattrack';
    public const ACTION_EXPORT_DIAGNOSTICS = 'export_diagnostics';

    /** @return list<string> */
    public static function allowed_actions(): array
    {
        return [
            self::ACTION_FORCE_CLASSIC,
            self::ACTION_DISABLE_REDIRECT,
            self::ACTION_DISABLE_DATTRACK,
            self::ACTION_EXPORT_DIAGNOSTICS,
        ];
    }

    /**
     * Pure: whether a recovery action name is allowed.
     */
    public static function is_allowed_action(string $action): bool
    {
        $action = self::pure_sanitize_key($action);
        return in_array($action, self::allowed_actions(), true);
    }

    /**
     * Pure authorization decision for recovery mutations.
     *
     * @return array{ok:bool,code:string,message:string}
     */
    public static function authorize_action(
        string $action,
        bool $has_capability,
        bool $nonce_valid
    ): array {
        if (!$has_capability) {
            return [
                'ok'      => false,
                'code'    => 'capability',
                'message' => 'Insufficient privileges for recovery action.',
            ];
        }
        if (!$nonce_valid) {
            return [
                'ok'      => false,
                'code'    => 'invalid_nonce',
                'message' => 'CSRF token validation failed.',
            ];
        }
        if (!self::is_allowed_action($action)) {
            return [
                'ok'      => false,
                'code'    => 'invalid_action',
                'message' => 'Unknown recovery action.',
            ];
        }
        return [
            'ok'      => true,
            'code'    => 'authorized',
            'message' => 'Recovery action authorized.',
        ];
    }

    /**
     * Whether a request path/page is the recovery surface (outside desktop shell).
     */
    public static function is_recovery_page_slug(string $page): bool
    {
        $page = self::pure_sanitize_key($page);
        return $page === 'vgt-recovery-center'
            || ($page === 'vgt-security-center'); // recovery view may ride under security center
    }

    /**
     * Pure cookie options shape for force-classic bypass (no I/O).
     *
     * @return array{name:string,value:string,ttl_seconds:int}
     */
    public static function force_classic_cookie_spec(): array
    {
        return [
            'name'        => 'vgt_desk_bypass',
            'value'       => '1',
            'ttl_seconds' => 86400 * 30,
        ];
    }

    public static function pure_sanitize_key(string $key): string
    {
        if (function_exists('sanitize_key')) {
            return sanitize_key($key);
        }
        $key = strtolower($key);
        return (string) preg_replace('/[^a-z0-9_\-]/', '', $key);
    }
}
