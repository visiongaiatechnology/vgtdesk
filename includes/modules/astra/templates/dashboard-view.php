<?php

declare(strict_types=1);

// STATUS: DIAMANT VGT SUPREME

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="vgta-root" data-vgta-lang="de">
    <header class="vgta-header">
        <div class="vgta-branding">
            <h1>VGTAstra <span class="vgta-version">AGENT SYSTEM <?php echo esc_html(VGTA_PLUGIN_VERSION); ?></span></h1>
            <div class="vgta-subtitle" data-i18n="subtitle">LIVE-KI-ENTWICKLUNGSCHAT - GROQ REASONING PIPELINE - SAFE PATCH VAULT</div>
        </div>
        <div class="vgta-status-container">
            <span class="vgta-badge diamant">DIAMANT VGT SUPREME</span>
            <div class="vgta-header-actions">
                <button id="vgta-btn-language-toggle" class="vgta-btn secondary tiny" type="button" data-i18n="language_toggle">ENGLISH</button>
                <button id="vgta-btn-open-guide" class="vgta-btn secondary tiny" type="button" data-i18n="open_guide">GUIDE ÖFFNEN</button>
                <a class="vgta-powered-link" href="<?php echo esc_url('https://visiongaiatechnology.de'); ?>" target="_blank" rel="noopener noreferrer">Powered by VisionGaiaTechnology</a>
            </div>
        </div>
    </header>

    <div class="vgta-shell" id="vgta-shell">
        <aside class="vgta-panel vgta-context-panel" id="vgta-context-panel">
            <div class="vgta-panel-header">
                <span data-i18n="context_header">CONTEXT</span>
                <button id="vgta-btn-toggle-context" class="vgta-panel-toggle" type="button" aria-expanded="true" data-i18n-title="collapse_context" title="Context einklappen">‹</button>
            </div>
            <div class="vgta-panel-content">
                <div class="vgta-vault-status-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 15px;">
                    <div class="vgta-vault-state">
                        <span class="vgta-vault-dot<?php echo esc_attr($groqSealed ? ' sealed' : ''); ?>"></span>
                        <span>Groq: <?php echo $groqSealed ? 'Sealed' : 'Missing'; ?></span>
                    </div>
                    <div class="vgta-vault-state">
                        <span class="vgta-vault-dot<?php echo esc_attr($geminiSealed ? ' sealed' : ''); ?>"></span>
                        <span>Gemini: <?php echo $geminiSealed ? 'Sealed' : 'Missing'; ?></span>
                    </div>
                    <div class="vgta-vault-state">
                        <span class="vgta-vault-dot<?php echo esc_attr($claudeSealed ? ' sealed' : ''); ?>"></span>
                        <span>Claude: <?php echo $claudeSealed ? 'Sealed' : 'Missing'; ?></span>
                    </div>
                    <div class="vgta-vault-state">
                        <span class="vgta-vault-dot<?php echo esc_attr($chatgptSealed ? ' sealed' : ''); ?>"></span>
                        <span>ChatGPT: <?php echo $chatgptSealed ? 'Sealed' : 'Missing'; ?></span>
                    </div>
                </div>

                <div class="vgta-form-group">
                    <label for="vgta-provider-select">KI-PROVIDER</label>
                    <select id="vgta-provider-select" class="vgta-input">
                        <option value="groq">Groq (gsk_...)</option>
                        <option value="gemini">Gemini (AIzaSy...)</option>
                        <option value="claude">Claude (sk-ant-...)</option>
                        <option value="chatgpt">ChatGPT / OpenAI (sk-...)</option>
                    </select>
                </div>

                <div class="vgta-form-group">
                    <label for="vgta-api-key">API KEY</label>
                    <input type="password" id="vgta-api-key" value="" placeholder="API-Key eintragen..." class="vgta-input" autocomplete="off">
                    <button id="vgta-btn-save-credentials" class="vgta-btn secondary" data-i18n="encrypt_key">SCHLÜSSEL VERSCHLÜSSELN</button>
                </div>

                <div class="vgta-form-group">
                    <label for="vgta-plugin-select" data-i18n="target_plugin">ZIELPLUGIN (NUR INAKTIV)</label>
                    <select id="vgta-plugin-select" class="vgta-input">
                        <option value="" data-i18n="select_inactive_plugin">-- INAKTIVES PLUGIN WÄHLEN --</option>
                        <?php foreach ($inactivePlugins as $slug => $data) : ?>
                            <option value="<?php echo esc_attr($slug); ?>"><?php echo esc_html($data['Name'] . ' - ' . $slug); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button id="vgta-btn-generate-map" class="vgta-btn primary" disabled data-i18n="build_map">MAP + CONTEXT BAUEN</button>
                </div>

                <div class="vgta-map-container">
                    <div class="vgta-section-sub-title" data-i18n="plugin_map">PLUGIN MAP + FILE CONTEXT</div>
                    <div id="vgta-map-tree" class="vgta-tree">
                        <div class="vgta-placeholder-text" data-i18n="no_target_map">Keine Ziel-Map geladen.</div>
                    </div>
                </div>
            </div>
        </aside>

        <main class="vgta-panel vgta-chat-panel" id="vgta-chat-panel">
            <div class="vgta-panel-header">
                <span data-i18n="chat_header">LIVE ASSISTANT CHAT</span>
            </div>
            <section class="vgta-chat-config-shell">
                <button id="vgta-btn-toggle-chat-config" class="vgta-drawer-toggle" type="button" aria-expanded="false">
                    <span data-i18n="chat_config_title">Chat Memory, Artifacts & Modell-Konfiguration</span>
                    <span class="vgta-drawer-state" data-i18n="drawer_open">ÖFFNEN</span>
                </button>
                <div id="vgta-chat-config-drawer" class="vgta-chat-config-drawer is-collapsed">
                    <div class="vgta-config-grid">
                        <div class="vgta-config-card">
                            <div class="vgta-section-sub-title" data-i18n="model_config">MODELL & THINKING MODE</div>
                            <div class="vgta-chat-toolbar">
                                <label class="vgta-field-label" for="vgta-chat-model" data-i18n="model_label">Modell</label>
                                <select id="vgta-chat-model" class="vgta-input"></select>
                                <label class="vgta-field-label" for="vgta-chat-reasoning" data-i18n="thinking_label">Thinking Mode</label>
                                <select id="vgta-chat-reasoning" class="vgta-input">
                                    <option value="none" data-i18n="thinking_loading">Thinking lädt</option>
                                </select>
                                <button id="vgta-btn-new-chat" class="vgta-btn secondary tiny" data-i18n="new_chat">NEUER CHAT</button>
                            </div>
                        </div>
                        <div class="vgta-config-card">
                            <div class="vgta-section-sub-title" data-i18n="web_grounding">WEB GROUNDING</div>
                            <div class="vgta-grounding-bar">
                                <label class="vgta-toggle-line"><input id="vgta-use-grounding" type="checkbox"> <span data-i18n="use_grounding">Web Grounding nutzen</span></label>
                                <select id="vgta-grounding-mode" class="vgta-input">
                                    <option value="cited">CITED</option>
                                    <option value="research">RESEARCH</option>
                                    <option value="strict_allowlist">STRICT ALLOWLIST</option>
                                </select>
                                <input id="vgta-grounding-sources" class="vgta-input" type="number" min="1" max="5" value="3">
                                <input id="vgta-grounding-domains" class="vgta-input" type="text" placeholder="Erlaubte Domains" data-i18n-placeholder="allowed_domains">
                                <button id="vgta-btn-clear-grounding-cache" class="vgta-btn secondary tiny" data-i18n="clear_cache">CACHE LEEREN</button>
                            </div>
                        </div>
                    </div>
                    <div class="vgta-memory-dock">
                        <div class="vgta-memory-column">
                            <div class="vgta-section-sub-title" data-i18n="chat_memory">CHAT MEMORY</div>
                            <div id="vgta-memory-sessions" class="vgta-memory-list">
                                <div class="vgta-placeholder-text" data-i18n="no_saved_chats">Keine gespeicherten Chats.</div>
                            </div>
                        </div>
                        <div class="vgta-memory-column">
                            <div class="vgta-section-sub-title" data-i18n="artifacts">ARTIFACTS</div>
                            <div id="vgta-memory-artifacts" class="vgta-memory-list">
                                <div class="vgta-placeholder-text" data-i18n="no_artifacts">Keine Artefakte gespeichert.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <div id="vgta-chat-log" class="vgta-chat-log">
                <div class="vgta-message system">
                    <div class="vgta-message-meta">VGTAstra SYSTEM</div>
                    <div class="vgta-message-body" data-i18n="chat_ready">Chat bereit. Wähle ein Zielplugin, baue den File-Context und gib dann Entwicklungsanweisungen oder starte die Rollen-Pipeline.</div>
                </div>
            </div>
            <div class="vgta-chat-composer">
                <textarea id="vgta-chat-input" class="vgta-chat-input" placeholder="Schreib mit VGTAstra über die aktuelle Entwicklung, Anforderungen, Bugs oder Pipeline-Anweisungen." data-i18n-placeholder="chat_placeholder"></textarea>
                <button id="vgta-btn-send-chat" class="vgta-btn success" data-i18n="send">SENDEN</button>
            </div>
            <div class="vgta-terminal-metrics" id="vgta-metrics-display">
                <span>CACHE: STANDBY</span>
                <span>LATENCY: 0ms</span>
                <span>SPEED: 0 tps</span>
            </div>
            <div class="vgta-context-meter" id="vgta-context-meter">
                <span>CONTEXT: STANDBY</span>
            </div>
        </main>

        <aside class="vgta-panel vgta-orchestrator-panel" id="vgta-orchestrator-panel">
            <div class="vgta-panel-header">
                <span data-i18n="role_pipeline">ROLE PIPELINE</span>
                <button id="vgta-btn-toggle-pipeline" class="vgta-panel-toggle" type="button" aria-expanded="true" data-i18n-title="collapse_pipeline" title="Pipeline einklappen">›</button>
            </div>
            <div class="vgta-panel-content">
                <div class="vgta-form-group">
                    <label for="vgta-global-prompt" data-i18n="operator_prompt">OPERATOR SYSTEM PROMPT</label>
                    <textarea id="vgta-global-prompt" class="vgta-textarea" placeholder="Dieser Prompt liegt unter den rollenfesten Systemprompts und über der konkreten Step-Anweisung." data-i18n-placeholder="operator_prompt_placeholder"></textarea>
                </div>

                <div class="vgta-loop-grid">
                    <div class="vgta-form-group">
                        <label for="vgta-loop-count" data-i18n="max_loops">MAX LOOPS</label>
                        <input id="vgta-loop-count" class="vgta-input" type="number" min="1" max="12" value="3">
                    </div>
                    <div class="vgta-form-group">
                        <label for="vgta-stop-mode" data-i18n="stop_mode">STOP MODE</label>
                        <select id="vgta-stop-mode" class="vgta-input">
                            <option value="approval" selected data-i18n="stop_on_approval">Bei Auditor-Freigabe stoppen</option>
                            <option value="fixed" data-i18n="fixed_loop_count">Feste Loop-Anzahl</option>
                        </select>
                    </div>
                </div>

                <div class="vgta-loop-actions">
                    <div class="vgta-section-sub-title" data-i18n="agent_roles">AGENT ROLES</div>
                    <button id="vgta-btn-add-agent" class="vgta-btn secondary tiny" data-i18n="add_role">ROLLE HINZUFÜGEN</button>
                </div>

                <div id="vgta-agent-steps-list" class="vgta-agent-stack"></div>

                <div class="vgta-execution-control-block">
                    <button id="vgta-btn-start-orchestration" class="vgta-btn success large" disabled data-i18n="start_pipeline">PIPELINE STARTEN</button>
                    <button id="vgta-btn-abort-orchestration" class="vgta-btn danger large is-hidden" data-i18n="stop_pipeline">PIPELINE STOPPEN</button>
                </div>
            </div>
        </aside>
    </div>

    <section class="vgta-panel vgta-patch-vault">
        <div class="vgta-panel-header" data-i18n="safe_patch_vault">SAFE PATCH VAULT</div>
        <div class="vgta-patch-vault-head">
            <div class="vgta-section-sub-title" data-i18n="server_staged">SERVER-STAGED FILE PROPOSALS</div>
            <div class="vgta-patch-vault-actions">
                <button id="vgta-btn-review-bundle" class="vgta-btn success tiny" disabled data-i18n="review_all">ALLE PRÜFEN</button>
                <button id="vgta-btn-clear-patches" class="vgta-btn secondary tiny" disabled data-i18n="clear_vault">VAULT LEEREN</button>
            </div>
        </div>
        <div id="vgta-patch-list" class="vgta-patch-list">
            <div class="vgta-placeholder-text" data-i18n="no_proposals">Keine staged File-Proposals.</div>
        </div>
    </section>

    <section class="vgta-panel vgta-agent-forge">
        <div class="vgta-panel-header">AGENT FORGE</div>
        <div class="vgta-forge-grid">
            <div class="vgta-forge-preview">
                <div class="vgta-section-sub-title">BLUEPRINT PREVIEW</div>
                <div id="vgta-agent-blueprint-preview" class="vgta-agent-blueprint-preview">
                    <div class="vgta-placeholder-text" data-i18n="blueprint_hint">Frag den Chat nach einem Spezial-Agenten und prüfe den Blueprint hier.</div>
                </div>
            </div>
            <div class="vgta-forge-registry">
                <div class="vgta-section-sub-title">CUSTOM AGENTS</div>
                <div id="vgta-custom-agent-list" class="vgta-custom-agent-list">
                    <div class="vgta-placeholder-text" data-i18n="no_custom_agents">Keine Custom Agents registriert.</div>
                </div>
            </div>
        </div>
    </section>

    <div id="vgta-beta-security-gate" class="vgta-beta-gate" role="dialog" aria-modal="true" aria-labelledby="vgta-beta-gate-title">
        <div class="vgta-beta-card">
            <div class="vgta-beta-kicker">VGT R&amp;D LABOR - PUBLIC BETA</div>
            <h2 id="vgta-beta-gate-title" data-i18n="beta_title">Sicherheitshinweis vor dem Start</h2>
            <p data-i18n="beta_copy_1">
                VGTAstra wurde mit Security by Design gebaut: Nonce-Prüfung, Capability-Gates, Path-Jail,
                verschlüsselter Vault, sichere Artefakt-Speicherung und defensive Ausgabewege sind aktiv.
            </p>
            <p data-i18n="beta_copy_2">
                Trotzdem kann kein Agentensystem garantieren, dass KI-generierter Code fachlich korrekt,
                frei von Sicherheitslücken oder für produktive Systeme geeignet ist. Jede erzeugte Datei,
                jeder Patch und jede Architekturentscheidung muss vor dem Einsatz durch einen Operator geprüft werden.
            </p>
            <ul class="vgta-beta-list">
                <li data-i18n="beta_li_1">Nur inaktive Plugins bearbeiten und Patches vor dem Commit im Diff prüfen.</li>
                <li data-i18n="beta_li_2">Keine geheimen Kundendaten in Prompts oder Artefakte schreiben.</li>
                <li data-i18n="beta_li_3">KI-Ausgaben sind Vorschläge, keine automatische Freigabe für Produktion.</li>
                <li data-i18n="beta_li_4">Backups und Review-Prozess bleiben Pflicht.</li>
            </ul>
            <div class="vgta-beta-actions">
                <a class="vgta-beta-brand" href="<?php echo esc_url('https://visiongaiatechnology.de'); ?>" target="_blank" rel="noopener noreferrer">Powered by VisionGaiaTechnology</a>
                <button id="vgta-beta-security-confirm" class="vgta-btn success large" type="button" data-i18n="beta_confirm">RISIKO VERSTANDEN - DASHBOARD ÖFFNEN</button>
            </div>
        </div>
    </div>
</div>
