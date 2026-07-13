/**
 * VGT Desktop Engine - Core Namespace & Settings
 */

window.VGTDeskEngine = {
    activeZIndex: 100,
    activeWindows: {
        'welcome': false,
        'settings': false,
        'about': false
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
    /**
     * Classic-mode: open outside iframe workspace (new admin tab).
     * Honors app.classic_mode / open_mode and classic_apps overrides.
     */
    shouldOpenClassic(appId, url) {
        const id = String(appId || '');
        const overrides = (this.userSettings && this.userSettings.classic_apps) ? this.userSettings.classic_apps : {};
        if (id && overrides[id]) {
            return true;
        }
        const app = (typeof vgtConfig !== 'undefined' && vgtConfig.apps) ? vgtConfig.apps[id] : null;
        if (app && (app.classic_mode === true || app.open_mode === 'classic-required')) {
            return true;
        }
        const hay = String(url || (app && app.url) || '').toLowerCase();
        const markers = [
            'customize.php', 'theme-editor.php', 'plugin-editor.php', 'site-editor.php',
            'widgets.php', 'nav-menus.php', 'update-core.php', 'elementor', 'brizy',
            'fl_builder', 'oxygen', 'et_fb='
        ];
        return markers.some((m) => hay.indexOf(m) !== -1);
    },

    openClassicAdmin(url) {
        const clean = this.cleanUrl(url);
        if (!clean || clean === 'about:blank') {
            return;
        }
        try {
            const u = new URL(clean, window.location.origin);
            u.searchParams.delete('vgt_iframe');
            window.open(u.toString(), '_blank', 'noopener,noreferrer');
            this.addLog && this.addLog('Classic-Mode: Admin in neuem Tab geöffnet.');
        } catch (e) {
            window.open(clean, '_blank', 'noopener,noreferrer');
        }
    },

    cleanUrl(urlStr) {
        try {
            if (typeof urlStr === 'string' && /^data:image\/(png|jpe?g|webp|gif);base64,/i.test(urlStr.trim())) {
                return urlStr.trim();
            }
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
     * True when URL targets wp-admin (or login) — never the public front.
     */
    isAdminPortalUrl(urlStr) {
        try {
            if (!urlStr || urlStr === 'about:blank') return false;
            const url = new URL(urlStr, window.location.origin);
            const path = (url.pathname || '').toLowerCase();
            if (/\/wp-admin(\/|$)/.test(path)) return true;
            if (/wp-login\.php/.test(path)) return true;
            // Bare admin scripts only valid when already under admin base path resolution.
            const adminBase = (typeof vgtConfig !== 'undefined' && vgtConfig.adminUrl)
                ? String(vgtConfig.adminUrl)
                : (window.location.origin + '/wp-admin/');
            if (String(urlStr).indexOf(adminBase) === 0) return true;
            return false;
        } catch (e) {
            return false;
        }
    },

    /**
     * Build a safe same-origin admin portal URL for an app key.
     * Never returns the public homepage (root) — that triggers XFO DENY stacks.
     */
    resolvePortalUrl(appId, preferredUrl) {
        const adminBase = (typeof vgtConfig !== 'undefined' && vgtConfig.adminUrl)
            ? String(vgtConfig.adminUrl).replace(/\/?$/, '/')
            : (window.location.origin + '/wp-admin/');

        const candidates = [];
        if (preferredUrl) candidates.push(preferredUrl);

        const app = (typeof vgtConfig !== 'undefined' && vgtConfig.apps && appId)
            ? vgtConfig.apps[appId]
            : null;
        if (app && app.url) candidates.push(app.url);

        // Known WordPress admin list screens (sanitize_key mangled slugs).
        const known = {
            editphppost_typepage: 'edit.php?post_type=page',
            'edit.php?post_type=page': 'edit.php?post_type=page',
            editphp: 'edit.php',
            pluginsphp: 'plugins.php',
            themesphp: 'themes.php',
            uploadphp: 'upload.php',
            usersphp: 'users.php',
            editcommentsphp: 'edit-comments.php',
            indexphp: 'index.php',
            optionsgeneralphp: 'options-general.php',
            toolspphp: 'tools.php',
            toolsphp: 'tools.php'
        };
        const idKey = String(appId || '').toLowerCase().replace(/[^a-z0-9_\-?=.&]/g, '');
        if (known[idKey]) {
            candidates.push(adminBase + known[idKey]);
        }
        // Heuristic: id contains "page" + edit → pages list
        if (/post_type.?page|editphp.*page|pages?/i.test(String(appId || ''))) {
            candidates.push(adminBase + 'edit.php?post_type=page');
        }
        if (/plugin/i.test(String(appId || ''))) {
            candidates.push(adminBase + 'plugins.php');
        }
        if (/theme|design|designs/i.test(String(appId || ''))) {
            candidates.push(adminBase + 'themes.php');
        }

        for (let i = 0; i < candidates.length; i++) {
            let raw = candidates[i];
            if (!raw || raw === 'about:blank') continue;

            // Relative admin file → prefix admin base
            if (!/^https?:\/\//i.test(raw) && /\.php/i.test(raw) && raw.indexOf('/wp-admin') === -1) {
                raw = adminBase + raw.replace(/^\//, '');
            }

            const safe = this.cleanUrl(raw);
            if (!safe || safe === 'about:blank') continue;

            // Reject public front (/, /index.php without wp-admin)
            try {
                const u = new URL(safe);
                const p = (u.pathname || '').replace(/\/+$/, '') || '/';
                if (p === '/' || p === '' || p === '/index.php') {
                    continue;
                }
            } catch (e) {
                continue;
            }

            if (this.isAdminPortalUrl(safe)) {
                return safe;
            }
        }
        return '';
    },

    /**
     * Ensure vgt_iframe=true on an admin portal URL.
     */
    withIframeParam(urlStr) {
        try {
            const u = new URL(urlStr, window.location.origin);
            u.searchParams.set('vgt_iframe', 'true');
            return u.toString();
        } catch (e) {
            if (!urlStr) return '';
            return urlStr + (urlStr.indexOf('?') === -1 ? '?' : '&') + 'vgt_iframe=true';
        }
    },

    _isTruthy(val) {
        return val === true || val === 'true' || val === 1 || val === '1';
    },

    /**
     * BENUTZERPROFIL-AJAX SYNC ENGINE:
     * Übermittelt Einstellungsänderungen des WordPress-Users live und asynchron an die DB.
     */
    saveUserSetting(type, value) {
        if (typeof vgtConfig === 'undefined' || !vgtConfig.ajaxUrl) return;

        // Speicher-Objekt als Boolean normalisieren falls anwendbar
        let normalizedValue = value;
        if (type === 'blur' || type === 'widgets_visible' || type === 'icons_visible' || type === 'audio_enabled' || type === 'auto_redirect' || type === 'first_run_completed' || type === 'show_welcome_on_startup') {
            normalizedValue = (value === 'true' || value === true);
        }
        this.userSettings[type] = normalizedValue;

        const formData = new FormData();
        formData.append('action', 'vgt_save_user_settings');
        formData.append('nonce', vgtConfig.nonce);
        formData.append('setting_type', type);

        let sendVal = value;
        if (typeof value === 'boolean') {
            sendVal = value ? 'true' : 'false';
        } else if (typeof value === 'object') {
            sendVal = JSON.stringify(value);
        } else {
            sendVal = String(value);
        }
        formData.append('value', sendVal);

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
        if (win && (win.classList.contains('vgt-window-maximized') || win.style.width === "100%" || win.style.width.includes("calc") || win.style.height.includes("calc"))) {
            return;
        }

        // Sicherung gegen Array-Verzerrung
        if (!this.userSettings.window_settings || Array.isArray(this.userSettings.window_settings)) {
            this.userSettings.window_settings = {};
        }

        this.userSettings.window_settings[winId] = { left, top, width, height };
        this.saveUserSetting('window_settings', this.userSettings.window_settings);
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

    addLog(text) {
        const logs = document.getElementById('cc-logs');
        if (!logs) return;
        const span = document.createElement('span');
        span.textContent = `• [${new Date().toLocaleTimeString('de-DE')}] ${text}`;
        logs.appendChild(span);
        logs.scrollTop = logs.scrollHeight;
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
        if (!this._isTruthy(this.userSettings.audio_enabled)) return;
        this.initAudio();
        if (!this.audioCtx) return;
        
        const ctx = this.audioCtx;
        const now = ctx.currentTime;
        const pack = this.userSettings.sound_pack || 'synth_default';
        
        if (pack === 'synth_default') {
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
        } else if (pack === 'cyber_neon') {
            if (type === 'startup') {
                // Futuristic sci-fi ascending arpeggio sweep
                const freqs = [180, 270, 360, 540, 720];
                freqs.forEach((freq, index) => {
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    const filter = ctx.createBiquadFilter();
                    
                    osc.type = index % 2 === 0 ? 'sawtooth' : 'square';
                    osc.frequency.setValueAtTime(freq, now + index * 0.04);
                    osc.frequency.exponentialRampToValueAtTime(freq * 1.5, now + index * 0.04 + 0.3);
                    
                    filter.type = 'bandpass';
                    filter.Q.setValueAtTime(8, now);
                    filter.frequency.setValueAtTime(100, now + index * 0.04);
                    filter.frequency.exponentialRampToValueAtTime(3000, now + index * 0.04 + 0.25);
                    
                    gain.gain.setValueAtTime(0, now + index * 0.04);
                    gain.gain.linearRampToValueAtTime(0.08, now + index * 0.04 + 0.05);
                    gain.gain.exponentialRampToValueAtTime(0.001, now + index * 0.04 + 0.5);
                    
                    osc.connect(filter);
                    filter.connect(gain);
                    gain.connect(ctx.destination);
                    
                    osc.start(now + index * 0.04);
                    osc.stop(now + index * 0.04 + 0.6);
                });
            } else if (type === 'click') {
                // Resonant laser-like click
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'triangle';
                osc.frequency.setValueAtTime(1800, now);
                osc.frequency.exponentialRampToValueAtTime(300, now + 0.04);
                
                gain.gain.setValueAtTime(0.12, now);
                gain.gain.exponentialRampToValueAtTime(0.001, now + 0.04);
                
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start(now);
                osc.stop(now + 0.05);
            } else if (type === 'alert') {
                // Detuned cyber alarm / warning laser pulse
                [0, 0.14].forEach((delay) => {
                    const osc1 = ctx.createOscillator();
                    const osc2 = ctx.createOscillator();
                    const gain = ctx.createGain();
                    
                    osc1.type = 'sawtooth';
                    osc2.type = 'sawtooth';
                    
                    osc1.frequency.setValueAtTime(500, now + delay);
                    osc1.frequency.linearRampToValueAtTime(200, now + delay + 0.12);
                    osc2.frequency.setValueAtTime(496, now + delay);
                    osc2.frequency.linearRampToValueAtTime(196, now + delay + 0.12);
                    
                    gain.gain.setValueAtTime(0, now + delay);
                    gain.gain.linearRampToValueAtTime(0.1, now + delay + 0.02);
                    gain.gain.exponentialRampToValueAtTime(0.001, now + delay + 0.12);
                    
                    osc1.connect(gain);
                    osc2.connect(gain);
                    gain.connect(ctx.destination);
                    
                    osc1.start(now + delay);
                    osc2.start(now + delay);
                    osc1.stop(now + delay + 0.13);
                    osc2.stop(now + delay + 0.13);
                });
            }
        } else if (pack === 'classic_bell') {
            if (type === 'startup') {
                // E-major warm triad bell chime with natural metallic overtones
                const notes = [329.63, 415.30, 493.88, 659.25]; // E4, G#4, B4, E5
                notes.forEach((fundamental) => {
                    const partials = [
                        { ratio: 1.0, amp: 0.12 },
                        { ratio: 2.0, amp: 0.04 },
                        { ratio: 3.5, amp: 0.02 },
                        { ratio: 5.1, amp: 0.01 }
                    ];
                    
                    partials.forEach((part) => {
                        const osc = ctx.createOscillator();
                        const gain = ctx.createGain();
                        osc.type = 'sine';
                        osc.frequency.setValueAtTime(fundamental * part.ratio, now);
                        
                        gain.gain.setValueAtTime(0, now);
                        gain.gain.linearRampToValueAtTime(part.amp, now + 0.04);
                        gain.gain.exponentialRampToValueAtTime(0.0001, now + 2.0);
                        
                        osc.connect(gain);
                        gain.connect(ctx.destination);
                        
                        osc.start(now);
                        osc.stop(now + 2.1);
                    });
                });
            } else if (type === 'click') {
                // Cozy woodblock/bell tap
                const fundamental = 1200;
                const partials = [
                    { ratio: 1.0, amp: 0.15 },
                    { ratio: 2.0, amp: 0.04 },
                    { ratio: 3.16, amp: 0.02 }
                ];
                partials.forEach((part) => {
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(fundamental * part.ratio, now);
                    
                    gain.gain.setValueAtTime(part.amp, now);
                    gain.gain.exponentialRampToValueAtTime(0.001, now + 0.06);
                    
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start(now);
                    osc.stop(now + 0.07);
                });
            } else if (type === 'alert') {
                // Warm double chime ring (ding-dong)
                const playChime = (freq, delay) => {
                    const partials = [
                        { ratio: 1.0, amp: 0.12 },
                        { ratio: 2.0, amp: 0.04 },
                        { ratio: 3.0, amp: 0.02 }
                    ];
                    partials.forEach((part) => {
                        const osc = ctx.createOscillator();
                        const gain = ctx.createGain();
                        osc.type = 'sine';
                        osc.frequency.setValueAtTime(freq * part.ratio, now + delay);
                        
                        gain.gain.setValueAtTime(0, now + delay);
                        gain.gain.linearRampToValueAtTime(part.amp, now + delay + 0.02);
                        gain.gain.exponentialRampToValueAtTime(0.0001, now + delay + 0.6);
                        
                        osc.connect(gain);
                        gain.connect(ctx.destination);
                        osc.start(now + delay);
                        osc.stop(now + delay + 0.65);
                    });
                };
                playChime(587.33, 0);       // D5
                playChime(440.00, 0.18);    // A4
            }
        } else if (pack === 'digital_minimal') {
            if (type === 'startup') {
                // Elegant, smooth minimal double-blip
                const playBlip = (freq, start, duration) => {
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(freq, now + start);
                    
                    gain.gain.setValueAtTime(0, now + start);
                    gain.gain.linearRampToValueAtTime(0.12, now + start + 0.02);
                    gain.gain.exponentialRampToValueAtTime(0.001, now + start + duration);
                    
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start(now + start);
                    osc.stop(now + start + duration + 0.05);
                };
                playBlip(523.25, 0, 0.08);    // C5
                playBlip(783.99, 0.08, 0.15); // G5
            } else if (type === 'click') {
                // Discrete mechanical tick
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(2200, now);
                osc.frequency.linearRampToValueAtTime(1800, now + 0.008);
                
                gain.gain.setValueAtTime(0.08, now);
                gain.gain.exponentialRampToValueAtTime(0.001, now + 0.008);
                
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start(now);
                osc.stop(now + 0.01);
            } else if (type === 'alert') {
                // Minimalist double beep
                const playBeep = (start) => {
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(660, now + start);
                    
                    gain.gain.setValueAtTime(0, now + start);
                    gain.gain.linearRampToValueAtTime(0.1, now + start + 0.01);
                    gain.gain.exponentialRampToValueAtTime(0.001, now + start + 0.05);
                    
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start(now + start);
                    osc.stop(now + start + 0.06);
                };
                playBeep(0);
                playBeep(0.09);
            }
        }
    }
};
