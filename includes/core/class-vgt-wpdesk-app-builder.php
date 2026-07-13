<?php
declare(strict_types=1);

namespace VisionGaia\WPDesk;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * SERVICE: WPDeskAppBuilder
 * STATUS: 💠 DIAMANT VGT SUPREME
 * Analysiert WordPress-Menüstrukturen und übersetzt sie in desktopkompatible Workspace-Apps.
 */
final class WPDeskAppBuilder
{
    /**
     * Resolve a $menu / $submenu slug into a safe absolute admin URL.
     * Handles "edit.php?post_type=page" without mangling the query string.
     */
    public static function resolve_admin_menu_url(string $slug): string
    {
        $slug = ltrim(trim($slug), '/');
        if ($slug === '') {
            return admin_url();
        }
        if (preg_match('#^https?://#i', $slug)) {
            return $slug;
        }

        // "edit.php?post_type=page" / "upload.php?page=..." etc.
        if (str_contains($slug, '?')) {
            [$path, $query] = explode('?', $slug, 2);
            $path = $path !== '' ? $path : 'admin.php';
            $url = admin_url($path);
            $params = [];
            parse_str($query, $params);
            if (!empty($params)) {
                $url = add_query_arg($params, $url);
            }
            return $url;
        }

        if (str_contains($slug, '.php')) {
            return admin_url($slug);
        }

        return admin_url('admin.php?page=' . $slug);
    }

