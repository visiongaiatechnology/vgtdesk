<?php

declare(strict_types=1);

// STATUS: DIAMANT VGT SUPREME

namespace VGTAstra\AgentSystem;

if (!defined('ABSPATH')) {
    exit;
}

trait AjaxActionsTrait
{
    public function ajaxSaveCredentials(): void
    {
        $this->initializeErrorHandling();
        $this->sendJsonFromOperation(function (): array {
            $this->assertAjaxAccess();

            $apiKey = isset($_POST['api_key']) ? \sanitize_text_field((string) \wp_unslash($_POST['api_key'])) : '';
            if (\preg_match('/\Agsk_[A-Za-z0-9_\-]{20,}\z/', $apiKey) !== 1) {
                $this->throwTypedException('Groq API key format rejected.', 'validation');
            }

            $encrypted = CryptoVault::encrypt($apiKey, self::API_KEY_CONTEXT);
            $status = \update_option(self::OPTION_KEY_API_KEY, $encrypted, false);
            if ($status === false && \get_option(self::OPTION_KEY_API_KEY, '') !== $encrypted) {
                $this->throwTypedException('Credential vault storage failed.', 'storage');
            }

            VaultRegistry::addToIndex(self::OPTION_KEY_API_KEY);
            return ['message' => 'Groq credential sealed in VGTAstra encrypted vault.'];
        });
    }


    public function ajaxGeneratePluginMap(): void
    {
        $this->initializeErrorHandling();
        $this->sendJsonFromOperation(function (): array {
            $this->assertAjaxAccess();

            $pluginSlug = isset($_POST['plugin_slug']) ? \sanitize_text_field((string) \wp_unslash($_POST['plugin_slug'])) : '';
            $scope = $this->resolveInactivePluginScope($pluginSlug);
            $manifest = $this->buildPluginManifest($scope['root'], $pluginSlug, $scope['single_file']);

            $storageKey = $this->getPluginMapOptionKey($pluginSlug);
            $status = \update_option($storageKey, $manifest, false);
            if ($status === false && \get_option($storageKey, null) !== $manifest) {
                $this->throwTypedException('Plugin map storage failed.', 'storage');
            }

            return [
                'message' => 'Structural map cached. File context packs will be attached to chat and pipeline calls on demand.',
                'map' => $manifest,
                'proposals' => $this->summarizePatchVault($pluginSlug),
            ];
        });
    }


