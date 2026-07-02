/**
 * STATUS: DIAMANT VGT SUPREME
 * VGTAstra persistent memory and artifact UI.
 */

window.VGTAstraMemory = (() => {
    'use strict';

    function renderMemory(memory, nodes, createTextElement, handlers) {
        renderSessions(Array.isArray(memory.sessions) ? memory.sessions : [], nodes, createTextElement, handlers);
        renderArtifacts(Array.isArray(memory.artifacts) ? memory.artifacts : [], nodes, createTextElement, handlers);
    }

    function renderSessions(sessions, nodes, createTextElement, handlers) {
        nodes.memorySessions.replaceChildren();
        if (sessions.length === 0) {
            nodes.memorySessions.appendChild(createTextElement('div', 'vgta-placeholder-text', 'No saved chats.'));
            return;
        }

        sessions.forEach((session) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'vgta-memory-item';
            button.appendChild(createTextElement('span', 'title', String(session.title || 'Untitled chat')));
            button.appendChild(createTextElement('span', 'meta', `${String(session.message_count || 0)} messages - ${String(session.updated_at || '')}`));
            button.addEventListener('click', () => handlers.loadSession(String(session.id || '')));
            nodes.memorySessions.appendChild(button);
        });
    }

    function renderArtifacts(artifacts, nodes, createTextElement, handlers) {
        nodes.memoryArtifacts.replaceChildren();
        if (artifacts.length === 0) {
            nodes.memoryArtifacts.appendChild(createTextElement('div', 'vgta-placeholder-text', 'No artifacts stored.'));
            return;
        }

        artifacts.forEach((artifact) => {
            const row = document.createElement('div');
            row.className = 'vgta-memory-item artifact';
            const text = document.createElement('button');
            text.type = 'button';
            text.className = 'vgta-memory-open';
            text.appendChild(createTextElement('span', 'title', String(artifact.title || 'Untitled artifact')));
            text.appendChild(createTextElement('span', 'meta', `${String(artifact.role || '')} - ${String(artifact.model || '')}`));
            text.addEventListener('click', () => handlers.includeArtifact(String(artifact.id || '')));

            const include = document.createElement('button');
            include.type = 'button';
            include.className = 'vgta-memory-include';
            include.textContent = 'INCLUDE';
            include.addEventListener('click', () => handlers.includeArtifact(String(artifact.id || '')));
            row.appendChild(text);
            row.appendChild(include);
            nodes.memoryArtifacts.appendChild(row);
        });
    }

    function applySessionToChat(session, config, nodes, appendPlainMessage) {
        const messages = Array.isArray(session.messages) ? session.messages : [];
        config.currentSessionId = String(session.id || '');
        config.chatHistory = [];
        nodes.chatLog.replaceChildren();

        messages.forEach((message) => {
            const role = String(message.role || '');
            const content = String(message.content || '');
            if (role !== 'user' && role !== 'assistant') {
                return;
            }
            config.chatHistory.push({ role, content });
            appendPlainMessage(role === 'user' ? 'user' : 'assistant', String(message.label || role), content);
        });
    }

    function appendArtifactToComposer(artifact, nodes) {
        const content = String(artifact.content || '');
        if (content === '') {
            return;
        }

        const prefix = nodes.chatInput.value.trim() === '' ? '' : `${nodes.chatInput.value.trim()}\n\n`;
        nodes.chatInput.value = `${prefix}Eingebundenes VGTAstra Artifact:\n${content}`;
        nodes.chatInput.focus();
    }

    function createController(options) {
        const { config, nodes, postForm, formatAjaxError, createTextElement, appendPlainMessage } = options;

        function resetChatSurface() {
            nodes.chatLog.replaceChildren();
            appendPlainMessage('system', 'VGTAstra SYSTEM', 'Chat ready. Select a target plugin, build the file context, then give development instructions or start the role pipeline.');
        }

        function loadMemory() {
            const formData = new FormData();
            formData.append('action', 'vgta_list_memory');
            formData.append('nonce', config.nonce);
            formData.append('plugin_slug', config.activePlugin);
            postForm(formData)
                .then((response) => {
                    if (response.success) {
                        renderMemoryData(response.data);
                    }
                })
                .catch((error) => appendPlainMessage('system error', 'Memory Error', error.message));
        }

        function renderMemoryData(memory) {
            config.memory = memory && typeof memory === 'object' ? memory : { sessions: [], artifacts: [] };
            renderMemory(config.memory, nodes, createTextElement, {
                loadSession,
                includeArtifact,
            });
        }

        function loadSession(sessionId) {
            const formData = new FormData();
            formData.append('action', 'vgta_load_memory_session');
            formData.append('nonce', config.nonce);
            formData.append('plugin_slug', config.activePlugin);
            formData.append('session_id', sessionId);
            postForm(formData)
                .then((response) => {
                    if (response.success) {
                        applySessionToChat(response.data.session, config, nodes, appendPlainMessage);
                    } else {
                        appendPlainMessage('system error', 'Memory Error', formatAjaxError(response.data));
                    }
                })
                .catch((error) => appendPlainMessage('system error', 'Memory Error', error.message));
        }

        function includeArtifact(artifactId) {
            const formData = new FormData();
            formData.append('action', 'vgta_load_memory_artifact');
            formData.append('nonce', config.nonce);
            formData.append('plugin_slug', config.activePlugin);
            formData.append('artifact_id', artifactId);
            postForm(formData)
                .then((response) => {
                    if (response.success) {
                        appendArtifactToComposer(response.data.artifact, nodes);
                    } else {
                        appendPlainMessage('system error', 'Artifact Error', formatAjaxError(response.data));
                    }
                })
                .catch((error) => appendPlainMessage('system error', 'Artifact Error', error.message));
        }

        return { loadMemory, renderMemoryData, resetChatSurface };
    }

    return { renderMemory, applySessionToChat, appendArtifactToComposer, createController };
})();
