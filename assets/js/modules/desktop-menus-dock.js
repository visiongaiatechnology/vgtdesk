/**
 * VGT Desktop Module — Dock magnification
 * Extracted from desktop-menus.js for file-size and cohesion.
 */

Object.assign(window.VGTDeskEngine, {
    initDockMagnification() {
        const dock = document.getElementById('desktop-dock');
        if (!dock) return;

        const items = dock.querySelectorAll('.vgt-dock-item');

        dock.addEventListener('mousemove', (e) => {
            const mouseX = e.clientX;

            items.forEach(item => {
                const itemRect = item.getBoundingClientRect();
                const itemCenterX = itemRect.left + itemRect.width / 2;

                const dist = Math.abs(mouseX - itemCenterX);
                const maxDist = 120;

                if (dist < maxDist) {
                    const scale = 1 + 0.35 * (1 - dist / maxDist);
                    const icon = item.querySelector('.vgt-dock-icon');
                    if (icon) icon.style.transform = `scale(${scale})`;
                    item.style.margin = `0 ${8 * (scale - 1)}px`;
                } else {
                    const icon = item.querySelector('.vgt-dock-icon');
                    if (icon) icon.style.transform = 'none';
                    item.style.margin = '0';
                }
            });
        });

        dock.addEventListener('mouseleave', () => {
            items.forEach(item => {
                const icon = item.querySelector('.vgt-dock-icon');
                if (icon) icon.style.transform = 'none';
                item.style.margin = '0';
            });
        });
    }
});