    public function ajaxChatMessage(): void
    {
        $this->initializeErrorHandling();
        $this->sendJsonFromOperation(function (): array {
            $this->assertAjaxAccess();

            $pluginSlug = isset($_POST['plugin_slug']) ? \sanitize_text_field((string) \wp_unslash($_POST['plugin_slug'])) : '';
            $model = $this->sanitizeModel(isset($_POST['model']) ? (string) \wp_unslash($_POST['model']) : 'openai/gpt-oss-20b');
            $reasoningEffort = $this->sanitizeReasoningEffort($model, isset($_POST['reasoning_effort']) ? (string) \wp_unslash($_POST['reasoning_effort']) : '');
            $message = $this->sanitizeBoundedText(isset($_POST['message']) ? (string) \wp_unslash($_POST['message']) : '', self::MAX_CHAT_BYTES);
            $history = $this->sanitizeHistory($this->decodeJsonArray(isset($_POST['history']) ? (string) \wp_unslash($_POST['history']) : '[]'));
            $pipelineLedger = $this->sanitizePipelineLedger($this->decodeJsonArray(isset($_POST['pipeline_ledger']) ? (string) \wp_unslash($_POST['pipeline_ledger']) : '[]'));
            $sessionId = isset($_POST['session_id']) ? $this->sanitizeMemoryId((string) \wp_unslash($_POST['session_id'])) : '';
            $groundingMode = !empty($_POST['use_grounding']) ? $this->sanitizeGroundingMode(isset($_POST['grounding_mode']) ? (string) \wp_unslash($_POST['grounding_mode']) : 'cited') : 'off';
            $groundingSources = isset($_POST['grounding_sources']) ? \max(1, \min(self::MAX_GROUNDING_SOURCES, \absint(\wp_unslash($_POST['grounding_sources'])))) : 3;
            $groundingDomains = isset($_POST['grounding_domains']) ? $this->sanitizeBoundedText((string) \wp_unslash($_POST['grounding_domains']), 600) : '';

            if ($message === '') {
                $this->throwTypedException('Chat message required.', 'validation');
            }

            $pluginMap = [];
            $fileContext = '';
            if ($pluginSlug !== '') {
                $scope = $this->resolveInactivePluginScope($pluginSlug);
                $pluginMap = $this->getOrBuildPluginMap($pluginSlug, $scope);
                $fileContext = $this->buildPluginFileContext($pluginSlug, $scope, $pluginMap, self::MAX_CONTEXT_PACK_BYTES);
            }

            $groundingPack = $this->buildGroundingPack($message, $groundingMode, $groundingSources, $groundingDomains);
            $task = $message;
            $groundingContext = $this->formatGroundingPackForPrompt($groundingPack);
            if ($groundingContext !== '') {
                $task .= "\n\n" . $groundingContext;
            }

            $apiResponse = $this->queryGroqGateway(
                $this->getDecryptedApiKey(),
                $model,
                $this->buildMessages('Assistant', $pluginSlug, $pluginMap, $fileContext, '', $history, $pipelineLedger, $task),
                $reasoningEffort,
                8192
            );

            $proposals = $this->stageFileWritesFromContent($pluginSlug, 'Assistant', $model, $apiResponse['stage_content']);
            $rejectedWrites = $this->consumeRejectedWrites();
            $memory = $this->persistAssistantExchange($pluginSlug, $sessionId, $message, 'Assistant', $model, $apiResponse, $proposals);
            $memoryWarning = $this->consumeMemoryWarning();
            $agentBlueprint = $this->shouldOfferAgentBlueprint($message) ? $this->buildAgentBlueprintFromIntent($message, $model) : null;

            return [
                'role' => 'Assistant',
                'model' => $model,
                'content' => $apiResponse['content'],
                'reasoning' => $apiResponse['reasoning'],
                'usage' => $apiResponse['usage'],
                'proposals' => $proposals,
                'rejected_writes' => $rejectedWrites,
                'session_id' => $memory['session_id'],
                'artifact' => $memory['artifact'],
                'memory' => $memory['memory'],
                'memory_warning' => $memoryWarning,
                'agent_blueprint' => $agentBlueprint,
                'grounding_pack' => $groundingPack,
            ];
        });
    }


