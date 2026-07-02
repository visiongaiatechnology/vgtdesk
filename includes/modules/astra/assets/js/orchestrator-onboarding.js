/**
 * STATUS: DIAMANT VGT SUPREME
 * VGTAstra beta security gate and first-run guide.
 */

document.addEventListener('DOMContentLoaded', () => {
    'use strict';

    const guideStorageKey = 'vgta_beta_guide_hidden_v1';
    const gate = document.getElementById('vgta-beta-security-gate');
    const confirm = document.getElementById('vgta-beta-security-confirm');
    const root = document.querySelector('.vgta-root');

    if (root) {
        root.classList.add('vgta-dashboard-locked');
    }

    if (confirm && gate) {
        confirm.focus();
        confirm.addEventListener('click', () => {
            gate.classList.add('is-hidden');
            if (root) {
                root.classList.remove('vgta-dashboard-locked');
            }
            maybeOpenGuide();
        });
    }

    function maybeOpenGuide() {
        try {
            if (window.localStorage.getItem(guideStorageKey) === '1') {
                return;
            }
        } catch (error) {
            return;
        }

        openGuide();
    }

    function openGuide() {
        const steps = getGuideSteps();
        let activeIndex = 0;
        const overlay = document.createElement('div');
        overlay.className = 'vgta-guide-overlay';
        overlay.setAttribute('role', 'dialog');
        overlay.setAttribute('aria-modal', 'true');
        overlay.setAttribute('aria-labelledby', 'vgta-guide-title');

        const card = document.createElement('div');
        card.className = 'vgta-guide-card';
        const rail = document.createElement('div');
        rail.className = 'vgta-guide-rail';
        const main = document.createElement('div');
        main.className = 'vgta-guide-main';
        const panel = document.createElement('div');
        panel.className = 'vgta-guide-panel';
        const actions = document.createElement('div');
        actions.className = 'vgta-guide-actions';

        const skip = document.createElement('button');
        skip.type = 'button';
        skip.className = 'vgta-btn secondary';
        skip.textContent = 'NICHT MEHR ANZEIGEN';
        skip.addEventListener('click', () => {
            persistGuideHidden();
            overlay.remove();
        });

        const next = document.createElement('button');
        next.type = 'button';
        next.className = 'vgta-btn success';
        next.textContent = 'WEITER';
        next.addEventListener('click', () => {
            if (activeIndex >= steps.length - 1) {
                overlay.remove();
                return;
            }
            activeIndex++;
            render();
        });

        actions.append(skip, next);
        main.append(panel, actions);
        card.append(rail, main);
        overlay.appendChild(card);
        document.body.appendChild(overlay);

        function render() {
            rail.replaceChildren();
            steps.forEach((step, index) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = index === activeIndex ? 'vgta-guide-step is-active' : 'vgta-guide-step';
                button.textContent = `${String(index + 1).padStart(2, '0')} ${step.label}`;
                button.addEventListener('click', () => {
                    activeIndex = index;
                    render();
                });
                rail.appendChild(button);
            });

            const step = steps[activeIndex];
            panel.replaceChildren();
            panel.appendChild(createText('div', 'vgta-guide-kicker', 'VGTAstra Quick Guide'));
            const title = createText('h2', 'vgta-guide-title', step.title);
            title.id = 'vgta-guide-title';
            panel.appendChild(title);
            panel.appendChild(createText('p', 'vgta-guide-copy', step.copy));
            panel.appendChild(createText('div', 'vgta-guide-benefit', step.benefit));
            next.textContent = activeIndex >= steps.length - 1 ? 'GUIDE SCHLIESSEN' : 'WEITER';
        }

        render();
    }

    function persistGuideHidden() {
        try {
            window.localStorage.setItem(guideStorageKey, '1');
        } catch (error) {
            return;
        }
    }

    function createText(tagName, className, text) {
        const element = document.createElement(tagName);
        element.className = className;
        element.textContent = text;
        return element;
    }

    function getGuideSteps() {
        return [
            {
                label: 'Vault',
                title: 'API Key versiegeln',
                copy: 'Trage den Groq API Key ein. VGTAstra speichert ihn verschluesselt und entschluesselt ihn nur fuer konkrete Gateway-Requests.',
                benefit: 'Nutzen: Der Schluessel liegt nicht im Klartext in der Datenbank und bleibt an den VGTAstra-Kontext gebunden.',
            },
            {
                label: 'Context',
                title: 'Inaktives Plugin analysieren',
                copy: 'Waehle ein deaktiviertes Zielplugin und erstelle die Strukturkarte. Daraus baut VGTAstra spaeter kompakte File-Context-Packs.',
                benefit: 'Nutzen: Die KI bekommt relevante Dateien, ohne jedes Mal das gesamte Plugin blind zu laden.',
            },
            {
                label: 'Chat',
                title: 'Mit dem Agenten arbeiten',
                copy: 'Nutze den Live-Chat fuer Anforderungen, Korrekturen und Rueckfragen. Alte Chats und Artefakte bleiben im Sandbox-Speicher erhalten.',
                benefit: 'Nutzen: Du kannst spaeter weitermachen und wichtige KI-Ausgaben gezielt wieder einbinden.',
            },
            {
                label: 'Pipeline',
                title: 'Rollen orchestrieren',
                copy: 'Architect, Developer, Auditor und Integrator arbeiten in Schleifen. Die festen Rollenprompts liegen ueber deinem Operator-Prompt.',
                benefit: 'Nutzen: Architektur, Umsetzung und Audit werden getrennt, statt alles in einem unkontrollierten Prompt zu vermischen.',
            },
            {
                label: 'Review',
                title: 'Patch erst nach Diff-Review committen',
                copy: 'KI-Patches landen zuerst im Safe Patch Vault. Vor dem Schreiben zeigt VGTAstra einen Diff-Screen mit Review-Token und Guard-Pruefung.',
                benefit: 'Nutzen: Kein direkter Blind-Commit. Der Operator bleibt die letzte Instanz vor jeder Dateiaenderung.',
            },
        ];
    }
});
