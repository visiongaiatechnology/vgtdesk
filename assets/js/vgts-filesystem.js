/**
 * VGT SENTINEL - FILESYSTEM DASHBOARD LOGIC
 * STATUS: PLATIN STATUS
 */
"use strict";

document.addEventListener('DOMContentLoaded', () => {
    const FilesystemUI = {
        init() {
            this.setupLanguageSync();
            console.log('VGT FILESYSTEM: Guard UI Kernel Active.');
        },

        setupLanguageSync() {
            const toggle   = document.getElementById('vgts-fs-lang-toggle');
            const wrapper  = document.getElementById('vgts-fs-container');
            const langKey  = 'vgts_v7_lang_preference';

            if (!toggle || !wrapper) return;

            // Load initial state from central preference
            if (localStorage.getItem(langKey) === 'en') {
                toggle.checked = true;
                wrapper.classList.add('vgts-state-en');
                wrapper.classList.remove('vgts-state-de');
            } else {
                wrapper.classList.add('vgts-state-de');
            }

            toggle.addEventListener('change', (e) => {
                const newLang = e.target.checked ? 'en' : 'de';
                if (e.target.checked) {
                    wrapper.classList.add('vgts-state-en');
                    wrapper.classList.remove('vgts-state-de');
                } else {
                    wrapper.classList.remove('vgts-state-en');
                    wrapper.classList.add('vgts-state-de');
                }
                localStorage.setItem(langKey, newLang);
                // Sync event for other open tabs
                window.dispatchEvent(new Event('vgts_lang_sync'));
            });

            window.addEventListener('vgts_lang_sync', () => {
                const globalLang = localStorage.getItem(langKey);
                toggle.checked = (globalLang === 'en');
                if (toggle.checked) {
                    wrapper.classList.add('vgts-state-en');
                    wrapper.classList.remove('vgts-state-de');
                } else {
                    wrapper.classList.remove('vgts-state-en');
                    wrapper.classList.add('vgts-state-de');
                }
            });
        }
    };

    FilesystemUI.init();
});