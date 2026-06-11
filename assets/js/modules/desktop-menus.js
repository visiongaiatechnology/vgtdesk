/**
 * VGT Desktop Module - Menus & Control Center
 * Handles: initDockMagnification, toggleStartMenu, filterStartMenu, handleStartItemClick,
 *          toggleControlCenter, toggleCCToggle, updateControlCenterToggles, applyWidgetsVisibility,
 *          applyIconsVisibility, updateCCWidgetToggles, toggleCCWidget, toggleWidgetVisibility,
 *          resetAllSettings, initShortcuts, initWorkspaceContextMenu, openSubmenuPopup, openSubmenuWindow
 */

Object.assign(window.VGTDeskEngine, {
    initDockMagnification() {
        const dock = document.getElementById('desktop-dock');
        if (!dock) return;
        
        const items = dock.querySelectorAll('.vgt-dock-item');
        
        dock.addEventListener('mousemove', (e) => {
            const mouseX = e.clientX;
            
            items.forEach(item => {
                const itemRect = item.getBoundingClientRect();
                const itemCenterX = itemRect.left + itemRect.width / 2;
                
                const dist = Math.abs(mouseX - itemCenterX);
                const maxDist = 120;
                
                if (dist < maxDist) {
                    const scale = 1 + 0.35 * (1 - dist / maxDist);
                    item.querySelector('.vgt-dock-icon').style.transform = `scale(${scale})`;
                    item.style.margin = `0 ${8 * (scale - 1)}px`;
                } else {
                    item.querySelector('.vgt-dock-icon').style.transform = 'none';
                    item.style.margin = '0';
                }
            });
        });
        
        dock.addEventListener('mouseleave', () => {
            items.forEach(item => {
                item.querySelector('.vgt-dock-icon').style.transform = 'none';
                item.style.margin = '0';
            });
        });
    },

    toggleStartMenu(e) {
        if (e) {
            e.stopPropagation();
            e.preventDefault();
        }
        const menu = document.getElementById('vgt-start-menu');
        if (!menu) return;
        
        const isHidden = menu.classList.contains('hidden');
        this.playSound('click');
        if (isHidden) {
            menu.classList.remove('hidden');
            
            const closeHandler = (event) => {
                const contextMenu = document.getElementById('vgt-start-context-menu') || document.getElementById('vgt-workspace-context-menu');
                if (contextMenu && contextMenu.contains(event.target)) {
                    return;
                }
                const submenuPopup = document.getElementById('vgt-submenu-popup');
                if (submenuPopup && submenuPopup.contains(event.target)) {
                    return;
                }
                if (!menu.contains(event.target) && !event.target.closest('.vgt-logo-group')) {
                    menu.classList.add('hidden');
                    document.removeEventListener('click', closeHandler);
                }
            };
            document.addEventListener('click', closeHandler);
            
            const searchInput = document.getElementById('vgt-start-search');
            if (searchInput) {
                searchInput.value = '';
                searchInput.focus();
                this.filterStartMenu();
            }
        } else {
            menu.classList.add('hidden');
        }
    },

    filterStartMenu() {
        const query = (document.getElementById('vgt-start-search')?.value || '').toLowerCase();
        const items = document.querySelectorAll('.vgt-start-item');
        items.forEach(item => {
            const title = (item.dataset.title || '').toLowerCase();
            if (title.indexOf(query) > -1) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });

        // Hide section headers if all child items are hidden
        const sections = ['pinned', 'other'];
        sections.forEach(sec => {
            const sectionEl = document.getElementById(`vgt-start-section-${sec}`);
            if (!sectionEl) return;
            const visibleItems = Array.from(sectionEl.querySelectorAll('.vgt-start-item')).some(item => item.style.display !== 'none');
            sectionEl.style.display = visibleItems ? '' : 'none';
        });
    },

    handleStartItemClick(key) {
        const app = (typeof vgtConfig !== 'undefined' && vgtConfig.apps) ? vgtConfig.apps[key] : null;
        if (app && app.submenus && app.submenus.length > 0) {
            this.openSubmenuPopup(null, key);
        } else {
            this.openWindow(key);
            const menu = document.getElementById('vgt-start-menu');
            if (menu) menu.classList.add('hidden');
        }
    },

    /* ==========================================================================
       PREMIUM CONTROL CENTER
       ========================================================================== */
    toggleControlCenter(e) {
        if (e) {
            e.stopPropagation();
            e.preventDefault();
        }
        const cc = document.getElementById('vgt-control-center');
        if (!cc) return;
        
        const isHidden = cc.classList.contains('hidden');
        this.playSound('click');
        
        if (isHidden) {
            cc.classList.remove('hidden');
            this.updateControlCenterToggles();
            this.startLatencyGraph();
            
            const closeCC = (event) => {
                if (!cc.contains(event.target) && !event.target.closest('.vgt-profile-badge') && !event.target.closest('.vgt-clock-badge')) {
                    cc.classList.add('hidden');
                    this.stopLatencyGraph();
                    document.removeEventListener('click', closeCC);
                }
            };
            document.addEventListener('click', closeCC);
        } else {
            cc.classList.add('hidden');
            this.stopLatencyGraph();
        }
    },

    toggleCCToggle(key) {
        this.playSound('click');
        if (key === 'sound') {
            this.userSettings.audio_enabled = !this.userSettings.audio_enabled;
            this.saveUserSetting('audio_enabled', this.userSettings.audio_enabled);
        } else if (key === 'widgets') {
            this.userSettings.widgets_visible = !this.userSettings.widgets_visible;
            this.saveUserSetting('widgets_visible', this.userSettings.widgets_visible);
            this.applyWidgetsVisibility();
        } else if (key === 'icons') {
            this.userSettings.icons_visible = !this.userSettings.icons_visible;
            this.saveUserSetting('icons_visible', this.userSettings.icons_visible);
            this.applyIconsVisibility();
        } else if (key === 'blur') {
            this.userSettings.blur = !this.userSettings.blur;
            this.saveUserSetting('blur', this.userSettings.blur);
            this.applyBlur(this.userSettings.blur);
        }
        this.updateControlCenterToggles();
    },

    toggleAutoRedirect() {
        this.playSound('click');
        const checkbox = document.getElementById('redirect-toggle');
        const state = checkbox ? checkbox.checked : false;
        this.userSettings.auto_redirect = state ? 'true' : 'false';
        this.saveUserSetting('auto_redirect', state ? 'true' : 'false');
        this.addLog(`Automatisches Umleiten ${state ? 'aktiviert' : 'deaktiviert'}.`);
    },
    
    changeLayoutStyle(style) {
        this.playSound('click');
        if (!['macos', 'windows', 'linux'].includes(style)) return;

        const shellRoot = document.getElementById('vgt-shell-root');
        if (shellRoot) {
            shellRoot.classList.remove('vgt-layout-macos', 'vgt-layout-windows', 'vgt-layout-linux');
            shellRoot.classList.add(`vgt-layout-${style}`);
        }

        this.userSettings.layout_style = style;
        this.saveUserSetting('layout_style', style);
        this.addLog(`Layout-Design auf '${style}' geändert.`);

        // Dynamic rename Dashboard -> Dieser PC for Windows and back
        const dbIconItem = document.querySelector('.vgt-icon-item[data-id="index_php"]');
        const dbIconLabel = dbIconItem ? dbIconItem.querySelector('.vgt-icon-label') : null;
        const dbStartItem = document.querySelector('.vgt-start-item[onclick*="index_php"]');
        const dbStartLabel = dbStartItem ? dbStartItem.querySelector('.vgt-start-label') : null;
        const dbDockItem = document.getElementById('dock-task-index_php');
        const dbDockTooltip = dbDockItem ? dbDockItem.querySelector('.vgt-dock-tooltip') : null;
        const dbWin = document.getElementById('win-index_php');
        const dbWinTitle = dbWin ? dbWin.querySelector('.vgt-window-title') : null;

        const originalTitle = (typeof vgtConfig !== 'undefined' && vgtConfig.apps && vgtConfig.apps.index_php) ? vgtConfig.apps.index_php.title : 'Dashboard';
        const isWin = style === 'windows';
        const targetTitle = isWin ? 'Dieser PC' : originalTitle;

        if (dbIconLabel) dbIconLabel.textContent = targetTitle;
        if (dbStartLabel) dbStartLabel.textContent = targetTitle;
        if (dbStartItem) dbStartItem.dataset.title = targetTitle;
        if (dbDockTooltip) dbDockTooltip.textContent = targetTitle;
        if (dbWinTitle) dbWinTitle.textContent = targetTitle;

        // Dynamic icon tile replacement for Dashboard -> computer
        const dbIconTile = dbIconItem ? dbIconItem.querySelector('.vgt-icon-tile') : null;
        const dbStartTile = dbStartItem ? dbStartItem.querySelector('.vgt-start-icon-tile') : null;
        const dbDockIcon = dbDockItem ? dbDockItem.querySelector('.vgt-dock-icon') : null;

        [dbIconTile, dbStartTile, dbDockIcon].forEach(tile => {
            if (!tile) return;
            if (!tile.dataset.originalHtml) {
                tile.dataset.originalHtml = tile.innerHTML;
                tile.dataset.originalClass = tile.className;
            }
            if (isWin) {
                tile.innerHTML = '<span class="dashicons dashicons-desktop vgt-icon-dashicon vgt-start-icon-dashicon vgt-dock-dashicon"></span>';
                // Replace gradient classes with slate settings color
                tile.className = 'vgt-color-gradient-settings ' + tile.className.replace(/vgt-color-gradient-\S+/g, '').replace(/from-\S+ to-\S+/g, '').trim();
            } else {
                tile.innerHTML = tile.dataset.originalHtml;
                tile.className = tile.dataset.originalClass;
            }
        });

        // Update active class on thumbs
        document.querySelectorAll('.vgt-layout-thumb').forEach(thumb => {
            thumb.classList.toggle('active', thumb.dataset.layout === style);
        });

        // Re-arrange desktop icons immediately and after transition
        this.arrangeDesktopIcons();
        setTimeout(() => {
            this.arrangeDesktopIcons();
        }, 150);
    },
    
    updateControlCenterToggles() {
        const soundActive = this.userSettings.audio_enabled === true;
        const widgetsActive = this.userSettings.widgets_visible === true;
        const iconsActive = this.userSettings.icons_visible === true;
        const blurActive = this.userSettings.blur === true;
        
        const toggleSound = document.getElementById('cc-toggle-sound');
        const toggleWidgets = document.getElementById('cc-toggle-widgets');
        const toggleIcons = document.getElementById('cc-toggle-icons');
        const toggleBlur = document.getElementById('cc-toggle-blur');
        
        if (toggleSound) {
            toggleSound.parentElement.classList.toggle('active', soundActive);
            toggleSound.textContent = soundActive ? '🔊' : '🔇';
        }
        if (toggleWidgets) toggleWidgets.parentElement.classList.toggle('active', widgetsActive);
        if (toggleIcons) toggleIcons.parentElement.classList.toggle('active', iconsActive);
        if (toggleBlur) toggleBlur.parentElement.classList.toggle('active', blurActive);

        this.updateCCWidgetToggles();
    },
    
    applyWidgetsVisibility() {
        const container = document.getElementById('vgt-widgets-container');
        if (container) {
            container.style.display = (this.userSettings.widgets_visible === true) ? '' : 'none';
        }
    },
    
    applyIconsVisibility() {
        const container = document.getElementById('desktop-icons-area');
        if (container) {
            container.style.display = (this.userSettings.icons_visible === true) ? '' : 'none';
        }
    },

    updateCCWidgetToggles() {
        const widgetIds = ['clock', 'system', 'notes', 'sentinel'];
        widgetIds.forEach(id => {
            const toggle = document.getElementById(`cc-widget-toggle-${id}`);
            if (!toggle) return;
            const widget = document.getElementById(`widget-${id}`);
            const isVisible = widget ? widget.style.display !== 'none' : true;
            toggle.classList.toggle('active', isVisible);
        });
    },

    toggleCCWidget(id) {
        this.playSound('click');
        this.toggleWidgetVisibility(id);
        this.updateCCWidgetToggles();
    },
    
    toggleWidgetVisibility(id) {
        const widget = document.getElementById(`widget-${id}`);
        if (!widget) return;
        
        const isHidden = widget.style.display === 'none';
        widget.style.display = isHidden ? 'flex' : 'none';
        this.playSound('click');
        this.addLog(`Widget '${id}' wurde ${isHidden ? 'eingeblendet' : 'ausgeblendet'}.`);
        
        if (!this.userSettings.widget_positions || Array.isArray(this.userSettings.widget_positions)) {
            this.userSettings.widget_positions = {};
        }
        if (!this.userSettings.widget_positions[widget.id]) {
            this.userSettings.widget_positions[widget.id] = {};
        }
        this.userSettings.widget_positions[widget.id].visible = isHidden;
        this.saveUserSetting('widget_positions', this.userSettings.widget_positions);
    },

    resetAllSettings() {
        this.showModal({
            title: 'System zurücksetzen',
            message: 'Möchten Sie wirklich alle Einstellungen auf Werkseinstellungen zurücksetzen?',
            confirmText: 'Zurücksetzen',
            confirmClass: 'vgt-btn-danger',
            onConfirm: () => {
                localStorage.removeItem('vgt_widget_notes');
                
                this.saveUserSetting('wallpaper', '');
                this.saveUserSetting('accent_color', 'indigo');
                this.saveUserSetting('blur', 'true');
                this.saveUserSetting('icon_positions', {});
                this.saveUserSetting('window_settings', {});
                this.saveUserSetting('widgets_visible', 'true');
                this.saveUserSetting('icons_visible', 'true');
                this.saveUserSetting('audio_enabled', 'true');
                this.saveUserSetting('widget_positions', {});
                
                this.addLog("System zurückgesetzt. Lade neu...");
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            }
        });
    },

    /* ==========================================================================
       KEYBOARD SHORTCUTS
       ========================================================================== */
    matchShortcut(e, shortcutStr) {
        if (!shortcutStr) return false;
        const parts = shortcutStr.split('+');
        const keyOrCode = parts[parts.length - 1];
        
        const needsCtrl = parts.includes('Control') || parts.includes('Ctrl');
        const needsAlt = parts.includes('Alt');
        const needsShift = parts.includes('Shift');
        const needsMeta = parts.includes('Meta') || parts.includes('Win') || parts.includes('Command');

        const hasCtrl = e.ctrlKey;
        const hasAlt = e.altKey;
        const hasShift = e.shiftKey;
        const hasMeta = e.metaKey;

        if (needsCtrl !== hasCtrl) return false;
        if (needsAlt !== hasAlt) return false;
        if (needsShift !== hasShift) return false;
        if (needsMeta !== hasMeta) return false;

        const cleanKey = keyOrCode.toLowerCase();
        return (e.code.toLowerCase() === cleanKey || e.key.toLowerCase() === cleanKey);
    },

    toggleShowDesktop() {
        const openWindows = Object.keys(this.activeWindows).filter(id => this.activeWindows[id] && !this.minimizedWindows[id]);
        if (openWindows.length > 0) {
            this.prevOpenWindows = openWindows;
            openWindows.forEach(id => this.minimizeWindow(id));
            this.addLog("Desktop angezeigt (alle Fenster minimiert).");
        } else if (this.prevOpenWindows && this.prevOpenWindows.length > 0) {
            this.prevOpenWindows.forEach(id => this.restoreWindow(id));
            this.prevOpenWindows = [];
            this.addLog("Fenster wiederhergestellt.");
        }
    },

    capturedAction: null,
    captureKeydownHandler: null,

    startCaptureShortcut(action) {
        this.stopCaptureShortcut();

        const input = document.getElementById(`vgt-shortcut-${action}`);
        if (!input) return;

        this.playSound('click');
        this.capturedAction = action;

        input.classList.add('vgt-shortcut-capturing');
        input.value = 'Tastenkombination drücken...';
        input.placeholder = 'Tastenkombination drücken...';
        input.focus();

        const button = input.nextElementSibling;
        if (button) {
            button.textContent = 'Bereit...';
            button.classList.add('vgt-btn-capturing');
        }

        this.captureKeydownHandler = (e) => {
            e.preventDefault();
            e.stopPropagation();

            if (['Control', 'Alt', 'Shift', 'Meta'].includes(e.key)) {
                return;
            }

            const parts = [];
            if (e.ctrlKey) parts.push('Control');
            if (e.altKey) parts.push('Alt');
            if (e.shiftKey) parts.push('Shift');
            if (e.metaKey) parts.push('Meta');
            parts.push(e.code);

            const shortcutCode = parts.join('+');

            if (!this.userSettings.shortcuts || Array.isArray(this.userSettings.shortcuts)) {
                this.userSettings.shortcuts = {};
            }
            this.userSettings.shortcuts[action] = shortcutCode;
            this.saveUserSetting('shortcuts', this.userSettings.shortcuts);

            input.value = this.formatShortcutString(shortcutCode);
            this.playSound('click');
            this.addLog(`Hotkey '${action}' auf '${shortcutCode}' geändert.`);

            this.stopCaptureShortcut();
        };

        document.addEventListener('keydown', this.captureKeydownHandler, true);
    },

    stopCaptureShortcut() {
        if (this.capturedAction) {
            const input = document.getElementById(`vgt-shortcut-${this.capturedAction}`);
            if (input) {
                input.classList.remove('vgt-shortcut-capturing');
                const button = input.nextElementSibling;
                if (button) {
                    button.textContent = 'Aufzeichnen';
                    button.classList.remove('vgt-btn-capturing');
                }
            }
            this.capturedAction = null;
        }

        if (this.captureKeydownHandler) {
            document.removeEventListener('keydown', this.captureKeydownHandler, true);
            this.captureKeydownHandler = null;
        }
    },

    initShortcuts() {
        document.addEventListener('keydown', (e) => {
            if (this.capturedAction) return;

            const activeEl = document.activeElement;
            if (activeEl && (activeEl.tagName === 'INPUT' || activeEl.tagName === 'TEXTAREA' || activeEl.isContentEditable)) {
                if (e.key === 'Escape' && activeEl.id.startsWith('vgt-shortcut-')) {
                    this.stopCaptureShortcut();
                    this.applyShortcuts();
                }
                return;
            }

            if (e.key === 'Escape') {
                const menu = document.getElementById('vgt-start-menu');
                if (menu && !menu.classList.contains('hidden')) {
                    menu.classList.add('hidden');
                }
                const cc = document.getElementById('vgt-control-center');
                if (cc && !cc.classList.contains('hidden')) {
                    cc.classList.add('hidden');
                    this.stopLatencyGraph();
                }
                return;
            }

            const shortcuts = this.userSettings.shortcuts || {
                window_switch: "Alt+KeyQ",
                show_desktop: "Alt+KeyD",
                spotlight: "Control+Space",
                control_center: "Alt+KeyC",
                start_menu: "Alt+KeyS"
            };

            if (this.matchShortcut(e, shortcuts.window_switch)) {
                e.preventDefault();
                const windows = Array.from(document.querySelectorAll('.vgt-window:not(.hidden)'));
                if (windows.length <= 1) return;
                
                windows.sort((a, b) => {
                    return (parseInt(b.style.zIndex) || 0) - (parseInt(a.style.zIndex) || 0);
                });
                
                const nextWin = windows[1];
                if (nextWin) {
                    const winId = nextWin.id.replace('win-', '');
                    this.focusWindow(winId);
                    this.addLog(`Hotkey: Fokusiert Fenster '${winId}'`);
                }
            }

            if (this.matchShortcut(e, shortcuts.show_desktop)) {
                e.preventDefault();
                this.toggleShowDesktop();
            }

            if (this.matchShortcut(e, shortcuts.spotlight)) {
                e.preventDefault();
                this.toggleSpotlight();
            }

            if (this.matchShortcut(e, shortcuts.control_center)) {
                e.preventDefault();
                this.toggleControlCenter();
            }

            if (this.matchShortcut(e, shortcuts.start_menu)) {
                e.preventDefault();
                this.toggleStartMenu();
            }
        });
    },

    /* ==========================================================================
       WORKSPACE CONTEXT MENU
       ========================================================================== */
    initWorkspaceContextMenu() {
        const workspace = document.getElementById('desktop-workspace');
        if (!workspace) return;

        workspace.addEventListener('contextmenu', (e) => {
            const icon = e.target.closest('.desktop-icon');
            if (icon) {
                e.preventDefault();
                e.stopPropagation();
                this.showIconContextMenu(e, icon);
                return;
            }

            const isWidgetOrWindow = e.target.closest('.vgt-widget') || e.target.closest('.window');
            if (isWidgetOrWindow) return;

            e.preventDefault();
            e.stopPropagation();

            let menu = document.getElementById('vgt-workspace-context-menu');
            if (!menu) {
                menu = document.createElement('div');
                menu.id = 'vgt-workspace-context-menu';
                menu.className = 'vgt-context-menu glassmorphism absolute';
                
                const optNewFolder = document.createElement('div');
                optNewFolder.className = 'vgt-context-menu-item';
                optNewFolder.textContent = '📁 Neuer Ordner';
                optNewFolder.addEventListener('click', (ev) => {
                    ev.stopPropagation();
                    this.createNewFolder();
                    menu.style.display = 'none';
                });
                
                const optCleanGrid = document.createElement('div');
                optCleanGrid.className = 'vgt-context-menu-item';
                optCleanGrid.textContent = '🧹 Symbole aufräumen';
                optCleanGrid.addEventListener('click', (ev) => {
                    ev.stopPropagation();
                    this.resetIconGrid();
                    menu.style.display = 'none';
                });
                
                const optSettings = document.createElement('div');
                optSettings.className = 'vgt-context-menu-item';
                optSettings.textContent = '⚙️ Einstellungen';
                optSettings.addEventListener('click', (ev) => {
                    ev.stopPropagation();
                    this.openWindow('settings');
                    menu.style.display = 'none';
                });
                
                menu.appendChild(optNewFolder);
                menu.appendChild(optCleanGrid);
                menu.appendChild(optSettings);
                
                (document.getElementById('vgt-shell-root') || document.body).appendChild(menu);
            }
            
            menu.style.left = `${e.clientX}px`;
            menu.style.top = `${e.clientY}px`;
            menu.style.display = 'block';
            
            const closeMenu = (event) => {
                if (!menu.contains(event.target)) {
                    menu.style.display = 'none';
                    document.removeEventListener('click', closeMenu);
                }
            };
            setTimeout(() => {
                document.addEventListener('click', closeMenu);
            }, 50);
        });
    },

    showIconContextMenu(e, iconEl) {
        const iconId = iconEl.dataset.id;
        const isFolder = iconEl.dataset.isFolder === 'true' || iconEl.classList.contains('desktop-folder');

        // Remove any existing menus
        const existingWorkspaceMenu = document.getElementById('vgt-workspace-context-menu');
        if (existingWorkspaceMenu) existingWorkspaceMenu.style.display = 'none';

        const existingStartMenu = document.getElementById('vgt-start-context-menu');
        if (existingStartMenu) existingStartMenu.remove();

        let menu = document.getElementById('vgt-icon-context-menu');
        if (menu) menu.remove();

        menu = document.createElement('div');
        menu.id = 'vgt-icon-context-menu';
        menu.className = 'vgt-context-menu glassmorphism';
        menu.style.display = 'block';
        menu.style.left = `${e.clientX}px`;
        menu.style.top = `${e.clientY}px`;

        // 1. Reset Position Option
        const optReset = document.createElement('div');
        optReset.className = 'vgt-context-menu-item';
        optReset.innerHTML = `<span>🔄</span><span>Position zurücksetzen</span>`;
        optReset.addEventListener('click', (ev) => {
            ev.stopPropagation();
            if (this.userSettings.icon_positions) {
                delete this.userSettings.icon_positions[iconId];
                this.saveUserSetting('icon_positions', this.userSettings.icon_positions);
            }
            this.playSound('click');
            this.addLog(`Position von '${iconId}' zurückgesetzt.`);
            this.arrangeDesktopIcons();
            menu.remove();
        });
        menu.appendChild(optReset);

        if (isFolder) {
            // 2. Rename Option (Folders only)
            const optRename = document.createElement('div');
            optRename.className = 'vgt-context-menu-item';
            optRename.innerHTML = `<span>✏️</span><span>Umbenennen</span>`;
            optRename.addEventListener('click', (ev) => {
                ev.stopPropagation();
                menu.remove();
                this.renameFolder(iconId);
            });
            menu.appendChild(optRename);

            // 3. Delete Option (Folders only)
            const optDelete = document.createElement('div');
            optDelete.className = 'vgt-context-menu-item';
            optDelete.innerHTML = `<span>🗑️</span><span style="color: #f43f5e;">Löschen</span>`;
            optDelete.addEventListener('click', (ev) => {
                ev.stopPropagation();
                menu.remove();
                this.deleteFolder(iconId);
            });
            menu.appendChild(optDelete);
        }

        (document.getElementById('vgt-shell-root') || document.body).appendChild(menu);

        const closeMenu = (event) => {
            if (!menu.contains(event.target)) {
                menu.remove();
                document.removeEventListener('click', closeMenu);
            }
        };
        setTimeout(() => document.addEventListener('click', closeMenu), 10);
    },

    /* ==========================================================================
       SUBMENU POPUPS
       ========================================================================== */
    openSubmenuPopup(e, key) {
        const appData = vgtConfig.apps[key];
        if (!appData) return;

        const existing = document.getElementById('vgt-submenu-popup');
        if (existing) existing.remove();

        const popup = document.createElement('div');
        popup.id = 'vgt-submenu-popup';
        popup.className = 'vgt-submenu-popup glassmorphism absolute';
        
        const header = document.createElement('div');
        header.className = 'vgt-submenu-header';
        header.style.padding = '8px 12px';
        header.style.borderBottom = '1px solid rgba(255, 255, 255, 0.08)';
        header.style.fontSize = '11px';
        header.style.color = '#64748b';
        header.style.fontWeight = 'bold';
        header.textContent = appData.title;
        popup.appendChild(header);

        const list = document.createElement('div');
        list.className = 'vgt-submenu-list';
        list.style.display = 'flex';
        list.style.flexDirection = 'column';

        appData.submenus.forEach(sub => {
            const item = document.createElement('div');
            item.className = 'vgt-submenu-item';
            item.style.padding = '8px 12px';
            item.style.cursor = 'pointer';
            item.style.fontSize = '12px';
            item.style.color = '#cbd5e1';
            item.style.transition = 'background-color 0.15s, color 0.15s';
            item.textContent = sub.title;

            item.addEventListener('mouseenter', () => {
                const hexColor = getComputedStyle(document.documentElement).getPropertyValue('--vgt-accent-color').trim() || '#6366f1';
                item.style.backgroundColor = hexColor + '20';
                item.style.color = '#ffffff';
            });
            item.addEventListener('mouseleave', () => {
                item.style.backgroundColor = 'transparent';
                item.style.color = '#cbd5e1';
            });

            item.addEventListener('click', (ev) => {
                ev.stopPropagation();
                this.openSubmenuWindow(key, sub.title, sub.url);
                popup.remove();
            });
            list.appendChild(item);
        });
        popup.appendChild(list);

        (document.getElementById('vgt-shell-root') || document.body).appendChild(popup);

        const x = e ? e.clientX : window.innerWidth / 2 - 100;
        const y = e ? e.clientY : window.innerHeight / 2 - 150;
        popup.style.left = `${x}px`;
        popup.style.top = `${y}px`;

        const rect = popup.getBoundingClientRect();
        if (x + rect.width > window.innerWidth) {
            popup.style.left = `${window.innerWidth - rect.width - 16}px`;
        }
        if (y + rect.height > window.innerHeight) {
            popup.style.top = `${window.innerHeight - rect.height - 16}px`;
        }

        const closePopup = (event) => {
            if (!popup.contains(event.target)) {
                popup.remove();
                document.removeEventListener('click', closePopup);
            }
        };
        setTimeout(() => {
            document.addEventListener('click', closePopup);
        }, 50);
    },

    openSubmenuWindow(appKey, subTitle, subUrl) {
        const appData = vgtConfig.apps[appKey];
        if (!appData) return;

        this.openWindow(appKey);
        const menu = document.getElementById('vgt-start-menu');
        if (menu) menu.classList.add('hidden');
        const iframe = document.getElementById(`iframe-${appKey}`);
        if (iframe) {
            const spinner = document.getElementById(`spinner-${appKey}`);
            if (spinner) spinner.style.display = 'block';

            let targetUrl = subUrl;
            if (targetUrl.indexOf('vgt_iframe') === -1) {
                targetUrl += (targetUrl.indexOf('?') === -1 ? '?' : '&') + 'vgt_iframe=true';
            }
            iframe.src = this.cleanUrl(targetUrl);
            iframe.dataset.loaded = 'true';

            const win = document.getElementById(`win-${appKey}`);
            if (win) {
                const titleEl = win.querySelector('.vgt-window-title');
                if (titleEl) {
                    titleEl.textContent = `${appData.title} › ${subTitle}`;
                }
            }
        }
    },

    showStartItemContextMenu(e, key, type) {
        e.preventDefault();
        e.stopPropagation();

        const existing = document.getElementById('vgt-start-context-menu');
        if (existing) existing.remove();

        const isPinned = this.userSettings.pinned_apps ? this.userSettings.pinned_apps.includes(key) : ['index_php', 'options_general_php', 'upload_php', 'plugins_php', 'users_php', 'tools_php', 'themes_php', 'edit_php', 'edit_comments_php'].includes(key);

        const menu = document.createElement('div');
        menu.id = 'vgt-start-context-menu';
        menu.className = 'vgt-context-menu';
        menu.style.display = 'block';
        menu.style.left = `${e.clientX}px`;
        menu.style.top = `${e.clientY}px`;

        const actionText = isPinned ? 'Von Start lösen' : 'An Start anheften';
        const actionIcon = isPinned ? '📌' : '📍';

        const item = document.createElement('div');
        item.className = 'vgt-context-menu-item';
        item.innerHTML = `<span>${actionIcon}</span><span>${actionText}</span>`;
        item.onclick = () => {
            this.togglePinApp(key, !isPinned);
            menu.remove();
        };

        menu.appendChild(item);
        (document.getElementById('vgt-shell-root') || document.body).appendChild(menu);

        const closeMenu = () => {
            menu.remove();
            document.removeEventListener('click', closeMenu);
        };
        setTimeout(() => document.addEventListener('click', closeMenu), 10);
    },

    togglePinApp(key, pin) {
        if (!this.userSettings.pinned_apps) {
            this.userSettings.pinned_apps = ['index_php', 'options_general_php', 'upload_php', 'plugins_php', 'users_php', 'tools_php', 'themes_php', 'edit_php', 'edit_comments_php'];
        }
        if (pin) {
            if (!this.userSettings.pinned_apps.includes(key)) {
                this.userSettings.pinned_apps.push(key);
            }
        } else {
            this.userSettings.pinned_apps = this.userSettings.pinned_apps.filter(k => k !== key);
        }
        this.saveUserSetting('pinned_apps', this.userSettings.pinned_apps);
        this.updateStartMenuPinnedDOM(key, pin);
        this.addLog(`App '${key}' wurde vom Startmenü ${pin ? 'angeheftet' : 'gelöst'}.`);
    },

    getAppIconHTML(app) {
        if (app.icon_type === 'dashicons') {
            return `<span class="dashicons ${app.icon_val} vgt-start-icon-dashicon"></span>`;
        } else {
            return `<img src="${app.icon_val}" class="vgt-start-icon-img" alt="" />`;
        }
    },

    updateStartMenuPinnedDOM(key, pin) {
        const pinnedGrids = document.querySelectorAll('#vgt-start-grid-pinned, .vgt-start-win10-grid');
        pinnedGrids.forEach(grid => {
            const item = grid.querySelector(`[data-key="${key}"]`);
            if (item) item.remove();
        });

        // Toggle visibility of standard layout "Other Apps" sections where applicable
        const allAppsGrids = document.querySelectorAll('#vgt-start-grid-other');
        allAppsGrids.forEach(grid => {
            const item = grid.querySelector(`[data-key="${key}"]`);
            if (item) {
                if (pin) {
                    item.classList.add('hidden');
                    item.style.display = 'none';
                } else {
                    item.classList.remove('hidden');
                    item.style.display = 'flex';
                }
            }
        });

        // Update the pin button in any list items (Win10 all apps list or standard lists)
        const listItems = document.querySelectorAll(`.vgt-start-item[data-key="${key}"]:not(.win10-tile-item):not(#vgt-start-grid-pinned .vgt-start-item)`);
        listItems.forEach(item => {
            const pinBtn = item.querySelector('.vgt-start-pin-btn');
            if (pinBtn) {
                pinBtn.setAttribute('onclick', `event.stopPropagation(); event.preventDefault(); VGTDeskEngine.togglePinApp('${key}', ${!pin})`);
                pinBtn.setAttribute('title', pin ? 'Von Start lösen' : 'An Start anheften');
                pinBtn.textContent = pin ? '📌' : '📍';
            }
        });

        if (pin) {
            const app = (typeof vgtConfig !== 'undefined' && vgtConfig.apps) ? vgtConfig.apps[key] : null;
            if (!app) return;

            // Add to Win10 Pinned Grid
            const win10Grid = document.querySelector('.vgt-start-win10-grid');
            if (win10Grid) {
                const iconHTML = this.getAppIconHTML(app);
                const tile = document.createElement('div');
                tile.className = 'vgt-start-item win10-tile-item';
                tile.dataset.key = key;
                tile.dataset.title = app.title;
                tile.onclick = () => this.handleStartItemClick(key);
                tile.oncontextmenu = (e) => this.showStartItemContextMenu(e, key, 'pinned');
                tile.innerHTML = `
                    <div class="vgt-start-pin-btn" onclick="event.stopPropagation(); event.preventDefault(); VGTDeskEngine.togglePinApp('${key}', false)" title="Von Start lösen">📌</div>
                    <div class="vgt-start-icon-tile win10-tile-icon ${app.color}">
                        ${iconHTML}
                    </div>
                    <span class="vgt-start-label win10-tile-label">${this.escapeHTML(app.title)}</span>
                `;
                win10Grid.appendChild(tile);
            }

            // Add to Standard Pinned Grid
            const stdGrid = document.querySelector('#vgt-start-grid-pinned');
            if (stdGrid) {
                const iconHTML = this.getAppIconHTML(app);
                const item = document.createElement('div');
                item.className = 'vgt-start-item';
                item.dataset.key = key;
                item.dataset.title = app.title;
                item.onclick = () => this.handleStartItemClick(key);
                item.oncontextmenu = (e) => this.showStartItemContextMenu(e, key, 'pinned');
                item.innerHTML = `
                    <div class="vgt-start-pin-btn" onclick="event.stopPropagation(); event.preventDefault(); VGTDeskEngine.togglePinApp('${key}', false)" title="Von Start lösen">📌</div>
                    <div class="vgt-start-icon-tile ${app.color}">
                        ${iconHTML}
                    </div>
                    <span class="vgt-start-label">${this.escapeHTML(app.title)}</span>
                `;
                stdGrid.appendChild(item);
            }
        } else {
            // Restore visibility in standard "Other" section if unpinned
            const app = (typeof vgtConfig !== 'undefined' && vgtConfig.apps) ? vgtConfig.apps[key] : null;
            if (!app) return;
            const otherGrid = document.querySelector('#vgt-start-grid-other');
            if (otherGrid) {
                const itemExists = otherGrid.querySelector(`[data-key="${key}"]`);
                if (!itemExists) {
                    const iconHTML = this.getAppIconHTML(app);
                    const item = document.createElement('div');
                    item.className = 'vgt-start-item';
                    item.dataset.key = key;
                    item.dataset.title = app.title;
                    item.onclick = () => this.handleStartItemClick(key);
                    item.oncontextmenu = (e) => this.showStartItemContextMenu(e, key, 'all_apps');
                    item.innerHTML = `
                        <div class="vgt-start-pin-btn" onclick="event.stopPropagation(); event.preventDefault(); VGTDeskEngine.togglePinApp('${key}', true)" title="An Start anheften">📍</div>
                        <div class="vgt-start-icon-tile ${app.color}">
                            ${iconHTML}
                        </div>
                        <span class="vgt-start-label">${this.escapeHTML(app.title)}</span>
                    `;
                    otherGrid.appendChild(item);
                }
            }
        }

        // Re-run filtering to update section headers (if search is empty, they will show correctly)
        this.filterStartMenu();
    },

    matrixInterval: null,
    triggerEasterEgg() {
        this.playSound('startup');
        
        const container = document.getElementById('vgt-about-matrix-container');
        if (!container) return;
        
        const isHidden = container.classList.contains('hidden');
        if (!isHidden) {
            container.classList.add('hidden');
            if (this.matrixInterval) {
                clearInterval(this.matrixInterval);
                this.matrixInterval = null;
            }
            return;
        }
        
        container.classList.remove('hidden');
        
        const canvas = document.getElementById('vgt-matrix-canvas');
        if (!canvas) return;
        
        const ctx = canvas.getContext('2d');
        const width = canvas.width = container.clientWidth || 430;
        const height = canvas.height = 150;
        
        const cols = Math.floor(width / 10) + 1;
        const ypos = Array(cols).fill(0);
        
        ctx.fillStyle = '#000';
        ctx.fillRect(0, 0, width, height);
        
        const drawMatrix = () => {
            ctx.fillStyle = 'rgba(0, 0, 0, 0.05)';
            ctx.fillRect(0, 0, width, height);
            
            ctx.fillStyle = '#10b981';
            ctx.font = '9pt monospace';
            
            ypos.forEach((y, ind) => {
                const text = String.fromCharCode(33 + Math.floor(Math.random() * 93));
                const x = ind * 10;
                ctx.fillText(text, x, y);
                
                if (y > 100 + Math.random() * 10000) {
                    ypos[ind] = 0;
                } else {
                    ypos[ind] = y + 10;
                }
            });
        };
        
        if (this.matrixInterval) clearInterval(this.matrixInterval);
        this.matrixInterval = setInterval(drawMatrix, 33);
        
        this.addLog("Easteregg: VGT Matrix-Modus gestartet.");
    }
});
