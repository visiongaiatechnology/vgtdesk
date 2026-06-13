// STATUS: DIAMANT VGT SUPREME
/**
 * VGT Desktop Module - Desktop Icons & Grid Management
 * Handles: handleIconClick, resetIconGrid, arrangeDesktopIcons, initIconDraggable, makeIconDraggable,
 *          isCellOccupied, findNearestFreeCell, openDeepLink
 */

Object.assign(window.VGTDeskEngine, {
    handleIconClick(e, id) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        if (this.preventClick) {
            this.preventClick = false;
            return;
        }
        const app = (typeof vgtConfig !== 'undefined' && vgtConfig.apps) ? vgtConfig.apps[id] : null;
        if (app && app.submenus && app.submenus.length > 0) {
            this.openSubmenuPopup(e, id);
        } else {
            this.openWindow(id);
        }
    },

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

    arrangeDesktopIcons() {
        this.renderFolders();

        const appsInFolders = new Set();
        if (this.userSettings.folders) {
            for (let folderId in this.userSettings.folders) {
                const folderData = this.userSettings.folders[folderId];
                if (folderData && folderData.apps) {
                    folderData.apps.forEach(appId => appsInFolders.add(appId));
                }
            }
        }
        
        const appIcons = document.querySelectorAll('.desktop-icon:not(.desktop-folder)');
        appIcons.forEach(icon => {
            const appId = icon.dataset.id;
            if (appsInFolders.has(appId)) {
                icon.style.display = 'none';
                icon.classList.add('in-folder');
            } else {
                icon.style.display = '';
                icon.classList.remove('in-folder');
            }
        });

        const visibleIcons = document.querySelectorAll('.desktop-icon:not(.in-folder)');
        
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
        
        visibleIcons.forEach(icon => {
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

    initIconDraggable() {
        const icons = document.querySelectorAll('.desktop-icon');
        icons.forEach(icon => this.makeIconDraggable(icon));
    },

    makeIconDraggable(icon) {
        const workspace = document.getElementById('desktop-workspace');
        if (!workspace) return;

        let isDragging = false;
        let offsetX = 0, offsetY = 0;

        const onMouseDown = (e) => {
            if (e.button !== 0) return;

            isDragging = true;
            this.preventClick = false;
            let hasMoved = false;
            
            const zoom = parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--vgt-font-zoom')) || 1;
            const rect = icon.getBoundingClientRect();
            offsetX = (e.clientX - rect.left) / zoom;
            offsetY = (e.clientY - rect.top) / zoom;
            
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
                
                const z = parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--vgt-font-zoom')) || 1;
                const wsR = workspace.getBoundingClientRect();
                const r = icon.getBoundingClientRect();
                
                const wsLeft = wsR.left / z;
                const wsTop = wsR.top / z;
                const wsWidth = wsR.width / z;
                const wsHeight = wsR.height / z;
                const iconWidth = r.width / z;
                const iconHeight = r.height / z;
                
                let left = (ev.clientX / z) - wsLeft - offsetX;
                let top = (ev.clientY / z) - wsTop - offsetY;
                
                if (left < 10) left = 10;
                if (top < 10) top = 10;
                if (left > wsWidth - iconWidth - 10) left = wsWidth - iconWidth - 10;
                if (top > wsHeight - iconHeight - 10) top = wsHeight - iconHeight - 10;
                
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
                
                let overlappedFolder = null;
                if (!icon.classList.contains('desktop-folder')) {
                    const iconRect = icon.getBoundingClientRect();
                    const folders = document.querySelectorAll('.desktop-folder');
                    
                    folders.forEach(folder => {
                        const folderRect = folder.getBoundingClientRect();
                        const overlap = !(iconRect.right < folderRect.left || 
                                          iconRect.left > folderRect.right || 
                                          iconRect.bottom < folderRect.top || 
                                          iconRect.top > folderRect.bottom);
                        if (overlap) {
                            overlappedFolder = folder;
                        }
                    });
                }

                if (overlappedFolder) {
                    const folderId = overlappedFolder.dataset.id;
                    const appId = icon.dataset.id;
                    
                    if (!this.userSettings.folders) {
                        this.userSettings.folders = {};
                    }
                    if (!this.userSettings.folders[folderId]) {
                        this.userSettings.folders[folderId] = { title: 'Ordner', apps: [] };
                    }
                    if (!this.userSettings.folders[folderId].apps) {
                        this.userSettings.folders[folderId].apps = [];
                    }
                    if (!this.userSettings.folders[folderId].apps.includes(appId)) {
                        this.userSettings.folders[folderId].apps.push(appId);
                    }
                    delete this.userSettings.icon_positions[appId];
                    
                    this.saveUserSetting('folders', this.userSettings.folders);
                    this.saveUserSetting('icon_positions', this.userSettings.icon_positions);
                    
                    this.playSound('click');
                    this.addLog(`App '${appId}' in Ordner '${this.userSettings.folders[folderId].title}' verschoben.`);
                    this.arrangeDesktopIcons();
                    return;
                }

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
            
            const zoom = parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--vgt-font-zoom')) || 1;
            const rect = icon.getBoundingClientRect();
            offsetX = (touch.clientX - rect.left) / zoom;
            offsetY = (touch.clientY - rect.top) / zoom;
            
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
                
                const z = parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--vgt-font-zoom')) || 1;
                const wsR = workspace.getBoundingClientRect();
                const r = icon.getBoundingClientRect();
                
                const wsLeft = wsR.left / z;
                const wsTop = wsR.top / z;
                const wsWidth = wsR.width / z;
                const wsHeight = wsR.height / z;
                const iconWidth = r.width / z;
                const iconHeight = r.height / z;
                
                let left = (t.clientX / z) - wsLeft - offsetX;
                let top = (t.clientY / z) - wsTop - offsetY;

                if (left < 10) left = 10;
                if (top < 10) top = 10;
                if (left > wsWidth - iconWidth - 10) left = wsWidth - iconWidth - 10;
                if (top > wsHeight - iconHeight - 10) top = wsHeight - iconHeight - 10;

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
                
                let overlappedFolder = null;
                if (!icon.classList.contains('desktop-folder')) {
                    const iconRect = icon.getBoundingClientRect();
                    const folders = document.querySelectorAll('.desktop-folder');
                    
                    folders.forEach(folder => {
                        const folderRect = folder.getBoundingClientRect();
                        const overlap = !(iconRect.right < folderRect.left || 
                                          iconRect.left > folderRect.right || 
                                          iconRect.bottom < folderRect.top || 
                                          iconRect.top > folderRect.bottom);
                        if (overlap) {
                            overlappedFolder = folder;
                        }
                    });
                }

                if (overlappedFolder) {
                    const folderId = overlappedFolder.dataset.id;
                    const appId = icon.dataset.id;
                    
                    if (!this.userSettings.folders) {
                        this.userSettings.folders = {};
                    }
                    if (!this.userSettings.folders[folderId]) {
                        this.userSettings.folders[folderId] = { title: 'Ordner', apps: [] };
                    }
                    if (!this.userSettings.folders[folderId].apps) {
                        this.userSettings.folders[folderId].apps = [];
                    }
                    if (!this.userSettings.folders[folderId].apps.includes(appId)) {
                        this.userSettings.folders[folderId].apps.push(appId);
                    }
                    delete this.userSettings.icon_positions[appId];
                    
                    this.saveUserSetting('folders', this.userSettings.folders);
                    this.saveUserSetting('icon_positions', this.userSettings.icon_positions);
                    
                    this.playSound('click');
                    this.addLog(`App '${appId}' in Ordner '${this.userSettings.folders[folderId].title}' verschoben.`);
                    this.arrangeDesktopIcons();
                    return;
                }

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

    openDeepLink(rawUrl) {
        try {
            const targetUrl = new URL(rawUrl, window.location.origin);
            if (targetUrl.origin !== window.location.origin) {
                console.error("VGT Safety Guard: External Deep Links blockiert.");
                this.addLog("Sicherheits-Alarm: Externer Deep Link abgewiesen.");
                return this.openWindow('welcome');
            }

            // Ignoriere Weiterleitungen zum Haupt-Dashboard oder leeren Admin-Pfaden
            const targetPath = targetUrl.pathname;
            const targetPage = targetUrl.searchParams.get('page');
            if ((targetPath.endsWith('/wp-admin/') || targetPath.endsWith('/wp-admin/index.php') || targetPath.endsWith('/wp-admin')) && !targetPage) {
                return;
            }
            if (targetPage === 'vgt-wp-desk') {
                return;
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
    }
});
