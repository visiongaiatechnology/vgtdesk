/**
 * VGT Desktop Module - Window Draggable & Resize & Snap Layouts
 * Handles: makeWindowDraggable, initDraggable, startResize, doResize, initSnapLayouts, attachSnapMenuListeners, snapActiveWindow
 */

Object.assign(window.VGTDeskEngine, {
    makeWindowDraggable(win) {
        const header = win.querySelector('.window-header');
        if (!header) return;

        let isDragging = false;
        let offsetX = 0;
        let offsetY = 0;
        let startX = 0, startY = 0;
        let currentSnapZone = null;
        const overlay = win.querySelector('.drag-overlay');
        const winId = win.id.replace('win-', '');

        const onMouseMove = (e) => {
            if (!isDragging) return;

            if (!win.classList.contains('dragging')) {
                const dx = e.clientX - startX;
                const dy = e.clientY - startY;
                if (Math.sqrt(dx * dx + dy * dy) > 4) {
                    win.classList.add('dragging');
                    if (overlay) overlay.classList.remove('hidden');
                    
                    const globalOverlay = document.getElementById('vgt-global-drag-overlay');
                    if (globalOverlay) globalOverlay.style.display = 'block';
                    
                    if (win.classList.contains('vgt-window-maximized')) {
                        win.classList.remove('vgt-window-maximized');
                        win.style.width = win.dataset.prevWidth || "850px";
                        win.style.height = win.dataset.prevHeight || "550px";
                        offsetX = parseFloat(win.style.width) / 2;
                        offsetY = 15;
                    }

                    const snapClasses = ['vgt-window-snap-left', 'vgt-window-snap-right', 'vgt-window-snap-topleft', 'vgt-window-snap-bottomleft'];
                    const hasSnap = snapClasses.some(cls => win.classList.contains(cls));
                    if (hasSnap) {
                        win.classList.remove(...snapClasses);
                        win.style.width = win.dataset.prevWidth || "850px";
                        win.style.height = win.dataset.prevHeight || "550px";
                        offsetX = parseFloat(win.style.width) / 2;
                        offsetY = 15;
                    }
                } else {
                    return;
                }
            }

            let top = e.clientY - offsetY;
            let left = e.clientX - offsetX;
            
            if (top < 0) top = 0; 
            
            win.style.left = `${left}px`;
            win.style.top = `${top}px`;

            let preview = document.getElementById('vgt-snap-preview-indicator');
            if (!preview) {
                preview = document.createElement('div');
                preview.id = 'vgt-snap-preview-indicator';
                preview.className = 'vgt-snap-preview-indicator';
                (document.getElementById('vgt-shell-root') || document.body).appendChild(preview);
            }

            const rootEl = document.getElementById('vgt-shell-root');
            const isWin = rootEl && rootEl.classList.contains('vgt-layout-windows');
            const isLin = rootEl && rootEl.classList.contains('vgt-layout-linux');

            let baseTop = "4px";
            let baseLeft = "0px";
            let baseWidth = "100%";
            let baseHeight = "calc(100% - 20px)";

            if (isWin) {
                baseTop = "0px";
                baseHeight = "calc(100% - 10px)";
            } else if (isLin) {
                baseLeft = "80px";
                baseWidth = "calc(100% - 80px)";
            }

            if (e.clientY < 60) {
                currentSnapZone = 'top';
                preview.style.left = baseLeft;
                preview.style.top = baseTop;
                preview.style.width = baseWidth;
                preview.style.height = baseHeight;
                preview.classList.add('visible');
            } else if (e.clientX < 40) {
                currentSnapZone = 'left';
                preview.style.left = baseLeft;
                preview.style.top = baseTop;
                if (isWin) {
                    preview.style.width = "50%";
                } else if (isLin) {
                    preview.style.width = "calc((100% - 80px) / 2)";
                } else {
                    preview.style.width = "50%";
                }
                preview.style.height = baseHeight;
                preview.classList.add('visible');
            } else if (e.clientX > window.innerWidth - 40) {
                currentSnapZone = 'right';
                if (isWin) {
                    preview.style.left = "50%";
                    preview.style.width = "50%";
                } else if (isLin) {
                    preview.style.left = "calc(80px + (100% - 80px) / 2)";
                    preview.style.width = "calc((100% - 80px) / 2)";
                } else {
                    preview.style.left = "50%";
                    preview.style.width = "50%";
                }
                preview.style.top = baseTop;
                preview.style.height = baseHeight;
                preview.classList.add('visible');
            } else {
                currentSnapZone = null;
                preview.classList.remove('visible');
            }
        };

        const onMouseUp = () => {
            isDragging = false;
            
            document.removeEventListener('mousemove', onMouseMove);
            document.removeEventListener('mouseup', onMouseUp);
            
            if (overlay) overlay.classList.add('hidden');
            
            const globalOverlay = document.getElementById('vgt-global-drag-overlay');
            if (globalOverlay) globalOverlay.style.display = 'none';
            
            const preview = document.getElementById('vgt-snap-preview-indicator');
            if (preview) preview.classList.remove('visible');

            if (win.classList.contains('dragging')) {
                win.classList.remove('dragging');
                
                if (currentSnapZone === 'top') {
                    if (!win.classList.contains('vgt-window-maximized')) {
                        this.maximizeWindow(winId);
                    }
                } else if (currentSnapZone === 'left' || currentSnapZone === 'right') {
                    this.activeSnapWindowId = winId;
                    this.snapActiveWindow(currentSnapZone);
                } else {
                    this.saveWindowPosition(winId, win.style.left, win.style.top, win.style.width, win.style.height);
                }
            }
            
            currentSnapZone = null;
        };

        header.addEventListener('mousedown', (e) => {
            if (window.innerWidth <= 768) return;

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
            startX = e.clientX;
            startY = e.clientY;
            offsetX = e.clientX - win.offsetLeft;
            offsetY = e.clientY - win.offsetTop;
            this.focusWindow(winId);
            
            document.addEventListener('mousemove', onMouseMove);
            document.addEventListener('mouseup', onMouseUp);
        });

        header.addEventListener('dblclick', () => {
            this.maximizeWindow(winId);
        });
    },

    initDraggable() {
        const windows = document.querySelectorAll('.window');
        windows.forEach(win => {
            this.makeWindowDraggable(win);
        });
    },

    startResize(e, winId, direction) {
        e.preventDefault();
        this.currentResizeWin = document.getElementById(`win-${winId}`);
        this.resizeDirection = direction;
        
        const snapClasses = ['vgt-window-snap-left', 'vgt-window-snap-right', 'vgt-window-snap-topleft', 'vgt-window-snap-bottomleft', 'vgt-window-maximized'];
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
        
        const globalOverlay = document.getElementById('vgt-global-drag-overlay');
        if (globalOverlay) globalOverlay.style.display = 'block';
        this.currentResizeWin.classList.add('resizing');

        this.resizeBinder = (ev) => this.doResize(ev);
        this.resizeStopBinder = () => {
            document.removeEventListener('mousemove', this.resizeBinder);
            if (overlay) overlay.classList.add('hidden');
            
            const globalOverlay2 = document.getElementById('vgt-global-drag-overlay');
            if (globalOverlay2) globalOverlay2.style.display = 'none';
            
            if (this.currentResizeWin) {
                this.currentResizeWin.classList.remove('resizing');
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
            if (newTop < 0) {
                newHeight = this.startHeight + this.startTop;
                newTop = 0;
            }
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

    /* ==========================================================================
       WINDOW SNAP LAYOUTS
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
        
        const snapClasses = ['vgt-window-snap-left', 'vgt-window-snap-right', 'vgt-window-snap-topleft', 'vgt-window-snap-bottomleft'];
        const isAlreadySnapped = snapClasses.some(cls => win.classList.contains(cls));
        if (!isAlreadySnapped) {
            win.dataset.prevWidth = win.style.width;
            win.dataset.prevHeight = win.style.height;
            win.dataset.prevTop = win.style.top;
            win.dataset.prevLeft = win.style.left;
        }
        
        win.classList.remove(...snapClasses);
        win.classList.add('vgt-window-snap-' + zone);
        
        this.focusWindow(winId);
        this.saveWindowPosition(winId, win.style.left, win.style.top, win.style.width, win.style.height);
        
        const menu = document.getElementById('vgt-global-snap-menu');
        if (menu) menu.classList.add('hidden');
    }
});
