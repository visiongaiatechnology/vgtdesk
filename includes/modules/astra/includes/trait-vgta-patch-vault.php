<?php

declare(strict_types=1);

// STATUS: DIAMANT VGT SUPREME

namespace VGTAstra\AgentSystem;

if (!defined('ABSPATH')) {
    exit;
}

trait PatchVaultTrait
{
    private function resolvePatchDestination(array $scope, string $relativePath): string
    {
        $root = \realpath($scope['root']);
        if ($root === false || !\is_dir($root)) {
            $this->throwTypedException('Plugin storage root unavailable.', 'storage');
        }

        if ($scope['single_file'] !== null && !\hash_equals($scope['single_file'], $relativePath)) {
            throw new ValidationException('Single-file plugins can only rewrite their primary file.');
        }

        $destination = $root . \DIRECTORY_SEPARATOR . \str_replace('/', \DIRECTORY_SEPARATOR, $relativePath);
        if (!\str_starts_with($destination, $root . \DIRECTORY_SEPARATOR)) {
            $this->throwTypedException('Path escaped jail.', 'security');
        }

        if (\file_exists($destination)) {
            $resolvedDestination = \realpath($destination);
            if ($resolvedDestination === false || !\str_starts_with($resolvedDestination, $root . \DIRECTORY_SEPARATOR)) {
                $this->throwTypedException('Path escaped jail.', 'security');
            }
        }

        return $destination;
    }

    /**
     * @param array{root:string,single_file:?string} $scope
     * @return array{mode:string,message:string,workspace_path:string}
     */

    private function writePatchWithWorkspaceFallback(string $pluginSlug, array $scope, string $relativePath, string $destination, string $codeRaw): array
    {
        $targetWrite = $this->tryWriteTargetPatch($scope, $relativePath, $destination, $codeRaw);
        if ($targetWrite === true) {
            return [
                'mode' => 'target',
                'message' => 'Staged patch committed to target plugin: ' . $relativePath . '.',
                'workspace_path' => '',
            ];
        }

        $workspacePath = $this->writePatchToSecureWorkspace($pluginSlug, $relativePath, $codeRaw);
        return [
            'mode' => 'workspace',
            'message' => 'Target plugin filesystem denied direct write. Patch stored in secure VGTAstra workspace: ' . $workspacePath . '.',
            'workspace_path' => $workspacePath,
        ];
    }

    /**
     * @param array{root:string,single_file:?string} $scope
     */

    private function tryWriteTargetPatch(array $scope, string $relativePath, string $destination, string $codeRaw): bool
    {
        $root = \realpath($scope['root']);
        if ($root === false || !\is_dir($root)) {
            return false;
        }

        $parentRelative = \dirname($relativePath);
        $parentDir = $parentRelative === '.' || $parentRelative === ''
            ? $root
            : $this->tryEnsurePatchParentDirectory($root, $parentRelative);
        if ($parentDir === '') {
            return false;
        }

        $destination = $parentDir . \DIRECTORY_SEPARATOR . \basename($relativePath);
        if (!\str_starts_with($destination, $root . \DIRECTORY_SEPARATOR)) {
            $this->throwTypedException('Path escaped jail.', 'security');
        }

        if (\file_exists($destination)) {
            $resolvedDestination = \realpath($destination);
            if ($resolvedDestination === false || !\str_starts_with($resolvedDestination, $root . \DIRECTORY_SEPARATOR)) {
                $this->throwTypedException('Path escaped jail.', 'security');
            }

            $backupPath = $destination . '.vgta_backup_' . \gmdate('YmdHis') . '_' . \bin2hex(\random_bytes(8));
            if (@\copy($destination, $backupPath) !== true) {
                return false;
            }
        }

        $bytesWritten = @\file_put_contents($destination, $codeRaw, \LOCK_EX);
        if ($bytesWritten === false || $bytesWritten !== \strlen($codeRaw)) {
            return false;
        }

        $writtenPath = \realpath($destination);
        if ($writtenPath === false || !\is_file($writtenPath) || !\str_starts_with($writtenPath, $root . \DIRECTORY_SEPARATOR)) {
            $this->throwTypedException('Path escaped jail.', 'security');
        }

        return true;
    }


