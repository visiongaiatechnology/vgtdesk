/**
 * VGT Desktop Module - Aero Spotlight Search & Command Runner
 * Handles: initSpotlight, toggleSpotlight, searchSpotlight, updateSpotlightSelection,
 *          executeSpotlightItem, getSearchableItems, renderSpotlightResults, executeCommand
 */

Object.assign(window.VGTDeskEngine, {
    spotlightSelectedIndex: -1,
    spotlightItems: [],

    initSpotlight() {
        const input = document.getElementById('vgt-spotlight-input');
        const spotlight = document.getElementById('vgt-spotlight');
        if (!input || !spotlight) return;
        
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.code === 'Space') {
                e.preventDefault();
                this.toggleSpotlight();
            }
        });
        
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
                
                if (value.startsWith('/')) {
                    this.executeCommand(value);
                    this.toggleSpotlight(false);
                    return;
                }
                
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
        items.push({ id: 'settings', title: 'Command Center', type: 'app', desc: 'System-Konfiguration, Farben und Hintergründe' });
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
        items.push({ id: 'cmd-layout', title: '/layout [macos|windows|linux]', type: 'cmd', desc: 'Ändert das Layout-Design des Desktops' });
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
        } else if (cmd === '/layout') {
            const layouts = ['macos', 'windows', 'linux'];
            if (layouts.includes(arg.toLowerCase())) {
                this.changeLayoutStyle(arg.toLowerCase());
            } else {
                this.playSound('alert');
                this.addLog(`Fehler: Ungültiges Layout. Erlaubt: ${layouts.join(', ')}`);
            }
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
    }
});
