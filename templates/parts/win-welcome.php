<?php
/**
 * Template part: Welcome Window
 * STATUS: 💠 DIAMANT VGT SUPREME
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}
?>
<?php
$show_welcome = !isset($user_settings['show_welcome_on_startup']) || $user_settings['show_welcome_on_startup'] !== 'false';
?>
                <!-- NATIVES WILLKOMMENS-FENSTER -->
                <div id="win-welcome" class="window <?php echo $show_welcome ? '' : 'hidden'; ?> absolute vgt-window" style="width: 760px; height: 660px; top: 6%; left: 18%; z-index: 100;" onclick="VGTDeskEngine.focusWindow('welcome')">
                    
                    <!-- 8 Resize Handles -->
                    <div class="resize-handle resize-handle-n" onmousedown="VGTDeskEngine.startResize(event, 'welcome', 'n')"></div>
                    <div class="resize-handle resize-handle-s" onmousedown="VGTDeskEngine.startResize(event, 'welcome', 's')"></div>
                    <div class="resize-handle resize-handle-e" onmousedown="VGTDeskEngine.startResize(event, 'welcome', 'e')"></div>
                    <div class="resize-handle resize-handle-w" onmousedown="VGTDeskEngine.startResize(event, 'welcome', 'w')"></div>
                    <div class="resize-handle resize-handle-nw" onmousedown="VGTDeskEngine.startResize(event, 'welcome', 'nw')"></div>
                    <div class="resize-handle resize-handle-ne" onmousedown="VGTDeskEngine.startResize(event, 'welcome', 'ne')"></div>
                    <div class="resize-handle resize-handle-sw" onmousedown="VGTDeskEngine.startResize(event, 'welcome', 'sw')"></div>
                    <div class="resize-handle resize-handle-se" onmousedown="VGTDeskEngine.startResize(event, 'welcome', 'se')"></div>

                    <!-- Titlebar -->
                    <div class="vgt-window-header cursor-move window-header">
                        <div class="vgt-window-dots">
                            <span class="vgt-window-dot dot-rose" onclick="VGTDeskEngine.closeWindow('welcome')"></span>
                            <span class="vgt-window-dot dot-amber" onclick="VGTDeskEngine.minimizeWindow('welcome')"></span>
                            <span class="vgt-window-dot dot-emerald" onclick="VGTDeskEngine.maximizeWindow('welcome')"></span>
                        </div>
                        <span class="vgt-window-title" id="welcome-title-accent"><?php echo esc_html__('Willkommen bei VGT WP-Desk — V1.0 Beta v4', 'vgtdesk'); ?></span>
                        <div class="vgt-window-spacer"></div>
                    </div>
                    
                    <!-- Body Content -->
                    <div class="vgt-window-body" style="padding: 0; background: #070a13; display: flex; flex-direction: column;">
                        
                        <style nonce="<?php echo function_exists('vgt_get_csp_nonce') ? esc_attr(vgt_get_csp_nonce()) : ''; ?>">
                            .vgt-welcome-hero {
                                background: linear-gradient(135deg, rgba(var(--vgt-accent-rgb), 0.12) 0%, rgba(6, 182, 212, 0.05) 100%);
                                border-bottom: 1px solid rgba(255, 255, 255, 0.05);
                                padding: 25px 30px;
                                text-align: center;
                                position: relative;
                                overflow: hidden;
                            }
                            .vgt-welcome-hero::after {
                                content: '';
                                position: absolute;
                                bottom: -50px;
                                left: 50%;
                                transform: translateX(-50%);
                                width: 220px;
                                height: 100px;
                                background: radial-gradient(circle, var(--vgt-accent-rgba15) 0%, transparent 70%);
                                pointer-events: none;
                            }
                            .vgt-v4-badge {
                                display: inline-block;
                                background: linear-gradient(135deg, var(--vgt-accent-color) 0%, #06b6d4 100%);
                                color: #ffffff;
                                font-size: 10px;
                                font-weight: 800;
                                text-transform: uppercase;
                                padding: 4px 12px;
                                border-radius: 50px;
                                letter-spacing: 1.5px;
                                margin-bottom: 10px;
                                box-shadow: 0 0 15px var(--vgt-accent-rgba15);
                            }
                            .vgt-welcome-h2 {
                                font-size: 22px;
                                font-weight: 800;
                                color: #ffffff;
                                margin: 0 0 6px 0;
                                letter-spacing: -0.025em;
                            }
                            .vgt-welcome-p {
                                font-size: 13px;
                                color: #94a3b8;
                                max-width: 580px;
                                margin: 0 auto;
                                line-height: 1.45;
                            }
                            .vgt-welcome-content {
                                padding: 20px 25px;
                                overflow-y: auto;
                                flex: 1;
                            }
                            .vgt-welcome-columns {
                                display: flex;
                                gap: 20px;
                                margin-bottom: 20px;
                            }
                            .vgt-column-left {
                                flex: 1.1;
                                display: flex;
                                flex-direction: column;
                                gap: 12px;
                            }
                            .vgt-column-right {
                                flex: 1.4;
                                display: flex;
                                flex-direction: column;
                                gap: 10px;
                            }
                            .vgt-welcome-card {
                                background: rgba(15, 23, 42, 0.4);
                                border: 1px solid rgba(255, 255, 255, 0.04);
                                border-radius: 12px;
                                padding: 16px;
                                transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1);
                            }
                            .vgt-welcome-card:hover {
                                border-color: rgba(255, 255, 255, 0.08);
                                transform: translateY(-1px);
                                background: rgba(15, 23, 42, 0.55);
                                box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
                            }
                            .vgt-welcome-card.vgt-feat-active {
                                border-color: rgba(var(--vgt-accent-rgb), 0.25);
                                background: rgba(15, 23, 42, 0.5);
                                box-shadow: inset 0 0 12px rgba(var(--vgt-accent-rgb), 0.05);
                            }
                            .vgt-feat-card {
                                background: rgba(15, 23, 42, 0.4);
                                border: 1px solid rgba(255, 255, 255, 0.04);
                                border-radius: 12px;
                                padding: 12px 14px;
                                transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1);
                            }
                            .vgt-feat-card:hover {
                                border-color: rgba(255, 255, 255, 0.08);
                                transform: translateY(-1px);
                                background: rgba(15, 23, 42, 0.55);
                                box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
                            }
                            .vgt-feat-card.vgt-feat-active {
                                border-color: rgba(var(--vgt-accent-rgb), 0.2);
                            }
                            .vgt-feat-header {
                                display: flex;
                                align-items: center;
                                gap: 10px;
                                margin-bottom: 5px;
                            }
                            .vgt-feat-icon {
                                font-size: 16px;
                            }
                            .vgt-feat-title {
                                font-size: 13px;
                                font-weight: 700;
                                color: #f1f5f9;
                            }
                            .vgt-feat-desc {
                                font-size: 11.5px;
                                color: #94a3b8;
                                line-height: 1.4;
                                margin: 0;
                            }
                            .vgt-welcome-btn-primary {
                                box-sizing: border-box !important;
                                background: linear-gradient(135deg, var(--vgt-accent-color) 0%, #06b6d4 100%);
                                color: #ffffff !important;
                                border: none;
                                padding: 10px 18px;
                                border-radius: 8px;
                                font-weight: 700;
                                font-size: 12px;
                                cursor: pointer;
                                text-decoration: none;
                                display: inline-flex;
                                align-items: center;
                                gap: 8px;
                                transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1);
                                box-shadow: 0 4px 12px var(--vgt-accent-rgba15);
                            }
                            .vgt-welcome-btn-primary:hover {
                                transform: translateY(-1px);
                                box-shadow: 0 8px 18px var(--vgt-accent-rgba8), 0 0 10px var(--vgt-accent-rgba15);
                                opacity: 0.98;
                            }
                            .vgt-welcome-btn-secondary {
                                box-sizing: border-box !important;
                                background: rgba(255, 255, 255, 0.03);
                                color: #cbd5e1 !important;
                                border: 1px solid rgba(255, 255, 255, 0.06);
                                padding: 10px 18px;
                                border-radius: 8px;
                                font-weight: 600;
                                font-size: 12px;
                                cursor: pointer;
                                text-decoration: none;
                                display: inline-flex;
                                align-items: center;
                                gap: 8px;
                                transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1);
                            }
                            .vgt-welcome-btn-secondary:hover {
                                background-color: rgba(255, 255, 255, 0.08);
                                border-color: rgba(255, 255, 255, 0.15);
                                color: #ffffff !important;
                            }
                        </style>

                        <!-- Hero Header -->
                        <div class="vgt-welcome-hero">
                            <span class="vgt-v4-badge">VGT WP-Desk V4 Stable</span>
                            <h2 class="vgt-welcome-h2">Das Betriebssystem für Ihr WordPress 🚀</h2>
                            <p class="vgt-welcome-p">
                                Willkommen im ultimativen Desktop-Workspace. Version 4 bringt bahnbrechende Upgrades in Stabilität, Hardening und Interface-Management — nativ, superschnell und ohne Overhead.
                            </p>
                        </div>

                        <!-- Main Content Scroll Area -->
                        <div class="vgt-welcome-content">
                            
                            <!-- Two Column Layout -->
                            <div class="vgt-welcome-columns">
                                <!-- Left Column: Quick Actions & Checklist -->
                                <div class="vgt-column-left">
                                    <div class="vgt-welcome-card vgt-feat-active">
                                        <h3 style="color: #ffffff; font-size: 14px; font-weight: 700; margin: 0 0 8px 0; display: flex; align-items: center; gap: 8px;">
                                            <span>🎯</span> Quick Start
                                        </h3>
                                        <p style="color: #94a3b8; font-size: 12px; line-height: 1.45; margin: 0 0 12px 0;">
                                            Richten Sie Ihr System in wenigen Klicks ein oder verwalten Sie Ihre WAF-Regeln im Sicherheits-Center.
                                        </p>
                                        <div style="display: flex; flex-direction: column; gap: 8px;">
                                            <button onclick="VGTDeskEngine.closeWindow('welcome'); VGTDeskEngine.startFirstRunWizard();" class="vgt-welcome-btn-primary" style="justify-content: center; width: 100%;">
                                                <span>Setup-Wizard starten 🚀</span>
                                            </button>
                                            <button onclick="VGTDeskEngine.closeWindow('welcome'); VGTDeskEngine.openWindow('vgt-security-center');" class="vgt-welcome-btn-secondary" style="justify-content: center; width: 100%;">
                                                <span>Sicherheits-Center öffnen 🛡️</span>
                                            </button>
                                            <button onclick="VGTDeskEngine.closeWindow('welcome'); VGTDeskEngine.openWindow('settings');" class="vgt-welcome-btn-secondary" style="justify-content: center; width: 100%;">
                                                <span>System konfigurieren ⚙️</span>
                                            </button>
                                            <button onclick="VGTDeskEngine.saveUserSetting('show_welcome_on_startup', 'false'); VGTDeskEngine.closeWindow('welcome');" class="vgt-welcome-btn-secondary" style="justify-content: center; width: 100%; border-color: rgba(244, 63, 94, 0.2); color: #fb7185 !important;">
                                                <span>Nicht mehr anzeigen ❌</span>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="vgt-welcome-card">
                                        <h4 style="color: #cbd5e1; font-size: 11px; font-weight: 700; margin: 0 0 8px 0; text-transform: uppercase; letter-spacing: 0.5px;">Aktivierte Module</h4>
                                        <ul style="margin: 0; padding: 0 0 0 15px; font-size: 11.5px; color: #94a3b8; line-height: 1.6; list-style-type: disc;">
                                            <li>Sentinel CE v1.7.0 <span style="color: #10b981; font-weight: 600;">(Aktiv)</span></li>
                                            <li>Throne Guard v2.6.0 <span style="color: #10b981; font-weight: 600;">(Gesichert)</span></li>
                                            <li>Dattrack Telemetry <span style="color: #06b6d4; font-weight: 600;">(DSGVO-safe)</span></li>
                                            <li>Aegis Firewall V4.0 <span style="color: #10b981; font-weight: 600;">(Aktiv)</span></li>
                                        </ul>
                                    </div>
                                </div>

                                <!-- Right Column: V4 Highlights -->
                                <div class="vgt-column-right">
                                    <h3 style="color: #ffffff; font-size: 14px; font-weight: 700; margin: 0 0 4px 0; display: flex; align-items: center; gap: 8px;">
                                        <span>✨</span> Was ist neu in V4?
                                    </h3>
                                    
                                    <div class="vgt-feat-card">
                                        <div class="vgt-feat-header">
                                            <span class="vgt-feat-icon">🖥️</span>
                                            <span class="vgt-feat-title">Aero Snap Layouts</span>
                                        </div>
                                        <p class="vgt-feat-desc">Ziehen Sie Fenster an den Bildschirmrand zum schnellen Andocken, oder nutzen Sie das neue Snap-Menü durch Hovern über den grünen Maximieren-Button.</p>
                                    </div>

                                    <div class="vgt-feat-card">
                                        <div class="vgt-feat-header">
                                            <span class="vgt-feat-icon">⚡</span>
                                            <span class="vgt-feat-title">Zero-Overheat & RAM-Safe</span>
                                        </div>
                                        <p class="vgt-feat-desc">Intelligente RAM-Hibernation versetzt inaktive Tabs/Iframes in den Ruhezustand und spart Arbeitsspeicher. 100% nativer, frameworkfreier Code.</p>
                                    </div>

                                    <div class="vgt-feat-card">
                                        <div class="vgt-feat-header">
                                            <span class="vgt-feat-icon">📊</span>
                                            <span class="vgt-feat-title">Gehärtetes Dattrack-Tracking</span>
                                        </div>
                                        <p class="vgt-feat-desc">Volle DSGVO-Konformität durch Same-Origin-Payloads, signierte Site-Tokens und stündliche Ratenbegrenzungen. Keine externen Datenflüsse.</p>
                                    </div>

                                    <div class="vgt-feat-card">
                                        <div class="vgt-feat-header">
                                            <span class="vgt-feat-icon">🩹</span>
                                            <span class="vgt-feat-title">Early Boot Recovery Hook</span>
                                        </div>
                                        <p class="vgt-feat-desc">Ein dedizierter Recovery-Modus greift direkt im WordPress <code>admin_init</code> Hook, um fatalen Fehlern vorzubeugen und den Classic-Bypass sicherzustellen.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- SPENDEN UNTERSTÜTZUNG SEKTION -->
                            <div class="vgt-donation-section" style="border-top: 1px solid rgba(255,255,255,0.05); padding-top: 20px;">
                                <h3 class="vgt-donation-header" style="color: #ffffff; font-size: 13px; font-weight: 700; margin: 0 0 4px 0;"><?php echo esc_html__('Entwicklung unterstützen ❤️', 'vgtdesk'); ?></h3>
                                <p class="vgt-donation-desc" style="color: #94a3b8; font-size: 12px; line-height: 1.4; margin: 0 0 15px 0;"><?php echo esc_html__('Wenn dir das System gefällt und du uns unterstützen möchtest, freuen wir uns über eine kleine Spende:', 'vgtdesk'); ?></p>
                                
                                <div class="vgt-donation-grid">
                                    <!-- PayPal Link -->
                                    <a href="https://www.paypal.com/paypalme/dergoldenelotus" target="_blank" class="vgt-donation-card-item paypal-color">
                                        <span class="vgt-donation-icon">💙</span>
                                        <span class="vgt-donation-label"><?php echo esc_html__('PayPal', 'vgtdesk'); ?></span>
                                        <small class="vgt-donation-addr">dergoldenelotus</small>
                                    </a>

                                    <!-- Bitcoin Click to Copy -->
                                    <div class="vgt-donation-card-item crypto-btn" onclick="VGTDeskEngine.copyToClipboard('bc1q3ue5gq822tddmkdrek79adlkm36fatat3lz0dm', this)">
                                        <span class="vgt-donation-icon">🪙</span>
                                        <span class="vgt-donation-label"><?php echo esc_html__('Bitcoin', 'vgtdesk'); ?></span>
                                        <small class="vgt-donation-addr"><?php echo esc_html__('Kopieren', 'vgtdesk'); ?></small>
                                    </div>

                                    <!-- Ethereum Click to Copy -->
                                    <div class="vgt-donation-card-item crypto-btn" onclick="VGTDeskEngine.copyToClipboard('0xD37DEfb09e07bD775EaaE9ccDaFE3a5b2348Fe85', this)">
                                        <span class="vgt-donation-icon">💎</span>
                                        <span class="vgt-donation-label"><?php echo esc_html__('Ethereum', 'vgtdesk'); ?></span>
                                        <small class="vgt-donation-addr"><?php echo esc_html__('Kopieren', 'vgtdesk'); ?></small>
                                    </div>

                                    <!-- USDT Click to Copy -->
                                    <div class="vgt-donation-card-item crypto-btn" onclick="VGTDeskEngine.copyToClipboard('0xD37DEfb09e07bD775EaaE9ccDaFE3a5b2348Fe85', this)">
                                        <span class="vgt-donation-icon">💵</span>
                                        <span class="vgt-donation-label"><?php echo esc_html__('USDT (ERC-20)', 'vgtdesk'); ?></span>
                                        <small class="vgt-donation-addr"><?php echo esc_html__('Kopieren', 'vgtdesk'); ?></small>
                                    </div>
                                </div>
                            </div>

                            <!-- BRANDING FOOTER -->
                            <div class="vgt-popup-footer" style="border-top: 1px solid rgba(255,255,255,0.05); padding-top: 15px; margin-top: 20px; display: flex; justify-content: space-between; align-items: center; font-size: 11px; color: #475569;">
                                <span class="vgt-footer-branding">Powered by <a href="https://visiongaiatechnology.de" target="_blank" style="color: inherit; text-decoration: underline; transition: color 0.2s;">VisionGaiaTechnology</a></span>
                                <span class="vgt-footer-version"><?php echo esc_html__('V1.0 Beta v4 (Stable Candidate)', 'vgtdesk'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
