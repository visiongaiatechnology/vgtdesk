<?php
/**
 * Template: VGT WP-Desk Shell Layout
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$pinned_keys = $user_settings['pinned_apps'] ?? [];
?>
<div class="vgt-desk-shell select-none vgt-layout-<?php echo esc_attr($user_settings['layout_style']); ?>" id="vgt-shell-root">

    <!-- Dynamischer Wallpaper-Hintergrund -->
    <div id="vgt-wallpaper" class="vgt-wallpaper-bg"></div>
    <div class="vgt-wallpaper-overlay"></div>

    <!-- Globaler Drag & Resize Overlay Schutz vor Iframe Capturing -->
    <div id="vgt-global-drag-overlay" style="position: fixed; inset: 0; z-index: 99999; display: none; background: transparent;"></div>

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
                        <div class="vgt-start-win10-sidebar-icon" onclick="VGTDeskEngine.openWindow('settings')" title="Einstellungen">⚙️</div>
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

    <!-- SPOTLIGHT SEARCH / COMMAND RUNNER -->
    <div id="vgt-spotlight" class="vgt-spotlight hidden glassmorphism">
        <div class="vgt-spotlight-input-wrap">
            <span class="vgt-spotlight-search-icon">🔍</span>
            <input type="text" id="vgt-spotlight-input" placeholder="Apps, Aktionen oder Befehle suchen..." autocomplete="off">
        </div>
        <div id="vgt-spotlight-results" class="vgt-spotlight-results"></div>
    </div>

    <!-- PREMIUM CONTROL CENTER -->
    <div id="vgt-control-center" class="vgt-control-center hidden glassmorphism">
        <div class="vgt-cc-header">
            <span class="vgt-cc-title">Kontrollzentrum</span>
            <span class="vgt-cc-status">Online</span>
        </div>
        <div class="vgt-cc-toggles">
            <div class="vgt-cc-toggle" onclick="VGTDeskEngine.toggleCCToggle('sound')">
                <div class="vgt-cc-toggle-icon" id="cc-toggle-sound">🔊</div>
                <div class="vgt-cc-toggle-label">Sound</div>
            </div>
            <div class="vgt-cc-toggle" onclick="VGTDeskEngine.toggleCCToggle('widgets')">
                <div class="vgt-cc-toggle-icon" id="cc-toggle-widgets">🧩</div>
                <div class="vgt-cc-toggle-label">Widgets</div>
            </div>
            <div class="vgt-cc-toggle" onclick="VGTDeskEngine.toggleCCToggle('icons')">
                <div class="vgt-cc-toggle-icon" id="cc-toggle-icons">🖥️</div>
                <div class="vgt-cc-toggle-label">Symbole</div>
            </div>
            <div class="vgt-cc-toggle" onclick="VGTDeskEngine.toggleCCToggle('blur')">
                <div class="vgt-cc-toggle-icon" id="cc-toggle-blur">✨</div>
                <div class="vgt-cc-toggle-label">Blur</div>
            </div>
        </div>
        
        <div class="vgt-cc-section">
            <span class="vgt-cc-section-title">Widget-Sichtbarkeit</span>
            <div class="vgt-cc-widget-toggles">
                <div class="vgt-cc-widget-toggle active" id="cc-widget-toggle-clock" onclick="VGTDeskEngine.toggleCCWidget('clock')">
                    <span class="vgt-cc-wt-icon">🕐</span>
                    <span class="vgt-cc-wt-label">Uhr</span>
                </div>
                <div class="vgt-cc-widget-toggle active" id="cc-widget-toggle-system" onclick="VGTDeskEngine.toggleCCWidget('system')">
                    <span class="vgt-cc-wt-icon">📊</span>
                    <span class="vgt-cc-wt-label">System</span>
                </div>
                <div class="vgt-cc-widget-toggle active" id="cc-widget-toggle-notes" onclick="VGTDeskEngine.toggleCCWidget('notes')">
                    <span class="vgt-cc-wt-icon">📝</span>
                    <span class="vgt-cc-wt-label">Notizen</span>
                </div>
                <div class="vgt-cc-widget-toggle active" id="cc-widget-toggle-sentinel" onclick="VGTDeskEngine.toggleCCWidget('sentinel')">
                    <span class="vgt-cc-wt-icon">🛡️</span>
                    <span class="vgt-cc-wt-label">Sentinel</span>
                </div>
            </div>
        </div>

        <div class="vgt-cc-section">
            <span class="vgt-cc-section-title">System-Latenz (Live)</span>
            <canvas id="vgt-cc-graph" width="230" height="70" class="vgt-cc-canvas"></canvas>
        </div>

        <div class="vgt-cc-section">
            <span class="vgt-cc-section-title">Schnell-Bypässe</span>
            <div class="vgt-cc-row-buttons">
                <button class="vgt-btn-secondary" style="font-size:10px; padding:6px 10px;" onclick="VGTDeskEngine.resetIconGrid()">Grid reset</button>
                <a href="<?php echo esc_url(wp_nonce_url(admin_url('index.php?vgt_action=disable_desk'), 'vgt_toggle_desktop')); ?>" class="vgt-btn-danger" style="font-size:10px; padding:6px 10px; line-height:1.2;">Bypass</a>
            </div>
        </div>
    </div>

    <!-- Globaler Snap-Layout Helfer für Fenster-Tiling -->
    <div id="vgt-global-snap-menu" class="vgt-snap-menu hidden glassmorphism">
        <div class="vgt-snap-option option-left" onclick="VGTDeskEngine.snapActiveWindow('left')">
            <div class="vgt-snap-preview"></div>
            <span>Links</span>
        </div>
        <div class="vgt-snap-option option-right" onclick="VGTDeskEngine.snapActiveWindow('right')">
            <div class="vgt-snap-preview"></div>
            <span>Rechts</span>
        </div>
        <div class="vgt-snap-option option-topleft" onclick="VGTDeskEngine.snapActiveWindow('topleft')">
            <div class="vgt-snap-preview"></div>
            <span>O. Links</span>
        </div>
        <div class="vgt-snap-option option-bottomleft" onclick="VGTDeskEngine.snapActiveWindow('bottomleft')">
            <div class="vgt-snap-preview"></div>
            <span>U. Links</span>
        </div>
    </div>

    <!-- MAIN INTERFACE OVERLAY -->
    <div class="vgt-interface-overlay">
        
        <!-- STATUS BAR (MENÜOBEN) -->
        <header class="vgt-topbar" id="top-bar">
            <div class="vgt-topbar-left">
                <div class="vgt-logo-group" onclick="VGTDeskEngine.toggleStartMenu(event)">
                    <span class="vgt-topbar-dot" id="vgt-topbar-dot"></span>
                    <span class="vgt-topbar-title" id="vgt-topbar-title">VGT</span>
                    <span class="vgt-topbar-subtitle">WP-Desk</span>
                </div>
                <span class="vgt-topbar-separator">|</span>
                <span class="vgt-topbar-branding">Powered by VisionGaiaTechnology</span>
                <span class="vgt-topbar-version-badge">V1.0 Beta v3</span>
                <span class="vgt-topbar-separator">|</span>
                <div class="vgt-topbar-nav">
                    <button class="vgt-topbar-btn" onclick="VGTDeskEngine.openWindow('welcome')">Home</button>
                    <button class="vgt-topbar-btn" onclick="VGTDeskEngine.openWindow('vgt-login-omega')">Login-Design</button>
                    <button class="vgt-topbar-btn" onclick="VGTDeskEngine.openWindow('settings')">Einstellungen</button>
                </div>
            </div>

            <!-- Uhrzeit und Status -->
            <div class="vgt-topbar-right">
                <div class="vgt-profile-badge" onclick="VGTDeskEngine.toggleControlCenter(event)">
                    <span class="vgt-profile-icon" id="vgt-accent-badge">⚙️</span>
                    <span class="vgt-profile-name"><?php echo esc_html($current_user->display_name); ?></span>
                </div>
                <div id="vgt-clock" class="vgt-clock-badge" onclick="VGTDeskEngine.toggleControlCenter(event)" style="cursor:pointer;">00:00</div>
            </div>
        </header>

        <!-- CANVAS ARBEITSBEREICH -->
        <main class="vgt-workspace" id="desktop-workspace">
            
            <!-- DESKTOP WIDGETS LAYER -->
            <div id="vgt-widgets-container" class="vgt-widgets-container">
                <!-- Clock Widget -->
                <div id="widget-clock" class="vgt-widget widget-clock glassmorphism absolute" style="z-index: 15; width: 260px;">
                    <div id="vgt-widget-clock-time">00:00</div>
                    <div id="vgt-widget-clock-date">Montag, 1. Januar</div>
                </div>
                <!-- System Status Widget -->
                <div id="widget-system" class="vgt-widget widget-system glassmorphism absolute" style="z-index: 15; width: 260px;">
                    <h4 class="vgt-widget-title">WordPress Status</h4>
                    <div class="vgt-widget-row"><span>WP Version:</span><strong><?php echo esc_html(get_bloginfo('version')); ?></strong></div>
                    <div class="vgt-widget-row"><span>PHP Version:</span><strong><?php echo esc_html(PHP_VERSION); ?></strong></div>
                    <div class="vgt-widget-row"><span>Aktiv:</span><strong><?php echo esc_html(wp_get_theme()->get('Name')); ?></strong></div>
                </div>
                <!-- Notes Widget -->
                <div id="widget-notes" class="vgt-widget widget-notes glassmorphism absolute" style="z-index: 15; width: 260px; height: 160px; display: flex; flex-direction: column;">
                    <h4 class="vgt-widget-title">Notizen</h4>
                    <textarea id="vgt-widget-notes-text" class="vgt-widget-textarea" placeholder="Schnelle Gedanken aufschreiben..."></textarea>
                </div>
                <!-- Sentinel Widget -->
                <div id="widget-sentinel" class="vgt-widget widget-sentinel glassmorphism absolute" style="z-index: 15; width: 260px; display: flex; flex-direction: column; gap: 8px;">
                    <h4 class="vgt-widget-title" style="color: #f43f5e; display: flex; align-items: center; justify-content: space-between; margin: 0 0 10px 0;">
                        <span>VGT Sentinel</span>
                        <span id="vgt-sentinel-status-dot" class="vgt-widget-dot" style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #ef4444; box-shadow: 0 0 8px #ef4444; transition: all 0.3s;"></span>
                    </h4>
                    <div class="vgt-widget-row">
                        <span>Status:</span>
                        <strong id="vgt-sentinel-status-text" style="transition: color 0.3s; color: #f43f5e;">Inaktiv</strong>
                    </div>
                    <div class="vgt-widget-row">
                        <span>Bans:</span>
                        <strong id="vgt-sentinel-bans-count">0</strong>
                    </div>
                    <button id="vgt-sentinel-toggle-btn" class="vgt-btn-primary" style="margin-top: 6px; width: 100%; padding: 6px 12px; font-size: 11px; border-radius: 8px; background: linear-gradient(135deg, #f43f5e, #e11d48); border: none; cursor: pointer; color: #fff; font-weight: 700; transition: all 0.2s; box-shadow: 0 4px 12px rgba(244, 63, 94, 0.2);">
                        Sentinel aktivieren
                    </button>
                </div>
            </div>

            <!-- FREI ANORDNBARE DESKTOP ICONS LAYER -->
            <div id="desktop-icons-area" class="vgt-icons-area">
                
                <!-- Festes System-Icon: Systemeinstellungen -->
                <div class="desktop-icon absolute vgt-icon-item" data-id="settings" onclick="VGTDeskEngine.handleIconClick(event, 'settings')">
                    <div class="vgt-icon-tile vgt-color-gradient-settings">
                        <span class="vgt-icon-emoji">⚙️</span>
                    </div>
                    <span class="vgt-icon-label">Einstellungen</span>
                </div>

                <!-- Dynamisch geladene Apps (Third-Party Plugins) -->
                <?php foreach ($apps_data as $key => $app): ?>
                    <div class="desktop-icon absolute vgt-icon-item" data-id="<?php echo esc_attr($key); ?>" onclick="VGTDeskEngine.handleIconClick(event, '<?php echo esc_js($key); ?>')">
                        <div class="vgt-icon-tile <?php echo esc_attr($app['color']); ?>">
                            
                            <!-- Dashicon-Ausgabe -->
                            <?php if ($app['icon_type'] === 'dashicons'): ?>
                                <span class="dashicons <?php echo esc_attr($app['icon_val']); ?> vgt-icon-dashicon"></span>
                            <?php elseif ($app['icon_type'] === 'svg' || $app['icon_type'] === 'url'): ?>
                                <img src="<?php echo $app['icon_type'] === 'svg' ? esc_attr($app['icon_val']) : esc_url($app['icon_val']); ?>" class="vgt-icon-img" alt="" />
                            <?php endif; ?>

                        </div>
                        <span class="vgt-icon-label"><?php echo esc_html($app['title']); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- FENSTERCONTAINER (WINDOWS LAYER) -->
            <div id="windows-container" class="vgt-windows-container">
                
                <!-- NATIVES WILLKOMMENS-FENSTER -->
                <div id="win-welcome" class="window absolute vgt-window" style="width: 640px; height: 580px; top: 12%; left: 22%; z-index: 100;" onclick="VGTDeskEngine.focusWindow('welcome')">
                    
                    <!-- 8 Resize Handles -->
                    <div class="resize-handle resize-handle-n" onmousedown="VGTDeskEngine.startResize(event, 'welcome', 'n')"></div>
                    <div class="resize-handle resize-handle-s" onmousedown="VGTDeskEngine.startResize(event, 'welcome', 's')"></div>
                    <div class="resize-handle resize-handle-e" onmousedown="VGTDeskEngine.startResize(event, 'welcome', 'e')"></div>
                    <div class="resize-handle resize-handle-w" onmousedown="VGTDeskEngine.startResize(event, 'welcome', 'w')"></div>
                    <div class="resize-handle resize-handle-nw" onmousedown="VGTDeskEngine.startResize(event, 'welcome', 'nw')"></div>
                    <div class="resize-handle resize-handle-ne" onmousedown="VGTDeskEngine.startResize(event, 'welcome', 'ne')"></div>
                    <div class="resize-handle resize-handle-sw" onmousedown="VGTDeskEngine.startResize(event, 'welcome', 'sw')"></div>
                    <div class="resize-handle resize-handle-se" onmousedown="VGTDeskEngine.startResize(event, 'welcome', 'se')"></div>

                    <!-- Titlebar -->
                    <div class="vgt-window-header cursor-move window-header">
                        <div class="vgt-window-dots">
                            <span class="vgt-window-dot dot-rose" onclick="VGTDeskEngine.closeWindow('welcome')"></span>
                            <span class="vgt-window-dot dot-amber" onclick="VGTDeskEngine.minimizeWindow('welcome')"></span>
                            <span class="vgt-window-dot dot-emerald" onclick="VGTDeskEngine.maximizeWindow('welcome')"></span>
                        </div>
                        <span class="vgt-window-title" id="welcome-title-accent"><?php echo esc_html__('Willkommen bei VGT WP-Desk — V1.0 Beta v3', 'vgtdesk'); ?></span>
                        <div class="vgt-window-spacer"></div>
                    </div>
                    <!-- Body Content -->
                    <div class="vgt-window-body">
                        <h2 class="vgt-body-h2"><?php echo esc_html__('Die "Zero-Dependency" Evolution 🚀', 'vgtdesk'); ?></h2>
                        <p class="vgt-body-p">
                            <?php echo esc_html__('Alle deine installierten Plugins wurden automatisch ausgelesen und als Desktop-Apps angelegt. Sie laden blitzschnell im ablenkungsfreien Iframe-Workspace, geschützt durch ein gehärtetes Security-Fundament.', 'vgtdesk'); ?>
                        </p>
                        <div class="vgt-welcome-grid">
                            <div class="vgt-welcome-card">
                                <h3 class="vgt-card-h3 card-accent-indigo" id="box-accent-1"><?php echo esc_html__('Zero-Dependency', 'vgtdesk'); ?></h3>
                                <p class="vgt-card-p"><?php echo esc_html__('100% nativer, optimierter Code ohne externe Laufzeit-Abhängigkeiten für maximale Ladegeschwindigkeit.', 'vgtdesk'); ?></p>
                            </div>
                            <div class="vgt-welcome-card">
                                <h3 class="vgt-card-h3 card-accent-emerald" id="box-accent-2"><?php echo esc_html__('Integrierte WAF', 'vgtdesk'); ?></h3>
                                <p class="vgt-card-p"><?php echo esc_html__('Aegis schützt das System in Echtzeit vor RCE-Exploits, CSRF-Angriffen und unberechtigtem Datenzugriff.', 'vgtdesk'); ?></p>
                            </div>
                            <div class="vgt-welcome-card">
                                <h3 class="vgt-card-h3 card-accent-cyan" id="box-accent-3" style="color: #22d3ee;"><?php echo esc_html__('Multi-Layouts', 'vgtdesk'); ?></h3>
                                <p class="vgt-card-p"><?php echo esc_html__('Wechsle flexibel zwischen macOS Cupertino, Windows Redmond und Linux Tux Designs mit automatischer Icon-Anordnung.', 'vgtdesk'); ?></p>
                            </div>
                            <div class="vgt-welcome-card">
                                <h3 class="vgt-card-h3 card-accent-amber" id="box-accent-4" style="color: #fbbf24;"><?php echo esc_html__('Zero-Trust Hardening', 'vgtdesk'); ?></h3>
                                <p class="vgt-card-p"><?php echo esc_html__('Throne Guard sichert den Admin-Bereich durch strikte Content-Security-Policy (CSP) Härtung und privilege-stripping.', 'vgtdesk'); ?></p>
                            </div>
                        </div>

                        <!-- SPENDEN UNTERSTÜTZUNG SEKTION -->
                        <div class="vgt-donation-section">
                            <h3 class="vgt-donation-header"><?php echo esc_html__('VGT WP-Desk unterstützen ❤️', 'vgtdesk'); ?></h3>
                            <p class="vgt-donation-desc"><?php echo esc_html__('Wenn dir das System gefällt und du uns unterstützen möchtest, freuen wir uns über eine kleine Spende:', 'vgtdesk'); ?></p>
                            
                            <div class="vgt-donation-grid">
                                <!-- PayPal Link -->
                                <a href="https://www.paypal.com/paypalme/dergoldenelotus" target="_blank" class="vgt-donation-card-item paypal-color">
                                    <span class="vgt-donation-icon">💙</span>
                                    <span class="vgt-donation-label"><?php echo esc_html__('PayPal', 'vgtdesk'); ?></span>
                                    <small class="vgt-donation-addr">dergoldenelotus</small>
                                </a>

                                <!-- Bitcoin Click to Copy -->
                                <div class="vgt-donation-card-item crypto-btn" onclick="VGTDeskEngine.copyToClipboard('bc1q3ue5gq822tddmkdrek79adlkm36fatat3lz0dm', this)">
                                    <span class="vgt-donation-icon">🪙</span>
                                    <span class="vgt-donation-label"><?php echo esc_html__('Bitcoin', 'vgtdesk'); ?></span>
                                    <small class="vgt-donation-addr"><?php echo esc_html__('Kopieren', 'vgtdesk'); ?></small>
                                </div>

                                <!-- Ethereum Click to Copy -->
                                <div class="vgt-donation-card-item crypto-btn" onclick="VGTDeskEngine.copyToClipboard('0xD37DEfb09e07bD775EaaE9ccDaFE3a5b2348Fe85', this)">
                                    <span class="vgt-donation-icon">💎</span>
                                    <span class="vgt-donation-label"><?php echo esc_html__('Ethereum', 'vgtdesk'); ?></span>
                                    <small class="vgt-donation-addr"><?php echo esc_html__('Kopieren', 'vgtdesk'); ?></small>
                                </div>

                                <!-- USDT Click to Copy -->
                                <div class="vgt-donation-card-item crypto-btn" onclick="VGTDeskEngine.copyToClipboard('0xD37DEfb09e07bD775EaaE9ccDaFE3a5b2348Fe85', this)">
                                    <span class="vgt-donation-icon">💵</span>
                                    <span class="vgt-donation-label"><?php echo esc_html__('USDT (ERC-20)', 'vgtdesk'); ?></span>
                                    <small class="vgt-donation-addr"><?php echo esc_html__('Kopieren', 'vgtdesk'); ?></small>
                                </div>
                            </div>
                        </div>

                        <!-- BRANDING FOOTER -->
                        <div class="vgt-popup-footer">
                            <span class="vgt-footer-branding"><?php echo esc_html__('Powered by VisionGaiaTechnology', 'vgtdesk'); ?></span>
                            <span class="vgt-footer-version"><?php echo esc_html__('V1.0 Beta v3', 'vgtdesk'); ?></span>
                        </div>
                    </div>
                </div>

                <!-- NATIVES ABOUT / PROFIL-FENSTER -->
                <div id="win-about" class="window hidden absolute vgt-window" style="width: 480px; height: 500px; top: 15%; left: 25%; z-index: 102;" onclick="VGTDeskEngine.focusWindow('about')">
                    
                    <!-- Resize Handles -->
                    <div class="resize-handle resize-handle-n" onmousedown="VGTDeskEngine.startResize(event, 'about', 'n')"></div>
                    <div class="resize-handle resize-handle-s" onmousedown="VGTDeskEngine.startResize(event, 'about', 's')"></div>
                    <div class="resize-handle resize-handle-e" onmousedown="VGTDeskEngine.startResize(event, 'about', 'e')"></div>
                    <div class="resize-handle resize-handle-w" onmousedown="VGTDeskEngine.startResize(event, 'about', 'w')"></div>
                    <div class="resize-handle resize-handle-nw" onmousedown="VGTDeskEngine.startResize(event, 'about', 'nw')"></div>
                    <div class="resize-handle resize-handle-ne" onmousedown="VGTDeskEngine.startResize(event, 'about', 'ne')"></div>
                    <div class="resize-handle resize-handle-sw" onmousedown="VGTDeskEngine.startResize(event, 'about', 'sw')"></div>
                    <div class="resize-handle resize-handle-se" onmousedown="VGTDeskEngine.startResize(event, 'about', 'se')"></div>

                    <!-- Titlebar -->
                    <div class="vgt-window-header cursor-move window-header">
                        <div class="vgt-window-dots">
                            <span class="vgt-window-dot dot-rose" onclick="VGTDeskEngine.closeWindow('about')"></span>
                            <span class="vgt-window-dot dot-amber" onclick="VGTDeskEngine.minimizeWindow('about')"></span>
                            <span class="vgt-window-dot dot-emerald" onclick="VGTDeskEngine.maximizeWindow('about')"></span>
                        </div>
                        <span class="vgt-window-title">Über VGT WP-Desk</span>
                        <div class="vgt-window-spacer"></div>
                    </div>
                    <!-- Body Content -->
                    <div class="vgt-window-body vgt-about-body">
                        <div class="vgt-about-profile-card">
                            <div class="vgt-about-avatar">
                                <?php echo get_avatar($current_user->ID, 80); ?>
                            </div>
                            <h2 class="vgt-about-name"><?php echo esc_html($current_user->display_name); ?></h2>
                            <span class="vgt-about-role"><?php echo esc_html(ucfirst(join(', ', $current_user->roles))); ?></span>
                        </div>
                        
                        <div class="vgt-about-info">
                            <div class="vgt-about-info-row">
                                <span>Build-Version:</span>
                                <strong class="vgt-build-badge" onclick="VGTDeskEngine.triggerEasterEgg()">V1.0.0-Beta v3 (Hardened)</strong>
                            </div>
                            <div class="vgt-about-info-row">
                                <span>Lizenz:</span>
                                <strong>Premium Lifetime</strong>
                            </div>
                        </div>

                        <div class="vgt-about-message">
                            <p>🌟 <strong>Herzlichen Dank für das Vertrauen!</strong></p>
                            <p>VGT WP-Desk transformiert Ihre WordPress-Verwaltung in eine sichere, modulare und performante Desktop-Umgebung. Wir schätzen Ihre Unterstützung und Partnerschaft.</p>
                        </div>

                        <!-- Canvas für Easteregg (Matrix Rain) -->
                        <div class="vgt-about-easteregg-container hidden" id="vgt-about-matrix-container">
                            <canvas id="vgt-matrix-canvas" width="430" height="150"></canvas>
                            <div class="vgt-matrix-overlay-text">VGT ENCLAVE SECURITY ACTIVE</div>
                        </div>

                        <div class="vgt-about-actions">
                            <button class="vgt-btn-primary" onclick="VGTDeskEngine.triggerEasterEgg()">Easteregg aktivieren 🚀</button>
                        </div>
                    </div>
                </div>

                <!-- NATIVES SYSTEMEINSTELLUNGEN-FENSTER -->
                <div id="win-settings" class="window hidden absolute vgt-window" style="width: 820px; height: 550px; top: 15%; left: 30%; z-index: 101;" onclick="VGTDeskEngine.focusWindow('settings')">
                    
                    <!-- Resize Handles -->
                    <div class="resize-handle resize-handle-n" onmousedown="VGTDeskEngine.startResize(event, 'settings', 'n')"></div>
                    <div class="resize-handle resize-handle-s" onmousedown="VGTDeskEngine.startResize(event, 'settings', 's')"></div>
                    <div class="resize-handle resize-handle-e" onmousedown="VGTDeskEngine.startResize(event, 'settings', 'e')"></div>
                    <div class="resize-handle resize-handle-w" onmousedown="VGTDeskEngine.startResize(event, 'settings', 'w')"></div>
                    <div class="resize-handle resize-handle-nw" onmousedown="VGTDeskEngine.startResize(event, 'settings', 'nw')"></div>
                    <div class="resize-handle resize-handle-ne" onmousedown="VGTDeskEngine.startResize(event, 'settings', 'ne')"></div>
                    <div class="resize-handle resize-handle-sw" onmousedown="VGTDeskEngine.startResize(event, 'settings', 'sw')"></div>
                    <div class="resize-handle resize-handle-se" onmousedown="VGTDeskEngine.startResize(event, 'settings', 'se')"></div>

                    <!-- Titlebar -->
                    <div class="vgt-window-header cursor-move window-header">
                        <div class="vgt-window-dots">
                            <span class="vgt-window-dot dot-rose" onclick="VGTDeskEngine.closeWindow('settings')"></span>
                            <span class="vgt-window-dot dot-amber" onclick="VGTDeskEngine.minimizeWindow('settings')"></span>
                            <span class="vgt-window-dot dot-emerald" onclick="VGTDeskEngine.maximizeWindow('settings')"></span>
                        </div>
                        <span class="vgt-window-title" id="settings-title-accent">VGT Command Center — V2.1 Supreme</span>
                        <div class="vgt-window-spacer"></div>
                    </div>
                    <!-- Body Content -->
                    <div class="vgt-window-body vgt-cc-container">
                        <!-- Left Navigation Sidebar -->
                        <div class="vgt-cc-sidebar">
                            <button class="vgt-cc-nav-item active" data-tab="status" onclick="VGTDeskEngine.switchCCTab('status')">
                                <span class="vgt-cc-nav-icon">📊</span>
                                <span class="vgt-cc-nav-text">Status & Diagnose</span>
                            </button>
                            <button class="vgt-cc-nav-item" data-tab="themes" onclick="VGTDeskEngine.switchCCTab('themes')">
                                <span class="vgt-cc-nav-icon">🎨</span>
                                <span class="vgt-cc-nav-text">Display & Themes</span>
                            </button>
                            <button class="vgt-cc-nav-item" data-tab="security" onclick="VGTDeskEngine.switchCCTab('security')">
                                <span class="vgt-cc-nav-icon">🛡️</span>
                                <span class="vgt-cc-nav-text">Sicherheits-Center</span>
                            </button>
                            <button class="vgt-cc-nav-item" data-tab="shortcuts" onclick="VGTDeskEngine.switchCCTab('shortcuts')">
                                <span class="vgt-cc-nav-icon">⌨️</span>
                                <span class="vgt-cc-nav-text">Shortcuts Mapper</span>
                            </button>
                            
                            <div class="vgt-cc-sidebar-footer">
                                <a href="<?php echo esc_url(wp_nonce_url(admin_url('index.php?vgt_action=disable_desk'), 'vgt_toggle_desktop')); ?>" class="vgt-cc-btn-classic" title="Bypass / Klassische Ansicht">❌ Classic Mode</a>
                            </div>
                        </div>
                        
                        <!-- Right Panel Content -->
                        <div class="vgt-cc-content">
                            <!-- Tab: Diagnostics -->
                            <div id="vgt-cc-tab-status" class="vgt-cc-tab-panel active">
                                <h3 class="vgt-cc-section-title">Echtzeit Systemdiagnose</h3>
                                <div class="vgt-cc-grid">
                                    <div class="vgt-cc-card">
                                        <div class="vgt-cc-card-header">
                                            <span class="vgt-cc-card-title">PHP CPU-Last</span>
                                            <span class="vgt-cc-card-value" id="vgt-diag-cpu-val">--%</span>
                                        </div>
                                        <div class="vgt-cc-meter-track">
                                            <div class="vgt-cc-meter-bar" id="vgt-diag-cpu-bar" style="width: 0%"></div>
                                        </div>
                                    </div>
                                    <div class="vgt-cc-card">
                                        <div class="vgt-cc-card-header">
                                            <span class="vgt-cc-card-title">PHP Arbeitsspeicher</span>
                                            <span class="vgt-cc-card-value" id="vgt-diag-ram-val">-- MB / -- MB</span>
                                        </div>
                                        <div class="vgt-cc-meter-track">
                                            <div class="vgt-cc-meter-bar" id="vgt-diag-ram-bar" style="width: 0%"></div>
                                        </div>
                                    </div>
                                    <div class="vgt-cc-card vgt-cc-span-2">
                                        <div class="vgt-cc-card-header">
                                            <span class="vgt-cc-card-title">VGT-Datenbankgröße</span>
                                            <span class="vgt-cc-card-value" id="vgt-diag-db-val">-- KB</span>
                                        </div>
                                        <p class="vgt-cc-card-desc">Speicherbedarf der Desktop-Metadaten, Sentinel Sperr- und Protokolltabellen.</p>
                                    </div>
                                </div>
                                
                                <div class="vgt-cc-section">
                                    <h4 class="vgt-cc-subtitle">System-Ereignisprotokoll</h4>
                                    <div class="vgt-cc-console" id="cc-logs">
                                        <!-- Logs are populated here -->
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Tab: Themes & personalization -->
                            <div id="vgt-cc-tab-themes" class="vgt-cc-tab-panel">
                                <h3 class="vgt-cc-section-title">Display & Themes</h3>
                                
                                <div class="vgt-cc-form-group">
                                    <label class="vgt-cc-label">Hintergrundbild wählen</label>
                                    <div class="vgt-wallpaper-grid">
                                        <?php $wallpapers_url = VGT_WPDESK_URL . 'wallpapers/'; ?>
                                        <div class="vgt-wallpaper-thumb" style="background-image: url('<?php echo esc_url($wallpapers_url . 'wall1.webp'); ?>');" onclick="VGTDeskEngine.changeWallpaper('<?php echo esc_js($wallpapers_url . 'wall1.webp'); ?>')"></div>
                                        <div class="vgt-wallpaper-thumb" style="background-image: url('<?php echo esc_url($wallpapers_url . 'wall2.webp'); ?>');" onclick="VGTDeskEngine.changeWallpaper('<?php echo esc_js($wallpapers_url . 'wall2.webp'); ?>')"></div>
                                        <div class="vgt-wallpaper-thumb" style="background-image: url('<?php echo esc_url($wallpapers_url . 'wall3.webp'); ?>');" onclick="VGTDeskEngine.changeWallpaper('<?php echo esc_js($wallpapers_url . 'wall3.webp'); ?>')"></div>
                                        <div class="vgt-wallpaper-thumb" style="background-image: url('<?php echo esc_url($wallpapers_url . 'wall4.webp'); ?>');" onclick="VGTDeskEngine.changeWallpaper('<?php echo esc_js($wallpapers_url . 'wall4.webp'); ?>')"></div>
                                    </div>
                                    
                                    <div class="vgt-form-input-line" style="margin-top: 10px;">
                                        <input type="text" id="vgt-custom-wall-url-cc" placeholder="Eigene Wallpaper Bild-URL eintragen (https://...)" class="vgt-input-text">
                                        <button onclick="VGTDeskEngine.applyCustomWallpaperCC()" class="vgt-btn-primary">Übernehmen</button>
                                    </div>
                                </div>
                                
                                <div class="vgt-cc-form-group">
                                    <label class="vgt-cc-label">Akzentfarbe festlegen</label>
                                    <div class="vgt-color-palette">
                                        <button onclick="VGTDeskEngine.changeAccentColor('indigo')" class="vgt-color-circle bg-indigo" title="Indigo"></button>
                                        <button onclick="VGTDeskEngine.changeAccentColor('emerald')" class="vgt-color-circle bg-emerald" title="Emerald"></button>
                                        <button onclick="VGTDeskEngine.changeAccentColor('cyan')" class="vgt-color-circle bg-cyan" title="Cyan"></button>
                                        <button onclick="VGTDeskEngine.changeAccentColor('amber')" class="vgt-color-circle bg-amber" title="Amber"></button>
                                        <button onclick="VGTDeskEngine.changeAccentColor('rose')" class="vgt-color-circle bg-rose" title="Rose"></button>
                                    </div>
                                </div>
                                
                                <div class="vgt-cc-form-group">
                                    <label class="vgt-cc-label">Schriftgröße (Skalierung)</label>
                                    <div class="vgt-font-size-slider-wrapper">
                                        <input type="range" id="vgt-font-size-slider" min="10" max="24" value="14" class="vgt-slider" oninput="VGTDeskEngine.changeFontSize(this.value)">
                                        <span id="vgt-font-size-label" class="vgt-font-size-badge">14px</span>
                                    </div>
                                </div>
                                
                                <div class="vgt-cc-form-group">
                                    <label class="vgt-cc-label">Layout-Design wählen</label>
                                    <div class="vgt-layout-grid">
                                        <div class="vgt-layout-thumb <?php echo $user_settings['layout_style'] === 'macos' ? 'active' : ''; ?>" data-layout="macos" onclick="VGTDeskEngine.changeLayoutStyle('macos')">
                                            <div class="vgt-layout-thumb-preview macos-preview">
                                                <div class="preview-bar-top"></div>
                                                <div class="preview-dock-bottom"></div>
                                            </div>
                                            <span class="vgt-layout-thumb-label">🍎 macOS</span>
                                        </div>
                                        <div class="vgt-layout-thumb <?php echo $user_settings['layout_style'] === 'windows' ? 'active' : ''; ?>" data-layout="windows" onclick="VGTDeskEngine.changeLayoutStyle('windows')">
                                            <div class="vgt-layout-thumb-preview windows-preview">
                                                <div class="preview-bar-bottom"></div>
                                            </div>
                                            <span class="vgt-layout-thumb-label">🪟 Windows</span>
                                        </div>
                                        <div class="vgt-layout-thumb <?php echo $user_settings['layout_style'] === 'linux' ? 'active' : ''; ?>" data-layout="linux" onclick="VGTDeskEngine.changeLayoutStyle('linux')">
                                            <div class="vgt-layout-thumb-preview linux-preview">
                                                <div class="preview-bar-top"></div>
                                                <div class="preview-dock-left"></div>
                                            </div>
                                            <span class="vgt-layout-thumb-label">🐧 Linux</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="vgt-cc-form-group">
                                    <label class="vgt-cc-label">Desktop-Optionen</label>
                                    <div class="vgt-cc-toggle-card">
                                        <div class="vgt-cc-toggle-info">
                                            <span class="vgt-cc-toggle-title">Weichzeichner (Glassmorphismus)</span>
                                            <span class="vgt-cc-toggle-desc">Blur-Effekt auf allen Panels anwenden.</span>
                                        </div>
                                        <input type="checkbox" checked class="vgt-toggle-switch" id="blur-toggle" onchange="VGTDeskEngine.toggleBlur()">
                                    </div>
                                    <div class="vgt-cc-toggle-card" style="margin-top: 8px;">
                                        <div class="vgt-cc-toggle-info">
                                            <span class="vgt-cc-toggle-title">Desktop Standard-Ansicht</span>
                                            <span class="vgt-cc-toggle-desc">Umleitung in den VGT-Desk bei Backend-Aufrufen.</span>
                                        </div>
                                        <input type="checkbox" class="vgt-toggle-switch" id="redirect-toggle" onchange="VGTDeskEngine.toggleAutoRedirect()">
                                    </div>
                                </div>
                                
                                <div class="vgt-cc-action-bar">
                                    <button onclick="VGTDeskEngine.resetIconGrid()" class="vgt-btn-secondary">Symbole zurücksetzen / Raster bereinigen</button>
                                </div>
                            </div>
                            
                            <!-- Tab: Security Center -->
                            <div id="vgt-cc-tab-security" class="vgt-cc-tab-panel">
                                <h3 class="vgt-cc-section-title">Enclave Sicherheits-Center</h3>
                                
                                <div class="vgt-cc-sec-health-card">
                                    <div class="vgt-cc-sec-health-shield">🛡️</div>
                                    <div class="vgt-cc-sec-health-details">
                                        <span class="vgt-cc-sec-health-status">VGT ENCLAVE HARDENING: AKTIV</span>
                                        <span class="vgt-cc-sec-health-desc">Das System läuft im <strong id="vgt-sec-mode-badge" style="color: var(--vgt-accent-color);">Master User Mode</strong>.</span>
                                    </div>
                                    <div class="vgt-cc-sec-badge" id="vgt-sec-health-score">100% SECURE</div>
                                </div>
                                
                                <div class="vgt-cc-grid">
                                    <div class="vgt-cc-card">
                                        <div class="vgt-cc-card-header">
                                            <span class="vgt-cc-card-title">Throne Guard</span>
                                            <span class="vgt-cc-status-dot active" id="vgt-status-tg-dot"></span>
                                        </div>
                                        <p class="vgt-cc-card-desc">Schützt toxische Privilegien hinter dem Master-User-Schutzwall.</p>
                                    </div>
                                    <div class="vgt-cc-card">
                                        <div class="vgt-cc-card-header">
                                            <span class="vgt-cc-card-title">Sentinel Firewall</span>
                                            <span class="vgt-cc-status-dot active" id="vgt-status-sentinel-dot"></span>
                                        </div>
                                        <p class="vgt-cc-card-desc">Zero-Trust Web Application Firewall gegen Exploits und Angriffe.</p>
                                    </div>
                                </div>
                                
                                <div class="vgt-cc-section">
                                    <h4 class="vgt-cc-subtitle">Throne Guard Superkey verwalten</h4>
                                    <div class="vgt-cc-form-card">
                                        <p class="vgt-cc-form-desc">Ändern oder initialisieren Sie den Superkey für RCE-Uploads und Kernelsperren.</p>
                                        <div id="vgt-superkey-form-wrapper">
                                            <div class="vgt-cc-input-row" id="vgt-row-current-superkey">
                                                <label class="vgt-cc-input-label">Aktueller Superkey</label>
                                                <input type="password" id="vgt-current-superkey" class="vgt-input-text" placeholder="Geben Sie den aktuellen Superkey ein...">
                                            </div>
                                            <div class="vgt-cc-input-row">
                                                <label class="vgt-cc-input-label">Neuer Superkey (min. 12 Zeichen)</label>
                                                <input type="password" id="vgt-new-superkey" class="vgt-input-text" placeholder="Neuen Superkey eingeben...">
                                            </div>
                                            <button onclick="VGTDeskEngine.updateSuperkey()" class="vgt-btn-primary" style="margin-top: 10px;">Superkey speichern</button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="vgt-cc-section">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                        <h4 class="vgt-cc-subtitle" style="margin: 0;">Sentinel IP-Bannliste</h4>
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=vgt-login-settings')); ?>" class="vgt-btn-secondary" style="font-size: 11px; padding: 4px 8px;">Login-Simulation Matrix</a>
                                    </div>
                                    <div class="vgt-cc-table-container">
                                        <table class="vgt-cc-table">
                                            <thead>
                                                <tr>
                                                    <th>IP-Adresse</th>
                                                    <th>Grund für Ausschluss</th>
                                                    <th>Zeitpunkt</th>
                                                    <th>Modul</th>
                                                    <th>Aktion</th>
                                                </tr>
                                            </thead>
                                            <tbody id="vgt-cc-ban-table-body">
                                                <tr>
                                                    <td colspan="5" style="text-align: center; color: #64748b;">Lade Bannliste...</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Tab: Shortcuts Mapper -->
                            <div id="vgt-cc-tab-shortcuts" class="vgt-cc-tab-panel">
                                <h3 class="vgt-cc-section-title">Keyboard Shortcuts Mapper</h3>
                                <p class="vgt-cc-section-desc">Klicken Sie in ein Eingabefeld und drücken Sie die gewünschte Tastenkombination, um globale Hotkeys festzulegen.</p>
                                
                                <div class="vgt-cc-shortcuts-list">
                                    <div class="vgt-cc-shortcut-row">
                                        <div class="vgt-cc-shortcut-info">
                                            <span class="vgt-cc-shortcut-name">Fenster wechseln</span>
                                            <span class="vgt-cc-shortcut-desc">Fokusiert das nächste geöffnete Fenster im Stack.</span>
                                        </div>
                                        <div class="vgt-cc-shortcut-input-wrap">
                                            <input type="text" id="vgt-shortcut-window_switch" readonly class="vgt-cc-shortcut-input" placeholder="Tastenkombination drücken...">
                                            <button onclick="VGTDeskEngine.startCaptureShortcut('window_switch')" class="vgt-btn-secondary vgt-btn-capture">Aufzeichnen</button>
                                        </div>
                                    </div>
                                    <div class="vgt-cc-shortcut-row">
                                        <div class="vgt-cc-shortcut-info">
                                            <span class="vgt-cc-shortcut-name">Desktop anzeigen</span>
                                            <span class="vgt-cc-shortcut-desc">Minimiert oder stellt alle Fenster wieder her.</span>
                                        </div>
                                        <div class="vgt-cc-shortcut-input-wrap">
                                            <input type="text" id="vgt-shortcut-show_desktop" readonly class="vgt-cc-shortcut-input" placeholder="Tastenkombination drücken...">
                                            <button onclick="VGTDeskEngine.startCaptureShortcut('show_desktop')" class="vgt-btn-secondary vgt-btn-capture">Aufzeichnen</button>
                                        </div>
                                    </div>
                                    <div class="vgt-cc-shortcut-row">
                                        <div class="vgt-cc-shortcut-info">
                                            <span class="vgt-cc-shortcut-name">Spotlight-Suche</span>
                                            <span class="vgt-cc-shortcut-desc">Öffnet den Spotlight Command-Runner.</span>
                                        </div>
                                        <div class="vgt-cc-shortcut-input-wrap">
                                            <input type="text" id="vgt-shortcut-spotlight" readonly class="vgt-cc-shortcut-input" placeholder="Tastenkombination drücken...">
                                            <button onclick="VGTDeskEngine.startCaptureShortcut('spotlight')" class="vgt-btn-secondary vgt-btn-capture">Aufzeichnen</button>
                                        </div>
                                    </div>
                                    <div class="vgt-cc-shortcut-row">
                                        <div class="vgt-cc-shortcut-info">
                                            <span class="vgt-cc-shortcut-name">Startmenü öffnen</span>
                                            <span class="vgt-cc-shortcut-desc">Öffnet das System-Startmenü.</span>
                                        </div>
                                        <div class="vgt-cc-shortcut-input-wrap">
                                            <input type="text" id="vgt-shortcut-start_menu" readonly class="vgt-cc-shortcut-input" placeholder="Tastenkombination drücken...">
                                            <button onclick="VGTDeskEngine.startCaptureShortcut('start_menu')" class="vgt-btn-secondary vgt-btn-capture">Aufzeichnen</button>
                                        </div>
                                    </div>
                                    <div class="vgt-cc-shortcut-row">
                                        <div class="vgt-cc-shortcut-info">
                                            <span class="vgt-cc-shortcut-name">Control Center</span>
                                            <span class="vgt-cc-shortcut-desc">Öffnet das Control-Center Schnellmenü.</span>
                                        </div>
                                        <div class="vgt-cc-shortcut-input-wrap">
                                            <input type="text" id="vgt-shortcut-control_center" readonly class="vgt-cc-shortcut-input" placeholder="Tastenkombination drücken...">
                                            <button onclick="VGTDeskEngine.startCaptureShortcut('control_center')" class="vgt-btn-secondary vgt-btn-capture">Aufzeichnen</button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="vgt-cc-action-bar">
                                    <button onclick="VGTDeskEngine.restoreDefaultShortcuts()" class="vgt-btn-secondary">Shortcuts zurücksetzen</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STATISCH REGISTRIERTE PLUGINS / IFRAME WINDOWS -->
                <?php foreach ($apps_data as $key => $app): ?>
                    <div id="win-<?php echo esc_attr($key); ?>" class="window hidden absolute vgt-window" style="width: 850px; height: 550px; top: 10%; left: 20%; z-index: 50;" onclick="VGTDeskEngine.focusWindow('<?php echo esc_attr($key); ?>')">
                        
                        <!-- 8 Resize Handles -->
                        <div class="resize-handle resize-handle-n" onmousedown="VGTDeskEngine.startResize(event, '<?php echo esc_attr($key); ?>', 'n')"></div>
                        <div class="resize-handle resize-handle-s" onmousedown="VGTDeskEngine.startResize(event, '<?php echo esc_attr($key); ?>', 's')"></div>
                        <div class="resize-handle resize-handle-e" onmousedown="VGTDeskEngine.startResize(event, '<?php echo esc_attr($key); ?>', 'e')"></div>
                        <div class="resize-handle resize-handle-w" onmousedown="VGTDeskEngine.startResize(event, '<?php echo esc_attr($key); ?>', 'w')"></div>
                        <div class="resize-handle resize-handle-nw" onmousedown="VGTDeskEngine.startResize(event, '<?php echo esc_attr($key); ?>', 'nw')"></div>
                        <div class="resize-handle resize-handle-ne" onmousedown="VGTDeskEngine.startResize(event, '<?php echo esc_attr($key); ?>', 'ne')"></div>
                        <div class="resize-handle resize-handle-sw" onmousedown="VGTDeskEngine.startResize(event, '<?php echo esc_attr($key); ?>', 'sw')"></div>
                        <div class="resize-handle resize-handle-se" onmousedown="VGTDeskEngine.startResize(event, '<?php echo esc_attr($key); ?>', 'se')"></div>

                        <!-- Titlebar -->
                        <div class="vgt-window-header cursor-move window-header">
                            <div class="vgt-window-dots">
                                <span class="vgt-window-dot dot-rose" onclick="VGTDeskEngine.closeWindow('<?php echo esc_attr($key); ?>')"></span>
                                <span class="vgt-window-dot dot-amber" onclick="VGTDeskEngine.minimizeWindow('<?php echo esc_attr($key); ?>')"></span>
                                <span class="vgt-window-dot dot-emerald" onclick="VGTDeskEngine.maximizeWindow('<?php echo esc_attr($key); ?>')"></span>
                            </div>
                            <span class="vgt-window-title"><?php echo esc_html($app['title']); ?></span>
                            <div class="vgt-window-badge-wrap">
                                <div id="spinner-<?php echo esc_attr($key); ?>" class="spinner-vgt"></div>
                                <span class="vgt-badge-item vgt-accent-badge-item">Portal</span>
                            </div>
                        </div>
                        <!-- Iframe Box -->
                        <div class="flex-1 iframe-container relative">
                            <div class="drag-overlay absolute inset-0 hidden z-50 bg-transparent"></div>
                            <iframe 
                                id="iframe-<?php echo esc_attr($key); ?>" 
                                src="about:blank" 
                                data-src="<?php echo esc_url($app['url']); ?>"
                                onload="window.VGTDeskEngine && VGTDeskEngine.handleIframeLoaded('<?php echo esc_js($key); ?>')">
                            </iframe>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- CONTAINER FÜR DYNAMISCH ERZEUGTE WINDOWS (DEEP-LINK-PORTALE) -->
                <div id="vgt-dynamic-windows" class="vgt-dynamic-windows-container"></div>

            </div>

        </main>

        <!-- DESKTOP TASKBAR / DOCK -->
        <footer class="vgt-footer">
            <div class="vgt-dock" id="desktop-dock">
                
                <!-- Home-Button -->
                <div class="vgt-dock-item" onclick="VGTDeskEngine.handleDockClick('welcome')">
                    <div class="vgt-dock-icon" id="vgt-dock-home">
                        <span class="vgt-dock-emoji">🏠</span>
                    </div>
                    <span class="vgt-dock-tooltip">Home</span>
                    <span class="vgt-dock-indicator indicator-welcome" id="indicator-welcome"></span>
                </div>

                <!-- Settings-Button -->
                <div class="vgt-dock-item" onclick="VGTDeskEngine.handleDockClick('settings')">
                    <div class="vgt-dock-icon" id="vgt-dock-settings">
                        <span class="vgt-dock-emoji">⚙️</span>
                    </div>
                    <span class="vgt-dock-tooltip">Einstellungen</span>
                    <span class="vgt-dock-indicator indicator-settings" id="indicator-settings"></span>
                </div>

                <span class="vgt-dock-divider"></span>

                <!-- Aktive/Geöffnete Plugin-Apps in der Taskleiste -->
                <div id="vgt-dynamic-task-bar" class="vgt-dynamic-taskbar-flex">
                    <?php foreach ($apps_data as $key => $app): ?>
                        <div class="vgt-dock-item hidden" id="dock-task-<?php echo esc_attr($key); ?>" onclick="VGTDeskEngine.handleDockClick('<?php echo esc_js($key); ?>')">
                            <div class="vgt-dock-icon <?php echo esc_attr($app['color']); ?>">
                                <?php if ($app['icon_type'] === 'dashicons'): ?>
                                    <span class="dashicons <?php echo esc_attr($app['icon_val']); ?> vgt-dock-dashicon"></span>
                                <?php elseif ($app['icon_type'] === 'svg' || $app['icon_type'] === 'url'): ?>
                                    <img src="<?php echo $app['icon_type'] === 'svg' ? esc_attr($app['icon_val']) : esc_url($app['icon_val']); ?>" class="vgt-dock-img" alt="" />
                                <?php endif; ?>
                            </div>
                            <span class="vgt-dock-tooltip"><?php echo esc_html($app['title']); ?></span>
                            <span class="vgt-dock-indicator" id="indicator-<?php echo esc_attr($key); ?>"></span>
                        </div>
                    <?php endforeach; ?>
                </div>

            </div>
        </footer>

    </div>
</div>