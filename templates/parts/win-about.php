<?php
/**
 * Template part: About Window
 * STATUS: 💠 DIAMANT VGT SUPREME
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}
?>
                <!-- NATIVES ABOUT / PROFIL-FENSTER -->
                <div id="win-about" class="window hidden absolute vgt-window" style="width: 480px; height: 500px; top: 15%; left: 25%; z-index: 102;" onclick="VGTDeskEngine.focusWindow('about')">
                    
                    <!-- Resize Handles -->
                    <div class="resize-handle resize-handle-n" onmousedown="VGTDeskEngine.startResize(event, 'about', 'n')"></div>
                    <div class="resize-handle resize-handle-s" onmousedown="VGTDeskEngine.startResize(event, 'about', 's')"></div>
                    <div class="resize-handle resize-handle-e" onmousedown="VGTDeskEngine.startResize(event, 'about', 'e')"></div>
                    <div class="resize-handle resize-handle-w" onmousedown="VGTDeskEngine.startResize(event, 'about', 'w')"></div>
                    <div class="resize-handle resize-handle-nw" onmousedown="VGTDeskEngine.startResize(event, 'about', 'nw')"></div>
                    <div class="resize-handle resize-handle-ne" onmousedown="VGTDeskEngine.startResize(event, 'about', 'ne')"></div>
                    <div class="resize-handle resize-handle-sw" onmousedown="VGTDeskEngine.startResize(event, 'about', 'sw')"></div>
                    <div class="resize-handle resize-handle-se" onmousedown="VGTDeskEngine.startResize(event, 'about', 'se')"></div>

                    <!-- Titlebar -->
                    <div class="vgt-window-header cursor-move window-header">
                        <div class="vgt-window-dots">
                            <span class="vgt-window-dot dot-rose" onclick="VGTDeskEngine.closeWindow('about')"></span>
                            <span class="vgt-window-dot dot-amber" onclick="VGTDeskEngine.minimizeWindow('about')"></span>
                            <span class="vgt-window-dot dot-emerald" onclick="VGTDeskEngine.maximizeWindow('about')"></span>
                        </div>
                        <span class="vgt-window-title">Über VGT WP-Desk</span>
                        <div class="vgt-window-spacer"></div>
                    </div>
                    <!-- Body Content -->
                    <div class="vgt-window-body vgt-about-body">
                        <div class="vgt-about-profile-card">
                            <div class="vgt-about-avatar">
                                <?php echo get_avatar($current_user->ID, 80); ?>
                            </div>
                            <h2 class="vgt-about-name"><?php echo esc_html($current_user->display_name); ?></h2>
                            <span class="vgt-about-role"><?php echo esc_html(ucfirst(join(', ', $current_user->roles))); ?></span>
                        </div>
                        
                        <div class="vgt-about-info">
                            <div class="vgt-about-info-row">
                                <span>Build-Version:</span>
                                <strong class="vgt-build-badge" onclick="VGTDeskEngine.triggerEasterEgg()">V1.0.0-Beta v4 (Stable Candidate)</strong>
                            </div>
                            <div class="vgt-about-info-row">
                                <span>Lizenz:</span>
                                <strong>Premium Lifetime</strong>
                            </div>
                        </div>

                        <div class="vgt-about-message">
                            <p>🌟 <strong>Herzlichen Dank für das Vertrauen!</strong></p>
                            <p>VGT WP-Desk transformiert Ihre WordPress-Verwaltung in eine sichere, modulare und performante Desktop-Umgebung. Wir schätzen Ihre Unterstützung und Partnerschaft.</p>
                        </div>

                        <!-- Canvas für Easteregg (Matrix Rain) -->
                        <div class="vgt-about-easteregg-container hidden" id="vgt-about-matrix-container">
                            <canvas id="vgt-matrix-canvas" width="430" height="150"></canvas>
                            <div class="vgt-matrix-overlay-text">VGT ENCLAVE SECURITY ACTIVE</div>
                        </div>

                        <div class="vgt-about-actions">
                            <button class="vgt-btn-primary" onclick="VGTDeskEngine.triggerEasterEgg()">Easteregg aktivieren 🚀</button>
                        </div>
                    </div>
                </div>
