/**
 * VGT Desktop Module - Task Manager & Cron Orchestrator
 */

Object.assign(window.VGTDeskEngine, {
    tmPollInterval: null,

    switchTMTab(tabName) {
        this.playSound('click');
        
        // Tab buttons
        const navItems = document.querySelectorAll('.vgt-tm-nav-item');
        navItems.forEach(item => {
            if (item.dataset.tab === tabName) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });

        // Panels
        const panels = document.querySelectorAll('.vgt-tm-tab-panel');
        panels.forEach(panel => {
            if (panel.id === `vgt-tm-tab-${tabName}`) {
                panel.classList.add('active');
            } else {
                panel.classList.remove('active');
            }
        });
    },

    startTaskManagerPolling() {
        this.stopTaskManagerPolling();
        this.loadTaskManagerStats();
        this.tmPollInterval = setInterval(() => {
            this.loadTaskManagerStats();
        }, 4000);
    },

    stopTaskManagerPolling() {
        if (this.tmPollInterval) {
            clearInterval(this.tmPollInterval);
            this.tmPollInterval = null;
        }
    },

    loadTaskManagerStats() {
        if (typeof vgtConfig === 'undefined' || !vgtConfig.ajaxUrl) return;

        const formData = new FormData();
        formData.append('action', 'vgt_get_task_manager_stats');
        formData.append('nonce', vgtConfig.nonce);

        fetch(vgtConfig.ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                this.renderCrons(data.data.crons);
                this.renderTransients(data.data.transients);
                this.renderWorkers(data.data.workers);
            }
        })
        .catch(err => console.error("Task-Manager Sync-Fehler:", err));
    },

    renderCrons(crons) {
        const tbody = document.getElementById('vgt-tm-crons-table-body');
        if (!tbody) return;

        if (!crons || crons.length === 0) {
            tbody.innerHTML = `<tr><td colspan="4" style="text-align: center; color: #64748b;">Keine Cron-Schedules registriert.</td></tr>`;
            return;
        }

        let html = '';
        crons.forEach(cron => {
            const hookEscaped = this.escapeHTML(cron.hook);
            const timeEscaped = this.escapeHTML(cron.time_formatted);
            const scheduleEscaped = this.escapeHTML(cron.schedule);
            
            html += `
                <tr>
                    <td style="font-weight: 500; color: #ffffff;">${hookEscaped}</td>
                    <td>${timeEscaped}</td>
                    <td><span class="vgt-badge-item" style="background: rgba(99, 102, 241, 0.15); color: #818cf8; border: 1px solid rgba(99, 102, 241, 0.3); font-size:10px;">${scheduleEscaped}</span></td>
                    <td>
                        <button onclick="VGTDeskEngine.unscheduleCron('${hookEscaped}', ${cron.timestamp}, this)" class="vgt-btn-danger" style="font-size: 10px; padding: 4px 8px;">Beenden</button>
                    </td>
                </tr>
            `;
        });
        tbody.innerHTML = html;
    },

    renderTransients(transients) {
        const tbody = document.getElementById('vgt-tm-transients-table-body');
        if (!tbody) return;

        if (!transients || transients.length === 0) {
            tbody.innerHTML = `<tr><td colspan="3" style="text-align: center; color: #64748b;">Keine aktiven Transients vorhanden.</td></tr>`;
            return;
        }

        let html = '';
        transients.forEach(t => {
            const nameEscaped = this.escapeHTML(t.name);
            const optionEscaped = this.escapeHTML(t.option_name);

            html += `
                <tr>
                    <td style="font-weight: 500; color: #ffffff;">${nameEscaped}</td>
                    <td style="font-family: monospace; font-size: 11px; color: #94a3b8;">${optionEscaped}</td>
                    <td>
                        <button onclick="VGTDeskEngine.killTransient('${nameEscaped}', this)" class="vgt-btn-danger" style="font-size: 10px; padding: 4px 8px;">Kill</button>
                    </td>
                </tr>
            `;
        });
        tbody.innerHTML = html;
    },

    renderWorkers(workers) {
        const tbody = document.getElementById('vgt-tm-workers-table-body');
        if (!tbody) return;

        if (!workers || workers.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5" style="text-align: center; color: #64748b;">Keine asynchronen AJAX-Worker aktiv.</td></tr>`;
            return;
        }

        let html = '';
        workers.forEach(w => {
            const pidEscaped = this.escapeHTML(String(w.pid));
            const workerEscaped = this.escapeHTML(w.worker);
            const runtimeEscaped = this.escapeHTML(w.runtime);
            const memoryEscaped = this.escapeHTML(w.memory);
            const statusEscaped = this.escapeHTML(w.status);

            const statusColor = statusEscaped === 'running' ? '#10b981' : '#64748b';

            html += `
                <tr>
                    <td style="font-family: monospace; color: #94a3b8;">${pidEscaped}</td>
                    <td style="font-weight: 500; color: #ffffff;">${workerEscaped}</td>
                    <td>${runtimeEscaped}</td>
                    <td>${memoryEscaped}</td>
                    <td>
                        <span style="display:inline-flex; align-items:center; gap:4px; color:${statusColor}; font-size:11px;">
                            <span class="vgt-widget-pulse-dot" style="width: 6px; height: 6px; border-radius: 50%; background: ${statusColor};"></span>
                            ${statusEscaped}
                        </span>
                    </td>
                </tr>
            `;
        });
        tbody.innerHTML = html;
    },

    unscheduleCron(hook, timestamp, btn) {
        if (typeof vgtConfig === 'undefined' || !vgtConfig.ajaxUrl) return;

        this.playSound('click');
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Beende...';
        }

        const formData = new FormData();
        formData.append('action', 'vgt_unschedule_cron');
        formData.append('nonce', vgtConfig.nonce);
        formData.append('hook', hook);
        formData.append('timestamp', timestamp);

        fetch(vgtConfig.ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                this.playSound('alert');
                this.addLog(data.data);
                this.loadTaskManagerStats();
            } else {
                this.addLog(`Fehler beim Beenden von ${hook}`);
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = 'Beenden';
                }
            }
        })
        .catch(err => {
            console.error(err);
            if (btn) {
                btn.disabled = false;
                btn.textContent = 'Beenden';
            }
        });
    },

    killTransient(name, btn) {
        if (typeof vgtConfig === 'undefined' || !vgtConfig.ajaxUrl) return;

        this.playSound('click');
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Killing...';
        }

        const formData = new FormData();
        formData.append('action', 'vgt_kill_transient');
        formData.append('nonce', vgtConfig.nonce);
        formData.append('name', name);

        fetch(vgtConfig.ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                this.playSound('alert');
                this.addLog(data.data);
                this.loadTaskManagerStats();
            } else {
                this.addLog(`Fehler beim Löschen von Transient ${name}`);
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = 'Kill';
                }
            }
        })
        .catch(err => {
            console.error(err);
            if (btn) {
                btn.disabled = false;
                btn.textContent = 'Kill';
            }
        });
    }
});
