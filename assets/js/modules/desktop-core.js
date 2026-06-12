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
        if (type === 'blur' || type === 'widgets_visible' || type === 'icons_visible' || type === 'audio_enabled' || type === 'auto_redirect' || type === 'first_run_completed') {
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
    }
};
