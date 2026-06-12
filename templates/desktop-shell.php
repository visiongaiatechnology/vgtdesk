<?php
/**
 * Template: VGT WP-Desk Shell Layout (Modular)
 * STATUS: 💠 DIAMANT VGT SUPREME
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$pinned_keys = $user_settings['pinned_apps'] ?? [];
?>
<div class="vgt-desk-shell select-none vgt-layout-<?php echo esc_attr($user_settings['layout_style']); ?>" id="vgt-shell-root">

    <!-- Dynamischer Wallpaper-Hintergrund -->
    <div id="vgt-wallpaper" class="vgt-wallpaper-bg"></div>
    <div class="vgt-wallpaper-overlay"></div>

    <!-- Globaler Drag & Resize Overlay Schutz vor Iframe Capturing -->
    <div id="vgt-global-drag-overlay" style="position: fixed; inset: 0; z-index: 99999; display: none; background: transparent;"></div>

    <!-- STARTMENÜ / APP LAUNCHER -->
    <?php include __DIR__ . '/parts/start-menu.php'; ?>

    <!-- SPOTLIGHT SEARCH / COMMAND RUNNER -->
    <?php include __DIR__ . '/parts/spotlight.php'; ?>

    <!-- PREMIUM CONTROL CENTER -->
    <?php include __DIR__ . '/parts/control-center.php'; ?>

    <!-- Globaler Snap-Layout Helfer für Fenster-Tiling -->
    <?php include __DIR__ . '/parts/snap-menu.php'; ?>

    <!-- MAIN INTERFACE OVERLAY -->
    <div class="vgt-interface-overlay">
        
        <!-- STATUS BAR (MENÜOBEN) -->
        <?php include __DIR__ . '/parts/topbar.php'; ?>

        <!-- CANVAS ARBEITSBEREICH -->
        <main class="vgt-workspace" id="desktop-workspace">
            
            <!-- DESKTOP WIDGETS LAYER -->
            <?php include __DIR__ . '/parts/widgets.php'; ?>

            <!-- FREI ANORDNBARE DESKTOP ICONS LAYER -->
            <?php include __DIR__ . '/parts/desktop-icons.php'; ?>

            <!-- FENSTERCONTAINER (WINDOWS LAYER) -->
            <div id="windows-container" class="vgt-windows-container">
                
                <!-- NATIVES WILLKOMMENS-FENSTER -->
                <?php include __DIR__ . '/parts/win-welcome.php'; ?>

                <!-- NATIVES ABOUT / PROFIL-FENSTER -->
                <?php include __DIR__ . '/parts/win-about.php'; ?>

                <!-- NATIVES SYSTEMEINSTELLUNGEN-FENSTER -->
                <?php include __DIR__ . '/parts/win-settings.php'; ?>

                <!-- NATIVES TASK-MANAGER-FENSTER -->
                <?php include __DIR__ . '/parts/win-task-manager.php'; ?>

                <!-- NATIVES KALENDER-FENSTER -->
                <?php include __DIR__ . '/parts/win-calendar.php'; ?>

                <!-- STATISCH REGISTRIERTE PLUGINS / IFRAME WINDOWS -->
                <?php include __DIR__ . '/parts/win-iframes.php'; ?>

            </div>

        </main>

        <!-- DESKTOP TASKBAR / DOCK -->
        <footer class="vgt-footer">
            <div class="vgt-dock" id="desktop-dock">
                
                <!-- Home-Button -->
                <div class="vgt-dock-item" onclick="VGTDeskEngine.handleDockClick('welcome')">
                    <div class="vgt-dock-icon" id="vgt-dock-home">
                        <span class="vgt-dock-emoji">🏠</span>
                    </div>
                    <span class="vgt-dock-tooltip">Home</span>
                    <span class="vgt-dock-indicator indicator-welcome" id="indicator-welcome"></span>
                </div>

                <!-- Settings-Button -->
                <div class="vgt-dock-item" onclick="VGTDeskEngine.handleDockClick('settings')">
                    <div class="vgt-dock-icon" id="vgt-dock-settings">
                        <span class="vgt-dock-emoji">⚙️</span>
                    </div>
                    <span class="vgt-dock-tooltip">Command Center</span>
                    <span class="vgt-dock-indicator indicator-settings" id="indicator-settings"></span>
                </div>

                <span class="vgt-dock-divider"></span>

                <!-- Aktive/Geöffnete Plugin-Apps in der Taskleiste -->
                <div id="vgt-dynamic-task-bar" class="vgt-dynamic-taskbar-flex">
                    <?php foreach ($apps_data as $key => $app): ?>
                        <div class="vgt-dock-item hidden" id="dock-task-<?php echo esc_attr($key); ?>" onclick="VGTDeskEngine.handleDockClick('<?php echo esc_js($key); ?>')">
                            <div class="vgt-dock-icon <?php echo esc_attr($app['color']); ?>">
                                <?php if ($app['icon_type'] === 'dashicons'): ?>
                                    <span class="dashicons <?php echo esc_attr($app['icon_val']); ?> vgt-dock-dashicon"></span>
                                <?php elseif ($app['icon_type'] === 'svg' || $app['icon_type'] === 'url'): ?>
                                    <img src="<?php echo $app['icon_type'] === 'svg' ? esc_attr($app['icon_val']) : esc_url($app['icon_val']); ?>" class="vgt-dock-img" alt="" />
                                <?php endif; ?>
                            </div>
                            <span class="vgt-dock-tooltip"><?php echo esc_html($app['title']); ?></span>
                            <span class="vgt-dock-indicator" id="indicator-<?php echo esc_attr($key); ?>"></span>
                        </div>
                    <?php endforeach; ?>
                </div>

            </div>
        </footer>

    </div>

    <!-- FIRST RUN ONBOARDING WIZARD -->
    <?php include __DIR__ . '/parts/wizard.php'; ?>

</div>