    public function ajaxExecuteAgentStep(): void
    {
        $this->initializeErrorHandling();
        $this->sendJsonFromOperation(function (): array {
            $this->assertAjaxAccess();

            $pluginSlug = isset($_POST['plugin_slug']) ? \sanitize_text_field((string) \wp_unslash($_POST['plugin_slug'])) : '';
            $scope = $this->resolveInactivePluginScope($pluginSlug);

            $stepIndex = isset($_POST['step_index']) ? \absint(\wp_unslash($_POST['step_index'])) : 0;
            $steps = $this->sanitizeWorkflowSteps($this->decodeJsonArray(isset($_POST['steps']) ? (string) \wp_unslash($_POST['steps']) : '[]'));
            $history = \array_slice($this->sanitizeHistory($this->decodeJsonArray(isset($_POST['history']) ? (string) \wp_unslash($_POST['history']) : '[]')), -self::MAX_HISTORY_MESSAGES_FOR_PIPELINE);
            $pipelineLedger = \array_slice($this->sanitizePipelineLedger($this->decodeJsonArray(isset($_POST['pipeline_ledger']) ? (string) \wp_unslash($_POST['pipeline_ledger']) : '[]')), -self::MAX_PIPELINE_LEDGER_FOR_CONTEXT);
            $globalPrompt = isset($_POST['global_prompt']) ? $this->sanitizeBoundedText((string) \wp_unslash($_POST['global_prompt']), 6000) : '';
            $sessionId = isset($_POST['session_id']) ? $this->sanitizeMemoryId((string) \wp_unslash($_POST['session_id'])) : '';
            $loopIndex = isset($_POST['loop_index']) ? \max(1, \absint(\wp_unslash($_POST['loop_index']))) : 1;

            if (!isset($steps[$stepIndex])) {
                $this->throwTypedException('Agent step validation failed.', 'security');
            }

            $pluginMap = $this->getOrBuildPluginMap($pluginSlug, $scope);
            $fileContext = $this->buildPluginFileContext($pluginSlug, $scope, $pluginMap, self::MAX_CONTEXT_PACK_BYTES);

            $currentStep = $steps[$stepIndex];
            try {
                $apiResponse = $this->queryGroqGateway(
                    $this->getDecryptedApiKey(),
                    $currentStep['model'],
                    $this->buildMessages($currentStep['role'], $pluginSlug, $pluginMap, $fileContext, $globalPrompt, $history, $pipelineLedger, $currentStep['instructions']),
                    $currentStep['reasoning_effort'],
                    8192
                );
                $proposals = $this->stageFileWritesFromContent($pluginSlug, $currentStep['role'], $currentStep['model'], $apiResponse['stage_content']);
                $rejectedWrites = $this->consumeRejectedWrites();
                if ($rejectedWrites !== []) {
                    $repairEvent = new ValidationException('AI FILE_WRITE proposals were rejected by path or payload validation.');
                    $repairPayload = $this->handlePipelineStepFailure($pluginSlug, $stepIndex, $loopIndex, $currentStep, $history, $pipelineLedger, $globalPrompt, $sessionId, $repairEvent, $rejectedWrites);
                    $repairPayload['content'] = "ORIGINAL_AGENT_OUTPUT\n" . $apiResponse['content'] . "\n\nREPAIR_LAYER\n" . (string) $repairPayload['content'];
                    return $repairPayload;
                }
                $memory = $this->persistAssistantExchange($pluginSlug, $sessionId, '', $currentStep['role'], $currentStep['model'], $apiResponse, $proposals);
                $memoryWarning = $this->consumeMemoryWarning();
            } catch (\Throwable $e) {
                return $this->handlePipelineStepFailure($pluginSlug, $stepIndex, $loopIndex, $currentStep, $history, $pipelineLedger, $globalPrompt, $sessionId, $e, $this->consumeRejectedWrites());
            }

            return [
                'role' => $currentStep['role'],
                'model' => $currentStep['model'],
                'reasoning_effort' => $currentStep['reasoning_effort'],
                'content' => $apiResponse['content'],
                'reasoning' => $apiResponse['reasoning'],
                'usage' => $apiResponse['usage'],
                'proposals' => $proposals,
                'rejected_writes' => $rejectedWrites,
                'session_id' => $memory['session_id'],
                'artifact' => $memory['artifact'],
                'memory' => $memory['memory'],
                'memory_warning' => $memoryWarning,
                'pipeline_status' => $this->extractPipelineStatus($apiResponse['stage_content']),
                'memory_entry' => [
                    'role' => $currentStep['role'],
                    'model' => $currentStep['model'],
                    'loop' => 1,
                    'content' => \substr($apiResponse['content'], 0, self::MAX_LEDGER_ENTRY_BYTES),
                ],
            ];
        });
    }


