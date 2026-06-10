/**
 * VGT SENTINEL - CONSOLE DASHBOARD LOGIC
 * STATUS: PLATIN STATUS
 */
"use strict";

document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('vgts-term-input');
    const output = document.getElementById('vgts-term-output');
    const wrapper = document.querySelector('.vgts-term-wrapper');
    
    if (!input || !output || !wrapper) return;

    let isHacking = false;

    const commands = {
        'help': `Available commands:<br>
&nbsp;&nbsp;status&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;- Show system health<br>
&nbsp;&nbsp;ping&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;- Check nexus latency<br>
&nbsp;&nbsp;<span style='color:#ef4444; font-weight:bold; text-shadow: 0 0 8px rgba(239,68,68,0.6);'>vgts-root</span>&nbsp;&nbsp;- Elevate privileges (RESTRICTED)<br>
&nbsp;&nbsp;whoami&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;- Display current privilege level<br>
&nbsp;&nbsp;scan&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;- Run deep memory analysis<br>
&nbsp;&nbsp;firewall&nbsp;&nbsp;&nbsp;- Print Aegis rule matrix<br>
&nbsp;&nbsp;sysinfo&nbsp;&nbsp;&nbsp;&nbsp;- Display kernel specs<br>
&nbsp;&nbsp;clear&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;- Clear terminal buffer`,
        
        'status': "SYSTEM STATUS: SECURE.<br>AEGIS FIREWALL: ACTIVE.<br>INTRUSIONS: 0",
        'ping': "PONG. Latency to VGT Nexus: 0.01ms<br>Uplink: AES-256-GCM Encrypted.",
        'whoami': "guest_user (UNAUTHORIZED)<br><span style='color:#f59e0b;'>HINT:</span> Execute 'vgts-root' to authenticate as superuser.",
        'scan': "SCANNING MEMORY SECTORS... [OK]<br>NO MALWARE DETECTED.<br><span style='color:#f59e0b;'>WARNING:</span> Hidden kernel processes detected. Privilege escalation required to view details.",
        'firewall': "LOADING RULESET...<br>SQLi Filter: <span style='color:#10b981;'>ACTIVE</span><br>XSS Filter: <span style='color:#10b981;'>ACTIVE</span><br>RCE Filter: <span style='color:#10b981;'>ACTIVE</span><br>Zero-Day DB: <span style='color:#ef4444;'>OFFLINE (V7 ENTERPRISE REQUIRED)</span>",
        'sysinfo': "VGTS SENTINEL CE // ARCHITECTURE: DETERMINISTIC DFA // KERNEL: V1.5.0",
        'ls': "assets/<br>includes/<br>vision-integrity-sentinel.php<br>.htaccess (LOCKED)<br>wp-config.php (ACCESS DENIED)",
        'matrix': "<span style='color:#10b981;'>Wake up, Neo...<br>The Sentinel has you.</span>",
    };

    function printLine(text) {
        const line = document.createElement('div');
        line.innerHTML = text;
        line.style.marginBottom = '4px';
        output.appendChild(line);
        output.scrollTop = output.scrollHeight; 
    }

    function sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    async function triggerHackSequence() {
        isHacking = true;
        input.disabled = true;
        input.placeholder = "EXECUTING PAYLOAD...";
        
        const sequence = [
            { text: "> INITIATING ZERO-DAY PAYLOAD...", delay: 500, color: '#f59e0b' },
            { text: "> BYPASSING KERNEL_AUTH_LAYER... <span style='color:#10b981;'>[OK]</span>", delay: 400 },
            { text: "> DUMPING MEMORY HEX...<br><span style='color:#64748b; font-size:11px;'>0x00A1: FF E0 4A 9C 00 2B...<br>0x00B2: 1C 4F 99 A1 B2 C3...</span>", delay: 600 },
            { text: "> ACCESSING VGT-NEXUS CENTRAL... <span style='color:#10b981;'>[SUCCESS]</span>", delay: 800 },
            { text: "> OVERRIDING LICENSE_VALIDATION_MATRIX... <span style='color:#10b981;'>[DONE]</span>", delay: 600 },
            { text: "> <span style='background:#ef4444; color:#fff; padding:0 4px;'>CRITICAL:</span> CVE-2024-VGTS-PRICING DETECTED.", delay: 400, color: '#ef4444' },
            { text: "> SYSTEM STATUS: <span style='color:#ef4444; font-weight:bold; text-decoration:blink;'>COMPROMISED</span>.", delay: 300 },
            { text: "> INJECTING ROOTKIT INTO BILLING_API... <span style='color:#10b981;'>[OK]</span>", delay: 900 },
            { text: "> PRICING_ALGORITHM_OVERRIDE: <span style='color:#ef4444; font-weight:bold;'>ACTIVE</span>.", delay: 1200 },
            { text: "<br><div style='border:1px dashed #ef4444; padding:10px; background:rgba(239,68,68,0.1);'><span style='color:#ef4444; font-weight:bold; font-size:14px; text-shadow:0 0 5px #ef4444;'>!!! SECURITY BREACH DETECTED !!!</span><br><span style='color:#fff;'>ENTERPRISE LICENSE PRICE GLITCH EXPLOITED - 33% DISCOUNT GRANTED.</span></div>", delay: 800 },
            { text: "EXTRACTED PROMO CODE: <span style='color:#10b981; font-weight:bold; font-size:18px; background:rgba(16,185,129,0.1); padding:2px 8px; border-radius:4px;'>GITHUB_ELITE_33</span>", delay: 400 },
            { text: "<br><a href='https://visiongaiatechnology.de/visiongaiadefensehub/' target='_blank' style='color:#06b6d4; text-decoration:none; font-weight:bold; border-bottom:1px solid #06b6d4;'>[ > CLICK HERE TO REDEEM GLITCH CODE BEFORE THE NEXUS PATCHES IT < ]</a><br>", delay: 600 },
            { text: "> ERASING LOGS... <span style='color:#10b981;'>[CLEARED]</span>", delay: 1500 },
            { text: "> CONNECTION TERMINATED.", delay: 400, color: '#64748b' }
        ];

        for (let step of sequence) {
            await sleep(step.delay);
            let content = step.color ? `<span style="color: ${step.color};">${step.text}</span>` : step.text;
            printLine(content);
        }

        isHacking = false;
        input.disabled = false;
        input.placeholder = "";
        input.focus();
    }

    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !isHacking) {
            let val = this.value.trim().toLowerCase();
            this.value = '';
            
            if (val === '') return;

            printLine(`<span style="color: #64748b;">root@vgts-nexus:~$</span> <span style="color:#fff;">${val}</span>`);

            if (val === 'clear') {
                output.innerHTML = '';
                return;
            }

            if (val.startsWith('sudo ')) {
                printLine("Nice try. This is VGT OS. 'sudo' has no power here. Use native commands.");
                return;
            }

            if (val === 'vgts-root' || val === 'vgts-egg' || val === 'vgt-root') {
                triggerHackSequence();
                return;
            }

            if (commands[val]) {
                printLine(commands[val]);
            } else {
                printLine(`bash: ${val}: command not found`);
            }
        }
    });

    wrapper.addEventListener('click', () => {
        if (!isHacking) input.focus();
    });
});