<?php

declare(strict_types=1);

// STATUS: DIAMANT VGT SUPREME

namespace VGTAstra\AgentSystem;

if (!defined('ABSPATH')) {
    exit;
}

trait RuntimeTrait
{
    public function __construct()
    {
        \add_action('admin_init', [$this, 'emitSecurityHeaders']);
        \add_action('admin_menu', [$this, 'registerAdminMenu']);
        \add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        \add_action('wp_ajax_vgta_save_credentials', [$this, 'ajaxSaveCredentials']);
        \add_action('wp_ajax_vgta_generate_plugin_map', [$this, 'ajaxGeneratePluginMap']);
        \add_action('wp_ajax_vgta_chat_message', [$this, 'ajaxChatMessage']);
        \add_action('wp_ajax_vgta_execute_agent_step', [$this, 'ajaxExecuteAgentStep']);
        \add_action('wp_ajax_vgta_prepare_patch_review', [$this, 'ajaxPreparePatchReview']);
        \add_action('wp_ajax_vgta_prepare_patch_bundle_review', [$this, 'ajaxPreparePatchBundleReview']);
        \add_action('wp_ajax_vgta_analyze_error', [$this, 'ajaxAnalyzeError']);
        \add_action('wp_ajax_vgta_commit_staged_patch', [$this, 'ajaxCommitStagedPatch']);
        \add_action('wp_ajax_vgta_clear_patch_vault', [$this, 'ajaxClearPatchVault']);
        \add_action('wp_ajax_vgta_list_memory', [$this, 'ajaxListMemory']);
        \add_action('wp_ajax_vgta_load_memory_session', [$this, 'ajaxLoadMemorySession']);
        \add_action('wp_ajax_vgta_load_memory_artifact', [$this, 'ajaxLoadMemoryArtifact']);
        \add_action('wp_ajax_vgta_create_agent_blueprint', [$this, 'ajaxCreateAgentBlueprint']);
        \add_action('wp_ajax_vgta_validate_agent_blueprint', [$this, 'ajaxValidateAgentBlueprint']);
        \add_action('wp_ajax_vgta_register_agent_blueprint', [$this, 'ajaxRegisterAgentBlueprint']);
        \add_action('wp_ajax_vgta_list_custom_agents', [$this, 'ajaxListCustomAgents']);
        \add_action('wp_ajax_vgta_delete_custom_agent', [$this, 'ajaxDeleteCustomAgent']);
        \add_action('wp_ajax_vgta_export_agent_blueprint', [$this, 'ajaxExportAgentBlueprint']);
        \add_action('wp_ajax_vgta_import_agent_blueprint', [$this, 'ajaxImportAgentBlueprint']);
        \add_action('wp_ajax_vgta_clear_grounding_cache', [$this, 'ajaxClearGroundingCache']);
    }


    public function emitSecurityHeaders(): void
    {
        if (!$this->isKernelPageRequest() || \headers_sent()) {
            return;
        }

        \header('X-Content-Type-Options: nosniff');
        // XFO owned by WPDeskFramePolicy late consolidation (avoids multi-value stacks).
        if (!\class_exists('\\VisionGaia\\WPDesk\\WPDeskFramePolicy')) {
            \header('X-Frame-Options: SAMEORIGIN');
        }
        \header("Content-Security-Policy: frame-ancestors 'self'");
        \header('Referrer-Policy: strict-origin-when-cross-origin');
        \header('Permissions-Policy: accelerometer=(), ambient-light-sensor=(), autoplay=(), camera=(), display-capture=(), encrypted-media=(), fullscreen=(), geolocation=(), gyroscope=(), magnetometer=(), microphone=(), midi=(), payment=(), usb=()');

        if (\is_ssl()) {
            \header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }
    }


    public function registerAdminMenu(): void
    {
        \add_menu_page(
            'VGTAstra Agent System',
            'VGTAstra',
            'manage_options',
            self::MENU_SLUG,
            [$this, 'renderAdminDashboard'],
            'dashicons-superhero',
            50
        );
    }

    /**
     * @param string $hook
     */

