/**
 * STATUS: DIAMANT VGT SUPREME
 * VGTAstra role pipeline step editor.
 */

window.VGTAstraSteps = (() => {
    'use strict';

    function renderStepsConfig(api) {
        const {
            workflowSteps,
            nodes,
            createTextElement,
            appendRoleOptions,
            appendModelOptions,
            normalizeReasoning,
            appendReasoningOptions,
            rerender,
        } = api;

        nodes.agentStepsList.replaceChildren();
        workflowSteps.forEach((step, index) => {
            const wrapper = document.createElement('div');
            wrapper.className = 'vgta-agent-step-item';
            const topRow = document.createElement('div');
            topRow.className = 'vgta-step-top-row';
            topRow.appendChild(createTextElement('span', 'vgta-step-num', `STEP ${index + 1}`));
            const btnDelete = document.createElement('button');
            btnDelete.type = 'button';
            btnDelete.className = 'vgta-btn-remove-step';
            btnDelete.textContent = 'REMOVE';
            btnDelete.disabled = workflowSteps.length <= 1;
            btnDelete.addEventListener('click', () => {
                workflowSteps.splice(index, 1);
                rerender();
            });
            topRow.appendChild(btnDelete);
            wrapper.appendChild(topRow);

            const grid = document.createElement('div');
            grid.className = 'vgta-step-grid';
            grid.appendChild(buildRoleGroup(api, step, index));
            grid.appendChild(buildModelGroup(api, step, index));
            grid.appendChild(buildReasoningGroup(api, step, index));
            wrapper.appendChild(grid);

            const promptGroup = document.createElement('div');
            promptGroup.className = 'vgta-form-group';
            promptGroup.appendChild(createTextElement('label', '', 'STEP INSTRUCTION'));
            const promptArea = document.createElement('textarea');
            promptArea.className = 'vgta-textarea compact';
            promptArea.maxLength = 4000;
            promptArea.value = step.instructions;
            promptArea.addEventListener('input', (event) => {
                workflowSteps[index].instructions = event.target.value;
            });
            promptGroup.appendChild(promptArea);
            wrapper.appendChild(promptGroup);
            nodes.agentStepsList.appendChild(wrapper);
        });
    }

    function buildRoleGroup(api, step, index) {
        const group = document.createElement('div');
        group.className = 'vgta-form-group';
        group.appendChild(api.createTextElement('label', '', 'ROLE'));
        const select = document.createElement('select');
        select.className = 'vgta-input';
        api.appendRoleOptions(select, step.role);
        select.addEventListener('change', (event) => {
            api.workflowSteps[index].role = event.target.value;
        });
        group.appendChild(select);
        return group;
    }

    function buildModelGroup(api, step, index) {
        const group = document.createElement('div');
        group.className = 'vgta-form-group';
        group.appendChild(api.createTextElement('label', '', 'MODEL'));
        const select = document.createElement('select');
        select.className = 'vgta-input';
        api.appendModelOptions(select, step.model);
        select.addEventListener('change', (event) => {
            api.workflowSteps[index].model = event.target.value;
            api.workflowSteps[index].reasoning_effort = api.normalizeReasoning(event.target.value, api.workflowSteps[index].reasoning_effort);
            api.rerender();
        });
        group.appendChild(select);
        return group;
    }

    function buildReasoningGroup(api, step, index) {
        const group = document.createElement('div');
        group.className = 'vgta-form-group';
        group.appendChild(api.createTextElement('label', '', 'THINKING'));
        const select = document.createElement('select');
        select.className = 'vgta-input';
        api.workflowSteps[index].reasoning_effort = api.normalizeReasoning(step.model, step.reasoning_effort);
        api.appendReasoningOptions(select, step.model, api.workflowSteps[index].reasoning_effort);
        select.addEventListener('change', (event) => {
            api.workflowSteps[index].reasoning_effort = event.target.value;
        });
        group.appendChild(select);
        return group;
    }

    return { renderStepsConfig };
})();
