document.addEventListener('DOMContentLoaded', function() {
    if (typeof vgtDashboardData === 'undefined' || !vgtDashboardData.metrics) return;
    
    const vaultData = vgtDashboardData.metrics;
    const state = { activeTab: 'today' };
    
    const els = {
        events: document.getElementById('stat-events'),
        users: document.getElementById('stat-users'),
        paths: document.getElementById('vgt-paths-container'),
        tabs: document.querySelectorAll('.vgt-tab')
    };

    const fmt = (num) => new Intl.NumberFormat('de-DE').format(num);

    function renderState() {
        const data = vaultData[state.activeTab];
        if (!data) return;
        
        els.events.innerText = fmt(data.events);
        els.users.innerText = fmt(data.unique_users);
        
        els.paths.innerHTML = '';
        if (Object.keys(data.paths).length === 0) {
            els.paths.innerHTML = '<div class="vgt-path-row"><span class="vgt-path-url" style="color: #475569;">Keine Daten in diesem Zeitraum</span></div>';
            return;
        }

        const pathValues = Object.values(data.paths);
        const maxEvents = pathValues.length > 0 ? Math.max(...pathValues) : 1;
        
        for (const [path, count] of Object.entries(data.paths)) {
            const pct = (count / maxEvents) * 100;
            const row = document.createElement('div');
            row.className = 'vgt-path-row';
            row.innerHTML = `
                <div class="vgt-path-bar" style="width: 0%"></div>
                <div class="vgt-path-url">${path}</div>
                <div class="vgt-path-count">${fmt(count)}</div>
            `;
            els.paths.appendChild(row);
            
            setTimeout(() => {
                const bar = row.querySelector('.vgt-path-bar');
                if (bar) bar.style.width = pct + '%';
            }, 50);
        }
    }

    els.tabs.forEach(tab => {
        tab.addEventListener('click', (e) => {
            els.tabs.forEach(t => t.classList.remove('active'));
            e.target.classList.add('active');
            state.activeTab = e.target.getAttribute('data-target');
            renderState();
        });
    });

    function initChart() {
        const canvas = document.getElementById('vgtTelemetryChart');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        
        function resizeCanvas() {
            const rect = canvas.parentElement.getBoundingClientRect();
            // Fallback Dimensionen für Print Isolationskammer
            const cWidth = rect.width > 0 ? rect.width : 800; 
            const cHeight = rect.height > 0 ? rect.height : 280;

            canvas.width = (cWidth - 48) * window.devicePixelRatio; 
            canvas.height = (cHeight - 60) * window.devicePixelRatio;
            ctx.scale(window.devicePixelRatio, window.devicePixelRatio);
            drawChart();
        }

        function drawChart() {
            const data = vaultData.all.timeline_chart;
            if (!data || data.length === 0) return;

            const width = canvas.width / window.devicePixelRatio;
            const height = canvas.height / window.devicePixelRatio;
            ctx.clearRect(0, 0, width, height);

            const counts = data.map(d => d.count);
            const maxCount = counts.length > 0 ? Math.max(...counts, 10) : 10;
            const padX = 10, padY = 20;
            const effWidth = width - padX * 2;
            const effHeight = height - padY * 2;

            ctx.strokeStyle = 'rgba(255, 255, 255, 0.03)';
            ctx.lineWidth = 1;
            ctx.beginPath();
            for(let i=0; i<=4; i++) {
                const y = padY + (effHeight / 4) * i;
                ctx.moveTo(padX, y);
                ctx.lineTo(width - padX, y);
            }
            ctx.stroke();

            ctx.beginPath();
            ctx.strokeStyle = '#00f0ff';
            ctx.lineWidth = 2;
            ctx.lineJoin = 'round';

            data.forEach((point, index) => {
                const x = padX + (effWidth / Math.max(1, data.length - 1)) * index;
                const y = padY + effHeight - (point.count / maxCount) * effHeight;
                if (index === 0) ctx.moveTo(x, y); else ctx.lineTo(x, y);
            });
            ctx.stroke();

            ctx.lineTo(width - padX, height - padY);
            ctx.lineTo(padX, height - padY);
            ctx.closePath();
            
            const gradient = ctx.createLinearGradient(0, 0, 0, height);
            gradient.addColorStop(0, 'rgba(0, 240, 255, 0.15)');
            gradient.addColorStop(1, 'rgba(0, 240, 255, 0)');
            ctx.fillStyle = gradient;
            ctx.fill();
        }

        window.addEventListener('resize', resizeCanvas);
        resizeCanvas();
    }

    // --- VGT SUPREME PRINT MODE INTERCEPTOR ---
    if (window.vgtIsPrintMode) {
        state.activeTab = 'all';
        renderState();
        initChart();
        
        // Warte 800ms für Font-Rendering & Canvas-Animationen, dann zwinge Print
        setTimeout(() => {
            window.print();
        }, 800);
        return; // Stoppe normales Dashboard Setup
    }

    renderState();
    initChart();
});