    public function enqueueAssets($hook): void
    {
        if ($hook !== 'toplevel_page_' . self::MENU_SLUG) {
            return;
        }

        if (\class_exists('\\VisionGaia\\WPDesk\\WPDeskDesignSystem')) {
            \VisionGaia\WPDesk\WPDeskDesignSystem::enqueue('astra');
        }

        // Portal iframe: full-bleed + hide portal badge; fix panel CLOSE size.
        $is_iframe = (isset($_GET['vgt_iframe']) && (string) $_GET['vgt_iframe'] === 'true')
            || (isset($_SERVER['HTTP_SEC_FETCH_DEST']) && strtolower((string) $_SERVER['HTTP_SEC_FETCH_DEST']) === 'iframe');
        if ($is_iframe) {
            $nonce = \function_exists('vgt_get_csp_nonce') ? \vgt_get_csp_nonce() : '';
            echo '<style nonce="' . \esc_attr((string) $nonce) . '">
                html,body,#wpwrap,#wpcontent,#wpbody,#wpbody-content{margin:0!important;padding:0!important;height:100%!important;background:#070b14!important}
                #wpadminbar,#adminmenumain,#adminmenuback,#adminmenuwrap,#wpfooter{display:none!important}
                #wpbody-content::before{display:none!important;content:none!important}
                .vgta-root{margin:0!important;min-height:100vh!important;padding:14px!important;box-sizing:border-box!important}
                .vgta-panel-toggle{width:auto!important;min-width:64px!important;padding:6px 12px!important;white-space:nowrap!important;border-radius:999px!important}
            </style>';
        }

        \wp_enqueue_style('vgta-orchestrator-css', VGTA_PLUGIN_URL . 'assets/css/orchestrator.css', ['vgt-ds-compat'], VGTA_PLUGIN_VERSION);
        \wp_enqueue_style('vgta-orchestrator-workbench-css', VGTA_PLUGIN_URL . 'assets/css/orchestrator-workbench.css', ['vgta-orchestrator-css'], VGTA_PLUGIN_VERSION);
        \wp_enqueue_style('vgta-orchestrator-diff-css', VGTA_PLUGIN_URL . 'assets/css/orchestrator-diff.css', ['vgta-orchestrator-workbench-css'], VGTA_PLUGIN_VERSION);
        \wp_enqueue_style('vgta-orchestrator-memory-css', VGTA_PLUGIN_URL . 'assets/css/orchestrator-memory.css', ['vgta-orchestrator-diff-css'], VGTA_PLUGIN_VERSION);
        \wp_enqueue_style('vgta-orchestrator-onboarding-css', VGTA_PLUGIN_URL . 'assets/css/orchestrator-onboarding.css', ['vgta-orchestrator-memory-css'], VGTA_PLUGIN_VERSION);
        \wp_enqueue_style('vgta-orchestrator-premium-css', VGTA_PLUGIN_URL . 'assets/css/orchestrator-premium.css', ['vgta-orchestrator-onboarding-css'], VGTA_PLUGIN_VERSION);
        \wp_enqueue_style('vgta-orchestrator-layout-css', VGTA_PLUGIN_URL . 'assets/css/orchestrator-layout.css', ['vgta-orchestrator-premium-css'], VGTA_PLUGIN_VERSION);
        \wp_enqueue_script('vgta-orchestrator-core-js', VGTA_PLUGIN_URL . 'assets/js/orchestrator-core.js', [], VGTA_PLUGIN_VERSION, true);
        \wp_enqueue_script('vgta-orchestrator-onboarding-js', VGTA_PLUGIN_URL . 'assets/js/orchestrator-onboarding.js', [], VGTA_PLUGIN_VERSION, true);
        \wp_enqueue_script('vgta-orchestrator-layout-js', VGTA_PLUGIN_URL . 'assets/js/orchestrator-layout.js', ['vgta-orchestrator-core-js'], VGTA_PLUGIN_VERSION, true);
        \wp_enqueue_script('vgta-orchestrator-renderers-js', VGTA_PLUGIN_URL . 'assets/js/orchestrator-renderers.js', ['vgta-orchestrator-core-js'], VGTA_PLUGIN_VERSION, true);
        \wp_enqueue_script('vgta-orchestrator-review-js', VGTA_PLUGIN_URL . 'assets/js/orchestrator-review.js', ['vgta-orchestrator-renderers-js'], VGTA_PLUGIN_VERSION, true);
        \wp_enqueue_script('vgta-orchestrator-steps-js', VGTA_PLUGIN_URL . 'assets/js/orchestrator-steps.js', ['vgta-orchestrator-core-js'], VGTA_PLUGIN_VERSION, true);
        \wp_enqueue_script('vgta-orchestrator-memory-js', VGTA_PLUGIN_URL . 'assets/js/orchestrator-memory.js', ['vgta-orchestrator-renderers-js'], VGTA_PLUGIN_VERSION, true);
        \wp_enqueue_script('vgta-orchestrator-forge-js', VGTA_PLUGIN_URL . 'assets/js/orchestrator-forge.js', ['vgta-orchestrator-renderers-js'], VGTA_PLUGIN_VERSION, true);
        \wp_enqueue_script('vgta-orchestrator-js', VGTA_PLUGIN_URL . 'assets/js/orchestrator.js', ['vgta-orchestrator-core-js', 'vgta-orchestrator-layout-js', 'vgta-orchestrator-renderers-js', 'vgta-orchestrator-review-js', 'vgta-orchestrator-steps-js', 'vgta-orchestrator-memory-js', 'vgta-orchestrator-forge-js'], VGTA_PLUGIN_VERSION, true);
        \wp_localize_script('vgta-orchestrator-js', 'vgtaConfig', [
            'ajaxUrl' => \admin_url('admin-ajax.php'),
            'nonce' => \wp_create_nonce(self::NONCE_ACTION),
            'models' => $this->getModelPayload(),
            'modelAliases' => $this->getModelAliasPayload(),
            'roles' => $this->getRolePayload(),
            'customAgents' => $this->getCustomAgentPayload(),
        ]);
    }


