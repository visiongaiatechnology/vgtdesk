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
            $slug  = $item[2];

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

            if (preg_match('/^https?:\/\//', (string)$slug)) {
                continue;
            } elseif (stripos($slug, '.php') !== false) {
                $url = admin_url($slug);
            } else {
                $url = admin_url('admin.php?page=' . $slug);
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

                    if (preg_match('/^https?:\/\//', (string)$sub_item[2])) {
                        continue;
                    } elseif (strpos($sub_item[2], '.php') !== false) {
                        $sub_url = admin_url($sub_item[2]);
                    } else {
                        if (strpos($slug, '.php') !== false) {
                            $sub_url = admin_url($slug . '?page=' . $sub_item[2]);
                        } else {
                            $sub_url = admin_url('admin.php?page=' . $sub_item[2]);
                        }
                    }

                    $sub_id = sanitize_key($slug . '_' . $sub_item[2]);
                    $submenus_data[] = [
                        'id'    => $sub_id,
                        'title' => $sub_title,
                        'url'   => $sub_url
                    ];
                }
            }

            $parsed_apps[$app_id] = [
                'title'     => $title,
                'url'       => $url,
                'icon_type' => $icon_type,
                'icon_val'  => $icon_val,
                'color'     => $color,
                'submenus'  => $submenus_data
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
}
