/**
 * STATUS: PLATIN
 * VGT OMEGA ENGINE - LIVE SIMULATION CONTROLLER
 * Strikte Trennung von State und DOM. Synchronisiert Inputs mit CSS Custom Properties O(1).
 */
document.addEventListener('DOMContentLoaded', () => {
    'use strict';

    class VGTSimulationEngine {
        constructor() {
            this.simEnv = document.getElementById('vgt-sim-environment');
            if (!this.simEnv) return;

            // State Maps: Input ID -> CSS Variable im Simulation Container
            this.bindings = {
                'login_bg_color': '--sim-bg',
                'login_accent': '--sim-accent'
            };

            this.urlBindings = {
                'login_bg_image': '--sim-bg-img',
                'login_logo': '--sim-logo-img'
            };

            this.initEngine();
        }

        initEngine() {
            this.bindColors();
            this.bindUrls();
        }

        bindColors() {
            for (const [inputId, cssVar] of Object.entries(this.bindings)) {
                const input = document.getElementById(inputId);
                if (!input) continue;

                // Live Update der Simulation
                input.addEventListener('input', (e) => {
                    const val = e.target.value;
                    this.simEnv.style.setProperty(cssVar, val);
                    
                    // Hex-Text Update im Control Node
                    const hexDisplay = input.nextElementSibling;
                    if (hexDisplay && hexDisplay.classList.contains('color-hex')) {
                        hexDisplay.textContent = val;
                    }
                });
            }
        }

        bindUrls() {
            for (const [inputId, cssVar] of Object.entries(this.urlBindings)) {
                const input = document.getElementById(inputId);
                if (!input) continue;

                // Debounced Update für URLs zur Vermeidung von Layout-Thrashing
                let timeout;
                input.addEventListener('input', (e) => {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => {
                        const val = e.target.value.trim();
                        // Security/Sanitization auf Client-Seite für die Simulation
                        const safeUrl = val.replace(/['"()]/g, '');
                        
                        if (safeUrl === '') {
                            this.simEnv.style.setProperty(cssVar, 'none');
                        } else {
                            this.simEnv.style.setProperty(cssVar, `url('${safeUrl}')`);
                        }
                    }, 150); // 150ms Debounce
                });
            }
        }
    }

    // Zündung
    new VGTSimulationEngine();
});
