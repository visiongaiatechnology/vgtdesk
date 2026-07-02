<?php

declare(strict_types=1);

// STATUS: DIAMANT VGT SUPREME

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="vgta-root">
    <header class="vgta-header">
        <div class="vgta-branding">
            <h1>VGTAstra <span class="vgta-version">AGENT SYSTEM <?php echo esc_html(VGTA_PLUGIN_VERSION); ?></span></h1>
            <div class="vgta-subtitle">LIVE AI DEVELOPMENT CHAT - GROQ REASONING PIPELINE - SAFE PATCH VAULT</div>
        </div>
        <div class="vgta-status-container">
            <span class="vgta-badge diamant">DIAMANT VGT SUPREME</span>
            <a class="vgta-powered-link" href="<?php echo esc_url('https://visiongaiatechnology.de'); ?>" target="_blank" rel="noopener noreferrer">Powered by VisionGaiaTechnology</a>
        </div>
    </header>

    <div class="vgta-shell">
        <aside class="vgta-panel vgta-context-panel">
            <div class="vgta-panel-header">CONTEXT</div>
            <div class="vgta-panel-content">
                <div class="vgta-vault-state">
                    <span class="vgta-vault-dot<?php echo esc_attr($credentialSealed ? ' sealed' : ''); ?>"></span>
                    <span><?php echo $credentialSealed ? esc_html__('Groq key encrypted', 'vgta') : esc_html__('Groq key missing', 'vgta'); ?></span>
                </div>

                <div class="vgta-form-group">
                    <label for="vgta-api-key">GROQ API KEY</label>
                    <input type="password" id="vgta-api-key" value="" placeholder="gsk_..." class="vgta-input" autocomplete="off">
                    <button id="vgta-btn-save-credentials" class="vgta-btn secondary">ENCRYPT KEY</button>
                </div>

                <div class="vgta-form-group">
                    <label for="vgta-plugin-select">TARGET PLUGIN (INACTIVE ONLY)</label>
                    <select id="vgta-plugin-select" class="vgta-input">
                        <option value="">-- SELECT INACTIVE PLUGIN --</option>
                        <?php foreach ($inactivePlugins as $slug => $data) : ?>
                            <option value="<?php echo esc_attr($slug); ?>"><?php echo esc_html($data['Name'] . ' - ' . $slug); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button id="vgta-btn-generate-map" class="vgta-btn primary" disabled>BUILD MAP + CONTEXT</button>
                </div>

                <div class="vgta-map-container">
                    <div class="vgta-section-sub-title">PLUGIN MAP + FILE CONTEXT</div>
                    <div id="vgta-map-tree" class="vgta-tree">
                        <div class="vgta-placeholder-text">No target map loaded.</div>
                    </div>
                </div>
            </div>
        </aside>

        <main class="vgta-panel vgta-chat-panel">
            <div class="vgta-panel-header">LIVE ASSISTANT CHAT</div>
            <div class="vgta-chat-toolbar">
                <select id="vgta-chat-model" class="vgta-input"></select>
                <select id="vgta-chat-reasoning" class="vgta-input">
                    <option value="none">thinking loading</option>
                </select>
                <button id="vgta-btn-new-chat" class="vgta-btn secondary tiny">NEW CHAT</button>
            </div>
            <div class="vgta-grounding-bar">
                <label class="vgta-toggle-line"><input id="vgta-use-grounding" type="checkbox"> Use Web Grounding</label>
                <select id="vgta-grounding-mode" class="vgta-input">
                    <option value="cited">CITED</option>
                    <option value="research">RESEARCH</option>
                    <option value="strict_allowlist">STRICT ALLOWLIST</option>
                </select>
                <input id="vgta-grounding-sources" class="vgta-input" type="number" min="1" max="5" value="3">
                <input id="vgta-grounding-domains" class="vgta-input" type="text" placeholder="allowed domains">
                <button id="vgta-btn-clear-grounding-cache" class="vgta-btn secondary tiny">CLEAR CACHE</button>
            </div>
            <div class="vgta-memory-dock">
                <div class="vgta-memory-column">
                    <div class="vgta-section-sub-title">CHAT MEMORY</div>
                    <div id="vgta-memory-sessions" class="vgta-memory-list">
                        <div class="vgta-placeholder-text">No saved chats.</div>
                    </div>
                </div>
                <div class="vgta-memory-column">
                    <div class="vgta-section-sub-title">ARTIFACTS</div>
                    <div id="vgta-memory-artifacts" class="vgta-memory-list">
                        <div class="vgta-placeholder-text">No artifacts stored.</div>
                    </div>
                </div>
            </div>
            <div id="vgta-chat-log" class="vgta-chat-log">
                <div class="vgta-message system">
                    <div class="vgta-message-meta">VGTAstra SYSTEM</div>
                    <div class="vgta-message-body">Chat ready. Select a target plugin, build the file context, then give development instructions or start the role pipeline.</div>
                </div>
            </div>
            <div class="vgta-chat-composer">
                <textarea id="vgta-chat-input" class="vgta-chat-input" placeholder="Schreib mit VGTAstra über die aktuelle Entwicklung, Anforderungen, Bugs oder Pipeline-Anweisungen."></textarea>
                <button id="vgta-btn-send-chat" class="vgta-btn success">SEND</button>
            </div>
            <div class="vgta-terminal-metrics" id="vgta-metrics-display">
                <span>CACHE: STANDBY</span>
                <span>LATENCY: 0ms</span>
                <span>SPEED: 0 tps</span>
            </div>
        </main>

        <aside class="vgta-panel vgta-orchestrator-panel">
            <div class="vgta-panel-header">ROLE PIPELINE</div>
            <div class="vgta-panel-content">
                <div class="vgta-form-group">
                    <label for="vgta-global-prompt">OPERATOR SYSTEM PROMPT</label>
                    <textarea id="vgta-global-prompt" class="vgta-textarea" placeholder="Dieser Prompt liegt unter den rollenfesten Systemprompts und über der konkreten Step-Anweisung."></textarea>
                </div>

                <div class="vgta-loop-grid">
                    <div class="vgta-form-group">
                        <label for="vgta-loop-count">MAX LOOPS</label>
                        <input id="vgta-loop-count" class="vgta-input" type="number" min="1" max="12" value="3">
                    </div>
                    <div class="vgta-form-group">
                        <label for="vgta-stop-mode">STOP MODE</label>
                        <select id="vgta-stop-mode" class="vgta-input">
                            <option value="approval" selected>stop on auditor approval</option>
                            <option value="fixed">fixed loop count</option>
                        </select>
                    </div>
                </div>

                <div class="vgta-loop-actions">
                    <div class="vgta-section-sub-title">AGENT ROLES</div>
                    <button id="vgta-btn-add-agent" class="vgta-btn secondary tiny">ADD ROLE</button>
                </div>

                <div id="vgta-agent-steps-list" class="vgta-agent-stack"></div>

                <div class="vgta-execution-control-block">
                    <button id="vgta-btn-start-orchestration" class="vgta-btn success large" disabled>START PIPELINE</button>
                    <button id="vgta-btn-abort-orchestration" class="vgta-btn danger large is-hidden">STOP PIPELINE</button>
                </div>
            </div>
        </aside>
    </div>

    <section class="vgta-panel vgta-patch-vault">
        <div class="vgta-panel-header">SAFE PATCH VAULT</div>
        <div class="vgta-patch-vault-head">
            <div class="vgta-section-sub-title">SERVER-STAGED FILE PROPOSALS</div>
            <button id="vgta-btn-clear-patches" class="vgta-btn secondary tiny" disabled>CLEAR VAULT</button>
        </div>
        <div id="vgta-patch-list" class="vgta-patch-list">
            <div class="vgta-placeholder-text">No staged file proposals.</div>
        </div>
    </section>

    <section class="vgta-panel vgta-agent-forge">
        <div class="vgta-panel-header">AGENT FORGE</div>
        <div class="vgta-forge-grid">
            <div class="vgta-forge-preview">
                <div class="vgta-section-sub-title">BLUEPRINT PREVIEW</div>
                <div id="vgta-agent-blueprint-preview" class="vgta-agent-blueprint-preview">
                    <div class="vgta-placeholder-text">Ask the chat to build a specialist agent, then review the blueprint here.</div>
                </div>
            </div>
            <div class="vgta-forge-registry">
                <div class="vgta-section-sub-title">CUSTOM AGENTS</div>
                <div id="vgta-custom-agent-list" class="vgta-custom-agent-list">
                    <div class="vgta-placeholder-text">No custom agents registered.</div>
                </div>
            </div>
        </div>
    </section>

    <div id="vgta-beta-security-gate" class="vgta-beta-gate" role="dialog" aria-modal="true" aria-labelledby="vgta-beta-gate-title">
        <div class="vgta-beta-card">
            <div class="vgta-beta-kicker">VGT R&amp;D LABOR - PUBLIC BETA</div>
            <h2 id="vgta-beta-gate-title">Sicherheitshinweis vor dem Start</h2>
            <p>
                VGTAstra wurde mit Security by Design gebaut: Nonce-Pruefung, Capability-Gates, Path-Jail,
                verschluesselter Vault, sichere Artefakt-Speicherung und defensive Ausgabewege sind aktiv.
            </p>
            <p>
                Trotzdem kann kein Agentensystem garantieren, dass KI-generierter Code fachlich korrekt,
                frei von Sicherheitsluecken oder fuer produktive Systeme geeignet ist. Jede erzeugte Datei,
                jeder Patch und jede Architekturentscheidung muss vor dem Einsatz durch einen Operator geprueft werden.
            </p>
            <ul class="vgta-beta-list">
                <li>Nur inaktive Plugins bearbeiten und Patches vor dem Commit im Diff pruefen.</li>
                <li>Keine geheimen Kundendaten in Prompts oder Artefakte schreiben.</li>
                <li>KI-Ausgaben sind Vorschlaege, keine automatische Freigabe fuer Produktion.</li>
                <li>Backups und Review-Prozess bleiben Pflicht.</li>
            </ul>
            <div class="vgta-beta-actions">
                <a class="vgta-beta-brand" href="<?php echo esc_url('https://visiongaiatechnology.de'); ?>" target="_blank" rel="noopener noreferrer">Powered by VisionGaiaTechnology</a>
                <button id="vgta-beta-security-confirm" class="vgta-btn success large" type="button">RISIKO VERSTANDEN - DASHBOARD OEFFNEN</button>
            </div>
        </div>
    </div>
</div>
