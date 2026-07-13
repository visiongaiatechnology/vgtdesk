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
        modelAliases: vgtaConfig.modelAliases && typeof vgtaConfig.modelAliases === 'object' ? vgtaConfig.modelAliases : {},
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
        sessionCost: 0.0,
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
    const confirmAction = core.confirmAction;
    const showNotice = core.showNotice;
    const formatAjaxError = core.formatAjaxError;
    const getLoopCount = () => core.getLoopCount(nodes);
    const updateMetricsRow = (usage, latencyMs) => core.updateMetricsRow(nodes, createTextElement, usage, latencyMs, config.sessionCost);
    const updateContextMeter = (contextUsage) => core.updateContextMeter(nodes, createTextElement, contextUsage);
    let memoryController = null;
    let forgeController = null;

    const defaultModel = pickModel('architect_default');
    const compactModel = pickModel('compact_default');
    const auditModel = pickModel('audit_default');

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
        providerSelect: document.getElementById('vgta-provider-select'),
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
        contextMeter: document.getElementById('vgta-context-meter'),
        globalPrompt: document.getElementById('vgta-global-prompt'),
        loopCount: document.getElementById('vgta-loop-count'),
        stopMode: document.getElementById('vgta-stop-mode'),
        btnAddAgent: document.getElementById('vgta-btn-add-agent'),
        agentStepsList: document.getElementById('vgta-agent-steps-list'),
        btnStartOrchestration: document.getElementById('vgta-btn-start-orchestration'),
        btnAbortOrchestration: document.getElementById('vgta-btn-abort-orchestration'),
        patchList: document.getElementById('vgta-patch-list'),
        btnReviewBundle: document.getElementById('vgta-btn-review-bundle'),
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
        const provider = nodes.providerSelect ? nodes.providerSelect.value : 'groq';

        const formData = new FormData();
        formData.append('action', 'vgta_save_credentials');
        formData.append('nonce', config.nonce);
        formData.append('api_key', apiKey);
        formData.append('provider', provider);

        nodes.btnSaveCredentials.disabled = true;
        postForm(formData)
            .then((response) => {
                if (response.success) {
                    nodes.apiKeyInput.value = '';
                    appendPlainMessage('system', 'VGTAstra Vault', response.data.message);
                    window.setTimeout(() => window.location.reload(), 1500);
                } else {
                    appendAjaxError('Credential Error', response.data);
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
                    appendAjaxError('Map Error', response.data);
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
                    config.sessionCost += Number(payload.usage ? payload.usage.cost || 0 : 0);
                    updateMetricsRow(payload.usage || {}, Math.round(performance.now() - startTime));
                    updateContextMeter(payload.context_usage || null);
                    updateProposals(payload.proposals || config.proposals);
                    memoryController.renderMemoryData(payload.memory || config.memory);
                    if (payload.memory_warning) {
                        appendPlainMessage('system', 'Memory Guard', String(payload.memory_warning));
                    }
                    forgeController.renderGroundingPack(payload.grounding_pack);
                    forgeController.renderBlueprint(payload.agent_blueprint);
                } else {
                    appendAjaxError('Chat Error', response.data);
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
                    
                    config.sessionCost += Number(payload.usage ? payload.usage.cost || 0 : 0);
                    updateMetricsRow(payload.usage || {}, Math.round(performance.now() - startTime));
                    updateContextMeter(payload.context_usage || null);
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
                    appendAjaxError('Pipeline Error', response.data);
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

    function appendAjaxError(meta, data) {
        appendPlainMessage('system error', meta, formatAjaxError(data));
        if (!data || typeof data !== 'object' || typeof data.code !== 'string' || data.code.trim() === '') {
            return;
        }

        const box = document.createElement('div');
        box.className = 'vgta-message system error';
        box.appendChild(createTextElement('div', 'vgta-message-meta', 'VGTAstra Error Diagnostic'));
        const body = document.createElement('div');
        body.className = 'vgta-message-body';
        body.appendChild(createTextElement('div', 'vgta-response-content', `Fehlercode ${data.code} kann mit Server-Kontext analysiert werden.`));
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'vgta-btn secondary tiny';
        button.textContent = 'ANALYZE ERROR';
        button.addEventListener('click', () => analyzeError(data.code, button));
        body.appendChild(button);
        box.appendChild(body);
        nodes.chatLog.appendChild(box);
        nodes.chatLog.scrollTop = nodes.chatLog.scrollHeight;
    }

    function analyzeError(errorCode, button) {
        const formData = new FormData();
        formData.append('action', 'vgta_analyze_error');
        formData.append('nonce', config.nonce);
        formData.append('plugin_slug', config.activePlugin);
        formData.append('error_code', errorCode);
        button.disabled = true;
        button.textContent = 'ANALYZING';
        postForm(formData)
            .then((response) => {
                if (response.success) {
                    const payload = response.data;
                    appendRichAssistantMessage(payload.role || 'Repair', payload.model || 'diagnostic', payload.content || '', payload.reasoning || '');
                    updateMetricsRow(payload.usage || {}, 0);
                    updateContextMeter(payload.context_usage || null);
                } else {
                    appendPlainMessage('system error', 'Diagnostic Error', formatAjaxError(response.data));
                }
            })
            .catch((error) => appendPlainMessage('system error', 'Diagnostic Network Error', error.message))
            .finally(() => {
                button.disabled = false;
                button.textContent = 'ANALYZE ERROR';
            });
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

    function preparePatchBundleReview() {
        const formData = new FormData();
        formData.append('action', 'vgta_prepare_patch_bundle_review');
        formData.append('nonce', config.nonce);
        formData.append('plugin_slug', config.activePlugin);

        postForm(formData)
            .then((response) => {
                if (response.success) {
                    window.VGTAstraPatchReview.openPatchReviewModal(response.data, createTextElement, commitProposal);
                } else {
                    appendAjaxError('Patch Bundle Review Error', response.data);
                }
            })
            .catch((error) => appendPlainMessage('system error', 'Network Error', error.message));
    }

    function preparePatchReview(proposalId) {
        if (!proposalId) {
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
                    appendAjaxError('Patch Review Error', response.data);
                }
            })
            .catch((error) => appendPlainMessage('system error', 'Network Error', error.message));
    }

    function commitProposal(filesOrProposalId, reviewToken) {
        const files = Array.isArray(filesOrProposalId)
            ? filesOrProposalId
            : [{ proposal_id: filesOrProposalId, review_token: reviewToken }];
        const selectedFiles = files.filter((file) => file && file.proposal_id && file.review_token);
        if (selectedFiles.length === 0) {
            return;
        }

        confirmAction({
            title: 'Patch Commit Approval',
            message: `Commit ${selectedFiles.length} reviewed file(s)? This writes the staged code after token validation.`,
            detail: selectedFiles.map((file) => String(file.path || file.proposal_id)).join('\n'),
            confirmLabel: 'COMMIT',
            cancelLabel: 'CANCEL',
            danger: false,
        }).then((approved) => {
            if (!approved) {
                return;
            }
            commitSelectedFiles(selectedFiles);
        });
    }

    function commitSelectedFiles(selectedFiles) {
        const commitNext = (index) => {
            if (index >= selectedFiles.length) {
                window.VGTAstraPatchReview.closePatchReviewModal();
                appendPlainMessage('system', 'Patch Vault', `${selectedFiles.length} approved file(s) committed or workspace-staged.`);
                return Promise.resolve();
            }

            const file = selectedFiles[index];
            const formData = new FormData();
            formData.append('action', 'vgta_commit_staged_patch');
            formData.append('nonce', config.nonce);
            formData.append('plugin_slug', config.activePlugin);
            formData.append('proposal_id', file.proposal_id);
            formData.append('review_token', file.review_token);

            return postForm(formData).then((response) => {
                if (!response.success) {
                    appendAjaxError('Patch Error', response.data);
                    return Promise.reject(new Error('Patch commit rejected.'));
                }
                updateProposals(response.data.proposals || []);
                return commitNext(index + 1);
            });
        };

        commitNext(0).catch((error) => appendPlainMessage('system error', 'Patch Commit Halted', error.message));
    }

    if (nodes.btnReviewBundle) {
        nodes.btnReviewBundle.addEventListener('click', preparePatchBundleReview);
    }

    nodes.btnClearPatches.addEventListener('click', () => {
        confirmAction({
            title: 'Clear Patch Vault',
            message: 'Remove all staged patch proposals for the current Astra scope?',
            detail: `${config.proposals.length} proposal(s) staged. This does not touch committed files.`,
            confirmLabel: 'CLEAR',
            cancelLabel: 'CANCEL',
            danger: true,
        }).then((approved) => {
            if (!approved) {
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
                        appendAjaxError('Patch Vault Error', response.data);
                    }
                })
                .catch((error) => appendPlainMessage('system error', 'Network Error', error.message));
        });
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
    void showNotice;
    forgeController = window.VGTAstraAgentForge.createController({
        config,
        nodes,
        postForm,
        formatAjaxError,
        createTextElement,
        appendPlainMessage,
        rerenderSteps: renderStepsConfig,
    });
    if (window.VGTAstraLayout && typeof window.VGTAstraLayout.init === 'function') {
        window.VGTAstraLayout.init(nodes);
    }
    renderStepsConfig();
    renderPatchVault();
    updateContextMeter(null);
    memoryController.loadMemory();
    forgeController.loadAgents();
});
