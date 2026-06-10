/**
 * VGT SENTINEL - MU-DEPLOYER DASHBOARD LOGIC
 * STATUS: PLATIN STATUS
 */
"use strict";

document.addEventListener('DOMContentLoaded', () => {
    const MUDeployerUI = {
        init() {
            this.setupLanguageSync();
            console.log('VGT MU-DEPLOYER: Manual Deployment Bridge Active.');
        },

        setupLanguageSync() {
            const toggle  = document.getElementById('vgts-mu-lang-toggle');
            const wrapper = document.getElementById('vgts-mu-container');
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

    MUDeployerUI.init();
});