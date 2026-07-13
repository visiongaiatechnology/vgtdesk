<?php
declare(strict_types=1);

namespace VisionGaia\WPDesk;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shared AJAX auth + typed-exception response envelope for the desk control plane.
 */
final class WPDeskAjaxGuard
{
    public const NONCE_ACTION = 'vgt_desktop_action';
    public const NONCE_FIELD = 'nonce';

    /**
     * Run $handler after nonce + capability checks. Maps exceptions to JSON responses.
     *
     * @param callable():void $handler
     */
    public static function run(string $capability, callable $handler, string $nonce_action = self::NONCE_ACTION): void
    {
        try {
            self::assert_authorized($capability, $nonce_action);
            $handler();
        } catch (ValidationException $e) {
            self::send_error($e->getMessage());
        } catch (SecurityException $e) {
            if (function_exists('error_log')) {
                error_log('[SEC] VGT WP-Desk — ' . $e->getMessage());
            }
            self::send_error('Request rejected for security reasons.');
        } catch (StorageException $e) {
            if (function_exists('error_log')) {
                error_log('[STORAGE] VGT WP-Desk — ' . $e->getMessage());
            }
            self::send_error($e->getMessage() !== '' ? $e->getMessage() : 'A persistent server storage error occurred.');
        } catch (\Throwable $e) {
            if (function_exists('error_log')) {
                error_log('[FATAL] VGT WP-Desk — ' . $e->getMessage());
            }
            self::send_error('Critical system fault.');
        }
    }

    public static function assert_authorized(string $capability, string $nonce_action = self::NONCE_ACTION): void
    {
        if (!self::verify_nonce($nonce_action)) {
            throw new SecurityException('CSRF Token validation failed.');
        }

        if ($capability === 'read') {
            $user_id = function_exists('get_current_user_id') ? (int) get_current_user_id() : 0;
            if (!$user_id || !self::user_can($capability)) {
                throw new SecurityException('Insufficient capabilities or unauthenticated session.');
            }
            return;
        }

        if (!self::user_can($capability)) {
            throw new SecurityException('Insufficient capabilities.');
        }
    }

    /**
     * Pure shape for opaque security errors (used by unit tests without WP).
     *
     * @return array{success:bool,data:string}
     */
    public static function opaque_security_error_payload(): array
    {
        return [
            'success' => false,
            'data'    => 'Request rejected for security reasons.',
        ];
    }

    public static function verify_nonce(string $nonce_action = self::NONCE_ACTION): bool
    {
        if (!function_exists('check_ajax_referer')) {
            return false;
        }
        return (bool) check_ajax_referer($nonce_action, self::NONCE_FIELD, false);
    }

    public static function user_can(string $capability): bool
    {
        if (!function_exists('current_user_can')) {
            return false;
        }
        return (bool) current_user_can($capability);
    }

    /** @param mixed $data */
    public static function send_success($data = null): void
    {
        if (function_exists('wp_send_json_success')) {
            wp_send_json_success($data);
        }
        throw new \RuntimeException('wp_send_json_success unavailable');
    }

    public static function send_error(string $message): void
    {
        if (function_exists('wp_send_json_error')) {
            wp_send_json_error($message);
        }
        throw new \RuntimeException('wp_send_json_error unavailable: ' . $message);
    }
}
