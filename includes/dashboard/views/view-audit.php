<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

// Generate secure CSP Nonce
$nonce = wp_create_nonce('vgt_sentinel_audit_action');
$csp_nonce = function_exists('vgt_get_csp_nonce') ? vgt_get_csp_nonce() : '';
?>

<div class="vgt-audit-wrapper">
    <!-- Header-Bereich -->
    <div class="cyber-hud-card" style="margin-bottom: 25px; padding: 30px; border-left: 4px solid var(--vgts-accent);">
        <div style="display: flex; align-items: center; gap: 20px;">
            <div style="font-size: 36px; line-height: 1; filter: drop-shadow(0 0 10px var(--vgts-accent-glow));">💠</div>
            <div>
                <h2 class="cyber-glitch-title" style="margin: 0; font-size: 22px; font-weight: 800; color: #ffffff; letter-spacing: 0.5px;">VGT SENTINEL AUDITOR</h2>
                <p style="margin: 5px 0 0 0; font-size: 11px; color: var(--vgts-text-secondary); font-family: var(--vgts-font-mono); text-transform: uppercase; letter-spacing: 1px;">WordPress Architecture Deep Scan v2.2 — DIAMANT SUPREME</p>
            </div>
        </div>
    </div>

    <!-- Einleitungs-Bereich -->
    <div id="vgt-intro" class="cyber-hud-card" style="margin-bottom: 25px; border-color: rgba(0, 229, 255, 0.15);">
        <h3 style="margin: 0 0 15px 0; font-size: 13px; text-transform: uppercase; letter-spacing: 2px; color: var(--vgts-accent); text-shadow: 0 0 8px var(--vgts-accent-glow); font-weight: 800; font-family: var(--vgts-font-mono);">[ SYSTEM_SECURITY_HARDENING_AUDIT ]</h3>
        <p style="color: var(--vgts-text-secondary); line-height: 1.7; font-size: 13px; margin-bottom: 20px;">
            Dieser Deep-Scanner analysiert die kritische Sicherheitsarchitektur Ihrer WordPress-Installation. Er führt Isolationstests des Dateisystems durch, überprüft Zugriffsrechte und validiert kryptografische Header nach dem Zero-Trust-Prinzip. Sentinel-Schutzkonfigurationen werden dabei automatisch einbezogen und als Schutzschilde bewertet.
        </p>
        <div style="background: rgba(0, 229, 255, 0.02); border-left: 3px solid var(--vgts-accent); padding: 15px 20px; border-radius: 4px; margin-bottom: 25px; font-size: 12px; color: var(--vgts-text-secondary); line-height: 1.6; border-top: 1px solid rgba(0, 229, 255, 0.05); border-right: 1px solid rgba(0, 229, 255, 0.05); border-bottom: 1px solid rgba(0, 229, 255, 0.05); font-family: var(--vgts-font-mono);">
            <strong style="color: #fff; display: block; margin-bottom: 6px; letter-spacing: 0.5px;">⚠️ AKTIVER AUDIT-TEST-HINWEIS:</strong>
            Dieser Audit führt aktive lokale Sicherheitstests aus und erstellt temporär eine isolierte Testdatei im Upload-Verzeichnis. Die Datei wird nach der erfolgreichen Prüfung automatisch wieder entfernt.
        </div>
        <button id="vgt-start-btn" class="cyber-btn">
            <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="3" fill="none" stroke-linecap="round" stroke-linejoin="round" style="display: block;">
                <circle cx="12" cy="12" r="10"></circle>
                <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                <path d="M2 12h20"></path>
            </svg>
            System-Audit Starten
        </button>
    </div>

    <!-- Lade-Indikator -->
    <div id="vgt-loading" class="vgt-hidden">
        <div class="cyber-scanner-bar"></div>
        <div class="vgt-spinner-box">
            <div class="vgt-spinner"></div>
            <h3 class="vgt-loading-title">Führe Hardening Audit aus...</h3>
            <p id="vgt-loading-text" class="vgt-loading-sub">Initiiere Phase 1: Core Analysis...</p>
        </div>
    </div>

    <!-- Fehler-Zustand -->
    <div id="vgt-error-box" class="vgt-hidden">
        <div class="vgt-error-content">
            <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round" style="display: block;">
                <polygon points="7.86 2 16.14 2 22 7.86 22 16.14 16.14 22 7.86 22 2 16.14 2 7.86 7.86 2"></polygon>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <span id="vgt-error-message"></span>
        </div>
    </div>

    <!-- Ergebnis-Bereich -->
    <div id="vgt-results" class="vgt-hidden">
        <!-- Tier Cards Grid -->
        <div class="vgt-tier-grid">
            <div id="vgt-tier-card-5" class="vgt-tier-card vgt-tier-card-diamond">
                <div class="vgt-tier-card-badge">💎 DIAMANT</div>
                <div class="vgt-tier-card-title">SUPREME COMPLIANT</div>
                <div class="vgt-tier-card-desc">90% - 100% Score. Der ultimative Sicherheitsstatus. Volles Zero-Trust-Hardening, gehärtete PHP-Umgebung und WAF-Schutz.</div>
            </div>
            <div id="vgt-tier-card-4" class="vgt-tier-card vgt-tier-card-platin">
                <div class="vgt-tier-card-badge">🏅 PLATIN GOLD</div>
                <div class="vgt-tier-card-title">GOLD SECURE</div>
                <div class="vgt-tier-card-desc">75% - 89% Score. Hervorragende Absicherung. Wichtige Login-Schilde, restriktive Rechte und Sentinel-Schutz aktiv.</div>
            </div>
            <div id="vgt-tier-card-3" class="vgt-tier-card vgt-tier-card-secured">
                <div class="vgt-tier-card-badge">🛡️ VGT SECURED</div>
                <div class="vgt-tier-card-title">SECURED BASE</div>
                <div class="vgt-tier-card-desc">60% - 74% Score. Solider Basisschutz. Standardkonfigurationen und DB-Präfixe sind gesichert, bedürfen aber Härtung.</div>
            </div>
            <div id="vgt-tier-card-1" class="vgt-tier-card vgt-tier-card-critical">
                <div class="vgt-tier-card-badge">⚠️ CRITICAL RISK</div>
                <div class="vgt-tier-card-title">ARCHITECTURE RISK</div>
                <div class="vgt-tier-card-desc">&lt; 60% Score. Kritisches Risiko. Ungeschützte Dateisystembereiche, Standard-Präfixe oder offene Benutzer-API.</div>
            </div>
        </div>

        <div class="cyber-score-board">
            <div>
                <p class="vgt-metric-label">Hardening Index</p>
                <div class="vgt-score-value"><span id="vgt-score">0</span><span class="vgt-score-max">/100</span></div>
            </div>
            <div class="vgt-align-right">
                <p class="vgt-metric-label">Sicherheits-Status</p>
                <div id="vgt-tier" class="vgt-tier-label">CALCULATING...</div>
            </div>
        </div>

        <h3 style="font-size: 14px; color: #ffffff; text-transform: uppercase; border-bottom: 1px solid var(--vgts-border); padding-bottom: 12px; margin-bottom: 24px; letter-spacing: 1.5px; font-weight: 800; font-family: var(--vgts-font-mono);">[ Audit-Vektoren ]</h3>
        <div id="vgt-vectors" class="vgt-grid">
            <!-- Wird via JavaScript sicher und XSS-geschützt aufgebaut -->
        </div>
    </div>
