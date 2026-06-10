/**
 * VGT SENTINEL - AEGIS MODULE LOGIC
 * STATUS: PLATIN STATUS
 */
"use strict";

document.addEventListener('DOMContentLoaded', () => {
    const aegisUI = {
        init() {
            this.setupIPValidation();
            this.setupUIEffects();
            console.log('VGT AEGIS: Matrix Logic initialized.');
        },

        setupIPValidation() {
            const whitelistArea = document.querySelector('textarea[name="vgts_config[aegis_whitelist_ips]"]');
            if (!whitelistArea) return;

            whitelistArea.addEventListener('input', (e) => {
                // Simple validation feedback logic could be added here
                // For Platinum Status: Real-time syntax highlighting for IPs
            });
        },

        setupUIEffects() {
            // Interaktive Effekte für die Pattern-Badges
            const badges = document.querySelectorAll('.vgts-pattern-badge');
            badges.forEach(badge => {
                badge.addEventListener('mouseenter', () => {
                    badge.style.boxShadow = '0 0 12px rgba(6, 182, 212, 0.2)';
                });
                badge.addEventListener('mouseleave', () => {
                    badge.style.boxShadow = 'none';
                });
            });
        }
    };

    aegisUI.init();
});