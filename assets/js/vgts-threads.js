/**
 * VGT SENTINEL - THREADS DASHBOARD LOGIC
 * STATUS: PLATIN STATUS
 */
"use strict";

document.addEventListener('DOMContentLoaded', () => {
    const ThreadsUI = {
        init() {
            this.animateGauges();
            this.animateCounters();
            console.log('VGT THREADS: Radar System Online.');
        },

        animateGauges() {
            const circles = document.querySelectorAll('.vgts-circle');
            circles.forEach(circle => {
                const pct = circle.getAttribute('data-pct');
                if (pct) {
                    // Slight delay for organic flow
                    setTimeout(() => {
                        circle.style.strokeDasharray = `${pct}, 100`;
                    }, 300 + Math.random() * 200);
                }
            });
        },

        animateCounters() {
            const texts = document.querySelectorAll('.vgts-percentage[data-count]');
            texts.forEach(text => {
                const target = parseInt(text.getAttribute('data-count'), 10);
                if (target === 0 || isNaN(target)) return;
                
                const duration = 1500; 
                const frameRate = 30;
                const totalFrames = Math.round(duration / frameRate);
                let frame = 0;

                const counter = setInterval(() => {
                    frame++;
                    // Cubic easing out
                    const progress = 1 - Math.pow(1 - frame / totalFrames, 3);
                    const currentCount = Math.round(target * progress);
                    
                    text.textContent = currentCount;

                    if (frame === totalFrames) {
                        clearInterval(counter);
                        text.textContent = target; 
                    }
                }, frameRate);
            });
        }
    };

    ThreadsUI.init();
});