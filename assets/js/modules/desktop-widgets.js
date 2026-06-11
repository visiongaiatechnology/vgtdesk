/**
 * VGT Desktop Module - Widgets & Latency Graph
 * Handles: initWidgets, initSentinelWidget, initWidgetDraggables, startLatencyGraph, stopLatencyGraph
 */

Object.assign(window.VGTDeskEngine, {
    initWidgets() {
        const textarea = document.getElementById('vgt-widget-notes-text');
        if (!textarea) return;
        
        textarea.value = localStorage.getItem('vgt_widget_notes') || '';
        
        textarea.addEventListener('input', () => {
            localStorage.setItem('vgt_widget_notes', textarea.value);
        });
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
                if (saved.left) widget.style.left = saved.left;
                if (saved.top) widget.style.top = saved.top;
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
                
                isDragging = true;
                widget.classList.add('dragging');
                
                const rect = widget.getBoundingClientRect();
                const wsRect = workspace.getBoundingClientRect();
                
                offsetX = e.clientX - rect.left;
                offsetY = e.clientY - rect.top;
                
                const onMouseMove = (ev) => {
                    if (!isDragging) return;
                    
                    let left = ev.clientX - wsRect.left - offsetX;
                    let top = ev.clientY - wsRect.top - offsetY;
                    
                    if (left < 10) left = 10;
                    if (top < 10) top = 10;
                    if (left > wsRect.width - rect.width - 10) left = wsRect.width - rect.width - 10;
                    if (top > wsRect.height - rect.height - 10) top = wsRect.height - rect.height - 10;
                    
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
                    this.userSettings.widget_positions[widget.id] = {
                        left: widget.style.left,
                        top: widget.style.top,
                        visible: widget.style.display !== 'none'
                    };
                    this.saveUserSetting('widget_positions', this.userSettings.widget_positions);
                };
                
                document.addEventListener('mousemove', onMouseMove);
                document.addEventListener('mouseup', onMouseUp);
            });
        });
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
    }
});
