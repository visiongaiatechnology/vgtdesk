<?php

declare(strict_types=1);

// STATUS: DIAMANT VGT SUPREME

namespace VGTAstra\AgentSystem;

if (!defined('ABSPATH')) {
    exit;
}

trait MemoryStoreTrait
{
    /**
     * @return array{sessions:array<string,mixed>,artifacts:array<int,mixed>}
     */
    private function loadMemoryStore(string $pluginSlug): array
    {
        $file = $this->getMemoryStoreFile($pluginSlug);
        if (!\is_file($file)) {
            return ['sessions' => [], 'artifacts' => []];
        }

        $size = \filesize($file);
        if ($size === false || $size > 2097152) {
            $this->rotateCorruptMemoryStore($file);
            return ['sessions' => [], 'artifacts' => []];
        }

        $json = \file_get_contents($file);
        if ($json === false || $json === '') {
            return ['sessions' => [], 'artifacts' => []];
        }

        try {
            $decoded = \json_decode($json, true, 64, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $this->logInternalThrowable('MEMORY', $this->buildOpaqueErrorCode($e), $e);
            $this->rotateCorruptMemoryStore($file);
            return ['sessions' => [], 'artifacts' => []];
        }

        if (!\is_array($decoded)) {
            return ['sessions' => [], 'artifacts' => []];
        }

        return [
            'sessions' => isset($decoded['sessions']) && \is_array($decoded['sessions']) ? $decoded['sessions'] : [],
            'artifacts' => isset($decoded['artifacts']) && \is_array($decoded['artifacts']) ? $decoded['artifacts'] : [],
        ];
    }

    /**
     * @param array{sessions:array<string,mixed>,artifacts:array<int,mixed>} $store
     */
    private function saveMemoryStore(string $pluginSlug, array $store): void
    {
        try {
            $this->writeMemoryPayload($this->getMemoryStoreFile($pluginSlug), $this->buildMemoryPayload($pluginSlug, $store));
            return;
        } catch (\Throwable $e) {
            $this->logInternalThrowable('MEMORY', $this->buildOpaqueErrorCode($e), $e);
        }

        try {
            $prunedStore = [
                'sessions' => \array_slice($store['sessions'], -10, null, true),
                'artifacts' => \array_slice(\array_values($store['artifacts']), -30),
            ];
            $this->writeMemoryPayload($this->getMemoryStoreFile($pluginSlug), $this->buildMemoryPayload($pluginSlug, $prunedStore));
            $this->lastMemoryWarning = 'Memory store was pruned or rotated to keep pipeline stable.';
            return;
        } catch (\Throwable $e) {
            $this->logInternalThrowable('MEMORY', $this->buildOpaqueErrorCode($e), $e);
        }

        try {
            $file = $this->getMemoryStoreFile($pluginSlug);
            $this->rotateCorruptMemoryStore($file);
            $this->writeMemoryPayload($file, $this->buildMemoryPayload($pluginSlug, ['sessions' => [], 'artifacts' => []]));
            $this->lastMemoryWarning = 'Memory store was pruned or rotated to keep pipeline stable.';
        } catch (\Throwable $e) {
            $this->logInternalThrowable('MEMORY', $this->buildOpaqueErrorCode($e), $e);
        }
    }

    /**
     * @param array{sessions:array<string,mixed>,artifacts:array<int,mixed>} $store
     * @return array<string,mixed>
     */
    private function buildMemoryPayload(string $pluginSlug, array $store): array
    {
        return $this->normalizeMemoryValue([
            'version' => 1,
            'plugin_slug_hash' => \hash_hmac('sha256', $pluginSlug, self::MEMORY_CONTEXT),
            'updated_at' => \gmdate('c'),
            'sessions' => \array_slice($store['sessions'], -self::MAX_MEMORY_SESSIONS, null, true),
            'artifacts' => \array_slice(\array_values($store['artifacts']), -self::MAX_MEMORY_ARTIFACTS),
        ]);
    }

    /**
     * @param array<string,mixed> $payload
     */
    private function writeMemoryPayload(string $file, array $payload): void
    {
        $json = \json_encode($payload, \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_INVALID_UTF8_SUBSTITUTE);
        if (!\is_string($json)) {
            throw new StorageException('Memory store serialization failed.');
        }

        $temporary = $file . '.tmp_' . \bin2hex(\random_bytes(8));
        $bytes = @\file_put_contents($temporary, $json, \LOCK_EX);
        if ($bytes === false || $bytes !== \strlen($json)) {
            throw new StorageException('Memory store write failed.');
        }
        @\chmod($temporary, 0600);

        if (@\rename($temporary, $file) !== true) {
            @\unlink($temporary);
            throw new StorageException('Memory store commit failed.');
        }
        @\chmod($file, 0600);
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private function normalizeMemoryValue($value, int $depth = 0)
    {
        if ($depth > 8) {
            return '[VGTA_DEPTH_LIMIT]';
        }

        if (\is_string($value)) {
            $clean = \preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $value) ?? '';
            if (\strlen($clean) > 12000) {
                $clean = \substr($clean, 0, 12000) . "\n[VGTA_TRUNCATED]";
            }

            if (\function_exists('mb_convert_encoding')) {
                return \mb_convert_encoding($clean, 'UTF-8', 'UTF-8');
            }

            return \wp_check_invalid_utf8($clean, true);
        }

        if (\is_int($value) || \is_float($value) || \is_bool($value) || $value === null) {
            return $value;
        }

        if (\is_array($value)) {
            $out = [];
            foreach ($value as $key => $child) {
                $safeKey = \is_string($key) ? \preg_replace('/[^A-Za-z0-9_\-.]/', '_', $key) : (string) $key;
                if ($safeKey === '' || $safeKey === null) {
                    $safeKey = 'key_' . (string) \count($out);
                }
                $out[$safeKey] = $this->normalizeMemoryValue($child, $depth + 1);
            }
            return $out;
        }

        return '[VGTA_UNSERIALIZABLE]';
    }

    private function rotateCorruptMemoryStore(string $file): void
    {
        if (!\is_file($file)) {
            return;
        }

        try {
            $suffix = \bin2hex(\random_bytes(4));
        } catch (\Throwable $e) {
            $suffix = \substr(\hash('sha256', $file . '|' . \microtime(true)), 0, 8);
        }

        $rotated = $file . '.corrupt.' . \gmdate('YmdHis') . '.' . $suffix;
        @\rename($file, $rotated);
        if (\is_file($rotated)) {
            @\chmod($rotated, 0600);
        }
    }

    private function consumeMemoryWarning(): string
    {
        $warning = $this->lastMemoryWarning;
        $this->lastMemoryWarning = '';
        return $warning;
    }

    private function getMemoryStoreFile(string $pluginSlug): string
    {
        $memoryRoot = $this->ensureWorkspaceDirectory($this->getSecureWorkspaceRoot(), self::MEMORY_DIR_NAME);
        $fileName = \hash_hmac('sha256', $pluginSlug === '' ? 'global' : $pluginSlug, self::MEMORY_CONTEXT) . '.json';
        $file = $memoryRoot . \DIRECTORY_SEPARATOR . $fileName;
        if (!\str_starts_with($file, $memoryRoot . \DIRECTORY_SEPARATOR)) {
            $this->throwTypedException('Path escaped jail.', 'security');
        }

        return $file;
    }

    /**
     * @param array<string,mixed> $apiResponse
     * @param list<array<string,mixed>> $proposals
     */
    private function persistAssistantExchange(string $pluginSlug, string $sessionId, string $operatorMessage, string $role, string $model, array $apiResponse, array $proposals): array
    {
        $sessionId = $this->sanitizeMemoryId($sessionId);
        if ($sessionId === '') {
            $sessionId = \bin2hex(\random_bytes(16));
        }

        $store = $this->loadMemoryStore($pluginSlug);
        $now = \gmdate('c');
        $session = isset($store['sessions'][$sessionId]) && \is_array($store['sessions'][$sessionId])
            ? $store['sessions'][$sessionId]
            : [
                'id' => $sessionId,
                'title' => $this->buildMemoryTitle($operatorMessage, $role),
                'created_at' => $now,
                'updated_at' => $now,
                'messages' => [],
                'artifact_ids' => [],
            ];

        $messages = isset($session['messages']) && \is_array($session['messages']) ? $session['messages'] : [];
        if ($operatorMessage !== '') {
            $messages[] = [
                'role' => 'user',
                'label' => 'Operator',
                'content' => $this->sanitizeMemoryText($operatorMessage, self::MAX_CHAT_BYTES),
                'created_at' => $now,
            ];
        }

        $assistantContent = $this->sanitizeMemoryText((string) ($apiResponse['content'] ?? ''), self::MAX_ARTIFACT_BYTES);
        $messages[] = [
            'role' => 'assistant',
            'label' => $this->sanitizeBoundedText($role . ' - ' . $model, 160),
            'content' => $assistantContent,
            'created_at' => $now,
        ];

        $artifactId = \bin2hex(\random_bytes(16));
        $artifact = [
            'id' => $artifactId,
            'session_id' => $sessionId,
            'type' => 'assistant_output',
            'role' => $this->sanitizeBoundedText($role, 80),
            'model' => $this->sanitizeModel($model),
            'title' => $this->buildMemoryTitle($assistantContent, $role),
            'content' => $assistantContent,
            'reasoning' => $this->sanitizeMemoryText((string) ($apiResponse['reasoning'] ?? ''), self::MAX_ARTIFACT_BYTES),
            'proposal_count' => \count($proposals),
            'created_at' => $now,
        ];

        $artifactIds = isset($session['artifact_ids']) && \is_array($session['artifact_ids']) ? $session['artifact_ids'] : [];
        $artifactIds[] = $artifactId;
        $session['messages'] = \array_slice($messages, -self::MAX_MEMORY_MESSAGES);
        $session['artifact_ids'] = \array_slice($artifactIds, -self::MAX_MEMORY_ARTIFACTS);
        $session['updated_at'] = $now;
        $store['sessions'][$sessionId] = $session;
        $store['artifacts'][] = $artifact;
        $this->saveMemoryStore($pluginSlug, $store);

        return [
            'session_id' => $sessionId,
            'artifact' => $artifact,
            'memory' => $this->summarizeMemoryStore($pluginSlug),
        ];
    }

    private function sanitizeMemoryId(string $value): string
    {
        $clean = \sanitize_text_field($value);
        return \preg_match('/\A[a-f0-9]{32}\z/i', $clean) === 1 ? \strtolower($clean) : '';
    }

    private function buildMemoryTitle(string $content, string $fallback): string
    {
        $source = $content !== '' ? $content : $fallback;
        $source = \preg_replace('/\s+/', ' ', $this->sanitizeBoundedText($source, 180));
        $title = \trim((string) $source);
        if ($title === '') {
            return 'VGTAstra Memory';
        }

        return \strlen($title) > 96 ? \substr($title, 0, 93) . '...' : $title;
    }

    private function sanitizeMemoryText(string $value, int $maxLength): string
    {
        $clean = \wp_check_invalid_utf8(\str_replace("\0", '', $value), true);
        $clean = \preg_replace('/[^\P{C}\t\r\n]/u', '', (string) $clean);
        if (\strlen((string) $clean) > $maxLength) {
            $clean = \substr((string) $clean, 0, $maxLength);
        }

        return \trim((string) $clean);
    }

    private function summarizeMemoryStore(string $pluginSlug): array
    {
        $store = $this->loadMemoryStore($pluginSlug);
        $sessions = [];
        foreach ($store['sessions'] as $session) {
            if (!\is_array($session)) {
                continue;
            }
            $sessions[] = [
                'id' => isset($session['id']) ? (string) $session['id'] : '',
                'title' => isset($session['title']) ? (string) $session['title'] : '',
                'updated_at' => isset($session['updated_at']) ? (string) $session['updated_at'] : '',
                'message_count' => isset($session['messages']) && \is_array($session['messages']) ? \count($session['messages']) : 0,
            ];
        }

        \usort($sessions, static fn (array $a, array $b): int => \strcmp((string) $b['updated_at'], (string) $a['updated_at']));

        $artifacts = [];
        foreach (\array_reverse(\array_values($store['artifacts'])) as $artifact) {
            if (!\is_array($artifact)) {
                continue;
            }
            $artifacts[] = [
                'id' => isset($artifact['id']) ? (string) $artifact['id'] : '',
                'session_id' => isset($artifact['session_id']) ? (string) $artifact['session_id'] : '',
                'title' => isset($artifact['title']) ? (string) $artifact['title'] : '',
                'role' => isset($artifact['role']) ? (string) $artifact['role'] : '',
                'model' => isset($artifact['model']) ? (string) $artifact['model'] : '',
                'created_at' => isset($artifact['created_at']) ? (string) $artifact['created_at'] : '',
            ];
            if (\count($artifacts) >= 40) {
                break;
            }
        }

        return ['sessions' => $sessions, 'artifacts' => $artifacts];
    }

    private function getMemorySession(string $pluginSlug, string $sessionId): array
    {
        $sessionId = $this->sanitizeMemoryId($sessionId);
        if ($sessionId === '') {
            $this->throwTypedException('Memory token validation failed.', 'security');
        }

        $store = $this->loadMemoryStore($pluginSlug);
        if (!isset($store['sessions'][$sessionId]) || !\is_array($store['sessions'][$sessionId])) {
            throw new ValidationException('Memory session not found.');
        }

        return $store['sessions'][$sessionId];
    }

    private function getMemoryArtifact(string $pluginSlug, string $artifactId): array
    {
        $artifactId = $this->sanitizeMemoryId($artifactId);
        if ($artifactId === '') {
            $this->throwTypedException('Memory token validation failed.', 'security');
        }

        foreach ($this->loadMemoryStore($pluginSlug)['artifacts'] as $artifact) {
            if (\is_array($artifact) && isset($artifact['id']) && \hash_equals((string) $artifact['id'], $artifactId)) {
                return $artifact;
            }
        }

        throw new ValidationException('Memory artifact not found.');
    }
}
