/**
 * VGT OMEGA PROTOCOL: FRONTEND ENGINE
 * Status: Diamant Supreme
 * Note: Pure Vanilla JS. Isolierte Ausführung.
 */

document.addEventListener('DOMContentLoaded', () => {
    
    class VGTFrontendEngine {
        constructor() {
            this.triggers = document.querySelectorAll('.vgt-trigger-btn');
            this.overlay = document.getElementById('vgt-reader-overlay');
            
            if (!this.overlay || this.triggers.length === 0) return;

            // DOM Elements Cache
            this.closeBtn = document.getElementById('vgt-reader-close');
            this.iframe = document.getElementById('vgt-reader-iframe');
            this.titleEl = document.getElementById('vgt-reader-title');
            this.buyBtn = document.getElementById('vgt-reader-buy-btn');
            this.buyTextEl = document.getElementById('vgt-reader-buy-text');
            this.downloadBtn = document.getElementById('vgt-reader-download-btn');
            this.loader = document.getElementById('vgt-reader-loader');
            
            this.bindEvents();
        }

        bindEvents() {
            // Open Reader
            this.triggers.forEach(btn => {
                btn.addEventListener('click', (e) => this.openReader(e.currentTarget));
            });

            // Close Reader
            this.closeBtn.addEventListener('click', () => this.closeReader());
            
            // Close on ESC key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.overlay.classList.contains('vgt-reader-active')) {
                    this.closeReader();
                }
            });

            // Handle Iframe Load State
            this.iframe.addEventListener('load', () => {
                if (this.iframe.src && this.iframe.src !== window.location.href) {
                    this.loader.style.opacity = '0';
                    setTimeout(() => { this.loader.style.display = 'none'; }, 400);
                }
            });
        }

        openReader(btn) {
            const ds = btn.dataset;
            
            // 1. Populate Data
            this.titleEl.textContent = ds.title;
            
            // Buy Button Logic
            if(ds.buyEnabled === 'true' && ds.buyLink) {
                this.buyBtn.style.display = 'flex';
                this.buyBtn.href = ds.buyLink;
                this.buyTextEl.textContent = ds.buyText;
            } else {
                this.buyBtn.style.display = 'none';
            }

            // Download Button Logic
            if(ds.downloadEnabled === 'true' && ds.pdf) {
                this.downloadBtn.style.display = 'flex';
                this.downloadBtn.href = ds.pdf;
            } else {
                this.downloadBtn.style.display = 'none';
            }

            // 2. Lock Body Scroll (Prevent underlying page from moving)
            document.body.style.overflow = 'hidden';
            
            // 3. Reset Loader & Set Iframe
            this.loader.style.display = 'flex';
            this.loader.style.opacity = '1';
            
            // Start loading PDF
            // Adding #toolbar=0&navpanes=0 forces clean view in most browsers
            this.iframe.src = ds.pdf ? ds.pdf + '#toolbar=0&navpanes=0' : '';

            // 4. Trigger Animations
            // Small timeout ensures display block registers before opacity transition
            requestAnimationFrame(() => {
                this.overlay.classList.add('vgt-reader-active');
            });
        }

        closeReader() {
            // 1. Fade out
            this.overlay.classList.remove('vgt-reader-active');
            
            // 2. Cleanup after animation (400ms matching CSS)
            setTimeout(() => {
                document.body.style.overflow = ''; // Restore scroll
                this.iframe.src = ''; // Clear memory
                this.loader.style.display = 'flex'; // Reset loader for next time
                this.loader.style.opacity = '1';
            }, 400);
        }
    }

    // Ignite the Engine
    new VGTFrontendEngine();
});