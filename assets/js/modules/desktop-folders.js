/**
 * VGT Desktop Module - Folder Management
 * Handles: createNewFolder, renderFolders, handleFolderClick, openFolderWindow,
 *          renderFolderContents, removeAppFromFolder, renameFolder, deleteFolder
 */

Object.assign(window.VGTDeskEngine, {
    createNewFolder() {
        this.showModal({
            title: 'Neuer Ordner',
            message: 'Geben Sie einen Namen für den neuen Ordner ein:',
            inputType: 'text',
            inputValue: 'Unbenannter Ordner',
            confirmText: 'Erstellen',
            onConfirm: (cleanTitle) => {
                if (!cleanTitle) return;
                const folderId = 'folder-' + Math.random().toString(36).substring(2, 9);
                
                if (!this.userSettings.folders) {
                    this.userSettings.folders = {};
                }

                this.userSettings.folders[folderId] = {
                    title: cleanTitle,
                    apps: []
                };

                const workspace = document.getElementById('desktop-workspace');
                if (workspace) {
                    const maxCols = Math.floor((workspace.clientWidth - this.gridX) / this.cellWidth) || 1;
                    const maxRows = Math.floor((workspace.clientHeight - this.gridY) / this.cellHeight) || 1;
                    const target = this.findNearestFreeCell(0, 0, folderId, maxCols, maxRows);
                    const left = `${this.gridX + target.col * this.cellWidth}px`;
                    const top = `${this.gridY + target.row * this.cellHeight}px`;

                    if (!this.userSettings.icon_positions || Array.isArray(this.userSettings.icon_positions)) {
                        this.userSettings.icon_positions = {};
                    }
                    this.userSettings.icon_positions[folderId] = { left, top };
                    this.saveUserSetting('icon_positions', this.userSettings.icon_positions);
                }

                this.saveUserSetting('folders', this.userSettings.folders);
                this.playSound('click');
                this.addLog(`Ordner '${cleanTitle}' erstellt.`);

                this.arrangeDesktopIcons();
            }
        });
    },

    renderFolders() {
        const folderEls = document.querySelectorAll('.desktop-folder');
        folderEls.forEach(el => {
            const id = el.dataset.id;
            if (!this.userSettings.folders || !this.userSettings.folders[id]) {
                el.remove();
            }
        });

        if (this.userSettings.folders) {
            const iconsArea = document.getElementById('desktop-icons-area');
            if (iconsArea) {
                for (let folderId in this.userSettings.folders) {
                    const folderData = this.userSettings.folders[folderId];
                    let folderEl = document.getElementById(`folder-icon-${folderId}`);
                    if (!folderEl) {
                        folderEl = document.createElement('div');
                        folderEl.id = `folder-icon-${folderId}`;
                        folderEl.className = 'desktop-icon desktop-folder absolute vgt-icon-item';
                        folderEl.dataset.id = folderId;
                        folderEl.dataset.isFolder = 'true';
                        
                        folderEl.innerHTML = `
                            <div class="vgt-icon-tile vgt-color-gradient-folder">
                                <span class="vgt-icon-emoji">📁</span>
                            </div>
                            <span class="vgt-icon-label"></span>
                        `;
                        
                        folderEl.addEventListener('click', (e) => this.handleFolderClick(e, folderId));
                        
                        iconsArea.appendChild(folderEl);
                        
                        this.makeIconDraggable(folderEl);
                    }
                    folderEl.querySelector('.vgt-icon-label').textContent = folderData.title;
                }
            }
        }
    },

    handleFolderClick(e, folderId) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        if (this.preventClick) {
            this.preventClick = false;
            return;
        }
        this.openFolderWindow(folderId);
    },

    openFolderWindow(folderId) {
        const winId = `folder-win-${folderId}`;
        const existing = document.getElementById(`win-${winId}`);
        if (existing) {
            existing.classList.remove('hidden');
            existing.style.transform = 'none';
            existing.style.opacity = '1';
            this.focusWindow(winId);
            this.renderFolderContents(folderId);
            return;
        }

        const folderData = this.userSettings.folders[folderId];
        if (!folderData) return;

        const container = document.getElementById('vgt-dynamic-windows') || document.body;
        
        const escapedId = this.escapeHTML(winId);
        const escapedFolderId = this.escapeHTML(folderId);

        const windowHtml = `
            <div id="win-${escapedId}" class="window absolute vgt-window folder-window" style="width: 500px; height: 350px; top: 20%; left: 30%; z-index: ${this.activeZIndex + 5};" onclick="VGTDeskEngine.focusWindow('${escapedId}')">
                
                <!-- Resize Handles -->
                <div class="resize-handle resize-handle-n" onmousedown="VGTDeskEngine.startResize(event, '${escapedId}', 'n')"></div>
                <div class="resize-handle resize-handle-s" onmousedown="VGTDeskEngine.startResize(event, '${escapedId}', 's')"></div>
                <div class="resize-handle resize-handle-e" onmousedown="VGTDeskEngine.startResize(event, '${escapedId}', 'e')"></div>
                <div class="resize-handle resize-handle-w" onmousedown="VGTDeskEngine.startResize(event, '${escapedId}', 'w')"></div>
                
                <!-- Titlebar -->
                <div class="vgt-window-header cursor-move window-header">
                    <div class="vgt-window-dots">
                        <span class="vgt-window-dot dot-rose" onclick="VGTDeskEngine.closeWindow('${escapedId}')"></span>
                        <span class="vgt-window-dot dot-amber" onclick="VGTDeskEngine.minimizeWindow('${escapedId}')"></span>
                        <span class="vgt-window-dot dot-emerald" onclick="VGTDeskEngine.maximizeWindow('${escapedId}')"></span>
                    </div>
                    <span class="vgt-window-title"></span>
                    <div class="vgt-window-badge-wrap" style="display:flex; align-items:center; gap:8px;">
                        <button class="vgt-folder-rename-btn" style="background:none; border:none; color:#94a3b8; cursor:pointer; font-size:12px;" onclick="VGTDeskEngine.renameFolder('${escapedFolderId}')">✏️</button>
                        <button class="vgt-folder-delete-btn" style="background:none; border:none; color:#f43f5e; cursor:pointer; font-size:12px;" onclick="VGTDeskEngine.deleteFolder('${escapedFolderId}')">🗑️</button>
                        <span class="vgt-badge-item vgt-accent-badge-item">Ordner</span>
                    </div>
                </div>
                <!-- Body -->
                <div class="vgt-window-body folder-window-body" style="padding: 20px; overflow-y: auto;">
                    <div class="folder-apps-grid" id="folder-grid-${escapedFolderId}" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 16px;">
                    </div>
                </div>
            </div>
        `;

        container.insertAdjacentHTML('beforeend', windowHtml);
        
        const winEl = document.getElementById(`win-${escapedId}`);
        if (winEl) {
            winEl.querySelector('.vgt-window-title').textContent = folderData.title;
            this.makeWindowDraggable(winEl);
        }

        this.activeWindows[winId] = true;
        this.minimizedWindows[winId] = false;
        
        this.focusWindow(winId);
        this.renderFolderContents(folderId);
    },

    renderFolderContents(folderId) {
        const grid = document.getElementById(`folder-grid-${folderId}`);
        if (!grid) return;
        grid.innerHTML = '';

        const folderData = this.userSettings.folders[folderId];
        if (!folderData || !folderData.apps || folderData.apps.length === 0) {
            const empty = document.createElement('div');
            empty.className = 'folder-empty-state';
            empty.textContent = 'Ordner ist leer. Ziehe Symbole hinein.';
            empty.style.color = '#64748b';
            empty.style.gridColumn = '1 / -1';
            empty.style.textAlign = 'center';
            empty.style.padding = '20px';
            grid.appendChild(empty);
            return;
        }

        folderData.apps.forEach(appId => {
            const app = vgtConfig.apps[appId];
            if (!app) return;

            const item = document.createElement('div');
            item.className = 'folder-app-item relative';
            item.style.display = 'flex';
            item.style.flexDirection = 'column';
            item.style.alignItems = 'center';
            item.style.cursor = 'pointer';
            item.style.padding = '8px';
            item.style.borderRadius = '8px';
            item.style.transition = 'background-color 0.2s';
            
            item.addEventListener('mouseenter', () => {
                item.style.backgroundColor = 'rgba(255, 255, 255, 0.05)';
            });
            item.addEventListener('mouseleave', () => {
                item.style.backgroundColor = 'transparent';
            });

            item.addEventListener('click', (e) => {
                if (e.target.closest('.remove-from-folder-btn')) return;
                this.playSound('click');
                this.openWindow(appId);
            });

            const tile = document.createElement('div');
            tile.className = `vgt-icon-tile ${app.color}`;
            tile.style.width = '48px';
            tile.style.height = '48px';
            tile.style.borderRadius = '12px';
            tile.style.display = 'flex';
            tile.style.alignItems = 'center';
            tile.style.justifyContent = 'center';
            tile.style.marginBottom = '6px';
            tile.style.fontSize = '24px';
            
            if (app.icon_type === 'dashicons') {
                const icon = document.createElement('span');
                icon.className = `dashicons ${app.icon_val}`;
                icon.style.color = '#ffffff';
                icon.style.fontSize = '24px';
                icon.style.width = '24px';
                icon.style.height = '24px';
                tile.appendChild(icon);
            } else if (app.icon_type === 'svg' || app.icon_type === 'url') {
                const img = document.createElement('img');
                img.src = app.icon_val;
                img.style.width = '24px';
                img.style.height = '24px';
                tile.appendChild(img);
            }

            const label = document.createElement('span');
            label.className = 'vgt-icon-label';
            label.style.fontSize = '11px';
            label.style.color = '#cbd5e1';
            label.style.textAlign = 'center';
            label.style.wordBreak = 'break-word';
            label.textContent = app.title;

            const removeBtn = document.createElement('button');
            removeBtn.className = 'remove-from-folder-btn absolute';
            removeBtn.innerHTML = '×';
            removeBtn.title = 'Aus Ordner entfernen';
            removeBtn.style.position = 'absolute';
            removeBtn.style.top = '2px';
            removeBtn.style.right = '2px';
            removeBtn.style.width = '16px';
            removeBtn.style.height = '16px';
            removeBtn.style.borderRadius = '50%';
            removeBtn.style.background = 'rgba(244, 63, 94, 0.8)';
            removeBtn.style.border = 'none';
            removeBtn.style.color = '#ffffff';
            removeBtn.style.fontSize = '12px';
            removeBtn.style.lineHeight = '14px';
            removeBtn.style.cursor = 'pointer';
            removeBtn.style.display = 'none';
            
            item.appendChild(tile);
            item.appendChild(label);
            item.appendChild(removeBtn);

            item.addEventListener('mouseenter', () => { removeBtn.style.display = 'block'; });
            item.addEventListener('mouseleave', () => { removeBtn.style.display = 'none'; });

            removeBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.removeAppFromFolder(appId, folderId);
            });

            grid.appendChild(item);
        });
    },

    removeAppFromFolder(appId, folderId) {
        const folderData = this.userSettings.folders[folderId];
        if (!folderData) return;

        folderData.apps = folderData.apps.filter(id => id !== appId);
        
        this.saveUserSetting('folders', this.userSettings.folders);
        this.playSound('click');
        this.addLog(`App '${appId}' aus Ordner '${folderData.title}' entfernt.`);

        this.renderFolderContents(folderId);
        this.arrangeDesktopIcons();
    },

    renameFolder(folderId) {
        const folderData = this.userSettings.folders[folderId];
        if (!folderData) return;

        this.showModal({
            title: 'Ordner umbenennen',
            message: 'Geben Sie einen neuen Namen für den Ordner ein:',
            inputType: 'text',
            inputValue: folderData.title,
            confirmText: 'Umbenennen',
            onConfirm: (cleanTitle) => {
                if (!cleanTitle) return;
                folderData.title = cleanTitle;

                this.saveUserSetting('folders', this.userSettings.folders);
                this.playSound('click');
                this.addLog(`Ordner '${folderId}' umbenannt in '${cleanTitle}'.`);

                const winId = `folder-win-${folderId}`;
                const winEl = document.getElementById(`win-${winId}`);
                if (winEl) {
                    winEl.querySelector('.vgt-window-title').textContent = cleanTitle;
                }

                this.arrangeDesktopIcons();
            }
        });
    },

    deleteFolder(folderId) {
        const folderData = this.userSettings.folders[folderId];
        if (!folderData) return;

        this.showModal({
            title: 'Ordner löschen',
            message: `Möchten Sie den Ordner '${folderData.title}' wirklich löschen? Alle Apps darin werden wieder auf den Desktop gelegt.`,
            confirmText: 'Löschen',
            confirmClass: 'vgt-btn-danger',
            onConfirm: () => {
                const winId = `folder-win-${folderId}`;
                this.closeWindow(winId);
                const winEl = document.getElementById(`win-${winId}`);
                if (winEl) winEl.remove();

                if (folderData.apps) {
                    folderData.apps.forEach(appId => {
                        if (this.userSettings.icon_positions) {
                            delete this.userSettings.icon_positions[appId];
                        }
                    });
                }

                delete this.userSettings.folders[folderId];
                delete this.userSettings.icon_positions[folderId];

                this.saveUserSetting('folders', this.userSettings.folders);
                this.saveUserSetting('icon_positions', this.userSettings.icon_positions);
                this.playSound('click');
                this.addLog(`Ordner '${folderData.title}' gelöscht.`);

                this.arrangeDesktopIcons();
            }
        });
    }
});
