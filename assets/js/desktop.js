/**
 * VGT Desktop Window-Management & Drag-Icon Engine
 */

const VGTDeskEngine = {
    activeZIndex: 100,
    activeWindows: {
        'welcome': false,
        'settings': false
    },
    minimizedWindows: {},
    currentResizeWin: null,
    resizeDirection: '',
    startWidth: 0,
    startHeight: 0,
    startLeft: 0,
    startTop: 0,
    startX: 0,
    startY: 0,
    accentColor: 'indigo',
    preventClick: false, // Verhindert Klicks auf Desktop-Symbole beim Draggen
    userSettings: {},    // Nimmt die profilspezifischen WP-User-Meta-Daten auf

    // Perfektioniertes, kompaktes OS-Raster für bündige Symbolabstände
    gridX: 16,       // Start-Offset Links
    gridY: 16,       // Start-Offset Oben
    cellWidth: 72,   // Breite pro Zelle inkl. Abstand (64px Icon-Breite + 8px Gap)
    cellHeight: 90,  // Höhe pro Zelle inkl. Abstand (80px Icon-Höhe + 10px Gap)

    /**
     * BEHEBUNG DER KLICK-BLOCKIERUNG:
     * Fängt Klicks auf Desktop-Symbole ab. Verhindert das Öffnen während des Draggens,
     * führt jedoch bei regulärem Mausklick blitzschnell zur Ausführung.
     */
    handleIconClick(e, id) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        if (this.preventClick) {
            this.preventClick = false; // Reset-Schutz für nachfolgende Klick-Events
            return;
        }
        this.openWindow(id);
    },

    /**
     * EIN-KLICK KOPIERSYSTEM FÜR CRYPTO-SPENDENADRESSEN (Sicherheits-Bypass):
     */
    copyToClipboard(text, element) {
        const tempInput = document.createElement('input');
        tempInput.value = text;
        document.body.appendChild(tempInput);
        tempInput.select();
        document.execCommand('copy');
        document.body.removeChild(tempInput);
        
        // Visuelles Feedback
        const originalContent = element.innerHTML;
        element.style.borderColor = 'rgba(16, 185, 129, 0.5)';
        element.innerHTML = `
            <span class="vgt-donation-icon">✅</span>
            <span class="vgt-donation-label" style="color: #10b981 !important;">Kopiert!</span>
            <small class="vgt-donation-addr">Adresse in Zwischenablage</small>
        `;
        
        setTimeout(() => {
            element.style.borderColor = '';
            element.innerHTML = originalContent;
        }, 1500);
    },

    /**
     * BENUTZERPROFIL-AJAX SYNC ENGINE:
     * Übermittelt Einstellungsänderungen des WordPress-Users live und asynchron an die DB.
     */
    saveUserSetting(type, value) {
        if (typeof vgtConfig === 'undefined' || !vgtConfig.ajaxUrl) return;

        // Speicher-Objekt aktualisieren
        this.userSettings[type] = value;

        const formData = new FormData();
        formData.append('action', 'vgt_save_user_settings');
        formData.append('nonce', vgtConfig.nonce);
        formData.append('setting_type', type);
        formData.append('value', typeof value === 'object' ? JSON.stringify(value) : String(value));

        fetch(vgtConfig.ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                console.error("VGT Sync-Fehler:", data.data);
            } else {
                console.log(`VGT Sync: '${type}' erfolgreich in DB gespeichert.`);
            }
        })
        .catch(err => console.error("VGT Sync Netzwerkfehler:", err));
    },

    /**
     * SPEICHERT SPEZIFISCHE WINDOW-ABMESSUNGEN UND POSITIONEN:
     */
    saveWindowPosition(winId, left, top, width, height) {
        const win = document.getElementById(`win-${winId}`);
        // Maximierte Fenster nicht in die Standard-Konfiguration aufnehmen
        if (win && (win.style.width === "100%" || win.style.height.includes("calc"))) {
            return;
        }

        // Sicherung gegen Array-Verzerrung
        if (!this.userSettings.window_settings || Array.isArray(this.userSettings.window_settings)) {
            this.userSettings.window_settings = {};
        }

        this.userSettings.window_settings[winId] = { left, top, width, height };
        this.saveUserSetting('window_settings', this.userSettings.window_settings);
    },

    /**
     * BEHEBUNG DES FEHLENDEN GRID-RESETS:
     * Bereinigt gespeicherte Desktop-Icon-Koordinaten und ordnet alle Apps im Standard-Grid an.
     */
    resetIconGrid() {
        this.userSettings.icon_positions = {};
        this.saveUserSetting('icon_positions', {});
        const icons = document.querySelectorAll('.desktop-icon');
        icons.forEach(icon => {
            icon.style.left = '';
            icon.style.top = '';
        });
        this.arrangeDesktopIcons();
        this.addLog("Desktop-Icon-Raster erfolgreich zurückgesetzt.");
    },

    init() {
        this.startClock();
        this.initDraggable();
        
        // Erst Einstellungen laden, dann anordnen, um das geladene Profil anzuwenden
        this.loadSavedSettings();
        this.arrangeDesktopIcons();
        
        this.initIconDraggable();
        this.initDockMagnification();
        this.initWidgets();
        this.initWidgetDraggables();
        this.initSentinelWidget();
        this.initSpotlight();
        this.initSnapLayouts();
        this.initShortcuts();
        
        // Browser gesture startup sound trigger
        const playStartup = () => {
            this.playSound('startup');
            document.removeEventListener('click', playStartup);
            document.removeEventListener('keydown', playStartup);
        };
        document.addEventListener('click', playStartup);
        document.addEventListener('keydown', playStartup);
        
        // Deep Links verarbeiten oder standardmäßig Willkommensfenster laden
        const urlParams = new URLSearchParams(window.location.search);
        const redirectTo = urlParams.get('vgt_redirect_to');
        if (redirectTo) {
            this.openDeepLink(redirectTo);
        } else {
            this.openWindow('welcome');
        }

        // Icons im Raster neu berechnen, wenn sich die Fenstergröße ändert
        window.addEventListener('resize', () => this.arrangeDesktopIcons());
    },

    startClock() {
        const clockEl = document.getElementById('vgt-clock');
        const widgetClock = document.getElementById('vgt-widget-clock-time');
        const widgetDate = document.getElementById('vgt-widget-clock-date');
        
        const update = () => {
            const now = new Date();
            const timeStr = now.toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit' });
            if (clockEl) clockEl.textContent = timeStr;
            if (widgetClock) widgetClock.textContent = timeStr;
            if (widgetDate) {
                widgetDate.textContent = now.toLocaleDateString('de-DE', { 
                    weekday: 'long', 
                    day: 'numeric', 
                    month: 'long' 
                });
            }
        };
        update();
        setInterval(update, 1000);
    },

    /**
     * GEHÄRTET: HTML-ESCAPING FÜR DOM-INJEKTIONEN (XSS-SCHUTZ)
     */
    escapeHTML(str) {
        if (!str) return '';
        return String(str).replace(/[&<>"']/g, m => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
        })[m]);
    },

    /**
     * GEHÄRTET: URL-VALIDATION-GUARD (OPEN-REDIRECT-SCHUTZ & PROTOKOLL-CHECK)
     */
    cleanUrl(urlStr) {
        try {
            const url = new URL(urlStr, window.location.origin);
            if (url.protocol !== 'http:' && url.protocol !== 'https:') {
                return 'about:blank';
            }
            if (url.origin !== window.location.origin) {
                return 'about:blank';
            }
            return url.toString();
        } catch (e) {
            return 'about:blank';
        }
    },

    /**
     * DESKTOP ICON LAYOUT-ARRANGER:
     * Sortiert Symbole spaltenweise in ein unsichtbares Raster wie bei Windows.
     * Nutzt die benutzerprofilspezifischen Icon-Positionen.
     */
    arrangeDesktopIcons() {
        const icons = document.querySelectorAll('.desktop-icon');
        
        // Sicherung gegen Array-Verzerrung beim Laden
        if (!this.userSettings.icon_positions || Array.isArray(this.userSettings.icon_positions)) {
            this.userSettings.icon_positions = {};
        }
        const savedPositions = this.userSettings.icon_positions;
        
        const workspace = document.getElementById('desktop-workspace');
        if (!workspace) return;
        
        const maxRows = Math.floor((workspace.clientHeight - this.gridY) / this.cellHeight) || 1;
        const maxCols = Math.floor((workspace.clientWidth - this.gridX) / this.cellWidth) || 1;
        
        let currentRow = 0;
        let currentCol = 0;
        
        icons.forEach(icon => {
            const id = icon.dataset.id;
            
            if (savedPositions[id]) {
                icon.style.left = savedPositions[id].left;
                icon.style.top = savedPositions[id].top;
            } else {
                const target = this.findNearestFreeCell(currentCol, currentRow, id, maxCols, maxRows);
                
                icon.style.left = `${this.gridX + target.col * this.cellWidth}px`;
                icon.style.top = `${this.gridY + target.row * this.cellHeight}px`;
                
                currentRow = target.row + 1;
                if (currentRow >= maxRows) {
                    currentRow = 0;
                    currentCol++;
                }
            }
        });
    },

    /**
     * DESKTOP ICON DRAG & DROP ENGINE:
     */
    initIconDraggable() {
        const icons = document.querySelectorAll('.desktop-icon');
        const workspace = document.getElementById('desktop-workspace');
        if (!workspace) return;

        icons.forEach(icon => {
            let isDragging = false;
            let offsetX = 0, offsetY = 0;

            const onMouseDown = (e) => {
                if (e.button !== 0) return;

                isDragging = true;
                this.preventClick = false;
                let hasMoved = false;
                
                const rect = icon.getBoundingClientRect();
                offsetX = e.clientX - rect.left;
                offsetY = e.clientY - rect.top;
                
                let startX = e.clientX;
                let startY = e.clientY;

                const onMouseMove = (ev) => {
                    if (!isDragging) return;
                    
                    const dx = ev.clientX - startX;
                    const dy = ev.clientY - startY;
                    const distance = Math.sqrt(dx * dx + dy * dy);

                    if (!hasMoved) {
                        if (distance > 8) {
                            hasMoved = true;
                            this.preventClick = true; 
                            icon.style.zIndex = 999;
                            icon.style.transition = 'none';
                            document.body.classList.add('vgt-dragging');
                            icon.classList.add('dragging');
                        } else {
                            return; 
                        }
                    }
                    
                    const wsRect = workspace.getBoundingClientRect();
                    let left = ev.clientX - wsRect.left - offsetX;
                    let top = ev.clientY - wsRect.top - offsetY;
                    
                    if (left < 10) left = 10;
                    if (top < 10) top = 10;
                    if (left > wsRect.width - rect.width - 10) left = wsRect.width - rect.width - 10;
                    if (top > wsRect.height - rect.height - 10) top = wsRect.height - rect.height - 10;
                    
                    icon.style.left = `${left}px`;
                    icon.style.top = `${top}px`;
                };

                const onMouseUp = () => {
                    isDragging = false;
                    document.removeEventListener('mousemove', onMouseMove);
                    document.removeEventListener('mouseup', onMouseUp);
                    
                    if (!hasMoved) {
                        return;
                    }

                    document.body.classList.remove('vgt-dragging');
                    icon.classList.remove('dragging');
                    icon.style.zIndex = '';
                    
                    const currentLeft = parseFloat(icon.style.left);
                    const currentTop = parseFloat(icon.style.top);
                    
                    let targetCol = Math.round((currentLeft - this.gridX) / this.cellWidth);
                    let targetRow = Math.round((currentTop - this.gridY) / this.cellHeight);
                    
                    const maxCols = Math.floor((workspace.clientWidth - this.gridX) / this.cellWidth) || 1;
                    const maxRows = Math.floor((workspace.clientHeight - this.gridY) / this.cellHeight) || 1;
                    
                    targetCol = Math.max(0, Math.min(targetCol, maxCols - 1));
                    targetRow = Math.max(0, Math.min(targetRow, maxRows - 1));
                    
                    const finalCell = this.findNearestFreeCell(targetCol, targetRow, icon.dataset.id, maxCols, maxRows);
                    
                    const snappedLeft = this.gridX + finalCell.col * this.cellWidth;
                    const snappedTop = this.gridY + finalCell.row * this.cellHeight;
                    
                    icon.style.transition = 'left 0.2s cubic-bezier(0.25, 1, 0.5, 1), top 0.2s cubic-bezier(0.25, 1, 0.5, 1)';
                    icon.style.left = `${snappedLeft}px`;
                    icon.style.top = `${snappedTop}px`;
                    
                    // Sicherung gegen Array-Verzerrung beim Schreiben
                    if (!this.userSettings.icon_positions || Array.isArray(this.userSettings.icon_positions)) {
                        this.userSettings.icon_positions = {};
                    }
                    
                    this.userSettings.icon_positions[icon.dataset.id] = {
                        left: `${snappedLeft}px`,
                        top: `${snappedTop}px`
                    };
                    
                    this.saveUserSetting('icon_positions', this.userSettings.icon_positions);
                    
                    setTimeout(() => {
                        icon.style.transition = 'transform 0.15s ease, background-color 0.15s ease';
                    }, 200);
                };

                document.addEventListener('mousemove', onMouseMove);
                document.addEventListener('mouseup', onMouseUp);
            };

            icon.addEventListener('mousedown', onMouseDown);

            // Touch Support
            icon.addEventListener('touchstart', (e) => {
                isDragging = true;
                this.preventClick = false;
                let hasMoved = false;
                const touch = e.touches[0];
                const rect = icon.getBoundingClientRect();
                offsetX = touch.clientX - rect.left;
                offsetY = touch.clientY - rect.top;
                
                let startX = touch.clientX;
                let startY = touch.clientY;

                const onTouchMove = (ev) => {
                    if (!isDragging) return;
                    const t = ev.touches[0];
                    
                    const dx = t.clientX - startX;
                    const dy = t.clientY - startY;
                    const distance = Math.sqrt(dx * dx + dy * dy);
                    
                    if (!hasMoved) {
                        if (distance > 8) {
                            hasMoved = true;
                            this.preventClick = true;
                            icon.style.zIndex = 999;
                            icon.style.transition = 'none';
                            document.body.classList.add('vgt-dragging');
                            icon.classList.add('dragging');
                        } else {
                            return;
                        }
                    }
                    
                    const wsRect = workspace.getBoundingClientRect();
                    let left = t.clientX - wsRect.left - offsetX;
                    let top = t.clientY - wsRect.top - offsetY;

                    if (left < 10) left = 10;
                    if (top < 10) top = 10;
                    if (left > wsRect.width - rect.width - 10) left = wsRect.width - rect.width - 10;
                    if (top > wsRect.height - rect.height - 10) top = wsRect.height - rect.height - 10;

                    icon.style.left = `${left}px`;
                    icon.style.top = `${top}px`;
                };

                const onTouchEnd = () => {
                    isDragging = false;
                    document.removeEventListener('touchmove', onTouchMove);
                    document.removeEventListener('touchend', onTouchEnd);

                    if (!hasMoved) {
                        return;
                    }

                    document.body.classList.remove('vgt-dragging');
                    icon.classList.remove('dragging');
                    icon.style.zIndex = '';

                    const currentLeft = parseFloat(icon.style.left);
                    const currentTop = parseFloat(icon.style.top);
                    
                    let targetCol = Math.round((currentLeft - this.gridX) / this.cellWidth);
                    let targetRow = Math.round((currentTop - this.gridY) / this.cellHeight);
                    
                    const maxCols = Math.floor((workspace.clientWidth - this.gridX) / this.cellWidth) || 1;
                    const maxRows = Math.floor((workspace.clientHeight - this.gridY) / this.cellHeight) || 1;
                    
                    targetCol = Math.max(0, Math.min(targetCol, maxCols - 1));
                    targetRow = Math.max(0, Math.min(targetRow, maxRows - 1));
                    
                    const finalCell = this.findNearestFreeCell(targetCol, targetRow, icon.dataset.id, maxCols, maxRows);
                    
                    const snappedLeft = this.gridX + finalCell.col * this.cellWidth;
                    const snappedTop = this.gridY + finalCell.row * this.cellHeight;
                    
                    icon.style.transition = 'left 0.2s cubic-bezier(0.25, 1, 0.5, 1), top 0.2s cubic-bezier(0.25, 1, 0.5, 1)';
                    icon.style.left = `${snappedLeft}px`;
                    icon.style.top = `${snappedTop}px`;

                    if (!this.userSettings.icon_positions || Array.isArray(this.userSettings.icon_positions)) {
                        this.userSettings.icon_positions = {};
                    }

                    this.userSettings.icon_positions[icon.dataset.id] = {
                        left: `${snappedLeft}px`,
                        top: `${snappedTop}px`
                    };
                    this.saveUserSetting('icon_positions', this.userSettings.icon_positions);
                    
                    setTimeout(() => {
                        icon.style.transition = 'transform 0.15s ease, background-color 0.15s ease';
                    }, 200);
                };

                document.addEventListener('touchmove', onTouchMove, { passive: false });
                document.addEventListener('touchend', onTouchEnd);
            });
        });
    },

    isCellOccupied(col, row, excludeId) {
        const icons = document.querySelectorAll('.desktop-icon');
        let occupied = false;
        
        icons.forEach(icon => {
            if (icon.dataset.id === excludeId) return;
            
            const left = parseFloat(icon.style.left);
            const top = parseFloat(icon.style.top);
            
            const iconCol = Math.round((left - this.gridX) / this.cellWidth);
            const iconRow = Math.round((top - this.gridY) / this.cellHeight);
            
            if (iconCol === col && iconRow === row) {
                occupied = true;
            }
        });
        return occupied;
    },

    findNearestFreeCell(targetCol, targetRow, excludeId, maxCols, maxRows) {
        if (!this.isCellOccupied(targetCol, targetRow, excludeId)) {
            return { col: targetCol, row: targetRow };
        }

        let radius = 1;
        while (radius < Math.max(maxCols, maxRows)) {
            for (let dCol = -radius; dCol <= radius; dCol++) {
                for (let dRow = -radius; dRow <= radius; dRow++) {
                    if (Math.abs(dCol) !== radius && Math.abs(dRow) !== radius) continue;
                    
                    const col = targetCol + dCol;
                    const row = targetRow + dRow;
                    
                    if (col >= 0 && col < maxCols && row >= 0 && row < maxRows) {
                        if (!this.isCellOccupied(col, row, excludeId)) {
                            return { col, row };
                        }
                    }
                }
            }
            radius++;
        }
        return { col: targetCol, row: targetRow };
    },

    /**
     * GEHÄRTETES TIEFEN-LINK ROUTING (Deep Linking):
     * KRITISCHE SCHUTZVORRICHTUNG: openDeepLink sperrt nun rigoros fremde Origins
     * und vergleicht das vollständige origin-Property zur Vermeidung von Open-Redirects.
     */
    openDeepLink(rawUrl) {
        try {
            const targetUrl = new URL(rawUrl, window.location.origin);
            if (targetUrl.origin !== window.location.origin) {
                console.error("VGT Safety Guard: External Deep Links blockiert.");
                this.addLog("Sicherheits-Alarm: Externer Deep Link abgewiesen.");
                return this.openWindow('welcome');
            }

            let matchedAppKey = null;

            const iframes = document.querySelectorAll('iframe[data-src]');
            iframes.forEach(iframe => {
                const appKey = iframe.id.replace('iframe-', '');
                const appDataSrc = new URL(iframe.dataset.src, window.location.origin);
                
                const targetPage = targetUrl.searchParams.get('page');
                const appPage = appDataSrc.searchParams.get('page');
                
                if (targetPage && targetPage === appPage) {
                    matchedAppKey = appKey;
                } else if (targetUrl.pathname === appDataSrc.pathname && !targetPage && !appPage) {
                    matchedAppKey = appKey;
                }
            });

            if (matchedAppKey) {
                this.openWindow(matchedAppKey);
                const iframe = document.getElementById(`iframe-${matchedAppKey}`);
                if (iframe) {
                    let deepUrl = targetUrl.toString();
                    if (deepUrl.indexOf('vgt_iframe') === -1) {
                        deepUrl += (deepUrl.indexOf('?') === -1 ? '?' : '&') + 'vgt_iframe=true';
                    }
                    iframe.src = this.cleanUrl(deepUrl);
                    iframe.dataset.loaded = 'true';
                }
            } else {
                let title = "System-Schnittstelle";
                const pageParam = targetUrl.searchParams.get('page');
                if (pageParam) {
                    title = pageParam.replace(/[-_]/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
                } else {
                    const parts = targetUrl.pathname.split('/');
                    const lastPart = parts[parts.length - 1];
                    if (lastPart) title = lastPart.replace('.php', '').toUpperCase();
                }

                const dynamicId = 'dyn-' + Math.random().toString(36).substring(2, 9);
                this.createDynamicWindow(dynamicId, title, targetUrl.toString());
            }
        } catch (e) {
            console.error("Fehler beim Verarbeiten des Deep Links: ", e);
            this.openWindow('welcome');
        }
    },

    /**
     * GEHÄRTETE WINDOW-GENERATION (Dynamic Window System)
     * OPTIMIERT: Nutzt nun die sauberen semantischen CSS-Klassen für Windows!
     * KRITISCHE SCHUTZVORRICHTUNG: Doppelter Origin-Check schützt vor Open-Redirects im Iframe.
     */
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

    /**
     * GEHÄRTETES UND REVOLUTIONIERTES FENSTER-DRAG & DROP SYSTEM:
     * Vollständig ereignisbasiert mit addEventListener im isolierten Scope. 
     * Verhindert unkontrolliertes Feststecken und gegenseitige Code-Blockaden komplett.
     * SICHERHEITS-CORRECTION: Dragging auf Mobilgeräten deaktiviert, um DB-Daten sauber zu halten.
     */
    makeWindowDraggable(win) {
        const header = win.querySelector('.window-header');
        if (!header) return;

        let isDragging = false;
        let offsetX = 0;
        let offsetY = 0;
        const overlay = win.querySelector('.drag-overlay');

        const onMouseMove = (e) => {
            if (!isDragging) return;

            // Check snap classes and unsnap if dragging
            const snapClasses = ['vgt-window-snap-left', 'vgt-window-snap-right', 'vgt-window-snap-topleft', 'vgt-window-snap-bottomleft'];
            const hasSnap = snapClasses.some(cls => win.classList.contains(cls));
            if (hasSnap) {
                win.classList.remove(...snapClasses);
                // Restore previous dimensions
                win.style.width = win.dataset.prevWidth || "850px";
                win.style.height = win.dataset.prevHeight || "550px";
                
                // Adjust offsets so mouse points to the title bar center
                offsetX = parseFloat(win.style.width) / 2;
                offsetY = 15;
            }

            let top = e.clientY - offsetY;
            let left = e.clientX - offsetX;
            
            // Ermöglicht das Gleiten unter die Topbar bis zu -70px
            if (top < -70) top = -70; 
            
            win.style.left = `${left}px`;
            win.style.top = `${top}px`;
        };

        const onMouseUp = () => {
            if (!isDragging) return;
            isDragging = false;
            
            // Event-Listener sauber de-registrieren
            document.removeEventListener('mousemove', onMouseMove);
            document.removeEventListener('mouseup', onMouseUp);
            
            if (overlay) overlay.classList.add('hidden');
            
            // Hide global Drag/Resize Protection Overlay
            const globalOverlay = document.getElementById('vgt-global-drag-overlay');
            if (globalOverlay) globalOverlay.style.display = 'none';
            win.classList.remove('dragging');

            // Koordinaten im Datenbankprofil sichern
            const winId = win.id.replace('win-', '');
            this.saveWindowPosition(winId, win.style.left, win.style.top, win.style.width, win.style.height);
        };

        header.addEventListener('mousedown', (e) => {
            // Drag-Schnittstelle auf mobilen Viewports bypassen
            if (window.innerWidth <= 768) {
                return;
            }

            // Dragging bei Klicks auf Funktionselemente unterbinden
            if (
                e.target.tagName === 'BUTTON' || 
                e.target.classList.contains('cursor-pointer') || 
                e.target.closest('.cursor-pointer') || 
                e.target.classList.contains('resize-handle') ||
                e.target.classList.contains('vgt-window-dot') ||
                e.target.closest('.vgt-window-dots')
            ) {
                return;
            }
            
            isDragging = true;
            offsetX = e.clientX - win.offsetLeft;
            offsetY = e.clientY - win.offsetTop;
            this.focusWindow(win.id.replace('win-', ''));

            if (overlay) overlay.classList.remove('hidden');
            
            // Show global Drag/Resize Protection Overlay
            const globalOverlay = document.getElementById('vgt-global-drag-overlay');
            if (globalOverlay) globalOverlay.style.display = 'block';
            win.classList.add('dragging');
            
            // Globale Maus-Überwachung sicher aktivieren
            document.addEventListener('mousemove', onMouseMove);
            document.addEventListener('mouseup', onMouseUp);
        });
    },

    /**
     * INTELLIGENTER TASK-SWITCHER
     */
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

        const iframe = document.getElementById(`iframe-${id}`);
        if (iframe && iframe.dataset.loaded !== 'true') {
            const spinner = document.getElementById(`spinner-${id}`);
            if (spinner) spinner.style.display = 'block';
            
            let srcUrl = iframe.dataset.src;
            if (srcUrl.indexOf('vgt_iframe') === -1) {
                srcUrl += (srcUrl.indexOf('?') === -1 ? '?' : '&') + 'vgt_iframe=true';
            }
            iframe.src = this.cleanUrl(srcUrl);
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
        this.showInTaskbar(id, false);
        this.updateDockIndicators();
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
            }
        }, 250);
        
        this.updateDockIndicators();
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
    },

    maximizeWindow(id) {
        const win = document.getElementById(`win-${id}`);
        if (!win) return;
        this.playSound('click');

        // Clean snap classes on maximize toggle
        const snapClasses = ['vgt-window-snap-left', 'vgt-window-snap-right', 'vgt-window-snap-topleft', 'vgt-window-snap-bottomleft'];
        win.classList.remove(...snapClasses);

        if (win.style.width === "100%" && win.style.height === "calc(100% - 60px)") {
            win.style.width = win.dataset.prevWidth || "850px";
            win.style.height = win.dataset.prevHeight || "550px";
            win.style.top = win.dataset.prevTop || "10%";
            win.style.left = win.dataset.prevLeft || "20%";
        } else {
            win.dataset.prevWidth = win.style.width;
            win.dataset.prevHeight = win.style.height;
            win.dataset.prevTop = win.style.top;
            win.dataset.prevLeft = win.style.left;

            win.style.width = "100%";
            win.style.height = "calc(100% - 60px)";
            win.style.top = "44px";
            win.style.left = "0px";
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

    /**
     * UNERBITTLICHER INTERCEPTOR (KRAPFEN-KORREKTUR FÜR IFRAMES):
     */
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

    /**
     * DYNAMISCHE DOCK-INDIKATOREN:
     * Übernimmt nun die neuen nativen CSS-Kompilate der Dock-Klassen (.vgt-bg-*)!
     */
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

    /**
     * DYNAMISCHE WALLPAPER-ÄNDERUNG:
     * KRITISCHE SCHUTZVORRICHTUNG: Der Client-Filter cleanUrl() blockiert nun unerwünschte 
     * CSS-Injektionen und verifiziert alle Pfade vor dem Rendern im DOM.
     */
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

    /**
     * WENDET DIE DB-PROFILWERTE BEIM INITIALEN LADEN DES DESKTOPS AN:
     */
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
            audio_enabled: true
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

        const savedWall = this.userSettings.wallpaper || 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?q=80&w=2564&auto=format&fit=crop';
        this.changeWallpaper(savedWall, false);

        const savedAccent = this.userSettings.accent_color || 'indigo';
        this.changeAccentColor(savedAccent, false);

        const blurState = this.userSettings.blur !== false;
        const checkbox = document.getElementById('blur-toggle');
        if (checkbox) checkbox.checked = blurState;
        this.applyBlur(blurState, false);

        this.applyWidgetsVisibility();
        this.applyIconsVisibility();
        this.applySavedWindowSettings();
    },

    toggleBlur() {
        const checkbox = document.getElementById('blur-toggle');
        const state = checkbox ? checkbox.checked : true;
        this.saveUserSetting('blur', state ? 'true' : 'false');
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

    /**
     * PASST BEREITS GERENDERTE WINDOW-ELEMENTE AN DIE DB-PROFILWERTE AN:
     */
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

    initDraggable() {
        const windows = document.querySelectorAll('.window');
        windows.forEach(win => {
            this.makeWindowDraggable(win);
        });
    },

    /**
     * DYNAMISCHE MULTI-EDGE RESIZE ENGINE:
     */
    startResize(e, winId, direction) {
        e.preventDefault();
        this.currentResizeWin = document.getElementById(`win-${winId}`);
        this.resizeDirection = direction;
        
        // Clean snap classes on resize
        const snapClasses = ['vgt-window-snap-left', 'vgt-window-snap-right', 'vgt-window-snap-topleft', 'vgt-window-snap-bottomleft'];
        this.currentResizeWin.classList.remove(...snapClasses);
        
        const rect = this.currentResizeWin.getBoundingClientRect();
        this.startWidth = rect.width;
        this.startHeight = rect.height;
        this.startLeft = this.currentResizeWin.offsetLeft;
        this.startTop = this.currentResizeWin.offsetTop;
        this.startX = e.clientX;
        this.startY = e.clientY;

        const overlay = this.currentResizeWin.querySelector('.drag-overlay');
        if (overlay) overlay.classList.remove('hidden');
        
        // Show global Drag/Resize Protection Overlay
        const globalOverlay = document.getElementById('vgt-global-drag-overlay');
        if (globalOverlay) globalOverlay.style.display = 'block';
        this.currentResizeWin.classList.add('resizing');

        this.resizeBinder = (ev) => this.doResize(ev);
        this.resizeStopBinder = () => {
            document.removeEventListener('mousemove', this.resizeBinder);
            if (overlay) overlay.classList.add('hidden');
            
            // Hide global Drag/Resize Protection Overlay
            const globalOverlay2 = document.getElementById('vgt-global-drag-overlay');
            if (globalOverlay2) globalOverlay2.style.display = 'none';
            
            if (this.currentResizeWin) {
                this.currentResizeWin.classList.remove('resizing');
                // Abmessungen des vergrößerten Fensters serverseitig in DB sichern
                const finishedId = this.currentResizeWin.id.replace('win-', '');
                this.saveWindowPosition(finishedId, this.currentResizeWin.style.left, this.currentResizeWin.style.top, this.currentResizeWin.style.width, this.currentResizeWin.style.height);
            }
            this.currentResizeWin = null;
        };

        document.addEventListener('mousemove', this.resizeBinder);
        document.addEventListener('mouseup', this.resizeStopBinder, { once: true });
    },

    doResize(e) {
        if (!this.currentResizeWin) return;
        
        const dx = e.clientX - this.startX;
        const dy = e.clientY - this.startY;
        
        let newWidth = this.startWidth;
        let newHeight = this.startHeight;
        let newTop = this.startTop;
        let newLeft = this.startLeft;

        const dir = this.resizeDirection;

        if (dir.includes('e')) {
            newWidth = this.startWidth + dx;
        } else if (dir.includes('w')) {
            newWidth = this.startWidth - dx;
            newLeft = this.startLeft + dx;
        }

        if (dir.includes('s')) {
            newHeight = this.startHeight + dy;
        } else if (dir.includes('n')) {
            newHeight = this.startHeight - dy;
            newTop = this.startTop + dy;
        }

        if (newWidth > 380) {
            this.currentResizeWin.style.width = `${newWidth}px`;
            this.currentResizeWin.style.left = `${newLeft}px`;
        }
        if (newHeight > 250) {
            this.currentResizeWin.style.height = `${newHeight}px`;
            this.currentResizeWin.style.top = `${newTop}px`;
        }
    },

    addLog(text) {
        const logs = document.getElementById('cc-logs');
        if (!logs) return;
        const span = document.createElement('span');
        span.textContent = `• [${new Date().toLocaleTimeString('de-DE')}] ${text}`;
        logs.appendChild(span);
        logs.scrollTop = logs.scrollHeight;
    },

    /* ==========================================================================
       DOCK MAGNIFICATION (PHASE 2)
       ========================================================================== */
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
                const maxDist = 120; // active range in px
                
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

    /* ==========================================================================
       START MENÜ / APP LAUNCHER LOGIK (PHASE 3)
       ========================================================================== */
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
    },

    handleStartItemClick(key) {
        this.openWindow(key);
        const menu = document.getElementById('vgt-start-menu');
        if (menu) menu.classList.add('hidden');
    },

    /* ==========================================================================
       DESKTOP WIDGETS LOGIK (PHASE 3)
       ========================================================================== */
    initWidgets() {
        const textarea = document.getElementById('vgt-widget-notes-text');
        if (!textarea) return;
        
        // Load saved notes
        textarea.value = localStorage.getItem('vgt_widget_notes') || '';
        
        // Auto save on input
        textarea.addEventListener('input', () => {
            localStorage.setItem('vgt_widget_notes', textarea.value);
        });
    },

    initSentinelWidget() {
        const statusDot = document.getElementById('vgt-sentinel-status-dot');
        const statusText = document.getElementById('vgt-sentinel-status-text');
        const bansCount = document.getElementById('vgt-sentinel-bans-count');
        const toggleBtn = document.getElementById('vgt-sentinel-toggle-btn');
        const widgetSentinel = document.getElementById('widget-sentinel');

        if (!widgetSentinel) return;

        const enabled = typeof vgtConfig !== 'undefined' && vgtConfig.sentinelEnabled;
        const bans = typeof vgtConfig !== 'undefined' ? vgtConfig.sentinelBans : 0;
        const isV7 = typeof vgtConfig !== 'undefined' && vgtConfig.isSentinelV7;

        if (bansCount) {
            bansCount.textContent = bans;
        }

        const updateUI = (isActive) => {
            if (statusText) {
                statusText.textContent = isActive ? 'Aktiv' : 'Inaktiv';
                statusText.style.color = isActive ? '#10b981' : '#f43f5e';
            }
            if (statusDot) {
                statusDot.style.background = isActive ? '#10b981' : '#ef4444';
                statusDot.style.boxShadow = isActive ? '0 0 10px #10b981' : '0 0 10px #ef4444';
            }
            if (toggleBtn) {
                if (isV7) {
                    toggleBtn.textContent = 'Sentinel V7 aktiv';
                    toggleBtn.style.background = 'linear-gradient(135deg, #10b981, #059669)';
                    toggleBtn.style.boxShadow = '0 4px 12px rgba(16, 185, 129, 0.2)';
                    toggleBtn.disabled = true;
                    toggleBtn.style.cursor = 'default';
                } else {
                    toggleBtn.textContent = isActive ? 'Sentinel deaktivieren' : 'Sentinel aktivieren';
                    toggleBtn.style.background = isActive 
                        ? 'linear-gradient(135deg, #10b981, #059669)' 
                        : 'linear-gradient(135deg, #f43f5e, #e11d48)';
                    toggleBtn.style.boxShadow = isActive 
                        ? '0 4px 12px rgba(16, 185, 129, 0.2)' 
                        : '0 4px 12px rgba(244, 63, 94, 0.2)';
                }
            }
        };

        updateUI(enabled);

        if (toggleBtn && !isV7) {
            toggleBtn.addEventListener('click', () => {
                this.playSound('click');
                toggleBtn.disabled = true;
                toggleBtn.style.opacity = '0.7';

                const formData = new FormData();
                formData.append('action', 'vgt_toggle_sentinel');
                formData.append('nonce', vgtConfig.nonce);

                fetch(vgtConfig.ajaxUrl, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.playSound('alert');
                        updateUI(data.data.enabled);
                        this.addLog(data.data.message);
                        vgtConfig.sentinelEnabled = data.data.enabled;
                        toggleBtn.textContent = 'Lade neu...';
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        console.error('Sentinel toggle failed:', data.data);
                        toggleBtn.disabled = false;
                        toggleBtn.style.opacity = '1';
                    }
                })
                .catch(err => {
                    console.error('Sentinel toggle network error:', err);
                    toggleBtn.disabled = false;
                    toggleBtn.style.opacity = '1';
                });
            });
        }
    },

    initWidgetDraggables() {
        const widgets = document.querySelectorAll('.vgt-widget');
        const workspace = document.getElementById('desktop-workspace');
        if (!workspace) return;
        
        // Apply saved positions and visibility
        const positions = this.userSettings.widget_positions || {};
        widgets.forEach(widget => {
            const id = widget.id;
            const saved = positions[id];
            if (saved) {
                if (saved.left) widget.style.left = saved.left;
                if (saved.top) widget.style.top = saved.top;
                if (saved.visible === false) {
                    widget.style.display = 'none';
                } else if (saved.visible === true) {
                    widget.style.display = 'flex';
                }
            }
        });
        
        widgets.forEach(widget => {
            let isDragging = false;
            let offsetX = 0, offsetY = 0;
            
            widget.addEventListener('mousedown', (e) => {
                // Deactivate dragging on textareas, inputs, or buttons
                if (
                    e.target.tagName === 'TEXTAREA' ||
                    e.target.tagName === 'INPUT' ||
                    e.target.tagName === 'BUTTON' ||
                    e.target.closest('.vgt-widget-textarea')
                ) {
                    return;
                }
                
                if (e.button !== 0) return; // Only left click
                
                isDragging = true;
                widget.classList.add('dragging');
                
                const rect = widget.getBoundingClientRect();
                const wsRect = workspace.getBoundingClientRect();
                
                offsetX = e.clientX - rect.left;
                offsetY = e.clientY - rect.top;
                
                const onMouseMove = (ev) => {
                    if (!isDragging) return;
                    
                    let left = ev.clientX - wsRect.left - offsetX;
                    let top = ev.clientY - wsRect.top - offsetY;
                    
                    // Keep widget inside workspace bounds
                    if (left < 10) left = 10;
                    if (top < 10) top = 10;
                    if (left > wsRect.width - rect.width - 10) left = wsRect.width - rect.width - 10;
                    if (top > wsRect.height - rect.height - 10) top = wsRect.height - rect.height - 10;
                    
                    widget.style.left = `${left}px`;
                    widget.style.top = `${top}px`;
                };
                
                const onMouseUp = () => {
                    if (!isDragging) return;
                    isDragging = false;
                    widget.classList.remove('dragging');
                    
                    document.removeEventListener('mousemove', onMouseMove);
                    document.removeEventListener('mouseup', onMouseUp);
                    
                    // Save to DB
                    if (!this.userSettings.widget_positions || Array.isArray(this.userSettings.widget_positions)) {
                        this.userSettings.widget_positions = {};
                    }
                    this.userSettings.widget_positions[widget.id] = {
                        left: widget.style.left,
                        top: widget.style.top,
                        visible: widget.style.display !== 'none'
                    };
                    this.saveUserSetting('widget_positions', this.userSettings.widget_positions);
                };
                
                document.addEventListener('mousemove', onMouseMove);
                document.addEventListener('mouseup', onMouseUp);
            });
        });
    },

    /* ==========================================================================
       WELLE 2 — AERO SPOTLIGHT SEARCH & COMMAND RUNNER
       ========================================================================== */
    spotlightSelectedIndex: -1,
    spotlightItems: [],
    
    initSpotlight() {
        const input = document.getElementById('vgt-spotlight-input');
        const spotlight = document.getElementById('vgt-spotlight');
        if (!input || !spotlight) return;
        
        // Listen for keyboard Ctrl + Space
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.code === 'Space') {
                e.preventDefault();
                this.toggleSpotlight();
            }
        });
        
        // Hide on click outside
        document.addEventListener('click', (e) => {
            if (!spotlight.contains(e.target) && !spotlight.classList.contains('hidden')) {
                this.toggleSpotlight(false);
            }
        });
        
        input.addEventListener('input', () => {
            this.searchSpotlight();
        });
        
        input.addEventListener('keydown', (e) => {
            const resultsContainer = document.getElementById('vgt-spotlight-results');
            const items = resultsContainer ? resultsContainer.querySelectorAll('.vgt-spotlight-item') : [];
            
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (items.length === 0) return;
                this.spotlightSelectedIndex = (this.spotlightSelectedIndex + 1) % items.length;
                this.updateSpotlightSelection(items);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (items.length === 0) return;
                this.spotlightSelectedIndex = (this.spotlightSelectedIndex - 1 + items.length) % items.length;
                this.updateSpotlightSelection(items);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                const value = input.value.trim();
                
                // Execute direct command if typed
                if (value.startsWith('/')) {
                    this.executeCommand(value);
                    this.toggleSpotlight(false);
                    return;
                }
                
                // Execute selected item
                if (this.spotlightSelectedIndex >= 0 && this.spotlightSelectedIndex < this.spotlightItems.length) {
                    this.executeSpotlightItem(this.spotlightItems[this.spotlightSelectedIndex]);
                }
            } else if (e.key === 'Escape') {
                this.toggleSpotlight(false);
            }
        });
    },
    
    toggleSpotlight(forceState) {
        const spotlight = document.getElementById('vgt-spotlight');
        const input = document.getElementById('vgt-spotlight-input');
        if (!spotlight || !input) return;
        
        const isHidden = spotlight.classList.contains('hidden');
        const show = forceState !== undefined ? forceState : isHidden;
        
        this.playSound('click');
        
        if (show) {
            spotlight.classList.remove('hidden');
            input.value = '';
            this.spotlightSelectedIndex = -1;
            this.spotlightItems = [];
            this.searchSpotlight();
            setTimeout(() => input.focus(), 50);
        } else {
            spotlight.classList.add('hidden');
        }
    },
    
    searchSpotlight() {
        const input = document.getElementById('vgt-spotlight-input');
        if (!input) return;
        
        const query = input.value.trim().toLowerCase();
        const allItems = this.getSearchableItems();
        
        if (query === '') {
            this.spotlightItems = allItems.filter(item => item.type !== 'cmd');
        } else {
            this.spotlightItems = allItems.filter(item => {
                return item.title.toLowerCase().includes(query) || 
                       item.desc.toLowerCase().includes(query) ||
                       (item.type === 'cmd' && item.title.toLowerCase().startsWith(query));
            });
        }
        
        this.spotlightSelectedIndex = this.spotlightItems.length > 0 ? 0 : -1;
        this.renderSpotlightResults(this.spotlightItems);
    },
    
    updateSpotlightSelection(elements) {
        elements.forEach((el, idx) => {
            if (idx === this.spotlightSelectedIndex) {
                el.classList.add('active');
                el.scrollIntoView({ block: 'nearest' });
            } else {
                el.classList.remove('active');
            }
        });
    },
    
    executeSpotlightItem(item) {
        if (item.type === 'app') {
            this.openWindow(item.id);
        } else if (item.type === 'action') {
            this.openDeepLink(item.url);
        } else if (item.type === 'cmd') {
            const input = document.getElementById('vgt-spotlight-input');
            if (input) {
                input.value = item.title.split(' ')[0] + ' ';
                input.focus();
                this.searchSpotlight();
                return;
            }
        }
        this.toggleSpotlight(false);
    },
    
    getSearchableItems() {
        const items = [];
        items.push({ id: 'settings', title: 'Systemeinstellungen', type: 'app', desc: 'System-Konfiguration, Farben und Hintergründe' });
        items.push({ id: 'welcome', title: 'Willkommen', type: 'app', desc: 'Willkommensbildschirm und Spenden-Infos' });
        
        document.querySelectorAll('.desktop-icon').forEach(icon => {
            const id = icon.dataset.id;
            if (id === 'settings' || id === 'welcome') return;
            const title = icon.querySelector('.vgt-icon-label')?.textContent || '';
            items.push({ id, title, type: 'app', desc: `App: ${title} öffnen` });
        });
        
        items.push({ id: 'wp-new-post', title: 'Neuer Beitrag', type: 'action', desc: 'Erstellt einen neuen WordPress-Blogbeitrag', url: vgtConfig.adminUrl + 'post-new.php' });
        items.push({ id: 'wp-new-page', title: 'Neue Seite', type: 'action', desc: 'Erstellt eine neue WordPress-Seite', url: vgtConfig.adminUrl + 'post-new.php?post_type=page' });
        items.push({ id: 'wp-plugins', title: 'Plugins verwalten', type: 'action', desc: 'Öffnet die WordPress-Plugin-Übersicht', url: vgtConfig.adminUrl + 'plugins.php' });
        
        items.push({ id: 'cmd-accent', title: '/accent [color]', type: 'cmd', desc: 'Ändert die Akzentfarbe (indigo, emerald, cyan, amber, rose)' });
        items.push({ id: 'cmd-bypass', title: '/bypass', type: 'cmd', desc: 'Deaktiviert den WP-Desk temporär (Rückkehr zum WP-Standard)' });
        items.push({ id: 'cmd-clean', title: '/clean', type: 'cmd', desc: 'Setzt das Icon-Raster zurück' });
        items.push({ id: 'cmd-reset', title: '/reset', type: 'cmd', desc: 'Setzt alle Einstellungen und Fensterpositionen komplett zurück' });
        items.push({ id: 'cmd-widget', title: '/widget [clock|system|notes|sentinel]', type: 'cmd', desc: 'Toggelt die Sichtbarkeit eines bestimmten Widgets' });
        
        return items;
    },
    
    renderSpotlightResults(results) {
        const container = document.getElementById('vgt-spotlight-results');
        if (!container) return;
        container.innerHTML = '';
        
        if (results.length === 0) {
            const empty = document.createElement('div');
            empty.className = 'vgt-spotlight-item empty-state';
            empty.textContent = 'Keine Ergebnisse gefunden.';
            empty.style.color = '#64748b';
            empty.style.fontSize = '12px';
            empty.style.padding = '12px';
            container.appendChild(empty);
            return;
        }
        
        results.forEach((item, index) => {
            const itemEl = document.createElement('div');
            itemEl.className = 'vgt-spotlight-item';
            if (index === this.spotlightSelectedIndex) {
                itemEl.classList.add('active');
            }
            itemEl.dataset.id = item.id;
            itemEl.dataset.index = index;
            
            const iconEl = document.createElement('div');
            iconEl.className = 'vgt-spotlight-item-icon';
            if (item.type === 'app') {
                iconEl.textContent = '📱';
                iconEl.style.backgroundColor = 'rgba(99, 102, 241, 0.15)';
                iconEl.style.color = '#818cf8';
            } else if (item.type === 'action') {
                iconEl.textContent = '⚡';
                iconEl.style.backgroundColor = 'rgba(16, 185, 129, 0.15)';
                iconEl.style.color = '#34d399';
            } else if (item.type === 'cmd') {
                iconEl.textContent = '💻';
                iconEl.style.backgroundColor = 'rgba(244, 63, 94, 0.15)';
                iconEl.style.color = '#fb7185';
            }
            itemEl.appendChild(iconEl);
            
            const detailsEl = document.createElement('div');
            detailsEl.className = 'vgt-spotlight-item-details';
            
            const titleEl = document.createElement('span');
            titleEl.className = 'vgt-spotlight-item-title';
            titleEl.textContent = item.title;
            detailsEl.appendChild(titleEl);
            
            const descEl = document.createElement('span');
            descEl.className = 'vgt-spotlight-item-desc';
            descEl.textContent = item.desc;
            detailsEl.appendChild(descEl);
            
            itemEl.appendChild(detailsEl);
            
            itemEl.addEventListener('click', () => {
                this.executeSpotlightItem(item);
            });
            
            container.appendChild(itemEl);
        });
    },
    
    executeCommand(cmdStr) {
        const parts = cmdStr.split(' ');
        const cmd = parts[0].toLowerCase();
        const arg = parts.slice(1).join(' ').trim();
        
        if (cmd === '/accent') {
            const colors = ['indigo', 'emerald', 'cyan', 'amber', 'rose'];
            if (colors.includes(arg.toLowerCase())) {
                this.changeAccentColor(arg.toLowerCase());
                this.addLog(`Akzentfarbe auf ${arg} geändert.`);
            } else {
                this.playSound('alert');
                this.addLog(`Fehler: Ungültige Farbe. Erlaubt: ${colors.join(', ')}`);
            }
        } else if (cmd === '/bypass') {
            this.addLog("Leite um auf Standard-Ansicht...");
            const bypassUrl = `${vgtConfig.adminUrl}index.php?vgt_action=disable_desk&_wpnonce=${vgtConfig.toggleNonce}`;
            window.location.href = bypassUrl;
        } else if (cmd === '/clean') {
            this.resetIconGrid();
        } else if (cmd === '/reset') {
            this.resetAllSettings();
        } else if (cmd === '/widget') {
            const widgetId = arg.toLowerCase();
            if (['clock', 'system', 'notes', 'sentinel'].includes(widgetId)) {
                this.toggleWidgetVisibility(widgetId);
            } else {
                this.playSound('alert');
                this.addLog("Fehler: Ungültiges Widget. Erlaubt: clock, system, notes, sentinel");
            }
        } else {
            this.playSound('alert');
            this.addLog(`Unbekannter Befehl: ${cmd}`);
        }
    },
    
    resetAllSettings() {
        if (confirm("Möchten Sie wirklich alle Einstellungen auf Werkseinstellungen zurücksetzen?")) {
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
    },

    /* ==========================================================================
       WELLE 2 — WINDOW SNAP LAYOUTS
       ========================================================================== */
    activeSnapWindowId: null,
    snapMenuTimeout: null,
    
    initSnapLayouts() {
        const windows = document.querySelectorAll('.vgt-window');
        windows.forEach(win => {
            this.attachSnapMenuListeners(win);
        });
        
        const menu = document.getElementById('vgt-global-snap-menu');
        if (menu) {
            menu.addEventListener('mouseenter', () => {
                clearTimeout(this.snapMenuTimeout);
            });
            menu.addEventListener('mouseleave', () => {
                this.snapMenuTimeout = setTimeout(() => {
                    menu.classList.add('hidden');
                }, 200);
            });
        }
    },
    
    attachSnapMenuListeners(win) {
        const dot = win.querySelector('.dot-emerald');
        if (!dot) return;
        const winId = win.id.replace('win-', '');
        
        dot.addEventListener('mouseenter', (e) => {
            clearTimeout(this.snapMenuTimeout);
            this.activeSnapWindowId = winId;
            const rect = dot.getBoundingClientRect();
            const menu = document.getElementById('vgt-global-snap-menu');
            if (menu) {
                menu.style.top = `${rect.bottom + 6}px`;
                menu.style.left = `${rect.left - 40}px`;
                menu.classList.remove('hidden');
            }
        });
        
        dot.addEventListener('mouseleave', () => {
            this.snapMenuTimeout = setTimeout(() => {
                const menu = document.getElementById('vgt-global-snap-menu');
                if (menu) menu.classList.add('hidden');
            }, 200);
        });
    },
    
    snapActiveWindow(zone) {
        const winId = this.activeSnapWindowId;
        const win = document.getElementById('win-' + winId);
        if (!win) return;
        
        this.playSound('click');
        
        // Save previous dimensions if not already snapped
        const snapClasses = ['vgt-window-snap-left', 'vgt-window-snap-right', 'vgt-window-snap-topleft', 'vgt-window-snap-bottomleft'];
        const isAlreadySnapped = snapClasses.some(cls => win.classList.contains(cls));
        if (!isAlreadySnapped) {
            win.dataset.prevWidth = win.style.width;
            win.dataset.prevHeight = win.style.height;
            win.dataset.prevTop = win.style.top;
            win.dataset.prevLeft = win.style.left;
        }
        
        // Remove snap classes
        win.classList.remove(...snapClasses);
        
        // Add snap class
        win.classList.add('vgt-window-snap-' + zone);
        
        this.focusWindow(winId);
        
        // Save to DB
        this.saveWindowPosition(winId, win.style.left, win.style.top, win.style.width, win.style.height);
        
        const menu = document.getElementById('vgt-global-snap-menu');
        if (menu) menu.classList.add('hidden');
    },

    /* ==========================================================================
       WELLE 2 — PREMIUM CONTROL CENTER
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
            const state = this.userSettings.audio_enabled !== false;
            this.saveUserSetting('audio_enabled', state ? 'false' : 'true');
        } else if (key === 'widgets') {
            const state = this.userSettings.widgets_visible !== false;
            this.saveUserSetting('widgets_visible', state ? 'false' : 'true');
            this.applyWidgetsVisibility();
        } else if (key === 'icons') {
            const state = this.userSettings.icons_visible !== false;
            this.saveUserSetting('icons_visible', state ? 'false' : 'true');
            this.applyIconsVisibility();
        } else if (key === 'blur') {
            const state = this.userSettings.blur !== false;
            this.saveUserSetting('blur', state ? 'false' : 'true');
            this.applyBlur(!state);
        }
        this.updateControlCenterToggles();
    },
    
    updateControlCenterToggles() {
        const soundActive = this.userSettings.audio_enabled !== false;
        const widgetsActive = this.userSettings.widgets_visible !== false;
        const iconsActive = this.userSettings.icons_visible !== false;
        const blurActive = this.userSettings.blur !== false;
        
        const toggleSound = document.getElementById('cc-toggle-sound')?.parentElement;
        const toggleWidgets = document.getElementById('cc-toggle-widgets')?.parentElement;
        const toggleIcons = document.getElementById('cc-toggle-icons')?.parentElement;
        const toggleBlur = document.getElementById('cc-toggle-blur')?.parentElement;
        
        if (toggleSound) toggleSound.classList.toggle('active', soundActive);
        if (toggleWidgets) toggleWidgets.classList.toggle('active', widgetsActive);
        if (toggleIcons) toggleIcons.classList.toggle('active', iconsActive);
        if (toggleBlur) toggleBlur.classList.toggle('active', blurActive);
    },
    
    applyWidgetsVisibility() {
        const container = document.getElementById('vgt-widgets-container');
        if (container) {
            const visible = this.userSettings.widgets_visible !== false;
            container.style.display = visible ? '' : 'none';
        }
    },
    
    applyIconsVisibility() {
        const container = document.getElementById('desktop-icons-area');
        if (container) {
            const visible = this.userSettings.icons_visible !== false;
            container.style.display = visible ? '' : 'none';
        }
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
    
    /* ==========================================================================
       WELLE 2 — LIVE LATENCY GRAPH CANVAS ANIMATION
       ========================================================================== */
    ccGraphInterval: null,
    ccGraphPoints: [],
    
    startLatencyGraph() {
        const canvas = document.getElementById('vgt-cc-graph');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        if (!ctx) return;
        
        this.stopLatencyGraph();
        this.ccGraphPoints = Array(20).fill(15);
        
        let lastFrameTime = performance.now();
        
        const renderGraph = () => {
            const now = performance.now();
            const delta = now - lastFrameTime;
            lastFrameTime = now;
            
            const latencyVal = Math.min(60, Math.max(5, delta + (Math.random() * 4 - 2)));
            
            this.ccGraphPoints.push(latencyVal);
            if (this.ccGraphPoints.length > 30) {
                this.ccGraphPoints.shift();
            }
            
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            // Draw grid lines
            ctx.strokeStyle = 'rgba(255, 255, 255, 0.05)';
            ctx.lineWidth = 1;
            for (let y = 10; y < canvas.height; y += 20) {
                ctx.beginPath();
                ctx.moveTo(0, y);
                ctx.lineTo(canvas.width, y);
                ctx.stroke();
            }
            
            // Draw area under line
            ctx.beginPath();
            const step = canvas.width / (this.ccGraphPoints.length - 1);
            ctx.moveTo(0, canvas.height);
            this.ccGraphPoints.forEach((p, idx) => {
                const y = canvas.height - ((p - 5) / 55) * (canvas.height - 20) - 10;
                ctx.lineTo(idx * step, y);
            });
            ctx.lineTo(canvas.width, canvas.height);
            ctx.closePath();
            
            const accentColor = getComputedStyle(document.documentElement).getPropertyValue('--vgt-accent-color').trim() || '#6366f1';
            ctx.fillStyle = accentColor + '15';
            ctx.fill();
            
            // Draw line path
            ctx.beginPath();
            this.ccGraphPoints.forEach((p, idx) => {
                const y = canvas.height - ((p - 5) / 55) * (canvas.height - 20) - 10;
                if (idx === 0) {
                    ctx.moveTo(0, y);
                } else {
                    ctx.lineTo(idx * step, y);
                }
            });
            ctx.strokeStyle = accentColor;
            ctx.lineWidth = 2;
            ctx.lineJoin = 'round';
            ctx.lineCap = 'round';
            ctx.stroke();
            
            ctx.fillStyle = '#ffffff';
            ctx.font = 'bold 9px sans-serif';
            ctx.fillText(Math.round(latencyVal) + ' ms', canvas.width - 45, 15);
            
            this.ccGraphInterval = requestAnimationFrame(renderGraph);
        };
        
        this.ccGraphInterval = requestAnimationFrame(renderGraph);
    },
    
    stopLatencyGraph() {
        if (this.ccGraphInterval) {
            cancelAnimationFrame(this.ccGraphInterval);
            this.ccGraphInterval = null;
        }
    },

    /* ==========================================================================
       WELLE 2 — WEB AUDIO TACTILE SYNTH SOUND ENGINE
       ========================================================================== */
    audioCtx: null,
    
    initAudio() {
        if (!this.audioCtx) {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (AudioContext) {
                this.audioCtx = new AudioContext();
            }
        }
        if (this.audioCtx && this.audioCtx.state === 'suspended') {
            this.audioCtx.resume();
        }
    },
    
    playSound(type) {
        if (this.userSettings.audio_enabled === false) return;
        this.initAudio();
        if (!this.audioCtx) return;
        
        const ctx = this.audioCtx;
        const now = ctx.currentTime;
        
        if (type === 'startup') {
            const freqs = [261.63, 329.63, 392.00, 523.25]; // C4, E4, G4, C5 (warm triad)
            freqs.forEach((freq, index) => {
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'triangle';
                osc.frequency.setValueAtTime(freq, now + index * 0.05);
                
                gain.gain.setValueAtTime(0, now + index * 0.05);
                gain.gain.linearRampToValueAtTime(0.15, now + index * 0.05 + 0.1);
                gain.gain.exponentialRampToValueAtTime(0.001, now + index * 0.05 + 1.2);
                
                osc.connect(gain);
                gain.connect(ctx.destination);
                
                osc.start(now + index * 0.05);
                osc.stop(now + index * 0.05 + 1.3);
            });
        } else if (type === 'click') {
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.setValueAtTime(800, now);
            osc.frequency.exponentialRampToValueAtTime(100, now + 0.03);
            
            gain.gain.setValueAtTime(0.2, now);
            gain.gain.exponentialRampToValueAtTime(0.001, now + 0.03);
            
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start(now);
            osc.stop(now + 0.04);
        } else if (type === 'alert') {
            const osc1 = ctx.createOscillator();
            const osc2 = ctx.createOscillator();
            const gain = ctx.createGain();
            
            osc1.type = 'sawtooth';
            osc2.type = 'sawtooth';
            
            osc1.frequency.setValueAtTime(220, now);
            osc2.frequency.setValueAtTime(218, now); // Detune effect
            
            const filter = ctx.createBiquadFilter();
            filter.type = 'lowpass';
            filter.frequency.setValueAtTime(600, now);
            filter.frequency.exponentialRampToValueAtTime(100, now + 0.3);
            
            gain.gain.setValueAtTime(0.15, now);
            gain.gain.linearRampToValueAtTime(0.15, now + 0.1);
            gain.gain.exponentialRampToValueAtTime(0.001, now + 0.3);
            
            osc1.connect(filter);
            osc2.connect(filter);
            filter.connect(gain);
            gain.connect(ctx.destination);
            
            osc1.start(now);
            osc2.start(now);
            osc1.stop(now + 0.35);
            osc2.stop(now + 0.35);
        }
    },

    /* ==========================================================================
       KEYBOARD SHORTCUTS LOGIK (PHASE 3)
       ========================================================================== */
    initShortcuts() {
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const menu = document.getElementById('vgt-start-menu');
                if (menu && !menu.classList.contains('hidden')) {
                    menu.classList.add('hidden');
                }
            }
            
            // Alt + Q to cycle focus between active windows
            if (e.altKey && e.key.toLowerCase() === 'q') {
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
                }
            }
        });
    }
};

document.addEventListener('DOMContentLoaded', () => {
    VGTDeskEngine.init();
});