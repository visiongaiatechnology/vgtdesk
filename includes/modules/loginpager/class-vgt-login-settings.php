<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * STATUS: PLATIN
 * Generiert die Control Node und integriert die Live-Simulations-Matrix.
 * 
 * Adaptiert für WP-Desk Modul-Integration. Asset-Pfade verwenden VGT_LOGIN_MODULE_URL.
 */
final class VGTLoginSettings {

    private const OPTION_NAME = 'vgt_login_options';

    public static function init(): void {
        add_action('admin_menu', [self::class, 'register_menu']);
        add_action('admin_init', [self::class, 'register_settings']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_assets']);
    }

    public static function enqueue_assets(string $hook): void {
        if ($hook !== 'toplevel_page_vgt-login-omega') {
            return;
        }

        wp_enqueue_style('vgt-login-admin', VGT_LOGIN_MODULE_URL . 'assets/css/vgt-login-admin.css', [], '1.0.0');
        wp_enqueue_script('vgt-login-admin-js', VGT_LOGIN_MODULE_URL . 'assets/js/vgt-login-admin.js', [], '1.0.0', true);
    }

    public static function register_menu(): void {
        add_menu_page(
            'VGT Login Engine',
            'VGT Login',
            'manage_options',
            'vgt-login-omega',
            [self::class, 'render_dashboard'],
            'dashicons-shield-alt',
            3
        );
    }

    public static function register_settings(): void {
        register_setting(
            'vgt_login_settings_group',
            self::OPTION_NAME,
            [
                'type' => 'array',
                'sanitize_callback' => [self::class, 'sanitize_input']
            ]
        );
    }

    public static function sanitize_input(mixed $input): array {
        $existing = get_option(self::OPTION_NAME, []);
        if (!is_array($existing)) $existing = [];
        if (!is_array($input)) return $existing; 

        $sanitized = [];
        $sanitized['login_bg_color'] = sanitize_hex_color((string)($input['login_bg_color'] ?? '')) ?: '#09090b';
        $sanitized['login_accent']   = sanitize_hex_color((string)($input['login_accent'] ?? '')) ?: '#00f0ff';
        
        $bg_image_raw = esc_url_raw((string)($input['login_bg_image'] ?? ''));
        $logo_url_raw = esc_url_raw((string)($input['login_logo'] ?? ''));
        
        // VGT SUPREME HARDENING: Brutale Eliminierung von CSS-Injection Vektoren.
        $sanitized['login_bg_image'] = preg_replace('/[()\'\"\\\\]/', '', $bg_image_raw);
        $sanitized['login_logo']     = preg_replace('/[()\'\"\\\\]/', '', $logo_url_raw);
        
        return $sanitized;
    }

    public static function render_dashboard(): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized System Access. [VGT-SEC-03]', 'vgt-wp-desk'), 'Access Denied', ['response' => 403]);
        }

        $options = wp_parse_args(get_option(self::OPTION_NAME, []), [
            'login_bg_color' => '#09090b',
            'login_accent' => '#00f0ff',
            'login_bg_image' => '',
            'login_logo' => ''
        ]);
        ?>
        <div class="vgt-matrix-wrapper">
            <header class="vgt-matrix-header">
                <h1>VGT OMEGA LOGIN ENGINE</h1>
                <div class="vgt-status-bar">
                    System State: <span class="vgt-pulse-green">ACTIVE</span> | Security: <span class="vgt-pulse-green">DIAMANT ENFORCED</span>
                </div>
            </header>

            <div class="vgt-grid-layout">
                
                <!-- CONTROL NODE -->
                <div class="vgt-control-node">
                    <form action="options.php" method="post" id="vgt-login-form">
                        <?php settings_fields('vgt_login_settings_group'); ?>
                        
                        <div class="vgt-panel">
                            <h2>Aesthetic Parameters</h2>
                            
                            <div class="vgt-input-group">
                                <label for="login_bg_color">Fallback Base Color (Void)</label>
                                <div class="color-picker-wrapper">
                                    <input type="color" id="login_bg_color" name="<?php echo esc_attr(self::OPTION_NAME); ?>[login_bg_color]" value="<?php echo esc_attr($options['login_bg_color']); ?>">
                                    <span class="color-hex"><?php echo esc_html($options['login_bg_color']); ?></span>
                                </div>
                            </div>

                            <div class="vgt-input-group">
                                <label for="login_accent">Primary Accent (Energy)</label>
                                <div class="color-picker-wrapper">
                                    <input type="color" id="login_accent" name="<?php echo esc_attr(self::OPTION_NAME); ?>[login_accent]" value="<?php echo esc_attr($options['login_accent']); ?>">
                                    <span class="color-hex"><?php echo esc_html($options['login_accent']); ?></span>
                                </div>
                            </div>

                            <div class="vgt-input-group">
                                <label for="login_bg_image">Background Image URL (Overrides Base Color)</label>
                                <input type="text" id="login_bg_image" class="vgt-text-input" name="<?php echo esc_attr(self::OPTION_NAME); ?>[login_bg_image]" value="<?php echo esc_url($options['login_bg_image']); ?>" placeholder="https://domain.com/cyber-city.jpg">
                            </div>

                            <div class="vgt-input-group">
                                <label for="login_logo">Custom Logo URL (Replaces WP Default)</label>
                                <input type="text" id="login_logo" class="vgt-text-input" name="<?php echo esc_attr(self::OPTION_NAME); ?>[login_logo]" value="<?php echo esc_url($options['login_logo']); ?>" placeholder="https://domain.com/logo.png">
                            </div>

                            <div class="vgt-actions">
                                <button type="submit" class="vgt-btn-execute">COMMIT CHANGES TO CORE</button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- SIMULATION MATRIX -->
                <div class="vgt-simulation-container">
                    <div class="vgt-panel vgt-simulation-panel">
                        <h2>Simulation Matrix <span class="vgt-badge">LIVE</span></h2>
                        
                        <?php
                        $sim_bg_image_safe = preg_replace('/[()\'\"\\\\]/', '', $options['login_bg_image']);
                        $sim_logo_safe     = preg_replace('/[()\'\"\\\\]/', '', $options['login_logo']);
                        ?>
                        <div id="vgt-sim-environment" class="vgt-sim-environment" 
                             style="--sim-bg: <?php echo esc_attr($options['login_bg_color']); ?>; 
                                    --sim-accent: <?php echo esc_attr($options['login_accent']); ?>;
                                    --sim-bg-img: url('<?php echo esc_url($sim_bg_image_safe); ?>');">
                            
                            <!-- Mock WP Login Form -->
                            <div class="vgt-mock-login">
                                <div class="vgt-mock-logo" style="--sim-logo-img: url('<?php echo esc_url($sim_logo_safe); ?>');"></div>
                                <div class="vgt-mock-form-box">
                                    <div class="vgt-mock-input-group">
                                        <div class="vgt-mock-label">Username or Email Address</div>
                                        <div class="vgt-mock-input"></div>
                                    </div>
                                    <div class="vgt-mock-input-group">
                                        <div class="vgt-mock-label">Password</div>
                                        <div class="vgt-mock-input"></div>
                                    </div>
                                    <div class="vgt-mock-actions">
                                        <div class="vgt-mock-checkbox"><span class="check"></span> Remember Me</div>
                                        <div class="vgt-mock-button">Log In</div>
                                    </div>
                                </div>
                                <div class="vgt-mock-links">
                                    <span>Lost your password?</span>
                                    <span>← Go to VGT Systems</span>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

            </div>
        </div>
        <?php
    }
}