    public function ajaxPreparePatchReview(): void
    {
        $this->initializeErrorHandling();
        $this->sendJsonFromOperation(function (): array {
            $this->assertAjaxAccess();

            $pluginSlug = isset($_POST['plugin_slug']) ? \sanitize_text_field((string) \wp_unslash($_POST['plugin_slug'])) : '';
            $proposalId = isset($_POST['proposal_id']) ? \sanitize_text_field((string) \wp_unslash($_POST['proposal_id'])) : '';
            if (\preg_match('/\A[a-f0-9]{64}\z/i', $proposalId) !== 1) {
                $this->throwTypedException('Patch token validation failed.', 'security');
            }

            $scope = $this->resolveInactivePluginScope($pluginSlug);
            $vault = $this->getPatchVault($pluginSlug);
            if (!isset($vault[$proposalId]) || !\is_array($vault[$proposalId])) {
                $this->throwTypedException('Patch token not found.', 'security');
            }

            $proposal = $vault[$proposalId];
            $relativePath = $this->sanitizeRelativePath((string) $proposal['path']);
            $proposedCode = (string) $proposal['code'];
            if ($proposedCode === '' || \strlen($proposedCode) > self::MAX_WRITE_BYTES) {
                $this->throwTypedException('Write payload size rejected.', 'validation');
            }

            $destination = $this->resolvePatchDestination($scope, $relativePath);
            $currentCode = $this->readCurrentTargetContent($scope, $destination);
            $reviewToken = \bin2hex(\random_bytes(32));
            $vault[$proposalId]['review_token_hash'] = $this->hashReviewToken($proposalId, $reviewToken);
            $vault[$proposalId]['reviewed_at'] = \gmdate('c');
            $this->savePatchVault($pluginSlug, $vault);

            return [
                'proposal_id' => $proposalId,
                'path' => $relativePath,
                'review_token' => $reviewToken,
                'current_code' => $currentCode,
                'proposed_code' => $proposedCode,
                'diff' => $this->buildSideBySideDiff($currentCode, $proposedCode),
            ];
        });
    }


    public function ajaxCommitStagedPatch(): void
    {
        $this->initializeErrorHandling();
        $this->sendJsonFromOperation(function (): array {
            $this->assertAjaxAccess();

            $pluginSlug = isset($_POST['plugin_slug']) ? \sanitize_text_field((string) \wp_unslash($_POST['plugin_slug'])) : '';
            $proposalId = isset($_POST['proposal_id']) ? \sanitize_text_field((string) \wp_unslash($_POST['proposal_id'])) : '';
            $reviewToken = isset($_POST['review_token']) ? \sanitize_text_field((string) \wp_unslash($_POST['review_token'])) : '';
            if (\preg_match('/\A[a-f0-9]{64}\z/i', $proposalId) !== 1) {
                $this->throwTypedException('Patch token validation failed.', 'security');
            }

            $scope = $this->resolveInactivePluginScope($pluginSlug);
            $vault = $this->getPatchVault($pluginSlug);
            if (!isset($vault[$proposalId]) || !\is_array($vault[$proposalId])) {
                $this->throwTypedException('Patch token not found.', 'security');
            }

            $proposal = $vault[$proposalId];
            $this->assertReviewToken($proposalId, $reviewToken, $proposal);
            $this->assertCommitGuard($pluginSlug, $proposalId);
            $relativePath = $this->sanitizeRelativePath((string) $proposal['path']);
            $codeRaw = (string) $proposal['code'];
            if ($codeRaw === '' || \strlen($codeRaw) > self::MAX_WRITE_BYTES) {
                $this->throwTypedException('Write payload size rejected.', 'validation');
            }

            $destination = $this->resolvePatchDestination($scope, $relativePath);
            if (\is_dir($destination)) {
                throw new ValidationException('Patch target is a directory.');
            }

            if (\is_link($destination)) {
                $this->throwTypedException('Patch target symlink rejected.', 'security');
            }

            $writeResult = $this->writePatchWithWorkspaceFallback($pluginSlug, $scope, $relativePath, $destination, $codeRaw);

            $vault[$proposalId]['committed_at'] = \gmdate('c');
            $vault[$proposalId]['commit_mode'] = $writeResult['mode'];
            $vault[$proposalId]['workspace_path'] = $writeResult['workspace_path'];
            $this->savePatchVault($pluginSlug, $vault);
            if ($writeResult['mode'] === 'target') {
                $this->refreshPluginMap($pluginSlug, $scope);
            }

            return [
                'message' => $writeResult['message'],
                'proposals' => $this->summarizePatchVault($pluginSlug),
            ];
        });
    }


