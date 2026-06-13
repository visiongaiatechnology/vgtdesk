<?php
declare(strict_types=1);

namespace VGT\Chronos;

if (!defined('ABSPATH')) {
    exit('VGT PROTOCOL: Unauthorized Access Terminated.');
}

// STATUS: DIAMANT VGT SUPREME
// ARCHITEKTUR: Secure Rendering Pipeline. Absolute Epoch Time Sync.

final class Frontend
{
    public static function init(): void
    {
        add_shortcode('vgt_chronos', [self::class, 'render_shortcode']);
        add_action('wp_enqueue_scripts', [self::class, 'enqueue_assets']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_admin_preview_styles']);
    }

    public static function enqueue_assets(): void
    {
        wp_enqueue_script('vgt-chronos-engine', VGT_CHRONOS_URL . 'assets/vgt-core.js', [], Bootstrapper::VERSION, true);
        self::inject_theme_styles('wp-block-library'); 
    }

    public static function enqueue_admin_preview_styles(string $hook): void
    {
        if (strpos($hook, 'vgt-chronos-builder') !== false || strpos($hook, 'vgt-chronos') !== false) {
            self::inject_theme_styles('vgt-admin-css');
        }
    }

    private static function inject_theme_styles(string $handle): void
    {
        // STATUS: DIAMANT - Hardware Accelerated GPU Animations
        $css = '
            .vgt-timer-wrapper { display: flex; gap: 1rem; font-family: ui-sans-serif, system-ui, -apple-system, sans-serif; justify-content: center; margin: 2rem 0; perspective: 1000px; }
            .vgt-timer-block { padding: 1rem; text-align: center; min-width: 80px; display: flex; flex-direction: column; align-items: center; justify-content: center; transition: all 0.3s ease; }
            .vgt-timer-value { font-size: var(--vgt-size, 2.5rem); font-weight: 800; line-height: 1; margin-bottom: 0.25rem; font-variant-numeric: tabular-nums; display: inline-block; transform-style: preserve-3d; }
            .vgt-timer-label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: var(--vgt-label, #888); }
            .vgt-timer-hidden { display: none !important; }
            
            /* THEME: BLOCKS */
            .vgt-timer-wrapper[data-theme="blocks"] .vgt-timer-block { background: var(--vgt-bg, #111); border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.4); border: 1px solid rgba(255,255,255,0.05); }
            .vgt-timer-wrapper[data-theme="blocks"] .vgt-timer-value { color: var(--vgt-color, #00ffcc); }

            /* THEME: CYBER */
            .vgt-timer-wrapper[data-theme="cyber"] .vgt-timer-block { background: transparent; border: 1px solid var(--vgt-color); box-shadow: inset 0 0 15px rgba(0,0,0,0.5), 0 0 10px rgba(0,0,0,0.5); border-radius: 4px; position: relative; overflow: hidden; }
            .vgt-timer-wrapper[data-theme="cyber"] .vgt-timer-block::before { content: ""; position: absolute; top: 0; left: 0; width: 100%; height: 2px; background: var(--vgt-color); box-shadow: 0 0 10px var(--vgt-color); }
            .vgt-timer-wrapper[data-theme="cyber"] .vgt-timer-value { color: var(--vgt-color); text-shadow: 0 0 10px var(--vgt-color); }

            /* THEME: MINIMAL */
            .vgt-timer-wrapper[data-theme="minimal"] { gap: 0; border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; overflow: hidden; background: var(--vgt-bg, transparent); }
            .vgt-timer-wrapper[data-theme="minimal"] .vgt-timer-block { border-right: 1px solid rgba(255,255,255,0.1); border-radius: 0; }
            .vgt-timer-wrapper[data-theme="minimal"] .vgt-timer-block:last-child { border-right: none; }
            .vgt-timer-wrapper[data-theme="minimal"] .vgt-timer-value { color: var(--vgt-color); font-weight: 300; }

            /* THEME: MATRIX */
            .vgt-timer-wrapper[data-theme="matrix"] { font-family: "Courier New", Courier, monospace; }
            .vgt-timer-wrapper[data-theme="matrix"] .vgt-timer-block { background: #000; border: 1px solid #111; border-radius: 0; }
            .vgt-timer-wrapper[data-theme="matrix"] .vgt-timer-value { color: var(--vgt-color); text-shadow: 0 0 5px var(--vgt-color); font-weight: bold; }
            .vgt-timer-wrapper[data-theme="matrix"] .vgt-timer-label { font-weight: bold; }

            /* THEME: GLASSMORPHISM */
            .vgt-timer-wrapper[data-theme="glass"] .vgt-timer-block { background: var(--vgt-bg, rgba(255, 255, 255, 0.05)); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 16px; box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3); }
            .vgt-timer-wrapper[data-theme="glass"] .vgt-timer-value { color: var(--vgt-color); text-shadow: 0 2px 4px rgba(0,0,0,0.2); }

            /* THEME: NEON PULSE */
            @keyframes vgtThemePulse { 0% { box-shadow: 0 0 5px var(--vgt-color), inset 0 0 5px var(--vgt-color); } 50% { box-shadow: 0 0 20px var(--vgt-color), inset 0 0 10px var(--vgt-color); } 100% { box-shadow: 0 0 5px var(--vgt-color), inset 0 0 5px var(--vgt-color); } }
            .vgt-timer-wrapper[data-theme="neon-pulse"] .vgt-timer-block { background: var(--vgt-bg); border: 2px solid var(--vgt-color); border-radius: 50%; width: 100px; height: 100px; padding: 0; animation: vgtThemePulse 2s infinite alternate; }
            .vgt-timer-wrapper[data-theme="neon-pulse"] .vgt-timer-value { color: var(--vgt-color); font-size: 2rem; margin-bottom: 0; }
            .vgt-timer-wrapper[data-theme="neon-pulse"] .vgt-timer-label { font-size: 0.65rem; margin-top: 5px; }

            /* =========================================
               VGT ZERO-RUNTIME ANIMATION ENGINE (GPU)
               ========================================= */
            
            /* ANIMATION: PULSE */
            @keyframes vgtAnimPulse { 0% { transform: scale(1); } 50% { transform: scale(1.15); text-shadow: 0 0 20px var(--vgt-color); } 100% { transform: scale(1); } }
            .vgt-timer-wrapper[data-animation="pulse"] .vgt-timer-value.vgt-tick { animation: vgtAnimPulse 0.4s cubic-bezier(0.25, 1, 0.5, 1); }

            /* ANIMATION: SLIDE */
            @keyframes vgtAnimSlide { 0% { transform: translateY(-20px); opacity: 0; } 100% { transform: translateY(0); opacity: 1; } }
            .vgt-timer-wrapper[data-animation="slide"] .vgt-timer-value.vgt-tick { animation: vgtAnimSlide 0.4s cubic-bezier(0.25, 1, 0.5, 1); }

            /* ANIMATION: GLITCH */
            @keyframes vgtAnimGlitch { 
                0% { transform: translate(0); text-shadow: none; } 
                20% { transform: translate(-2px, 2px); text-shadow: 2px 0 0 #ff0044, -2px 0 0 #00ffcc; } 
                40% { transform: translate(-2px, -2px); text-shadow: -2px 0 0 #ff0044, 2px 0 0 #00ffcc; } 
                60% { transform: translate(2px, 2px); text-shadow: 2px 0 0 #ff0044, -2px 0 0 #00ffcc; } 
                80% { transform: translate(2px, -2px); text-shadow: -2px 0 0 #ff0044, 2px 0 0 #00ffcc; } 
                100% { transform: translate(0); text-shadow: none; } 
            }
            .vgt-timer-wrapper[data-animation="glitch"] .vgt-timer-value.vgt-tick { animation: vgtAnimGlitch 0.3s linear; }

            /* ANIMATION: 3D MECHANICAL FLIP (SPLIT-FLAP) */
            .vgt-timer-wrapper[data-animation="flip"] .vgt-timer-value {
                position: relative;
                color: transparent !important; /* Hide structural layout text */
                perspective: 1000px;
            }

            .vgt-timer-wrapper[data-animation="flip"] .vgt-flip-base,
            .vgt-timer-wrapper[data-animation="flip"] .vgt-flip-flap {
                position: absolute;
                left: 0; right: 0;
                height: 50%;
                background: var(--vgt-bg);
                border-radius: 4px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.3);
                overflow: hidden;
            }

            .vgt-timer-wrapper[data-animation="flip"] .vgt-flip-top,
            .vgt-timer-wrapper[data-animation="flip"] .vgt-flip-flap-top {
                top: 0;
                transform-origin: bottom center;
                border-bottom-left-radius: 0;
                border-bottom-right-radius: 0;
                border-bottom: 1px solid rgba(0,0,0,0.5); /* Mechanical Hinge */
            }

            .vgt-timer-wrapper[data-animation="flip"] .vgt-flip-bottom,
            .vgt-timer-wrapper[data-animation="flip"] .vgt-flip-flap-bottom {
                bottom: 0;
                transform-origin: top center;
                border-top-left-radius: 0;
                border-top-right-radius: 0;
            }

            .vgt-timer-wrapper[data-animation="flip"] .vgt-flip-top { z-index: 1; }
            .vgt-timer-wrapper[data-animation="flip"] .vgt-flip-bottom { z-index: 1; }
            .vgt-timer-wrapper[data-animation="flip"] .vgt-flip-flap-top { z-index: 2; }
            /* Bottom flap waits dynamically */
            .vgt-timer-wrapper[data-animation="flip"] .vgt-flip-flap-bottom { z-index: 3; transform: rotateX(90deg); opacity: 0; }

            .vgt-timer-wrapper[data-animation="flip"] .vgt-flip-text {
                position: absolute;
                left: 0; right: 0;
                height: 200%; /* Double height allows precise vertical centering of cut font */
                display: flex;
                align-items: center;
                justify-content: center;
                color: var(--vgt-color);
            }

            .vgt-timer-wrapper[data-animation="flip"] .vgt-flip-top .vgt-flip-text,
            .vgt-timer-wrapper[data-animation="flip"] .vgt-flip-flap-top .vgt-flip-text { top: 0; }

            .vgt-timer-wrapper[data-animation="flip"] .vgt-flip-bottom .vgt-flip-text,
            .vgt-timer-wrapper[data-animation="flip"] .vgt-flip-flap-bottom .vgt-flip-text { bottom: 0; }

            .vgt-timer-wrapper[data-animation="flip"] .vgt-timer-value.vgt-tick .vgt-flip-flap-top {
                animation: vgtFlipDownTop 0.4s cubic-bezier(0.455, 0.03, 0.515, 0.955) forwards;
            }

            .vgt-timer-wrapper[data-animation="flip"] .vgt-timer-value.vgt-tick .vgt-flip-flap-bottom {
                animation: vgtFlipDownBottom 0.4s cubic-bezier(0.455, 0.03, 0.515, 0.955) 0.4s forwards;
            }

            @keyframes vgtFlipDownTop {
                0% { transform: rotateX(0deg); filter: brightness(1); }
                100% { transform: rotateX(-90deg); filter: brightness(0.5); opacity: 0; }
            }

            @keyframes vgtFlipDownBottom {
                0% { transform: rotateX(90deg); filter: brightness(0.5); opacity: 0; }
                1% { opacity: 1; } /* Defense-in-depth z-fighting fix */
                100% { transform: rotateX(0deg); filter: brightness(1); opacity: 1; }
            }
        ';
        wp_add_inline_style($handle, $css);
    }

    public static function render_shortcode(array|string $atts): string
    {
        $attributes = shortcode_atts(['id' => 0], (array)$atts);
        $id = absint($attributes['id']);

        if ($id === 0) return '<!-- VGT ERROR: Invalid ID -->';

        $data = Database::get_countdown($id);
        if (!$data) return '<!-- VGT ERROR: Matrix not found -->';

        // Defense-in-Depth Härtung der Styles
        $settings = json_decode($data['design_settings'], true);
        $color = sanitize_hex_color($settings['color_primary'] ?? '') ?: '#00ffcc';
        $bg = sanitize_hex_color($settings['color_bg'] ?? '') ?: '#111111';
        $label_color = sanitize_hex_color($settings['color_label'] ?? '') ?: '#888888';
        
        $theme = esc_attr($settings['theme'] ?? 'blocks');
        $animation = esc_attr($settings['animation'] ?? 'none');
        $lang = esc_attr($settings['language'] ?? 'de');
        $type = esc_attr($data['type']);
        $action = esc_attr($data['action_on_expire']);
        $redirect = esc_url($data['redirect_url']);
        $duration = absint($data['duration_seconds'] ?? 0);
        
        // VGT KERNEL: Absolute Time Synchronisation
        // Konvertiere die lokale WP-Zeit in globale Unix-Epoche-Millisekunden, 
        // um Cross-Timezone Bugs beim Client-Rendering auszulöschen.
        $timestamp_ms = 0;
        if (!empty($data['end_datetime']) && $type === 'fixed') {
            $wp_timezone = wp_timezone();
            try {
                $date_obj = new \DateTime($data['end_datetime'], $wp_timezone);
                $timestamp_ms = $date_obj->getTimestamp() * 1000;
            } catch (\Exception $e) {
                $timestamp_ms = 0;
            }
        }
        
        $html = sprintf(
            '<div class="vgt-timer-wrapper" id="vgt-timer-%1$d" data-id="%1$d" data-type="%2$s" data-theme="%8$s" data-animation="%10$s" data-endtime-ms="%3$d" data-duration="%4$d" data-action="%5$s" data-redirect="%6$s" style="--vgt-color: %7$s; --vgt-bg: %11$s; --vgt-label: %9$s;">',
            $id,
            $type,
            $timestamp_ms,
            $duration,
            $action,
            $redirect,
            $color,
            $theme,
            $label_color,
            $animation,
            $bg
        );

        $labels = [
            'de' => ['Tage' => 'days', 'Stunden' => 'hours', 'Minuten' => 'minutes', 'Sekunden' => 'seconds'],
            'en' => ['Days' => 'days', 'Hours' => 'hours', 'Minutes' => 'minutes', 'Seconds' => 'seconds']
        ];
        $blocks = $labels[$lang] ?? $labels['de'];

        foreach ($blocks as $label => $unit) {
            $html .= '<div class="vgt-timer-block">';
            $html .= '<div class="vgt-timer-value" data-unit="' . esc_attr($unit) . '">00</div>';
            $html .= '<div class="vgt-timer-label">' . esc_html($label) . '</div>';
            $html .= '</div>';
        }

        $html .= '</div>';
        return $html;
    }
}