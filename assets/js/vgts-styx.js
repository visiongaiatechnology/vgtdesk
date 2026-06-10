/**
 * VGT SENTINEL - STYX DASHBOARD LOGIC
 * STATUS: PLATIN STATUS
 */
"use strict";

document.addEventListener('DOMContentLoaded', () => {
    const StyxUI = {
        init() {
            this.setupLanguageSync();
            console.log('VGT STYX: Outbound Shield Interface Active.');
        },

        setupLanguageSync() {
            const toggle  = document.getElementById('vgts-styx-lang-toggle');
            const wrapper = document.getElementById('vgts-styx-container');
            const langKey = 'vgts_v7_lang_preference';

            if (!toggle || !wrapper) return;

            if (localStorage.getItem(langKey) === 'en') {
                toggle.checked = true;
                wrapper.classList.add('vgts-state-en');
            }

            toggle.addEventListener('change', (e) => {
                const newLang = e.target.checked ? 'en' : 'de';
                if (e.target.checked) wrapper.classList.add('vgts-state-en');
                else wrapper.classList.remove('vgts-state-en');
                
                localStorage.setItem(langKey, newLang);
                window.dispatchEvent(new Event('vgts_lang_sync'));
            });

            window.addEventListener('vgts_lang_sync', () => {
                toggle.checked = localStorage.getItem(langKey) === 'en';
                if (toggle.checked) wrapper.classList.add('vgts-state-en');
                else wrapper.classList.remove('vgts-state-en');
            });
        }
    };

    StyxUI.init();
});