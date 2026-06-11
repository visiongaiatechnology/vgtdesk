<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * STATUS: DIAMANT VGT SUPREME
 * Verarbeitet die tatsächliche Injektion der Styles auf der wp-login.php
 * 
 * Adaptiert für WP-Desk Modul-Integration.
 */
final class VGTLoginInjector {

    public static function init(): void {
        add_action('login_enqueue_scripts', [self::class, 'inject_login_aesthetics'], 999);
        add_filter('login_headerurl', [self::class, 'modify_login_logo_url']);
    }

    public static function modify_login_logo_url(string $url): string {
        return home_url(); 
    }

    public static function inject_login_aesthetics(): void {
        $options = get_option('vgt_login_options', []);
        
        if (!is_array($options)) {
            $options = [];
        }
        
        $bg_color = sanitize_hex_color((string)($options['login_bg_color'] ?? '')) ?: '#09090b';
        $accent   = sanitize_hex_color((string)($options['login_accent'] ?? '')) ?: '#00f0ff';
        
        $bg_image_raw = esc_url_raw((string)($options['login_bg_image'] ?? ''));
        $logo_url_raw = esc_url_raw((string)($options['login_logo'] ?? ''));
        
        // VGT SUPREME HARDENING: Brutale Eliminierung von CSS-Injection Vektoren.
        $bg_image_safe = preg_replace('/[()\'\"\\\\]/', '', $bg_image_raw);
        $logo_url_safe = preg_replace('/[()\'\"\\\\]/', '', $logo_url_raw);
        
        $bg_css = $bg_image_safe !== '' ? "background: url('{$bg_image_safe}') no-repeat center center fixed !important; background-size: cover !important;" : "background-color: {$bg_color} !important;";
        $logo_url_css = $logo_url_safe !== '' ? "background-image: url('{$logo_url_safe}') !important; background-size: contain !important; width: 100% !important; height: 80px !important;" : "";

        // Minimalistischer, O(1) gerenderter CSS Block für maximale Performance
        $css = "
            :root {
                --vgt-login-accent: {$accent};
                --vgt-login-surface: rgba(24, 24, 27, 0.65);
                --vgt-login-border: rgba(255, 255, 255, 0.1);
            }
            body.login { {$bg_css} display: flex; align-items: center; justify-content: center; min-height: 100vh; font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; }
            body.login h1 a, .login h1 a { {$logo_url_css} }
            body.login #login { width: 400px !important; padding: 0 !important; }
            body.login #loginform, body.login #registerform, body.login #lostpasswordform { background: var(--vgt-login-surface) !important; backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1px solid var(--vgt-login-border) !important; border-radius: 16px !important; box-shadow: 0 30px 60px rgba(0,0,0,0.6) !important; padding: 40px 30px !important; }
            body.login label { color: #ffffff !important; font-weight: 500; }
            body.login input[type='text'], body.login input[type='password'], body.login input[type='email'] { background: rgba(0, 0, 0, 0.4) !important; border: 1px solid var(--vgt-login-border) !important; color: #ffffff !important; border-radius: 8px !important; padding: 10px 15px !important; box-shadow: inset 0 2px 4px rgba(0,0,0,0.3) !important; transition: all 0.3s ease; }
            body.login input[type='text']:focus, body.login input[type='password']:focus { border-color: var(--vgt-login-accent) !important; box-shadow: 0 0 10px rgba(var(--vgt-login-accent), 0.2) !important; outline: none !important; }
            body.login .button-primary { background: var(--vgt-login-accent) !important; color: #000000 !important; border: none !important; border-radius: 8px !important; font-weight: 700 !important; text-transform: uppercase; text-shadow: none !important; padding: 6px 20px !important; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3) !important; transition: all 0.3s ease; width: 100%; margin-top: 20px; }
            body.login .button-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(255, 255, 255, 0.2) !important; background: #ffffff !important; }
            body.login #nav a, body.login #backtoblog a { color: var(--vgt-login-accent) !important; text-decoration: none !important; transition: all 0.3s ease; text-shadow: 0 2px 4px rgba(0,0,0,0.8); }
            body.login #nav a:hover, body.login #backtoblog a:hover { color: #ffffff !important; }
            .login .language-switcher { display: none !important; }
        ";

        wp_register_style('vgt-login-engine-frontend', false);
        wp_enqueue_style('vgt-login-engine-frontend');
        wp_add_inline_style('vgt-login-engine-frontend', wp_strip_all_tags($css));
    }
}
