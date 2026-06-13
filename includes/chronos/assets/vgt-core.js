/**
 * STATUS: DIAMANT VGT SUPREME
 * ARCHITEKTUR: Zero-Dependency Object-Oriented Physics Engine for Time.
 * UPDATE: Client-seitiges Date Parsing eliminiert. Nutzt absolute Unix-Epoche 
 * zur Sicherstellung perfekter globaler Synchronisation über alle Zeitzonen.
 */

class VGTChronosEngine {
    constructor(element) {
        this.el = element;
        this.id = parseInt(this.el.dataset.id, 10);
        this.type = this.el.dataset.type;
        this.action = this.el.dataset.action;
        this.redirect = this.el.dataset.redirect;
        this.animation = this.el.dataset.animation || 'none';
        
        this.nodes = {
            days: this.el.querySelector('[data-unit="days"]'),
            hours: this.el.querySelector('[data-unit="hours"]'),
            minutes: this.el.querySelector('[data-unit="minutes"]'),
            seconds: this.el.querySelector('[data-unit="seconds"]')
        };

        this.initFlipDOM();

        this.endTime = this.calculateEndTime();
        this.animationFrameId = null;

        if (this.endTime && this.endTime > Date.now()) {
            this.start();
        } else {
            this.triggerExpire();
        }
    }

    initFlipDOM() {
        if (this.animation === 'flip') {
            Object.values(this.nodes).forEach(node => {
                if(!node) return;
                const val = node.dataset.val || node.textContent.trim().substring(0, 2);
                node.dataset.val = val;
                node.innerHTML = `
                    <span style="visibility: hidden;">${val}</span>
                    <div class="vgt-flip-base vgt-flip-top"><div class="vgt-flip-text">${val}</div></div>
                    <div class="vgt-flip-base vgt-flip-bottom"><div class="vgt-flip-text">${val}</div></div>
                `;
            });
        }
    }

    calculateEndTime() {
        if (this.type === 'fixed') {
            // VGT FIX: Liest die vorverarbeitete Unix-Epoche direkt aus, um 
            // Browser-spezifisches und lokales Timezone-Offsetting zu zerstören.
            const endMs = parseInt(this.el.dataset.endtimeMs, 10);
            return isNaN(endMs) || endMs <= 0 ? null : endMs;
        }

        if (this.type === 'evergreen') {
            const durationMs = parseInt(this.el.dataset.duration, 10) * 1000;
            if (isNaN(durationMs) || durationMs <= 0) return null;

            const storageKey = `vgt_chronos_${this.id}_start`;
            let startTime = localStorage.getItem(storageKey);

            if (!startTime) {
                startTime = Date.now();
                localStorage.setItem(storageKey, startTime.toString());
            }
            return parseInt(startTime, 10) + durationMs;
        }

        return null;
    }

    start() {
        const tick = () => {
            const now = Date.now();
            const distance = this.endTime - now;

            if (distance <= 0) {
                this.triggerExpire();
                return;
            }

            this.updateDOM(distance);
            this.animationFrameId = requestAnimationFrame(tick);
        };

        this.animationFrameId = requestAnimationFrame(tick);
    }

    updateDOM(distance) {
        const d = Math.floor(distance / (1000 * 60 * 60 * 24));
        const h = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const m = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const s = Math.floor((distance % (1000 * 60)) / 1000);

        this.updateNode(this.nodes.days, d);
        this.updateNode(this.nodes.hours, h);
        this.updateNode(this.nodes.minutes, m);
        this.updateNode(this.nodes.seconds, s);
    }

    updateNode(node, value) {
        if (!node) return;
        const formatted = value < 10 ? `0${value}` : value.toString();
        const current = node.dataset.val || node.textContent.trim().substring(0, 2);
        
        if (current !== formatted) {
            node.dataset.val = formatted;
            
            if (this.animation === 'flip') {
                node.innerHTML = `
                    <span style="visibility: hidden;">${formatted}</span>
                    <div class="vgt-flip-base vgt-flip-top"><div class="vgt-flip-text">${formatted}</div></div>
                    <div class="vgt-flip-base vgt-flip-bottom"><div class="vgt-flip-text">${current}</div></div>
                    <div class="vgt-flip-flap vgt-flip-flap-top"><div class="vgt-flip-text">${current}</div></div>
                    <div class="vgt-flip-flap vgt-flip-flap-bottom"><div class="vgt-flip-text">${formatted}</div></div>
                `;
            } else {
                node.textContent = formatted;
            }
            
            if (this.animation !== 'none') {
                node.classList.remove('vgt-tick');
                void node.offsetWidth;
                node.classList.add('vgt-tick');
            }
        }
    }

    triggerExpire() {
        if (this.animationFrameId) cancelAnimationFrame(this.animationFrameId);

        Object.values(this.nodes).forEach(node => {
            if (!node) return;
            if (this.animation === 'flip') {
                node.innerHTML = `
                    <span style="visibility: hidden;">00</span>
                    <div class="vgt-flip-base vgt-flip-top"><div class="vgt-flip-text">00</div></div>
                    <div class="vgt-flip-base vgt-flip-bottom"><div class="vgt-flip-text">00</div></div>
                `;
            } else {
                node.textContent = '00';
            }
        });

        if (this.action === 'hide') {
            this.el.classList.add('vgt-timer-hidden');
        } else if (this.action === 'redirect' && this.redirect) {
            window.location.replace(this.redirect); // Security: Ersetzt History-Stack (User kann nicht zurück-glitchen)
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.vgt-timer-wrapper').forEach(el => {
        new VGTChronosEngine(el);
    });
});