</div>

<!-- High-Fidelity Custom Styles for Auditor -->
<style nonce="<?php echo esc_attr($csp_nonce); ?>">
    .vgt-hidden { display: none !important; }
    
    /* TIER CARDS GRID */
    .vgt-tier-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 15px;
        margin-bottom: 30px;
    }
    @media (max-width: 1024px) {
        .vgt-tier-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    @media (max-width: 640px) {
        .vgt-tier-grid {
            grid-template-columns: 1fr;
        }
    }
    .vgt-tier-card {
        background: rgba(10, 15, 30, 0.4);
        border: 1px solid rgba(255, 255, 255, 0.05);
        border-radius: 4px;
        padding: 18px;
        opacity: 0.3;
        filter: grayscale(100%);
        transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
        position: relative;
        overflow: hidden;
    }
    .vgt-tier-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(180deg, rgba(255,255,255,0.01) 50%, rgba(0,0,0,0.1) 50%);
        background-size: 100% 4px;
        pointer-events: none;
    }
    .vgt-tier-card-badge {
        font-family: var(--vgts-font-mono), monospace;
        font-size: 11px;
        font-weight: 800;
        margin-bottom: 6px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .vgt-tier-card-title {
        font-size: 12px;
        font-weight: 800;
        color: #ffffff;
        margin-bottom: 10px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .vgt-tier-card-desc {
        font-size: 11px;
        color: var(--vgts-text-secondary);
        line-height: 1.5;
    }
    
    /* TIER ACTIVE STATES */
    .vgt-tier-card-diamond.active {
        opacity: 1;
        filter: none;
        border-color: #00e5ff;
        background: rgba(0, 229, 255, 0.08);
        box-shadow: 0 0 25px rgba(0, 229, 255, 0.15), inset 0 0 15px rgba(0, 229, 255, 0.1);
    }
    .vgt-tier-card-diamond .vgt-tier-card-badge {
        color: #00e5ff;
        text-shadow: 0 0 8px rgba(0, 229, 255, 0.3);
    }
    
    .vgt-tier-card-platin.active {
        opacity: 1;
        filter: none;
        border-color: #ffb703;
        background: rgba(245, 158, 11, 0.08);
        box-shadow: 0 0 25px rgba(245, 158, 11, 0.15), inset 0 0 15px rgba(245, 158, 11, 0.1);
    }
    .vgt-tier-card-platin .vgt-tier-card-badge {
        color: #ffb703;
        text-shadow: 0 0 8px rgba(245, 158, 11, 0.3);
    }
    
    .vgt-tier-card-secured.active {
        opacity: 1;
        filter: none;
        border-color: #00fa9a;
        background: rgba(0, 250, 154, 0.08);
        box-shadow: 0 0 25px rgba(0, 250, 154, 0.15), inset 0 0 15px rgba(0, 250, 154, 0.1);
    }
    .vgt-tier-card-secured .vgt-tier-card-badge {
        color: #00fa9a;
        text-shadow: 0 0 8px rgba(0, 250, 154, 0.3);
    }
    
    .vgt-tier-card-critical.active {
        opacity: 1;
        filter: none;
        border-color: #ff2a5f;
        background: rgba(255, 42, 95, 0.08);
        box-shadow: 0 0 25px rgba(255, 42, 95, 0.15), inset 0 0 15px rgba(255, 42, 95, 0.1);
    }
    .vgt-tier-card-critical .vgt-tier-card-badge {
        color: #ff2a5f;
        text-shadow: 0 0 8px rgba(255, 42, 95, 0.3);
    }
    
    /* CYBERPUNK HUD CARD & UI */
    .cyber-hud-card {
        position: relative;
        background: rgba(7, 10, 20, 0.75);
        border: 1px solid rgba(0, 229, 255, 0.2);
        box-shadow: 0 0 20px rgba(0, 229, 255, 0.05), inset 0 0 15px rgba(0, 229, 255, 0.05);
        border-radius: 4px;
        padding: 25px;
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        overflow: hidden;
    }
    
    .cyber-hud-card::before, .cyber-hud-card::after {
        content: '';
        position: absolute;
        width: 12px;
        height: 12px;
        border-color: var(--vgts-accent);
        border-style: solid;
        pointer-events: none;
    }
    
    .cyber-hud-card::before {
        top: 0;
        left: 0;
        border-width: 2px 0 0 2px;
    }
    
    .cyber-hud-card::after {
        bottom: 0;
        right: 0;
        border-width: 0 2px 2px 0;
    }
    
    .cyber-hud-card:hover {
        border-color: rgba(0, 229, 255, 0.4);
        box-shadow: 0 0 30px rgba(0, 229, 255, 0.15), inset 0 0 20px rgba(0, 229, 255, 0.1);
    }
    
    /* TECH GRID UNDERLAY */
    .vgt-audit-wrapper {
        position: relative;
        background: radial-gradient(circle at center, rgba(10, 15, 30, 0.2) 0%, rgba(5, 7, 12, 0.95) 100%);
        padding: 10px;
        min-height: 100%;
    }
    
    /* GLITCH EFFECT (SUBTLE & CLEAN) */
    .cyber-glitch-title {
        font-family: var(--vgts-font-mono), monospace;
        letter-spacing: 2px;
        text-shadow: 2px 0 0 rgba(255, 0, 85, 0.5), -2px 0 0 rgba(0, 229, 255, 0.5);
    }
    
    /* DIGITAL SCOREBOARD */
    .cyber-score-board {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: linear-gradient(135deg, rgba(8, 12, 24, 0.9) 0%, rgba(4, 6, 12, 0.95) 100%);
        border: 1px solid rgba(0, 229, 255, 0.25);
        padding: 30px;
        border-radius: 4px;
        margin-bottom: 40px;
        box-shadow: 0 0 25px rgba(0, 229, 255, 0.08), inset 0 0 20px rgba(0, 229, 255, 0.05);
        position: relative;
        overflow: hidden;
    }
    .cyber-score-board::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(180deg, rgba(0,229,255,0.02) 50%, rgba(0,0,0,0.2) 50%);
        background-size: 100% 4px;
        pointer-events: none;
    }
    
    /* CYBER BUTTON */
    .cyber-btn {
        background: linear-gradient(135deg, var(--vgts-accent) 0%, #00b8cc 100%);
        color: #000000 !important;
        border: 1px solid var(--vgts-accent);
        font-weight: 800;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 2px;
        padding: 14px 28px;
        cursor: pointer;
        position: relative;
        transition: all 0.3s ease;
        box-shadow: 0 0 10px rgba(0, 229, 255, 0.2);
        clip-path: polygon(10px 0, 100% 0, 100% calc(100% - 10px), calc(100% - 10px) 100%, 0 100%, 0 10px);
        display: inline-flex;
        align-items: center;
        gap: 10px;
    }
    .cyber-btn:hover {
        box-shadow: 0 0 20px var(--vgts-accent);
        background: #00e5ff;
        transform: translateY(-1px);
    }
    
    /* Lade-Animation */
    #vgt-loading {
        position: relative;
        border: 1px dashed rgba(0, 229, 255, 0.4);
        background: rgba(0, 229, 255, 0.03);
        border-radius: 4px;
        padding: 60px;
        text-align: center;
        margin-bottom: 30px;
        overflow: hidden;
    }
    .cyber-scanner-bar {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 4px;
        background: linear-gradient(180deg, transparent, var(--vgts-accent), transparent);
        opacity: 0.8;
        animation: scanline 2.5s linear infinite;
        pointer-events: none;
    }
    @keyframes scanline {
        0% { top: 0%; }
        50% { top: 100%; }
        100% { top: 0%; }
    }
    .vgt-spinner {
        width: 48px;
        height: 48px;
        border: 3px solid rgba(0, 229, 255, 0.1);
        border-top: 3px solid var(--vgts-accent);
        border-radius: 50%;
        margin: 0 auto 20px;
        animation: spin 0.8s linear infinite;
        filter: drop-shadow(0 0 8px var(--vgts-accent));
    }
    .vgt-loading-title { color: var(--vgts-accent); font-size: 16px; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 1px; font-weight: 800;}
    .vgt-loading-sub { color: var(--vgts-text-secondary); font-size: 12px; font-family: var(--vgts-font-mono); }
    
    /* Fehlerbox */
    #vgt-error-box {
        background: var(--vgts-danger-bg);
        border: 1px solid var(--vgts-danger);
        border-radius: 4px;
        padding: 20px;
        margin-bottom: 30px;
        color: var(--vgts-danger);
    }
    .vgt-error-content { display: flex; align-items: center; gap: 15px; font-size: 13px; font-weight: 700; }
    
    /* Score-System */
    .vgt-metric-label { font-size: 10px; text-transform: uppercase; color: var(--vgts-text-muted); letter-spacing: 1.5px; margin: 0 0 8px 0; font-weight: 700; }
    .vgt-score-value { font-size: 54px; font-weight: 800; color: #ffffff; line-height: 1; font-family: var(--vgts-font-mono); }
    .vgt-score-max { font-size: 20px; color: var(--vgts-text-muted); }
    .vgt-tier-label { font-size: 22px; font-weight: 800; letter-spacing: 1px; }
    .vgt-align-right { text-align: right; }
    
    /* Grid & Cards */
    .vgt-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 24px; margin-bottom: 40px; }
    @media (max-width: 1024px) { .vgt-grid { grid-template-columns: 1fr; } }
    .vgt-card { 
        position: relative;
        background: rgba(7, 10, 20, 0.5); 
        border: 1px solid var(--vgts-border); 
        border-radius: 4px; 
        padding: 24px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
        transition: all 0.3s ease;
    }
    .vgt-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 6px;
        height: 6px;
        border-top: 1.5px solid var(--vgts-accent);
        border-left: 1.5px solid var(--vgts-accent);
    }
    .vgt-card:hover {
        border-color: rgba(0, 229, 255, 0.25);
        box-shadow: 0 10px 30px rgba(0, 229, 255, 0.05);
    }
    .vgt-card-title { 
        font-size: 11px; 
        color: #ffffff; 
        font-weight: 800; 
        border-bottom: 1px solid rgba(255,255,255,0.08); 
        padding-bottom: 10px; 
        margin: 0 0 16px 0; 
        text-transform: uppercase; 
        letter-spacing: 1.5px; 
        font-family: var(--vgts-font-mono);
    }
    .vgt-vector-list { list-style: none; padding: 0; margin: 0; }
    .vgt-vector-item { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1px dashed rgba(255,255,255,0.05); padding: 10px 0; font-size: 11px; font-family: var(--vgts-font-mono); transition: all 0.2s ease; }
    .vgt-vector-item:last-child { border-bottom: none; }
    .vgt-vector-name { color: var(--vgts-text-secondary); }
    .vgt-status-safe { color: var(--vgts-success); font-weight: 700; text-shadow: 0 0 8px rgba(0,250,135,0.25); }
    .vgt-status-warn { color: var(--vgts-danger); font-weight: 700; text-shadow: 0 0 8px rgba(255,42,95,0.25); }
    .vgt-status-danger { color: var(--vgts-danger); font-weight: 700; text-shadow: 0 0 8px rgba(255,42,95,0.25); }
    
    /* Highlight open / unfulfilled checks in red */
    .vgt-vector-item.has-warning, .vgt-vector-item.has-danger {
        border-left: 2px solid var(--vgts-danger);
        padding-left: 8px;
        background: rgba(255, 42, 95, 0.04);
        border-bottom-color: rgba(255, 42, 95, 0.1);
    }
    .vgt-vector-item.has-warning .vgt-vector-name, .vgt-vector-item.has-danger .vgt-vector-name {
        color: #f1f5f9;
        font-weight: 600;
    }
    @keyframes spin { 100% { transform: rotate(360deg); } }
