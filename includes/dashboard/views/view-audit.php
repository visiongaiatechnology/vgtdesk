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
    <div class="vgts-card" style="margin-bottom: 25px; padding: 30px; border-left: 4px solid var(--vgts-accent);">
        <div style="display: flex; align-items: center; gap: 20px;">
            <div style="font-size: 36px; line-height: 1; filter: drop-shadow(0 0 10px var(--vgts-accent-glow));">💠</div>
            <div>
                <h2 style="margin: 0; font-size: 22px; font-weight: 800; color: #ffffff; letter-spacing: 0.5px;">VGT SENTINEL AUDITOR</h2>
                <p style="margin: 5px 0 0 0; font-size: 12px; color: var(--vgts-text-secondary); font-family: var(--vgts-font-mono);">WordPress Architecture Deep Scan v2.2 — DIAMANT SUPREME</p>
            </div>
        </div>
    </div>

    <!-- Einleitungs-Bereich -->
    <div id="vgt-intro" class="vgts-card" style="margin-bottom: 25px;">
        <h3 style="margin: 0 0 15px 0; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; color: #fff;">Zero-Trust Penetration-Test</h3>
        <p style="color: var(--vgts-text-secondary); line-height: 1.7; font-size: 13px; margin-bottom: 25px;">
            Dieser Deep-Scanner analysiert die kritische Sicherheitsarchitektur Ihrer WordPress-Installation. Er führt Isolationstests des Dateisystems durch, überprüft Zugriffsrechte und validiert kryptografische Header nach dem Zero-Trust-Prinzip. Sentinel-Schutzkonfigurationen werden dabei automatisch einbezogen und als Schutzschilde bewertet.
        </p>
        <button id="vgt-start-btn" class="vgts-btn vgts-btn-primary" style="background: linear-gradient(135deg, var(--vgts-accent) 0%, #00b8cc 100%); color: #000; border: none; font-weight: 800; font-size: 13px; text-transform: uppercase; letter-spacing: 1px; padding: 14px 28px; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; gap: 10px; transition: all 0.25s ease;">
            <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round" style="display: block;">
                <circle cx="12" cy="12" r="10"></circle>
                <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                <path d="M2 12h20"></path>
            </svg>
            System-Audit Starten
        </button>
    </div>

    <!-- Lade-Indikator -->
    <div id="vgt-loading" class="vgt-hidden">
        <div class="vgt-spinner-box">
            <div class="vgt-spinner"></div>
            <h3 class="vgt-loading-title">Führe Penetration-Tests aus...</h3>
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
        <div class="vgt-score-board">
            <div>
                <p class="vgt-metric-label">Hardening Index</p>
                <div class="vgt-score-value"><span id="vgt-score">0</span><span class="vgt-score-max">/100</span></div>
            </div>
            <div class="vgt-align-right">
                <p class="vgt-metric-label">Sicherheits-Status</p>
                <div id="vgt-tier" class="vgt-tier-label">CALCULATING...</div>
            </div>
        </div>

        <h3 style="font-size: 15px; color: #ffffff; text-transform: uppercase; border-bottom: 1px solid var(--vgts-border); padding-bottom: 12px; margin-bottom: 24px; letter-spacing: 1px; font-weight: 800;">Audit Vektoren</h3>
        <div id="vgt-vectors" class="vgt-grid">
            <!-- Wird via JavaScript sicher und XSS-geschützt aufgebaut -->
        </div>
    </div>
</div>

<!-- High-Fidelity Custom Styles for Auditor -->
<style nonce="<?php echo esc_attr($csp_nonce); ?>">
    .vgt-hidden { display: none !important; }
    #vgt-start-btn:hover {
        box-shadow: 0 0 20px var(--vgts-accent-glow);
        transform: translateY(-1px);
    }
    /* Lade-Animation */
    #vgt-loading {
        border: 1px dashed rgba(0, 229, 255, 0.3);
        background: rgba(0, 229, 255, 0.02);
        border-radius: 12px;
        padding: 60px;
        text-align: center;
        margin-bottom: 30px;
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
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 30px;
        color: var(--vgts-danger);
    }
    .vgt-error-content { display: flex; align-items: center; gap: 15px; font-size: 13px; font-weight: 700; }
    /* Score-System */
    .vgt-score-board {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: rgba(7, 9, 19, 0.6);
        border: 1px solid var(--vgts-border);
        padding: 30px;
        border-radius: 12px;
        margin-bottom: 40px;
        box-shadow: inset 0 0 20px rgba(0, 229, 255, 0.02);
        backdrop-filter: blur(10px);
    }
    .vgt-metric-label { font-size: 10px; text-transform: uppercase; color: var(--vgts-text-muted); letter-spacing: 1.5px; margin: 0 0 8px 0; font-weight: 700; }
    .vgt-score-value { font-size: 54px; font-weight: 800; color: #ffffff; line-height: 1; font-family: var(--vgts-font-mono); }
    .vgt-score-max { font-size: 20px; color: var(--vgts-text-muted); }
    .vgt-tier-label { font-size: 22px; font-weight: 800; letter-spacing: 1px; }
    .vgt-align-right { text-align: right; }
    /* Grid & Cards */
    .vgt-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 24px; margin-bottom: 40px; }
    @media (max-width: 1024px) { .vgt-grid { grid-template-columns: 1fr; } }
    .vgt-card { 
        background: var(--vgts-bg-card); 
        border: 1px solid var(--vgts-border); 
        border-radius: 12px; 
        padding: 24px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
    }
    .vgt-card-title { 
        font-size: 12px; 
        color: #ffffff; 
        font-weight: 800; 
        border-bottom: 1px solid rgba(255,255,255,0.08); 
        padding-bottom: 10px; 
        margin: 0 0 16px 0; 
        text-transform: uppercase; 
        letter-spacing: 1px; 
    }
    .vgt-vector-list { list-style: none; padding: 0; margin: 0; }
    .vgt-vector-item { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1px dashed rgba(255,255,255,0.05); padding: 10px 0; font-size: 12px; font-family: var(--vgts-font-mono); }
    .vgt-vector-item:last-child { border-bottom: none; }
    .vgt-vector-name { color: var(--vgts-text-secondary); }
    .vgt-status-safe { color: var(--vgts-success); font-weight: 700; text-shadow: 0 0 8px rgba(0,250,135,0.15); }
    .vgt-status-warn { color: var(--vgts-warning); font-weight: 700; }
    .vgt-status-danger { color: var(--vgts-danger); font-weight: 700; text-shadow: 0 0 8px rgba(255,42,95,0.15); }
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
                    } else {
                        statusSpan.className = 'vgt-status-warn';
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
