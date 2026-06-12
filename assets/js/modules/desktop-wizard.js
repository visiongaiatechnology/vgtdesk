/**
 * VGT Desktop Module - First-Run Wizard (Interaktives Onboarding)
 * Handles layout selection, accent selection, superkey config, sentinel settings, redirect toggling, and completion.
 */

Object.assign(window.VGTDeskEngine, {
    vgtWizardState: {
        currentStep: 1,
        maxSteps: 7,
        layout: 'macos',
        color: 'indigo',
        superkey: '',
        currentSuperkey: '',
        sentinel: true,
        dattrack: false,
        redirect: false
    },

    initFirstRunWizard() {
        // Run check on initialization
        if (this.userSettings && this.userSettings.first_run_completed === false) {
            // Wait slightly for shell UI to settle, then start
            setTimeout(() => {
                this.startFirstRunWizard();
            }, 800);
        }
    },

    startFirstRunWizard() {
        const wizardOverlay = document.getElementById('vgt-first-run-wizard');
        if (!wizardOverlay) return;

        const maxSteps = (typeof vgtConfig !== 'undefined' && vgtConfig.isSentinelV7) ? 6 : 7;
        this.vgtWizardState = {
            currentStep: 1,
            maxSteps: maxSteps,
            layout: this.userSettings.layout_style || 'macos',
            color: this.userSettings.accent_color || 'indigo',
            superkey: '',
            currentSuperkey: '',
            sentinel: (typeof vgtConfig !== 'undefined' && vgtConfig.sentinelEnabled),
            dattrack: (typeof vgtConfig !== 'undefined' && vgtConfig.dattrackEnabled),
            redirect: this.userSettings.auto_redirect || false
        };

        // Initialize UI values to match current state
        this.selectWizardLayout(this.vgtWizardState.layout);
        this.selectWizardColor(this.vgtWizardState.color);
        
        // Show/hide current superkey row based on whether it is active on the server
        const currentKeyRow = document.getElementById('vgt-wizard-current-key-row');
        if (currentKeyRow) {
            if (typeof vgtConfig !== 'undefined' && vgtConfig.superkeyActive) {
                currentKeyRow.classList.remove('hidden');
            } else {
                currentKeyRow.classList.add('hidden');
            }
        }

        // Initialize checkbox Toggles
        const sentinelToggle = document.getElementById('vgt-wizard-sentinel-toggle');
        if (sentinelToggle) sentinelToggle.checked = this.vgtWizardState.sentinel;

        const dattrackToggle = document.getElementById('vgt-wizard-dattrack-toggle');
        if (dattrackToggle) dattrackToggle.checked = this.vgtWizardState.dattrack;

        const redirectToggle = document.getElementById('vgt-wizard-redirect-toggle');
        if (redirectToggle) redirectToggle.checked = this.vgtWizardState.redirect;

        // Reset step content visibility
        this.showWizardStep(1);

        // Clear password inputs
        const currentPassInput = document.getElementById('vgt-wizard-superkey-current');
        if (currentPassInput) currentPassInput.value = '';
        const passInput = document.getElementById('vgt-wizard-superkey-input');
        if (passInput) {
            passInput.value = '';
            passInput.type = 'password';
        }
        this.updateWizardSuperkeyStrength('');

        // Display Wizard Overlay
        wizardOverlay.classList.remove('hidden');
        this.playSound('click');
        this.addLog("Setup-Assistent gestartet.");
    },

    closeFirstRunWizard() {
        const wizardOverlay = document.getElementById('vgt-first-run-wizard');
        if (wizardOverlay) {
            wizardOverlay.classList.add('hidden');
        }
    },

    showWizardStep(step) {
        this.vgtWizardState.currentStep = step;

        // Toggle step contents active class
        for (let i = 1; i <= this.vgtWizardState.maxSteps; i++) {
            const el = document.getElementById(`vgt-wizard-step-${i}`);
            const ind = document.querySelector(`.vgt-wizard-step-indicator[data-step="${i}"]`);
            if (el) {
                el.classList.toggle('active', i === step);
            }
            if (ind) {
                ind.classList.toggle('active', i === step);
                ind.classList.toggle('completed', i < step);
            }
        }

        // Back button visibility
        const prevBtn = document.getElementById('vgt-wizard-prev');
        if (prevBtn) {
            prevBtn.classList.toggle('hidden', step === 1);
        }

        // Skip Link visibility (only visible during step 3: Superkey)
        const skipLink = document.getElementById('vgt-wizard-skip');
        if (skipLink) {
            skipLink.classList.toggle('hidden', step !== 3);
        }

        // Next button title / states
        const nextBtn = document.getElementById('vgt-wizard-next');
        if (nextBtn) {
            if (step === this.vgtWizardState.maxSteps) {
                nextBtn.textContent = 'Desktop starten 🚀';
                nextBtn.className = 'vgt-btn-primary';
            } else {
                nextBtn.textContent = 'Weiter';
                nextBtn.className = 'vgt-btn-primary';
            }
        }
    },

    nextWizardStep() {
        const step = this.vgtWizardState.currentStep;
        
        // 1. Validation before advancing
        if (step === 3) {
            // Validate superkey input if they typed something
            const passInput = document.getElementById('vgt-wizard-superkey-input');
            const keyVal = passInput ? passInput.value : '';
            if (keyVal.length > 0 && keyVal.length < 12) {
                alert('Der neue Superkey muss mindestens 12 Zeichen lang sein.');
                return;
            }
            // Store keys
            this.vgtWizardState.superkey = keyVal;
            const currentPassInput = document.getElementById('vgt-wizard-superkey-current');
            this.vgtWizardState.currentSuperkey = currentPassInput ? currentPassInput.value : '';
        } else if (step === 4) {
            // Read Sentinel toggle
            const sentinelToggle = document.getElementById('vgt-wizard-sentinel-toggle');
            this.vgtWizardState.sentinel = sentinelToggle ? sentinelToggle.checked : true;
        } else if (step === 5) {
            if (this.vgtWizardState.maxSteps === 6) {
                // Sentinel V7 active: step 5 is Auto-Redirect
                const redirectToggle = document.getElementById('vgt-wizard-redirect-toggle');
                this.vgtWizardState.redirect = redirectToggle ? redirectToggle.checked : false;
                this.populateWizardSummary();
            } else {
                // Standard mode: step 5 is Dattrack
                const dattrackToggle = document.getElementById('vgt-wizard-dattrack-toggle');
                this.vgtWizardState.dattrack = dattrackToggle ? dattrackToggle.checked : false;
            }
        } else if (step === 6) {
            if (this.vgtWizardState.maxSteps === 7) {
                // Standard mode: step 6 is Auto-Redirect
                const redirectToggle = document.getElementById('vgt-wizard-redirect-toggle');
                this.vgtWizardState.redirect = redirectToggle ? redirectToggle.checked : false;
                this.populateWizardSummary();
            }
        }

        this.playSound('click');

        if (step < this.vgtWizardState.maxSteps) {
            this.showWizardStep(step + 1);
        } else {
            // If on step 6, complete setup
            this.completeWizard();
        }
    },

    prevWizardStep() {
        const step = this.vgtWizardState.currentStep;
        this.playSound('click');
        if (step > 1) {
            this.showWizardStep(step - 1);
        }
    },

    selectWizardLayout(layout) {
        this.vgtWizardState.layout = layout;
        document.querySelectorAll('.vgt-wizard-layout-card').forEach(card => {
            card.classList.toggle('active', card.dataset.layout === layout);
        });
    },

    selectWizardColor(color) {
        this.vgtWizardState.color = color;
        document.querySelectorAll('.vgt-wizard-color-item').forEach(item => {
            item.classList.toggle('active', item.dataset.color === color);
        });
    },

    toggleWizardPassword() {
        const passInput = document.getElementById('vgt-wizard-superkey-input');
        if (passInput) {
            passInput.type = passInput.type === 'password' ? 'text' : 'password';
        }
    },

    updateWizardSuperkeyStrength(val) {
        const bar = document.getElementById('vgt-wizard-strength-bar');
        const label = document.getElementById('vgt-wizard-strength-label');
        if (!bar || !label) return;

        if (val.length === 0) {
            bar.style.width = '0%';
            bar.style.background = '#475569';
            label.textContent = 'Kein Key eingegeben';
            return;
        }

        let strength = 0;
        if (val.length >= 12) strength += 30;
        if (val.length >= 16) strength += 20;
        if (/[A-Z]/.test(val)) strength += 15;
        if (/[0-9]/.test(val)) strength += 15;
        if (/[^A-Za-z0-9]/.test(val)) strength += 20;

        bar.style.width = `${strength}%`;

        if (val.length < 12) {
            bar.style.background = '#f43f5e';
            label.textContent = `Zu kurz (min. 12 Zeichen) - Stärke: ${strength}%`;
        } else if (strength < 50) {
            bar.style.background = '#fb923c';
            label.textContent = `Schwach - Stärke: ${strength}%`;
        } else if (strength < 80) {
            bar.style.background = '#fbbf24';
            label.textContent = `Mittel - Stärke: ${strength}%`;
        } else {
            bar.style.background = '#10b981';
            label.textContent = `Stark - Stärke: ${strength}%`;
        }
    },

    skipWizardSuperkey() {
        const currentPassInput = document.getElementById('vgt-wizard-superkey-current');
        if (currentPassInput) currentPassInput.value = '';
        const passInput = document.getElementById('vgt-wizard-superkey-input');
        if (passInput) passInput.value = '';
        this.vgtWizardState.superkey = '';
        this.vgtWizardState.currentSuperkey = '';
        this.nextWizardStep();
    },

    populateWizardSummary() {
        const layoutLabel = document.getElementById('vgt-summary-layout');
        if (layoutLabel) {
            const map = { macos: 'macOS Style 🍎', windows: 'Windows Style 🪟', linux: 'Linux Style 🐧' };
            layoutLabel.textContent = map[this.vgtWizardState.layout] || this.vgtWizardState.layout;
        }

        const colorLabel = document.getElementById('vgt-summary-color');
        if (colorLabel) {
            colorLabel.textContent = this.vgtWizardState.color.toUpperCase();
        }

        const keyLabel = document.getElementById('vgt-summary-superkey');
        if (keyLabel) {
            keyLabel.textContent = this.vgtWizardState.superkey ? 'Neu konfiguriert 🔒' : 'Unverändert';
        }

        const sentinelLabel = document.getElementById('vgt-summary-sentinel');
        if (sentinelLabel) {
            sentinelLabel.textContent = this.vgtWizardState.sentinel ? 'Aktiviert 🛡️' : 'Deaktiviert';
        }

        const dattrackLabel = document.getElementById('vgt-summary-dattrack');
        if (dattrackLabel) {
            dattrackLabel.textContent = this.vgtWizardState.dattrack ? 'Aktiviert 📊' : 'Deaktiviert';
        }

        const redirectLabel = document.getElementById('vgt-summary-redirect');
        if (redirectLabel) {
            redirectLabel.textContent = this.vgtWizardState.redirect ? 'Aktiviert' : 'Inaktiv';
        }
    },

    async completeWizard() {
        // Enforce maximum loading indications and prevent duplicate clicks
        const nextBtn = document.getElementById('vgt-wizard-next');
        if (nextBtn) {
            nextBtn.disabled = true;
            nextBtn.textContent = 'Speichere Konfiguration...';
        }

        try {
            // 1. Save Accent Color
            this.userSettings.accent_color = this.vgtWizardState.color;
            this.changeAccentColor(this.vgtWizardState.color, true);

            // 2. Save Layout Style
            if (typeof this.changeLayoutStyle === 'function') {
                this.changeLayoutStyle(this.vgtWizardState.layout);
            }

            // 3. Save Auto Redirect
            this.userSettings.auto_redirect = this.vgtWizardState.redirect;
            this.saveUserSetting('auto_redirect', this.vgtWizardState.redirect);
            const redirectCheckbox = document.getElementById('redirect-toggle');
            if (redirectCheckbox) redirectCheckbox.checked = this.vgtWizardState.redirect;

            // 4. Save Sentinel Toggle if state changed on client side
            const initialSentinel = (typeof vgtConfig !== 'undefined' && vgtConfig.sentinelEnabled);
            if (this.vgtWizardState.sentinel !== initialSentinel) {
                await this.ajaxToggleSentinelAsync();
            }

            // Save Dattrack Toggle if state changed on client side (only in community mode)
            const initialDattrack = (typeof vgtConfig !== 'undefined' && vgtConfig.dattrackEnabled);
            if (this.vgtWizardState.maxSteps === 7 && this.vgtWizardState.dattrack !== initialDattrack) {
                await this.ajaxToggleDattrackAsync(this.vgtWizardState.dattrack);
            }

            // 5. Update Superkey if set
            if (this.vgtWizardState.superkey) {
                await this.ajaxUpdateSuperkeyAsync(this.vgtWizardState.currentSuperkey, this.vgtWizardState.superkey);
            }

            // 6. Complete wizard flag
            this.userSettings.first_run_completed = true;
            this.saveUserSetting('first_run_completed', true);

            // Hide overlay
            this.closeFirstRunWizard();

            // Success toast
            this.showPresetToast("VisionGaia Desktop");
            this.addLog("Erstkonfiguration erfolgreich abgeschlossen.");
            this.playSound('click');

            // Open welcome window as a nice start
            this.openWindow('welcome');

        } catch (err) {
            console.error("Setup Wizard Fehler:", err);
            alert(`Setup konnte nicht vollständig abgeschlossen werden: ${err.message || err}`);
        } finally {
            if (nextBtn) {
                nextBtn.disabled = false;
                nextBtn.textContent = 'Desktop starten 🚀';
            }
        }
    },

    ajaxToggleSentinelAsync() {
        return new Promise((resolve, reject) => {
            if (typeof vgtConfig === 'undefined' || !vgtConfig.ajaxUrl) return resolve();

            const formData = new FormData();
            formData.append('action', 'vgt_toggle_sentinel');
            formData.append('nonce', vgtConfig.nonce);

            fetch(vgtConfig.ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (typeof vgtConfig !== 'undefined') {
                        vgtConfig.sentinelEnabled = !vgtConfig.sentinelEnabled;
                    }
                    // Update Sentinel Widget UI if functions exist
                    if (typeof this.updateSentinelWidgetUI === 'function') {
                        this.updateSentinelWidgetUI();
                    }
                    resolve();
                } else {
                    reject(new Error(data.data));
                }
            })
            .catch(err => reject(err));
        });
    },

    ajaxUpdateSuperkeyAsync(currentVal, newVal) {
        return new Promise((resolve, reject) => {
            if (typeof vgtConfig === 'undefined' || !vgtConfig.ajaxUrl) return resolve();

            const formData = new FormData();
            formData.append('action', 'vgt_update_superkey');
            formData.append('nonce', vgtConfig.nonce);
            formData.append('current_superkey', currentVal);
            formData.append('new_superkey', newVal);

            fetch(vgtConfig.ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (typeof vgtConfig !== 'undefined') {
                        vgtConfig.superkeyActive = true;
                    }
                    resolve();
                } else {
                    reject(new Error(data.data));
                }
            })
            .catch(err => reject(err));
        });
    },

    ajaxToggleDattrackAsync(state) {
        return new Promise((resolve, reject) => {
            if (typeof vgtConfig === 'undefined' || !vgtConfig.ajaxUrl) return resolve();

            const formData = new FormData();
            formData.append('action', 'vgt_toggle_dattrack');
            formData.append('nonce', vgtConfig.nonce);

            fetch(vgtConfig.ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (typeof vgtConfig !== 'undefined') {
                        vgtConfig.dattrackEnabled = data.data.enabled;
                    }
                    const settingsDattrackToggle = document.getElementById('vgt-cc-dattrack-toggle');
                    if (settingsDattrackToggle) {
                        settingsDattrackToggle.checked = data.data.enabled;
                    }
                    resolve();
                } else {
                    reject(new Error(data.data));
                }
            })
            .catch(err => reject(err));
        });
    }
});
