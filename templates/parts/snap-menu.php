<?php
/**
 * Template part: Snap Menu
 * STATUS: 💠 DIAMANT VGT SUPREME
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}
?>
    <!-- Globaler Snap-Layout Helfer für Fenster-Tiling -->
    <div id="vgt-global-snap-menu" class="vgt-snap-menu hidden glassmorphism">
        <div class="vgt-snap-option option-left" onclick="VGTDeskEngine.snapActiveWindow('left')">
            <div class="vgt-snap-preview"></div>
            <span>Links</span>
        </div>
        <div class="vgt-snap-option option-right" onclick="VGTDeskEngine.snapActiveWindow('right')">
            <div class="vgt-snap-preview"></div>
            <span>Rechts</span>
        </div>
        <div class="vgt-snap-option option-topleft" onclick="VGTDeskEngine.snapActiveWindow('topleft')">
            <div class="vgt-snap-preview"></div>
            <span>O. Links</span>
        </div>
        <div class="vgt-snap-option option-bottomleft" onclick="VGTDeskEngine.snapActiveWindow('bottomleft')">
            <div class="vgt-snap-preview"></div>
            <span>U. Links</span>
        </div>
    </div>
