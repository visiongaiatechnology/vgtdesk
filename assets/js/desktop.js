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
        if (!clockEl) return;
        
        const update = () => {
            const now = new Date();
            clockEl.textContent = now.toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit' });
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
            if (urlStr.startsWith('/') && !urlStr.startsWith('//')) {
                return urlStr;
            }

            const url = new URL(urlStr, window.location.origin);
            if (url.protocol !== 'http:' && url.protocol !== 'https:') {
                return 'about:blank';
            }
            if (url.hostname !== window.location.hostname) {
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
        const escapedTitle = this.escapeHTML(title);
        const escapedUrl = this.escapeHTML(safeUrl.toString());

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
                    <span class="vgt-window-title">${escapedTitle}</span>
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
        const escapedTitle = this.escapeHTML(title);

        const taskHtml = `
            <div class="vgt-dock-item" id="dock-task-${escapedId}" onclick="VGTDeskEngine.handleDockClick('${escapedId}')">
                <div class="vgt-dock-icon vgt-color-gradient-settings">
                    <span class="vgt-icon-label" style="margin-top:0; padding:0; text-shadow:none;">${escapedTitle.substring(0, 2).toUpperCase()}</span>
                </div>
                <span class="vgt-dock-tooltip">${escapedTitle}</span>
                <span class="vgt-dock-indicator" id="indicator-${escapedId}"></span>
            </div>
        `;
        taskBar.insertAdjacentHTML('beforeend', taskHtml);
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
                e.target.classList.contains('resize-handle')
            ) {
                return;
            }
            
            isDragging = true;
            offsetX = e.clientX - win.offsetLeft;
            offsetY = e.clientY - win.offsetTop;
            this.focusWindow(win.id.replace('win-', ''));

            if (overlay) overlay.classList.remove('hidden');
            
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
        
        document.querySelectorAll('.window').forEach(w => w.classList.add('opacity-90'));
        win.classList.remove('opacity-90');
        win.classList.add('opacity-100');
    },

    minimizeWindow(id) {
        const win = document.getElementById(`win-${id}`);
        if (!win) return;
        
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
        this.interceptIframeNavigations(iframe);
    },

    /**
     * UNERBITTLICHER INTERCEPTOR (KRAPFEN-KORREKTUR FÜR IFRAMES):
     */
    interceptIframeNavigations(iframe) {
        try {
            const iframeWindow = iframe.contentWindow;
            const iframeDoc = iframe.contentDocument || iframeWindow.document;
            if (!iframeDoc) return;

            // Klicks auf Links abfangen und "vgt_iframe=true" anhängen
            iframeDoc.addEventListener('click', (e) => {
                const anchor = e.target.closest('a');
                if (!anchor) return;

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
            indigo: '#6366f1',
            emerald: '#10b981',
            cyan: '#06b6d4',
            amber: '#f59e0b',
            rose: '#f43f5e'
        };

        const hex = tailwindColors[color] || '#6366f1';

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
            window_settings: {}
        };

        // RIGOROSE SCHLÜSSEL-KORREKTUR: Verhindert die zerstörerische Array-Konvertierung von leeren PHP-Objekten
        if (!this.userSettings.icon_positions || Array.isArray(this.userSettings.icon_positions)) {
            this.userSettings.icon_positions = {};
        }
        if (!this.userSettings.window_settings || Array.isArray(this.userSettings.window_settings)) {
            this.userSettings.window_settings = {};
        }

        const savedWall = this.userSettings.wallpaper || 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?q=80&w=2564&auto=format&fit=crop';
        this.changeWallpaper(savedWall, false);

        const savedAccent = this.userSettings.accent_color || 'indigo';
        this.changeAccentColor(savedAccent, false);

        const blurState = this.userSettings.blur !== false;
        const checkbox = document.getElementById('blur-toggle');
        if (checkbox) checkbox.checked = blurState;
        this.applyBlur(blurState, false);

        this.applySavedWindowSettings();
    },

    toggleBlur() {
        const checkbox = document.getElementById('blur-toggle');
        const state = checkbox ? checkbox.checked : true;
        this.saveUserSetting('blur', state ? 'true' : 'false');
        this.applyBlur(state);
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
            if (settings.left) win.style.left = settings.left;
            if (settings.top) win.style.top = settings.top;
            if (settings.width) win.style.width = settings.width;
            if (settings.height) win.style.height = settings.height;
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
        
        const rect = this.currentResizeWin.getBoundingClientRect();
        this.startWidth = rect.width;
        this.startHeight = rect.height;
        this.startLeft = this.currentResizeWin.offsetLeft;
        this.startTop = this.currentResizeWin.offsetTop;
        this.startX = e.clientX;
        this.startY = e.clientY;

        const overlay = this.currentResizeWin.querySelector('.drag-overlay');
        if (overlay) overlay.classList.remove('hidden');

        this.resizeBinder = (ev) => this.doResize(ev);
        this.resizeStopBinder = () => {
            document.removeEventListener('mousemove', this.resizeBinder);
            if (overlay) overlay.classList.add('hidden');
            
            // Abmessungen des vergrößerten Fensters serverseitig in DB sichern
            if (this.currentResizeWin) {
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
    }
};

document.addEventListener('DOMContentLoaded', () => {
    VGTDeskEngine.init();
});