/**
 * STATUS: DIAMANT VGT SUPREME
 * VGTAstra patch review and side-by-side diff UI.
 */

window.VGTAstraPatchReview = (() => {
    'use strict';

    function renderPatchVault(config, nodes, createTextElement, preparePatchReview) {
        nodes.patchList.replaceChildren();
        nodes.btnClearPatches.disabled = config.proposals.length === 0;
        if (nodes.btnReviewBundle) {
            nodes.btnReviewBundle.disabled = config.proposals.filter((proposal) => !proposal.committed).length < 2;
        }
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
            const proposalId = proposal.id || proposal.proposal_id || '';
            button.addEventListener('click', () => preparePatchReview(proposalId));
            row.appendChild(button);
            nodes.patchList.appendChild(row);
        });
    }

    function openPatchReviewModal(review, createTextElement, commitProposal) {
        closePatchReviewModal();
        const files = Array.isArray(review.files) && review.files.length > 0 ? review.files : [review];
        let activeIndex = 0;
        const selected = new Set(files.map((file) => file.proposal_id));

        const overlay = document.createElement('div');
        overlay.className = 'vgta-diff-overlay';
        overlay.id = 'vgta-diff-overlay';
        const modal = document.createElement('div');
        modal.className = 'vgta-diff-modal vgta-diff-modal-tabs';
        overlay.appendChild(modal);

        const header = document.createElement('div');
        header.className = 'vgta-diff-header';
        header.appendChild(createTextElement('div', 'vgta-diff-title', files.length > 1 ? 'Multi-File Patch Review' : 'Patch Review'));
        const headerPath = createTextElement('div', 'vgta-diff-path', files[0].path || 'unknown');
        header.appendChild(headerPath);
        modal.appendChild(header);

        const tabBar = document.createElement('div');
        tabBar.className = 'vgta-diff-tabbar';
        modal.appendChild(tabBar);

        const grid = document.createElement('div');
        grid.className = 'vgta-diff-grid';
        modal.appendChild(grid);

        const table = document.createElement('div');
        table.className = 'vgta-diff-table';
        modal.appendChild(table);

        function renderActiveFile() {
            const file = files[activeIndex];
            headerPath.textContent = file.path || 'unknown';
            grid.replaceChildren(
                createDiffPane('CURRENT', file.current_code || '', createTextElement),
                createDiffPane('PROPOSED', file.proposed_code || '', createTextElement),
            );
            table.replaceChildren();
            (Array.isArray(file.diff) ? file.diff : []).forEach((row) => {
                const line = document.createElement('div');
                line.className = `vgta-diff-row ${row.type || 'same'}`;
                line.appendChild(createTextElement('span', 'line-num', String(row.left || '')));
                line.appendChild(createTextElement('span', 'line-code', row.left_text || ''));
                line.appendChild(createTextElement('span', 'line-num', String(row.right || '')));
                line.appendChild(createTextElement('span', 'line-code', row.right_text || ''));
                table.appendChild(line);
            });
            renderTabs();
        }

        function renderTabs() {
            tabBar.replaceChildren();
            files.forEach((file, index) => {
                const tab = document.createElement('button');
                tab.type = 'button';
                tab.className = index === activeIndex ? 'vgta-diff-tab active' : 'vgta-diff-tab';
                tab.textContent = file.path || `file-${index + 1}`;
                tab.addEventListener('click', () => {
                    activeIndex = index;
                    renderActiveFile();
                });

                const toggle = document.createElement('input');
                toggle.type = 'checkbox';
                toggle.checked = selected.has(file.proposal_id);
                toggle.addEventListener('click', (event) => {
                    event.stopPropagation();
                    if (toggle.checked) {
                        selected.add(file.proposal_id);
                    } else {
                        selected.delete(file.proposal_id);
                    }
                });
                tab.prepend(toggle);
                tabBar.appendChild(tab);
            });
        }

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
        confirm.textContent = files.length > 1 ? 'COMMIT SELECTED' : 'CONFIRM COMMIT';
        confirm.addEventListener('click', () => {
            const selectedFiles = files.filter((file) => selected.has(file.proposal_id));
            if (selectedFiles.length === 0) {
                return;
            }
            commitProposal(selectedFiles);
        });
        actions.appendChild(confirm);
        modal.appendChild(actions);
        renderActiveFile();
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
