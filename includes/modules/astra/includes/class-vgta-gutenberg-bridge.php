<?php

declare(strict_types=1);

namespace VGTAstra\AgentSystem;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Block editor bridge: enqueue insert UI + AJAX for local-first content insert.
 */
final class GutenbergBridge
{
    private const AJAX_ACTION = 'vgta_gutenberg_generate';
    private const NONCE_ACTION = 'vgta_gutenberg_insert';

    public static function boot(): void
    {
        add_action('enqueue_block_editor_assets', [self::class, 'enqueue_editor_assets']);
        add_action('wp_ajax_' . self::AJAX_ACTION, [self::class, 'ajax_generate']);
    }

    public static function enqueue_editor_assets(): void
    {
        if (!current_user_can(GutenbergContent::CAPABILITY)) {
            return;
        }

        $handle = 'vgta-gutenberg-insert';
        $src = VGTA_PLUGIN_URL . 'assets/js/gutenberg-insert.js';
        wp_enqueue_script(
            $handle,
            $src,
            ['wp-blocks', 'wp-element', 'wp-data', 'wp-components', 'wp-edit-post', 'wp-plugins', 'wp-i18n'],
            defined('VGTA_PLUGIN_VERSION') ? VGTA_PLUGIN_VERSION : '1.0.0',
            true
        );
        wp_localize_script($handle, 'vgtaGutenberg', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce(self::NONCE_ACTION),
            'action'  => self::AJAX_ACTION,
        ]);
    }

    public static function ajax_generate(): void
    {
        if (!check_ajax_referer(self::NONCE_ACTION, 'nonce', false)) {
            wp_send_json_error(['code' => 'invalid_nonce', 'message' => 'CSRF validation failed.'], 403);
        }

        $can = GutenbergContent::can_insert_content(current_user_can(GutenbergContent::CAPABILITY));
        if (!$can) {
            wp_send_json_error(['code' => 'capability', 'message' => 'Insufficient privileges.'], 403);
        }

        $prompt = isset($_POST['prompt']) ? (string) wp_unslash($_POST['prompt']) : '';
        $prompt = GutenbergContent::normalize_prompt($prompt);
        if ($prompt === '') {
            wp_send_json_error(['code' => 'empty_prompt', 'message' => 'Prompt required.'], 400);
        }

        // Local-first generation — no external API required.
        $raw = GutenbergContent::generate_local_html($prompt);
        $sanitized = GutenbergContent::sanitize_html_for_insert($raw);
        if (!$sanitized['ok']) {
            wp_send_json_error(['code' => $sanitized['code'], 'message' => 'Content rejected by sanitizer.'], 400);
        }

        $blocks = GutenbergContent::to_block_markup($sanitized['html']);
        wp_send_json_success([
            'html'   => $sanitized['html'],
            'blocks' => $blocks,
            'mode'   => 'local',
        ]);
    }
}
