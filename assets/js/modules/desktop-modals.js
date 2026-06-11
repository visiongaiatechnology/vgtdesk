/**
 * VGT Desktop Module - Modal Dialog Engine
 * Handles: showModal
 */

Object.assign(window.VGTDeskEngine, {
    showModal(options) {
        const existing = document.getElementById('vgt-custom-modal');
        if (existing) existing.remove();

        const modal = document.createElement('div');
        modal.id = 'vgt-custom-modal';
        modal.className = 'vgt-modal-overlay';
        
        const box = document.createElement('div');
        box.className = 'vgt-modal-box glassmorphism';
        
        const titleEl = document.createElement('h3');
        titleEl.className = 'vgt-modal-title';
        titleEl.textContent = options.title || 'System';
        box.appendChild(titleEl);
        
        if (options.message) {
            const msgEl = document.createElement('p');
            msgEl.className = 'vgt-modal-message';
            msgEl.textContent = options.message;
            box.appendChild(msgEl);
        }
        
        let inputEl = null;
        if (options.inputType === 'text') {
            inputEl = document.createElement('input');
            inputEl.type = 'text';
            inputEl.className = 'vgt-input-text vgt-modal-input';
            inputEl.value = options.inputValue || '';
            if (options.placeholder) inputEl.placeholder = options.placeholder;
            box.appendChild(inputEl);
            
            setTimeout(() => inputEl.focus(), 100);
        }
        
        const actions = document.createElement('div');
        actions.className = 'vgt-modal-actions';
        
        const cancelBtn = document.createElement('button');
        cancelBtn.className = 'vgt-btn-secondary vgt-modal-btn';
        cancelBtn.textContent = options.cancelText || 'Abbrechen';
        cancelBtn.addEventListener('click', () => {
            this.playSound('click');
            modal.remove();
            if (options.onCancel) options.onCancel();
        });
        
        const confirmBtn = document.createElement('button');
        confirmBtn.className = `${options.confirmClass || 'vgt-btn-primary'} vgt-modal-btn`;
        confirmBtn.textContent = options.confirmText || 'Bestätigen';
        
        const handleConfirm = () => {
            this.playSound('click');
            const value = inputEl ? inputEl.value.trim() : null;
            modal.remove();
            if (options.onConfirm) options.onConfirm(value);
        };
        
        confirmBtn.addEventListener('click', handleConfirm);
        
        if (inputEl) {
            inputEl.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    handleConfirm();
                } else if (e.key === 'Escape') {
                    modal.remove();
                    if (options.onCancel) options.onCancel();
                }
            });
        }
        
        actions.appendChild(cancelBtn);
        actions.appendChild(confirmBtn);
        box.appendChild(actions);
        modal.appendChild(box);
        
        (document.getElementById('vgt-shell-root') || document.body).appendChild(modal);
    }
});
