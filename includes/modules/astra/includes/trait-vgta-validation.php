<?php

declare(strict_types=1);

// STATUS: DIAMANT VGT SUPREME

namespace VGTAstra\AgentSystem;

if (!defined('ABSPATH')) {
    exit;
}

trait ValidationTrait
{
    private function sanitizeRelativePath(string $filepath): string
    {
        $normalized = \trim($filepath);
        $normalized = \preg_replace('/\A\[(.*)\]\z/', '$1', $normalized);
        $normalized = \str_replace('\\', '/', (string) $normalized);
        $normalized = \trim($normalized);

        if (
            $normalized === ''
            || \strlen($normalized) > 220
            || \str_contains($normalized, "\0")
            || \preg_match('/\A[A-Za-z]:/i', $normalized) === 1
            || \str_starts_with($normalized, '/')
            || \preg_match('/\A[A-Za-z0-9._\-\/]+\z/', $normalized) !== 1
        ) {
            $this->throwTypedException('Path validation failed.', 'security');
        }

        foreach (\explode('/', $normalized) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                $this->throwTypedException('Path traversal rejected.', 'security');
            }
        }

        $extension = \strtolower((string) \pathinfo($normalized, \PATHINFO_EXTENSION));
        if (!\in_array($extension, ['php', 'js', 'css', 'json', 'txt', 'html', 'md'], true)) {
            $this->throwTypedException('File extension validation failed.', 'validation');
        }

        return $normalized;
    }

    /**
     * @param mixed $raw
     * @return array<int|string, mixed>
     */

    private function decodeJsonArray($raw): array
    {
        try {
            $decoded = \json_decode((string) $raw, true, 64, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $this->throwTypedException('JSON validation failed.', 'security');
        }

        if (!\is_array($decoded)) {
            $this->throwTypedException('JSON validation failed.', 'security');
        }

        return $decoded;
    }

    /**
     * @param array<int|string, mixed> $steps
     * @return list<array{role:string,model:string,instructions:string,reasoning_effort:string}>
     */

    private function sanitizeWorkflowSteps(array $steps): array
    {
        if (\count($steps) < 1 || \count($steps) > self::MAX_AGENT_STEPS) {
            $this->throwTypedException('Agent step validation failed.', 'security');
        }

        $sanitized = [];
        foreach (\array_values($steps) as $step) {
            if (!\is_array($step)) {
                $this->throwTypedException('Agent step validation failed.', 'security');
            }

            $role = isset($step['role']) ? \sanitize_text_field((string) $step['role']) : '';
            if (!isset(self::ROLE_PROMPTS[$role]) && $this->getCustomAgentPrompt($role) === '') {
                $this->throwTypedException('Agent role validation failed.', 'validation');
            }

            $model = $this->sanitizeModel(isset($step['model']) ? (string) $step['model'] : '');
            $instructions = $this->sanitizeBoundedText(isset($step['instructions']) ? (string) $step['instructions'] : '', 4000);
            if ($instructions === '') {
                $this->throwTypedException('Agent step validation failed.', 'security');
            }

            $sanitized[] = [
                'role' => $role,
                'model' => $model,
                'instructions' => $instructions,
                'reasoning_effort' => $this->sanitizeReasoningEffort($model, isset($step['reasoning_effort']) ? (string) $step['reasoning_effort'] : ''),
            ];
        }

        return $sanitized;
    }


    private function sanitizeModel(string $model): string
    {
        $clean = \sanitize_text_field($model);
        if (!isset(self::GROQ_MODELS[$clean])) {
            $this->throwTypedException('Model validation failed.', 'validation');
        }

        return $clean;
    }


    private function sanitizeReasoningEffort(string $model, string $reasoningEffort): string
    {
        if (!isset(self::GROQ_MODELS[$model])) {
            $this->throwTypedException('Model validation failed.', 'validation');
        }

        $allowed = self::GROQ_MODELS[$model]['reasoning_values'];
        $default = self::GROQ_MODELS[$model]['reasoning_default'];
        if ($allowed === []) {
            return $default;
        }

        $clean = \sanitize_key($reasoningEffort);
        if ($clean === '' || $clean === 'auto') {
            return $default;
        }

        if (\in_array($clean, $allowed, true)) {
            return $clean;
        }

        if (\in_array($clean, ['low', 'medium', 'high'], true) && \in_array('default', $allowed, true)) {
            return 'default';
        }

        if (\in_array($clean, ['default', 'none'], true) && \in_array('high', $allowed, true)) {
            return 'high';
        }

        return $default;
    }

    /**
     * @param array<int|string, mixed> $history
     * @return list<array{role:string,content:string}>
     */

    private function sanitizeHistory(array $history): array
    {
        $sanitized = [];
        foreach (\array_slice(\array_values($history), -self::MAX_HISTORY_MESSAGES) as $message) {
            if (!\is_array($message)) {
                continue;
            }

            $role = isset($message['role']) ? \sanitize_key((string) $message['role']) : '';
            if (!\in_array($role, ['assistant', 'user'], true)) {
                continue;
            }

            $content = $this->sanitizeBoundedText(isset($message['content']) ? (string) $message['content'] : '', self::MAX_CHAT_BYTES);
            if ($content !== '') {
                $sanitized[] = ['role' => $role, 'content' => $content];
            }
        }

        return $sanitized;
    }


    /**
     * @param array<int|string, mixed> $ledger
     * @return list<array{role:string,model:string,loop:int,content:string}>
     */
    private function sanitizePipelineLedger(array $ledger): array
    {
        $sanitized = [];
        foreach (\array_slice(\array_values($ledger), -self::MAX_LEDGER_ENTRIES) as $entry) {
            if (!\is_array($entry)) {
                continue;
            }

            $role = isset($entry['role']) ? \sanitize_text_field((string) $entry['role']) : '';
            $model = isset($entry['model']) ? $this->sanitizeModel((string) $entry['model']) : '';
            $loop = isset($entry['loop']) ? \absint($entry['loop']) : 0;
            $content = $this->sanitizeBoundedText(isset($entry['content']) ? (string) $entry['content'] : '', self::MAX_LEDGER_ENTRY_BYTES);
            if ($role !== '' && $model !== '' && $loop > 0 && $content !== '') {
                $sanitized[] = [
                    'role' => $role,
                    'model' => $model,
                    'loop' => $loop,
                    'content' => $content,
                ];
            }
        }

        return $sanitized;
    }


    private function sanitizeBoundedText(string $value, int $maxLength): string
    {
        $clean = \sanitize_textarea_field($value);
        if (\strlen($clean) > $maxLength) {
            $clean = \substr($clean, 0, $maxLength);
        }

        return \trim($clean);
    }

    /**
     * @param array<string, mixed> $pluginMap
     * @param list<array{role:string,content:string}> $history
     * @return list<array{role:string,content:string}>
     */
}
