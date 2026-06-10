/**
 * VGT SENTINEL - OVERVIEW DASHBOARD LOGIC
 * STATUS: PLATIN STATUS
 */
"use strict";

document.addEventListener('DOMContentLoaded', () => {
    const OverviewUI = {
        init() {
            this.setupLanguageSync();
            this.bindApprovalButton();
            console.log('VGT OVERVIEW: Command Center initialized.');
        },

        setupLanguageSync() {
            const toggle  = document.getElementById('vgts-global-lang-toggle');
            const wrapper = document.getElementById('vgts-master-container');
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

        bindApprovalButton() {
            const approveBtn = document.getElementById('vgts-btn-approve');
            if (!approveBtn) return;

            approveBtn.addEventListener('click', (e) => {
                e.preventDefault();
                
                if (!confirm('VGT SECURITY ALERT:\nAuthorize all current system files and create a new integrity baseline?')) {
                    return;
                }

                const originalText = approveBtn.innerHTML;
                approveBtn.innerHTML = '<span class="dashicons dashicons-update spin"></span> RE-INDEXING...';
                approveBtn.disabled = true;

                const formData = new FormData();
                formData.append('action', 'vgts_approve_changes');
                if (typeof vgtsConfig !== 'undefined') {
                    formData.append('nonce', vgtsConfig.nonce);
                }

                fetch(vgtsConfig.ajaxUrl, {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert('System Baseline successfully re-indexed.');
                        location.reload();
                    } else {
                        alert('Approval failed: ' + (data.data.message || 'Unknown error'));
                        approveBtn.innerHTML = originalText;
                        approveBtn.disabled = false;
                    }
                })
                .catch(err => {
                    console.error('Integrity Approval Failure:', err);
                    alert('CRITICAL: Network disruption during baseline approval.');
                    approveBtn.innerHTML = originalText;
                    approveBtn.disabled = false;
                });
            });
        }
    };

    OverviewUI.init();
});