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
        \header('X-Frame-Options: SAMEORIGIN');
        \header("Content-Security-Policy: frame-ancestors 'self'");
        \header('Referrer-Policy: strict-origin-when-cross-origin');
        \header('Permissions-Policy: accelerometer=(), ambient-light-sensor=(), autoplay=(), camera=(), display-capture=(), encrypted-media=(), fullscreen=(), geolocation=(), gyroscope=(), magnetometer=(), microphone=(), midi=(), payment=(), usb=()');

        if (\is_ssl()) {
            \header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }
    }


    public function registerAdminMenu(): void
    {
        global $menu;

        $hasBuildCenter = false;
        foreach ((array) $menu as $menuItem) {
            if (($menuItem[2] ?? '') === 'vgt-build-center') {
                $hasBuildCenter = true;
                break;
            }
        }

        if ($hasBuildCenter) {
            \add_submenu_page(
                'vgt-build-center',
                'VGTAstra Agent Lab',
                'VGTAstra Agent Lab',
                'manage_options',
                self::MENU_SLUG,
                [$this, 'renderAdminDashboard']
            );
            return;
        }

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
        $allowedHooks = [
            'toplevel_page_' . self::MENU_SLUG,
            'vgt-build-center_page_' . self::MENU_SLUG,
        ];

        if (!\in_array((string) $hook, $allowedHooks, true)) {
            return;
        }

        \wp_enqueue_style('vgta-orchestrator-css', VGTA_PLUGIN_URL . 'assets/css/orchestrator.css', [], VGTA_PLUGIN_VERSION);
        \wp_enqueue_style('vgta-orchestrator-workbench-css', VGTA_PLUGIN_URL . 'assets/css/orchestrator-workbench.css', ['vgta-orchestrator-css'], VGTA_PLUGIN_VERSION);
        \wp_enqueue_style('vgta-orchestrator-diff-css', VGTA_PLUGIN_URL . 'assets/css/orchestrator-diff.css', ['vgta-orchestrator-workbench-css'], VGTA_PLUGIN_VERSION);
        \wp_enqueue_style('vgta-orchestrator-memory-css', VGTA_PLUGIN_URL . 'assets/css/orchestrator-memory.css', ['vgta-orchestrator-diff-css'], VGTA_PLUGIN_VERSION);
        \wp_enqueue_style('vgta-orchestrator-onboarding-css', VGTA_PLUGIN_URL . 'assets/css/orchestrator-onboarding.css', ['vgta-orchestrator-memory-css'], VGTA_PLUGIN_VERSION);
        \wp_enqueue_style('vgta-orchestrator-premium-css', VGTA_PLUGIN_URL . 'assets/css/orchestrator-premium.css', ['vgta-orchestrator-onboarding-css'], VGTA_PLUGIN_VERSION);
        \wp_enqueue_script('vgta-orchestrator-core-js', VGTA_PLUGIN_URL . 'assets/js/orchestrator-core.js', [], VGTA_PLUGIN_VERSION, true);
        \wp_enqueue_script('vgta-orchestrator-onboarding-js', VGTA_PLUGIN_URL . 'assets/js/orchestrator-onboarding.js', [], VGTA_PLUGIN_VERSION, true);
        \wp_enqueue_script('vgta-orchestrator-renderers-js', VGTA_PLUGIN_URL . 'assets/js/orchestrator-renderers.js', ['vgta-orchestrator-core-js'], VGTA_PLUGIN_VERSION, true);
        \wp_enqueue_script('vgta-orchestrator-review-js', VGTA_PLUGIN_URL . 'assets/js/orchestrator-review.js', ['vgta-orchestrator-renderers-js'], VGTA_PLUGIN_VERSION, true);
        \wp_enqueue_script('vgta-orchestrator-steps-js', VGTA_PLUGIN_URL . 'assets/js/orchestrator-steps.js', ['vgta-orchestrator-core-js'], VGTA_PLUGIN_VERSION, true);
        \wp_enqueue_script('vgta-orchestrator-memory-js', VGTA_PLUGIN_URL . 'assets/js/orchestrator-memory.js', ['vgta-orchestrator-renderers-js'], VGTA_PLUGIN_VERSION, true);
        \wp_enqueue_script('vgta-orchestrator-forge-js', VGTA_PLUGIN_URL . 'assets/js/orchestrator-forge.js', ['vgta-orchestrator-renderers-js'], VGTA_PLUGIN_VERSION, true);
        \wp_enqueue_script('vgta-orchestrator-js', VGTA_PLUGIN_URL . 'assets/js/orchestrator.js', ['vgta-orchestrator-core-js', 'vgta-orchestrator-renderers-js', 'vgta-orchestrator-review-js', 'vgta-orchestrator-steps-js', 'vgta-orchestrator-memory-js', 'vgta-orchestrator-forge-js'], VGTA_PLUGIN_VERSION, true);
        \wp_localize_script('vgta-orchestrator-js', 'vgtaConfig', [
            'ajaxUrl' => \admin_url('admin-ajax.php'),
            'nonce' => \wp_create_nonce(self::NONCE_ACTION),
            'models' => $this->getModelPayload(),
            'roles' => $this->getRolePayload(),
            'customAgents' => $this->getCustomAgentPayload(),
        ]);
    }


    private function initializeErrorHandling(): void
    {
        \ini_set('display_errors', '0');
        \error_reporting(\E_ALL);
        \set_error_handler(static function (int $sev, string $msg, string $file, int $line): bool {
            if (!(\error_reporting() & $sev)) {
                return false;
            }

            $resolvedFile = \realpath($file);
            $pluginRoot = \realpath(VGTA_PLUGIN_DIR);
            if ($resolvedFile === false || $pluginRoot === false || !\str_starts_with($resolvedFile, $pluginRoot . \DIRECTORY_SEPARATOR)) {
                return false;
            }

            throw new \ErrorException($msg, 0, $sev, $file, $line);
        });
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
        $credentialSealed = \is_string(\get_option(self::OPTION_KEY_API_KEY, '')) && \get_option(self::OPTION_KEY_API_KEY, '') !== '';
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
            \wp_send_json_error(['status' => 'error', 'message' => 'Request rejected for security reasons.', 'code' => $errorCode]);
        } catch (StorageException $e) {
            $errorCode = $this->buildOpaqueErrorCode($e);
            $this->logInternalThrowable('STORAGE', $errorCode, $e);
            \wp_send_json_error(['status' => 'error', 'message' => 'A server error occurred.', 'code' => $errorCode]);
        } catch (\Throwable $e) {
            $errorCode = $this->buildOpaqueErrorCode($e);
            $this->logInternalThrowable('FATAL', $errorCode, $e);
            \wp_send_json_error(['status' => 'error', 'message' => 'Critical system fault.', 'code' => $errorCode]);
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