    /**
     * Erstellt die Liste registrierter Desktop-Apps.
     */
    public static function build(array $user_settings): array
    {
        global $menu, $submenu;
        if (empty($menu)) {
            return [];
        }

        $parsed_apps = [];
        $exclusions  = ['vgt-wp-desk', 'separator', 'wp-logo'];

        foreach ($menu as $item) {
            if (empty($item[0]) || empty($item[2])) {
                continue;
            }

            $title = wp_strip_all_tags($item[0]);
            $slug  = (string) $item[2];

            $should_exclude = false;
            foreach ($exclusions as $ex) {
                if (stripos($slug, $ex) !== false) {
                    $should_exclude = true;
                    break;
                }
            }
            if (defined('VIS_VERSION') && (stripos($slug, 'dattrack') !== false)) {
                $should_exclude = true;
            }
            if ($should_exclude) {
                continue;
            }

            if (preg_match('/^https?:\/\//', $slug)) {
                continue;
            }

            $url = self::resolve_admin_menu_url($slug);
            // Never expose bare front/home as a portal target (XFO DENY stacks + blank iframe).
            // If URL is not an admin portal and cannot be forced, skip the app entirely
            // (do NOT reassign the same rejected resolve_admin_menu_url result).
            if (class_exists(WPDeskFramePolicy::class, false)) {
                $admin_base = function_exists('admin_url') ? admin_url() : '';
                if ($admin_base !== '' && !WPDeskFramePolicy::is_admin_portal_url($url, $admin_base)) {
                    $forced = WPDeskFramePolicy::force_admin_portal_url($slug, $admin_base);
                    if ($forced === '') {
                        // Last resort: known list screens from mangled slug heuristics.
                        $forced = WPDeskFramePolicy::force_admin_portal_url(
                            self::guess_admin_file_from_slug($slug),
                            $admin_base
                        );
                    }
                    if ($forced !== '' && WPDeskFramePolicy::is_admin_portal_url($forced, $admin_base)) {
                        $url = $forced;
                    } else {
                        continue; // omit non-portal app from desk registry
                    }
                }
            }

            $icon_type = 'dashicons';
            $icon_val  = 'dashicons-admin-generic';

            if (!empty($item[6])) {
                $raw_icon = $item[6];
                if (str_starts_with($raw_icon, 'dashicons-')) {
                    $icon_val  = $raw_icon;
                } elseif (str_starts_with($raw_icon, 'data:image/svg+xml') || str_starts_with($raw_icon, 'data:image/png')) {
                    $icon_type = 'svg';
                    $icon_val  = $raw_icon;
                } elseif (filter_var($raw_icon, FILTER_VALIDATE_URL)) {
                    $icon_type = 'url';
                    $icon_val  = $raw_icon;
                }
            }

            $color_presets = [
                'from-indigo-500 to-indigo-600',
                'from-emerald-500 to-emerald-600',
                'from-cyan-500 to-cyan-600',
                'from-amber-500 to-amber-600',
                'from-purple-500 to-purple-600',
                'from-rose-500 to-rose-600',
                'from-pink-500 to-pink-600',
                'from-blue-500 to-blue-600'
            ];
            $preset_index = abs(crc32($slug)) % count($color_presets);
            $color        = $color_presets[$preset_index];
            $app_id       = sanitize_key($slug);
            
            // Submenu-Erfassung für das Portal-Pop-Up
            $submenus_data = [];
            if (!empty($submenu[$slug])) {
                foreach ($submenu[$slug] as $sub_item) {
                    if (empty($sub_item[0]) || empty($sub_item[2])) {
                        continue;
                    }
                    if (!empty($sub_item[1]) && !current_user_can($sub_item[1])) {
                        continue;
                    }

                    $sub_title = wp_strip_all_tags($sub_item[0]);

                    $sub_slug = (string) $sub_item[2];
                    if (preg_match('/^https?:\/\//', $sub_slug)) {
                        continue;
                    }
                    if (str_contains($sub_slug, '.php') || str_contains($sub_slug, '?')) {
                        $sub_url = self::resolve_admin_menu_url($sub_slug);
                    } elseif (str_contains($slug, '.php')) {
                        // Parent is file-based (e.g. edit.php?post_type=page); child is page slug.
                        $base = self::resolve_admin_menu_url($slug);
                        $sub_url = add_query_arg('page', $sub_slug, $base);
                    } else {
                        $sub_url = admin_url('admin.php?page=' . $sub_slug);
                    }

                    $sub_id = sanitize_key($slug . '_' . $sub_item[2]);
                    $submenus_data[] = [
                        'id'    => $sub_id,
                        'title' => $sub_title,
                        'url'   => $sub_url
                    ];
                }
            }

            $classic_overrides = [];
            if (!empty($user_settings['classic_apps']) && is_array($user_settings['classic_apps'])) {
                $classic_overrides = $user_settings['classic_apps'];
            }
            $mode = WPDeskIframePolicy::classify((string) $url, $app_id, $classic_overrides);

            $parsed_apps[$app_id] = [
                'title'        => $title,
                'url'          => $url,
                'icon_type'    => $icon_type,
                'icon_val'     => $icon_val,
                'color'        => $color,
                'submenus'     => $submenus_data,
                'classic_mode' => $mode === WPDeskIframePolicy::MODE_CLASSIC_REQUIRED,
                'open_mode'    => $mode,
            ];
        }

        if (!empty($user_settings['layout_style']) && $user_settings['layout_style'] === 'windows' && isset($parsed_apps['index_php'])) {
            $parsed_apps['index_php']['title'] = 'Dieser PC';
            $parsed_apps['index_php']['icon_type'] = 'dashicons';
            $parsed_apps['index_php']['icon_val'] = 'dashicons-desktop';
            $parsed_apps['index_php']['color'] = 'vgt-color-gradient-settings';
        }

        $parsed_apps['task-manager'] = [
            'title'     => 'Task-Manager',
            'url'       => admin_url('admin.php?page=vgt-task-manager'),
            'icon_type' => 'dashicons',
            'icon_val'  => 'dashicons-performance',
            'color'     => 'from-rose-500 to-red-600',
            'submenus'  => []
        ];

        return apply_filters('vgt_wpdesk_registered_apps', $parsed_apps);
    }

    /**
     * Pure heuristic: map a WP menu slug to a known admin PHP file when
     * force_admin_portal_url cannot rebuild from the slug alone.
     */
    public static function guess_admin_file_from_slug(string $slug): string
    {
        $slug = strtolower(trim($slug));
        if ($slug === '') {
            return '';
        }
        if (str_contains($slug, 'post_type=page') || preg_match('/page/', $slug) === 1 && str_contains($slug, 'edit')) {
            return 'edit.php?post_type=page';
        }
        if (str_contains($slug, 'plugin')) {
            return 'plugins.php';
        }
        if (str_contains($slug, 'theme')) {
            return 'themes.php';
        }
        if (str_contains($slug, 'upload') || str_contains($slug, 'media')) {
            return 'upload.php';
        }
        if (str_contains($slug, 'user')) {
            return 'users.php';
        }
        if (str_contains($slug, 'comment')) {
            return 'edit-comments.php';
        }
        if (preg_match('#([a-z0-9_\-]+\.php)#', $slug, $m) === 1) {
            return $m[1];
        }
        return '';
    }
}
