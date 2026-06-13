/**
 * VGT OMEGA PROTOCOL: DASHBOARD ENGINE
 * Status: Diamant Supreme (Zero-Trust DOM Injection)
 * Note: Pure Vanilla JS AJAX Engine. Strict Content-Security.
 */

document.addEventListener('DOMContentLoaded', () => {
    
    class VGTDashboardEngine {
        constructor() {
            this.navItems = document.querySelectorAll('.vgt-nav-item');
            this.views = document.querySelectorAll('.vgt-view');
            this.gridContainer = document.getElementById('vgt-books-container');
            this.form = document.getElementById('vgt-book-form');
            this.buyCheckbox = document.getElementById('vgt-buy-enabled');
            this.buyOptions = document.getElementById('vgt-buy-options');
            
            this.init();
        }

        init() {
            console.log('VGT Engine: Diamant Core Initialized.');
            this.bindEvents();
            this.loadBooks();
        }

        bindEvents() {
            this.navItems.forEach(item => {
                item.addEventListener('click', (e) => {
                    const targetViewId = e.currentTarget.getAttribute('data-target');
                    this.switchView(targetViewId, e.currentTarget);
                    if(targetViewId === 'view-create') {
                        this.resetForm();
                    }
                });
            });

            this.buyCheckbox.addEventListener('change', (e) => {
                this.buyOptions.style.display = e.target.checked ? 'block' : 'none';
            });

            this.form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.saveBook();
            });
        }

        switchView(viewId, activeBtn) {
            this.navItems.forEach(btn => btn.classList.remove('active'));
            this.views.forEach(view => view.classList.remove('active'));

            if(activeBtn) activeBtn.classList.add('active');
            document.getElementById(viewId).classList.add('active');
        }

        resetForm() {
            this.form.reset();
            document.getElementById('vgt-book-id').value = '0';
            document.getElementById('vgt-form-title').textContent = 'Neues Buch anlegen';
            this.buyOptions.style.display = 'none';
        }

        async loadBooks() {
            try {
                const formData = new FormData();
                formData.append('action', 'vgt_get_books');
                formData.append('nonce', vgtConfig.nonce);

                const response = await fetch(vgtConfig.ajaxUrl, {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if(data.success) {
                    this.renderBooks(data.data);
                }
            } catch (error) {
                console.error('VGT Matrix Error:', error);
            }
        }

        // RED TEAM FIX: Zero-Trust DOM Rendering (No innerHTML for variables)
        renderBooks(books) {
            this.gridContainer.innerHTML = ''; // Clear container

            if(books.length === 0) {
                const emptyMsg = document.createElement('div');
                emptyMsg.style.cssText = 'grid-column: 1/-1; text-align: center; color: #a1a1aa; padding: 40px;';
                emptyMsg.textContent = 'Datenbank leer. Füttere die Matrix.';
                this.gridContainer.appendChild(emptyMsg);
                return;
            }

            const fragment = document.createDocumentFragment();

            books.forEach(book => {
                const card = document.createElement('div');
                card.className = 'vgt-book-card';

                // Cover Placeholder
                const cover = document.createElement('div');
                cover.className = 'vgt-book-cover-placeholder';
                cover.textContent = 'PDF';

                // Meta Info
                const meta = document.createElement('div');
                meta.className = 'vgt-book-meta';

                const title = document.createElement('h3');
                title.textContent = book.title; // XSS Proof Text Injection

                const shortcode = document.createElement('div');
                shortcode.className = 'vgt-shortcode-snippet';
                shortcode.textContent = `[vgt_reader id="${book.id}"]`;
                shortcode.onclick = function() { vgtCopyCode(this); };

                meta.appendChild(title);
                meta.appendChild(shortcode);

                // Actions
                const actions = document.createElement('div');
                actions.className = 'vgt-book-actions';
                actions.style.cssText = 'justify-content: space-between; align-items: center;';

                const delBtn = document.createElement('button');
                delBtn.className = 'vgt-btn outline text-red';
                delBtn.style.cssText = 'border-color: #ef4444; color: #ef4444; font-size: 11px;';
                delBtn.textContent = 'Destroy';
                delBtn.onclick = () => window.vgtApp.deleteBook(book.id);

                const copyBtn = document.createElement('button');
                copyBtn.className = 'vgt-btn outline';
                copyBtn.textContent = 'Kopieren';
                copyBtn.onclick = () => window.vgtCopyCode(shortcode);

                actions.appendChild(delBtn);
                actions.appendChild(copyBtn);

                // Assemble
                card.appendChild(cover);
                card.appendChild(meta);
                card.appendChild(actions);

                fragment.appendChild(card);
            });

            this.gridContainer.appendChild(fragment);
        }

        async saveBook() {
            const submitBtn = this.form.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Synchronisiere...';
            submitBtn.disabled = true;

            try {
                const formData = new FormData();
                formData.append('action', 'vgt_save_book');
                formData.append('nonce', vgtConfig.nonce);
                
                formData.append('book_id', document.getElementById('vgt-book-id').value);
                formData.append('title', document.getElementById('vgt-title').value);
                formData.append('pdf_url', document.getElementById('vgt-pdf-url').value);
                formData.append('btn_text', document.getElementById('vgt-btn-text').value);
                formData.append('btn_color', document.getElementById('vgt-btn-color').value);
                formData.append('btn_style', document.getElementById('vgt-btn-style').value);
                formData.append('buy_enabled', document.getElementById('vgt-buy-enabled').checked);
                formData.append('buy_link', document.getElementById('vgt-buy-link').value);
                formData.append('buy_text', document.getElementById('vgt-buy-text').value);
                formData.append('download_enabled', document.getElementById('vgt-download-enabled').checked);

                const response = await fetch(vgtConfig.ajaxUrl, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                
                if(data.success) {
                    this.resetForm();
                    this.loadBooks();
                    
                    const listBtn = document.querySelector('.vgt-nav-item[data-target="view-list"]');
                    this.switchView('view-list', listBtn);
                } else {
                    // Safe error rendering
                    const errStr = typeof data.data === 'string' ? data.data : 'Unknown Matrix Error';
                    alert('System Error: ' + errStr);
                }

            } catch (error) {
                console.error('VGT Form Error:', error);
            } finally {
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            }
        }

        async deleteBook(id) {
            if(!confirm('Achtung: Dies löscht den Datensatz permanent aus der VGT Matrix. Fortfahren?')) return;

            try {
                const formData = new FormData();
                formData.append('action', 'vgt_delete_book');
                formData.append('nonce', vgtConfig.nonce);
                formData.append('book_id', id);

                const response = await fetch(vgtConfig.ajaxUrl, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                if(data.success) {
                    this.loadBooks();
                }
            } catch (error) {
                console.error('VGT Deletion Error:', error);
            }
        }
    }

    window.vgtApp = new VGTDashboardEngine();

    window.vgtCopyCode = function(element) {
        const text = element.textContent;
        navigator.clipboard.writeText(text).then(() => {
            const originalText = element.textContent;
            element.textContent = 'Kopiert!';
            element.style.color = '#10b981';
            element.style.borderColor = '#10b981';
            
            setTimeout(() => {
                element.textContent = originalText;
                element.style.color = '';
                element.style.borderColor = '';
            }, 1500);
        });
    };
});