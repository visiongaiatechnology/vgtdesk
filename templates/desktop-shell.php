<?php
/**
 * Template: VGT WP-Desk Shell Layout
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="vgt-desk-shell select-none" id="vgt-shell-root">

    <!-- Dynamischer Wallpaper-Hintergrund -->
    <div id="vgt-wallpaper" class="vgt-wallpaper-bg"></div>
    <div class="vgt-wallpaper-overlay"></div>

    <!-- MAIN INTERFACE OVERLAY -->
    <div class="vgt-interface-overlay">
        
        <!-- STATUS BAR (MENÜOBEN) -->
        <header class="vgt-topbar" id="top-bar">
            <div class="vgt-topbar-left">
                <div class="vgt-logo-group" onclick="VGTDeskEngine.handleDockClick('welcome')">
                    <span class="vgt-topbar-dot" id="vgt-topbar-dot"></span>
                    <span class="vgt-topbar-title" id="vgt-topbar-title">VGT</span>
                    <span class="vgt-topbar-subtitle">WP-Desk</span>
                </div>
                <span class="vgt-topbar-separator">|</span>
                <span class="vgt-topbar-branding">Powered by VisionGaiaTechnology</span>
                <span class="vgt-topbar-version-badge">V1.0 Beta</span>
                <span class="vgt-topbar-separator">|</span>
                <div class="vgt-topbar-nav">
                    <button class="vgt-topbar-btn" onclick="VGTDeskEngine.openWindow('welcome')">Home</button>
                    <button class="vgt-topbar-btn" onclick="VGTDeskEngine.openWindow('settings')">Einstellungen</button>
                </div>
            </div>

            <!-- Uhrzeit und Status -->
            <div class="vgt-topbar-right">
                <div class="vgt-profile-badge" onclick="VGTDeskEngine.openWindow('settings')">
                    <span class="vgt-profile-icon" id="vgt-accent-badge">⚙️</span>
                    <span class="vgt-profile-name"><?php echo esc_html($current_user->display_name); ?></span>
                </div>
                <div id="vgt-clock" class="vgt-clock-badge">00:00</div>
            </div>
        </header>

        <!-- CANVAS ARBEITSBEREICH -->
        <main class="vgt-workspace" id="desktop-workspace">
            
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
                                <img src="<?php echo esc_url($app['icon_val']); ?>" class="vgt-icon-img" alt="" />
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
                        <span class="vgt-window-title" id="welcome-title-accent">Willkommen bei VGT WP-Desk — V1.0 Beta</span>
                        <div class="vgt-window-spacer"></div>
                    </div>
                    <!-- Body Content -->
                    <div class="vgt-window-body">
                        <h2 class="vgt-body-h2">Die "Zero-Overheat" Evolution 🚀</h2>
                        <p class="vgt-body-p">
                            Alle deine installierten Plugins wurden automatisch ausgelesen und als Desktop-Apps angelegt. Sie laden direkt und blitzschnell im Iframe, befreit von der störenden linken Sidebar!
                        </p>
                        <div class="vgt-welcome-grid">
                            <div class="vgt-welcome-card">
                                <h3 class="vgt-card-h3 card-accent-indigo" id="box-accent-1">Auto-Menü Erfassung</h3>
                                <p class="vgt-card-p">Jedes Plugin-Menü steht sofort als App bereit. Kein lästiges API-Konfigurieren.</p>
                            </div>
                            <div class="vgt-welcome-card">
                                <h3 class="vgt-card-h3 card-accent-emerald" id="box-accent-2">Smart Taskbar</h3>
                                <p class="vgt-card-p">Das Dock am unteren Bildschirmrand zeigt dir deine aktiven und minimierten Fenster an.</p>
                            </div>
                        </div>

                        <!-- SPENDEN UNTERSTÜTZUNG SEKTION -->
                        <div class="vgt-donation-section">
                            <h3 class="vgt-donation-header">VGT WP-Desk unterstützen ❤️</h3>
                            <p class="vgt-donation-desc">Wenn dir das System gefällt und du uns unterstützen möchtest, freuen wir uns über eine kleine Spende:</p>
                            
                            <div class="vgt-donation-grid">
                                <!-- PayPal Link -->
                                <a href="https://www.paypal.com/paypalme/dergoldenelotus" target="_blank" class="vgt-donation-card-item paypal-color">
                                    <span class="vgt-donation-icon">💙</span>
                                    <span class="vgt-donation-label">PayPal</span>
                                    <small class="vgt-donation-addr">dergoldenelotus</small>
                                </a>

                                <!-- Bitcoin Click to Copy -->
                                <div class="vgt-donation-card-item crypto-btn" onclick="VGTDeskEngine.copyToClipboard('bc1q3ue5gq822tddmkdrek79adlkm36fatat3lz0dm', this)">
                                    <span class="vgt-donation-icon">🪙</span>
                                    <span class="vgt-donation-label">Bitcoin</span>
                                    <small class="vgt-donation-addr">Kopieren</small>
                                </div>

                                <!-- Ethereum Click to Copy -->
                                <div class="vgt-donation-card-item crypto-btn" onclick="VGTDeskEngine.copyToClipboard('0xD37DEfb09e07bD775EaaE9ccDaFE3a5b2348Fe85', this)">
                                    <span class="vgt-donation-icon">💎</span>
                                    <span class="vgt-donation-label">Ethereum</span>
                                    <small class="vgt-donation-addr">Kopieren</small>
                                </div>

                                <!-- USDT Click to Copy -->
                                <div class="vgt-donation-card-item crypto-btn" onclick="VGTDeskEngine.copyToClipboard('0xD37DEfb09e07bD775EaaE9ccDaFE3a5b2348Fe85', this)">
                                    <span class="vgt-donation-icon">💵</span>
                                    <span class="vgt-donation-label">USDT (ERC-20)</span>
                                    <small class="vgt-donation-addr">Kopieren</small>
                                </div>
                            </div>
                        </div>

                        <!-- BRANDING FOOTER -->
                        <div class="vgt-popup-footer">
                            <span class="vgt-footer-branding">Powered by VisionGaiaTechnology</span>
                            <span class="vgt-footer-version">V1.0 Beta</span>
                        </div>
                    </div>
                </div>

                <!-- NATIVES SYSTEMEINSTELLUNGEN-FENSTER -->
                <div id="win-settings" class="window hidden absolute vgt-window" style="width: 640px; height: 500px; top: 15%; left: 30%; z-index: 101;" onclick="VGTDeskEngine.focusWindow('settings')">
                    
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
                        <span class="vgt-window-title" id="settings-title-accent">Systemeinstellungen — V1.0 Beta</span>
                        <div class="vgt-window-spacer"></div>
                    </div>
                    <!-- Body Content -->
                    <div class="vgt-window-body vgt-settings-body">
                        <!-- Sektion: Hintergrund (Lokale WebP-Wallpapers) -->
                        <div class="vgt-settings-section">
                            <h3 class="vgt-settings-title">Hintergrundbild wählen</h3>
                            <div class="vgt-wallpaper-grid">
                                <?php $wallpapers_url = VGT_WPDESK_URL . 'wallpapers/'; ?>
                                <div class="vgt-wallpaper-thumb" style="background-image: url('<?php echo esc_url($wallpapers_url . 'wall1.webp'); ?>');" onclick="VGTDeskEngine.changeWallpaper('<?php echo esc_js($wallpapers_url . 'wall1.webp'); ?>')"></div>
                                <div class="vgt-wallpaper-thumb" style="background-image: url('<?php echo esc_url($wallpapers_url . 'wall2.webp'); ?>');" onclick="VGTDeskEngine.changeWallpaper('<?php echo esc_js($wallpapers_url . 'wall2.webp'); ?>')"></div>
                                <div class="vgt-wallpaper-thumb" style="background-image: url('<?php echo esc_url($wallpapers_url . 'wall3.webp'); ?>');" onclick="VGTDeskEngine.changeWallpaper('<?php echo esc_js($wallpapers_url . 'wall3.webp'); ?>')"></div>
                                <div class="vgt-wallpaper-thumb" style="background-image: url('<?php echo esc_url($wallpapers_url . 'wall4.webp'); ?>');" onclick="VGTDeskEngine.changeWallpaper('<?php echo esc_js($wallpapers_url . 'wall4.webp'); ?>')"></div>
                            </div>
                        </div>

                        <!-- Sektion: Custom Wallpaper URL -->
                        <div class="vgt-settings-form-row">
                            <span class="vgt-settings-title">Eigene Bild-URL eintragen</span>
                            <div class="vgt-form-input-line">
                                <input type="text" id="vgt-custom-wall-url" placeholder="https://..." class="vgt-input-text">
                                <button onclick="VGTDeskEngine.applyCustomWallpaper()" class="vgt-btn-primary">Anwenden</button>
                            </div>
                        </div>

                        <!-- Sektion: Akzentfarben -->
                        <div class="vgt-settings-section">
                            <h3 class="vgt-settings-title">Akzentfarbe festlegen</h3>
                            <div class="vgt-color-palette">
                                <button onclick="VGTDeskEngine.changeAccentColor('indigo')" class="vgt-color-circle bg-indigo" title="Indigo"></button>
                                <button onclick="VGTDeskEngine.changeAccentColor('emerald')" class="vgt-color-circle bg-emerald" title="Emerald"></button>
                                <button onclick="VGTDeskEngine.changeAccentColor('cyan')" class="vgt-color-circle bg-cyan" title="Cyan"></button>
                                <button onclick="VGTDeskEngine.changeAccentColor('amber')" class="vgt-color-circle bg-amber" title="Amber"></button>
                                <button onclick="VGTDeskEngine.changeAccentColor('rose')" class="vgt-color-circle bg-rose" title="Rose"></button>
                            </div>
                        </div>

                        <!-- Sektion: Navigation & Bypass-Schnittstellen -->
                        <div class="vgt-bypass-card">
                            <h3 class="vgt-settings-title">System-Bypass</h3>
                            <div class="vgt-bypass-buttons-row">
                                <button onclick="VGTDeskEngine.resetIconGrid()" class="vgt-btn-secondary">Icons aufräumen / Grid zurücksetzen</button>
                                <a href="<?php echo esc_url(wp_nonce_url(admin_url('index.php?vgt_action=disable_desk'), 'vgt_toggle_desktop')); ?>" class="vgt-btn-danger">Zur klassischen Ansicht</a>
                            </div>
                        </div>

                        <!-- Sektion: Blur -->
                        <div class="vgt-toggle-card">
                            <div class="vgt-toggle-label-group">
                                <h4 class="vgt-toggle-title">Weichzeichner (Glassmorphismus)</h4>
                                <p class="vgt-toggle-desc">Verlauf-Blur auf Fenster und Menüs anwenden.</p>
                            </div>
                            <input type="checkbox" checked class="vgt-toggle-switch" id="blur-toggle" onchange="VGTDeskEngine.toggleBlur()">
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
                                    <img src="<?php echo esc_url($app['icon_val']); ?>" class="vgt-dock-img" alt="" />
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