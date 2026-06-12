<?php
/**
 * Template part: Start Menu
 * STATUS: 💠 DIAMANT VGT SUPREME
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}
?>
    <!-- STARTMENÜ / APP LAUNCHER -->
    <div id="vgt-start-menu" class="vgt-start-menu hidden glassmorphism">
        <div class="vgt-start-menu-header">
            <input type="text" id="vgt-start-search" placeholder="Apps durchsuchen..." class="vgt-input-text" oninput="VGTDeskEngine.filterStartMenu()">
        </div>
        <?php if ($user_settings['layout_style'] === 'windows'): ?>
            <!-- WINDOWS 10 STYLE START MENU (THREE-PANE) -->
            <div class="vgt-start-win10-layout">
                <!-- 1. Left Sidebar -->
                <div class="vgt-start-win10-sidebar">
                    <div class="vgt-start-win10-sidebar-top" onclick="VGTDeskEngine.openWindow('about')" style="cursor: pointer;">
                        <div class="vgt-start-win10-sidebar-icon" title="<?php echo esc_attr($current_user->display_name); ?>">👤</div>
                    </div>
                    <div class="vgt-start-win10-sidebar-bottom">
                        <div class="vgt-start-win10-sidebar-icon" onclick="VGTDeskEngine.openWindow('settings')" title="Command Center">⚙️</div>
                        <a href="<?php echo esc_url(wp_nonce_url(admin_url('index.php?vgt_action=disable_desk'), 'vgt_toggle_desktop')); ?>" class="vgt-start-win10-sidebar-icon power-icon" title="Bypass / Klassische Ansicht">❌</a>
                    </div>
                </div>

                <!-- 2. Center Column: All Apps List (Alphabetical) -->
                <div class="vgt-start-win10-all-apps" id="vgt-start-section-other">
                    <div class="vgt-start-win10-section-title">Alle Apps</div>
                    <div class="vgt-start-win10-list">
                        <?php 
                        $sorted_apps = $apps_data;
                        uasort($sorted_apps, function($a, $b) {
                            return strcasecmp($a['title'], $b['title']);
                        });
                        foreach ($sorted_apps as $key => $app): 
                        ?>
                            <div class="vgt-start-item win10-list-item" data-key="<?php echo esc_attr($key); ?>" data-title="<?php echo esc_attr($app['title']); ?>" onclick="VGTDeskEngine.handleStartItemClick('<?php echo esc_js($key); ?>')" oncontextmenu="VGTDeskEngine.showStartItemContextMenu(event, '<?php echo esc_js($key); ?>', 'all_apps')">
                                <div class="vgt-start-icon-tile win10-list-icon <?php echo esc_attr($app['color']); ?>">
                                    <?php if ($app['icon_type'] === 'dashicons'): ?>
                                        <span class="dashicons <?php echo esc_attr($app['icon_val']); ?> vgt-start-icon-dashicon"></span>
                                    <?php elseif ($app['icon_type'] === 'svg' || $app['icon_type'] === 'url'): ?>
                                        <img src="<?php echo $app['icon_type'] === 'svg' ? esc_attr($app['icon_val']) : esc_url($app['icon_val']); ?>" class="vgt-start-icon-img" alt="" />
                                    <?php endif; ?>
                                </div>
                                <span class="vgt-start-label win10-list-label"><?php echo esc_html($app['title']); ?></span>
                                <span class="vgt-start-pin-btn" onclick="event.stopPropagation(); event.preventDefault(); VGTDeskEngine.togglePinApp('<?php echo esc_js($key); ?>', <?php echo in_array($key, $pinned_keys, true) ? 'false' : 'true'; ?>)" title="<?php echo in_array($key, $pinned_keys, true) ? 'Von Start lösen' : 'An Start anheften'; ?>">
                                    <?php echo in_array($key, $pinned_keys, true) ? '📌' : '📍'; ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- 3. Right Column: Pinned Tiles Grid -->
                <div class="vgt-start-win10-tiles" id="vgt-start-section-pinned">
                    <div class="vgt-start-win10-section-title">Angeheftet</div>
                    <div class="vgt-start-win10-grid">
                        <?php 
                        $pinned_keys = $user_settings['pinned_apps'];
                        foreach ($apps_data as $key => $app): 
                            if (!in_array($key, $pinned_keys, true)) continue;
                        ?>
                            <div class="vgt-start-item win10-tile-item" data-key="<?php echo esc_attr($key); ?>" data-title="<?php echo esc_attr($app['title']); ?>" onclick="VGTDeskEngine.handleStartItemClick('<?php echo esc_js($key); ?>')" oncontextmenu="VGTDeskEngine.showStartItemContextMenu(event, '<?php echo esc_js($key); ?>', 'pinned')">
                                <div class="vgt-start-pin-btn" onclick="event.stopPropagation(); event.preventDefault(); VGTDeskEngine.togglePinApp('<?php echo esc_js($key); ?>', false)" title="Von Start lösen">📌</div>
                                <div class="vgt-start-icon-tile win10-tile-icon <?php echo esc_attr($app['color']); ?>">
                                    <?php if ($app['icon_type'] === 'dashicons'): ?>
                                        <span class="dashicons <?php echo esc_attr($app['icon_val']); ?> vgt-start-icon-dashicon"></span>
                                    <?php elseif ($app['icon_type'] === 'svg' || $app['icon_type'] === 'url'): ?>
                                        <img src="<?php echo $app['icon_type'] === 'svg' ? esc_attr($app['icon_val']) : esc_url($app['icon_val']); ?>" class="vgt-start-icon-img" alt="" />
                                    <?php endif; ?>
                                </div>
                                <span class="vgt-start-label win10-tile-label"><?php echo esc_html($app['title']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
        <?php else: ?>
            <div class="vgt-start-menu-body">
                <!-- Pinned Section -->
                <div class="vgt-start-section" id="vgt-start-section-pinned">
                    <div class="vgt-start-favorites-title">Angeheftet</div>
                    <div class="vgt-start-grid" id="vgt-start-grid-pinned">
                        <?php 
                        $pinned_keys = $user_settings['pinned_apps'];
                        foreach ($apps_data as $key => $app): 
                            if (!in_array($key, $pinned_keys, true)) continue;
                        ?>
                            <div class="vgt-start-item" data-key="<?php echo esc_attr($key); ?>" data-title="<?php echo esc_attr($app['title']); ?>" onclick="VGTDeskEngine.handleStartItemClick('<?php echo esc_js($key); ?>')" oncontextmenu="VGTDeskEngine.showStartItemContextMenu(event, '<?php echo esc_js($key); ?>', 'pinned')">
                                <div class="vgt-start-pin-btn" onclick="event.stopPropagation(); event.preventDefault(); VGTDeskEngine.togglePinApp('<?php echo esc_js($key); ?>', false)" title="Von Start lösen">📌</div>
                                <div class="vgt-start-icon-tile <?php echo esc_attr($app['color']); ?>">
                                    <?php if ($app['icon_type'] === 'dashicons'): ?>
                                        <span class="dashicons <?php echo esc_attr($app['icon_val']); ?> vgt-start-icon-dashicon"></span>
                                    <?php elseif ($app['icon_type'] === 'svg' || $app['icon_type'] === 'url'): ?>
                                        <img src="<?php echo $app['icon_type'] === 'svg' ? esc_attr($app['icon_val']) : esc_url($app['icon_val']); ?>" class="vgt-start-icon-img" alt="" />
                                    <?php endif; ?>
                                </div>
                                <span class="vgt-start-label"><?php echo esc_html($app['title']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Other Apps Section -->
                <div class="vgt-start-section" id="vgt-start-section-other" style="margin-top: 20px;">
                    <div class="vgt-start-favorites-title">Alle Apps & Erweiterungen</div>
                    <div class="vgt-start-grid" id="vgt-start-grid-other">
                        <?php 
                        foreach ($apps_data as $key => $app): 
                            if (in_array($key, $pinned_keys, true)) continue;
                        ?>
                            <div class="vgt-start-item" data-key="<?php echo esc_attr($key); ?>" data-title="<?php echo esc_attr($app['title']); ?>" onclick="VGTDeskEngine.handleStartItemClick('<?php echo esc_js($key); ?>')" oncontextmenu="VGTDeskEngine.showStartItemContextMenu(event, '<?php echo esc_js($key); ?>', 'all_apps')">
                                <div class="vgt-start-pin-btn" onclick="event.stopPropagation(); event.preventDefault(); VGTDeskEngine.togglePinApp('<?php echo esc_js($key); ?>', true)" title="An Start anheften">📍</div>
                                <div class="vgt-start-icon-tile <?php echo esc_attr($app['color']); ?>">
                                    <?php if ($app['icon_type'] === 'dashicons'): ?>
                                        <span class="dashicons <?php echo esc_attr($app['icon_val']); ?> vgt-start-icon-dashicon"></span>
                                    <?php elseif ($app['icon_type'] === 'svg' || $app['icon_type'] === 'url'): ?>
                                        <img src="<?php echo $app['icon_type'] === 'svg' ? esc_attr($app['icon_val']) : esc_url($app['icon_val']); ?>" class="vgt-start-icon-img" alt="" />
                                    <?php endif; ?>
                                </div>
                                <span class="vgt-start-label"><?php echo esc_html($app['title']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