    public function ajaxClearPatchVault(): void
    {
        $this->initializeErrorHandling();
        $this->sendJsonFromOperation(function (): array {
            $this->assertAjaxAccess();

            $pluginSlug = isset($_POST['plugin_slug']) ? \sanitize_text_field((string) \wp_unslash($_POST['plugin_slug'])) : '';
            $this->resolveInactivePluginScope($pluginSlug);
            \delete_option($this->getPatchVaultOptionKey($pluginSlug));

            return [
                'message' => 'Patch staging vault cleared.',
                'proposals' => [],
            ];
        });
    }


    public function ajaxListMemory(): void
    {
        $this->initializeErrorHandling();
        $this->sendJsonFromOperation(function (): array {
            $this->assertAjaxAccess();

            $pluginSlug = isset($_POST['plugin_slug']) ? \sanitize_text_field((string) \wp_unslash($_POST['plugin_slug'])) : '';
            if ($pluginSlug !== '') {
                $this->resolveInactivePluginScope($pluginSlug);
            }

            return $this->summarizeMemoryStore($pluginSlug);
        });
    }


    public function ajaxLoadMemorySession(): void
    {
        $this->initializeErrorHandling();
        $this->sendJsonFromOperation(function (): array {
            $this->assertAjaxAccess();

            $pluginSlug = isset($_POST['plugin_slug']) ? \sanitize_text_field((string) \wp_unslash($_POST['plugin_slug'])) : '';
            $sessionId = isset($_POST['session_id']) ? \sanitize_text_field((string) \wp_unslash($_POST['session_id'])) : '';
            if ($pluginSlug !== '') {
                $this->resolveInactivePluginScope($pluginSlug);
            }

            return ['session' => $this->getMemorySession($pluginSlug, $sessionId)];
        });
    }


    public function ajaxLoadMemoryArtifact(): void
    {
        $this->initializeErrorHandling();
        $this->sendJsonFromOperation(function (): array {
            $this->assertAjaxAccess();

            $pluginSlug = isset($_POST['plugin_slug']) ? \sanitize_text_field((string) \wp_unslash($_POST['plugin_slug'])) : '';
            $artifactId = isset($_POST['artifact_id']) ? \sanitize_text_field((string) \wp_unslash($_POST['artifact_id'])) : '';
            if ($pluginSlug !== '') {
                $this->resolveInactivePluginScope($pluginSlug);
            }

            return ['artifact' => $this->getMemoryArtifact($pluginSlug, $artifactId)];
        });
    }


    public function ajaxCreateAgentBlueprint(): void
    {
        $this->initializeErrorHandling();
        $this->sendJsonFromOperation(function (): array {
            $this->assertAjaxAccess();
            $message = $this->sanitizeBoundedText(isset($_POST['message']) ? (string) \wp_unslash($_POST['message']) : '', self::MAX_CHAT_BYTES);
            $model = $this->sanitizeModel(isset($_POST['model']) ? (string) \wp_unslash($_POST['model']) : 'openai/gpt-oss-20b');
            if ($message === '') {
                throw new ValidationException('Agent intent required.');
            }

            return ['blueprint' => $this->buildAgentBlueprintFromIntent($message, $model)];
        });
    }


