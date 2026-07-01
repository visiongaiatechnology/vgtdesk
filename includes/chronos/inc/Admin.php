<?php
declare(strict_types=1);

namespace VGT\Chronos;

if (!defined('ABSPATH')) {
    exit('VGT PROTOCOL: Unauthorized Access Terminated.');
}

// STATUS: DIAMANT VGT SUPREME
// ARCHITEKTUR: Isoliertes DOM, reaktive Hydration.
// SECURITY: Strict Whitelisting, Nonce-Validation, Defense-in-Depth Sanitization.
// FIX: HTML5 Silent-Abort Paradoxon eliminiert. UX Redirect Flow optimiert.

final class Admin
{
    private const ALLOWED_THEMES = ['blocks', 'cyber', 'minimal', 'matrix', 'glass', 'neon-pulse'];
    private const ALLOWED_ANIMATIONS = ['none', 'pulse', 'flip', 'slide', 'glitch'];
    private const ALLOWED_TYPES = ['fixed', 'evergreen'];
    private const ALLOWED_ACTIONS = ['hide', 'redirect'];
    private const ALLOWED_LANGUAGES = ['de', 'en'];

    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'register_menu']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_assets']);
        add_action('admin_post_vgt_save_countdown', [self::class, 'handle_save']);
        add_action('admin_post_vgt_delete_countdown', [self::class, 'handle_delete']);
    }

    public static function register_menu(): void
    {
        add_submenu_page(
            'vgt-build-center',
            'Chronos',
            'Chronos',
            'manage_options',
            'vgt-chronos-builder',
            [self::class, 'render_dashboard']
        );
    }

    public static function enqueue_assets(string $hook): void
    {
        if (strpos($hook, 'vgt-chronos-builder') === false) return;

        wp_enqueue_style('vgt-admin-css', VGT_CHRONOS_URL . 'assets/vgt-admin.css', [], Bootstrapper::VERSION);
        wp_enqueue_script('vgt-admin-js', VGT_CHRONOS_URL . 'assets/vgt-admin.js', [], Bootstrapper::VERSION, true);
    }

    public static function handle_save(): void
    {
        if (!current_user_can('manage_options') || !isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'vgt_save_action')) {
            wp_die('VGT SECURITY KERNEL: Invalid Integrity Token. Request Terminated.', 'VGT ERROR', ['response' => 403]);
        }

        $type = in_array($_POST['type'] ?? '', self::ALLOWED_TYPES, true) ? $_POST['type'] : 'fixed';
        $action = in_array($_POST['action_on_expire'] ?? '', self::ALLOWED_ACTIONS, true) ? $_POST['action_on_expire'] : 'hide';
        $theme = in_array($_POST['theme'] ?? '', self::ALLOWED_THEMES, true) ? $_POST['theme'] : 'blocks';
        $animation = in_array($_POST['animation'] ?? '', self::ALLOWED_ANIMATIONS, true) ? $_POST['animation'] : 'none';
        $language = in_array($_POST['language'] ?? '', self::ALLOWED_LANGUAGES, true) ? $_POST['language'] : 'de';

        // VGT KERNEL: Absolute Daten-Isolation basierend auf dem aktiven State
        $end_datetime = '';
        if ($type === 'fixed') {
            $end_datetime = sanitize_text_field($_POST['end_datetime'] ?? '');
            if (!empty($end_datetime) && !preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $end_datetime)) {
                $end_datetime = '';
            }
        }

        $duration_seconds = 0;
        if ($type === 'evergreen') {
            $duration_seconds = isset($_POST['duration_minutes']) ? absint($_POST['duration_minutes']) * 60 : 0;
        }

        $data = [
            'id' => isset($_POST['id']) ? absint($_POST['id']) : 0,
            'title' => sanitize_text_field($_POST['title'] ?? 'Untitled Matrix'),
            'type' => $type,
            'end_datetime' => $end_datetime,
            'duration_seconds' => $duration_seconds,
            'action_on_expire' => $action,
            'redirect_url' => esc_url_raw($_POST['redirect_url'] ?? ''), // Serverseitige Filterung ist dem Browser überlegen
            'design_settings' => [
                'color_primary' => sanitize_hex_color($_POST['color_primary'] ?? '') ?: '#00ffcc',
                'color_bg' => sanitize_hex_color($_POST['color_bg'] ?? '') ?: '#111111',
                'color_label' => sanitize_hex_color($_POST['color_label'] ?? '') ?: '#888888',
                'theme' => $theme,
                'animation' => $animation,
                'language' => $language
            ]
        ];

        // System speichert und gibt die ID zurück (selbst bei Updates 0 affected rows)
        $saved_id = Database::save_countdown($data);
        
        // VGT UX FIX: Wir halten den Nutzer in der Bearbeitungs-Session, anstatt ihn rauszuwerfen
        wp_safe_redirect(admin_url('admin.php?page=vgt-chronos-builder&edit_id=' . $saved_id . '&status=saved'));
        exit;
    }

    public static function handle_delete(): void
    {
        if (!current_user_can('manage_options') || !isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'vgt_delete_action')) {
            wp_die('VGT SECURITY KERNEL: Unauthorized Purge Request Terminated.', 'VGT ERROR', ['response' => 403]);
        }

        $id = isset($_GET['id']) ? absint($_GET['id']) : 0;
        if ($id > 0) {
            Database::delete_countdown($id);
        }

        wp_safe_redirect(admin_url('admin.php?page=vgt-chronos&status=purged'));
        exit;
    }

    public static function render_dashboard(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $countdowns = Database::get_countdowns();
        $edit_id = isset($_GET['edit_id']) ? absint($_GET['edit_id']) : 0;
        $edit_data = $edit_id > 0 ? Database::get_countdown($edit_id) : null;
        
        // Hydration Logic mit Defense-in-Depth Fallbacks
        $settings = $edit_data ? json_decode($edit_data['design_settings'], true) : [];
        if (!is_array($settings)) $settings = []; // JSON Crash-Protection

        $title = $edit_data ? esc_attr($edit_data['title']) : '';
        $type = $edit_data ? esc_attr($edit_data['type']) : 'fixed';
        $end_datetime = $edit_data ? esc_attr($edit_data['end_datetime'] ?? '') : '';
        
        // VGT FIX: Vermeidung der HTML5 `min="1"` Blockade durch explizite Null-Wert Löschung
        $duration_minutes = ($edit_data && !empty($edit_data['duration_seconds'])) ? absint($edit_data['duration_seconds'] / 60) : '';
        
        $action_on_expire = $edit_data ? esc_attr($edit_data['action_on_expire']) : 'hide';
        $redirect_url = $edit_data ? esc_url($edit_data['redirect_url']) : '';
        
        $color_primary = sanitize_hex_color($settings['color_primary'] ?? '') ?: '#00ffcc';
        $color_bg = sanitize_hex_color($settings['color_bg'] ?? '') ?: '#111111';
        $color_label = sanitize_hex_color($settings['color_label'] ?? '') ?: '#888888';
        $theme = esc_attr($settings['theme'] ?? 'blocks');
        $animation = esc_attr($settings['animation'] ?? 'none');
        $language = esc_attr($settings['language'] ?? 'de');
        
        $preview_labels = $language === 'en' 
            ? ['days' => 'Days', 'hours' => 'Hours', 'minutes' => 'Minutes', 'seconds' => 'Seconds'] 
            : ['days' => 'Tage', 'hours' => 'Stunden', 'minutes' => 'Minuten', 'seconds' => 'Sekunden'];
        ?>
        <!-- VGT ISOLATED ADMIN ENVIRONMENT - DIAMANT STATUS -->
        <div class="vgt-admin-wrapper">
            <header class="vgt-header">
                <div class="vgt-logo-area">
                    <div class="vgt-pulse"></div>
                    <h1>VISION GAIA<span>CHRONOS</span> // COUNTDOWN BUILDER</h1>
                </div>
                <div class="vgt-sys-status">SYS.STATE: <span class="vgt-glow-text">OPTIMAL</span></div>
            </header>

            <?php if (isset($_GET['status']) && $_GET['status'] === 'saved'): ?>
                <div style="background: rgba(0, 255, 204, 0.05); border: 1px solid var(--vgt-primary); color: var(--vgt-primary); padding: 1rem 1.5rem; border-radius: 8px; margin-bottom: 2rem; font-weight: 600; letter-spacing: 1px; text-transform: uppercase; display: flex; align-items: center; gap: 10px; box-shadow: 0 0 15px var(--vgt-primary-glow);">
                    <span style="display:inline-block; width:8px; height:8px; background:var(--vgt-primary); border-radius:50%; box-shadow:0 0 10px var(--vgt-primary);"></span>
                    SYSTEM OVERRIDE SUCCESSFUL: MATRIX SYNCHRONIZED TO DATABASE.
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['status']) && $_GET['status'] === 'purged'): ?>
                 <div style="background: rgba(255, 0, 68, 0.05); border: 1px solid #ff0044; color: #ff0044; padding: 1rem 1.5rem; border-radius: 8px; margin-bottom: 2rem; font-weight: 600; letter-spacing: 1px; text-transform: uppercase; display: flex; align-items: center; gap: 10px; box-shadow: 0 0 15px rgba(255, 0, 68, 0.2);">
                    <span style="display:inline-block; width:8px; height:8px; background:#ff0044; border-radius:50%; box-shadow:0 0 10px #ff0044;"></span>
                    MATRIX PURGE COMPLETE: DATA ERADICATED.
                </div>
            <?php endif; ?>

            <main class="vgt-grid">
                <!-- LEFT COLUMN: Creator -->
                <div class="vgt-col-left">
                    <section class="vgt-panel vgt-form-panel vgt-glass">
                        <h2><?php echo $edit_data ? 'Update Matrix Configuration' : 'Matrix Configuration'; ?></h2>
                        <?php if ($edit_data): ?>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=vgt-chronos-builder')); ?>" class="vgt-btn-cancel">CANCEL OVERRIDE (CREATE NEW)</a>
                        <?php endif; ?>
                        
                        <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="POST" class="vgt-form" id="vgt-creator-form">
                            <input type="hidden" name="action" value="vgt_save_countdown">
                            <input type="hidden" name="id" value="<?php echo esc_attr((string)$edit_id); ?>">
                            <?php wp_nonce_field('vgt_save_action'); ?>
                            
                            <div class="vgt-form-section">
                                <h3>1. Core Data</h3>
                                <div class="vgt-form-group">
                                    <label>Designation (Title)</label>
                                    <input type="text" name="title" value="<?php echo $title; ?>" required placeholder="e.g. Cyber Monday Matrix">
                                </div>

                                <div class="vgt-form-row">
                                    <div class="vgt-form-group">
                                        <label>Timer Logic</label>
                                        <select name="type" id="vgt-type-select">
                                            <option value="fixed" <?php selected($type, 'fixed'); ?>>Fixed Global Time</option>
                                            <option value="evergreen" <?php selected($type, 'evergreen'); ?>>Evergreen (Per User)</option>
                                        </select>
                                    </div>
                                    <div class="vgt-form-group" id="vgt-fixed-wrapper" style="display: <?php echo $type === 'fixed' ? 'block' : 'none'; ?>;">
                                        <label>Termination Date</label>
                                        <input type="datetime-local" name="end_datetime" value="<?php echo $end_datetime; ?>">
                                    </div>
                                    <div class="vgt-form-group" id="vgt-evergreen-wrapper" style="display: <?php echo $type === 'evergreen' ? 'block' : 'none'; ?>;">
                                        <label>Duration (Minutes)</label>
                                        <input type="number" min="1" name="duration_minutes" value="<?php echo esc_attr((string)$duration_minutes); ?>" placeholder="15">
                                    </div>
                                </div>
                            </div>

                            <div class="vgt-form-section">
                                <h3>2. Visual Engine</h3>
                                <div class="vgt-form-row">
                                    <div class="vgt-form-group">
                                        <label>Aesthetic Theme</label>
                                        <select name="theme" id="vgt-theme-select">
                                            <option value="blocks" <?php selected($theme, 'blocks'); ?>>Solid Blocks (Default)</option>
                                            <option value="cyber" <?php selected($theme, 'cyber'); ?>>Cyber Neon</option>
                                            <option value="minimal" <?php selected($theme, 'minimal'); ?>>Minimal Line</option>
                                            <option value="matrix" <?php selected($theme, 'matrix'); ?>>Digital Matrix</option>
                                            <option value="glass" <?php selected($theme, 'glass'); ?>>Glassmorphism</option>
                                            <option value="neon-pulse" <?php selected($theme, 'neon-pulse'); ?>>Neon Pulse</option>
                                        </select>
                                    </div>
                                    <div class="vgt-form-group">
                                        <label>Tick Animation</label>
                                        <select name="animation" id="vgt-anim-select">
                                            <option value="none" <?php selected($animation, 'none'); ?>>Zero Motion (Static)</option>
                                            <option value="pulse" <?php selected($animation, 'pulse'); ?>>Cyber Pulse</option>
                                            <option value="flip" <?php selected($animation, 'flip'); ?>>3D Flip</option>
                                            <option value="slide" <?php selected($animation, 'slide'); ?>>Digital Slide</option>
                                            <option value="glitch" <?php selected($animation, 'glitch'); ?>>System Glitch</option>
                                        </select>
                                    </div>
                                    <div class="vgt-form-group">
                                        <label>Language / Sprache</label>
                                        <select name="language" id="vgt-lang-select">
                                            <option value="de" <?php selected($language, 'de'); ?>>Deutsch</option>
                                            <option value="en" <?php selected($language, 'en'); ?>>English</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="vgt-form-row">
                                    <div class="vgt-form-group">
                                        <label>Primary Color</label>
                                        <div class="vgt-color-picker-wrapper">
                                            <input type="color" name="color_primary" id="vgt-color-primary" value="<?php echo esc_attr($color_primary); ?>">
                                            <span class="vgt-color-val"><?php echo esc_html($color_primary); ?></span>
                                        </div>
                                    </div>
                                    <div class="vgt-form-group">
                                        <label>Background Color</label>
                                        <div class="vgt-color-picker-wrapper">
                                            <input type="color" name="color_bg" id="vgt-color-bg" value="<?php echo esc_attr($color_bg); ?>">
                                            <span class="vgt-color-val"><?php echo esc_html($color_bg); ?></span>
                                        </div>
                                    </div>
                                    <div class="vgt-form-group">
                                        <label>Label Text Color</label>
                                        <div class="vgt-color-picker-wrapper">
                                            <input type="color" name="color_label" id="vgt-color-label" value="<?php echo esc_attr($color_label); ?>">
                                            <span class="vgt-color-val"><?php echo esc_html($color_label); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="vgt-form-section">
                                <h3>3. Post-Expiration Protocol</h3>
                                <div class="vgt-form-group">
                                    <label>Action</label>
                                    <select name="action_on_expire" id="vgt-action-select">
                                        <option value="hide" <?php selected($action_on_expire, 'hide'); ?>>Purge (Hide Timer)</option>
                                        <option value="redirect" <?php selected($action_on_expire, 'redirect'); ?>>Force Redirect</option>
                                    </select>
                                </div>
                                <div class="vgt-form-group" id="vgt-redirect-wrapper" style="display: <?php echo $action_on_expire === 'redirect' ? 'block' : 'none'; ?>;">
                                    <label>Target URL (Redirect)</label>
                                    <!-- VGT FIX: type="url" zu type="text" geändert, um Silent HTML5 Aborts bei unsichtbaren Feldern zu verhindern -->
                                    <input type="text" name="redirect_url" id="vgt-redirect-url" value="<?php echo $redirect_url; ?>" placeholder="https://visiongaiatechnology.com/offer">
                                </div>
                            </div>

                            <button type="submit" class="vgt-btn-primary">
                                <?php echo $edit_data ? 'DEPLOY OVERRIDE TO PRODUCTION' : 'DEPLOY MATRIX TO PRODUCTION'; ?>
                            </button>
                        </form>
                    </section>
                </div>

                <!-- RIGHT COLUMN: Live Preview & Existing -->
                <div class="vgt-col-right">
                    <section class="vgt-panel vgt-preview-panel vgt-glass sticky-panel">
                        <h2>Live Rendering Canvas</h2>
                        <div class="vgt-canvas-area" id="vgt-preview-container">
                            <!-- Live Preview DOM inject -->
                            <div class="vgt-timer-wrapper" id="vgt-live-preview" data-theme="<?php echo $theme; ?>" data-animation="<?php echo $animation; ?>" style="--vgt-color: <?php echo esc_attr($color_primary); ?>; --vgt-bg: <?php echo esc_attr($color_bg); ?>; --vgt-label: <?php echo esc_attr($color_label); ?>;">
                                <div class="vgt-timer-block"><div class="vgt-timer-value" data-unit="days" data-val="14">14</div><div class="vgt-timer-label"><?php echo esc_html($preview_labels['days']); ?></div></div>
                                <div class="vgt-timer-block"><div class="vgt-timer-value" data-unit="hours" data-val="09">09</div><div class="vgt-timer-label"><?php echo esc_html($preview_labels['hours']); ?></div></div>
                                <div class="vgt-timer-block"><div class="vgt-timer-value" data-unit="minutes" data-val="42">42</div><div class="vgt-timer-label"><?php echo esc_html($preview_labels['minutes']); ?></div></div>
                                <div class="vgt-timer-block"><div class="vgt-timer-value vgt-tick-sim" data-unit="seconds" data-val="41">41</div><div class="vgt-timer-label"><?php echo esc_html($preview_labels['seconds']); ?></div></div>
                            </div>
                        </div>
                        <p class="vgt-hint">WYSIWYG Engine active. Realtime Animation Engine rendering.</p>
                    </section>

                    <section class="vgt-panel vgt-list-panel vgt-glass mt-4">
                        <h2>Deployed Matrices</h2>
                        <div class="vgt-card-grid">
                            <?php if (empty($countdowns)): ?>
                                <div class="vgt-empty-state">NO ACTIVE TIMERS DETECTED.</div>
                            <?php else: ?>
                                <?php foreach ($countdowns as $c): ?>
                                    <div class="vgt-matrix-card <?php echo $edit_id === (int)$c['id'] ? 'vgt-card-active' : ''; ?>">
                                        <div class="vgt-mc-header">
                                            <h4><?php echo esc_html($c['title']); ?></h4>
                                            <div class="vgt-mc-actions">
                                                <span class="vgt-badge"><?php echo esc_html(strtoupper($c['type'])); ?></span>
                                                <a href="<?php echo esc_url(admin_url('admin.php?page=vgt-chronos-builder&edit_id=' . $c['id'])); ?>" class="vgt-btn-edit" title="Edit Matrix">⚙</a>
                                                <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=vgt_delete_countdown&id=' . $c['id']), 'vgt_delete_action')); ?>" class="vgt-btn-delete" data-confirm="Initiate Matrix Purge? This action is irreversible." title="Purge Matrix">✖</a>
                                            </div>
                                        </div>
                                        <div class="vgt-mc-body">
                                            <code>[vgt_chronos id="<?php echo esc_html((string)$c['id']); ?>"]</code>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </section>
                </div>
            </main>
        </div>
        <?php
        $nonce_attr = '';
        if (function_exists('vgt_get_csp_nonce')) {
            $nonce = vgt_get_csp_nonce();
            if (!empty($nonce)) {
                $nonce_attr = ' nonce="' . esc_attr($nonce) . '"';
            }
        }
        ?>
        <script<?php echo $nonce_attr; ?>>
        (function() {
            function syncAccent() {
                if (window.parent && window.parent.document && window.parent.document.documentElement) {
                    var parentEl = window.parent.document.documentElement;
                    var accentColor = parentEl.style.getPropertyValue('--vgt-accent-color');
                    var accentRgba15 = parentEl.style.getPropertyValue('--vgt-accent-rgba15');
                    
                    if (accentColor) {
                        document.documentElement.style.setProperty('--vgt-primary', accentColor);
                    }
                    if (accentRgba15) {
                        document.documentElement.style.setProperty('--vgt-primary-glow', accentRgba15);
                    }
                }
            }
            syncAccent();
            setInterval(syncAccent, 1000);
        })();
        </script>
        <?php
    }
}
