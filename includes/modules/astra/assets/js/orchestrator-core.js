/**
 * STATUS: DIAMANT VGT SUPREME
 * VGTAstra Agent System - dependency-free client core helpers.
 */

window.VGTAstraCore = (() => {
    'use strict';

    function resolveModelAlias(config, alias) {
        const aliases = config && config.modelAliases && typeof config.modelAliases === 'object' ? config.modelAliases : {};
        return typeof aliases[alias] === 'string' ? aliases[alias] : alias;
    }

    function pickModel(config, preferred) {
        const resolved = resolveModelAlias(config, preferred);
        return config.models.some((model) => model.id === resolved)
            ? resolved
            : (config.models[0] ? config.models[0].id : '');
    }

    function getModelMeta(config, modelId) {
        return config.models.find((model) => model.id === modelId) || null;
    }

    function getReasoningValues(config, modelId) {
        const meta = getModelMeta(config, modelId);
        return meta && Array.isArray(meta.reasoningValues) ? meta.reasoningValues : [];
    }

    function getReasoningDefault(config, modelId) {
        const meta = getModelMeta(config, modelId);
        return meta && typeof meta.reasoningDefault === 'string' ? meta.reasoningDefault : 'none';
    }

    function normalizeReasoning(config, modelId, requested) {
        const values = getReasoningValues(config, modelId);
        if (values.length === 0) {
            return getReasoningDefault(config, modelId);
        }
        if (values.includes(requested)) {
            return requested;
        }
        if (['low', 'medium', 'high'].includes(requested) && values.includes('default')) {
            return 'default';
        }
        if (['default', 'none'].includes(requested) && values.includes('high')) {
            return 'high';
        }
        return getReasoningDefault(config, modelId);
    }

    function createTextElement(tagName, className, text) {
        const element = document.createElement(tagName);
        if (className) {
            element.className = className;
        }
        element.textContent = text;
        return element;
    }

    function appendModelOptions(config, select, selectedModel) {
        select.replaceChildren();
        config.models.forEach((model) => {
            const option = document.createElement('option');
            option.value = model.id;
            option.textContent = model.reasoning ? `${model.label} · reasoning` : model.label;
            option.selected = model.id === selectedModel;
            select.appendChild(option);
        });
    }

    function appendRoleOptions(config, select, selectedRole) {
        select.replaceChildren();
        config.roles.forEach((role) => {
            const option = document.createElement('option');
            option.value = role;
            option.textContent = role;
            option.selected = role === selectedRole;
            select.appendChild(option);
        });
    }

    function appendReasoningOptions(config, select, modelId, selectedEffort) {
        select.replaceChildren();
        const values = getReasoningValues(config, modelId);
        const normalized = normalizeReasoning(config, modelId, selectedEffort);

        if (values.length === 0) {
            const option = document.createElement('option');
            option.value = 'none';
            option.textContent = 'thinking unsupported';
            option.selected = true;
            select.appendChild(option);
            select.disabled = true;
            return;
        }

        select.disabled = false;
        values.forEach((effort) => {
            const option = document.createElement('option');
            option.value = effort;
            option.textContent = `thinking ${effort}`;
            option.selected = effort === normalized;
            select.appendChild(option);
        });
    }

    function postForm(config, formData) {
        return fetch(config.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: formData,
        }).then((response) => {
            return response.text().then((text) => {
                try {
                    return JSON.parse(text);
                } catch (error) {
                    const cleanText = text.trim();
                    if (cleanText.startsWith('<')) {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(cleanText, 'text/html');
                        const errorMsg = doc.body ? doc.body.textContent.trim() : '';
                        const slicedMsg = errorMsg.slice(0, 200).replace(/\s+/g, ' ');
                        return {
                            success: false,
                            data: {
                                message: `Server error (HTML): ${slicedMsg || 'HTML Page returned.'}`,
                                code: 'html_error'
                            }
                        };
                    }
                    return {
                        success: false,
                        data: {
                            message: `Invalid JSON response: ${cleanText.slice(0, 150)}`,
                            code: 'invalid_json'
                        }
                    };
                }
            });
        });
    }

    function openSystemModal(options) {
        const title = typeof options.title === 'string' && options.title.trim() !== '' ? options.title.trim() : 'VGTAstra';
        const message = typeof options.message === 'string' ? options.message : '';
        const detail = typeof options.detail === 'string' ? options.detail : '';
        const confirmLabel = typeof options.confirmLabel === 'string' && options.confirmLabel.trim() !== '' ? options.confirmLabel.trim() : 'OK';
        const cancelLabel = typeof options.cancelLabel === 'string' && options.cancelLabel.trim() !== '' ? options.cancelLabel.trim() : 'CANCEL';
        const requireConfirm = Boolean(options.requireConfirm);
        const danger = Boolean(options.danger);

        return new Promise((resolve) => {
            const overlay = document.createElement('div');
            overlay.className = 'vgta-system-modal-overlay';
            overlay.setAttribute('role', 'dialog');
            overlay.setAttribute('aria-modal', 'true');

            const modal = document.createElement('div');
            modal.className = danger ? 'vgta-system-modal danger' : 'vgta-system-modal';
            overlay.appendChild(modal);

            const header = document.createElement('div');
            header.className = 'vgta-system-modal-header';
            header.appendChild(createTextElement('div', 'vgta-system-modal-title', title));
            modal.appendChild(header);

            const body = document.createElement('div');
            body.className = 'vgta-system-modal-body';
            if (message !== '') {
                body.appendChild(createTextElement('p', 'vgta-system-modal-message', message));
            }
            if (detail !== '') {
                body.appendChild(createTextElement('pre', 'vgta-system-modal-detail', detail));
            }
            modal.appendChild(body);

            const actions = document.createElement('div');
            actions.className = 'vgta-system-modal-actions';
            const cancel = document.createElement('button');
            cancel.type = 'button';
            cancel.className = 'vgta-btn secondary';
            cancel.textContent = cancelLabel;
            actions.appendChild(cancel);

            const confirm = document.createElement('button');
            confirm.type = 'button';
            confirm.className = danger ? 'vgta-btn danger' : 'vgta-btn success';
            confirm.textContent = confirmLabel;
            actions.appendChild(confirm);
            modal.appendChild(actions);

            const close = (approved) => {
                document.removeEventListener('keydown', onKeydown);
                overlay.remove();
                resolve(Boolean(approved));
            };
            const onKeydown = (event) => {
                if (event.key === 'Escape') {
                    event.preventDefault();
                    close(false);
                }
            };

            cancel.addEventListener('click', () => close(false));
            confirm.addEventListener('click', () => close(true));
            overlay.addEventListener('click', (event) => {
                if (event.target === overlay) {
                    close(false);
                }
            });
            document.addEventListener('keydown', onKeydown);
            document.body.appendChild(overlay);
            (requireConfirm ? confirm : cancel).focus();
        });
    }

    function confirmAction(options) {
        return openSystemModal({ ...options, requireConfirm: true });
    }

    function showNotice(options) {
        return openSystemModal({ ...options, cancelLabel: 'CLOSE', confirmLabel: 'OK' });
    }

    function formatAjaxError(data) {
        if (!data || typeof data !== 'object') {
            return 'Unknown server response.';
        }

        const message = typeof data.message === 'string' && data.message.trim() !== ''
            ? data.message.trim()
            : 'Unknown server response.';
        const code = typeof data.code === 'string' && data.code.trim() !== ''
            ? data.code.trim()
            : '';

        return code ? `${message}\nCode: ${code}` : message;
    }

    function getLoopCount(nodes) {
        const parsed = Number.parseInt(nodes.loopCount.value, 10);
        if (Number.isNaN(parsed)) {
            return 1;
        }
        return Math.min(Math.max(parsed, 1), 12);
    }

    function formatNumber(value) {
        return new Intl.NumberFormat('de-DE').format(Number(value || 0));
    }

    function updateContextMeter(nodes, createElement, contextUsage) {
        if (!nodes.contextMeter) {
            return;
        }

        if (!contextUsage || typeof contextUsage !== 'object') {
            nodes.contextMeter.replaceChildren(createElement('span', '', 'CONTEXT: STANDBY'));
            return;
        }

        const percent = Math.min(Math.max(Number(contextUsage.percent || 0), 0), 100);
        const label = createElement('span', 'vgta-context-meter-label', `CONTEXT: ${percent}%`);
        const detail = createElement('span', 'vgta-context-meter-detail', `${formatNumber(contextUsage.reserved_total_estimated)} / ${formatNumber(contextUsage.max_context_tokens)} est. tokens`);
        const bar = document.createElement('div');
        bar.className = 'vgta-context-meter-bar';
        const fill = document.createElement('span');
        fill.style.width = `${percent}%`;
        bar.appendChild(fill);
        nodes.contextMeter.replaceChildren(label, bar, detail);
    }

    function updateMetricsRow(nodes, createElement, usage, latencyMs, sessionCost) {
        const promptTokens = Number(usage.prompt_tokens || 0);
        const completionTokens = Number(usage.completion_tokens || 0);
        const cachedTokens = usage.prompt_tokens_details ? Number(usage.prompt_tokens_details.cached_tokens || 0) : 0;
        const hitPercent = promptTokens > 0 ? Math.round((cachedTokens / promptTokens) * 100) : 0;
        const speed = latencyMs > 0 ? Math.round((completionTokens / latencyMs) * 1000) : 0;
        const currentCost = Number(usage.cost || 0);
        const cumCost = Number(sessionCost || 0);

        nodes.metricsDisplay.replaceChildren(
            createElement('span', '', `CACHE: ${hitPercent}% (${cachedTokens}/${promptTokens})`),
            createElement('span', '', `LATENCY: ${latencyMs}ms`),
            createElement('span', '', `SPEED: ${speed} tps`),
            createElement('span', 'vgta-cost-badge', `COST: $${currentCost.toFixed(5)}`),
            createElement('span', 'vgta-session-cost-badge', `SESSION COST: $${cumCost.toFixed(5)}`),
        );
    }

    return {
        resolveModelAlias,
        pickModel,
        getReasoningDefault,
        normalizeReasoning,
        createTextElement,
        appendModelOptions,
        appendRoleOptions,
        appendReasoningOptions,
        postForm,
        confirmAction,
        showNotice,
        formatAjaxError,
        getLoopCount,
        updateContextMeter,
        updateMetricsRow,
    };
})();
