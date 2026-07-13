<?php

declare(strict_types=1);

namespace VGTAstra\AgentSystem;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Pure Gutenberg content helpers — sanitize/normalize for block-editor insert.
 * Local-first: generation can run without API keys (template HTML).
 */
final class GutenbergContent
{
    public const CAPABILITY = 'edit_posts';
    public const MAX_HTML_BYTES = 200000;
    public const MAX_PROMPT_BYTES = 8000;

    /**
     * Pure capability gate for editor insert (inject capability result for tests).
     */
    public static function can_insert_content(bool $user_can_edit_posts): bool
    {
        return $user_can_edit_posts;
    }

    /**
     * Normalize operator prompt before generation.
     */
    public static function normalize_prompt(string $prompt): string
    {
        $prompt = trim(str_replace("\0", '', $prompt));
        if (strlen($prompt) > self::MAX_PROMPT_BYTES) {
            $prompt = substr($prompt, 0, self::MAX_PROMPT_BYTES);
        }
        return $prompt;
    }

    /**
     * Local-first HTML generation without external APIs.
     * Produces insertable HTML from a prompt (template), never executes scripts.
     */
    public static function generate_local_html(string $prompt): string
    {
        $prompt = self::normalize_prompt($prompt);
        if ($prompt === '') {
            return '';
        }
        $safe_title = self::escape_text(substr($prompt, 0, 120));
        $safe_body  = self::escape_text($prompt);
        return '<section class="vgt-astra-generated">'
            . '<h2>' . $safe_title . '</h2>'
            . '<p>' . $safe_body . '</p>'
            . '</section>';
    }

    /**
     * Sanitize HTML for Gutenberg raw HTML / classic block insertion.
     * Rejects empty result after strip of active content.
     *
     * @return array{ok:bool,html:string,code:string}
     */
    public static function sanitize_html_for_insert(string $html): array
    {
        $html = str_replace("\0", '', $html);
        if (strlen($html) > self::MAX_HTML_BYTES) {
            return ['ok' => false, 'html' => '', 'code' => 'too_large'];
        }

        // Hard reject obvious active content before WP KSES (works offline in pure tests).
        if (preg_match('#<\s*(script|iframe|object|embed|form|link|meta|base)\b#i', $html) === 1) {
            return ['ok' => false, 'html' => '', 'code' => 'forbidden_tag'];
        }
        if (preg_match('#\son[a-z]+\s*=#i', $html) === 1) {
            return ['ok' => false, 'html' => '', 'code' => 'event_handler'];
        }
        if (preg_match('#javascript\s*:#i', $html) === 1) {
            return ['ok' => false, 'html' => '', 'code' => 'javascript_uri'];
        }

        if (function_exists('wp_kses_post')) {
            $html = wp_kses_post($html);
        } else {
            $html = self::offline_kses_lite($html);
        }

        $html = trim($html);
        if ($html === '') {
            return ['ok' => false, 'html' => '', 'code' => 'empty'];
        }

        return ['ok' => true, 'html' => $html, 'code' => 'ok'];
    }

    /**
     * Convert sanitized HTML into a Gutenberg-friendly block markup string.
     */
    public static function to_block_markup(string $safe_html): string
    {
        $safe_html = trim($safe_html);
        if ($safe_html === '') {
            return '';
        }
        // Classic/HTML block — editor can convert further; markup is inert HTML only.
        return "<!-- wp:html -->\n" . $safe_html . "\n<!-- /wp:html -->";
    }

    private static function escape_text(string $text): string
    {
        if (function_exists('esc_html')) {
            return esc_html($text);
        }
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Minimal offline strip for pure tests without WordPress KSES.
     */
    private static function offline_kses_lite(string $html): string
    {
        $html = preg_replace('#<\s*/?\s*(script|iframe|object|embed|form|link|meta|base|style)[^>]*>#i', '', $html) ?? '';
        $html = preg_replace('#\son[a-z]+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)#i', '', $html) ?? '';
        $html = preg_replace('#(href|src)\s*=\s*([\'"])\s*javascript:[^\'"]*\2#i', '$1="#"', $html) ?? '';
        return $html;
    }
}