    private function tryEnsurePatchParentDirectory(string $root, string $relativeParent): string
    {
        $current = $root;
        foreach (\explode('/', \str_replace('\\', '/', $relativeParent)) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                $this->throwTypedException('Path traversal rejected.', 'security');
            }

            $candidate = $current . \DIRECTORY_SEPARATOR . $segment;
            if (\is_link($candidate)) {
                $this->throwTypedException('Path escaped jail.', 'security');
            }

            if (\file_exists($candidate)) {
                $resolvedCandidate = \realpath($candidate);
                if ($resolvedCandidate === false || !\is_dir($resolvedCandidate) || !\str_starts_with($resolvedCandidate, $root . \DIRECTORY_SEPARATOR)) {
                    return '';
                }

                $current = $resolvedCandidate;
                continue;
            }

            $mode = \defined('FS_CHMOD_DIR') ? (int) \constant('FS_CHMOD_DIR') : 0755;
            if (@\mkdir($candidate, $mode) !== true && !\is_dir($candidate)) {
                return '';
            }

            $resolvedCreated = \realpath($candidate);
            if ($resolvedCreated === false || !\is_dir($resolvedCreated) || !\str_starts_with($resolvedCreated, $root . \DIRECTORY_SEPARATOR)) {
                $this->throwTypedException('Path escaped jail.', 'security');
            }

            $current = $resolvedCreated;
        }

