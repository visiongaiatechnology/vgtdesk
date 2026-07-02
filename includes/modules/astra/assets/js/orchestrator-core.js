/**
 * STATUS: DIAMANT VGT SUPREME
 * VGTAstra Agent System - dependency-free client core helpers.
 */

window.VGTAstraCore = (() => {
    'use strict';

    function pickModel(config, preferred) {
        return config.models.some((model) => model.id === preferred)
            ? preferred
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
        }).then((response) => response.json());
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

    function updateMetricsRow(nodes, createElement, usage, latencyMs) {
        const promptTokens = Number(usage.prompt_tokens || 0);
        const completionTokens = Number(usage.completion_tokens || 0);
        const cachedTokens = usage.prompt_tokens_details ? Number(usage.prompt_tokens_details.cached_tokens || 0) : 0;
        const hitPercent = promptTokens > 0 ? Math.round((cachedTokens / promptTokens) * 100) : 0;
        const speed = latencyMs > 0 ? Math.round((completionTokens / latencyMs) * 1000) : 0;

        nodes.metricsDisplay.replaceChildren(
            createElement('span', '', `CACHE: ${hitPercent}% (${cachedTokens}/${promptTokens})`),
            createElement('span', '', `LATENCY: ${latencyMs}ms`),
            createElement('span', '', `SPEED: ${speed} tps`),
        );
    }

    return {
        pickModel,
        getReasoningDefault,
        normalizeReasoning,
        createTextElement,
        appendModelOptions,
        appendRoleOptions,
        appendReasoningOptions,
        postForm,
        formatAjaxError,
        getLoopCount,
        updateMetricsRow,
    };
})();
