/**
 * STATUS: DIAMANT VGT SUPREME
 * ARCHITEKTUR: Reactive Vanilla JS Data-Binding. Zero-Latency Live Preview. 
 * Beinhaltet Realtime Animation Tick-Simulation.
 */
document.addEventListener('DOMContentLoaded', () => {
    'use strict';

    const typeSelect = document.getElementById('vgt-type-select');
    const fixedWrapper = document.getElementById('vgt-fixed-wrapper');
    const evergreenWrapper = document.getElementById('vgt-evergreen-wrapper');

    if (typeSelect) {
        typeSelect.addEventListener('change', (e) => {
            if (e.target.value === 'evergreen') {
                fixedWrapper.style.display = 'none';
                evergreenWrapper.style.display = 'block';
            } else {
                fixedWrapper.style.display = 'block';
                evergreenWrapper.style.display = 'none';
            }
        });
    }

    const actionSelect = document.getElementById('vgt-action-select');
    const redirectWrapper = document.getElementById('vgt-redirect-wrapper');

    if (actionSelect) {
        actionSelect.addEventListener('change', (e) => {
            if (e.target.value === 'redirect') {
                redirectWrapper.style.display = 'block';
            } else {
                redirectWrapper.style.display = 'none';
            }
        });
    }

    const previewContainer = document.getElementById('vgt-live-preview');
    if (previewContainer) {
        const inputs = {
            theme: document.getElementById('vgt-theme-select'),
            anim: document.getElementById('vgt-anim-select'),
            lang: document.getElementById('vgt-lang-select'),
            colorPrimary: document.getElementById('vgt-color-primary'),
            colorBg: document.getElementById('vgt-color-bg'),
            colorLabel: document.getElementById('vgt-color-label')
        };

        const updatePreview = () => {
            previewContainer.setAttribute('data-theme', inputs.theme.value);
            previewContainer.setAttribute('data-animation', inputs.anim.value);
            
            previewContainer.style.setProperty('--vgt-color', inputs.colorPrimary.value);
            inputs.colorPrimary.nextElementSibling.textContent = inputs.colorPrimary.value;
            
            previewContainer.style.setProperty('--vgt-bg', inputs.colorBg.value);
            inputs.colorBg.nextElementSibling.textContent = inputs.colorBg.value;

            previewContainer.style.setProperty('--vgt-label', inputs.colorLabel.value);
            inputs.colorLabel.nextElementSibling.textContent = inputs.colorLabel.value;

            // VGT Language Sync
            if (inputs.lang) {
                const langLabels = {
                    'de': { 'days': 'Tage', 'hours': 'Stunden', 'minutes': 'Minuten', 'seconds': 'Sekunden' },
                    'en': { 'days': 'Days', 'hours': 'Hours', 'minutes': 'Minutes', 'seconds': 'Seconds' }
                };
                const currentLang = inputs.lang.value;
                previewContainer.querySelectorAll('.vgt-timer-block').forEach(block => {
                    const unitNode = block.querySelector('.vgt-timer-value');
                    const labelNode = block.querySelector('.vgt-timer-label');
                    if (unitNode && labelNode && langLabels[currentLang][unitNode.dataset.unit]) {
                        labelNode.textContent = langLabels[currentLang][unitNode.dataset.unit];
                    }
                });
            }

            // VGT Architectural Sync: Base Nodes für 3D Flip vorbereiten (Löst Unsichtbarkeits-Bug)
            const staticNodes = previewContainer.querySelectorAll('.vgt-timer-value:not(.vgt-tick-sim)');
            staticNodes.forEach(node => {
                const val = node.dataset.val || node.textContent.trim().substring(0, 2);
                node.dataset.val = val;
                if (inputs.anim.value === 'flip') {
                    if (!node.querySelector('.vgt-flip-base')) {
                        node.innerHTML = `<span style="visibility: hidden;">${val}</span><div class="vgt-flip-base vgt-flip-top"><div class="vgt-flip-text">${val}</div></div><div class="vgt-flip-base vgt-flip-bottom"><div class="vgt-flip-text">${val}</div></div>`;
                    }
                } else {
                    if (node.querySelector('.vgt-flip-base')) {
                        node.textContent = val;
                    }
                }
            });

            // Local Scope Fetch for simNode to prevent ReferenceError during init
            const localSimNode = previewContainer.querySelector('.vgt-tick-sim');
            if (localSimNode) {
                const simVal = localSimNode.dataset.val || localSimNode.textContent.trim().substring(0, 2);
                localSimNode.dataset.val = simVal;
                if (inputs.anim.value === 'flip' && !localSimNode.querySelector('.vgt-flip-base')) {
                    localSimNode.innerHTML = `<span style="visibility: hidden;">${simVal}</span><div class="vgt-flip-base vgt-flip-top"><div class="vgt-flip-text">${simVal}</div></div><div class="vgt-flip-base vgt-flip-bottom"><div class="vgt-flip-text">${simVal}</div></div>`;
                } else if (inputs.anim.value !== 'flip' && localSimNode.querySelector('.vgt-flip-base')) {
                    localSimNode.textContent = simVal;
                }
            }
        };

        Object.values(inputs).forEach(input => {
            if (input) {
                input.addEventListener('input', updatePreview);
            }
        });
        
        updatePreview();

        // VGT SUPREME UX: Live Tick Simulation in Admin Dashboard
        const simNode = previewContainer.querySelector('.vgt-tick-sim');
        let simValue = parseInt(simNode.dataset.val || simNode.textContent.trim().substring(0, 2), 10) || 7;
        
        setInterval(() => {
            const current = simNode.dataset.val || simNode.textContent.trim().substring(0, 2);
            simValue = simValue === 0 ? 59 : simValue - 1;
            const formatted = simValue < 10 ? `0${simValue}` : simValue.toString();
            
            simNode.dataset.val = formatted;

            // DOM Simulation Injection for Preview
            if (inputs.anim.value === 'flip') {
                simNode.innerHTML = `
                    <span style="visibility: hidden;">${formatted}</span>
                    <div class="vgt-flip-base vgt-flip-top"><div class="vgt-flip-text">${formatted}</div></div>
                    <div class="vgt-flip-base vgt-flip-bottom"><div class="vgt-flip-text">${current}</div></div>
                    <div class="vgt-flip-flap vgt-flip-flap-top"><div class="vgt-flip-text">${current}</div></div>
                    <div class="vgt-flip-flap vgt-flip-flap-bottom"><div class="vgt-flip-text">${formatted}</div></div>
                `;
            } else {
                simNode.textContent = formatted;
            }
            
            if (inputs.anim.value !== 'none') {
                simNode.classList.remove('vgt-tick');
                void simNode.offsetWidth; 
                simNode.classList.add('vgt-tick');
            }
        }, 1000);
    }

    const codeBlocks = document.querySelectorAll('.vgt-mc-body code');
    codeBlocks.forEach(block => {
        block.addEventListener('click', (e) => {
            const originalText = e.target.innerText;
            navigator.clipboard.writeText(originalText).then(() => {
                e.target.innerText = 'COPIED TO CLIPBOARD!';
                e.target.style.color = 'var(--vgt-primary)';
                e.target.style.borderColor = 'var(--vgt-primary)';
                setTimeout(() => {
                    e.target.innerText = originalText;
                    e.target.style.color = '#fff';
                    e.target.style.borderColor = 'var(--vgt-border)';
                }, 2000);
            });
        });
    });

    const deleteBtns = document.querySelectorAll('.vgt-btn-delete');
    deleteBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const message = btn.getAttribute('data-confirm');
            if (!confirm(`[VGT SYSTEM PROMPT] - ${message}`)) {
                e.preventDefault(); 
            }
        });
    });
});