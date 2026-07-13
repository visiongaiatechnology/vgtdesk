<?php
declare(strict_types=1);

namespace VisionGaia\WPDesk;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Data-driven boot of optional integrated modules.
 * Replaces the open-ended require_once chain in the plugin constructor.
 */
final class WPDeskModuleRegistry
{
    /** @var array<string,bool> */
    private static array $booted = [];

    /**
     * Module map: key => relative path under VGT_WPDESK_PATH, option-gated or always-on.
     *
     * @return list<array{key:string,path:string,option_gated:bool,hook?:string,callback?:callable|null}>
     */
    public static function definitions(): array
    {
        return [
            [
                'key'          => 'sentinel_ce',
                'path'         => 'vision-integrity-sentinel.php',
                'option_gated' => true,
            ],
            [
                'key'          => 'iframe_transformer',
                'path'         => 'includes/class-iframe-transformer.php',
                'option_gated' => false,
            ],
            [
                'key'          => 'throne_guard',
                'path'         => 'includes/class-vgt-throne-guard.php',
                'option_gated' => true,
            ],
            [
                'key'          => 'security_center',
                'path'         => 'includes/dashboard/class-vgt-security-center.php',
                'option_gated' => false,
                'after_load'   => static function (): void {
                    // Class lives in VisionGaia\WPDesk (same namespace as the desk kernel).
                    if (class_exists(VGTSecurityCenter::class, false)) {
                        VGTSecurityCenter::get_instance();
                    } elseif (class_exists('\\VisionGaia\\WPDesk\\VGTSecurityCenter', false)) {
                        \VisionGaia\WPDesk\VGTSecurityCenter::get_instance();
                    }
                },
            ],
            [
                'key'          => 'loginpager',
                'path'         => 'includes/modules/loginpager/login-engine.php',
                'option_gated' => true,
            ],
            [
                'key'          => 'omega_vault',
                'path'         => 'includes/build-center/vault.php',
                'option_gated' => true,
            ],
            [
                'key'          => 'book_reader',
                'path'         => 'includes/book-reader/bookreader.php',
                'option_gated' => true,
            ],
            [
                'key'          => 'chronos',
                'path'         => 'includes/chronos/Chronosloader.php',
                'option_gated' => true,
            ],
            [
                'key'          => 'astra',
                'path'         => 'includes/modules/astra/ki.php',
                'option_gated' => true,
            ],
            [
                'key'          => 'dattrack',
                'path'         => 'includes/modules/dattrack/class-dattrack-engine.php',
                'option_gated' => true,
                'deferred'     => true,
                'guard'        => static function (): bool {
                    return !WPDeskSecurity::is_sentinel_v7_active()
                        && !class_exists('VisionGaia\\WPDesk\\VGT_Dattrack_Engine', false);
                },
                'after_load'   => static function (): void {
                    if (class_exists('\\VisionGaia\\WPDesk\\VGT_Dattrack_Engine', false)) {
                        \VisionGaia\WPDesk\VGT_Dattrack_Engine::boot();
                    }
                },
            ],
        ];
    }

    /**
     * @param callable(string):bool $is_enabled
     */
    public static function boot_all(callable $is_enabled): void
    {
        $base = defined('VGT_WPDESK_PATH') ? VGT_WPDESK_PATH : '';
        if ($base === '') {
            return;
        }

        foreach (self::definitions() as $mod) {
            $key = $mod['key'];
            if (!empty(self::$booted[$key])) {
                continue;
            }

            if (!empty($mod['option_gated']) && !$is_enabled($key)) {
                continue;
            }

            if (!empty($mod['deferred'])) {
                $path = $base . $mod['path'];
                $guard = $mod['guard'] ?? null;
                $after = $mod['after_load'] ?? null;
                if (function_exists('add_action')) {
                    add_action('plugins_loaded', static function () use ($key, $path, $guard, $after, $is_enabled): void {
                        if (!empty(self::$booted[$key])) {
                            return;
                        }
                        if (!$is_enabled($key)) {
                            return;
                        }
                        if (is_callable($guard) && !$guard()) {
                            return;
                        }
                        if (is_file($path)) {
                            require_once $path;
                            if (is_callable($after)) {
                                $after();
                            }
                            self::$booted[$key] = true;
                        }
                    }, 1);
                }
                continue;
            }

            $path = $base . $mod['path'];
            if (!is_file($path)) {
                continue;
            }

            require_once $path;
            if (!empty($mod['after_load']) && is_callable($mod['after_load'])) {
                ($mod['after_load'])();
            }
            self::$booted[$key] = true;
        }
    }

    /** @return array<string,bool> */
    public static function booted_keys(): array
    {
        return self::$booted;
    }
}
