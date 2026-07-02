/**
 * STATUS: DIAMANT VGT SUPREME
 * VGTAstra Agent System - client side pipeline orchestration & collapsible thinking.
 */

document.addEventListener('DOMContentLoaded', () => {
    'use strict';

    const config = {
        ajaxUrl: vgtaConfig.ajaxUrl,
        nonce: vgtaConfig.nonce,
        models: Array.isArray(vgtaConfig.models) ? vgtaConfig.models : [],
        roles: Array.isArray(vgtaConfig.roles) ? vgtaConfig.roles : [],
        activePlugin: '',
        isExecuting: false,
        chatHistory: [],
        executionHistory: [],
        proposals: [],
        pipelineLedger: [], // Persistent Cognitive state across steps
        currentSessionId: '',
        memory: { sessions: [], artifacts: [] },
        customAgents: Array.isArray(vgtaConfig.customAgents) ? vgtaConfig.customAgents : [],
    };

    const core = window.VGTAstraCore;
    const pickModel = (preferred) => core.pickModel(config, preferred);
    const getReasoningDefault = (modelId) => core.getReasoningDefault(config, modelId);
    const normalizeReasoning = (modelId, requested) => core.normalizeReasoning(config, modelId, requested);
    const createTextElement = core.createTextElement;
    const appendModelOptions = (select, selectedModel) => core.appendModelOptions(config, select, selectedModel);
    const appendRoleOptions = (select, selectedRole) => core.appendRoleOptions(config, select, selectedRole);
    const appendReasoningOptions = (select, modelId, selectedEffort) => core.appendReasoningOptions(config, select, modelId, selectedEffort);
    const postForm = (formData) => core.postForm(config, formData);
    const formatAjaxError = core.formatAjaxError;
    const getLoopCount = () => core.getLoopCount(nodes);
    const updateMetricsRow = (usage, latencyMs) => core.updateMetricsRow(nodes, createTextElement, usage, latencyMs);
    let memoryController = null;
    let forgeController = null;

    const defaultModel = pickModel('openai/gpt-oss-120b');
    const compactModel = pickModel('openai/gpt-oss-20b');
    const auditModel = pickModel('qwen/qwen3-32b');

    let workflowSteps = [
        {
            role: 'Architect',
            model: defaultModel,
            reasoning_effort: getReasoningDefault(defaultModel),
            instructions: 'Entwerfe die Architektur und Akzeptanzkriterien. Schreibe keinen Code.',
        },
        {
            role: 'Developer',
            model: compactModel,
            reasoning_effort: getReasoningDefault(compactModel),
            instructions: 'Setze den freigegebenen Architekturplan in vollständige FILE_WRITE Patches um.',
        },
        {
            role: 'Auditor',
            model: auditModel,
            reasoning_effort: getReasoningDefault(auditModel),
            instructions: 'Prüfe die Patches adversarial. Nutze PIPELINE_STATUS: APPROVED oder PIPELINE_STATUS: NEEDS_REVISION.',
        },
    ];

    const nodes = {
        apiKeyInput: document.getElementById('vgta-api-key'),
        btnSaveCredentials: document.getElementById('vgta-btn-save-credentials'),
        pluginSelect: document.getElementById('vgta-plugin-select'),
        btnGenerateMap: document.getElementById('vgta-btn-generate-map'),
        mapTree: document.getElementById('vgta-map-tree'),
        chatModel: document.getElementById('vgta-chat-model'),
        chatReasoning: document.getElementById('vgta-chat-reasoning'),
        useGrounding: document.getElementById('vgta-use-grounding'),
        groundingMode: document.getElementById('vgta-grounding-mode'),
        groundingSources: document.getElementById('vgta-grounding-sources'),
        groundingDomains: document.getElementById('vgta-grounding-domains'),
        btnClearGroundingCache: document.getElementById('vgta-btn-clear-grounding-cache'),
        chatLog: document.getElementById('vgta-chat-log'),
        chatInput: document.getElementById('vgta-chat-input'),
        btnSendChat: document.getElementById('vgta-btn-send-chat'),
        metricsDisplay: document.getElementById('vgta-metrics-display'),
        globalPrompt: document.getElementById('vgta-global-prompt'),
        loopCount: document.getElementById('vgta-loop-count'),
        stopMode: document.getElementById('vgta-stop-mode'),
        btnAddAgent: document.getElementById('vgta-btn-add-agent'),
        agentStepsList: document.getElementById('vgta-agent-steps-list'),
        btnStartOrchestration: document.getElementById('vgta-btn-start-orchestration'),
        btnAbortOrchestration: document.getElementById('vgta-btn-abort-orchestration'),
        patchList: document.getElementById('vgta-patch-list'),
        btnClearPatches: document.getElementById('vgta-btn-clear-patches'),
        btnNewChat: document.getElementById('vgta-btn-new-chat'),
        memorySessions: document.getElementById('vgta-memory-sessions'),
        memoryArtifacts: document.getElementById('vgta-memory-artifacts'),
        agentBlueprintPreview: document.getElementById('vgta-agent-blueprint-preview'),
        customAgentList: document.getElementById('vgta-custom-agent-list'),
    };

    function renderStepsConfig() {
        window.VGTAstraSteps.renderStepsConfig({
            workflowSteps,
            nodes,
            createTextElement,
            appendRoleOptions,
            appendModelOptions,
            normalizeReasoning,
            appendReasoningOptions,
            rerender: renderStepsConfig,
        });
    }

    nodes.btnSaveCredentials.addEventListener('click', () => {
        const apiKey = nodes.apiKeyInput.value.trim();
        if (!apiKey) {
            return;
        }

        const formData = new FormData();
        formData.append('action', 'vgta_save_credentials');
        formData.append('nonce', config.nonce);
        formData.append('api_key', apiKey);

        nodes.btnSaveCredentials.disabled = true;
        postForm(formData)
            .then((response) => {
                if (response.success) {
                    nodes.apiKeyInput.value = '';
                    appendPlainMessage('system', 'VGTAstra Vault', response.data.message);
                } else {
                    appendPlainMessage('system error', 'Credential Error', formatAjaxError(response.data));
                }
            })
            .catch((error) => appendPlainMessage('system error', 'Network Error', error.message))
            .finally(() => {
                nodes.btnSaveCredentials.disabled = false;
            });
    });

    nodes.pluginSelect.addEventListener('change', () => {
        config.activePlugin = nodes.pluginSelect.value;
        nodes.btnGenerateMap.disabled = config.activePlugin === '';
        nodes.btnStartOrchestration.disabled = true;
        config.proposals = [];
        config.currentSessionId = '';
        config.chatHistory = [];
        config.pipelineLedger = [];
        memoryController.resetChatSurface();
        renderPatchVault();
        memoryController.loadMemory();
    });

    nodes.btnGenerateMap.addEventListener('click', () => {
        if (!config.activePlugin) {
            return;
        }

        appendPlainMessage('system', 'Cognitive Structural Analyzer', `Building deep analysis map using GPT-OSS for ${config.activePlugin}.`);
        nodes.btnGenerateMap.disabled = true;
        showLoadingIndicator('Cognitive Structural Analyzer is analyzing plugin codebase...');

        const formData = new FormData();
        formData.append('action', 'vgta_generate_plugin_map');
        formData.append('nonce', config.nonce);
        formData.append('plugin_slug', config.activePlugin);

        postForm(formData)
            .then((response) => {
                removeLoadingIndicator();
                if (response.success) {
                    renderStructuralMap(response.data.map);
                    updateProposals(response.data.proposals || []);
                    appendPlainMessage('system', 'Cognitive Structural Analyzer', response.data.message);
                    nodes.btnStartOrchestration.disabled = false;
                    memoryController.loadMemory();
                } else {
                    appendPlainMessage('system error', 'Map Error', formatAjaxError(response.data));
                }
            })
            .catch((error) => {
                removeLoadingIndicator();
                appendPlainMessage('system error', 'Network Error', error.message);
            })
            .finally(() => {
                nodes.btnGenerateMap.disabled = config.activePlugin === '';
            });
    });

    nodes.btnSendChat.addEventListener('click', sendChatMessage);
    nodes.btnNewChat.addEventListener('click', () => {
        config.currentSessionId = '';
        config.chatHistory = [];
        config.pipelineLedger = [];
        memoryController.resetChatSurface();
    });
    nodes.chatInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && (event.ctrlKey || event.metaKey)) {
            event.preventDefault();
            sendChatMessage();
        }
    });

    function sendChatMessage() {
        const message = nodes.chatInput.value.trim();
        if (!message) {
            return;
        }

        appendPlainMessage('user', 'Operator', message);
        config.chatHistory.push({ role: 'user', content: message });
        nodes.chatInput.value = '';
        nodes.btnSendChat.disabled = true;
        showLoadingIndicator(`Computing response using ${nodes.chatModel.value}...`);
        const startTime = performance.now();

        const formData = new FormData();
        formData.append('action', 'vgta_chat_message');
        formData.append('nonce', config.nonce);
        formData.append('plugin_slug', config.activePlugin);
        formData.append('model', nodes.chatModel.value);
        formData.append('reasoning_effort', normalizeReasoning(nodes.chatModel.value, nodes.chatReasoning.value));
        formData.append('message', message);
        formData.append('history', JSON.stringify(config.chatHistory.slice(-24)));
        formData.append('pipeline_ledger', JSON.stringify(config.pipelineLedger));
        formData.append('session_id', config.currentSessionId);
        formData.append('use_grounding', nodes.useGrounding.checked ? '1' : '');
        formData.append('grounding_mode', nodes.groundingMode.value);
        formData.append('grounding_sources', nodes.groundingSources.value);
        formData.append('grounding_domains', nodes.groundingDomains.value);

        postForm(formData)
            .then((response) => {
                removeLoadingIndicator();
                if (response.success) {
                    const payload = response.data;
                    appendRichAssistantMessage(payload.role, payload.model, payload.content, payload.reasoning);
                    config.chatHistory.push({ role: 'assistant', content: payload.content });
                    config.currentSessionId = String(payload.session_id || config.currentSessionId);
                    updateMetricsRow(payload.usage || {}, Math.round(performance.now() - startTime));
                    updateProposals(payload.proposals || config.proposals);
                    memoryController.renderMemoryData(payload.memory || config.memory);
                    if (payload.memory_warning) {
                        appendPlainMessage('system', 'Memory Guard', String(payload.memory_warning));
                    }
                    forgeController.renderGroundingPack(payload.grounding_pack);
                    forgeController.renderBlueprint(payload.agent_blueprint);
                } else {
                    appendPlainMessage('system error', 'Chat Error', formatAjaxError(response.data));
                }
            })
            .catch((error) => {
                removeLoadingIndicator();
                appendPlainMessage('system error', 'Network Error', error.message);
            })
            .finally(() => {
                nodes.btnSendChat.disabled = false;
            });
    }

    nodes.btnAddAgent.addEventListener('click', () => {
        workflowSteps.push({
            role: 'Integrator',
            model: compactModel,
            reasoning_effort: getReasoningDefault(compactModel),
            instructions: 'Integriere den aktuellen Pipeline-Stand und entscheide den nächsten minimalen Schritt.',
        });
        renderStepsConfig();
    });

    nodes.btnStartOrchestration.addEventListener('click', () => {
        if (workflowSteps.length === 0 || !config.activePlugin) {
            return;
        }

        config.isExecuting = true;
        config.pipelineLedger = []; // Reset Ledger state for the new orchestration run
        nodes.btnStartOrchestration.classList.add('is-hidden');
        nodes.btnAbortOrchestration.classList.remove('is-hidden');
        nodes.btnGenerateMap.disabled = true;
        nodes.pluginSelect.disabled = true;

        appendPlainMessage('system', 'Pipeline', `Started ${workflowSteps.length} roles for up to ${getLoopCount()} loops.`);
        executeWorkflowCycle(0, 0);
    });

    nodes.btnAbortOrchestration.addEventListener('click', () => {
        config.isExecuting = false;
        resetExecutionControls();
        removeLoadingIndicator();
        appendPlainMessage('system', 'Pipeline', 'Execution stopped by operator.');
    });

    function executeWorkflowCycle(loopIndex, stepIndex) {
        if (!config.isExecuting) {
            return;
        }

        if (loopIndex >= getLoopCount()) {
            appendPlainMessage('system', 'Pipeline', 'Max loop count reached.');
            config.isExecuting = false;
            resetExecutionControls();
            return;
        }

        if (stepIndex >= workflowSteps.length) {
            window.setTimeout(() => executeWorkflowCycle(loopIndex + 1, 0), 400);
            return;
        }

        const stepData = workflowSteps[stepIndex];
        appendPlainMessage('system', 'Pipeline', `Loop ${loopIndex + 1}/${getLoopCount()} - ${stepData.role} - ${stepData.model} - ${stepData.reasoning_effort}.`);
        showLoadingIndicator(`Active Agent: ${stepData.role} processing via ${stepData.model}...`);
        const startTime = performance.now();

        const formData = new FormData();
        formData.append('action', 'vgta_execute_agent_step');
        formData.append('nonce', config.nonce);
        formData.append('plugin_slug', config.activePlugin);
        formData.append('step_index', String(stepIndex));
        formData.append('loop_index', String(loopIndex + 1));
        formData.append('steps', JSON.stringify(workflowSteps));
        
        // Pipeline agents now synchronized with live chat conversation history
        formData.append('history', JSON.stringify(config.chatHistory.slice(-24)));
        formData.append('pipeline_ledger', JSON.stringify(config.pipelineLedger));
        formData.append('global_prompt', nodes.globalPrompt.value);
        formData.append('session_id', config.currentSessionId);

        postForm(formData)
            .then((response) => {
                removeLoadingIndicator();
                if (!config.isExecuting) {
                    return;
                }

                if (response.success) {
                    const payload = response.data;
                    appendRichAssistantMessage(payload.role, payload.model, payload.content, payload.reasoning);
                    
                    config.chatHistory.push({
                        role: 'assistant',
                        content: `[Pipeline ${payload.role} - Loop ${loopIndex + 1}]\n${String(payload.content || '').slice(0, 1200)}`,
                    });
                    config.currentSessionId = String(payload.session_id || config.currentSessionId);

                    const ledgerEntry = payload.memory_entry && typeof payload.memory_entry === 'object'
                        ? payload.memory_entry
                        : {
                            role: payload.role,
                            model: payload.model,
                            loop: loopIndex + 1,
                            content: String(payload.content || '').slice(0, 6000),
                        };
                    config.pipelineLedger.push(ledgerEntry);
                    
                    updateMetricsRow(payload.usage || {}, Math.round(performance.now() - startTime));
                    updateProposals(payload.proposals || config.proposals);
                    memoryController.renderMemoryData(payload.memory || config.memory);
                    if (payload.memory_warning) {
                        appendPlainMessage('system', 'Memory Guard', String(payload.memory_warning));
                    }

                    if (payload.role === 'Repair') {
                        const repairAction = String(payload.repair_action || 'operator_required');
                        appendPlainMessage('system', 'Repair Agent', `Recovery action: ${repairAction}.`);
                        if (payload.continue_pipeline === false || repairAction === 'operator_required' || repairAction === 'abort') {
                            config.isExecuting = false;
                            resetExecutionControls();
                            return;
                        }

                        if (repairAction === 'reduce_context') {
                            config.chatHistory = config.chatHistory.slice(-8);
                            config.pipelineLedger = config.pipelineLedger.slice(-8);
                        }

                        if (repairAction === 'retry' || repairAction === 'reduce_context' || repairAction === 'prune_memory') {
                            window.setTimeout(() => executeWorkflowCycle(loopIndex, stepIndex), 400);
                            return;
                        }
                    }

                    if (nodes.stopMode.value === 'approval' && payload.pipeline_status === 'APPROVED') {
                        appendPlainMessage('system', 'Pipeline', 'Auditor approved. Pipeline stopped.');
                        config.isExecuting = false;
                        resetExecutionControls();
                        return;
                    }

                    window.setTimeout(() => executeWorkflowCycle(loopIndex, stepIndex + 1), 400);
                } else {
                    appendPlainMessage('system error', 'Pipeline Error', formatAjaxError(response.data));
                    config.isExecuting = false;
                    resetExecutionControls();
                }
            })
            .catch((error) => {
                removeLoadingIndicator();
                appendPlainMessage('system error', 'Network Error', error.message);
                config.isExecuting = false;
                resetExecutionControls();
            });
    }

    function resetExecutionControls() {
        nodes.btnStartOrchestration.classList.remove('is-hidden');
        nodes.btnAbortOrchestration.classList.add('is-hidden');
        nodes.btnGenerateMap.disabled = config.activePlugin === '';
        nodes.pluginSelect.disabled = false;
    }

    function showLoadingIndicator(text) {
        removeLoadingIndicator();
        const indicator = document.createElement('div');
        indicator.id = 'vgta-active-thinking-loader';
        indicator.className = 'vgta-thinking-loader';
        
        const pulse = document.createElement('div');
        pulse.className = 'vgta-thinking-pulse';
        indicator.appendChild(pulse);
        
        const label = document.createElement('span');
        label.textContent = text;
        indicator.appendChild(label);
        
        nodes.chatLog.appendChild(indicator);
        nodes.chatLog.scrollTop = nodes.chatLog.scrollHeight;
    }

    function removeLoadingIndicator() {
        const indicator = document.getElementById('vgta-active-thinking-loader');
        if (indicator) {
            indicator.remove();
        }
    }

    function renderStructuralMap(mapData) {
        window.VGTAstraRenderers.renderStructuralMap(mapData, nodes, createTextElement);
    }

    function appendPlainMessage(kind, meta, content) {
        window.VGTAstraRenderers.appendPlainMessage(kind, meta, content, nodes, createTextElement);
    }

    function appendRichAssistantMessage(role, model, content, reasoning) {
        window.VGTAstraRenderers.appendRichAssistantMessage(role, model, content, reasoning, nodes, createTextElement);
    }

    function updateProposals(proposals) {
        config.proposals = Array.isArray(proposals) ? proposals : [];
        renderPatchVault();
    }

    function renderPatchVault() {
        window.VGTAstraPatchReview.renderPatchVault(config, nodes, createTextElement, preparePatchReview);
    }

    function preparePatchReview(proposalId) {
        if (!config.activePlugin || !proposalId) {
            return;
        }

        const formData = new FormData();
        formData.append('action', 'vgta_prepare_patch_review');
        formData.append('nonce', config.nonce);
        formData.append('plugin_slug', config.activePlugin);
        formData.append('proposal_id', proposalId);

        postForm(formData)
            .then((response) => {
                if (response.success) {
                    window.VGTAstraPatchReview.openPatchReviewModal(response.data, createTextElement, commitProposal);
                } else {
                    appendPlainMessage('system error', 'Patch Review Error', formatAjaxError(response.data));
                }
            })
            .catch((error) => appendPlainMessage('system error', 'Network Error', error.message));
    }

    function commitProposal(proposalId, reviewToken) {
        if (!config.activePlugin || !proposalId || !reviewToken) {
            return;
        }

        const formData = new FormData();
        formData.append('action', 'vgta_commit_staged_patch');
        formData.append('nonce', config.nonce);
        formData.append('plugin_slug', config.activePlugin);
        formData.append('proposal_id', proposalId);
        formData.append('review_token', reviewToken);

        postForm(formData)
            .then((response) => {
                if (response.success) {
                    window.VGTAstraPatchReview.closePatchReviewModal();
                    appendPlainMessage('system', 'Patch Vault', response.data.message);
                    updateProposals(response.data.proposals || []);
                } else {
                    appendPlainMessage('system error', 'Patch Error', formatAjaxError(response.data));
                }
            })
            .catch((error) => appendPlainMessage('system error', 'Network Error', error.message));
    }

    nodes.btnClearPatches.addEventListener('click', () => {
        if (!config.activePlugin) {
            return;
        }

        const formData = new FormData();
        formData.append('action', 'vgta_clear_patch_vault');
        formData.append('nonce', config.nonce);
        formData.append('plugin_slug', config.activePlugin);

        postForm(formData)
            .then((response) => {
                if (response.success) {
                    appendPlainMessage('system', 'Patch Vault', response.data.message);
                    updateProposals([]);
                } else {
                    appendPlainMessage('system error', 'Patch Vault Error', formatAjaxError(response.data));
                }
            })
            .catch((error) => appendPlainMessage('system error', 'Network Error', error.message));
    });

    appendModelOptions(nodes.chatModel, compactModel);
    appendReasoningOptions(nodes.chatReasoning, compactModel, getReasoningDefault(compactModel));
    nodes.chatModel.addEventListener('change', () => {
        appendReasoningOptions(nodes.chatReasoning, nodes.chatModel.value, nodes.chatReasoning.value);
    });
    memoryController = window.VGTAstraMemory.createController({
        config,
        nodes,
        postForm,
        formatAjaxError,
        createTextElement,
        appendPlainMessage,
    });
    forgeController = window.VGTAstraAgentForge.createController({
        config,
        nodes,
        postForm,
        formatAjaxError,
        createTextElement,
        appendPlainMessage,
        rerenderSteps: renderStepsConfig,
    });
    renderStepsConfig();
    renderPatchVault();
    memoryController.loadMemory();
    forgeController.loadAgents();
});
