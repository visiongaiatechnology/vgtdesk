<?php
/**
 * Template part: Settings Window
 * STATUS: 💠 DIAMANT VGT SUPREME
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}
?>
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
                            <div class="vgt-cc-nav">
                                <button class="vgt-cc-nav-item active" data-tab="status" onclick="VGTDeskEngine.switchCCTab('status')">
                                    <span class="vgt-cc-nav-icon">📊</span>
                                    <span class="vgt-cc-nav-text">Status & Diagnose</span>
                                </button>
                                <button class="vgt-cc-nav-item" data-tab="themes" onclick="VGTDeskEngine.switchCCTab('themes')">
                                    <span class="vgt-cc-nav-icon">🎨</span>
                                    <span class="vgt-cc-nav-text">Display & Themes</span>
                                </button>
                                <button class="vgt-cc-nav-item" data-tab="shortcuts" onclick="VGTDeskEngine.switchCCTab('shortcuts')">
                                    <span class="vgt-cc-nav-icon">⌨️</span>
                                    <span class="vgt-cc-nav-text">Shortcuts Mapper</span>
                                </button>
                                <button class="vgt-cc-nav-item" data-tab="presets" onclick="VGTDeskEngine.switchCCTab('presets')">
                                    <span class="vgt-cc-nav-icon">🎭</span>
                                    <span class="vgt-cc-nav-text">Workspace Presets</span>
                                </button>
                            </div>
                            
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
                                        <div class="vgt-wallpaper-thumb" style="background-image: url('<?php echo esc_url($wallpapers_url . 'wall5.webp'); ?>');" onclick="VGTDeskEngine.changeWallpaper('<?php echo esc_js($wallpapers_url . 'wall5.webp'); ?>')"></div>
                                        <div class="vgt-wallpaper-thumb" style="background-image: url('<?php echo esc_url($wallpapers_url . 'wall6.webp'); ?>');" onclick="VGTDeskEngine.changeWallpaper('<?php echo esc_js($wallpapers_url . 'wall6.webp'); ?>')"></div>
                                    </div>
                                    
                                    <div class="vgt-form-input-line" style="margin-top: 10px;">
                                        <input type="text" id="vgt-custom-wall-url-cc" placeholder="Eigene Wallpaper Bild-URL eintragen (https://...)" class="vgt-input-text">
                                        <button onclick="VGTDeskEngine.applyCustomWallpaperCC()" class="vgt-btn-primary">Übernehmen</button>
                                    </div>
                                </div>
                                
                                <div class="vgt-cc-form-group">
                                    <label class="vgt-cc-label">Akzentfarbe festlegen</label>
                                    <div class="vgt-color-palette" style="flex-wrap: wrap; gap: 10px;">
                                        <button onclick="VGTDeskEngine.changeAccentColor('indigo')" class="vgt-color-circle bg-indigo" title="Indigo"></button>
                                        <button onclick="VGTDeskEngine.changeAccentColor('emerald')" class="vgt-color-circle bg-emerald" title="Emerald"></button>
                                        <button onclick="VGTDeskEngine.changeAccentColor('cyan')" class="vgt-color-circle bg-cyan" title="Cyan"></button>
                                        <button onclick="VGTDeskEngine.changeAccentColor('amber')" class="vgt-color-circle bg-amber" title="Amber"></button>
                                        <button onclick="VGTDeskEngine.changeAccentColor('rose')" class="vgt-color-circle bg-rose" title="Rose"></button>
                                        <button onclick="VGTDeskEngine.changeAccentColor('gold')" class="vgt-color-circle bg-gold" title="✨ Gold" style="border-color: #ffd700;"></button>
                                        <button onclick="VGTDeskEngine.changeAccentColor('purple')" class="vgt-color-circle bg-purple" title="Purple"></button>
                                        <button onclick="VGTDeskEngine.changeAccentColor('violet')" class="vgt-color-circle bg-violet" title="Violet"></button>
                                        <button onclick="VGTDeskEngine.changeAccentColor('neon')" class="vgt-color-circle bg-neon" title="Neon Green"></button>
                                    </div>
                                </div>
                                
                                <div class="vgt-cc-form-group">
                                    <label class="vgt-cc-label">Bildschirmauflösung (Skalierung)</label>
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
                                
                                <div class="vgt-cc-action-bar" style="display: flex; gap: 10px;">
                                    <button onclick="VGTDeskEngine.resetIconGrid()" class="vgt-btn-secondary">Symbole zurücksetzen</button>
                                    <button onclick="VGTDeskEngine.startFirstRunWizard()" class="vgt-btn-primary" style="padding: 8px 14px; font-size: 11px;">Setup-Wizard starten 🚀</button>
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

                            <!-- Tab: Workspace Presets -->
                            <div id="vgt-cc-tab-presets" class="vgt-cc-tab-panel">
                                <h3 class="vgt-cc-section-title">Workspace Presets</h3>
                                <p class="vgt-cc-section-desc">Ein-Klick Profile für verschiedene Arbeitsmodi. Passe dein System mit einem Klick an.</p>
                                <div class="vgt-preset-grid">
                                    <div class="vgt-preset-card preset-publisher" data-preset="publisher" onclick="VGTDeskEngine.applyWorkspacePreset('publisher')">
                                        <span class="vgt-preset-active-badge">Aktiv</span>
                                        <span class="vgt-preset-icon">🖊️</span>
                                        <p class="vgt-preset-name">Publisher Mode</p>
                                        <p class="vgt-preset-desc">Content-Erstellung: Emerald Akzent, macOS Layout, Widgets & Symbole eingeblendet.</p>
                                        <button class="vgt-preset-btn" onclick="event.stopPropagation(); VGTDeskEngine.applyWorkspacePreset('publisher')">Aktivieren</button>
                                    </div>
                                    <div class="vgt-preset-card preset-security" data-preset="security" onclick="VGTDeskEngine.applyWorkspacePreset('security')">
                                        <span class="vgt-preset-active-badge">Aktiv</span>
                                        <span class="vgt-preset-icon">🛡️</span>
                                        <p class="vgt-preset-name">Security Mode</p>
                                        <p class="vgt-preset-desc">Sentinel & Hardening: Rose Akzent, macOS Layout, Widgets & Symbole eingeblendet.</p>
                                        <button class="vgt-preset-btn" onclick="event.stopPropagation(); VGTDeskEngine.applyWorkspacePreset('security')">Aktivieren</button>
                                    </div>
                                    <div class="vgt-preset-card preset-developer" data-preset="developer" onclick="VGTDeskEngine.applyWorkspacePreset('developer')">
                                        <span class="vgt-preset-active-badge">Aktiv</span>
                                        <span class="vgt-preset-icon">💻</span>
                                        <p class="vgt-preset-name">Developer Mode</p>
                                        <p class="vgt-preset-desc">Code & Debugging: Violet Akzent, Linux Layout, Widgets & Symbole eingeblendet.</p>
                                        <button class="vgt-preset-btn" onclick="event.stopPropagation(); VGTDeskEngine.applyWorkspacePreset('developer')">Aktivieren</button>
                                    </div>
                                    <div class="vgt-preset-card preset-minimal" data-preset="minimal" onclick="VGTDeskEngine.applyWorkspacePreset('minimal')">
                                        <span class="vgt-preset-active-badge">Aktiv</span>
                                        <span class="vgt-preset-icon">⬜</span>
                                        <p class="vgt-preset-name">Minimal Mode</p>
                                        <p class="vgt-preset-desc">Konzentration & Fokus: Indigo Akzent, macOS Layout, Widgets & Symbole ausgeblendet.</p>
                                        <button class="vgt-preset-btn" onclick="event.stopPropagation(); VGTDeskEngine.applyWorkspacePreset('minimal')">Aktivieren</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
