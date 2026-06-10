/**
 * VGT SENTINEL - MASTER DASHBOARD LOGIC
 * STATUS: DIAMANT SUPREME (WP.ORG COMPLIANT)
 * Architecture: Clean SoC, High-End Modal System & Recursive Scan Engine.
 */
"use strict";

jQuery(document).ready(function($) {
    const config = window.vgtsConfig || {};
    const nonce  = config.nonce;

    if (!nonce) {
        console.error('VGT SENTINEL: Security Nonce missing. Core features may be restricted.');
        return;
    }
    
    // --- UI HELPER: VGT PLATINUM MODAL ---
    function showModal(title, message, type = 'info', onConfirm = null) {
        // Cleanup existing instances
        $('.vgts-modal-backdrop').remove();

        let icon = 'dashicons-info';
        let color = '#3b82f6'; // Indigo Base
        
        if (type === 'success') { icon = 'dashicons-yes-alt'; color = '#10b981'; }
        if (type === 'error')   { icon = 'dashicons-warning'; color = '#ef4444'; }
        if (type === 'confirm') { icon = 'dashicons-shield';  color = '#f59e0b'; }

        const modalHtml = `
            <div class="vgts-modal-backdrop">
                <div class="vgts-modal-content">
                    <div class="vgts-modal-header">
                        <div class="vgts-modal-title">
                            <span class="dashicons ${icon}" style="color:${color}"></span> ${title}
                        </div>
                        <button class="vgts-modal-close" aria-label="Close"><span class="dashicons dashicons-no-alt"></span></button>
                    </div>
                    <div class="vgts-modal-body">
                        ${message}
                    </div>
                    <div class="vgts-modal-footer">
                        ${type === 'confirm' ? 
                            '<button class="vgts-btn vgts-btn-ghost vgts-modal-cancel">CANCEL</button>' : ''
                        }
                        <button class="vgts-btn vgts-btn-primary vgts-modal-ok">
                            ${type === 'confirm' ? 'CONFIRM ACTION' : 'OK, UNDERSTOOD'}
                        </button>
                    </div>
                </div>
            </div>
        `;

        $('body').append(modalHtml);
        
        // Entschärfte Animationstrigger
        setTimeout(() => {
            $('.vgts-modal-backdrop').addClass('vgts-show');
        }, 10);

        // Handlers
        $('.vgts-modal-close, .vgts-modal-cancel').on('click', closeModal);

        $('.vgts-modal-ok').on('click', function() {
            closeModal();
            if (onConfirm && typeof onConfirm === 'function') {
                onConfirm();
            }
        });
    }

    function closeModal() {
        $('.vgts-modal-backdrop').removeClass('vgts-show');
        setTimeout(() => { $('.vgts-modal-backdrop').remove(); }, 300);
    }

    // Safe Reload Helper to prevent POST resubmission prompts
    function safeReload() {
        window.location.href = window.location.href.split('#')[0];
    }

    // --- RECURSIVE BATCH SCAN ENGINE ---
    function runScanBatch(offset, currentState) {
        const $progress = $('#vgts-scan-progress');
        const $statusText = $('#vgts-scan-status-text');
        
        $progress.show();
        $statusText.text('SCANNING SECTOR ' + offset + '...');
        
        $.post(config.ajaxUrl, { 
            action: 'vgts_run_scan', 
            nonce: nonce, 
            offset: offset,
            current_state: JSON.stringify(currentState)
        }, function(res) {
            if(!res.success) {
                showModal('SCAN ABORTED', 'Error: ' + (res.data ? res.data.message : 'Communication breakdown'), 'error');
                resetScanUI();
                return;
            }

            if (res.data.status === 'processing') {
                runScanBatch(res.data.offset, res.data.current_state);
            } else {
                finalizeScan(res.data);
            }
        }).fail(function() {
            showModal('CONNECTION LOST', 'Critical communication failure with the VGT kernel.', 'error');
            resetScanUI();
        });
    }

    function finalizeScan(data) {
        resetScanUI();

        if (data.status === 'init') {
            showModal('SYSTEM INITIALIZED', 'The security baseline has been successfully established.<br>Environment status: <strong>SECURED</strong>.', 'success', safeReload);
            return;
        }

        if(data.status !== 'clean') {
            let count = (data.changes && data.changes.length) ? data.changes.length : 0;
            
            if (count === 0 && data.status === 'warning') {
                showModal('SCAN WARNING', 'Integrity check reported an anomaly without specific file pointers.', 'error');
            } else {
                showModal('THREAT DETECTED', `Detected <strong>${count} system anomalies</strong>.<br>Please analyze the integrity report immediately.`, 'error', () => {
                    if (!location.href.includes('tab=integrity')) {
                         window.location.href = '?page=vgts-sentinel&tab=integrity';
                    } else {
                        safeReload();
                    }
                });
            }
        } else {
            showModal('SYSTEM CLEAN', 'Zero unauthorized modifications detected.<br>Environment status: <strong>SECURE</strong>', 'success', safeReload);
        }
    }

    function resetScanUI() {
        $('#vgts-btn-scan').prop('disabled', false).html('<span class="dashicons dashicons-search"></span> RUN DEEP SCAN');
        $('#vgts-scan-progress').hide();
    }

    // --- EVENT BINDING ---
    
    // Scan Trigger
    $(document).on('click', '#vgts-btn-scan', function(e) {
        e.preventDefault();
        $(this).prop('disabled', true).html('<span class="dashicons dashicons-update vgts-spin"></span> INITIALIZING...');
        runScanBatch(0, {});
    });

    // Baseline Approval
    $(document).on('click', '#vgts-btn-approve', function(e) {
        e.preventDefault();
        
        showModal('BASELINE RE-INDEX', 'Do you authorize the <strong>current state</strong> of all files as the new secure baseline?<br><br><small>Execute this only after legitimate updates or manual file verifications.</small>', 'confirm', function() {
            const $btn = $('#vgts-btn-approve');
            $btn.prop('disabled', true).html('<span class="dashicons dashicons-update vgts-spin"></span> RE-INDEXING...');
            
            $.post(config.ajaxUrl, { 
                action: 'vgts_approve_changes', 
                nonce: nonce
            }, function(res) {
                if(res.success) {
                    showModal('BASELINE UPDATED', 'System integrity matrix has been recalibrated.<br>Status: <strong>SECURE</strong>', 'success', safeReload);
                } else {
                    showModal('UPDATE FAILED', 'Vault write access failed: ' + (res.data ? res.data.message : 'Unknown IO Error'), 'error');
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-yes"></span> APPROVE BASELINE');
                }
            });
        });
    });

    // Success Message Fading (WordPress Default Redirects)
    if ($('.settings-error').length > 0) {
        setTimeout(() => {
            $('.settings-error').fadeOut(500);
        }, 3000);
    }
});
