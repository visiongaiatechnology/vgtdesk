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
        
        $bg_color = sanitize_hex_color((string)($options['login_bg_color'] ?? '')) ?: '#070a13';
        $accent   = sanitize_hex_color((string)($options['login_accent'] ?? '')) ?: '#00f0ff';
        
        $bg_image_raw = esc_url_raw((string)($options['login_bg_image'] ?? ''));
        $logo_url_raw = esc_url_raw((string)($options['login_logo'] ?? ''));
        
        // VGT SUPREME HARDENING: Brutale Eliminierung von CSS-Injection Vektoren.
        $bg_image_safe = preg_replace('/[()\'\"\\\\]/', '', $bg_image_raw);
        $logo_url_safe = preg_replace('/[()\'\"\\\\]/', '', $logo_url_raw);
        
        $bg_css = $bg_image_safe !== '' ? "background: url('{$bg_image_safe}') no-repeat center center fixed !important; background-size: cover !important;" : "background: radial-gradient(circle at 50% 50%, #0c1122 0%, #070a13 100%) !important;";
        $logo_url_css = $logo_url_safe !== '' ? "background-image: url('{$logo_url_safe}') !important; background-size: contain !important; width: 100% !important; height: 80px !important;" : "";

        // Parse hex for RGB
        $hex = str_replace('#', '', $accent);
        if (strlen($hex) === 3) {
            $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
            $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
            $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
        } else {
            $r = hexdec(substr($hex, 0, 2) ?: '0');
            $g = hexdec(substr($hex, 2, 2) ?: '240');
            $b = hexdec(substr($hex, 4, 2) ?: '255');
        }
        $accent_rgb = "{$r}, {$g}, {$b}";

        // Minimalistischer, O(1) gerenderter CSS Block für maximale Performance
        $css = "
            :root {
                --vgt-login-accent: {$accent};
                --vgt-login-accent-rgb: {$accent_rgb};
                --vgt-login-surface: rgba(10, 15, 30, 0.6);
                --vgt-login-border: rgba(255, 255, 255, 0.08);
            }
            body.login { 
                {$bg_css} 
                display: flex; 
                flex-direction: column;
                align-items: center; 
                justify-content: center; 
                min-height: 100vh; 
                font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
                position: relative;
            }
            body.login::before {
                content: '';
                position: absolute;
                inset: 0;
                background: radial-gradient(circle at 50% 50%, rgba(var(--vgt-login-accent-rgb), 0.08) 0%, transparent 70%);
                pointer-events: none;
                z-index: 0;
            }
            body.login h1 a, .login h1 a { 
                {$logo_url_css} 
                transition: transform 0.3s ease;
            }
            body.login h1 a:hover, .login h1 a:hover {
                transform: scale(1.02);
            }
            body.login #login { 
                width: 400px !important; 
                padding: 0 !important; 
                position: relative;
                z-index: 1;
            }
            body.login #loginform, body.login #registerform, body.login #lostpasswordform { 
                background: var(--vgt-login-surface) !important; 
                backdrop-filter: blur(25px); 
                -webkit-backdrop-filter: blur(25px); 
                border: 1px solid var(--vgt-login-border) !important; 
                border-radius: 16px !important; 
                box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5), 0 0 30px rgba(var(--vgt-login-accent-rgb), 0.05) !important; 
                padding: 40px 30px !important; 
            }
            body.login label { 
                color: #94a3b8 !important; 
                font-size: 13px;
                font-weight: 500; 
            }
            body.login .forgetmenot label {
                color: #cbd5e1 !important;
            }
            body.login input[type='text'], body.login input[type='password'], body.login input[type='email'] { 
                background: rgba(0, 0, 0, 0.3) !important; 
                border: 1px solid var(--vgt-login-border) !important; 
                color: #ffffff !important; 
                border-radius: 10px !important; 
                padding: 12px 16px !important; 
                box-shadow: inset 0 2px 4px rgba(0,0,0,0.2) !important; 
                transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1); 
            }
            body.login input[type='text']:focus, body.login input[type='password']:focus { 
                border-color: var(--vgt-login-accent) !important; 
                box-shadow: 0 0 12px rgba(var(--vgt-login-accent-rgb), 0.25), inset 0 2px 4px rgba(0,0,0,0.2) !important; 
                outline: none !important; 
            }
            body.login .button-primary { 
                background: linear-gradient(135deg, var(--vgt-login-accent) 0%, #06b6d4 100%) !important; 
                color: #ffffff !important; 
                border: none !important; 
                border-radius: 10px !important; 
                font-weight: 700 !important; 
                letter-spacing: 0.5px;
                text-transform: uppercase; 
                text-shadow: none !important; 
                padding: 10px 20px !important; 
                box-shadow: 0 4px 15px rgba(var(--vgt-login-accent-rgb), 0.3) !important; 
                transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1); 
                width: 100%; 
                margin-top: 20px; 
                cursor: pointer;
            }
            body.login .button-primary:hover { 
                transform: translateY(-1px); 
                box-shadow: 0 8px 25px rgba(var(--vgt-login-accent-rgb), 0.45) !important; 
                opacity: 0.95;
            }
            body.login #nav, body.login #backtoblog {
                text-align: center;
                margin-top: 15px !important;
                padding: 0 !important;
            }
            body.login #nav a, body.login #backtoblog a { 
                color: #94a3b8 !important; 
                text-decoration: none !important; 
                transition: color 0.2s ease; 
                text-shadow: none !important;
            }
            body.login #nav a:hover, body.login #backtoblog a:hover { 
                color: var(--vgt-login-accent) !important; 
            }
            .login .language-switcher { display: none !important; }
        ";

        wp_register_style('vgt-login-engine-frontend', false);
        wp_enqueue_style('vgt-login-engine-frontend');
        wp_add_inline_style('vgt-login-engine-frontend', wp_strip_all_tags($css));
    }
}
