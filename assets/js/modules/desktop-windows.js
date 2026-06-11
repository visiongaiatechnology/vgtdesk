/**
 * VGT Desktop Module - Window Management
 */

Object.assign(window.VGTDeskEngine, {
    createDynamicWindow(id, title, url) {
        const container = document.getElementById('vgt-dynamic-windows');
        if (!container) return;

        const cleanTargetUrl = this.cleanUrl(url);
        if (cleanTargetUrl === 'about:blank') {
            console.error("VGT Safety Guard: Blockiert verdächtigen dynamischen Link.");
            this.addLog("Sicherheits-Alarm: Ungültige Ziel-URL blockiert.");
            return;
        }

        // Maximale Sicherheit: Verhindert externe Open-Redirects
        try {
            const parsedUrl = new URL(cleanTargetUrl, window.location.origin);
            if (parsedUrl.origin !== window.location.origin) {
                console.error("VGT Safety Guard: Fremde Domain blockiert!");
                this.addLog("Sicherheits-Alarm: Externer Ursprung blockiert.");
                return;
            }
        } catch (e) {
            return;
        }

        const safeUrl = new URL(cleanTargetUrl);
        safeUrl.searchParams.set('vgt_iframe', 'true');

        const escapedId = this.escapeHTML(id);
        const escapedUrl = this.escapeHTML(safeUrl.toString());

        // PATTERN 1.5.F — Render with placeholder and inject text via textContent post-construction
        const windowHtml = `
            <div id="win-${escapedId}" class="window absolute vgt-window" style="width: 850px; height: 550px; top: 12%; left: 22%; z-index: ${this.activeZIndex + 5};" onclick="VGTDeskEngine.focusWindow('${escapedId}')">
                
                <!-- 8 Resize Handles -->
                <div class="resize-handle resize-handle-n" onmousedown="VGTDeskEngine.startResize(event, '${escapedId}', 'n')"></div>
                <div class="resize-handle resize-handle-s" onmousedown="VGTDeskEngine.startResize(event, '${escapedId}', 's')"></div>
                <div class="resize-handle resize-handle-e" onmousedown="VGTDeskEngine.startResize(event, '${escapedId}', 'e')"></div>
                <div class="resize-handle resize-handle-w" onmousedown="VGTDeskEngine.startResize(event, '${escapedId}', 'w')"></div>
                <div class="resize-handle resize-handle-nw" onmousedown="VGTDeskEngine.startResize(event, '${escapedId}', 'nw')"></div>
                <div class="resize-handle resize-handle-ne" onmousedown="VGTDeskEngine.startResize(event, '${escapedId}', 'ne')"></div>
                <div class="resize-handle resize-handle-sw" onmousedown="VGTDeskEngine.startResize(event, '${escapedId}', 'sw')"></div>
                <div class="resize-handle resize-handle-se" onmousedown="VGTDeskEngine.startResize(event, '${escapedId}', 'se')"></div>

                <!-- Titlebar -->
                <div class="vgt-window-header cursor-move window-header">
                    <div class="vgt-window-dots">
                        <span class="vgt-window-dot dot-rose" onclick="VGTDeskEngine.closeWindow('${escapedId}')"></span>
                        <span class="vgt-window-dot dot-amber" onclick="VGTDeskEngine.minimizeWindow('${escapedId}')"></span>
                        <span class="vgt-window-dot dot-emerald" onclick="VGTDeskEngine.maximizeWindow('${escapedId}')"></span>
                    </div>
                    <span class="vgt-window-title"></span>
                    <div class="vgt-window-badge-wrap">
                        <div id="spinner-${escapedId}" class="spinner-vgt" style="display: block;"></div>
                        <span class="vgt-badge-item vgt-accent-badge-item">Dynamic</span>
                    </div>
                </div>
                <!-- Iframe Box -->
                <div class="flex-1 iframe-container relative">
                    <div class="drag-overlay absolute inset-0 hidden z-50 bg-transparent"></div>
                    <iframe 
                        id="iframe-${escapedId}" 
                        src="${escapedUrl}" 
                        data-src="${escapedUrl}"
                        data-loaded="true"
                        onload="window.VGTDeskEngine && VGTDeskEngine.handleIframeLoaded('${escapedId}')">
                    </iframe>
                </div>
            </div>
        `;

        container.insertAdjacentHTML('beforeend', windowHtml);
        
        const winEl = document.getElementById(`win-${escapedId}`);
        if (winEl) {
            winEl.querySelector('.vgt-window-title').textContent = title;
            this.attachSnapMenuListeners(winEl);
        }

        this.addDynamicTaskToDock(id, title);

        this.activeWindows[id] = true;
        this.minimizedWindows[id] = false;

        this.makeWindowDraggable(document.getElementById(`win-${id}`));

        // Übernehme benutzerdefinierte Fenster-Abmessungen falls bereits in den Meta-Daten existent
        this.applySingleWindowSettings(id);

        this.focusWindow(id);
        this.showInTaskbar(id, true);
        this.updateDockIndicators();
    },

    addDynamicTaskToDock(id, title) {
        const taskBar = document.getElementById('vgt-dynamic-task-bar');
        if (!taskBar) return;

        const escapedId = this.escapeHTML(id);

        // PATTERN 1.5.F — Placeholders for user data to avoid innerHTML injection
        const taskHtml = `
            <div class="vgt-dock-item" id="dock-task-${escapedId}" onclick="VGTDeskEngine.handleDockClick('${escapedId}')">
                <div class="vgt-dock-icon vgt-color-gradient-settings">
                    <span class="vgt-icon-label" style="margin-top:0; padding:0; text-shadow:none;"></span>
                </div>
                <span class="vgt-dock-tooltip"></span>
                <span class="vgt-dock-indicator" id="indicator-${escapedId}"></span>
            </div>
        `;
        taskBar.insertAdjacentHTML('beforeend', taskHtml);
        
        const taskEl = document.getElementById(`dock-task-${escapedId}`);
        if (taskEl) {
            taskEl.querySelector('.vgt-icon-label').textContent = title.substring(0, 2).toUpperCase();
            taskEl.querySelector('.vgt-dock-tooltip').textContent = title;
        }
    },

    handleDockClick(id) {
        const win = document.getElementById(`win-${id}`);
        if (!win) return;

        const isOpened = this.activeWindows[id];
        const isMinimized = this.minimizedWindows[id];

        if (!isOpened) {
            this.openWindow(id);
        } else if (isMinimized) {
            this.restoreWindow(id);
        } else if (this.isTopWindow(id)) {
            this.minimizeWindow(id);
        } else {
            this.focusWindow(id);
        }
    },

    isTopWindow(id) {
        const win = document.getElementById(`win-${id}`);
        if (!win || win.classList.contains('hidden')) return false;
        
        const windows = document.querySelectorAll('.window:not(.hidden)');
        let maxZ = 0;
        windows.forEach(w => {
            const z = parseInt(w.style.zIndex) || 0;
            if (z > maxZ) maxZ = z;
        });
        return parseInt(win.style.zIndex) === maxZ;
    },

    openWindow(id) {
        const win = document.getElementById(`win-${id}`);
        if (!win) return;
        this.playSound('click');

        win.classList.remove('hidden');
        win.style.transform = 'none';
        win.style.opacity = '1';
        
        this.activeWindows[id] = true;
        this.minimizedWindows[id] = false;
        
        this.focusWindow(id);
        this.showInTaskbar(id, true);
        this.updateDockIndicators();

        if (id === 'settings') {
            this.startDiagnosticsPolling();
        }

        const iframe = document.getElementById(`iframe-${id}`);
        if (iframe && (iframe.dataset.loaded !== 'true' || iframe.dataset.suspendedUrl)) {
            const spinner = document.getElementById(`spinner-${id}`);
            if (spinner) spinner.style.display = 'block';
            
            let targetUrl = iframe.dataset.suspendedUrl || iframe.dataset.src;
            delete iframe.dataset.suspendedUrl;

            if (targetUrl.indexOf('vgt_iframe') === -1) {
                targetUrl += (targetUrl.indexOf('?') === -1 ? '?' : '&') + 'vgt_iframe=true';
            }
            iframe.src = this.cleanUrl(targetUrl);
            iframe.dataset.loaded = 'true';
        }
    },

    closeWindow(id) {
        const win = document.getElementById(`win-${id}`);
        if (!win) return;
        this.playSound('click');
        win.classList.add('hidden');
        this.activeWindows[id] = false;
        this.minimizedWindows[id] = false;
        this.suspendIframe(id);
        this.showInTaskbar(id, false);
        this.updateDockIndicators();

        if (id === 'settings') {
            this.stopDiagnosticsPolling();
        }
    },

    suspendIframe(id) {
        const iframe = document.getElementById(`iframe-${id}`);
        if (!iframe || iframe.dataset.loaded !== 'true') return;

        let currentUrl = iframe.dataset.src;
        try {
            if (iframe.contentWindow && iframe.contentWindow.location) {
                const href = iframe.contentWindow.location.href;
                if (href && href !== 'about:blank') {
                    currentUrl = href;
                }
            }
        } catch (e) {
            if (iframe.src && iframe.src !== 'about:blank') {
                currentUrl = iframe.src;
            }
        }

        iframe.dataset.suspendedUrl = currentUrl;
        iframe.src = 'about:blank';
        iframe.dataset.loaded = 'false';

        this.addLog(`Fenster '${id}' suspendiert (RAM freigegeben).`);
    },

    focusWindow(id) {
        const win = document.getElementById(`win-${id}`);
        if (!win) return;
        this.activeZIndex += 1;
        win.style.zIndex = this.activeZIndex;
        
        document.querySelectorAll('.vgt-window').forEach(w => {
            w.classList.add('opacity-90');
            w.classList.remove('vgt-window-active');
        });
        win.classList.remove('opacity-90');
        win.classList.add('opacity-100', 'vgt-window-active');
    },

    minimizeWindow(id) {
        const win = document.getElementById(`win-${id}`);
        if (!win) return;
        this.playSound('click');
        
        win.style.transform = "scale(0.8) translateY(120px)";
        win.style.opacity = "0";
        this.minimizedWindows[id] = true;
        
        setTimeout(() => {
            if (this.minimizedWindows[id]) {
                win.classList.add('hidden');
                this.suspendIframe(id);
            }
        }, 250);
        
        this.updateDockIndicators();

        if (id === 'settings') {
            this.stopDiagnosticsPolling();
        }
    },

    restoreWindow(id) {
        const win = document.getElementById(`win-${id}`);
        if (!win) return;
        this.playSound('click');

        win.classList.remove('hidden');
        setTimeout(() => {
            win.style.transform = "none";
            win.style.opacity = "1";
        }, 10);

        this.minimizedWindows[id] = false;
        this.focusWindow(id);
        this.updateDockIndicators();

        if (id === 'settings') {
            this.startDiagnosticsPolling();
        }

        const iframe = document.getElementById(`iframe-${id}`);
        if (iframe && (iframe.dataset.loaded !== 'true' || iframe.dataset.suspendedUrl)) {
            const spinner = document.getElementById(`spinner-${id}`);
            if (spinner) spinner.style.display = 'block';

            let targetUrl = iframe.dataset.suspendedUrl || iframe.dataset.src;
            delete iframe.dataset.suspendedUrl;

            if (targetUrl.indexOf('vgt_iframe') === -1) {
                targetUrl += (targetUrl.indexOf('?') === -1 ? '?' : '&') + 'vgt_iframe=true';
            }
            iframe.src = this.cleanUrl(targetUrl);
            iframe.dataset.loaded = 'true';
        }
    },

    maximizeWindow(id) {
        const win = document.getElementById(`win-${id}`);
        if (!win) return;
        this.playSound('click');

        // Clean snap classes on maximize toggle
        const snapClasses = ['vgt-window-snap-left', 'vgt-window-snap-right', 'vgt-window-snap-topleft', 'vgt-window-snap-bottomleft'];
        win.classList.remove(...snapClasses);

        const root = document.getElementById('vgt-shell-root');
        const isWindows = root && root.classList.contains('vgt-layout-windows');
        const isLinux = root && root.classList.contains('vgt-layout-linux');

        let targetWidth, targetHeight, targetTop, targetLeft;

        if (isWindows) {
            targetWidth = "100%";
            targetHeight = "calc(100% + 4px)";
            targetTop = "0px";
            targetLeft = "0px";
        } else if (isLinux) {
            targetWidth = "calc(100% - 80px)";
            targetHeight = "100%";
            targetTop = "-16px";
            targetLeft = "80px";
        } else { // macOS / default
            targetWidth = "100%";
            targetHeight = "100%";
            targetTop = "-16px";
            targetLeft = "0px";
        }

        if (win.classList.contains('vgt-window-maximized')) {
            win.classList.remove('vgt-window-maximized');
            win.style.width = win.dataset.prevWidth || "850px";
            win.style.height = win.dataset.prevHeight || "550px";
            win.style.top = win.dataset.prevTop || "10%";
            win.style.left = win.dataset.prevLeft || "20%";
        } else {
            win.dataset.prevWidth = win.style.width;
            win.dataset.prevHeight = win.style.height;
            win.dataset.prevTop = win.style.top;
            win.dataset.prevLeft = win.style.left;

            win.classList.add('vgt-window-maximized');
            win.style.width = targetWidth;
            win.style.height = targetHeight;
            win.style.top = targetTop;
            win.style.left = targetLeft;
        }
        
        const winId = win.id.replace('win-', '');
        this.saveWindowPosition(winId, win.style.left, win.style.top, win.style.width, win.style.height);
    },

    showInTaskbar(id, show) {
        const task = document.getElementById(`dock-task-${id}`);
        if (!task) return;
        if (show) {
            task.classList.remove('hidden');
        } else {
            task.classList.add('hidden');
        }
    },

    handleIframeLoaded(key) {
        const spinner = document.getElementById(`spinner-${key}`);
        if (spinner) spinner.style.display = 'none';

        const iframe = document.getElementById(`iframe-${key}`);
        this.interceptIframeNavigations(iframe, key);
        
        // Sync active accent color on iframe load
        try {
            const iframeWindow = iframe.contentWindow;
            const iframeDoc = iframe.contentDocument || iframeWindow.document;
            if (iframeDoc && iframeDoc.documentElement) {
                const tailwindColors = {
                    indigo: { main: '#6366f1', hover: '#818cf8', rgba15: 'rgba(99, 102, 241, 0.15)', rgba8: 'rgba(99, 102, 241, 0.08)' },
                    emerald: { main: '#10b981', hover: '#34d399', rgba15: 'rgba(16, 185, 129, 0.15)', rgba8: 'rgba(16, 185, 129, 0.08)' },
                    cyan: { main: '#06b6d4', hover: '#22d3ee', rgba15: 'rgba(6, 182, 212, 0.15)', rgba8: 'rgba(6, 182, 212, 0.08)' },
                    amber: { main: '#f59e0b', hover: '#fbbf24', rgba15: 'rgba(245, 158, 11, 0.15)', rgba8: 'rgba(245, 158, 11, 0.08)' },
                    rose: { main: '#f43f5e', hover: '#fb7185', rgba15: 'rgba(244, 63, 94, 0.15)', rgba8: 'rgba(244, 63, 94, 0.08)' }
                };
                const colors = tailwindColors[this.accentColor] || tailwindColors['indigo'];
                iframeDoc.documentElement.style.setProperty('--vgt-accent', colors.main);
                iframeDoc.documentElement.style.setProperty('--vgt-accent-hover', colors.hover);
                iframeDoc.documentElement.style.setProperty('--vgt-accent-rgba15', colors.rgba15);
                iframeDoc.documentElement.style.setProperty('--vgt-accent-rgba8', colors.rgba8);
            }
        } catch (err) {
            // cross-origin
        }
    },

    interceptIframeNavigations(iframe, key) {
        try {
            const iframeWindow = iframe.contentWindow;
            const iframeDoc = iframe.contentDocument || iframeWindow.document;
            if (!iframeDoc) return;

            // Focus window on click inside iframe
            iframeDoc.addEventListener('mousedown', () => {
                window.parent.VGTDeskEngine.focusWindow(key);
            });

            // Klicks auf Links abfangen und "vgt_iframe=true" anhängen
            iframeDoc.addEventListener('click', (e) => {
                const anchor = e.target.closest('a');
                if (!anchor) return;

                // Verhindert das Ausbrechen aus dem Iframe bei target="_top" oder target="_parent"
                if (anchor.target === '_top' || anchor.target === '_parent') {
                    anchor.target = '_self';
                }

                const href = anchor.getAttribute('href');
                if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;

                try {
                    const targetUrl = new URL(href, iframeWindow.location.href);
                    if (targetUrl.origin === window.location.origin) {
                        if (!targetUrl.searchParams.has('vgt_iframe')) {
                            targetUrl.searchParams.set('vgt_iframe', 'true');
                            anchor.href = targetUrl.toString();
                        }
                    }
                } catch (err) {
                    // Ignoriere fehlerhafte relative Pfade
                }
            }, true);

            // Formularabsendungen abfangen und "vgt_iframe=true" anhängen
            iframeDoc.addEventListener('submit', (e) => {
                const form = e.target;
                if (!form) return;

                const action = form.getAttribute('action') || iframeWindow.location.href;
                try {
                    const targetUrl = new URL(action, iframeWindow.location.href);
                    if (targetUrl.origin === window.location.origin) {
                        if (form.method.toLowerCase() === 'get') {
                            if (!form.querySelector('input[name="vgt_iframe"]')) {
                                const hiddenInput = iframeDoc.createElement('input');
                                hiddenInput.type = 'hidden';
                                hiddenInput.name = 'vgt_iframe';
                                hiddenInput.value = 'true';
                                form.appendChild(hiddenInput);
                            }
                        } else {
                            // POST actions erhalten URL parameter Erweiterung
                            if (!targetUrl.searchParams.has('vgt_iframe')) {
                                targetUrl.searchParams.set('vgt_iframe', 'true');
                                form.setAttribute('action', targetUrl.toString());
                            }
                        }
                    }
                } catch (err) {
                    // Ignoriere
                }
            }, true);

        } catch (e) {
            console.warn("Same-Origin iFrame Navigation-Interceptor eingeschränkt.");
        }
    },

    updateDockIndicators() {
        for (let id in this.activeWindows) {
            const indicator = document.getElementById(`indicator-${id}`);
            if (indicator) {
                if (this.activeWindows[id]) {
                    indicator.style.opacity = '1';
                    if (this.minimizedWindows[id]) {
                        indicator.className = 'vgt-dock-indicator vgt-bg-slate-500';
                    } else {
                        indicator.className = `vgt-dock-indicator vgt-bg-${this.accentColor}-400`;
                    }
                } else {
                    indicator.style.opacity = '0';
                }
            }
        }
    },

    changeWallpaper(url, doSync = true) {
        const clean = this.cleanUrl(url);
        if (clean === 'about:blank') {
            console.warn("VGT Safety Guard: Ungültige Wallpaper-URL blockiert.");
            return;
        }

        const wall = document.getElementById('vgt-wallpaper');
        if (wall) wall.style.backgroundImage = `url('${clean}')`;
        if (doSync) {
            this.saveUserSetting('wallpaper', clean);
        }
    },

    applyCustomWallpaper() {
        const urlInput = document.getElementById('vgt-custom-wall-url');
        if (urlInput && urlInput.value) {
            this.changeWallpaper(urlInput.value);
        }
    },

    changeAccentColor(color, doSync = true) {
        this.accentColor = color;
        if (doSync) {
            this.saveUserSetting('accent_color', color);
        }
        this.applyAccentStyles();
    },

    applyAccentStyles() {
        const color = this.accentColor;
        const tailwindColors = {
            indigo: { main: '#6366f1', hover: '#818cf8', rgba15: 'rgba(99, 102, 241, 0.15)', rgba8: 'rgba(99, 102, 241, 0.08)' },
            emerald: { main: '#10b981', hover: '#34d399', rgba15: 'rgba(16, 185, 129, 0.15)', rgba8: 'rgba(16, 185, 129, 0.08)' },
            cyan: { main: '#06b6d4', hover: '#22d3ee', rgba15: 'rgba(6, 182, 212, 0.15)', rgba8: 'rgba(6, 182, 212, 0.08)' },
            amber: { main: '#f59e0b', hover: '#fbbf24', rgba15: 'rgba(245, 158, 11, 0.15)', rgba8: 'rgba(245, 158, 11, 0.08)' },
            rose: { main: '#f43f5e', hover: '#fb7185', rgba15: 'rgba(244, 63, 94, 0.15)', rgba8: 'rgba(244, 63, 94, 0.08)' }
        };

        const colors = tailwindColors[color] || tailwindColors['indigo'];
        const hex = colors.main;

        // Set dynamic accent variables on root
        const root = document.documentElement;
        root.style.setProperty('--vgt-accent-color', colors.main);
        root.style.setProperty('--vgt-accent-rgba15', colors.rgba15);
        root.style.setProperty('--vgt-accent-rgba8', colors.rgba8);

        const dot = document.getElementById('vgt-topbar-dot');
        const title = document.getElementById('vgt-topbar-title');
        const welcomeTitle = document.getElementById('welcome-title-accent');
        const settingsTitle = document.getElementById('settings-title-accent');
        const badge = document.getElementById('vgt-accent-badge');
        
        if (dot) dot.style.backgroundColor = hex;
        if (title) title.style.color = hex;
        if (welcomeTitle) welcomeTitle.style.color = hex;
        if (settingsTitle) settingsTitle.style.color = hex;
        
        if (badge) {
            badge.style.backgroundColor = `${hex}20`;
            badge.style.color = hex;
        }

        document.querySelectorAll('.vgt-accent-badge-item').forEach(b => {
            b.style.backgroundColor = `${hex}20`;
            b.style.color = hex;
            b.style.borderColor = `${hex}20`;
        });

        document.querySelectorAll('#box-accent-1').forEach(b => b.style.color = hex);
        
        // Propagate to all open iframes dynamically
        document.querySelectorAll('iframe').forEach(iframe => {
            try {
                const iframeWindow = iframe.contentWindow;
                const iframeDoc = iframe.contentDocument || iframeWindow.document;
                if (iframeDoc && iframeDoc.documentElement) {
                    iframeDoc.documentElement.style.setProperty('--vgt-accent', colors.main);
                    iframeDoc.documentElement.style.setProperty('--vgt-accent-hover', colors.hover);
                    iframeDoc.documentElement.style.setProperty('--vgt-accent-rgba15', colors.rgba15);
                    iframeDoc.documentElement.style.setProperty('--vgt-accent-rgba8', colors.rgba8);
                }
            } catch (err) {
                // cross-origin
            }
        });

        this.updateDockIndicators();
    },

    loadSavedSettings() {
        this.userSettings = (typeof vgtConfig !== 'undefined' && vgtConfig.userSettings) ? vgtConfig.userSettings : {
            wallpaper: '',
            accent_color: 'indigo',
            blur: true,
            icon_positions: {},
            window_settings: {},
            widget_positions: {},
            widgets_visible: true,
            icons_visible: true,
            audio_enabled: true,
            folders: {},
            auto_redirect: false,
            layout_style: 'macos'
        };

        // RIGOROSE SCHLÜSSEL-KORREKTUR: Verhindert die zerstörerische Array-Konvertierung von leeren PHP-Objekten
        if (!this.userSettings.icon_positions || Array.isArray(this.userSettings.icon_positions)) {
            this.userSettings.icon_positions = {};
        }
        if (!this.userSettings.window_settings || Array.isArray(this.userSettings.window_settings)) {
            this.userSettings.window_settings = {};
        }
        if (!this.userSettings.widget_positions || Array.isArray(this.userSettings.widget_positions)) {
            this.userSettings.widget_positions = {};
        }
        if (!this.userSettings.folders || Array.isArray(this.userSettings.folders)) {
            this.userSettings.folders = {};
        }

        // Strikte Normalisierung auf echte Boolean-Werte
        this.userSettings.blur = this._isTruthy(this.userSettings.blur);
        this.userSettings.widgets_visible = this._isTruthy(this.userSettings.widgets_visible);
        this.userSettings.icons_visible = this._isTruthy(this.userSettings.icons_visible);
        this.userSettings.audio_enabled = this._isTruthy(this.userSettings.audio_enabled);
        this.userSettings.auto_redirect = this._isTruthy(this.userSettings.auto_redirect);

        const savedWall = this.userSettings.wallpaper || 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?q=80&w=2564&auto=format&fit=crop';
        this.changeWallpaper(savedWall, false);

        const savedAccent = this.userSettings.accent_color || 'indigo';
        this.changeAccentColor(savedAccent, false);

        const checkbox = document.getElementById('blur-toggle');
        if (checkbox) checkbox.checked = this.userSettings.blur;
        this.applyBlur(this.userSettings.blur, false);

        const redirectCheckbox = document.getElementById('redirect-toggle');
        if (redirectCheckbox) redirectCheckbox.checked = this.userSettings.auto_redirect;

        this.applyWidgetsVisibility();
        this.applyIconsVisibility();
        this.applySavedWindowSettings();

        const savedFontSize = this.userSettings.font_size || 14;
        this.applyFontSize(savedFontSize, false);
        const slider = document.getElementById('vgt-font-size-slider');
        if (slider) slider.value = savedFontSize;

        this.applyShortcuts();
    },

    toggleBlur() {
        const checkbox = document.getElementById('blur-toggle');
        const state = checkbox ? checkbox.checked : true;
        this.userSettings.blur = state;
        this.saveUserSetting('blur', state);
        this.applyBlur(state);
        this.updateControlCenterToggles();
    },

    applyBlur(state, sync = true) {
        const panels = document.querySelectorAll('.glassmorphism, .window, .vgt-window');
        panels.forEach(p => {
            p.style.backdropFilter = state ? "blur(25px)" : "none";
            p.style.webkitBackdropFilter = state ? "blur(25px)" : "none";
        });
    },

    applySavedWindowSettings() {
        if (!this.userSettings || !this.userSettings.window_settings) return;
        
        for (let winId in this.userSettings.window_settings) {
            this.applySingleWindowSettings(winId);
        }
    },

    applySingleWindowSettings(winId) {
        if (!this.userSettings || !this.userSettings.window_settings) return;
        
        if (!this.userSettings.window_settings || Array.isArray(this.userSettings.window_settings)) {
            this.userSettings.window_settings = {};
        }
        
        const settings = this.userSettings.window_settings[winId];
        if (!settings) return;

        const win = document.getElementById(`win-${winId}`);
        if (win) {
            // Clear any active snap classes first
            win.classList.remove('vgt-window-snap-left', 'vgt-window-snap-right', 'vgt-window-snap-topleft', 'vgt-window-snap-bottomleft');
            
            if (settings.snapClass) {
                win.classList.add(settings.snapClass);
            } else {
                if (settings.left) win.style.left = settings.left;
                if (settings.top) win.style.top = settings.top;
                if (settings.width) win.style.width = settings.width;
                if (settings.height) win.style.height = settings.height;
            }
        }
    },

    applyCustomWallpaperCC() {
        const urlInput = document.getElementById('vgt-custom-wall-url-cc');
        if (urlInput && urlInput.value) {
            this.changeWallpaper(urlInput.value);
        }
    },

    switchCCTab(tabName) {
        const sidebarButtons = document.querySelectorAll('.vgt-cc-nav-item');
        sidebarButtons.forEach(btn => {
            if (btn.getAttribute('data-tab') === tabName) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });

        const panels = document.querySelectorAll('.vgt-cc-tab-panel');
        panels.forEach(p => {
            if (p.id === `vgt-cc-tab-${tabName}`) {
                p.classList.add('active');
            } else {
                p.classList.remove('active');
            }
        });

        this.playSound('click');
        this.addLog(`Wechsel zu Tab '${tabName}'`);

        if (tabName === 'status') {
            this.updateDiagnostics();
        }
    },

    diagnosticsInterval: null,

    startDiagnosticsPolling() {
        this.stopDiagnosticsPolling();
        this.updateDiagnostics();
        this.diagnosticsInterval = setInterval(() => {
            this.updateDiagnostics();
        }, 5000);
    },

    stopDiagnosticsPolling() {
        if (this.diagnosticsInterval) {
            clearInterval(this.diagnosticsInterval);
            this.diagnosticsInterval = null;
        }
    },

    updateDiagnostics() {
        if (typeof vgtConfig === 'undefined' || !vgtConfig.ajaxUrl) return;

        const formData = new FormData();
        formData.append('action', 'vgt_get_diagnostics');
        formData.append('nonce', vgtConfig.nonce);

        fetch(vgtConfig.ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const diag = data.data;

                const cpuVal = document.getElementById('vgt-diag-cpu-val');
                const cpuBar = document.getElementById('vgt-diag-cpu-bar');
                if (cpuVal) cpuVal.textContent = `${diag.cpu}%`;
                if (cpuBar) {
                    cpuBar.style.width = `${diag.cpu}%`;
                    if (diag.cpu > 80) {
                        cpuBar.style.background = '#f43f5e';
                    } else if (diag.cpu > 50) {
                        cpuBar.style.background = '#f59e0b';
                    } else {
                        cpuBar.style.background = 'var(--vgt-accent-color)';
                    }
                }

                const ramVal = document.getElementById('vgt-diag-ram-val');
                const ramBar = document.getElementById('vgt-diag-ram-bar');
                if (ramVal) {
                    const usageMB = Math.round(diag.ram_usage / 1024 / 1024);
                    const limitMB = diag.ram_limit === -1 ? '∞' : Math.round(diag.ram_limit / 1024 / 1024);
                    ramVal.textContent = `${usageMB} MB / ${limitMB} MB`;
                    if (ramBar) {
                        const pct = diag.ram_limit === -1 ? 0 : Math.min(100, Math.round((diag.ram_usage / diag.ram_limit) * 100));
                        ramBar.style.width = `${pct}%`;
                        if (pct > 80) {
                            ramBar.style.background = '#f43f5e';
                        } else {
                            ramBar.style.background = 'var(--vgt-accent-color)';
                        }
                    }
                }

                const dbVal = document.getElementById('vgt-diag-db-val');
                if (dbVal) {
                    const sizeKB = Math.round(diag.db_size / 1024);
                    if (sizeKB > 1024) {
                        dbVal.textContent = `${(sizeKB / 1024).toFixed(2)} MB`;
                    } else {
                        dbVal.textContent = `${sizeKB} KB`;
                    }
                }

                const modeBadge = document.getElementById('vgt-sec-mode-badge');
                if (modeBadge) modeBadge.textContent = diag.throne_guard.mode;

                const tgDot = document.getElementById('vgt-status-tg-dot');
                if (tgDot) {
                    tgDot.className = diag.throne_guard.active ? 'vgt-cc-status-dot active' : 'vgt-cc-status-dot inactive';
                }

                const sDot = document.getElementById('vgt-status-sentinel-dot');
                if (sDot) {
                    sDot.className = diag.sentinel.active ? 'vgt-cc-status-dot active' : 'vgt-cc-status-dot inactive';
                }

                const banBody = document.getElementById('vgt-cc-ban-table-body');
                if (banBody) {
                    if (diag.bans.length === 0) {
                        banBody.innerHTML = `<tr><td colspan="5" style="text-align: center; color: #64748b;">Keine IP-Adressen gesperrt.</td></tr>`;
                    } else {
                        let html = '';
                        diag.bans.forEach(ban => {
                            const safeIp = this.escapeHTML(ban.ip);
                            const safeReason = this.escapeHTML(ban.reason);
                            const safeDate = this.escapeHTML(ban.banned_at);
                            const safeVer = this.escapeHTML(ban.version);
                            html += `
                                <tr>
                                    <td><strong>${safeIp}</strong></td>
                                    <td>${safeReason}</td>
                                    <td>${safeDate}</td>
                                    <td><span class="vgt-badge-item">${safeVer}</span></td>
                                    <td>
                                        <button onclick="VGTDeskEngine.unbanIP('${safeIp}', '${safeVer}')" class="vgt-btn-danger" style="font-size: 10px; padding: 2px 6px;">Entbannen</button>
                                    </td>
                                </tr>
                            `;
                        });
                        banBody.innerHTML = html;
                    }
                }

                const rowCurrent = document.getElementById('vgt-row-current-superkey');
                if (rowCurrent) {
                    rowCurrent.style.display = diag.throne_guard.active ? '' : 'none';
                }
            }
        })
        .catch(err => console.error("Diagnose Sync-Fehler:", err));
    },

    unbanIP(ip, version) {
        if (typeof vgtConfig === 'undefined' || !vgtConfig.ajaxUrl) return;
        if (!confirm(`Möchten Sie die IP-Adresse ${ip} wirklich entbannen?`)) return;

        const formData = new FormData();
        formData.append('action', 'vgt_unban_ip');
        formData.append('nonce', vgtConfig.nonce);
        formData.append('ip', ip);
        formData.append('version', version);

        fetch(vgtConfig.ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                this.playSound('click');
                this.addLog(`IP ${ip} entbannt.`);
                this.updateDiagnostics();
            } else {
                alert(`Fehler: ${data.data}`);
            }
        })
        .catch(err => console.error("Unban Sync-Fehler:", err));
    },

    updateSuperkey() {
        if (typeof vgtConfig === 'undefined' || !vgtConfig.ajaxUrl) return;

        const currentEl = document.getElementById('vgt-current-superkey');
        const newEl = document.getElementById('vgt-new-superkey');

        const currentVal = currentEl ? currentEl.value : '';
        const newVal = newEl ? newEl.value : '';

        if (!newVal || newVal.length < 12) {
            alert('Der neue Superkey muss mindestens 12 Zeichen lang sein.');
            return;
        }

        const formData = new FormData();
        formData.append('action', 'vgt_update_superkey');
        formData.append('nonce', vgtConfig.nonce);
        formData.append('current_superkey', currentVal);
        formData.append('new_superkey', newVal);

        fetch(vgtConfig.ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                this.playSound('click');
                alert(data.data);
                if (currentEl) currentEl.value = '';
                if (newEl) newEl.value = '';
                this.updateDiagnostics();
            } else {
                alert(`Fehler: ${data.data}`);
            }
        })
        .catch(err => console.error("Superkey Sync-Fehler:", err));
    },

    applyFontSize(size, sync = true) {
        document.documentElement.style.setProperty('--vgt-font-size', `${size}px`);
        const label = document.getElementById('vgt-font-size-label');
        if (label) label.textContent = `${size}px`;
        if (sync) {
            this.userSettings.font_size = size;
            this.saveUserSetting('font_size', size);
            this.addLog(`Schriftgröße auf ${size}px gesetzt.`);
        }
    },

    changeFontSize(size) {
        this.applyFontSize(size, true);
    },

    restoreDefaultShortcuts() {
        if (confirm("Möchten Sie alle Hotkeys auf die Standardwerte zurücksetzen?")) {
            const defaults = {
                window_switch: "Alt+KeyQ",
                show_desktop: "Alt+KeyD",
                spotlight: "Control+Space",
                control_center: "Alt+KeyC",
                start_menu: "Alt+KeyS"
            };
            this.userSettings.shortcuts = defaults;
            this.saveUserSetting('shortcuts', defaults);
            this.applyShortcuts();
            this.playSound('click');
            this.addLog("Hotkeys auf Standards zurückgesetzt.");
        }
    },

    applyShortcuts() {
        const shortcuts = this.userSettings.shortcuts || {};
        for (let action in shortcuts) {
            const input = document.getElementById(`vgt-shortcut-${action}`);
            if (input) {
                input.value = this.formatShortcutString(shortcuts[action]);
            }
        }
    },

    formatShortcutString(code) {
        if (!code) return '';
        return code.replace('Key', '').replace('Digit', '').replace(/\+/g, ' + ');
    }
});
