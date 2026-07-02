/**
 * STATUS: DIAMANT VGT SUPREME
 * VGTAstra Agent Forge and Grounding UI.
 */

window.VGTAstraAgentForge = (() => {
    'use strict';

    function createController(api) {
        const { config, nodes, postForm, formatAjaxError, createTextElement, appendPlainMessage, rerenderSteps } = api;
        let currentBlueprint = null;
        let blueprintEditor = null;

        function renderBlueprint(blueprint) {
            currentBlueprint = blueprint && typeof blueprint === 'object' ? blueprint : null;
            nodes.agentBlueprintPreview.replaceChildren();
            if (!currentBlueprint) {
                nodes.agentBlueprintPreview.appendChild(createTextElement('div', 'vgta-placeholder-text', 'No agent blueprint pending.'));
                return;
            }

            nodes.agentBlueprintPreview.appendChild(createTextElement('div', 'vgta-forge-title', String(currentBlueprint.label || currentBlueprint.id || 'Custom Agent')));
            nodes.agentBlueprintPreview.appendChild(createTextElement('div', 'vgta-forge-meta', `Role: ${currentBlueprint.role_type || 'Assistant'} | Model: ${currentBlueprint.model || ''}`));
            nodes.agentBlueprintPreview.appendChild(createTextElement('div', `vgta-risk ${String(currentBlueprint.risk_level || 'LOW').toLowerCase()}`, `Risk: ${currentBlueprint.risk_level || 'LOW'}`));
            nodes.agentBlueprintPreview.appendChild(createTextElement('div', 'vgta-forge-meta', `Tools: ${(currentBlueprint.allowed_tools || []).join(', ') || 'none'}`));
            nodes.agentBlueprintPreview.appendChild(createTextElement('pre', 'vgta-forge-prompt', String(currentBlueprint.system_prompt || '')));
            blueprintEditor = document.createElement('textarea');
            blueprintEditor.className = 'vgta-textarea compact';
            blueprintEditor.value = JSON.stringify(currentBlueprint, null, 2);
            blueprintEditor.addEventListener('input', () => {
                try {
                    currentBlueprint = JSON.parse(blueprintEditor.value);
                } catch (error) {
                    currentBlueprint = null;
                }
            });
            nodes.agentBlueprintPreview.appendChild(blueprintEditor);

            const actions = document.createElement('div');
            actions.className = 'vgta-forge-actions';
            const register = document.createElement('button');
            register.type = 'button';
            register.className = 'vgta-btn success tiny';
            register.textContent = 'REGISTER';
            register.addEventListener('click', registerBlueprint);
            const cancel = document.createElement('button');
            cancel.type = 'button';
            cancel.className = 'vgta-btn secondary tiny';
            cancel.textContent = 'CANCEL';
            cancel.addEventListener('click', () => renderBlueprint(null));
            actions.append(register, cancel);
            nodes.agentBlueprintPreview.appendChild(actions);
        }

        function renderAgents(agents) {
            config.customAgents = Array.isArray(agents) ? agents : [];
            nodes.customAgentList.replaceChildren();
            if (config.customAgents.length === 0) {
                nodes.customAgentList.appendChild(createTextElement('div', 'vgta-placeholder-text', 'No custom agents registered.'));
                return;
            }

            config.customAgents.forEach((agent) => {
                const row = document.createElement('div');
                row.className = 'vgta-custom-agent-row';
                row.appendChild(createTextElement('div', 'vgta-forge-title', String(agent.label || agent.id)));
                row.appendChild(createTextElement('div', 'vgta-forge-meta', `${agent.id} | ${agent.role_type} | ${agent.risk_level}`));
                const remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'vgta-btn danger tiny';
                remove.textContent = 'DELETE';
                remove.addEventListener('click', () => deleteAgent(String(agent.id || '')));
                row.appendChild(remove);
                nodes.customAgentList.appendChild(row);
            });
        }

        function registerBlueprint() {
            if (!currentBlueprint) {
                appendPlainMessage('system error', 'Agent Forge Error', 'Blueprint JSON is invalid.');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'vgta_register_agent_blueprint');
            formData.append('nonce', config.nonce);
            formData.append('blueprint', JSON.stringify(currentBlueprint));
            postForm(formData).then((response) => {
                if (!response.success) {
                    appendPlainMessage('system error', 'Agent Forge Error', formatAjaxError(response.data));
                    return;
                }

                config.roles = Array.isArray(response.data.roles) ? response.data.roles : config.roles;
                renderAgents(response.data.agents || []);
                renderBlueprint(null);
                rerenderSteps();
                appendPlainMessage('system', 'Agent Forge', response.data.message || 'Agent registered.');
            }).catch((error) => appendPlainMessage('system error', 'Agent Forge Network', error.message));
        }

        function deleteAgent(agentId) {
            if (!agentId) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'vgta_delete_custom_agent');
            formData.append('nonce', config.nonce);
            formData.append('agent_id', agentId);
            postForm(formData).then((response) => {
                if (!response.success) {
                    appendPlainMessage('system error', 'Agent Forge Error', formatAjaxError(response.data));
                    return;
                }

                config.roles = Array.isArray(response.data.roles) ? response.data.roles : config.roles;
                renderAgents(response.data.agents || []);
                rerenderSteps();
                appendPlainMessage('system', 'Agent Forge', response.data.message || 'Custom agent deleted.');
            }).catch((error) => appendPlainMessage('system error', 'Agent Forge Network', error.message));
        }

        function loadAgents() {
            const formData = new FormData();
            formData.append('action', 'vgta_list_custom_agents');
            formData.append('nonce', config.nonce);
            postForm(formData).then((response) => {
                if (response.success) {
                    config.roles = Array.isArray(response.data.roles) ? response.data.roles : config.roles;
                    renderAgents(response.data.agents || []);
                    rerenderSteps();
                }
            }).catch(() => renderAgents(config.customAgents || []));
        }

        function renderGroundingPack(pack) {
            if (!pack || typeof pack !== 'object' || !Array.isArray(pack.sources) || pack.sources.length === 0) {
                return;
            }

            const lines = pack.sources.map((source) => `${source.id}: ${source.domain}`).join('\n');
            appendPlainMessage('system', 'Grounding used', lines);
        }

        function clearGroundingCache() {
            const formData = new FormData();
            formData.append('action', 'vgta_clear_grounding_cache');
            formData.append('nonce', config.nonce);
            postForm(formData).then((response) => {
                appendPlainMessage(response.success ? 'system' : 'system error', 'Grounding Broker', response.success ? response.data.message : formatAjaxError(response.data));
            }).catch((error) => appendPlainMessage('system error', 'Grounding Broker', error.message));
        }

        nodes.btnClearGroundingCache.addEventListener('click', clearGroundingCache);
        renderAgents(config.customAgents || []);
        renderBlueprint(null);

        return { renderBlueprint, renderGroundingPack, loadAgents };
    }

    return { createController };
})();
