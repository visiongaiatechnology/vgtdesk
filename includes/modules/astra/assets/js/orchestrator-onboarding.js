/**
 * STATUS: DIAMANT VGT SUPREME
 * VGTAstra beta security gate and first-run guide.
 */

window.VGTAstraOnboarding = (() => {
    'use strict';

    const guideStorageKey = 'vgta_beta_guide_hidden_v2';

    document.addEventListener('DOMContentLoaded', init);

    function init() {
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
    }

    function maybeOpenGuide() {
        try {
            if (window.localStorage.getItem(guideStorageKey) === '1') {
                return;
            }
        } catch (error) {
            return;
        }

        openGuide(false);
    }

    function openGuide(force) {
        if (!force) {
            try {
                if (window.localStorage.getItem(guideStorageKey) === '1') {
                    return;
                }
            } catch (error) {
                return;
            }
        }

        const existing = document.getElementById('vgta-guide-overlay');
        if (existing) {
            existing.remove();
        }

        const steps = getGuideSteps(getActiveLanguage());
        let activeIndex = 0;
        const overlay = document.createElement('div');
        overlay.id = 'vgta-guide-overlay';
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
        skip.textContent = getActiveLanguage() === 'en' ? 'DO NOT SHOW AGAIN' : 'NICHT MEHR ANZEIGEN';
        skip.addEventListener('click', () => {
            persistGuideHidden();
            overlay.remove();
        });

        const next = document.createElement('button');
        next.type = 'button';
        next.className = 'vgta-btn success';
        next.textContent = getActiveLanguage() === 'en' ? 'NEXT' : 'WEITER';
        next.addEventListener('click', () => {
            if (activeIndex >= steps.length - 1) {
                overlay.remove();
                return;
            }
            activeIndex += 1;
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
            const lang = getActiveLanguage();
            next.textContent = activeIndex >= steps.length - 1 ? (lang === 'en' ? 'CLOSE GUIDE' : 'GUIDE SCHLIESSEN') : (lang === 'en' ? 'NEXT' : 'WEITER');
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

    function getActiveLanguage() {
        const root = document.querySelector('.vgta-root');
        return root && root.dataset.vgtaLang === 'en' ? 'en' : 'de';
    }

    function getGuideSteps(language) {
        if (language === 'en') {
            return [
            {
                label: 'Vault',
                title: 'Seal the API key',
                copy: 'Enter the Groq API key. VGTAstra stores it encrypted and decrypts it only for concrete gateway requests.',
                benefit: 'Benefit: The key is not stored in plaintext and remains bound to the VGTAstra context.',
            },
            {
                label: 'Context',
                title: 'Analyze an inactive plugin',
                copy: 'Select a deactivated target plugin and build the structure map. VGTAstra uses it later to create compact file context packs.',
                benefit: 'Benefit: The AI receives relevant files without blindly loading the whole plugin every time.',
            },
            {
                label: 'Chat',
                title: 'Work with the agent',
                copy: 'Open the chat configuration when you need model selection, Thinking Mode, Web Grounding, Memory, or Artifacts. Collapsed mode leaves more room for the chat.',
                benefit: 'Benefit: The workspace stays large without hiding model and memory controls.',
            },
            {
                label: 'Pipeline',
                title: 'Orchestrate roles',
                copy: 'Architect, Developer, Auditor, and Integrator work in loops. Collapse the Role Pipeline on the right when you only want to chat.',
                benefit: 'Benefit: Architecture, implementation, and audit remain separated while the chat can use the full width when needed.',
            },
            {
                label: 'Review',
                title: 'Commit patches only after diff review',
                copy: 'AI patches land in the Safe Patch Vault first. Before writing, VGTAstra shows a diff screen with review token and guard validation.',
                benefit: 'Benefit: No blind commit. The operator remains the final authority before every file change.',
            },
        ];
        }

        return [
            {
                label: 'Vault',
                title: 'API Key versiegeln',
                copy: 'Trage den Groq API Key ein. VGTAstra speichert ihn verschlüsselt und entschlüsselt ihn nur für konkrete Gateway-Requests.',
                benefit: 'Nutzen: Der Schlüssel liegt nicht im Klartext in der Datenbank und bleibt an den VGTAstra-Kontext gebunden.',
            },
            {
                label: 'Context',
                title: 'Inaktives Plugin analysieren',
                copy: 'Wähle ein deaktiviertes Zielplugin und erstelle die Strukturkarte. Daraus baut VGTAstra später kompakte File-Context-Packs.',
                benefit: 'Nutzen: Die KI bekommt relevante Dateien, ohne jedes Mal das gesamte Plugin blind zu laden.',
            },
            {
                label: 'Chat',
                title: 'Mit dem Agenten arbeiten',
                copy: 'Öffne die Chat-Konfiguration, wenn du Modell, Thinking Mode, Web Grounding, Memory oder Artifacts brauchst. Eingeklappt bleibt mehr Platz für den Chat.',
                benefit: 'Nutzen: Der Arbeitsbereich bleibt groß, ohne Modell- und Memory-Funktionen zu verstecken.',
            },
            {
                label: 'Pipeline',
                title: 'Rollen orchestrieren',
                copy: 'Architect, Developer, Auditor und Integrator arbeiten in Schleifen. Die Role Pipeline lässt sich rechts einklappen, wenn du nur chatten möchtest.',
                benefit: 'Nutzen: Architektur, Umsetzung und Audit bleiben getrennt, aber der Chat kann bei Bedarf die volle Breite nutzen.',
            },
            {
                label: 'Review',
                title: 'Patch erst nach Diff-Review committen',
                copy: 'KI-Patches landen zuerst im Safe Patch Vault. Vor dem Schreiben zeigt VGTAstra einen Diff-Screen mit Review-Token und Guard-Prüfung.',
                benefit: 'Nutzen: Kein direkter Blind-Commit. Der Operator bleibt die letzte Instanz vor jeder Dateiänderung.',
            },
        ];
    }

    return { openGuide };
})();