        return $current;
    }


    private function writePatchToSecureWorkspace(string $pluginSlug, string $relativePath, string $codeRaw): string
    {
        $workspaceRoot = $this->getSecureWorkspaceRoot();
        $pluginWorkspace = $this->ensureWorkspaceDirectory($workspaceRoot, \hash_hmac('sha256', $pluginSlug, self::WORKSPACE_CONTEXT));
        $patchRoot = $this->ensureWorkspaceDirectory($pluginWorkspace, \gmdate('Ymd_His') . '_' . \bin2hex(\random_bytes(8)));

        $parentRelative = \dirname($relativePath);
        $parentDir = $parentRelative === '.' || $parentRelative === ''
            ? $patchRoot
            : $this->ensureWorkspaceRelativeDirectory($patchRoot, $parentRelative);

        $workspaceDestination = $parentDir . \DIRECTORY_SEPARATOR . \basename($relativePath);
        if (!\str_starts_with($workspaceDestination, $patchRoot . \DIRECTORY_SEPARATOR)) {
            $this->throwTypedException('Path escaped jail.', 'security');
        }

        $bytesWritten = @\file_put_contents($workspaceDestination, $codeRaw, \LOCK_EX);
        if ($bytesWritten === false || $bytesWritten !== \strlen($codeRaw)) {
            throw new StorageException('Workspace write failed.');
        }
        @\chmod($workspaceDestination, 0600);

        $resolvedDestination = \realpath($workspaceDestination);
        if ($resolvedDestination === false || !\is_file($resolvedDestination) || !\str_starts_with($resolvedDestination, $patchRoot . \DIRECTORY_SEPARATOR)) {
            $this->throwTypedException('Path escaped jail.', 'security');
        }

        return $this->getRelativeWorkspacePath($resolvedDestination, $workspaceRoot);
    }


    private function getSecureWorkspaceRoot(): string
    {
        foreach ($this->getWorkspaceBaseCandidates() as $basePath) {
            $workspace = $this->tryPrepareWorkspaceRoot($basePath);
            if ($workspace !== '') {
                return $workspace;
            }
        }

        throw new StorageException('No writable secure workspace root available.');
    }

    /**
     * @return list<string>
     */

    private function getWorkspaceBaseCandidates(): array
    {
        $candidates = [];
        $uploadDir = \wp_upload_dir(null, false);
        if (\is_array($uploadDir) && isset($uploadDir['basedir']) && \is_string($uploadDir['basedir']) && $uploadDir['basedir'] !== '') {
            $candidates[] = $uploadDir['basedir'];
        }

        if (\defined('WP_CONTENT_DIR')) {
            $candidates[] = (string) \constant('WP_CONTENT_DIR');
        }

        $candidates[] = VGTA_PLUGIN_DIR;

        return \array_values(\array_unique(\array_filter($candidates, static fn (string $path): bool => $path !== '')));
    }


    private function tryPrepareWorkspaceRoot(string $basePath): string
    {
        $resolvedBase = \realpath($basePath);
        if ($resolvedBase === false || !\is_dir($resolvedBase)) {
            return '';
        }

        $workspaceRoot = $resolvedBase . \DIRECTORY_SEPARATOR . self::WORKSPACE_DIR_NAME;
        if (!\is_dir($workspaceRoot) && @\mkdir($workspaceRoot, 0700) !== true && !\is_dir($workspaceRoot)) {
            return '';
        }

        $resolvedWorkspace = \realpath($workspaceRoot);
        if ($resolvedWorkspace === false || !\is_dir($resolvedWorkspace) || !\str_starts_with($resolvedWorkspace, $resolvedBase . \DIRECTORY_SEPARATOR)) {
            return '';
        }

        $this->hardenWorkspaceDirectory($resolvedWorkspace);
        return $resolvedWorkspace;
    }


    private function ensureWorkspaceDirectory(string $parent, string $directoryName): string
    {
        if (\preg_match('/\A[a-f0-9_]{16,80}\z/i', $directoryName) !== 1) {
            $this->throwTypedException('Path validation failed.', 'security');
        }

        $destination = $parent . \DIRECTORY_SEPARATOR . $directoryName;
        if (!\is_dir($destination) && @\mkdir($destination, 0700) !== true && !\is_dir($destination)) {
            throw new StorageException('Workspace directory creation failed.');
        }

        $resolved = \realpath($destination);
        if ($resolved === false || !\is_dir($resolved) || !\str_starts_with($resolved, $parent . \DIRECTORY_SEPARATOR)) {
            $this->throwTypedException('Path escaped jail.', 'security');
        }

        $this->hardenWorkspaceDirectory($resolved);
        return $resolved;
    }


    private function ensureWorkspaceRelativeDirectory(string $root, string $relativeParent): string
    {
        $current = $root;
        foreach (\explode('/', \str_replace('\\', '/', $relativeParent)) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..' || \preg_match('/\A[A-Za-z0-9._\-]+\z/', $segment) !== 1) {
                $this->throwTypedException('Path traversal rejected.', 'security');
            }

            $candidate = $current . \DIRECTORY_SEPARATOR . $segment;
            if (!\is_dir($candidate) && @\mkdir($candidate, 0700) !== true && !\is_dir($candidate)) {
                throw new StorageException('Workspace directory creation failed.');
            }

            $resolved = \realpath($candidate);
            if ($resolved === false || !\is_dir($resolved) || !\str_starts_with($resolved, $root . \DIRECTORY_SEPARATOR)) {
                $this->throwTypedException('Path escaped jail.', 'security');
            }

            $this->hardenWorkspaceDirectory($resolved);
            $current = $resolved;
        }

        return $current;
    }


    private function hardenWorkspaceDirectory(string $directory): void
    {
        @\chmod($directory, 0700);
        $this->writeWorkspaceGuardFile($directory . \DIRECTORY_SEPARATOR . 'index.php', "<?php\nhttp_response_code(404);\nexit;\n");
        $this->writeWorkspaceGuardFile($directory . \DIRECTORY_SEPARATOR . '.htaccess', "Require all denied\nDeny from all\nOptions -Indexes\n");
        $this->writeWorkspaceGuardFile($directory . \DIRECTORY_SEPARATOR . 'web.config', "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<configuration><system.webServer><authorization><remove users=\"*\" roles=\"\" verbs=\"\" /><add accessType=\"Deny\" users=\"*\" /></authorization></system.webServer></configuration>\n");
    }


    private function writeWorkspaceGuardFile(string $path, string $content): void
    {
        if (\is_file($path)) {
            return;
        }

        @\file_put_contents($path, $content, \LOCK_EX);
        if (\is_file($path)) {
            @\chmod($path, 0600);
        }
    }


    private function getRelativeWorkspacePath(string $absolutePath, string $workspaceRoot): string
    {
        if (!\str_starts_with($absolutePath, $workspaceRoot . \DIRECTORY_SEPARATOR)) {
            $this->throwTypedException('Path escaped jail.', 'security');
        }

        return self::WORKSPACE_DIR_NAME . '/' . \str_replace(\DIRECTORY_SEPARATOR, '/', \substr($absolutePath, \strlen($workspaceRoot) + 1));
    }


    private function stageFileWritesFromContent(string $pluginSlug, string $actor, string $model, string $content): array
    {
        $this->lastRejectedWrites = [];
        if ($pluginSlug === '') {
            return [];
        }

        $vault = $this->getPatchVault($pluginSlug);
        $pattern = '/FILE_WRITE:\s*(?:\[([^\]\r\n]+)\]|([^\r\n]+))\s*(`{3,}|~{3,})([A-Za-z0-9_+\-]*)\s*\R([\s\S]*?)\R\3/m';
        if (\preg_match_all($pattern, $content, $matches, \PREG_SET_ORDER) <= 0) {
            return $this->summarizePatchVault($pluginSlug);
        }

        foreach ($matches as $match) {
            $rawPath = \trim($match[1] !== '' ? $match[1] : $match[2]);
            $normalizedPath = $this->normalizeAiWritePath($rawPath, $pluginSlug);

            try {
                $relativePath = $this->sanitizeRelativePath($normalizedPath);
                $code = \trim((string) $match[5]);
                if ($code === '' || \strlen($code) > self::MAX_WRITE_BYTES) {
                    throw new ValidationException('Write payload size rejected.');
                }

                $id = \bin2hex(\random_bytes(32));
            } catch (\Throwable $e) {
                $this->recordRejectedWrite([
                    'path' => $rawPath,
                    'normalized_path' => $normalizedPath,
                    'reason' => $e instanceof ValidationException ? $e->getMessage() : 'Patch staging rejected.',
                    'classification' => $this->classifyAiWritePathFailure($rawPath, $normalizedPath, $e),
                ]);
                $this->logInternalThrowable('PATCH_REJECTED', $this->buildOpaqueErrorCode($e), $e);
                continue;
            }

            $vault[$id] = [
                'id' => $id,
                'path' => $relativePath,
                'code' => $code,
                'language' => \sanitize_key((string) $match[4]),
                'actor' => $actor,
                'model' => $model,
                'bytes' => \strlen($code),
                'created_at' => \gmdate('c'),
                'committed_at' => '',
                'commit_mode' => 'pending',
                'workspace_path' => '',
            ];
        }

        $this->savePatchVault($pluginSlug, $vault);
        return $this->summarizePatchVault($pluginSlug);
    }

    /**
     * @return array<string, array<string, mixed>>
     */

    private function getPatchVault(string $pluginSlug): array
    {
        $vault = \get_option($this->getPatchVaultOptionKey($pluginSlug), []);
        if (\is_array($vault)) {
            return $vault;
        }

        if (!\is_string($vault) || $vault === '') {
            return [];
        }

        try {
            $json = CryptoVault::decrypt($vault, $this->getPatchVaultCryptoContext($pluginSlug));
            $decoded = \json_decode($json, true, 64, \JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            throw new StorageException('Patch vault decryption failed.');
        }

        return \is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<string, array<string, mixed>> $vault
     */

    private function savePatchVault(string $pluginSlug, array $vault): void
    {
        try {
            $json = \json_encode($vault, \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES);
            $encryptedVault = CryptoVault::encrypt($json, $this->getPatchVaultCryptoContext($pluginSlug));
        } catch (\Throwable $e) {
            throw new StorageException('Patch vault encryption failed.');
        }

        $status = \update_option($this->getPatchVaultOptionKey($pluginSlug), $encryptedVault, false);
        if ($status === false && \get_option($this->getPatchVaultOptionKey($pluginSlug), '') !== $encryptedVault) {
            $this->throwTypedException('Patch vault storage failed.', 'storage');
        }
    }


    private function getPatchVaultCryptoContext(string $pluginSlug): string
    {
        return self::PATCH_VAULT_CONTEXT . ':' . \hash_hmac('sha256', $pluginSlug, self::MENU_SLUG);
    }

    /**
     * @return list<array{id:string,path:string,bytes:int,actor:string,model:string,created_at:string,committed:bool,commit_mode:string,workspace_path:string}>
     */

    private function summarizePatchVault(string $pluginSlug): array
    {
        $summary = [];
        foreach ($this->getPatchVault($pluginSlug) as $id => $proposal) {
            if (!\is_array($proposal)) {
                continue;
            }

            $summary[] = [
                'id' => (string) $id,
                'path' => isset($proposal['path']) ? (string) $proposal['path'] : '',
                'bytes' => isset($proposal['bytes']) ? (int) $proposal['bytes'] : 0,
                'actor' => isset($proposal['actor']) ? (string) $proposal['actor'] : '',
                'model' => isset($proposal['model']) ? (string) $proposal['model'] : '',
                'created_at' => isset($proposal['created_at']) ? (string) $proposal['created_at'] : '',
                'committed' => isset($proposal['committed_at']) && (string) $proposal['committed_at'] !== '',
                'commit_mode' => isset($proposal['commit_mode']) ? (string) $proposal['commit_mode'] : '',
                'workspace_path' => isset($proposal['workspace_path']) ? (string) $proposal['workspace_path'] : '',
            ];
        }

        return $summary;
    }

}
