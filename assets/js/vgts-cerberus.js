/**
 * VGT SENTINEL - CERBERUS DASHBOARD LOGIC
 * STATUS: PLATIN STATUS
 */
"use strict";

document.addEventListener('DOMContentLoaded', () => {
    const CerberusUI = {
        init() {
            this.bindEvents();
            console.log('VGT CERBERUS: Interface initialized.');
        },

        bindEvents() {
            // Event Delegation for dynamically rendered tables
            document.addEventListener('click', (e) => {
                const unbanBtn = e.target.closest('.vgts-cerberus-btn-unban');
                
                if (unbanBtn) {
                    e.preventDefault();
                    const ip = unbanBtn.getAttribute('data-ip');
                    
                    if (!ip) return;

                    // Standard confirmation dialog
                    if (!confirm(`VGT SECURITY ALERT:\nDo you really want to unban IP ${ip} and restore access to the system?`)) {
                        return;
                    }

                    // Proceed with unban
                    const formData = new FormData();
                    formData.append('action', 'vgts_dashboard_unban_ip');
                    formData.append('ip', ip);
                    
                    // vgtsConfig is provided globally by Dashboard Core
                    if (typeof vgtsConfig !== 'undefined') {
                        formData.append('nonce', vgtsConfig.nonce);
                    }

                    // Show loading state
                    const originalHtml = unbanBtn.innerHTML;
                    unbanBtn.innerHTML = '<span class="dashicons dashicons-update spin"></span>...';
                    unbanBtn.disabled = true;

                    fetch(vgtsConfig.ajaxUrl, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('VGT SENTINEL ERROR: ' + (data.data || 'Database operation failed.'));
                            unbanBtn.innerHTML = originalHtml;
                            unbanBtn.disabled = false;
                        }
                    })
                    .catch(error => {
                        console.error('Fetch error:', error);
                        alert('CRITICAL: Network failure during unban operation.');
                        unbanBtn.innerHTML = originalHtml;
                        unbanBtn.disabled = false;
                    });
                }
            });
        }
    };

    CerberusUI.init();
});