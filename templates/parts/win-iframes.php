<?php
/**
 * Template part: Iframe Windows Loop
 * STATUS: 💠 DIAMANT VGT SUPREME
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}
?>
                <!-- STATISCH REGISTRIERTE PLUGINS / IFRAME WINDOWS -->
                <?php foreach ($apps_data as $key => $app): 
                    if ($key === 'task-manager') continue;
                ?>
                    <div id="win-<?php echo esc_attr($key); ?>" class="window hidden absolute vgt-window" style="width: 850px; height: 550px; top: 10%; left: 20%; z-index: 50;" onclick="VGTDeskEngine.focusWindow('<?php echo esc_attr($key); ?>')">
                        
                        <!-- 8 Resize Handles -->
                        <div class="resize-handle resize-handle-n" onmousedown="VGTDeskEngine.startResize(event, '<?php echo esc_attr($key); ?>', 'n')"></div>
                        <div class="resize-handle resize-handle-s" onmousedown="VGTDeskEngine.startResize(event, '<?php echo esc_attr($key); ?>', 's')"></div>
                        <div class="resize-handle resize-handle-e" onmousedown="VGTDeskEngine.startResize(event, '<?php echo esc_attr($key); ?>', 'e')"></div>
                        <div class="resize-handle resize-handle-w" onmousedown="VGTDeskEngine.startResize(event, '<?php echo esc_attr($key); ?>', 'w')"></div>
                        <div class="resize-handle resize-handle-nw" onmousedown="VGTDeskEngine.startResize(event, '<?php echo esc_attr($key); ?>', 'nw')"></div>
                        <div class="resize-handle resize-handle-ne" onmousedown="VGTDeskEngine.startResize(event, '<?php echo esc_attr($key); ?>', 'ne')"></div>
                        <div class="resize-handle resize-handle-sw" onmousedown="VGTDeskEngine.startResize(event, '<?php echo esc_attr($key); ?>', 'sw')"></div>
                        <div class="resize-handle resize-handle-se" onmousedown="VGTDeskEngine.startResize(event, '<?php echo esc_attr($key); ?>', 'se')"></div>

                        <!-- Titlebar -->
                        <div class="vgt-window-header cursor-move window-header">
                            <div class="vgt-window-dots">
                                <span class="vgt-window-dot dot-rose" onclick="VGTDeskEngine.closeWindow('<?php echo esc_attr($key); ?>')"></span>
                                <span class="vgt-window-dot dot-amber" onclick="VGTDeskEngine.minimizeWindow('<?php echo esc_attr($key); ?>')"></span>
                                <span class="vgt-window-dot dot-emerald" onclick="VGTDeskEngine.maximizeWindow('<?php echo esc_attr($key); ?>')"></span>
                            </div>
                            <span class="vgt-window-title"><?php echo esc_html($app['title']); ?></span>
                            <div class="vgt-window-badge-wrap">
                                <div id="spinner-<?php echo esc_attr($key); ?>" class="spinner-vgt"></div>
                                <span class="vgt-badge-item vgt-accent-badge-item">Portal</span>
                            </div>
                        </div>
                        <!-- Iframe Box -->
                        <div class="flex-1 iframe-container relative">
                            <div class="drag-overlay absolute inset-0 hidden z-50 bg-transparent"></div>
                            <iframe 
                                id="iframe-<?php echo esc_attr($key); ?>" 
                                src="about:blank" 
                                data-src="<?php echo esc_url($app['url']); ?>"
                                onload="window.VGTDeskEngine && VGTDeskEngine.handleIframeLoaded('<?php echo esc_js($key); ?>')">
                            </iframe>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- CONTAINER FÜR DYNAMISCH ERZEUGTE WINDOWS (DEEP-LINK-PORTALE) -->
                <div id="vgt-dynamic-windows" class="vgt-dynamic-windows-container"></div>
