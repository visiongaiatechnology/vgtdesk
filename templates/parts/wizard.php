<?php
/**
 * Template part: Setup Wizard
 * STATUS: 💠 DIAMANT VGT SUPREME
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}
?>
    <!-- FIRST RUN ONBOARDING WIZARD -->
    <div id="vgt-first-run-wizard" class="vgt-wizard-overlay hidden">
        <div class="vgt-wizard-card glassmorphism">
            
            <div class="vgt-wizard-title-group">
                <span class="vgt-wizard-subtitle">Systemkonfiguration</span>
                <h2 class="vgt-wizard-main-title">VisionGaia Setup-Assistent</h2>
            </div>

            <!-- Stepper Steps indicators -->
            <div class="vgt-wizard-steps">
                <div class="vgt-wizard-step-indicator active" data-step="1">
                    <span class="vgt-wizard-step-indicator-number">1</span>
                    <span>Layout</span>
                </div>
                <div class="vgt-wizard-step-indicator" data-step="2">
                    <span class="vgt-wizard-step-indicator-number">2</span>
                    <span>Farbe</span>
                </div>
                <div class="vgt-wizard-step-indicator" data-step="3">
                    <span class="vgt-wizard-step-indicator-number">3</span>
                    <span>Superkey</span>
                </div>
                <div class="vgt-wizard-step-indicator" data-step="4">
                    <span class="vgt-wizard-step-indicator-number">4</span>
                    <span>Sentinel</span>
                </div>
                <?php if (!defined('VIS_VERSION')): ?>
                <div class="vgt-wizard-step-indicator" data-step="5">
                    <span class="vgt-wizard-step-indicator-number">5</span>
                    <span>Dattrack</span>
                </div>
                <?php endif; ?>
                <div class="vgt-wizard-step-indicator" data-step="<?php echo defined('VIS_VERSION') ? 5 : 6; ?>">
                    <span class="vgt-wizard-step-indicator-number"><?php echo defined('VIS_VERSION') ? 5 : 6; ?></span>
                    <span>Redirect</span>
                </div>
                <div class="vgt-wizard-step-indicator" data-step="<?php echo defined('VIS_VERSION') ? 6 : 7; ?>">
                    <span class="vgt-wizard-step-indicator-number"><?php echo defined('VIS_VERSION') ? 6 : 7; ?></span>
                    <span>Fertig</span>
                </div>
            </div>

            <!-- Wizard step contents -->
            <div class="vgt-wizard-body">
                
                <!-- Step 1: Layout Selection -->
                <div class="vgt-wizard-step-content active" id="vgt-wizard-step-1">
                    <h3 class="vgt-wizard-step-title">Arbeitsbereich-Layout auswählen</h3>
                    <p class="vgt-wizard-step-desc">Wähle deinen bevorzugten Design- und Steuerungs-Stil. Du kannst dies jederzeit in den Einstellungen ändern.</p>
                    <div class="vgt-wizard-layout-grid">
                        <div class="vgt-wizard-layout-card active" data-layout="macos" onclick="VGTDeskEngine.selectWizardLayout('macos')">
                            <div class="vgt-wizard-layout-preview macos-preview">
                                <div class="preview-bar-top"></div>
                                <div class="preview-dock-bottom"></div>
                            </div>
                            <span class="vgt-wizard-layout-label">🍎 macOS Style</span>
                        </div>
                        <div class="vgt-wizard-layout-card" data-layout="windows" onclick="VGTDeskEngine.selectWizardLayout('windows')">
                            <div class="vgt-wizard-layout-preview windows-preview">
                                <div class="preview-bar-bottom"></div>
                            </div>
                            <span class="vgt-wizard-layout-label">🪟 Windows Style</span>
                        </div>
                        <div class="vgt-wizard-layout-card" data-layout="linux" onclick="VGTDeskEngine.selectWizardLayout('linux')">
                            <div class="vgt-wizard-layout-preview linux-preview">
                                <div class="preview-bar-top"></div>
                                <div class="preview-dock-left"></div>
                            </div>
                            <span class="vgt-wizard-layout-label">🐧 Linux Style</span>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Accent color -->
                <div class="vgt-wizard-step-content" id="vgt-wizard-step-2">
                    <h3 class="vgt-wizard-step-title">Akzentfarbe auswählen</h3>
                    <p class="vgt-wizard-step-desc">Passe die Hauptakzentfarbe für Schalter, Fensterrahmen, Lichter und Indicators an.</p>
                    <div class="vgt-wizard-color-palette">
                        <div class="vgt-wizard-color-item active" data-color="indigo" onclick="VGTDeskEngine.selectWizardColor('indigo')">
                            <span class="vgt-wizard-color-dot bg-indigo"></span>
                            <span class="vgt-wizard-color-label">Indigo</span>
                        </div>
                        <div class="vgt-wizard-color-item" data-color="emerald" onclick="VGTDeskEngine.selectWizardColor('emerald')">
                            <span class="vgt-wizard-color-dot bg-emerald"></span>
                            <span class="vgt-wizard-color-label">Emerald</span>
                        </div>
                        <div class="vgt-wizard-color-item" data-color="cyan" onclick="VGTDeskEngine.selectWizardColor('cyan')">
                            <span class="vgt-wizard-color-dot bg-cyan"></span>
                            <span class="vgt-wizard-color-label">Cyan</span>
                        </div>
                        <div class="vgt-wizard-color-item" data-color="amber" onclick="VGTDeskEngine.selectWizardColor('amber')">
                            <span class="vgt-wizard-color-dot bg-amber"></span>
                            <span class="vgt-wizard-color-label">Amber</span>
                        </div>
                        <div class="vgt-wizard-color-item" data-color="rose" onclick="VGTDeskEngine.selectWizardColor('rose')">
                            <span class="vgt-wizard-color-dot bg-rose"></span>
                            <span class="vgt-wizard-color-label">Rose</span>
                        </div>
                        <div class="vgt-wizard-color-item" data-color="gold" onclick="VGTDeskEngine.selectWizardColor('gold')">
                            <span class="vgt-wizard-color-dot bg-gold" style="box-shadow: 0 0 6px #ffd700;"></span>
                            <span class="vgt-wizard-color-label">✨ Gold</span>
                        </div>
                        <div class="vgt-wizard-color-item" data-color="purple" onclick="VGTDeskEngine.selectWizardColor('purple')">
                            <span class="vgt-wizard-color-dot bg-purple"></span>
                            <span class="vgt-wizard-color-label">Purple</span>
                        </div>
                        <div class="vgt-wizard-color-item" data-color="violet" onclick="VGTDeskEngine.selectWizardColor('violet')">
                            <span class="vgt-wizard-color-dot bg-violet"></span>
                            <span class="vgt-wizard-color-label">Violet</span>
                        </div>
                        <div class="vgt-wizard-color-item" data-color="neon" onclick="VGTDeskEngine.selectWizardColor('neon')">
                            <span class="vgt-wizard-color-dot bg-neon"></span>
                            <span class="vgt-wizard-color-label">Neon</span>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Superkey -->
                <div class="vgt-wizard-step-content" id="vgt-wizard-step-3">
                    <h3 class="vgt-wizard-step-title">Throne Guard Superkey festlegen</h3>
                    <p class="vgt-wizard-step-desc">Der Superkey sichert administrative Kernel-Eingriffe und Uploads ab. Du kannst diesen Schritt überspringen und den Standardkey behalten.</p>
                    <div class="vgt-wizard-superkey-container">
                        <!-- Current Superkey row (shown only if active) -->
                        <div id="vgt-wizard-current-key-row" class="vgt-cc-input-row hidden" style="margin-bottom: 12px;">
                            <label class="vgt-cc-input-label">Aktueller Superkey</label>
                            <div class="vgt-wizard-input-wrapper">
                                <input type="password" id="vgt-wizard-superkey-current" class="vgt-input-text" placeholder="Aktuellen Superkey eingeben...">
                            </div>
                        </div>
                        <div class="vgt-cc-input-row" style="margin-bottom: 12px;">
                            <label class="vgt-cc-input-label">Neuer Superkey (mindestens 12 Zeichen)</label>
                            <div class="vgt-wizard-input-wrapper">
                                <input type="password" id="vgt-wizard-superkey-input" class="vgt-input-text" placeholder="Neuen Superkey eingeben..." oninput="VGTDeskEngine.updateWizardSuperkeyStrength(this.value)">
                                <span class="vgt-wizard-eye-toggle" onclick="VGTDeskEngine.toggleWizardPassword()">👁️</span>
                            </div>
                        </div>
                        <div class="vgt-wizard-strength-meter">
                            <div id="vgt-wizard-strength-bar" class="vgt-wizard-strength-bar"></div>
                        </div>
                        <div id="vgt-wizard-strength-label" class="vgt-wizard-strength-label">Kein Key eingegeben</div>
                    </div>
                </div>

                <!-- Step 4: Sentinel -->
                <div class="vgt-wizard-step-content" id="vgt-wizard-step-4">
                    <h3 class="vgt-wizard-step-title">Sentinel Web Application Firewall</h3>
                    <p class="vgt-wizard-step-desc">Sentinel schützt deine WordPress-Instanz in Echtzeit vor Cyberangriffen, Injektionen und Privilege Escalations.</p>
                    <div class="vgt-wizard-toggle-card">
                        <div class="vgt-wizard-toggle-info">
                            <span class="vgt-wizard-toggle-title">Sentinel Firewall aktivieren</span>
                            <span class="vgt-wizard-toggle-desc">Echtzeit-Scans von Anfragen aktivieren und schädliche Zugriffe blockieren.</span>
                        </div>
                        <input type="checkbox" checked class="vgt-toggle-switch" id="vgt-wizard-sentinel-toggle">
                    </div>
                    <div class="vgt-wizard-benefits-list">
                        <div class="vgt-wizard-benefit-item">
                            <span class="vgt-wizard-benefit-bullet">✓</span>
                            <span>Schutz vor SQL-Injektionen, XSS-Lücken und RCE-Exploits</span>
                        </div>
                        <div class="vgt-wizard-benefit-item">
                            <span class="vgt-wizard-benefit-bullet">✓</span>
                            <span>Automatischer IP-Bann bei Brute-Force oder Angriffsversuchen</span>
                        </div>
                        <div class="vgt-wizard-benefit-item">
                            <span class="vgt-wizard-benefit-bullet">✓</span>
                            <span>Keine Latenzverzögerung dank Zero-Overheat Pattern matching</span>
                        </div>
                    </div>
                    <!-- Sentinel WAF Info/Warning Alerts -->
                    <div class="vgt-wizard-notice warning" style="margin-top: 15px; padding: 12px; background: rgba(239, 68, 68, 0.1); border-left: 3px solid #ef4444; border-radius: 6px; font-size: 12px; color: #fecaca; line-height: 1.4;">
                        <strong style="color: #fca5a5; display: block; margin-bottom: 4px; font-size: 13px;">⚠️ WICHTIGER HINWEIS:</strong>
                        <p style="margin: 0 0 6px 0;">
                            <strong>Sentinel CE sperrt auch Administratoren</strong>, wenn diese im Dateimanager oder Theme-Editor sensible Dateien manipulieren. Trage deine IP-Adresse nach der Einrichtung unbedingt in die <strong>IP-Whitelist</strong> ein (unter <em>Sentinel -> AEGIS</em>).
                        </p>
                        <p style="margin: 0;">
                            Beim Hochladen von Bildern oder Videos kann das WAF-Modul <strong>Airlock</strong> Fehlalarme (False Positives) auslösen. Deaktiviere Airlock temporär unter <em>Sentinel -> Airlock</em>, falls Uploads blockiert werden.
                        </p>
                    </div>
                </div>

                <?php if (!defined('VIS_VERSION')): ?>
                <!-- Step 5: Dattrack Telemetry -->
                <div class="vgt-wizard-step-content" id="vgt-wizard-step-5">
                    <h3 class="vgt-wizard-step-title">Dattrack Sovereign Analytics (Opt-In)</h3>
                    <p class="vgt-wizard-step-desc">Möchten Sie die souveräne, DSGVO-konforme Telemetrie- und Leistungsmessung aktivieren? Ihre Daten werden Ende-zu-Ende verschlüsselt gespeichert.</p>
                    <div class="vgt-wizard-toggle-card">
                        <div class="vgt-wizard-toggle-info">
                            <span class="vgt-wizard-toggle-title">Dattrack aktivieren</span>
                            <span class="vgt-wizard-toggle-desc">Erfasst anonyme Leistungsstatistiken zur Systemoptimierung.</span>
                        </div>
                        <input type="checkbox" class="vgt-toggle-switch" id="vgt-wizard-dattrack-toggle">
                    </div>
                    <div class="vgt-wizard-benefits-list">
                        <div class="vgt-wizard-benefit-item">
                            <span class="vgt-wizard-benefit-bullet">✓</span>
                            <span>100% DSGVO-konform ohne Cookies</span>
                        </div>
                        <div class="vgt-wizard-benefit-item">
                            <span class="vgt-wizard-benefit-bullet">✓</span>
                            <span>Kryptographische Absicherung (Aegis-256-GCM)</span>
                        </div>
                        <div class="vgt-wizard-benefit-item">
                            <span class="vgt-wizard-benefit-bullet">✓</span>
                            <span>Echtzeit-Performance-Messer für PHP & Server</span>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Step 6: Auto Redirect -->
                <div class="vgt-wizard-step-content" id="vgt-wizard-step-<?php echo defined('VIS_VERSION') ? 5 : 6; ?>">
                    <h3 class="vgt-wizard-step-title">Desktop-Standard-Ansicht (Redirect)</h3>
                    <p class="vgt-wizard-step-desc">Konfiguriere, ob du bei jedem administrativen Aufruf des Backends direkt in den WP-Desk weitergeleitet wirst.</p>
                    <div class="vgt-wizard-toggle-card">
                        <div class="vgt-wizard-toggle-info">
                            <span class="vgt-wizard-toggle-title">Direkte Weiterleitung aktivieren (Auto-Redirect)</span>
                            <span class="vgt-wizard-toggle-desc">Leitet Standard-Dashboard-Links automatisch auf die Desktop-Oberfläche um. Du kannst den Desktop im Kontrollzentrum umgehen.</span>
                        </div>
                        <input type="checkbox" class="vgt-toggle-switch" id="vgt-wizard-redirect-toggle">
                    </div>
                </div>

                <!-- Step 7: Completion Success -->
                <div class="vgt-wizard-step-content" id="vgt-wizard-step-<?php echo defined('VIS_VERSION') ? 6 : 7; ?>">
                    <div class="vgt-wizard-success-layout">
                        <span class="vgt-wizard-success-shield">🛡️</span>
                        <h3 class="vgt-wizard-step-title" style="font-size: 18px;">Setup erfolgreich abgeschlossen!</h3>
                        <p class="vgt-wizard-step-desc" style="max-width: 80%;">Dein VisionGaia Desktop wurde konfiguriert und gehärtet. Hier ist eine Zusammenfassung deiner Einstellungen:</p>
                        
                        <div class="vgt-wizard-summary-box">
                            <div class="vgt-wizard-summary-item">
                                <span>Layout:</span>
                                <span id="vgt-summary-layout">macOS</span>
                            </div>
                            <div class="vgt-wizard-summary-item">
                                <span>Akzentfarbe:</span>
                                <span id="vgt-summary-color">Indigo</span>
                            </div>
                            <div class="vgt-wizard-summary-item">
                                <span>Throne Guard Superkey:</span>
                                <span id="vgt-summary-superkey">Unverändert</span>
                            </div>
                            <div class="vgt-wizard-summary-item">
                                <span>Sentinel WAF:</span>
                                <span id="vgt-summary-sentinel">Aktiviert</span>
                            </div>
                            <?php if (!defined('VIS_VERSION')): ?>
                            <div class="vgt-wizard-summary-item">
                                <span>Dattrack Analytics:</span>
                                <span id="vgt-summary-dattrack">Deaktiviert</span>
                            </div>
                            <?php endif; ?>
                            <div class="vgt-wizard-summary-item">
                                <span>Auto-Redirect:</span>
                                <span id="vgt-summary-redirect">Inaktiv</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Wizard Footer Controls -->
            <div class="vgt-wizard-footer">
                <button id="vgt-wizard-prev" class="vgt-btn-secondary hidden" onclick="VGTDeskEngine.prevWizardStep()">Zurück</button>
                <button id="vgt-wizard-skip" class="vgt-wizard-skip-link" onclick="VGTDeskEngine.skipWizardSuperkey()">Superkey überspringen</button>
                <button id="vgt-wizard-next" class="vgt-btn-primary" onclick="VGTDeskEngine.nextWizardStep()">Weiter</button>
            </div>

        </div>
    </div>
