<?php

declare(strict_types=1);

// STATUS: DIAMANT VGT SUPREME

namespace VGTAstra\AgentSystem;

if (!defined('ABSPATH')) {
    exit;
}

trait PatchRepairTrait
{
    private function normalizeAiWritePath(string $path, string $pluginSlug): string
    {
        $normalized = \trim($path, " \t\n\r\0\x0B\"'`[]");
        $normalized = \preg_replace('/\s+/', '', $normalized) ?? '';
        $normalized = \str_replace('\\', '/', $normalized);
        if (
            \preg_match('/\A[A-Za-z]:/i', $normalized) === 1
            || \str_starts_with($normalized, '/')
            || \preg_match('/\A[A-Za-z][A-Za-z0-9+\-.]*:\/\//', $normalized) === 1
        ) {
            return $normalized;
        }

        while (\str_starts_with($normalized, './')) {
            $normalized = \substr($normalized, 2);
        }

        $pluginDir = \dirname(\trim($pluginSlug, '/'));
        if ($pluginDir === '.' || $pluginDir === '') {
            $pluginDir = '';
        }

        if ($pluginDir !== '' && \str_starts_with($normalized, $pluginDir . '/')) {
            $normalized = \substr($normalized, \strlen($pluginDir) + 1);
        }

        $marker = 'wp-content/plugins/';
        $markerPos = \stripos($normalized, $marker);
        if ($markerPos !== false) {
            $afterPlugins = \substr($normalized, $markerPos + \strlen($marker));
            if ($pluginDir !== '' && \str_starts_with($afterPlugins, $pluginDir . '/')) {
                $normalized = \substr($afterPlugins, \strlen($pluginDir) + 1);
            }
        }

        return \trim($normalized, '/');
    }

    /**
     * @param array<string,mixed> $write
     */
    private function recordRejectedWrite(array $write): void
    {
        $this->lastRejectedWrites[] = [
            'path' => isset($write['path']) ? \substr($this->sanitizeRejectedWriteText((string) $write['path']), 0, 220) : '',
            'normalized_path' => isset($write['normalized_path']) ? \substr($this->sanitizeRejectedWriteText((string) $write['normalized_path']), 0, 220) : '',
            'reason' => isset($write['reason']) ? \substr($this->sanitizeRejectedWriteText((string) $write['reason']), 0, 160) : 'rejected',
            'classification' => isset($write['classification']) ? \sanitize_key((string) $write['classification']) : 'unsafe_destination',
            'created_at' => \gmdate('c'),
        ];
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function consumeRejectedWrites(): array
    {
        $writes = $this->sanitizeRejectedWriteEvents($this->lastRejectedWrites);
        $this->lastRejectedWrites = [];
        return $writes;
    }

    /**
     * @param list<array<string,mixed>> $writes
     * @return list<array<string,mixed>>
     */
    private function sanitizeRejectedWriteEvents(array $writes): array
    {
        $safe = [];
        foreach (\array_slice($writes, -20) as $write) {
            if (!\is_array($write)) {
                continue;
            }

            $safe[] = [
                'path' => isset($write['path']) ? \substr($this->sanitizeRejectedWriteText((string) $write['path']), 0, 220) : '',
                'normalized_path' => isset($write['normalized_path']) ? \substr($this->sanitizeRejectedWriteText((string) $write['normalized_path']), 0, 220) : '',
                'reason' => isset($write['reason']) ? \substr($this->sanitizeRejectedWriteText((string) $write['reason']), 0, 160) : 'rejected',
                'classification' => isset($write['classification']) ? \sanitize_key((string) $write['classification']) : 'unsafe_destination',
                'created_at' => isset($write['created_at']) ? \sanitize_text_field((string) $write['created_at']) : \gmdate('c'),
            ];
        }

        return $safe;
    }

    private function classifyAiWritePathFailure(string $rawPath, string $normalizedPath, \Throwable $e): string
    {
        if (\str_contains($rawPath, "\0") || \str_contains($normalizedPath, "\0")) {
            return 'unsafe_destination';
        }

        if (\preg_match('/\A[A-Za-z][A-Za-z0-9+\-.]*:\/\//', $rawPath) === 1 || \preg_match('/\A[A-Za-z][A-Za-z0-9+\-.]*:\/\//', $normalizedPath) === 1) {
            return 'unsafe_destination';
        }

        if (\preg_match('/\A[A-Za-z]:/i', $rawPath) === 1 || \str_starts_with($rawPath, '/') || \str_starts_with($normalizedPath, '/')) {
            return 'absolute_path_mistake';
        }

        if (\str_contains($rawPath, '..') || \str_contains($normalizedPath, '..')) {
            return 'forbidden_traversal_attempt';
        }

        if ($e instanceof ValidationException) {
            return 'unsupported_file_type';
        }

        return $rawPath !== $normalizedPath ? 'harmless_formatting_error' : 'unsafe_destination';
    }

    private function sanitizeRejectedWriteText(string $value): string
    {
        $clean = \wp_check_invalid_utf8(\str_replace("\0", '', $value), true);
        $clean = \preg_replace('/[^\P{C}\t\r\n]/u', '', (string) $clean);
        return \trim((string) $clean);
    }
}
