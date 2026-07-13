/**
 * STATUS: DIAMANT VGT SUPREME
 * VGTAstra layout controls and static UI language pack.
 */

window.VGTAstraLayout = (() => {
    'use strict';

    const storageKeys = {
        language: 'vgta_ui_language_v1',
        chatConfig: 'vgta_chat_config_open_v1',
        context: 'vgta_context_collapsed_v1',
        pipeline: 'vgta_pipeline_collapsed_v1',
    };

    const translations = {
        de: {
            subtitle: 'LIVE-KI-ENTWICKLUNGSCHAT - GROQ REASONING PIPELINE - SAFE PATCH VAULT',
            language_toggle: 'ENGLISH',
            open_guide: 'GUIDE \u00d6FFNEN',
            context_header: 'CONTEXT',
            groq_api_key: 'GROQ API KEY',
            encrypt_key: 'SCHL\u00dcSSEL VERSCHL\u00dcSSELN',
            target_plugin: 'ZIELPLUGIN (NUR INAKTIV)',
            select_inactive_plugin: '-- INAKTIVES PLUGIN W\u00c4HLEN --',
            build_map: 'MAP + CONTEXT BAUEN',
            plugin_map: 'PLUGIN MAP + FILE CONTEXT',
            no_target_map: 'Keine Ziel-Map geladen.',
            chat_header: 'LIVE ASSISTANT CHAT',
            chat_config_title: 'Chat Memory, Artifacts & Modell-Konfiguration',
            drawer_open: '\u00d6FFNEN',
            drawer_close: 'SCHLIESSEN',
            model_config: 'MODELL & THINKING MODE',
            model_label: 'Modell',
            thinking_label: 'Thinking Mode',
            thinking_loading: 'Thinking l\u00e4dt',
            new_chat: 'NEUER CHAT',
            web_grounding: 'WEB GROUNDING',
            use_grounding: 'Web Grounding nutzen',
            allowed_domains: 'Erlaubte Domains',
            clear_cache: 'CACHE LEEREN',
            chat_memory: 'CHAT MEMORY',
            no_saved_chats: 'Keine gespeicherten Chats.',
            artifacts: 'ARTIFACTS',
            no_artifacts: 'Keine Artefakte gespeichert.',
            chat_ready: 'Chat bereit. W\u00e4hle ein Zielplugin, baue den File-Context und gib dann Entwicklungsanweisungen oder starte die Rollen-Pipeline.',
            chat_placeholder: 'Schreib mit VGTAstra \u00fcber die aktuelle Entwicklung, Anforderungen, Bugs oder Pipeline-Anweisungen.',
            send: 'SENDEN',
            role_pipeline: 'ROLE PIPELINE',
            operator_prompt: 'OPERATOR SYSTEM PROMPT',
            operator_prompt_placeholder: 'Dieser Prompt liegt unter den rollenfesten Systemprompts und \u00fcber der konkreten Step-Anweisung.',
            max_loops: 'MAX LOOPS',
            stop_mode: 'STOP MODE',
            stop_on_approval: 'Bei Auditor-Freigabe stoppen',
            fixed_loop_count: 'Feste Loop-Anzahl',
            agent_roles: 'AGENT ROLES',
            add_role: 'ROLLE HINZUF\u00dcGEN',
            start_pipeline: 'PIPELINE STARTEN',
            stop_pipeline: 'PIPELINE STOPPEN',
            safe_patch_vault: 'SAFE PATCH VAULT',
            server_staged: 'SERVER-STAGED FILE PROPOSALS',
            review_all: 'ALLE PR\u00dcFEN',
            clear_vault: 'VAULT LEEREN',
            no_proposals: 'Keine staged File-Proposals.',
            blueprint_hint: 'Frag den Chat nach einem Spezial-Agenten und pr\u00fcfe den Blueprint hier.',
            no_custom_agents: 'Keine Custom Agents registriert.',
            beta_title: 'Sicherheitshinweis vor dem Start',
            beta_copy_1: 'VGTAstra wurde mit Security by Design gebaut: Nonce-Pr\u00fcfung, Capability-Gates, Path-Jail, verschl\u00fcsselter Vault, sichere Artefakt-Speicherung und defensive Ausgabewege sind aktiv.',
            beta_copy_2: 'Trotzdem kann kein Agentensystem garantieren, dass KI-generierter Code fachlich korrekt, frei von Sicherheitsl\u00fccken oder f\u00fcr produktive Systeme geeignet ist. Jede erzeugte Datei, jeder Patch und jede Architekturentscheidung muss vor dem Einsatz durch einen Operator gepr\u00fcft werden.',
            beta_li_1: 'Nur inaktive Plugins bearbeiten und Patches vor dem Commit im Diff pr\u00fcfen.',
            beta_li_2: 'Keine geheimen Kundendaten in Prompts oder Artefakte schreiben.',
            beta_li_3: 'KI-Ausgaben sind Vorschl\u00e4ge, keine automatische Freigabe f\u00fcr Produktion.',
            beta_li_4: 'Backups und Review-Prozess bleiben Pflicht.',
            beta_confirm: 'RISIKO VERSTANDEN - DASHBOARD \u00d6FFNEN',
            collapse_context: 'Context einklappen',
            expand_context: 'Context ausklappen',
            collapse_pipeline: 'Pipeline einklappen',
            expand_pipeline: 'Pipeline ausklappen',
        },
        en: {
            subtitle: 'LIVE AI DEVELOPMENT CHAT - GROQ REASONING PIPELINE - SAFE PATCH VAULT',
            language_toggle: 'DEUTSCH',
            open_guide: 'OPEN GUIDE',
            context_header: 'CONTEXT',
            groq_api_key: 'GROQ API KEY',
            encrypt_key: 'ENCRYPT KEY',
            target_plugin: 'TARGET PLUGIN (INACTIVE ONLY)',
            select_inactive_plugin: '-- SELECT INACTIVE PLUGIN --',
            build_map: 'BUILD MAP + CONTEXT',
            plugin_map: 'PLUGIN MAP + FILE CONTEXT',
            no_target_map: 'No target map loaded.',
            chat_header: 'LIVE ASSISTANT CHAT',
            chat_config_title: 'Chat Memory, Artifacts & Model Configuration',
            drawer_open: 'OPEN',
            drawer_close: 'CLOSE',
            model_config: 'MODEL & THINKING MODE',
            model_label: 'Model',
            thinking_label: 'Thinking Mode',
            thinking_loading: 'Thinking loading',
            new_chat: 'NEW CHAT',
            web_grounding: 'WEB GROUNDING',
            use_grounding: 'Use Web Grounding',
            allowed_domains: 'Allowed domains',
            clear_cache: 'CLEAR CACHE',
            chat_memory: 'CHAT MEMORY',
            no_saved_chats: 'No saved chats.',
            artifacts: 'ARTIFACTS',
            no_artifacts: 'No artifacts stored.',
            chat_ready: 'Chat ready. Select a target plugin, build the file context, then give development instructions or start the role pipeline.',
            chat_placeholder: 'Talk to VGTAstra about current development, requirements, bugs, or pipeline instructions.',
            send: 'SEND',
            role_pipeline: 'ROLE PIPELINE',
            operator_prompt: 'OPERATOR SYSTEM PROMPT',
            operator_prompt_placeholder: 'This prompt sits below fixed role system prompts and above the concrete step instruction.',
            max_loops: 'MAX LOOPS',
            stop_mode: 'STOP MODE',
            stop_on_approval: 'Stop on auditor approval',
            fixed_loop_count: 'Fixed loop count',
            agent_roles: 'AGENT ROLES',
            add_role: 'ADD ROLE',
            start_pipeline: 'START PIPELINE',
            stop_pipeline: 'STOP PIPELINE',
            safe_patch_vault: 'SAFE PATCH VAULT',
            server_staged: 'SERVER-STAGED FILE PROPOSALS',
            review_all: 'REVIEW ALL',
            clear_vault: 'CLEAR VAULT',
            no_proposals: 'No staged file proposals.',
            blueprint_hint: 'Ask the chat to build a specialist agent, then review the blueprint here.',
            no_custom_agents: 'No custom agents registered.',
            beta_title: 'Security notice before start',
            beta_copy_1: 'VGTAstra was built with security by design: nonce checks, capability gates, path jail, encrypted vault, secure artifact storage, and defensive output paths are active.',
            beta_copy_2: 'Still, no agent system can guarantee that AI-generated code is functionally correct, free of security issues, or suitable for production systems. Every generated file, patch, and architecture decision must be reviewed by an operator before use.',
            beta_li_1: 'Only edit inactive plugins and review patches in the diff before committing.',
            beta_li_2: 'Do not write confidential customer data into prompts or artifacts.',
            beta_li_3: 'AI outputs are proposals, not automatic production approval.',
            beta_li_4: 'Backups and the review process remain mandatory.',
            beta_confirm: 'RISK UNDERSTOOD - OPEN DASHBOARD',
            collapse_context: 'Collapse context',
            expand_context: 'Expand context',
            collapse_pipeline: 'Collapse pipeline',
            expand_pipeline: 'Expand pipeline',
        },
    };

    function init() {
        const root = document.querySelector('.vgta-root');
        const shell = document.getElementById('vgta-shell');
        const languageButton = document.getElementById('vgta-btn-language-toggle');
        const guideButton = document.getElementById('vgta-btn-open-guide');
        const chatConfigButton = document.getElementById('vgta-btn-toggle-chat-config');
        const chatConfigDrawer = document.getElementById('vgta-chat-config-drawer');
        const contextButton = document.getElementById('vgta-btn-toggle-context');
        const pipelineButton = document.getElementById('vgta-btn-toggle-pipeline');

        applyLanguage(readStorage(storageKeys.language, 'de') === 'en' ? 'en' : 'de');
        setDrawerState(chatConfigButton, chatConfigDrawer, readStorage(storageKeys.chatConfig, '0') === '1');
        setPanelState(shell, contextButton, 'context', readStorage(storageKeys.context, '0') === '1');
        setPanelState(shell, pipelineButton, 'pipeline', readStorage(storageKeys.pipeline, '0') === '1');

        if (languageButton) {
            languageButton.addEventListener('click', () => {
                const current = root && root.dataset.vgtaLang === 'en' ? 'en' : 'de';
                applyLanguage(current === 'en' ? 'de' : 'en');
            });
        }

        if (guideButton) {
            guideButton.addEventListener('click', () => {
                if (window.VGTAstraOnboarding && typeof window.VGTAstraOnboarding.openGuide === 'function') {
                    window.VGTAstraOnboarding.openGuide(true);
                }
            });
        }

        if (chatConfigButton && chatConfigDrawer) {
            chatConfigButton.addEventListener('click', () => {
                const open = chatConfigDrawer.classList.contains('is-collapsed');
                setDrawerState(chatConfigButton, chatConfigDrawer, open);
                writeStorage(storageKeys.chatConfig, open ? '1' : '0');
            });
        }

        if (contextButton && shell) {
            contextButton.addEventListener('click', () => {
                const collapsed = !shell.classList.contains('is-context-collapsed');
                setPanelState(shell, contextButton, 'context', collapsed);
                writeStorage(storageKeys.context, collapsed ? '1' : '0');
            });
        }

        if (pipelineButton && shell) {
            pipelineButton.addEventListener('click', () => {
                const collapsed = !shell.classList.contains('is-pipeline-collapsed');
                setPanelState(shell, pipelineButton, 'pipeline', collapsed);
                writeStorage(storageKeys.pipeline, collapsed ? '1' : '0');
            });
        }
    }

    function applyLanguage(language) {
        const dict = translations[language] || translations.de;
        const root = document.querySelector('.vgta-root');
        if (root) {
            root.dataset.vgtaLang = language;
        }
        document.documentElement.lang = language === 'en' ? 'en' : 'de';
        document.querySelectorAll('[data-i18n]').forEach((element) => {
            const key = element.getAttribute('data-i18n');
            if (key && Object.prototype.hasOwnProperty.call(dict, key)) {
                element.textContent = dict[key];
            }
        });
        document.querySelectorAll('[data-i18n-placeholder]').forEach((element) => {
            const key = element.getAttribute('data-i18n-placeholder');
            if (key && Object.prototype.hasOwnProperty.call(dict, key)) {
                element.setAttribute('placeholder', dict[key]);
            }
        });
        syncStateLabels();
        writeStorage(storageKeys.language, language);
        window.dispatchEvent(new CustomEvent('vgta:language-changed', { detail: { language } }));
    }

    function translate(key, language) {
        const active = language || readStorage(storageKeys.language, 'de');
        const dict = translations[active] || translations.de;
        return Object.prototype.hasOwnProperty.call(dict, key) ? dict[key] : key;
    }

    function syncStateLabels() {
        const chatConfigButton = document.getElementById('vgta-btn-toggle-chat-config');
        const chatConfigDrawer = document.getElementById('vgta-chat-config-drawer');
        if (chatConfigButton && chatConfigDrawer) {
            setDrawerState(chatConfigButton, chatConfigDrawer, !chatConfigDrawer.classList.contains('is-collapsed'));
        }
        const shell = document.getElementById('vgta-shell');
        const contextButton = document.getElementById('vgta-btn-toggle-context');
        const pipelineButton = document.getElementById('vgta-btn-toggle-pipeline');
        if (shell && contextButton) {
            setPanelState(shell, contextButton, 'context', shell.classList.contains('is-context-collapsed'));
        }
        if (shell && pipelineButton) {
            setPanelState(shell, pipelineButton, 'pipeline', shell.classList.contains('is-pipeline-collapsed'));
        }
    }

    function setDrawerState(button, drawer, open) {
        if (!button || !drawer) {
            return;
        }
        drawer.classList.toggle('is-collapsed', !open);
        button.setAttribute('aria-expanded', open ? 'true' : 'false');
        const state = button.querySelector('.vgta-drawer-state');
        if (state) {
            state.textContent = translate(open ? 'drawer_close' : 'drawer_open');
        }
    }

    function setPanelState(shell, button, side, collapsed) {
        if (!shell || !button) {
            return;
        }
        const className = side === 'context' ? 'is-context-collapsed' : 'is-pipeline-collapsed';
        shell.classList.toggle(className, collapsed);
        button.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        if (side === 'context') {
            button.textContent = collapsed ? 'OPEN' : 'CLOSE';
            button.dataset.i18nTitle = collapsed ? 'expand_context' : 'collapse_context';
        } else {
            button.textContent = collapsed ? 'OPEN' : 'CLOSE';
            button.dataset.i18nTitle = collapsed ? 'expand_pipeline' : 'collapse_pipeline';
        }
        const lang = document.querySelector('.vgta-root')?.dataset.vgtaLang || 'de';
        button.setAttribute('title', translate(button.dataset.i18nTitle, lang));
        button.setAttribute('aria-label', translate(button.dataset.i18nTitle, lang));
    }

    function readStorage(key, fallback) {
        try {
            const value = window.localStorage.getItem(key);
            return value === null ? fallback : value;
        } catch (error) {
            return fallback;
        }
    }

    function writeStorage(key, value) {
        try {
            window.localStorage.setItem(key, value);
        } catch (error) {
            return;
        }
    }

    return { init, applyLanguage, translate };
})();
