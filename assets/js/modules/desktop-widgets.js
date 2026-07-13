/**
 * VGT Desktop Module - Widgets & Latency Graph
 * Handles: initWidgets, initSentinelWidget, initWidgetDraggables, startLatencyGraph, stopLatencyGraph
 */

Object.assign(window.VGTDeskEngine, {
    initWidgets() {
        const textarea = document.getElementById('vgt-widget-notes-text');
        if (textarea) {
            textarea.value = localStorage.getItem('vgt_widget_notes') || '';
            textarea.addEventListener('input', () => {
                localStorage.setItem('vgt_widget_notes', textarea.value);
            });
        }
        // Paint security status immediately from localized config (no wait for diagnostics poll).
        this.refreshSystemSecurityWidget();
    },

    /**
     * System & Sicherheit widget: Throne Guard + Sentinel from vgtConfig / last diagnostics.
     */
    refreshSystemSecurityWidget(diag) {
        const wTgStatus = document.getElementById('vgt-widget-tg-status');
        const wSentinel = document.getElementById('vgt-widget-sentinel-status');
        const wBans = document.getElementById('vgt-widget-bans-status');

        const cfg = typeof vgtConfig !== 'undefined' ? vgtConfig : {};
        let tgActive = !!(cfg.superkeyActive);
        if (diag && diag.throne_guard && typeof diag.throne_guard.active !== 'undefined') {
            tgActive = !!diag.throne_guard.active;
            cfg.superkeyActive = tgActive;
        }
        if (wTgStatus) {
            wTgStatus.textContent = tgActive ? 'Aktiv' : 'Inaktiv';
            wTgStatus.style.color = tgActive ? '#10b981' : '#f43f5e';
        }

        let sentinelOn = !!cfg.sentinelEnabled;
        if (diag && diag.sentinel && typeof diag.sentinel.active !== 'undefined') {
            sentinelOn = !!diag.sentinel.active;
            cfg.sentinelEnabled = sentinelOn;
        }
        if (wSentinel) {
            wSentinel.textContent = sentinelOn ? 'Aktiv' : 'Inaktiv';
            wSentinel.style.color = sentinelOn ? '#10b981' : '#f43f5e';
        }

        let bans = typeof cfg.sentinelBans === 'number' ? cfg.sentinelBans : 0;
        if (diag && typeof diag.total_bans !== 'undefined') {
            bans = parseInt(diag.total_bans, 10) || 0;
            cfg.sentinelBans = bans;
        }
        if (wBans) {
            wBans.textContent = bans + ' IPs';
        }
    },

    isSystemWidgetActive() {
        const w = document.getElementById('widget-system');
        return w && this.userSettings.widgets_visible === true && w.style.display !== 'none';
    },

    initSentinelWidget() {
        const statusDot = document.getElementById('vgt-sentinel-status-dot');
        const statusText = document.getElementById('vgt-sentinel-status-text');
        const bansCount = document.getElementById('vgt-sentinel-bans-count');
        const toggleBtn = document.getElementById('vgt-sentinel-toggle-btn');
        const widgetSentinel = document.getElementById('widget-sentinel');

        if (!widgetSentinel) return;

        const enabled = typeof vgtConfig !== 'undefined' && vgtConfig.sentinelEnabled;
        const bans = typeof vgtConfig !== 'undefined' ? vgtConfig.sentinelBans : 0;
        const isV7 = typeof vgtConfig !== 'undefined' && vgtConfig.isSentinelV7;

        if (bansCount) {
            bansCount.textContent = bans;
        }

        const updateUI = (isActive) => {
            if (statusText) {
                statusText.textContent = isActive ? 'Aktiv' : 'Inaktiv';
                statusText.style.color = isActive ? '#10b981' : '#f43f5e';
            }
            if (statusDot) {
                statusDot.style.background = isActive ? '#10b981' : '#ef4444';
                statusDot.style.boxShadow = isActive ? '0 0 10px #10b981' : '0 0 10px #ef4444';
            }
            if (toggleBtn) {
                if (isV7) {
                    toggleBtn.textContent = 'Sentinel V7 aktiv';
                    toggleBtn.style.background = 'linear-gradient(135deg, #10b981, #059669)';
                    toggleBtn.style.boxShadow = '0 4px 12px rgba(16, 185, 129, 0.2)';
                    toggleBtn.disabled = true;
                    toggleBtn.style.cursor = 'default';
                } else {
                    toggleBtn.textContent = isActive ? 'Sentinel deaktivieren' : 'Sentinel aktivieren';
                    toggleBtn.style.background = isActive 
                        ? 'linear-gradient(135deg, #10b981, #059669)' 
                        : 'linear-gradient(135deg, #f43f5e, #e11d48)';
                    toggleBtn.style.boxShadow = isActive 
                        ? '0 4px 12px rgba(16, 185, 129, 0.2)' 
                        : '0 4px 12px rgba(244, 63, 94, 0.2)';
                }
            }
        };

        updateUI(enabled);

        if (toggleBtn && !isV7) {
            toggleBtn.addEventListener('click', () => {
                this.playSound('click');
                toggleBtn.disabled = true;
                toggleBtn.style.opacity = '0.7';

                const formData = new FormData();
                formData.append('action', 'vgt_toggle_sentinel');
                formData.append('nonce', vgtConfig.nonce);

                fetch(vgtConfig.ajaxUrl, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.playSound('alert');
                        updateUI(data.data.enabled);
                        this.addLog(data.data.message);
                        vgtConfig.sentinelEnabled = data.data.enabled;
                        toggleBtn.textContent = 'Lade neu...';
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        console.error('Sentinel toggle failed:', data.data);
                        toggleBtn.disabled = false;
                        toggleBtn.style.opacity = '1';
                    }
                })
                .catch(err => {
                    console.error('Sentinel toggle network error:', err);
                    toggleBtn.disabled = false;
                    toggleBtn.style.opacity = '1';
                });
            });
        }
    },

    getWorkspaceMetrics() {
        const workspace = document.getElementById('desktop-workspace');
        if (!workspace) return null;
        const zoom = parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--vgt-font-zoom')) || 1;
        const rect = workspace.getBoundingClientRect();
        return {
            workspace,
            zoom,
            width: rect.width / zoom,
            height: rect.height / zoom
        };
    },

    clampWidgetToWorkspace(widget) {
        const metrics = this.getWorkspaceMetrics();
        if (!metrics || !widget || widget.style.display === 'none') return;

        const rect = widget.getBoundingClientRect();
        const widgetWidth = Math.max(160, rect.width / metrics.zoom);
        const widgetHeight = Math.max(120, rect.height / metrics.zoom);
        const maxLeft = Math.max(10, metrics.width - widgetWidth - 10);
        const maxTop = Math.max(10, metrics.height - widgetHeight - 10);

        let left;
        if (widget.style.left && widget.style.left !== 'auto') {
            left = parseFloat(widget.style.left);
        } else if (widget.style.right && widget.style.right !== 'auto') {
            left = metrics.width - parseFloat(widget.style.right) - widgetWidth;
        } else {
            left = Math.min(maxLeft, Math.max(10, rect.left / metrics.zoom));
        }

        let top;
        if (widget.style.top && widget.style.top !== 'auto') {
            top = parseFloat(widget.style.top);
        } else if (widget.style.bottom && widget.style.bottom !== 'auto') {
            top = metrics.height - parseFloat(widget.style.bottom) - widgetHeight;
        } else {
            top = Math.min(maxTop, Math.max(10, rect.top / metrics.zoom));
        }

        left = Math.min(maxLeft, Math.max(10, Number.isFinite(left) ? left : 10));
        top = Math.min(maxTop, Math.max(10, Number.isFinite(top) ? top : 10));

        widget.style.left = `${left}px`;
        widget.style.top = `${top}px`;
        widget.style.right = 'auto';
        widget.style.bottom = 'auto';
    },

    normalizeWidgetsToViewport() {
        document.querySelectorAll('.vgt-widget').forEach(widget => this.clampWidgetToWorkspace(widget));
    },
    /**
     * Apply absolute left/top px to a widget (clears right/bottom zombies).
     */
    applyWidgetPosition(widget, pos) {
        if (!widget || !pos || typeof pos !== 'object') return;
        if (pos.left) {
            widget.style.left = pos.left;
            widget.style.right = 'auto';
        } else if (pos.right) {
            widget.style.right = pos.right;
            widget.style.left = 'auto';
        }
        if (pos.top) {
            widget.style.top = pos.top;
            widget.style.bottom = 'auto';
        } else if (pos.bottom) {
            widget.style.bottom = pos.bottom;
            widget.style.top = 'auto';
        }
        if (pos.visible === false) {
            widget.style.display = 'none';
        } else if (pos.visible === true) {
            widget.style.display = 'flex';
        }
    },

    /**
     * Snapshot current widget box as integer px left+top (workspace-relative).
     */
    snapshotWidgetPosition(widget, workspace) {
        if (!widget || !workspace) return null;
        const z = parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--vgt-font-zoom')) || 1;
        const wsR = workspace.getBoundingClientRect();
        const r = widget.getBoundingClientRect();
        let left = (r.left - wsR.left) / z;
        let top = (r.top - wsR.top) / z;
        if (!Number.isFinite(left)) left = 10;
        if (!Number.isFinite(top)) top = 10;
        return {
            left: `${Math.round(left)}px`,
            top: `${Math.round(top)}px`,
            visible: widget.style.display !== 'none'
        };
    },

    persistWidgetPositions() {
        if (!this.userSettings.widget_positions || Array.isArray(this.userSettings.widget_positions)) {
            this.userSettings.widget_positions = {};
        }
        const payload = { ...this.userSettings.widget_positions };
        // Local backup so reload still works if AJAX is slow/fails.
        try {
            localStorage.setItem('vgt_widget_positions', JSON.stringify(payload));
        } catch (e) { /* ignore quota */ }
        this.saveUserSetting('widget_positions', payload);
    },

    initWidgetDraggables() {
        const widgets = document.querySelectorAll('.vgt-widget');
        const workspace = document.getElementById('desktop-workspace');
        if (!workspace) return;

        // Merge server settings with localStorage backup (server wins on conflict if non-empty).
        let positions = this.userSettings.widget_positions || {};
        if (!positions || Array.isArray(positions) || Object.keys(positions).length === 0) {
            try {
                const cached = JSON.parse(localStorage.getItem('vgt_widget_positions') || '{}');
                if (cached && typeof cached === 'object' && !Array.isArray(cached)) {
                    positions = cached;
                    this.userSettings.widget_positions = { ...cached };
                }
            } catch (e) { /* ignore */ }
        }
        if (!this.userSettings.widget_positions || Array.isArray(this.userSettings.widget_positions)) {
            this.userSettings.widget_positions = {};
        }

        widgets.forEach(widget => {
            const id = widget.id;
            if (!id) return;
            const saved = positions[id];
            if (saved) {
                this.applyWidgetPosition(widget, saved);
            } else {
                // Convert CSS calc() defaults into absolute px once so later saves are stable.
                const snap = this.snapshotWidgetPosition(widget, workspace);
                if (snap) {
                    widget.style.left = snap.left;
                    widget.style.top = snap.top;
                    widget.style.right = 'auto';
                    widget.style.bottom = 'auto';
                }
            }
        });

        // Clamp after layout so widgets never sit off-screen after restore.
        requestAnimationFrame(() => {
            this.normalizeWidgetsToViewport();
        });

        widgets.forEach(widget => {
            let isDragging = false;
            let offsetX = 0, offsetY = 0;

            widget.addEventListener('mousedown', (e) => {
                if (
                    e.target.tagName === 'TEXTAREA' ||
                    e.target.tagName === 'INPUT' ||
                    e.target.tagName === 'BUTTON' ||
                    e.target.closest('.vgt-widget-textarea')
                ) {
                    return;
                }

                if (e.button !== 0) return;

                const zoom = parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--vgt-font-zoom')) || 1;
                isDragging = true;
                widget.classList.add('dragging');

                const rect = widget.getBoundingClientRect();

                offsetX = (e.clientX - rect.left) / zoom;
                offsetY = (e.clientY - rect.top) / zoom;

                const onMouseMove = (ev) => {
                    if (!isDragging) return;

                    const z = parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--vgt-font-zoom')) || 1;
                    const wsR = workspace.getBoundingClientRect();
                    const r = widget.getBoundingClientRect();

                    const wsLeft = wsR.left / z;
                    const wsTop = wsR.top / z;
                    const wsWidth = wsR.width / z;
                    const wsHeight = wsR.height / z;
                    const widgetWidth = r.width / z;
                    const widgetHeight = r.height / z;

                    let left = (ev.clientX / z) - wsLeft - offsetX;
                    let top = (ev.clientY / z) - wsTop - offsetY;

                    if (left < 10) left = 10;
                    if (top < 10) top = 10;
                    if (left > wsWidth - widgetWidth - 10) left = wsWidth - widgetWidth - 10;
                    if (top > wsHeight - widgetHeight - 10) top = wsHeight - widgetHeight - 10;

                    widget.style.right = 'auto';
                    widget.style.bottom = 'auto';
                    widget.style.left = `${Math.round(left)}px`;
                    widget.style.top = `${Math.round(top)}px`;
                };

                const onMouseUp = () => {
                    if (!isDragging) return;
                    isDragging = false;
                    widget.classList.remove('dragging');

                    document.removeEventListener('mousemove', onMouseMove);
                    document.removeEventListener('mouseup', onMouseUp);

                    if (!widget.id) {
                        console.warn('VGT: widget without id — position not saved');
                        return;
                    }

                    // Prefer geometry snapshot (works even if style.left was calc).
                    const pos = this.snapshotWidgetPosition(widget, workspace) || {
                        left: '10px',
                        top: '10px',
                        visible: widget.style.display !== 'none'
                    };
                    widget.style.left = pos.left;
                    widget.style.top = pos.top;
                    widget.style.right = 'auto';
                    widget.style.bottom = 'auto';

                    if (!this.userSettings.widget_positions || Array.isArray(this.userSettings.widget_positions)) {
                        this.userSettings.widget_positions = {};
                    }
                    this.userSettings.widget_positions[widget.id] = pos;
                    // Full-replace payload (server no longer delta-merges widget_positions).
                    this.persistWidgetPositions();
                };

                document.addEventListener('mousemove', onMouseMove);
                document.addEventListener('mouseup', onMouseUp);
            });
        });

        if (this.isSystemWidgetActive()) {
            this.startDiagnosticsPolling();
        }
    },

    /* ==========================================================================
       LIVE LATENCY GRAPH CANVAS ANIMATION
       ========================================================================== */
    ccGraphInterval: null,
    ccGraphPoints: [],
    
    startLatencyGraph() {
        const canvas = document.getElementById('vgt-cc-graph');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        if (!ctx) return;
        
        this.stopLatencyGraph();
        this.ccGraphPoints = Array(20).fill(15);
        
        let lastFrameTime = performance.now();
        
        const renderGraph = () => {
            const now = performance.now();
            const delta = now - lastFrameTime;
            lastFrameTime = now;
            
            const latencyVal = Math.min(60, Math.max(5, delta + (Math.random() * 4 - 2)));
            
            this.ccGraphPoints.push(latencyVal);
            if (this.ccGraphPoints.length > 30) {
                this.ccGraphPoints.shift();
            }
            
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            ctx.strokeStyle = 'rgba(255, 255, 255, 0.05)';
            ctx.lineWidth = 1;
            for (let y = 10; y < canvas.height; y += 20) {
                ctx.beginPath();
                ctx.moveTo(0, y);
                ctx.lineTo(canvas.width, y);
                ctx.stroke();
            }
            
            ctx.beginPath();
            const step = canvas.width / (this.ccGraphPoints.length - 1);
            ctx.moveTo(0, canvas.height);
            this.ccGraphPoints.forEach((p, idx) => {
                const y = canvas.height - ((p - 5) / 55) * (canvas.height - 20) - 10;
                ctx.lineTo(idx * step, y);
            });
            ctx.lineTo(canvas.width, canvas.height);
            ctx.closePath();
            
            const accentColor = getComputedStyle(document.documentElement).getPropertyValue('--vgt-accent-color').trim() || '#6366f1';
            ctx.fillStyle = accentColor + '15';
            ctx.fill();
            
            ctx.beginPath();
            this.ccGraphPoints.forEach((p, idx) => {
                const y = canvas.height - ((p - 5) / 55) * (canvas.height - 20) - 10;
                if (idx === 0) {
                    ctx.moveTo(0, y);
                } else {
                    ctx.lineTo(idx * step, y);
                }
            });
            ctx.strokeStyle = accentColor;
            ctx.lineWidth = 2;
            ctx.lineJoin = 'round';
            ctx.lineCap = 'round';
            ctx.stroke();
            
            ctx.fillStyle = '#ffffff';
            ctx.font = 'bold 9px sans-serif';
            ctx.fillText(Math.round(latencyVal) + ' ms', canvas.width - 45, 15);
            
            this.ccGraphInterval = requestAnimationFrame(renderGraph);
        };
        
        this.ccGraphInterval = requestAnimationFrame(renderGraph);
    },
    
    stopLatencyGraph() {
        if (this.ccGraphInterval) {
            cancelAnimationFrame(this.ccGraphInterval);
            this.ccGraphInterval = null;
        }
    },

    updateWidgetData(diag) {
        // 1. Live Threat-Stream Widget
        const threatList = document.getElementById('vgt-threat-stream-list');
        if (threatList && diag.threats) {
            let html = '';
            diag.threats.forEach(t => {
                const ipEscaped = this.escapeHTML(t.ip);
                const typeEscaped = this.escapeHTML(t.type);
                const msgEscaped = this.escapeHTML(t.message);
                const verEscaped = this.escapeHTML(t.version);

                let badgeColor = '#ef4444'; // default red
                if (t.type === 'SQLi') badgeColor = '#f59e0b'; // amber
                if (t.type === 'Brute-Force') badgeColor = '#3b82f6'; // blue

                html += `
                    <div class="vgt-threat-card" style="padding: 8px; background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 8px; display: flex; flex-direction: column; gap: 4px; font-size: 11px;">
                        <div style="display: flex; align-items: center; justify-content: space-between; font-weight: 700; color: #ffffff;">
                            <span style="font-family: monospace;">${ipEscaped}</span>
                            <span class="vgt-badge-item" style="background: ${badgeColor}15; color: ${badgeColor}; border: 1px solid ${badgeColor}30; font-size: 9px; padding: 2px 4px; border-radius: 4px;">${typeEscaped}</span>
                        </div>
                        <div style="color: #94a3b8; line-height: 1.3; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${msgEscaped}">${msgEscaped}</div>
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 4px;">
                            <span style="color: #64748b; font-size: 9px;">${verEscaped}</span>
                            <button onclick="VGTDeskEngine.banIP('${ipEscaped}', this)" class="vgt-btn-danger" style="font-size: 9px; padding: 2px 6px; line-height: 1; border-radius: 4px; border: none; cursor: pointer; font-weight: 700;">Hard Punish</button>
                        </div>
                    </div>
                `;
            });
            threatList.innerHTML = html;
        }

        // 2. Dattrack Telemetry Widget
        const dtTodayEvents = document.getElementById('vgt-dt-today-events');
        const dtTodayUsers = document.getElementById('vgt-dt-today-users');
        const dtChart = document.getElementById('vgt-dattrack-chart');

        if (diag.dattrack && diag.dattrack.length > 0) {
            const today = diag.dattrack[diag.dattrack.length - 1];
            if (dtTodayEvents) dtTodayEvents.textContent = today.events;
            if (dtTodayUsers) dtTodayUsers.textContent = today.users;

            if (dtChart) {
                const maxEvents = Math.max(...diag.dattrack.map(d => d.events), 1);
                let chartHtml = '';
                diag.dattrack.forEach(day => {
                    const heightPct = Math.max(10, Math.round((day.events / maxEvents) * 100));
                    chartHtml += `
                        <div class="vgt-dt-bar-wrapper" style="display: flex; flex-direction: column; align-items: center; gap: 2px; flex: 1;">
                            <div class="vgt-dt-bar" style="width: 12px; height: ${heightPct}%; background: var(--vgt-accent-color); border-radius: 4px 4px 0 0; transition: height 0.3s;" title="${day.events} Hits (${day.users} Uniques)"></div>
                            <span style="font-size: 8px; color: #64748b;">${day.date}</span>
                        </div>
                    `;
                });
                dtChart.innerHTML = chartHtml;
            }
        }

        // 3. Sovereign Optimizer Widget
        const optOverhead = document.getElementById('vgt-opt-overhead');
        const optTransients = document.getElementById('vgt-opt-transients');
        if (optOverhead && diag.db_overhead !== undefined) {
            const sizeKB = Math.round(diag.db_overhead / 1024);
            if (sizeKB > 1024) {
                optOverhead.textContent = `${(sizeKB / 1024).toFixed(1)} MB`;
            } else {
                optOverhead.textContent = `${sizeKB} KB`;
            }
        }
        if (optTransients && diag.transient_count !== undefined) {
            optTransients.textContent = diag.transient_count;
        }
    },

    banIP(ip, btn) {
        if (typeof vgtConfig === 'undefined' || !vgtConfig.ajaxUrl) return;

        this.playSound('click');
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Blocking...';
            btn.style.opacity = '0.6';
        }

        const formData = new FormData();
        formData.append('action', 'vgt_ban_ip');
        formData.append('nonce', vgtConfig.nonce);
        formData.append('ip', ip);
        formData.append('reason', 'Permanente Sperrung über Threat-Stream Widget');

        fetch(vgtConfig.ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                this.playSound('alert');
                this.addLog(data.data);
                this.updateDiagnostics();
            } else {
                this.addLog(`Fehler beim Sperren von ${ip}`);
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = 'Hard Punish';
                    btn.style.opacity = '1';
                }
            }
        })
        .catch(err => {
            console.error(err);
            if (btn) {
                btn.disabled = false;
                btn.textContent = 'Hard Punish';
                btn.style.opacity = '1';
            }
        });
    },

    optimizeDatabase(btn) {
        if (typeof vgtConfig === 'undefined' || !vgtConfig.ajaxUrl) return;

        this.playSound('click');
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Bereinige...';
            btn.style.opacity = '0.6';
        }

        const formData = new FormData();
        formData.append('action', 'vgt_optimize_database');
        formData.append('nonce', vgtConfig.nonce);

        fetch(vgtConfig.ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                this.playSound('alert');
                this.addLog(data.data);
                this.updateDiagnostics();
                setTimeout(() => {
                    if (btn) {
                        btn.disabled = false;
                        btn.textContent = 'Bereinigung starten';
                        btn.style.opacity = '1';
                    }
                }, 1000);
            } else {
                this.addLog('Optimierung fehlgeschlagen.');
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = 'Bereinigung starten';
                    btn.style.opacity = '1';
                }
            }
        })
        .catch(err => {
            console.error(err);
            if (btn) {
                btn.disabled = false;
                btn.textContent = 'Bereinigung starten';
                btn.style.opacity = '1';
            }
        });
    }
});
