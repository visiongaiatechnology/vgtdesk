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

        const safeUrl = new URL(cleanTargetUrl, window.location.origin);
        safeUrl.searchParams.set('vgt_iframe', 'true');
        const domId = String(id);

        const winEl = document.createElement('div');
        winEl.id = `win-${domId}`;
        winEl.className = 'window absolute vgt-window';
        winEl.style.cssText = `width: 850px; height: 550px; top: 12%; left: 22%; z-index: ${this.activeZIndex + 5};`;
        winEl.addEventListener('click', () => this.focusWindow(id));

        ['n', 's', 'e', 'w', 'nw', 'ne', 'sw', 'se'].forEach((direction) => {
            const handle = document.createElement('div');
            handle.className = `resize-handle resize-handle-${direction}`;
            handle.addEventListener('mousedown', (event) => this.startResize(event, id, direction));
            winEl.appendChild(handle);
        });

        const header = document.createElement('div');
        header.className = 'vgt-window-header cursor-move window-header';

        const dots = document.createElement('div');
        dots.className = 'vgt-window-dots';
        [
            ['dot-rose', () => this.closeWindow(id)],
            ['dot-amber', () => this.minimizeWindow(id)],
            ['dot-emerald', () => this.maximizeWindow(id)]
        ].forEach(([className, handler]) => {
            const dot = document.createElement('span');
            dot.className = `vgt-window-dot ${className}`;
            dot.addEventListener('click', (event) => {
                event.stopPropagation();
                handler();
            });
            dots.appendChild(dot);
        });

        const titleEl = document.createElement('span');
        titleEl.className = 'vgt-window-title';
        titleEl.textContent = title;

        const badgeWrap = document.createElement('div');
        badgeWrap.className = 'vgt-window-badge-wrap';
        const spinner = document.createElement('div');
        spinner.id = `spinner-${domId}`;
        spinner.className = 'spinner-vgt';
        spinner.style.display = 'block';
        const badge = document.createElement('span');
        badge.className = 'vgt-badge-item vgt-accent-badge-item';
        badge.textContent = 'Dynamic';
        badgeWrap.append(spinner, badge);

        header.append(dots, titleEl, badgeWrap);

        const iframeBox = document.createElement('div');
        iframeBox.className = 'flex-1 iframe-container relative';
        const overlay = document.createElement('div');
        overlay.className = 'drag-overlay absolute inset-0 hidden z-50 bg-transparent';
        const iframe = document.createElement('iframe');
        iframe.id = `iframe-${domId}`;
        iframe.src = safeUrl.toString();
        iframe.dataset.src = safeUrl.toString();
        iframe.dataset.loaded = 'true';
        iframe.addEventListener('load', () => this.handleIframeLoaded(id));
        iframeBox.append(overlay, iframe);

        winEl.append(header, iframeBox);
        container.appendChild(winEl);
        this.attachSnapMenuListeners(winEl);

        this.addDynamicTaskToDock(id, title);

        this.activeWindows[id] = true;
        this.minimizedWindows[id] = false;

        this.makeWindowDraggable(winEl);
        this.applySingleWindowSettings(id);
        this.focusWindow(id);
        this.showInTaskbar(id, true);
        this.updateDockIndicators();
    },

    addDynamicTaskToDock(id, title) {
        const taskBar = document.getElementById('vgt-dynamic-task-bar');
        if (!taskBar) return;

        const domId = String(id);
        const taskEl = document.createElement('div');
        taskEl.className = 'vgt-dock-item';
        taskEl.id = `dock-task-${domId}`;
        taskEl.addEventListener('click', () => this.handleDockClick(id));

        const icon = document.createElement('div');
        icon.className = 'vgt-dock-icon vgt-color-gradient-settings';
        const label = document.createElement('span');
        label.className = 'vgt-icon-label';
        label.style.cssText = 'margin-top:0; padding:0; text-shadow:none;';
        label.textContent = title.substring(0, 2).toUpperCase();
        icon.appendChild(label);

        const tooltip = document.createElement('span');
        tooltip.className = 'vgt-dock-tooltip';
        tooltip.textContent = title;

        const indicator = document.createElement('span');
        indicator.className = 'vgt-dock-indicator';
        indicator.id = `indicator-${domId}`;

        taskEl.append(icon, tooltip, indicator);
        taskBar.appendChild(taskEl);
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
        if (id === 'task-manager') {
            this.startTaskManagerPolling();
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
        if (id === 'task-manager') {
            this.stopTaskManagerPolling();
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
        if (id === 'task-manager') {
            this.stopTaskManagerPolling();
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
        if (id === 'task-manager') {
            this.startTaskManagerPolling();
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
                const iframeColors = {
                    indigo:  { main: '#6366f1', hover: '#818cf8', rgba15: 'rgba(99, 102, 241, 0.15)',  rgba8: 'rgba(99, 102, 241, 0.08)' },
                    emerald: { main: '#10b981', hover: '#34d399', rgba15: 'rgba(16, 185, 129, 0.15)', rgba8: 'rgba(16, 185, 129, 0.08)' },
                    cyan:    { main: '#06b6d4', hover: '#22d3ee', rgba15: 'rgba(6, 182, 212, 0.15)',   rgba8: 'rgba(6, 182, 212, 0.08)' },
                    amber:   { main: '#f59e0b', hover: '#fbbf24', rgba15: 'rgba(245, 158, 11, 0.15)', rgba8: 'rgba(245, 158, 11, 0.08)' },
                    rose:    { main: '#f43f5e', hover: '#fb7185', rgba15: 'rgba(244, 63, 94, 0.15)',   rgba8: 'rgba(244, 63, 94, 0.08)' },
                    gold:    { main: '#daa520', hover: '#ffd700', rgba15: 'rgba(218, 165, 32, 0.15)',  rgba8: 'rgba(218, 165, 32, 0.08)' },
                    purple:  { main: '#a855f7', hover: '#c084fc', rgba15: 'rgba(168, 85, 247, 0.15)', rgba8: 'rgba(168, 85, 247, 0.08)' },
                    violet:  { main: '#8b5cf6', hover: '#a78bfa', rgba15: 'rgba(139, 92, 246, 0.15)', rgba8: 'rgba(139, 92, 246, 0.08)' },
                    neon:    { main: '#22c55e', hover: '#4ade80', rgba15: 'rgba(34, 197, 94, 0.15)',   rgba8: 'rgba(34, 197, 94, 0.08)' }
                };
                const colors = iframeColors[this.accentColor] || iframeColors['indigo'];
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
        if (wall) wall.style.backgroundImage = `url("${clean.replace(/"/g, '%22')}")`;
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
            indigo:  { main: '#6366f1', hover: '#818cf8',  rgba15: 'rgba(99, 102, 241, 0.15)',   rgba8: 'rgba(99, 102, 241, 0.08)', rgb: '99, 102, 241' },
            emerald: { main: '#10b981', hover: '#34d399',  rgba15: 'rgba(16, 185, 129, 0.15)',   rgba8: 'rgba(16, 185, 129, 0.08)', rgb: '16, 185, 129' },
            cyan:    { main: '#06b6d4', hover: '#22d3ee',  rgba15: 'rgba(6, 182, 212, 0.15)',    rgba8: 'rgba(6, 182, 212, 0.08)', rgb: '6, 182, 212' },
            amber:   { main: '#f59e0b', hover: '#fbbf24',  rgba15: 'rgba(245, 158, 11, 0.15)',   rgba8: 'rgba(245, 158, 11, 0.08)', rgb: '245, 158, 11' },
            rose:    { main: '#f43f5e', hover: '#fb7185',  rgba15: 'rgba(244, 63, 94, 0.15)',    rgba8: 'rgba(244, 63, 94, 0.08)', rgb: '244, 63, 94' },
            // New accent colors
            gold:    { main: '#daa520', hover: '#ffd700',  rgba15: 'rgba(218, 165, 32, 0.15)',   rgba8: 'rgba(218, 165, 32, 0.08)', rgb: '218, 165, 32' },
            purple:  { main: '#a855f7', hover: '#c084fc',  rgba15: 'rgba(168, 85, 247, 0.15)',   rgba8: 'rgba(168, 85, 247, 0.08)', rgb: '168, 85, 247' },
            violet:  { main: '#8b5cf6', hover: '#a78bfa',  rgba15: 'rgba(139, 92, 246, 0.15)',   rgba8: 'rgba(139, 92, 246, 0.08)', rgb: '139, 92, 246' },
            neon:    { main: '#22c55e', hover: '#4ade80',  rgba15: 'rgba(34, 197, 94, 0.15)',    rgba8: 'rgba(34, 197, 94, 0.08)', rgb: '34, 197, 94' }
        };

        const colors = tailwindColors[color] || tailwindColors['indigo'];
        const hex = colors.main;

        // Set dynamic accent variables on root
        const root = document.documentElement;
        root.style.setProperty('--vgt-accent-color', colors.main);
        root.style.setProperty('--vgt-accent-rgba15', colors.rgba15);
        root.style.setProperty('--vgt-accent-rgba8', colors.rgba8);
        root.style.setProperty('--vgt-accent-rgb', colors.rgb);

        const dot = document.getElementById('vgt-topbar-dot');
        const title = document.getElementById('vgt-topbar-title');
        const welcomeTitle = document.getElementById('welcome-title-accent');
        const settingsTitle = document.getElementById('settings-title-accent');
        const badge = document.getElementById('vgt-accent-badge');

        // Special Gold Mode: Use a gradient for the topbar dot & glowing gold text
        if (color === 'gold') {
            const goldGrad = 'linear-gradient(135deg, #b8860b 0%, #ffd700 40%, #daa520 70%, #b8860b 100%)';
            if (dot) {
                dot.style.background = goldGrad;
                dot.style.backgroundColor = '';
                dot.style.boxShadow = '0 0 8px rgba(255, 215, 0, 0.6)';
            }
            if (title) { title.style.backgroundImage = goldGrad; title.style.webkitBackgroundClip = 'text'; title.style.webkitTextFillColor = 'transparent'; title.style.backgroundClip = 'text'; }
            if (welcomeTitle) { welcomeTitle.style.backgroundImage = goldGrad; welcomeTitle.style.webkitBackgroundClip = 'text'; welcomeTitle.style.webkitTextFillColor = 'transparent'; welcomeTitle.style.backgroundClip = 'text'; }
            if (settingsTitle) { settingsTitle.style.backgroundImage = goldGrad; settingsTitle.style.webkitBackgroundClip = 'text'; settingsTitle.style.webkitTextFillColor = 'transparent'; settingsTitle.style.backgroundClip = 'text'; }
            // Activate full Gold Mode Easter Egg on shell root
            const shell = document.getElementById('vgt-shell-root');
            if (shell) shell.classList.add('vgt-gold-mode');
        } else {
            // Reset gold-specific styles for other colors
            if (dot) { dot.style.background = ''; dot.style.backgroundColor = hex; dot.style.boxShadow = ''; }
            if (title) { title.style.backgroundImage = ''; title.style.webkitBackgroundClip = ''; title.style.webkitTextFillColor = ''; title.style.backgroundClip = ''; title.style.color = hex; }
            if (welcomeTitle) { welcomeTitle.style.backgroundImage = ''; welcomeTitle.style.webkitBackgroundClip = ''; welcomeTitle.style.webkitTextFillColor = ''; welcomeTitle.style.backgroundClip = ''; welcomeTitle.style.color = hex; }
            if (settingsTitle) { settingsTitle.style.backgroundImage = ''; settingsTitle.style.webkitBackgroundClip = ''; settingsTitle.style.webkitTextFillColor = ''; settingsTitle.style.backgroundClip = ''; settingsTitle.style.color = hex; }
            // Deactivate Gold Mode Easter Egg
            const shell = document.getElementById('vgt-shell-root');
            if (shell) shell.classList.remove('vgt-gold-mode');
        }
        
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
                    iframeDoc.documentElement.style.setProperty('--vgt-accent-rgb', colors.rgb);
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
            sound_pack: 'synth_default',
            folders: {},
            auto_redirect: false,
            layout_style: 'macos',
            active_preset: '',
            first_run_completed: false
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
        this.userSettings.first_run_completed = this._isTruthy(this.userSettings.first_run_completed);

        const savedWall = this.userSettings.wallpaper || 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?q=80&w=2564&auto=format&fit=crop';
        this.changeWallpaper(savedWall, false);

        const savedAccent = this.userSettings.accent_color || 'indigo';
        this.changeAccentColor(savedAccent, false);

        const checkbox = document.getElementById('blur-toggle');
        if (checkbox) checkbox.checked = this.userSettings.blur;

        this.applyBlur(this.userSettings.blur, false);

        const redirectCheckbox = document.getElementById('redirect-toggle');
        if (redirectCheckbox) redirectCheckbox.checked = this.userSettings.auto_redirect;

        const dattrackToggle = document.getElementById('vgt-cc-dattrack-toggle');
        if (dattrackToggle) dattrackToggle.checked = (typeof vgtConfig !== 'undefined' && vgtConfig.dattrackEnabled);

        this.renderIntegratedModules();

        this.applyWidgetsVisibility();
        this.applyIconsVisibility();
        this.applySavedWindowSettings();

        const savedFontSize = this.userSettings.font_size || 14;
        this.applyFontSize(savedFontSize, false);
        const slider = document.getElementById('vgt-font-size-slider');
        if (slider) slider.value = savedFontSize;

        this.applyShortcuts();

        // Restore active preset UI state
        if (this.userSettings.active_preset) {
            this.updatePresetUI(this.userSettings.active_preset);
        }

        // Restore sound pack UI selection state
        const soundPackSelect = document.getElementById('vgt-sound-pack-select');
        if (soundPackSelect && this.userSettings.sound_pack) {
            soundPackSelect.value = this.userSettings.sound_pack;
        }
    },

    changeSoundPack(pack, doSync = true) {
        this.userSettings.sound_pack = pack;
        if (doSync) {
            this.saveUserSetting('sound_pack', pack);
            this.playSound('startup');
        }
    },

    toggleBlur() {
        const checkbox = document.getElementById('blur-toggle');
        const state = checkbox ? checkbox.checked : true;
        this.userSettings.blur = state;
        this.saveUserSetting('blur', state);
        this.applyBlur(state);
        this.updateControlCenterToggles();
    },


    renderIntegratedModules() {
        const list = document.getElementById('vgt-cc-module-list');
        if (!list) return;

        const modules = (typeof vgtConfig !== 'undefined' && Array.isArray(vgtConfig.integratedModules)) ? vgtConfig.integratedModules : [];
        list.replaceChildren();

        if (!modules.length) {
            const empty = document.createElement('div');
            empty.className = 'vgt-cc-card';
            empty.textContent = 'Keine integrierten Module registriert.';
            list.appendChild(empty);
            return;
        }

        modules.forEach((moduleInfo) => {
            const card = document.createElement('div');
            card.className = 'vgt-cc-toggle-card vgt-cc-module-card';

            const info = document.createElement('div');
            info.className = 'vgt-cc-toggle-info';

            const title = document.createElement('span');
            title.className = 'vgt-cc-toggle-title';
            title.textContent = moduleInfo.label || moduleInfo.key;

            const desc = document.createElement('span');
            desc.className = 'vgt-cc-toggle-desc';
            desc.textContent = moduleInfo.locked && moduleInfo.lockedReason ? moduleInfo.lockedReason : (moduleInfo.description || '');

            const state = document.createElement('span');
            state.className = moduleInfo.enabled ? 'vgt-cc-module-state active' : 'vgt-cc-module-state inactive';
            state.textContent = moduleInfo.enabled ? 'AKTIV' : 'INAKTIV';

            info.append(title, desc, state);

            const input = document.createElement('input');
            input.type = 'checkbox';
            input.className = 'vgt-toggle-switch vgt-integrated-module-toggle';
            input.checked = Boolean(moduleInfo.enabled);
            input.disabled = Boolean(moduleInfo.locked || !moduleInfo.available);
            input.dataset.module = moduleInfo.key || '';
            input.addEventListener('change', () => this.toggleIntegratedModule(input));

            card.append(info, input);
            list.appendChild(card);
        });
    },

    toggleIntegratedModule(input) {
        if (!input || typeof vgtConfig === 'undefined' || !vgtConfig.ajaxUrl) return;

        const previousState = !input.checked;
        const moduleKey = input.dataset.module || '';
        input.disabled = true;

        const formData = new FormData();
        formData.append('action', 'vgt_toggle_integrated_module');
        formData.append('nonce', vgtConfig.nonce);
        formData.append('module', moduleKey);
        formData.append('enabled', input.checked ? 'true' : 'false');

        fetch(vgtConfig.ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                input.checked = previousState;
                alert('Fehler: ' + data.data);
                return;
            }

            if (Array.isArray(data.data.modules)) {
                vgtConfig.integratedModules = data.data.modules;
            }
            if (data.data.module === 'dattrack') {
                vgtConfig.dattrackEnabled = Boolean(data.data.enabled);
            }

            this.addLog(data.data.message || 'Modulstatus aktualisiert.');
            this.renderIntegratedModules();
            this.updateDiagnostics();

            if (data.data.reload) {
                setTimeout(() => location.reload(), 450);
            }
        })
        .catch((err) => {
            input.checked = previousState;
            console.error('Module Sync-Fehler:', err);
        })
        .finally(() => {
            input.disabled = false;
        });
    },

    toggleDattrack() {
        this.playSound('click');
        const checkbox = document.getElementById('vgt-cc-dattrack-toggle');
        const state = checkbox ? checkbox.checked : false;

        const formData = new FormData();
        formData.append('action', 'vgt_toggle_dattrack');
        formData.append('nonce', vgtConfig.nonce);

        if (checkbox) checkbox.disabled = true;

        fetch(vgtConfig.ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (checkbox) checkbox.disabled = false;
            if (data.success) {
                const isEnabled = data.data.enabled;
                if (typeof vgtConfig !== 'undefined') {
                    vgtConfig.dattrackEnabled = isEnabled;
                }
                this.addLog(data.data.message || (isEnabled ? "Dattrack erfolgreich aktiviert." : "Dattrack erfolgreich deaktiviert."));
                
                setTimeout(() => {
                    location.reload();
                }, 400);
            } else {
                if (checkbox) checkbox.checked = !state;
                alert(`Fehler: ${data.data}`);
            }
        })
        .catch(err => {
            if (checkbox) {
                checkbox.disabled = false;
                checkbox.checked = !state;
            }
            console.error("Dattrack Sync-Fehler:", err);
        });
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
        if (tabName === 'modules') {
            this.renderIntegratedModules();
        }
    },

    diagnosticsInterval: null,

    startDiagnosticsPolling() {
        if (this.diagnosticsInterval) return; // Do not start duplicate intervals
        this.updateDiagnostics();
        this.diagnosticsInterval = setInterval(() => {
            this.updateDiagnostics();
        }, 8000); // Poll at a conservative 8s rate to ensure low footprint
    },

    stopDiagnosticsPolling() {
        const ccVisible = this.activeWindows && this.activeWindows['settings'] && !this.minimizedWindows['settings'];
        const widgetActive = this.isSystemWidgetActive && this.isSystemWidgetActive();
        
        if (!ccVisible && !widgetActive && this.diagnosticsInterval) {
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

                // Update System Widget
                const wCpuVal = document.getElementById('vgt-widget-cpu-val');
                const wCpuBar = document.getElementById('vgt-widget-cpu-bar');
                if (wCpuVal) wCpuVal.textContent = `${diag.cpu}%`;
                if (wCpuBar) {
                    wCpuBar.style.width = `${diag.cpu}%`;
                    if (diag.cpu > 80) {
                        wCpuBar.style.backgroundColor = '#f43f5e';
                    } else if (diag.cpu > 50) {
                        wCpuBar.style.backgroundColor = '#f59e0b';
                    } else {
                        wCpuBar.style.backgroundColor = 'var(--vgt-accent-color)';
                    }
                }

                const wRamVal = document.getElementById('vgt-widget-ram-val');
                const wRamBar = document.getElementById('vgt-widget-ram-bar');
                if (wRamVal) {
                    const usageMB = Math.round(diag.ram_usage / 1024 / 1024);
                    const limitMB = diag.ram_limit === -1 ? '∞' : Math.round(diag.ram_limit / 1024 / 1024);
                    wRamVal.textContent = `${usageMB} MB / ${limitMB} MB`;
                    if (wRamBar) {
                        const pct = diag.ram_limit === -1 ? 0 : Math.min(100, Math.round((diag.ram_usage / diag.ram_limit) * 100));
                        wRamBar.style.width = `${pct}%`;
                        if (pct > 80) {
                            wRamBar.style.backgroundColor = '#f43f5e';
                        } else {
                            wRamBar.style.backgroundColor = 'var(--vgt-accent-color)';
                        }
                    }
                }

                const wTgStatus = document.getElementById('vgt-widget-tg-status');
                if (wTgStatus) {
                    wTgStatus.textContent = diag.throne_guard.active ? 'Aktiv' : 'Inaktiv';
                    wTgStatus.style.color = diag.throne_guard.active ? '#10b981' : '#f43f5e';
                }

                const wSentinelStatus = document.getElementById('vgt-widget-sentinel-status');
                if (wSentinelStatus) {
                    wSentinelStatus.textContent = diag.sentinel.active ? 'Aktiv' : 'Inaktiv';
                    wSentinelStatus.style.color = diag.sentinel.active ? '#10b981' : '#f43f5e';
                }

                const wBansStatus = document.getElementById('vgt-widget-bans-status');
                if (wBansStatus) {
                    wBansStatus.textContent = `${diag.total_bans} IPs`;
                }

                if (Array.isArray(diag.integrated_modules) && typeof vgtConfig !== 'undefined') {
                    vgtConfig.integratedModules = diag.integrated_modules;
                    this.renderIntegratedModules();
                }

                if (this.updateWidgetData) {
                    this.updateWidgetData(diag);
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
        const sizeNum = parseFloat(size);
        // Set the font-size CSS variable (for elements that use it without !important)
        document.documentElement.style.setProperty('--vgt-font-size', `${sizeNum}px`);
        // Set zoom ratio relative to the base size (14px = 1.0x).
        // The zoom property on #vgt-shell-root scales ALL children proportionally,
        // including those with hardcoded !important px font-sizes.
        const zoomRatio = parseFloat((sizeNum / 14).toFixed(4));
        document.documentElement.style.setProperty('--vgt-font-zoom', zoomRatio.toString());
        
        const shell = document.getElementById('vgt-shell-root');
        if (shell) {
            shell.style.setProperty('width', `${window.innerWidth / zoomRatio}px`, 'important');
            shell.style.setProperty('height', `${window.innerHeight / zoomRatio}px`, 'important');
        }

        const label = document.getElementById('vgt-font-size-label');
        if (label) label.textContent = `${sizeNum}px`;
        if (sync) {
            this.userSettings.font_size = sizeNum;
            this.saveUserSetting('font_size', sizeNum);
            this.addLog(`Bildschirmauflösung auf ${sizeNum}px gesetzt.`);
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
    },

    /**
     * Workspace Presets (Phase 4)
     * One-click profiles that reconfigure the entire desktop environment.
     * @param {string} presetName - 'publisher' | 'security' | 'developer' | 'minimal'
     */
    applyWorkspacePreset(presetName) {
        const PRESETS = {
            publisher: {
                label:          'Publisher Mode',
                accent_color:   'emerald',
                layout_style:   'macos',
                widgets_visible: true,
                icons_visible:   true,
                log_msg:        'Publisher Mode aktiviert — Content-Erstellung bereit.'
            },
            security: {
                label:          'Security Mode',
                accent_color:   'rose',
                layout_style:   'macos',
                widgets_visible: true,
                icons_visible:   true,
                log_msg:        'Security Mode aktiviert — Sentinel & Throne Guard priorisiert.'
            },
            developer: {
                label:          'Developer Mode',
                accent_color:   'violet',
                layout_style:   'linux',
                widgets_visible: true,
                icons_visible:   true,
                log_msg:        'Developer Mode aktiviert — Code & Debug Umgebung bereit.'
            },
            minimal: {
                label:          'Minimal Mode',
                accent_color:   'indigo',
                layout_style:   'macos',
                widgets_visible: false,
                icons_visible:   false,
                log_msg:        'Minimal Mode aktiviert — Maximale Arbeitsfläche.'
            }
        };

        const preset = PRESETS[presetName];
        if (!preset) {
            console.warn(`VGT Presets: Unbekanntes Preset "${presetName}"`);
            return;
        }

        this.playSound('click');

        // 1. Apply accent color
        this.changeAccentColor(preset.accent_color, true);

        // 2. Apply layout
        if (typeof this.changeLayoutStyle === 'function') {
            this.changeLayoutStyle(preset.layout_style);
        }

        // 3. Apply widget visibility
        this.userSettings.widgets_visible = preset.widgets_visible;
        this.saveUserSetting('widgets_visible', preset.widgets_visible);
        if (typeof this.applyWidgetsVisibility === 'function') {
            this.applyWidgetsVisibility();
        }

        // 4. Apply icon visibility
        this.userSettings.icons_visible = preset.icons_visible;
        this.saveUserSetting('icons_visible', preset.icons_visible);
        if (typeof this.applyIconsVisibility === 'function') {
            this.applyIconsVisibility();
        }

        // 5. Save the active preset key for UI persistence
        this.userSettings.active_preset = presetName;
        this.saveUserSetting('active_preset', presetName);

        // 6. Update preset card UI
        this.updatePresetUI(presetName);

        // 7. Log
        this.addLog(`🎭 ${preset.log_msg}`);

        // 8. Show brief toast notification
        this.showPresetToast(preset.label);
    },

    /**
     * Update preset card UI to highlight the active preset.
     */
    updatePresetUI(activePreset) {
        document.querySelectorAll('.vgt-preset-card').forEach(card => {
            const isActive = card.dataset.preset === activePreset;
            card.classList.toggle('is-active', isActive);
        });
    },

    /**
     * Show a brief, dismissing toast notification for preset activation.
     */
    showPresetToast(label) {
        let toast = document.getElementById('vgt-preset-toast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'vgt-preset-toast';
            toast.style.cssText = [
                'position:fixed', 'bottom:100px', 'left:50%',
                'transform:translateX(-50%) translateY(0)',
                'background:rgba(15,23,42,0.92)',
                'border:1px solid var(--vgt-accent-color)',
                'color:#ffffff',
                'font-size:12px',
                'font-weight:700',
                'padding:10px 22px',
                'border-radius:50px',
                'z-index:999999',
                'box-shadow:0 8px 30px rgba(0,0,0,0.5),0 0 15px var(--vgt-accent-rgba15)',
                'backdrop-filter:blur(20px)',
                'transition:opacity 0.4s ease,transform 0.4s ease',
                'pointer-events:none',
                'white-space:nowrap'
            ].join(';');
            const shell = document.getElementById('vgt-shell-root') || document.body;
            shell.appendChild(toast);
        }
        // Sanitize label text
        toast.textContent = `🎭 ${label.substring(0, 50)} aktiviert`;
        toast.style.opacity = '1';
        toast.style.transform = 'translateX(-50%) translateY(0)';

        clearTimeout(toast._hideTimer);
        toast._hideTimer = setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(-50%) translateY(10px)';
        }, 2200);
    }
});
