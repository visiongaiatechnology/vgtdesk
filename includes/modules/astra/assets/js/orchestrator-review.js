/**
 * STATUS: DIAMANT VGT SUPREME
 * VGTAstra patch review and side-by-side diff UI.
 */

window.VGTAstraPatchReview = (() => {
    'use strict';

    function renderPatchVault(config, nodes, createTextElement, preparePatchReview) {
        nodes.patchList.replaceChildren();
        nodes.btnClearPatches.disabled = config.activePlugin === '' || config.proposals.length === 0;
        if (config.proposals.length === 0) {
            nodes.patchList.appendChild(createTextElement('div', 'vgta-placeholder-text', 'No staged file proposals.'));
            return;
        }

        config.proposals.forEach((proposal) => {
            const row = document.createElement('div');
            row.className = proposal.committed ? 'vgta-patch-item committed' : 'vgta-patch-item';
            const info = document.createElement('div');
            info.className = 'vgta-patch-info';
            info.appendChild(createTextElement('span', 'path', proposal.path || 'unknown'));
            const mode = proposal.commit_mode ? proposal.commit_mode.toUpperCase() : 'PENDING';
            info.appendChild(createTextElement('span', 'meta', `${proposal.actor || 'agent'} - ${proposal.model || 'model'} - ${proposal.bytes || 0} bytes - ${mode}`));
            if (proposal.workspace_path) {
                info.appendChild(createTextElement('span', 'meta workspace', proposal.workspace_path));
            }
            row.appendChild(info);
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'vgta-btn success tiny';
            button.textContent = proposal.committed ? 'COMMITTED' : 'REVIEW';
            button.disabled = Boolean(proposal.committed);
            button.addEventListener('click', () => preparePatchReview(proposal.id));
            row.appendChild(button);
            nodes.patchList.appendChild(row);
        });
    }

    function openPatchReviewModal(review, createTextElement, commitProposal) {
        closePatchReviewModal();
        const overlay = document.createElement('div');
        overlay.className = 'vgta-diff-overlay';
        overlay.id = 'vgta-diff-overlay';
        const modal = document.createElement('div');
        modal.className = 'vgta-diff-modal';
        overlay.appendChild(modal);
        const header = document.createElement('div');
        header.className = 'vgta-diff-header';
        header.appendChild(createTextElement('div', 'vgta-diff-title', 'Patch Review'));
        header.appendChild(createTextElement('div', 'vgta-diff-path', review.path || 'unknown'));
        modal.appendChild(header);
        const grid = document.createElement('div');
        grid.className = 'vgta-diff-grid';
        grid.appendChild(createDiffPane('CURRENT', review.current_code || '', createTextElement));
        grid.appendChild(createDiffPane('PROPOSED', review.proposed_code || '', createTextElement));
        modal.appendChild(grid);
        const table = document.createElement('div');
        table.className = 'vgta-diff-table';
        (Array.isArray(review.diff) ? review.diff : []).forEach((row) => {
            const line = document.createElement('div');
            line.className = `vgta-diff-row ${row.type || 'same'}`;
            line.appendChild(createTextElement('span', 'line-num', String(row.left || '')));
            line.appendChild(createTextElement('span', 'line-code', row.left_text || ''));
            line.appendChild(createTextElement('span', 'line-num', String(row.right || '')));
            line.appendChild(createTextElement('span', 'line-code', row.right_text || ''));
            table.appendChild(line);
        });
        modal.appendChild(table);
        const actions = document.createElement('div');
        actions.className = 'vgta-diff-actions';
        const cancel = document.createElement('button');
        cancel.type = 'button';
        cancel.className = 'vgta-btn secondary';
        cancel.textContent = 'CANCEL';
        cancel.addEventListener('click', closePatchReviewModal);
        actions.appendChild(cancel);
        const confirm = document.createElement('button');
        confirm.type = 'button';
        confirm.className = 'vgta-btn success';
        confirm.textContent = 'CONFIRM COMMIT';
        confirm.addEventListener('click', () => commitProposal(review.proposal_id, review.review_token));
        actions.appendChild(confirm);
        modal.appendChild(actions);
        document.body.appendChild(overlay);
    }

    function createDiffPane(title, codeText, createTextElement) {
        const pane = document.createElement('div');
        pane.className = 'vgta-diff-pane';
        pane.appendChild(createTextElement('div', 'vgta-diff-pane-title', title));
        const pre = document.createElement('pre');
        pre.className = 'vgta-diff-code';
        pre.textContent = codeText;
        pane.appendChild(pre);
        return pane;
    }

    function closePatchReviewModal() {
        const existing = document.getElementById('vgta-diff-overlay');
        if (existing) {
            existing.remove();
        }
    }

    return { renderPatchVault, openPatchReviewModal, closePatchReviewModal };
})();