    public function ajaxValidateAgentBlueprint(): void
    {
        $this->initializeErrorHandling();
        $this->sendJsonFromOperation(function (): array {
            $this->assertAjaxAccess();
            $raw = $this->decodeJsonArray(isset($_POST['blueprint']) ? (string) \wp_unslash($_POST['blueprint']) : '{}');
            return ['blueprint' => $this->validateAgentBlueprint($raw, true)];
        });
    }


    public function ajaxRegisterAgentBlueprint(): void
    {
        $this->initializeErrorHandling();
        $this->sendJsonFromOperation(function (): array {
            $this->assertAjaxAccess();
            $raw = $this->decodeJsonArray(isset($_POST['blueprint']) ? (string) \wp_unslash($_POST['blueprint']) : '{}');
            $blueprint = $this->validateAgentBlueprint($raw, false);
            $registry = $this->getCustomAgentRegistry();
            $registry[$blueprint['id']] = $blueprint;
            $this->saveCustomAgentRegistry($registry);

            return [
                'message' => 'Agent blueprint registered.',
                'agents' => $this->getCustomAgentPayload(),
                'roles' => $this->getRolePayload(),
            ];
        });
    }


    public function ajaxListCustomAgents(): void
    {
        $this->initializeErrorHandling();
        $this->sendJsonFromOperation(function (): array {
            $this->assertAjaxAccess();
            return ['agents' => $this->getCustomAgentPayload(), 'roles' => $this->getRolePayload()];
        });
    }


    public function ajaxDeleteCustomAgent(): void
    {
        $this->initializeErrorHandling();
        $this->sendJsonFromOperation(function (): array {
            $this->assertAjaxAccess();
            $agentId = \sanitize_key(isset($_POST['agent_id']) ? (string) \wp_unslash($_POST['agent_id']) : '');
            if (\preg_match('/\A[a-z0-9][a-z0-9_-]{2,63}\z/', $agentId) !== 1) {
                $this->throwTypedException('Agent token validation failed.', 'security');
            }

            $registry = $this->getCustomAgentRegistry();
            unset($registry[$agentId]);
            $this->saveCustomAgentRegistry($registry);

            return ['message' => 'Custom agent deleted.', 'agents' => $this->getCustomAgentPayload(), 'roles' => $this->getRolePayload()];
        });
    }


    public function ajaxExportAgentBlueprint(): void
    {
        $this->initializeErrorHandling();
        $this->sendJsonFromOperation(function (): array {
            $this->assertAjaxAccess();
            $agentId = \sanitize_key(isset($_POST['agent_id']) ? (string) \wp_unslash($_POST['agent_id']) : '');
            $registry = $this->getCustomAgentRegistry();
            if (!isset($registry[$agentId]) || !\is_array($registry[$agentId])) {
                throw new ValidationException('Agent blueprint not found.');
            }

            return ['blueprint' => $registry[$agentId]];
        });
    }


    public function ajaxImportAgentBlueprint(): void
    {
        $this->initializeErrorHandling();
        $this->sendJsonFromOperation(function (): array {
            $this->assertAjaxAccess();
            $raw = $this->decodeJsonArray(isset($_POST['blueprint']) ? (string) \wp_unslash($_POST['blueprint']) : '{}');
            return ['blueprint' => $this->validateAgentBlueprint($raw, true)];
        });
    }


    public function ajaxClearGroundingCache(): void
    {
        $this->initializeErrorHandling();
        $this->sendJsonFromOperation(function (): array {
            $this->assertAjaxAccess();
            $root = $this->ensureWorkspaceDirectory($this->getSecureWorkspaceRoot(), self::GROUNDING_DIR_NAME);
            foreach (\glob($root . \DIRECTORY_SEPARATOR . '*.json') ?: [] as $file) {
                if (\is_file($file) && \str_starts_with($file, $root . \DIRECTORY_SEPARATOR)) {
                    @\unlink($file);
                }
            }

            return ['message' => 'Grounding cache cleared.'];
        });
    }

}
