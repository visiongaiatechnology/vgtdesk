jQuery(document).ready(function($) {
    'use strict';

    const config = window.visConfig || {};
    const nonce = config.nonce;

    if (!nonce) {
        console.error('VisionGaia: Security Nonce missing.');
        return;
    }
    
    // --- UI HELPER: VISIONGAIA MODAL ---
    function showModal(title, message, type = 'info', onConfirm = null) {
        // Cleanup old modals
        $('.vis-modal-backdrop').remove();

        let icon = 'dashicons-info';
        let color = '#3b82f6';
        if (type === 'success') { icon = 'dashicons-yes-alt'; color = '#10b981'; }
        if (type === 'error') { icon = 'dashicons-warning'; color = '#ef4444'; }
        if (type === 'confirm') { icon = 'dashicons-shield'; color = '#f59e0b'; }

        // Template Construction
        const modalHtml = `
            <div class="vis-modal-backdrop">
                <div class="vis-modal-content">
                    <div class="vis-modal-header">
                        <div class="vis-modal-title">
                            <span class="dashicons ${icon}" style="color:${color}"></span> ${title}
                        </div>
                        <button class="vis-modal-close"><span class="dashicons dashicons-no-alt"></span></button>
                    </div>
                    <div class="vis-modal-body">
                        ${message}
                    </div>
                    <div class="vis-modal-footer">
                        ${type === 'confirm' ? 
                            '<button class="vis-btn vis-btn-ghost vis-modal-cancel">ABBRECHEN</button>' : ''
                        }
                        <button class="vis-btn vis-btn-neon vis-modal-ok">
                            ${type === 'confirm' ? 'BESTÄTIGEN' : 'OK, VERSTANDEN'}
                        </button>
                    </div>
                </div>
            </div>
        `;

        $('body').append(modalHtml);
        
        // Trigger Animation
        requestAnimationFrame(() => {
            $('.vis-modal-backdrop').addClass('vis-show');
        });

        // Events
        $('.vis-modal-close, .vis-modal-cancel').click(function() {
            closeModal();
        });

        $('.vis-modal-ok').click(function() {
            closeModal();
            if (onConfirm) onConfirm();
        });
    }

    function closeModal() {
        $('.vis-modal-backdrop').removeClass('vis-show');
        setTimeout(() => { $('.vis-modal-backdrop').remove(); }, 300);
    }

    // --- BATCH SCAN ENGINE ---
    function runScanBatch(offset, currentState) {
        $('#vis-scan-progress').show().find('#vis-scan-status-text').text('SCANNING SECTOR ' + offset + '...');
        
        $.post(config.ajaxUrl, { 
            action: 'vis_run_scan', 
            nonce: nonce, 
            offset: offset,
            current_state: currentState
        }, function(res) {
            if(!res.success) {
                showModal('SCAN ABORTED', 'Error: ' + (res.data ? res.data.message : 'Unknown Error'), 'error');
                resetScanUI();
                return;
            }

            if (res.data.status === 'processing') {
                runScanBatch(res.data.offset, res.data.current_state);
            } else {
                finalizeScan(res.data);
            }
        }).fail(function() {
            showModal('CONNECTION LOST', 'Critical communication failure with server.', 'error');
            resetScanUI();
        });
    }

    function finalizeScan(data) {
        resetScanUI();

        if (data.status === 'init') {
            showModal('SYSTEM INITIALIZED', 'Die Baseline wurde erfolgreich erstellt.<br>Das System ist jetzt <strong>GESICHERT</strong>.', 'success', () => location.reload());
            return;
        }

        if(data.status !== 'clean') {
            let count = (data.changes && data.changes.length) ? data.changes.length : 0;
            
            if (count === 0 && data.status === 'warning') {
                showModal('SCAN WARNING', 'Integritäts-Check meldet Fehler, aber keine Details.', 'error');
            } else {
                // Hier wollen wir KEINEN Reload erzwingen, sondern fragen
                showModal('THREAT DETECTED', `Es wurden <strong>${count} Anomalien</strong> gefunden.<br>Bitte prüfen Sie den Integritäts-Report.`, 'error', () => {
                    // Redirect to Integrity Tab if not there
                    if (!location.href.includes('tab=integrity')) {
                         window.location.href = '?page=vis-sentinel&tab=integrity';
                    } else {
                        location.reload();
                    }
                });
            }
        } else {
            showModal('SYSTEM CLEAN', 'Keine unautorisierten Änderungen gefunden.<br>Status: <strong>SECURE</strong>', 'success', () => location.reload());
        }
    }

    function resetScanUI() {
        $('#vis-btn-scan').prop('disabled', false).html('<span class="dashicons dashicons-search"></span> RUN DEEP SCAN');
        $('#vis-scan-progress').hide();
    }

    // TRIGGER SCAN
    $(document).on('click', '#vis-btn-scan', function(e) {
        e.preventDefault();
        $(this).prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> INITIALIZING...');
        runScanBatch(0, []);
    });

    // APPROVE BUTTON - FIXED LOGIC
    $(document).on('click', '#vis-btn-approve', function(e) {
        e.preventDefault();
        
        showModal('BASELINE UPDATE', 'Möchten Sie den <strong>aktuellen Zustand</strong> aller Dateien als neue, sichere Baseline akzeptieren?<br><br><small>Nutzen Sie dies nur, wenn Sie sicher sind, dass die Änderungen legitim sind (z.B. nach einem Update).</small>', 'confirm', function() {
            
            var btn = $('#vis-btn-approve'); // Re-select inside callback
            btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> RE-INDEXING...');
            
            // Neuer Request ohne Payload - Server macht die Arbeit
            $.post(config.ajaxUrl, { 
                action: 'vis_approve_changes', 
                nonce: nonce
            }, function(res) {
                if(res.success) {
                    showModal('BASELINE UPDATED', 'Das System wurde neu indexiert.<br>Status: <strong>SECURE</strong>', 'success', () => location.reload());
                } else {
                    showModal('UPDATE FAILED', 'Konnte Baseline nicht schreiben: ' + (res.data ? res.data.message : 'Unknown'), 'error');
                    btn.prop('disabled', false).html('<span class="dashicons dashicons-yes"></span> APPROVE BASELINE');
                }
            });
        });
    });
});