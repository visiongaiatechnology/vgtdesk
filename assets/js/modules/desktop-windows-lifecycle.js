/**
 * VGT Desktop Module — Window iframe lifecycle (suspend / rehydrate)
 * Extracted from desktop-windows.js for file-size and cohesion.
 */

Object.assign(window.VGTDeskEngine, {
    suspendIframe(id) {
        const iframe = document.getElementById(`iframe-${id}`);
        if (!iframe || iframe.dataset.loaded !== 'true') return;

        let currentUrl = iframe.dataset.src;
        try {
            if (iframe.contentWindow && iframe.contentWindow.location) {
                const href = iframe.contentWindow.location.href;
                if (href && href !== 'about:blank') {
                    currentUrl = href;
                }
            }
        } catch (e) {
            if (iframe.src && iframe.src !== 'about:blank') {
                currentUrl = iframe.src;
            }
        }

        iframe.dataset.suspendedUrl = currentUrl;
        iframe.src = 'about:blank';
        iframe.dataset.loaded = 'false';

        this.addLog(`Fenster '${id}' suspendiert (RAM freigegeben).`);
    },

    rehydrateIframe(id) {
        const iframe = document.getElementById(`iframe-${id}`);
        if (!iframe) return;

        const suspended = iframe.dataset.suspendedUrl || iframe.dataset.src;
        if (!suspended || suspended === 'about:blank') return;

        const spinner = document.getElementById(`spinner-${id}`);
        if (spinner) spinner.style.display = 'block';

        let targetUrl = suspended;
        if (targetUrl.indexOf('vgt_iframe') === -1) {
            targetUrl += (targetUrl.indexOf('?') === -1 ? '?' : '&') + 'vgt_iframe=true';
        }
        iframe.src = this.cleanUrl(targetUrl);
        iframe.dataset.loaded = 'true';
        delete iframe.dataset.suspendedUrl;
    }
});
