/**
 * VGT SENTINEL - AIRLOCK SPECIFIC LOGIC
 * STATUS: PLATIN STATUS
 */
"use strict";

document.addEventListener('DOMContentLoaded', () => {
    const toggle   = document.getElementById('vgts-airlock-lang-toggle');
    const wrapper  = document.getElementById('vgts-airlock-container');
    const langKey  = 'vgts_v7_lang_preference';

    if (!toggle || !wrapper) return;

    // Load initial state
    if (localStorage.getItem(langKey) === 'en') {
        toggle.checked = true;
        wrapper.classList.add('vgts-state-en');
    }

    // Handle user toggle
    toggle.addEventListener('change', (e) => {
        if (e.target.checked) {
            wrapper.classList.add('vgts-state-en');
            localStorage.setItem(langKey, 'en');
            window.dispatchEvent(new Event('vgts_lang_sync'));
        } else {
            wrapper.classList.remove('vgts-state-en');
            localStorage.setItem(langKey, 'de');
            window.dispatchEvent(new Event('vgts_lang_sync'));
        }
    });

    // Sync across potential other VGT tabs if needed
    window.addEventListener('vgts_lang_sync', () => {
        if (localStorage.getItem(langKey) === 'en') {
            toggle.checked = true;
            wrapper.classList.add('vgts-state-en');
        } else {
            toggle.checked = false;
            wrapper.classList.remove('vgts-state-en');
        }
    });
});