/**
 * VGT Desktop Engine — Orchestrator
 * Version: 1.0.0-Beta (Modular Architecture)
 *
 * This file is the lean init-only orchestrator. All domain logic lives in:
 *   modules/desktop-core.js       → Shared state, utilities, AJAX sync, audio
 *   modules/desktop-windows.js    → Window CRUD, focus, theming, settings
 *   modules/desktop-draggable.js  → Window drag, resize, snap layouts
 *   modules/desktop-icons.js      → Icon grid, icon drag, deep linking
 *   modules/desktop-menus.js      → Dock, start menu, control center, context menu, submenus
 *   modules/desktop-widgets.js    → Widgets, sentinel, latency graph
 *   modules/desktop-spotlight.js  → Spotlight search & command runner
 *   modules/desktop-modals.js     → Modal dialog engine
 *   modules/desktop-folders.js    → Folder management
 *
 * Zero-Overheat-Doktrin: No frameworks. No build pipeline. No compromise on UX.
 */

Object.assign(window.VGTDeskEngine, {
    init() {
        this.startClock();
        this.initDraggable();
        
        // Erst Einstellungen laden, dann anordnen, um das geladene Profil anzuwenden
        this.loadSavedSettings();
        this.arrangeDesktopIcons();
        
        this.initIconDraggable();
        this.initWorkspaceContextMenu();
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
        
        // Deep Links verarbeiten
        const urlParams = new URLSearchParams(window.location.search);
        const redirectTo = urlParams.get('vgt_redirect_to');
        if (redirectTo) {
            this.openDeepLink(redirectTo);
            urlParams.delete('vgt_redirect_to');
            const cleanSearch = urlParams.toString();
            const cleanUrl = window.location.pathname + (cleanSearch ? '?' + cleanSearch : '');
            window.history.replaceState({}, document.title, cleanUrl);
        }

        // Setup-Wizard falls nicht beendet initialisieren
        this.initFirstRunWizard();

        // Icons im Raster neu berechnen, wenn sich die Fenstergröße ändert
        window.addEventListener('resize', () => {
            const zoom = parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--vgt-font-zoom')) || 1;
            const shell = document.getElementById('vgt-shell-root');
            if (shell) {
                shell.style.setProperty('width', `${window.innerWidth / zoom}px`, 'important');
                shell.style.setProperty('height', `${window.innerHeight / zoom}px`, 'important');
            }
            this.arrangeDesktopIcons();
        });
    }
});

document.addEventListener('DOMContentLoaded', () => {
    VGTDeskEngine.init();
});