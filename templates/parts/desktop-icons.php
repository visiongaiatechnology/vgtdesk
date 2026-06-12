<?php
/**
 * Template part: Desktop Icons
 * STATUS: 💠 DIAMANT VGT SUPREME
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}
?>
            <!-- FREI ANORDNBARE DESKTOP ICONS LAYER -->
            <div id="desktop-icons-area" class="vgt-icons-area">
                
                <!-- Festes System-Icon: Systemeinstellungen -->
                <div class="desktop-icon absolute vgt-icon-item" data-id="settings" onclick="VGTDeskEngine.handleIconClick(event, 'settings')">
                    <div class="vgt-icon-tile vgt-color-gradient-settings">
                        <span class="vgt-icon-emoji">⚙️</span>
                    </div>
                    <span class="vgt-icon-label">Command Center</span>
                </div>

                <!-- Dynamisch geladene Apps (Third-Party Plugins) -->
                <?php foreach ($apps_data as $key => $app): ?>
                    <div class="desktop-icon absolute vgt-icon-item" data-id="<?php echo esc_attr($key); ?>" onclick="VGTDeskEngine.handleIconClick(event, '<?php echo esc_js($key); ?>')">
                        <div class="vgt-icon-tile <?php echo esc_attr($app['color']); ?>">
                            
                            <!-- Dashicon-Ausgabe -->
                            <?php if ($app['icon_type'] === 'dashicons'): ?>
                                <span class="dashicons <?php echo esc_attr($app['icon_val']); ?> vgt-icon-dashicon"></span>
                            <?php elseif ($app['icon_type'] === 'svg' || $app['icon_type'] === 'url'): ?>
                                <img src="<?php echo $app['icon_type'] === 'svg' ? esc_attr($app['icon_val']) : esc_url($app['icon_val']); ?>" class="vgt-icon-img" alt="" />
                            <?php endif; ?>

                        </div>
                        <span class="vgt-icon-label"><?php echo esc_html($app['title']); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
