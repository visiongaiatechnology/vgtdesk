<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VIEW: VGT CONSOLE (Easter Egg / CLI Interface)
 * STATUS: PLATIN VGT STATUS (Hardened & i18n)
 * MODULE: NEXUS TERMINAL EMULATION
 * TEXTDOMAIN: vgt-sentinel-ce
 */
?>

<div class="vgts-card vgts-term-wrapper">
    <div class="vgts-term-header">
        <div style="display: flex; gap: 8px;">
            <div style="width: 12px; height: 12px; border-radius: 50%; background: #ef4444;"></div>
            <div style="width: 12px; height: 12px; border-radius: 50%; background: #f59e0b;"></div>
            <div style="width: 12px; height: 12px; border-radius: 50%; background: #10b981;"></div>
        </div>
        <div style="color: #666; font-size: 12px; letter-spacing: 1px;"><?php esc_html_e('vgts-nexus-terminal_v1.5.sh', 'vgt-sentinel-ce'); ?></div>
        <div style="width: 44px;"></div> 
    </div>

    <div class="vgts-term-body">
        <div id="vgts-term-output" class="vgts-term-output">
            <div style="color: #fff; font-weight: bold;">
                <?php 
                printf(
                    esc_html__('VISIONGAIA TECHNOLOGY KERNEL [Version %s]', 'vgt-sentinel-ce'),
                    defined('VGTS_VERSION') ? esc_html(VGTS_VERSION) : '1.5.0'
                ); 
                ?>
            </div>
            <div style="color: #64748b;"><?php esc_html_e('(c) VisionGaia Technology. All rights reserved.', 'vgt-sentinel-ce'); ?></div><br>
            <div style="color: #64748b;">
                <?php 
                printf(
                    /* translators: %s: command name 'help' */
                    esc_html__('[System: ONLINE] Waiting for input. Type %s for available commands.', 'vgt-sentinel-ce'),
                    '<span style="color:#fff;">\'help\'</span>'
                ); 
                ?>
            </div><br>
        </div>

        <div class="vgts-term-input-row">
            <span style="color: #10b981; margin-right: 10px;"><?php esc_html_e('root@vgts-nexus:~$', 'vgt-sentinel-ce'); ?></span>
            <input type="text" id="vgts-term-input" class="vgts-term-input" autocomplete="off" spellcheck="false" autofocus aria-label="<?php echo esc_attr__('Terminal Input', 'vgt-sentinel-ce'); ?>">
        </div>

        <div class="vgts-scanline"></div>
    </div>
</div>