</style>

<!-- Secure Dynamic Rendering System -->
<script type="text/javascript" nonce="<?php echo esc_attr($csp_nonce); ?>">
    document.addEventListener('DOMContentLoaded', function() {
        const ajaxUrl = '<?php echo esc_js(admin_url('admin-ajax.php')); ?>';
        const actionNonce = '<?php echo esc_js($nonce); ?>';

        const startBtn = document.getElementById('vgt-start-btn');
        const introBox = document.getElementById('vgt-intro');
        const loadingBox = document.getElementById('vgt-loading');
        const loadingText = document.getElementById('vgt-loading-text');
        const resultsBox = document.getElementById('vgt-results');
        const errorBox = document.getElementById('vgt-error-box');
        const errorMessage = document.getElementById('vgt-error-message');
        const vectorsContainer = document.getElementById('vgt-vectors');

        const stepMessages = [
            "Analysiere WordPress Core-Signaturen...",
            "Prüfe Berechtigungsmatrix und API-Endpoints...",
            "Führe Path-Jail Sandbox Isolationstest aus...",
            "Überprüfe TLS/SSL und Security-Header...",
            "Analysiere PHP-Laufzeitumgebung..."
        ];

        startBtn.addEventListener('click', function() {
            introBox.classList.add('vgt-hidden');
            errorBox.classList.add('vgt-hidden');
            resultsBox.classList.add('vgt-hidden');
            loadingBox.classList.remove('vgt-hidden');

            let step = 0;
            const logSimulation = setInterval(() => {
                loadingText.textContent = stepMessages[step % stepMessages.length];
                step++;
            }, 1200);

            const formData = new FormData();
            formData.append('action', 'vgt_run_audit');
            formData.append('_ajax_nonce', actionNonce);

            fetch(ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Netzwerk-Antwort unvollständig oder ungültig.');
                }
                return response.json();
            })
            .then(res => {
                clearInterval(logSimulation);
                loadingBox.classList.add('vgt-hidden');

                if (res.success) {
                    renderResultsSafely(res.data);
                } else {
                    showError(res.data || 'Sicherheitsprüfung abgelehnt.');
                }
            })
            .catch(err => {
                clearInterval(logSimulation);
                loadingBox.classList.add('vgt-hidden');
                showError('Kritischer Kommunikationsfehler: ' + err.message);
            });
        });

        function showError(msg) {
            errorMessage.textContent = msg;
            errorBox.classList.remove('vgt-hidden');
            introBox.classList.remove('vgt-hidden');
        }

        // Absolut XSS-sicheres Rendering ohne unbereinigtes innerHTML
        function renderResultsSafely(data) {
            resultsBox.classList.remove('vgt-hidden');
            document.getElementById('vgt-score').textContent = data.ScorePercent;
            
            const tierLabel = document.getElementById('vgt-tier');
            tierLabel.textContent = data.TierName;

            // Reset active state of tier cards
            document.querySelectorAll('.vgt-tier-card').forEach(card => {
                card.classList.remove('active');
            });
            
            // Set active tier card
            const activeCard = document.getElementById('vgt-tier-card-' + data.AchievedTier);
            if (activeCard) {
                activeCard.classList.add('active');
            }

            // Farbliche Abstufung je nach Score
            if (data.ScorePercent >= 90) {
                tierLabel.style.color = 'var(--vgts-success)';
                tierLabel.style.textShadow = '0 0 10px rgba(0, 250, 154, 0.3)';
            } else if (data.ScorePercent >= 75) {
                tierLabel.style.color = 'var(--vgts-warning)';
                tierLabel.style.textShadow = 'none';
            } else {
                tierLabel.style.color = 'var(--vgts-danger)';
                tierLabel.style.textShadow = '0 0 10px rgba(255, 42, 95, 0.3)';
            }

            // Leere Container
            vectorsContainer.replaceChildren();

            const phaseNames = {
                "Phase1_Core": "1. Core & Versions-Integrität",
                "Phase2_Auth": "2. Authentifizierungs-Resilienz",
                "Phase3_Files": "3. Dateisystem-Isolierung",
                "Phase4_DB": "4. Datenbank-Absicherung",
                "Phase5_Headers": "5. Kryptografie & Header",
                "Phase6_Plugins": "6. Plugin Hygiene",
                "Phase7_SupplyChain": "7. Supply Chain & Code",
                "Phase8_Recon": "8. Exposure & OSINT Recon",
                "Phase9_Runtime": "9. Runtime & Execution"
            };

            for (const [phaseKey, checks] of Object.entries(data.SystemVectors)) {
                const card = document.createElement('div');
                card.className = 'vgt-card';

                const title = document.createElement('h4');
                title.className = 'vgt-card-title';
                title.textContent = phaseNames[phaseKey] || phaseKey;
                card.appendChild(title);

                const list = document.createElement('ul');
                list.className = 'vgt-vector-list';

                for (const [checkName, result] of Object.entries(checks)) {
                    const item = document.createElement('li');
                    item.className = 'vgt-vector-item';

                    const nameSpan = document.createElement('span');
                    nameSpan.className = 'vgt-vector-name';
                    // CamelCase in lesbaren Text konvertieren
                    nameSpan.textContent = checkName.replace(/([A-Z])/g, ' $1').trim();

                    const statusSpan = document.createElement('span');
                    statusSpan.textContent = result;

                    // Sicheres Zuweisen der CSS-Statusklasse
                    if (result.includes('[AKTIV') || result.includes('[SAFE') || result.includes('[GESCHÜTZT')) {
                        statusSpan.className = 'vgt-status-safe';
                    } else if (result.includes('[VULNERABLE') || result.includes('[GEFAHR')) {
                        statusSpan.className = 'vgt-status-danger';
                        item.classList.add('has-danger');
                    } else {
                        statusSpan.className = 'vgt-status-warn';
                        item.classList.add('has-warning');
                    }

                    item.appendChild(nameSpan);
                    item.appendChild(statusSpan);
                    list.appendChild(item);
                }

                card.appendChild(list);
                vectorsContainer.appendChild(card);
            }
        }
    });
</script>
