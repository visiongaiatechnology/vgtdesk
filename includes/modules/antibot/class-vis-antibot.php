<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

/**
 * MODULE: ANTIBOT (VGT SHIELD V2)
 * Status: DIAMANT SUPREME (Zero-UI Proof-of-Work)
 * Logic: Integrierte Konsolidierung von VGT Shield in den Sentinel Kernel. WP.org Compliant.
 * Nutzt VGTS_Network für O(1) IP Resolution, verzichtet auf externe Abhängigkeiten.
 */
class VGTS_Antibot {
    
    private array $options;
    
    public function __construct(array $options) {
        $this->options = $options;

        // VGT DRY: Scanner AJAX Logik wird unabhängig vom Modul-Status registriert (für UI)
        if (is_admin()) {
            add_action('wp_ajax_vgts_scan_plugin', [$this, 'ajax_scan_plugin']);
        }

        if (empty($this->options['antibot_enabled'])) return;

        // 1. Initialisierung Engine
        add_action('wp_enqueue_scripts', [$this, 'inject_engine']);
        add_shortcode('vgts_antibot', [$this, 'render_shortcode']);

        // 2. Kryptografische Schnittstelle (TITAN kompatibel)
        add_action('rest_api_init', function () {
            register_rest_route('vgts-antibot/v1', '/challenge', [
                'methods'             => 'GET',
                'callback'            => [$this, 'api_get_challenge'],
                'permission_callback' => '__return_true'
            ]);
        });

        // 3. System-Validatoren (Konditionale Interception Matrix)
        if (!empty($this->options['antibot_comments'])) {
            add_filter('preprocess_comment', [$this, 'validate_comment_submission']);
        }
        if (!empty($this->options['antibot_cf7'])) {
            add_action('wpcf7_before_send_mail', [$this, 'validate_cf7_submission'], 10, 3);
        }
        if (!empty($this->options['antibot_woo'])) {
            add_filter('woocommerce_process_registration_errors', [$this, 'validate_woo_auth'], 10, 2);
            add_action('woocommerce_process_login_errors', [$this, 'validate_woo_auth'], 10, 3);
            add_action('woocommerce_after_checkout_validation', [$this, 'validate_woo_checkout'], 10, 2);
        }
        if (!empty($this->options['antibot_wpforms'])) {
            add_filter('wpforms_process_initial_errors', [$this, 'validate_wpforms'], 10, 2);
        }
        if (!empty($this->options['antibot_gform'])) {
            add_filter('gform_validation', [$this, 'validate_gform']);
        }
        
        $this->attach_dynamic_hooks();
    }

    // --- ENGINE & INJECTION ---

    public function inject_engine(): void {
        if (wp_script_is('vgts-antibot-engine', 'enqueued')) return;

        wp_enqueue_script(
            'vgts-antibot-engine', 
            VGTS_URL . 'includes/modules/antibot/assets/js/vis-antibot-engine.js', 
            [], 
            VGTS_VERSION, 
            true
        );

        wp_localize_script('vgts-antibot-engine', 'vgtsAntibotConfig', [
            'apiUrl'    => esc_url_raw(rest_url('vgts-antibot/v1/challenge')),
            'workerUrl' => esc_url_raw(VGTS_URL . 'includes/modules/antibot/assets/js/vis-antibot-worker.js')
        ]);
    }

    public function render_shortcode(): string {
        $this->inject_engine();
        return '<div class="vgts-antibot-anchor" style="display:none;" data-vgts-status="active"></div>';
    }

    // --- CRYPTOGRAPHIC KERNEL ---

    private function get_dynamic_salt(int $offset_days = 0): string {
        $date = gmdate('Y-m-d', time() + ($offset_days * 86400));
        return hash('sha512', $date . wp_salt('auth') . wp_salt('secure_auth') . 'VGTS_MATRIX_SALT');
    }

    private function get_client_fingerprint(int $offset_days = 0): string {
        // OMEGA PROTOCOL: Nutzung des gehärteten Sentinel Kernels
        $raw_ip = class_exists('VGTS_Network') ? VGTS_Network::resolve_true_ip() : ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        $ip = sanitize_text_field(wp_unslash($raw_ip));
        $ua = sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'] ?? 'unknown_vgt_agent'));
        
        return hash('sha384', $ip . $ua . $this->get_dynamic_salt($offset_days));
    }

    public function api_get_challenge() {
        $timestamp  = time();
        $difficulty = (int) ($this->options['antibot_difficulty'] ?? 3);
        $seed       = hash_hmac('sha256', $timestamp . $this->get_client_fingerprint(0), $this->get_dynamic_salt(0));
        
        return rest_ensure_response([
            'seed'       => $seed,
            'timestamp'  => $timestamp,
            'difficulty' => $difficulty
        ]);
    }

    public function get_pow_payload(): string {
        if (!empty($_POST['vgts_pow_payload'])) {
            return sanitize_text_field(wp_unslash($_POST['vgts_pow_payload']));
        }
        if (!empty($_SERVER['HTTP_X_VGTS_ANTIBOT_POW'])) {
            return sanitize_text_field(wp_unslash($_SERVER['HTTP_X_VGTS_ANTIBOT_POW']));
        }
        return '';
    }