    private function initializeErrorHandling(): void
    {
        // Intentionally no global set_error_handler — desk suite policy.
        if (\function_exists('ini_set')) {
            @\ini_set('display_errors', '0');
        }
    }


    private function throwTypedException(string $message, string $type = 'validation'): void
    {
        if (\preg_match('/(injection|CSRF|polyglot|validation failed|origin|path|traversal|token)/i', $message) === 1) {
            throw new SecurityException($message);
        }

        if ($type === 'security') {
            throw new SecurityException($message);
        }

        if ($type === 'storage') {
            throw new StorageException($message);
        }

        throw new ValidationException($message);
    }


    private function assertAjaxAccess(): void
    {
        if (!\check_ajax_referer(self::NONCE_ACTION, 'nonce', false)) {
            $this->throwTypedException('CSRF token verification failed.', 'security');
        }

        if (!\current_user_can('manage_options')) {
            $this->throwTypedException('Authorization boundary rejected.', 'security');
        }
    }


    public function renderAdminDashboard(): void
    {
        if (!\current_user_can('manage_options')) {
            \wp_die(\esc_html__('Unauthorized access.', 'vgta'));
        }

        $inactivePlugins = $this->getInactivePlugins();
        
        $groqSealed = \is_string(\get_option(self::OPTION_KEYS['groq'], '')) && \get_option(self::OPTION_KEYS['groq'], '') !== '';
        $geminiSealed = \is_string(\get_option(self::OPTION_KEYS['gemini'], '')) && \get_option(self::OPTION_KEYS['gemini'], '') !== '';
        $claudeSealed = \is_string(\get_option(self::OPTION_KEYS['claude'], '')) && \get_option(self::OPTION_KEYS['claude'], '') !== '';
        $chatgptSealed = \is_string(\get_option(self::OPTION_KEYS['chatgpt'], '')) && \get_option(self::OPTION_KEYS['chatgpt'], '') !== '';
        
        $credentialSealed = $groqSealed || $geminiSealed || $claudeSealed || $chatgptSealed;
        $models = $this->getModelPayload();

        include VGTA_PLUGIN_DIR . 'templates/dashboard-view.php';
    }

    /**
     * @param callable(): array<string, mixed> $operation
     */

