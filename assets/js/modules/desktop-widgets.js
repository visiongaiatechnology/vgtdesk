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

    initWidgetDraggables() {
        const widgets = document.querySelectorAll('.vgt-widget');
        const workspace = document.getElementById('desktop-workspace');
        if (!workspace) return;
        
        const positions = this.userSettings.widget_positions || {};
        widgets.forEach(widget => {
            const id = widget.id;
            const saved = positions[id];
            if (saved) {
                if (saved.left) {
                    widget.style.left = saved.left;
                    widget.style.right = '';
                } else if (saved.right) {
                    widget.style.right = saved.right;
                    widget.style.left = '';
                }
                if (saved.top) {
                    widget.style.top = saved.top;
                    widget.style.bottom = '';
                } else if (saved.bottom) {
                    widget.style.bottom = saved.bottom;
                    widget.style.top = '';
                }
                if (saved.visible === false) {
                    widget.style.display = 'none';
                } else if (saved.visible === true) {
                    widget.style.display = 'flex';
                }
            }
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
                const wsRect = workspace.getBoundingClientRect();
                
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
                    
                    widget.style.right = '';
                    widget.style.left = `${left}px`;
                    widget.style.top = `${top}px`;
                };
                
                const onMouseUp = () => {
                    if (!isDragging) return;
                    isDragging = false;
                    widget.classList.remove('dragging');
                    
                    document.removeEventListener('mousemove', onMouseMove);
                    document.removeEventListener('mouseup', onMouseUp);
                    
                    if (!this.userSettings.widget_positions || Array.isArray(this.userSettings.widget_positions)) {
                        this.userSettings.widget_positions = {};
                    }
                    
                    const z = parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--vgt-font-zoom')) || 1;
                    const wsR = workspace.getBoundingClientRect();
                    const r = widget.getBoundingClientRect();
                    const wsWidth = wsR.width / z;
                    const widgetWidth = r.width / z;
                    
                    const leftVal = parseFloat(widget.style.left);
                    let pos = {
                        top: widget.style.top,
                        visible: widget.style.display !== 'none'
                    };
                    
                    if (leftVal > (wsWidth - widgetWidth) / 2) {
                        // Right-aligned widget saving
                        const rightVal = wsWidth - leftVal - widgetWidth;
                        pos.right = `${rightVal}px`;
                    } else {
                        pos.left = widget.style.left;
                    }
                    
                    this.userSettings.widget_positions[widget.id] = pos;
                    this.saveUserSetting('widget_positions', this.userSettings.widget_positions);
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
