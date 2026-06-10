/**
 * VGT SENTINEL - ANTIBOT DASHBOARD LOGIC
 * STATUS: PLATIN STATUS
 */
"use strict";

document.addEventListener('DOMContentLoaded', () => {
    const AntibotUI = {
        init() {
            this.setupLangToggle();
            this.setupScanner();
            this.setupDynamicHooks();
            console.log('VGT ANTIBOT: Interface initialized.');
        },

        setupLangToggle() {
            const toggle  = document.getElementById('vgts-antibot-lang-toggle');
            const wrapper = document.getElementById('vgts-antibot-container');
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
        },

        setupScanner() {
            const scanBtn = document.getElementById('vgts-scan-hooks-btn');
            if (!scanBtn) return;

            scanBtn.addEventListener('click', async () => {
                const plugin = document.getElementById('vgts-plugin-select').value;
                if (!plugin) return alert('Select a target module first.');

                scanBtn.innerHTML = '<span class="dashicons dashicons-update spin"></span>';
                scanBtn.disabled = true;
                
                const formData = new FormData();
                formData.append('action', 'vgts_scan_plugin');
                formData.append('plugin_file', plugin);
                // vgtsConfig is globally provided by Dashboard Core
                if (typeof vgtsConfig !== 'undefined') {
                    formData.append('nonce', vgtsConfig.nonce);
                }

                try {
                    const response = await fetch(vgtsConfig.ajaxUrl, { method: 'POST', body: formData });
                    const data = await response.json();
                    
                    if (data.success) {
                        const container = document.getElementById('vgts-hook-container');
                        container.innerHTML = '';
                        
                        data.data.hooks.forEach(hook => {
                            // Filter logic for likely execution vectors
                            if (hook.includes('submit') || hook.includes('process') || hook.includes('validate') || hook.includes('error') || hook.includes('save') || hook.includes('insert')) {
                                container.innerHTML += `
                                    <div class="vgts-hook-item">
                                        <input type="checkbox" class="vgts-dynamic-hook-cb" value="${hook}">
                                        ${hook}
                                    </div>
                                `;
                            }
                        });
                        document.getElementById('vgts-scan-results').style.display = 'block';
                    } else {
                        alert('VGT SENTINEL Scan Error: ' + (data.data || 'Unknown error'));
                    }
                } catch (e) {
                    alert('System Failure during scan.');
                }
                
                scanBtn.innerHTML = 'SCAN';
                scanBtn.disabled = false;
            });
        },

        setupDynamicHooks() {
            // Event Delegation: Statt inline onclick="addHookToActive(this)"
            document.addEventListener('change', (e) => {
                if (e.target && e.target.classList.contains('vgts-dynamic-hook-cb')) {
                    const checkbox = e.target;
                    if (checkbox.checked) {
                        const activeContainer = document.getElementById('vgts-active-hooks');
                        
                        if (activeContainer.innerHTML.includes('Keine dynamischen Hooks aktiv.') || activeContainer.innerHTML.includes('No dynamic hooks active.')) {
                            activeContainer.innerHTML = '';
                        }
                        
                        const existingInput = document.querySelector(`input[name="vgts_config[antibot_custom_hooks][]"][value="${checkbox.value}"]`);
                        if (!existingInput) {
                            activeContainer.insertAdjacentHTML('beforeend', `
                                <div class="vgts-hook-item">
                                    <input type="checkbox" name="vgts_config[antibot_custom_hooks][]" value="${checkbox.value}" checked>
                                    ${checkbox.value}
                                </div>
                            `);
                        }
                    }
                }
            });
        }
    };

    AntibotUI.init();
});