    public function validate_pow(string $payload): bool {
        if (empty($payload)) return false;

        $data = json_decode($payload, true);
        if (json_last_error() !== JSON_ERROR_NONE || !isset($data['seed'], $data['timestamp'], $data['nonce'])) {
            return false;
        }

        $timestamp = (int) $data['timestamp'];
        $duration  = time() - $timestamp;
        
        // TTL: 1800s (30 Min)
        if ($duration < 1 || $duration > 1800) return false;

        $hash_input = $data['seed'] . $data['nonce'];
        $hash       = hash('sha256', $hash_input);
        
        // Replay Protection
        $cache_key = 'vgts_pow_' . $hash;
        if (wp_cache_get($cache_key, 'vgts_antibot') || get_transient($cache_key)) {
            return false;
        }

        $expected_seed_today     = hash_hmac('sha256', $timestamp . $this->get_client_fingerprint(0), $this->get_dynamic_salt(0));
        $expected_seed_yesterday = hash_hmac('sha256', $timestamp . $this->get_client_fingerprint(-1), $this->get_dynamic_salt(-1));

        if (!hash_equals($expected_seed_today, $data['seed']) && !hash_equals($expected_seed_yesterday, $data['seed'])) {
            return false;
        }

        $difficulty    = (int) ($this->options['antibot_difficulty'] ?? 3);
        $target_prefix = str_repeat('0', $difficulty);
        
        $is_valid = str_starts_with($hash, $target_prefix);

        if ($is_valid) {
            wp_cache_set($cache_key, 1, 'vgts_antibot', 1800);
            set_transient($cache_key, 1, 1800);
        }

        return $is_valid;
    }

    // --- INTEGRATIONS (HOOKS) ---

    private function attach_dynamic_hooks(): void {
        $custom_hooks = $this->options['antibot_custom_hooks'] ?? [];
        if (!is_array($custom_hooks) || empty($custom_hooks)) return;
        
        foreach ($custom_hooks as $hook) {
            if (!empty($hook) && is_string($hook)) {
                add_action(sanitize_key($hook), [$this, 'validate_dynamic_hook'], 1, 0); 
            }
        }
    }

    public function validate_dynamic_hook(): void {
        $payload = $this->get_pow_payload();
        if (empty($payload) && empty($_POST)) return; 
        
        if (!$this->validate_pow($payload)) {
            wp_die(esc_html__('VGT SENTINEL: Dynamic Security Matrix Triggered. Access Denied.', 'vgt-sentinel-ce'));
        }
    }

    public function validate_woo_auth($validation_error, $username = '', $password = '') {
        if (!$this->validate_pow($this->get_pow_payload())) {
            $validation_error->add('vgts_error', wp_kses_post(__('<strong>VGT Sentinel</strong>: Security Validation Failed (Proof-of-Work Required).', 'vgt-sentinel-ce')));
        }
        return $validation_error;
    }

    public function validate_woo_checkout($data, $errors): void {
        if (!$this->validate_pow($this->get_pow_payload())) {
            $errors->add('vgts_error', wp_kses_post(__('<strong>VGT Sentinel</strong>: Checkout Security Validation Failed.', 'vgt-sentinel-ce')));
        }
    }

    public function validate_wpforms($errors, $form_data) {
        if (!$this->validate_pow($this->get_pow_payload())) {
            $errors[$form_data['id']]['header'] = esc_html__('VGT SENTINEL: Security Matrix Validation Failed.', 'vgt-sentinel-ce');
        }
        return $errors;
    }

    public function validate_gform($validation_result) {
        if (!$this->validate_pow($this->get_pow_payload())) {
            $validation_result['is_valid'] = false;
            $validation_result['form']['vgts_error'] = esc_html__('VGT SENTINEL: Validation Failed.', 'vgt-sentinel-ce');
        }
        return $validation_result;
    }

    public function validate_comment_submission($commentdata) {
        if (!$this->validate_pow($this->get_pow_payload())) {
            wp_die(esc_html__('VGT SENTINEL: Access Denied. Kognitive Anomalie erkannt (Proof-of-Work failed).', 'vgt-sentinel-ce'));
        }
        return $commentdata;
    }

    public function validate_cf7_submission($contact_form, &$abort, $submission): void {
        if (!$this->validate_pow($this->get_pow_payload())) {
            $abort = true;
            $submission->set_status('validation_failed');
            $submission->set_response(esc_html__('VGT SENTINEL: Access Denied. Security Matrix Validation Failed.', 'vgt-sentinel-ce'));
        }
    }

    // --- DEEP PLUGIN SCANNER (AJAX) ---

    public function ajax_scan_plugin(): void {
        check_ajax_referer('vgts_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

        $plugin_file = sanitize_text_field(wp_unslash($_POST['plugin_file'] ?? ''));
        if (empty($plugin_file)) wp_send_json_error('No plugin selected');

        // Sandbox Check & Path Normalization
        $base_dir       = wp_normalize_path(WP_PLUGIN_DIR);
        $requested_path = wp_normalize_path($base_dir . '/' . dirname($plugin_file));
        $real_path      = realpath($requested_path);
        
        if (!$real_path || strpos(wp_normalize_path($real_path), $base_dir) !== 0 || !is_dir($real_path)) {
            wp_send_json_error(esc_html__('VGT SENTINEL: Sandbox Escape Detektiert und Blockiert.', 'vgt-sentinel-ce'));
        }

        $hooks   = [];
        $files   = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($real_path));
        $pattern = '/(?:do_action|apply_filters)\s*\(\s*[\'"]([a-zA-Z0-9_\-]+)[\'"]/S';

        foreach ($files as $file) {
            if ($file->isDir() || $file->getExtension() !== 'php') continue;

            $handle = @fopen($file->getRealPath(), 'r');
            if ($handle) {
                while (($line = fgets($handle)) !== false) {
                    if (preg_match_all($pattern, $line, $matches)) {
                        foreach ($matches[1] as $match) {
                            $hooks[$match] = true;
                        }
                    }
                }
                fclose($handle);
            }
        }

        $unique_hooks = array_keys($hooks);
        sort($unique_hooks);
        wp_send_json_success(['hooks' => $unique_hooks]);
    }
}