    private function sendJsonFromOperation(callable $operation): void
    {
        try {
            \wp_send_json_success($operation());
        } catch (ValidationException $e) {
            \wp_send_json_error(['status' => 'error', 'message' => $e->getMessage()]);
        } catch (SecurityException $e) {
            $errorCode = $this->buildOpaqueErrorCode($e);
            $this->logInternalThrowable('SEC', $errorCode, $e);
            $this->recordAjaxDiagnosticEvent($errorCode, $e, 'SEC');
            \wp_send_json_error(['status' => 'error', 'message' => 'Request rejected for security reasons.', 'code' => $errorCode, 'diagnostic_available' => true]);
        } catch (StorageException $e) {
            $errorCode = $this->buildOpaqueErrorCode($e);
            $this->logInternalThrowable('STORAGE', $errorCode, $e);
            $this->recordAjaxDiagnosticEvent($errorCode, $e, 'STORAGE');
            \wp_send_json_error(['status' => 'error', 'message' => 'A server error occurred.', 'code' => $errorCode, 'diagnostic_available' => true]);
        } catch (\Throwable $e) {
            $errorCode = $this->buildOpaqueErrorCode($e);
            $this->logInternalThrowable('FATAL', $errorCode, $e);
            $this->recordAjaxDiagnosticEvent($errorCode, $e, 'FATAL');
            \wp_send_json_error(['status' => 'error', 'message' => 'Critical system fault.', 'code' => $errorCode, 'diagnostic_available' => true]);
        }
    }


    private function recordAjaxDiagnosticEvent(string $errorCode, \Throwable $e, string $scope): void
    {
        try {
            $pluginSlugRaw = isset($_POST['plugin_slug']) ? (string) \wp_unslash($_POST['plugin_slug']) : '';
            $pluginSlug = $pluginSlugRaw !== '' ? $this->sanitizePluginSlug($pluginSlugRaw) : '';
            $action = isset($_POST['action']) ? \sanitize_key((string) \wp_unslash($_POST['action'])) : 'unknown';
            $event = [
                'id' => \hash('sha256', $errorCode . '|' . \microtime(true) . '|' . \bin2hex(\random_bytes(8))),
                'created_at' => \gmdate('c'),
                'pipeline_run_id' => $this->sanitizeMemoryId(isset($_POST['session_id']) ? (string) \wp_unslash($_POST['session_id']) : ''),
                'step_index' => 0,
                'loop_index' => 0,
                'role' => 'Ajax',
                'model' => 'system',
                'error_code' => $errorCode,
                'error_scope' => $scope,
                'error_class' => \get_class($e),
                'error_message' => \substr($e->getMessage(), 0, 600),
                'error_file' => \basename($e->getFile()),
                'error_line' => $e->getLine(),
                'public_message' => $scope === 'SEC' ? 'Request rejected for security reasons.' : 'A server error occurred.',
                'context_hash' => \hash('sha256', $action . '|' . $pluginSlug),
                'action' => $action,
                'payload_bytes' => \strlen((string) \json_encode($_POST, \JSON_INVALID_UTF8_SUBSTITUTE)),
                'memory_bytes' => $this->getMemoryStoreSize($pluginSlug),
                'rejected_writes' => [],
            ];
            $this->appendErrorEvent($pluginSlug, $event);
            if ($pluginSlug !== '') {
                $this->appendErrorEvent('', $event);
            }
        } catch (\Throwable $diagnosticFailure) {
            $this->logInternalThrowable('DIAGNOSTIC', $this->buildOpaqueErrorCode($diagnosticFailure), $diagnosticFailure);
        }
    }


    private function buildOpaqueErrorCode(\Throwable $e): string
    {
        $material = \get_class($e) . '|' . $e->getMessage() . '|' . $e->getFile() . '|' . (string) $e->getLine();
        return 'VGTA-' . \strtoupper(\substr(\hash_hmac('sha256', $material, self::ERROR_CODE_CONTEXT), 0, 12));
    }


    private function logInternalThrowable(string $scope, string $errorCode, \Throwable $e): void
    {
        \error_log(
            '[VGTA ' . $scope . '][' . $errorCode . '] '
            . \get_class($e) . ': ' . $e->getMessage()
            . ' @ ' . $e->getFile() . ':' . (string) $e->getLine()
        );
    }


    private function isKernelPageRequest(): bool
    {
        return isset($_GET['page']) && \sanitize_key((string) \wp_unslash($_GET['page'])) === self::MENU_SLUG;
    }

    /**
     * @return array<string, array<string, string>>
     */
}
