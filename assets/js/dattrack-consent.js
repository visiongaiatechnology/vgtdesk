(function() {
    const CONSENT_KEY = 'vgt_dt_consent';

    function initVaultLogic() {
        const vault = document.getElementById('vgt-dt-vault');
        
        if (localStorage.getItem(CONSENT_KEY) === null) {
            if (vault) {
                setTimeout(() => {
                    vault.classList.add('vgt-active');
                }, 1500);
                
                const btnAccept = document.getElementById('vgt-dt-accept');
                const btnDeny = document.getElementById('vgt-dt-deny');
                
                if (btnAccept) btnAccept.addEventListener('click', () => dismissVault(true, vault));
                if (btnDeny) btnDeny.addEventListener('click', () => dismissVault(false, vault));
            }
        } else if (localStorage.getItem(CONSENT_KEY) === '1') {
            initTracking();
        }

        // VGT ADDITION: DSGVO Privacy Node Handler initialisieren
        initPrivacyNode();
    }

    function dismissVault(consent, vault) {
        localStorage.setItem(CONSENT_KEY, consent ? '1' : '0');
        if (vault) vault.classList.remove('vgt-active');
        if (consent) initTracking();
        updatePrivacyNodeUI(); // Synchronisiere Shortcode UI falls auf der gleichen Seite
    }

    // --- VGT DSGVO PRIVACY NODE (OPT-OUT KERNEL) ---
    function initPrivacyNode() {
        const node = document.getElementById('vgt-privacy-node');
        if (!node) return;

        const toggleBtn = document.getElementById('vgt-pn-toggle-btn');
        if (!toggleBtn) return;

        toggleBtn.removeAttribute('disabled');
        updatePrivacyNodeUI();

        toggleBtn.addEventListener('click', () => {
            const currentState = localStorage.getItem(CONSENT_KEY);
            
            if (currentState === '1') {
                // Führe Opt-Out durch
                localStorage.setItem(CONSENT_KEY, '0');
            } else {
                // Führe Opt-In durch
                localStorage.setItem(CONSENT_KEY, '1');
                initTracking(); // Sofortiger Pulse beim Opt-In
            }
            
            updatePrivacyNodeUI();
            
            // Verstecke den Consent-Banner, falls er noch offen ist
            const vault = document.getElementById('vgt-dt-vault');
            if (vault) vault.classList.remove('vgt-active');
        });
    }

    function updatePrivacyNodeUI() {
        const node = document.getElementById('vgt-privacy-node');
        if (!node) return;

        const state = localStorage.getItem(CONSENT_KEY);
        const statusText = document.getElementById('vgt-pn-status');
        const toggleBtn = document.getElementById('vgt-pn-toggle-btn');

        // Reset Classes
        node.classList.remove('state-active', 'state-inactive');

        if (state === '1') {
            node.classList.add('state-active');
            statusText.innerText = 'System aktiv (Anonyme Telemetrie wird erfasst)';
            toggleBtn.innerText = 'Tracking deaktivieren (Opt-Out)';
        } else if (state === '0') {
            node.classList.add('state-inactive');
            statusText.innerText = 'System inaktiv (Opt-Out verifiziert)';
            toggleBtn.innerText = 'Tracking aktivieren (Opt-In)';
        } else {
            statusText.innerText = 'Wartend auf Autorisierung...';
            toggleBtn.innerText = 'Tracking erlauben';
        }
    }

    function initTracking() {
        // Fallback-Check: Wenn sich der Status im laufenden Thread ändert, abbrechen.
        if (localStorage.getItem(CONSENT_KEY) !== '1') return;

        // Verhindere mehrfaches Feuern im selben Session-Kontext
        if (window.vgtPulseSent) return;
        window.vgtPulseSent = true;

        const payload = {
            t: document.title,
            p: window.location.pathname,
            r: document.referrer || null,
            s: window.screen.width + 'x' + window.screen.height,
            ts: Date.now()
        };
        
        const jsonStr = JSON.stringify(payload);
        const b64 = btoa(unescape(encodeURIComponent(jsonStr)));
        const token = (typeof vgtConfig !== 'undefined' && vgtConfig.token) ? vgtConfig.token : '';
        const data = JSON.stringify({ d: b64, tkn: token });

        let baseEndpoint = (typeof vgtConfig !== 'undefined' && vgtConfig.endpoint) 
            ? vgtConfig.endpoint 
            : '/wp-admin/admin-ajax.php';
        
        const targetUrl = baseEndpoint + '?action=vgt_sync_pulse';

        if (window.fetch) {
            fetch(targetUrl, { 
                method: 'POST', 
                headers: { 'Content-Type': 'application/json' },
                body: data, 
                keepalive: true 
            }).catch(()=>{});
        } else if (navigator.sendBeacon) {
            navigator.sendBeacon(targetUrl, new Blob([data], { type: 'application/json' }));
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initVaultLogic);
    } else {
        initVaultLogic();
    }
})();