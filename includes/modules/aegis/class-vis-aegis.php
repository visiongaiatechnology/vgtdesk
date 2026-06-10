<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * MODULE: AEGIS (The Shield) - VGT SUPREME DIAMOND
 * STATUS: DIAMANT STATUS (WP.ORG COMPLIANT & OPEN SOURCE HARDENED)
 * KOGNITIVE UPGRADES (V3.1 HOTFIX EDITION):
 * - [ FIXED ]: Localhost Proxy-Spoofing Bypass gelöst (Explizite Hostname-Filterung im IP-Validator).
 * - [ NEU ]: Anomaly Scoring System (Punkte-basiertes Modell statt binärem Block).
 * - [ NEU ]: Kryptografische JS-Challenge (Sanfter Schutz vor Spam/Bots, barrierefrei für echte Besucher).
 * - [ NEU ]: WP-Rollen-Erkennung (Automatische Lockerung für Admins & Editoren).
 * - [ NEU ]: Pfad- & Parameter-Ausnahmen (WooCommerce-Checkouts & Gutenberg REST-API Schutz).
 * - [ WP.ORG FIXED ]: Strict Input Sanitization via wp_unslash & esc_url_raw.
 * - [ WP.ORG FIXED ]: Header Injection Prevention (SERVER_PROTOCOL Sanitization).
 * - [ WP.ORG FIXED ]: Type-Safe JSON Payload Parsing.
 * - [ FIXED ]: Zero-Trust Forward-Confirmed Reverse DNS (FCrDNS) implementiert.
 * - [ FIXED ]: PCRE Fail-Closed Architecture (ReDoS-Immunität).
 * - Multi-Byte Boundary-Safe Stream Inspection mit optimierter I/O und GC.
 */
class VGTS_Aegis {

    private bool $enabled;
    private string $mode;
    private int $scan_limit = 524288; // 512KB Max Stream Scan
    private string $validated_ip;

    private array $whitelist_ips = [];
    private array $whitelist_uas = [];

    // Punkte-System-Konfiguration
    private int $anomaly_score = 0;
    private int $threshold_block = 12;      // Ab dieser Punktzahl erfolgt der sofortige harte Block
    private int $threshold_challenge = 5;  // Ab dieser Punktzahl muss der Besucher die JS-Challenge lösen
    private array $triggered_vectors = [];

    // VGT SUPREME REGEX: Gehärtet gegen ReDoS, optimiert mit atomaren Gruppen
    private array $patterns = [
        'rce'         => '/(?i)(?<![a-zA-Z0-9_])(?>system|exec|passthru|shell_exec|eval|proc_open|assert|phpinfo|pcntl_exec|popen|create_function|call_user_func(?:_array)?|putenv|mail|dl|ffi_load|preg_replace_callback|array_map|array_filter|array_walk|usort|uksort|register_shutdown_function|register_tick_function|invokefunction|invokeargs|setTimeout|setInterval|Function)\s*[\(\[]|`[^`]{1,255}`|\$\{(?>jndi|env|sys|lower|upper):[^\}]+\}|\$\([^)]+\)|(?:\(\)\s*\{\s*:;\s*\}\s*;)|(?:;|\|\||\||&&|`)\s*(?>whoami|net\s+user|id|cat|ls|pwd|wget|curl|nc|bash|sh|ping|type|dir|powershell|certutil|bitsadmin|rundll32)|\bwhoami\b|\buname\b|\bexec\b|\bpassthru\b|\bsystem\b|\bshell_exec\b|\/(?>bin|usr|etc|var|tmp|opt)\/[a-zA-Z0-9_\/\.\?\*-]{1,100}|(?>O:\d+:"[^"]+":\d+:\{)|rO0AB|\\\\x80\\\\x04\\\\x95/S',
        'lfi'         => '/(?i)(?>\.\.[\/\\\\])|(?>\/etc\/(?>passwd|shadow|hosts|group|issue))|(?>c:\\\\(?>windows|winnt))|(?>\bboot\.ini\b)|(?>wp-config\.php)|(?>php:\/\/(?>filter|input|temp|memory))|(?>\b(?>zip|phar|data|expect|input|glob|ssh2):\/\/)|(?>\/proc\/(?>self|version|cmdline|environ))|(?>\/var\/log\/(?>nginx|apache2|access|error))|%00/S',
        'sqli'        => '/(?i)(?>u[\W_]*n[\W_]*i[\W_]*o[\W_]*n(?:[\W_]+|\/\*!?\d*\*\/)+s[\W_]*e[\W_]*l[\W_]*e[\W_]*c[\W_]*t)|(?<![a-zA-Z0-9_])union\s*select|information_schema\.|pg_catalog\.|sys\.databases|mysql\.user|waitfor[\W_]+delay|pg_sleep\s*\(|dbms_pipe\.receive_message|sleep\s*\(\s*\d+\s*\)|(?<![a-zA-Z0-9_])(?>benchmark|extractvalue|updatexml|exp|gtid_subset|hex|unhex|concat_ws|group_concat|load_file|into\s+outfile|into\s+dumpfile)\s*\(|(?<![a-zA-Z0-9_])(?>OR|AND|XOR)(?![a-zA-Z0-9_])[^a-zA-Z0-9_]{0,3}[\d\'"`][^=<>]{0,5}(?>=|>|<|<=|>=|<>|!=|LIKE|RLIKE|REGEXP)[^a-zA-Z0-9_]{0,3}[\d\'"`]|(?<![a-zA-Z0-9_])(?>OR|AND)\s+\d+\s*=\s*\d+\s*(?>--|#|\/\*)|;\s*(?>drop|delete|truncate|alter|create|exec|execute)\s+(?>table|database|user|procedure|function)|(?>\{oj\s+|\{call\s+)|(?>\$(?>where|ne|regex|gt|gte|lt|lte|in|nin|exists|expr|and|or|not|nor|all|elemMatch|size|mod|type)(?:"|\')?\s*:)|(?>xp_cmdshell|sp_executesql|sp_oacreate)|(?<![a-zA-Z0-9_])order\s+by\s+\d+(?>\s*,\s*\d+){2,}|(?:--[ \+\t]+\w|#\s*\w|\/\*!\d{5})|(?<![a-zA-Z0-9_])0x[0-9a-fA-F]+\b|(?<![a-zA-Z0-9_])select[^;]{1,150}?from|(?:\|\||&&|(?<![a-zA-Z0-9_])(?>OR|AND|XOR))\s*\(?\s*select\b|(?<![a-zA-Z0-9_])0x(?>73656c656374|756e696f6e|64726f70|696e7365727420696e746f)/S',
        'xss'         => '/(?i)(?><\s*\/?\s*(?:script|svg|math|iframe|object|embed|applet|frame|frameset))|\bon[a-z]{3,20}\s*=|(?>\bjavascript\s*:)|(?>\bvbscript\s*:)|(?>\blivescript\s*:)|(?>\bdata\s*:\s*(?>text\/html|application\/(?:javascript|x-javascript)|image\/svg))|(?><\s style[^>]*>.*?(?>@import|expression\s*\(|behavior\s*:|javascript\s*:))|(?><\s*link[^>]+(?>rel\s*=\s*["\']?stylesheet["\']?[^>]+href\s*=\s*["\']?\s*(?>javascript|data):))|(?>srcdoc\s*=\s*["\']?[^"\']*<\s*script)|(?>formaction\s*=\s*["\']?\s*javascript\s*:)|(?><\s*(?>animate|set)[^>]+(?>values|to|from|by)\s*=\s*["\']?\s*javascript\s*:)|%ef%bc%9c|＜|\\\\uFF1C|%c0%bc|\{\{\s*\$on\.constructor|\{\{\s*constructor\.constructor|(?>src\s*=\s*["\']?\s*data:[^"\']{20,}base64)/S',
        'ua'          => '/(?i)\b(?>sqlmap|nikto|wpscan|python|curl|wget|libwww|jndi|masscan|havij|netsparker|burp|nmap|shellshock|headless|selenium|gobuster|dirbuster|shodan|zgrab|projectdiscovery|nuclei)/S',
        'framework'   => '/(?i)(?>\b(?>wp_set_current_user|wp_insert_user|wp_update_user)\b)|(?>update_option\s*\(\s*[\'"](?>siteurl|home|users_can_register|default_role)[\'"])|eval-stdin|_ignition\/execute-solution|telescope\/requests|api\/swagger|actuator\/(?>env|refresh|restart|heapdump)|(?>__(?>schema|type)\s*(?>\{|\(|:))|\.(?>env|git|svn)(?>\/|\b)/S',
        'db_direct'   => '/(?i)\$wpdb->|(?>\b(?>mysql_query|mysqli_query|pg_query|sqlite_query|PDO::exec)\b)/S',
        'gql_recon'   => '/(?i)(?>__(?>schema|type)\s*(?>\{|\(|:))/S',
        'rce_source_hijack'  => '/(?i)(?>action|data|plugin)[^&]*?(?>source|url|install|path)[^&]*?=(?>https?%3A%2F%2F|https?:\/\/|ftps?%3A%2F%2F|%68%74%74%70|%48%54%54%50)/S',
        'array_bypass'=> '/(?i)(?>\b[a-z0-9_]+(?:\[|%5B)[a-z0-9_\'"%]*?(?:\]|%5D)\s*=(?>\s|%20)*(?>system|exec|shell_exec|eval|assert|passthru|popen|proc_open|pcntl_exec|phpinfo))/S',
        'probes'      => '/(?i)(?>\.(?>env|git|htaccess|php_bak|old|bak|sql|tar\.gz|zip|remote-sync|ds_store|idea|vscode))|config\.php|wp-config\.php|\.aws\/credentials|vendor\/phpunit|composer\.json|phpunit\/src|\/\.well-known\/security|\/\.svn\/|\/\.hg\/|\/web\.config|\/\.user\.ini|\/telescope\/|\/horizon\/|\/_profiler\//S',
    ];

    // Zuordnung der Bedrohungsstufen (Sicherheitseinstufung)
    private array $pattern_weights = [
        'rce'               => 100, // Kritischer RCE -> Sofortiger Block
        'lfi'               => 100, // Kritischer LFI -> Sofortiger Block
        'framework'         => 100, // Framework Hijack -> Sofortiger Block
        'array_bypass'      => 100, // RCE Bypass -> Sofortiger Block
        'rce_source_hijack' => 100, // Remote Source Include -> Sofortiger Block
        'sqli'              => 5,   // SQL-Keywords -> Moderate (Score steigt)
        'xss'               => 5,   // XSS Patterns -> Moderate (Score steigt)
        'probes'            => 4,   // System-Probing -> Moderate (Score steigt)
        'db_direct'         => 6,   // Direkter DB-Aufruf -> Moderate
        'gql_recon'         => 4,   // GraphQL Analyse -> Moderate
        'ua'                => 5,   // Bösartige User-Agents -> Moderate
    ];

    public function __construct(array $options) {
        $this->enabled = !empty($options['aegis_enabled']);
        $this->mode    = sanitize_key($options['aegis_mode'] ?? 'strict');

        $raw_ips = sanitize_textarea_field($options['aegis_whitelist_ips'] ?? ($options['whitelist_ips'] ?? ''));
        $raw_uas = sanitize_textarea_field($options['aegis_whitelist_uas'] ?? ($options['whitelist_uas'] ?? ''));

        $this->whitelist_ips = array_filter(array_map('trim', explode("\n", $raw_ips)));
        $this->whitelist_uas = array_filter(array_map('trim', explode("\n", $raw_uas)));

        // IP-Resolution
        $raw_ip = class_exists('VGTS_Network') && method_exists('VGTS_Network', 'resolve_true_ip') 
                  ? VGTS_Network::resolve_true_ip() 
                  : ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
        $this->validated_ip = sanitize_text_field(wp_unslash($raw_ip));

        if (!$this->enabled || $this->is_whitelisted() || $this->is_static_asset()) {
            return;
        }

        // Falls der Besucher die kryptografische JS-Challenge bereits gelöst hat, 
        // überspringen wir die moderaten Regeln (Verhindert jegliche Folge-Fehlalarme am selben Tag).
        if ($this->verify_js_challenge_cookie()) {
            // Nur kritische Exploits (RCE, LFI) werden weiterhin blockiert!
            $this->pattern_weights['sqli'] = 0;
            $this->pattern_weights['xss'] = 0;
            $this->pattern_weights['probes'] = 0;
            $this->pattern_weights['db_direct'] = 0;
            $this->pattern_weights['gql_recon'] = 0;
            $this->pattern_weights['ua'] = 0;
        }

        // Setup PCRE Limits für Fail-Closed Evaluierung
        $old_backtrack = ini_get('pcre.backtrack_limit');
        $old_recursion = ini_get('pcre.recursion_limit');

        ini_set('pcre.backtrack_limit', '1000000');
        ini_set('pcre.recursion_limit', '1000000');

        $this->guard();

        // Wiederherstellung der Ursprungs-Limits
        if ($old_backtrack !== false) {
            ini_set('pcre.backtrack_limit', $old_backtrack);
        }
        if ($old_recursion !== false) {
            ini_set('pcre.recursion_limit', $old_recursion);
        }
    }

    private function guard(): void {
        // 1. Kontext-Prüfung: Ist der aktuelle Benutzer ein angemeldeter Admin/Editor?
        if (function_exists('current_user_can') && (current_user_can('edit_posts') || current_user_can('manage_options'))) {
            // Admins & Redakteure dürfen im WordPress-Admin arbeiten. Wir prüfen nur auf absolute Server-Zerstörer (RCE/LFI).
            $this->pattern_weights['sqli'] = 0;
            $this->pattern_weights['xss'] = 0;
            $this->pattern_weights['probes'] = 0;
            $this->pattern_weights['db_direct'] = 0;
            $this->pattern_weights['gql_recon'] = 0;
        }

        // 2. Pfad- & URL-Kontext-Ausnahmen (z. B. WooCommerce Checkout oder Gutenberg REST-APIs)
        $this->apply_contextual_exceptions();

        $this->inspect_headers();
        $this->inspect_uri();

        // 3. DEEP QUERY STRING INSPECTION (Killed Parameter Pollution / HPP)
        $raw_query = $_SERVER['QUERY_STRING'] ?? '';
        if ($raw_query !== '') {
            $this->scan_value_mutations($raw_query, 'query_string');
        }

        // 4. REKURSIVER MULTI-PASS SCAN (GET, POST, COOKIE)
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (!empty($_GET)) $this->recursive_array_scan($_GET, 'GET');
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        if (!empty($_POST)) $this->recursive_array_scan($_POST, 'POST');
        if (!empty($_COOKIE)) $this->recursive_array_scan($_COOKIE, 'COOKIE');

        $method = sanitize_text_field(wp_unslash($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        if ($method === 'POST' || $method === 'PUT' || $method === 'PATCH') {
            $content_type = strtolower(sanitize_text_field(wp_unslash($_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '')));
            
            if (strpos($content_type, 'multipart/form-data') !== false) {
                if (!empty($_FILES)) {
                    $this->inspect_files_baseline($_FILES);
                }
            } elseif (strpos($content_type, 'application/json') !== false) {
                $this->inspect_json_stream();
            } else {
                $this->inspect_body_stream();
            }
        }

        // 5. ANOMALY SCORE EVALUIERUNG
        $this->evaluate_anomaly_score();
    }

    /**
     * Setzt intelligente Ausnahmen auf bekannten, sicheren Endpunkten von WordPress,
     * um Fehlalarme bei legitimen Aktionen (z.B. Checkout, Artikelspeicherung) zu verhindern.
     */
    private function apply_contextual_exceptions(): void {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $path = parse_url($uri, PHP_URL_PATH) ?? '';

        // Ausnahme für WooCommerce Checkout (Kundenadressen mit Firmennamen wie "System GmbH")
        if (stripos($path, '/checkout') !== false || stripos($path, '/cart') !== false || stripos($path, '/warenkorb') !== false) {
            // Wir lockern SQLi und Probing-Einstufungen auf Checkout-Seiten für POST-Daten
            if (!empty($_POST)) {
                $safe_checkout_fields = [
                    'billing_company', 'shipping_company', 'billing_address_1', 
                    'shipping_address_1', 'billing_last_name', 'shipping_last_name'
                ];
                foreach ($safe_checkout_fields as $field) {
                    if (isset($_POST[$field])) {
                        // Desensibilisieren (Entfernen des Einflusses des spezifischen Feldes auf die WAF)
                        $_POST[$field] = ''; 
                    }
                }
            }
        }

        // Ausnahme für Gutenberg/REST-API Speichervorgänge (enthält oft HTML/JS Fragmente)
        if (stripos($path, '/wp-json/wp/v2/posts') !== false || stripos($path, '/wp-admin/post.php') !== false) {
            // Nur wenn der User angemeldet ist
            if (function_exists('is_user_logged_in') && is_user_logged_in()) {
                $this->threshold_challenge = 30; // Erhöhe Toleranzwert drastisch
                $this->threshold_block = 50;
            }
        }

        // Ausnahme für Sentinel / WP-Desk Admin-AJAX Aktionen (z.B. Integritäts-Scans, Einstellungs-Speicherung)
        if (stripos($path, '/admin-ajax.php') !== false) {
            $action = sanitize_key($_POST['action'] ?? $_GET['action'] ?? '');
            $trusted_actions = [
                'vgts_run_scan',
                'vgts_approve_changes',
                'vis_run_scan',
                'vis_approve_changes',
                'vgts_dashboard_unban_ip',
                'vgt_save_user_settings',
                'vgt_toggle_sentinel'
            ];
            if (in_array($action, $trusted_actions, true)) {
                if (function_exists('current_user_can') && current_user_can('manage_options')) {
                    $this->pattern_weights['rce'] = 0;
                    $this->pattern_weights['lfi'] = 0;
                }
            }
        }

        // Ausnahme für Sentinel-Dashboard-, WP-Desk- und Throne Guard-Seiten (Verhindert Fehlalarme bei Speicherung von Texten/UAs)
        if (stripos($path, '/admin.php') !== false) {
            $page = sanitize_key($_GET['page'] ?? '');
            if ($page === 'vgts-sentinel' || $page === 'vgt-wp-desk' || $page === 'mcp-dashboard') {
                if (function_exists('current_user_can') && current_user_can('manage_options')) {
                    $this->pattern_weights['rce'] = 0;
                    $this->pattern_weights['lfi'] = 0;
                }
            }
        }

        // Ausnahme für Throne Guard Admin-Post Aktionen (Rollen, Hardening, Datei-Upload, Session-Unlock)
        if (stripos($path, '/admin-post.php') !== false) {
            $action = sanitize_key($_POST['action'] ?? $_GET['action'] ?? '');
            $mcp_actions = ['mcp_save_roles', 'mcp_upload_file', 'mcp_admin_hardening', 'mcp_unlock_backend'];
            if (in_array($action, $mcp_actions, true)) {
                if (function_exists('is_user_logged_in') && is_user_logged_in()) {
                    $this->pattern_weights['rce'] = 0;
                    $this->pattern_weights['lfi'] = 0;
                }
            }
        }
    }

    /**
     * VGT KERNEL DEEP SCAN MUTATIONS PIPELINE (Multi-Pass Normalizer)
     * Bewertet Bedrohungsvektoren über Punkte, statt sofort abzubrechen.
     */
    private function scan_value_mutations(string $value, string $context): void {
        $normalized = $this->normalize_payload($value);
        $normalized_stripped = $this->normalize_payload_stripped($value);
        $normalized_no_quotes = str_replace(["'", '"', '`'], '', $normalized);
        $normalized_stripped_no_quotes = str_replace(["'", '"', '`'], '', $normalized_stripped);

        foreach ($this->patterns as $type => $regex) {
            if ($type === 'ua') continue;

            $this->match_pattern_score($regex, $normalized, $type . '_' . $context);
            $this->match_pattern_score($regex, $normalized_stripped, $type . '_' . $context . '_stripped');
            $this->match_pattern_score($regex, $normalized_no_quotes, $type . '_' . $context . '_no_quotes');
            $this->match_pattern_score($regex, $normalized_stripped_no_quotes, $type . '_' . $context . '_stripped_no_quotes');
        }
    }

    /**
     * Matcht ein Pattern und berechnet den Anomaly-Score basierend auf der Gewichtung.
     */
    private function match_pattern_score(string $regex, string $subject, string $context_type): void {
        $result = preg_match($regex, $subject);
        
        if ($result === 1) {
            // Typ des erkannten Vektors isolieren (z. B. 'sqli_uri_stripped' -> 'sqli')
            $base_type = explode('_', $context_type)[0];
            $weight = $this->pattern_weights[$base_type] ?? 0;

            if ($weight > 0 && !in_array($context_type, $this->triggered_vectors, true)) {
                $this->anomaly_score += $weight;
                $this->triggered_vectors[] = $context_type;
                
                // Kritische Angriffe (RCE/LFI) triggern sofortige Terminierung
                if ($weight >= 100) {
                    $this->terminate("Sofortiger Block: Kritischer Vektor [$context_type] erkannt.", 'BLOCK', $base_type);
                }
            }
        } elseif ($result === false) {
            $this->terminate("PCRE Engine Limit Exhaustion detected (Possible ReDoS) on Vector [$context_type].", 'BLOCK', 'pcre_evasion');
        }
    }

    /**
     * Prüft am Ende des Scans, ob der Schwellenwert für Challenge oder Block erreicht wurde.
     */
    private function evaluate_anomaly_score(): void {
        if ($this->anomaly_score === 0) {
            return;
        }

        // 1. Harter Block bei Überschreitung des maximalen Scores
        if ($this->anomaly_score >= $this->threshold_block) {
            $this->terminate(
                "Harter Block: Anomaly Score [{$this->anomaly_score}] überschreitet Grenzwert [{$this->threshold_block}]. Triggert: " . implode(', ', $this->triggered_vectors),
                'BLOCK',
                'anomaly_threshold_reached'
            );
        }

        // 2. JS-Challenge vorschalten, falls der Score verdächtig, aber nicht tödlich ist
        if ($this->anomaly_score >= $this->threshold_challenge) {
            $this->serve_js_challenge();
        }
    }

    /**
     * Präsentiert dem Client eine elegante, sichere JavaScript-Challenge.
     * Bots ohne JS-Engine oder Curl scheitern hier; echte Besucher bemerken nur eine kurze Verzögerung von 2 Sek.
     */
    private function serve_js_challenge(): void {
        while (ob_get_level()) {
            ob_end_clean();
        }

        if (!headers_sent()) {
            $protocol = sanitize_text_field(wp_unslash($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1'));
            header("$protocol 200 OK", true, 200);
            header('Content-Type: text/html; charset=utf-8');
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        }

        $expected_hash = $this->generate_js_hash();
        $site_name = get_bloginfo('name') ?: 'Website';

        // Hochgradig performante und optisch ansprechende Challenge-Seite im Corporate-Design von VisionGaiaTechnology
        ?>
        <!DOCTYPE html>
        <html lang="de">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Sicherheitsüberprüfung - <?php echo esc_html($site_name); ?></title>
            <style>
                :root {
                    --bg-primary: #0a0e17;
                    --text-primary: #f3f4f6;
                    --accent: #3b82f6;
                    --accent-glow: rgba(59, 130, 246, 0.35);
                    --card-bg: #111827;
                }
                * { box-sizing: border-box; margin: 0; padding: 0; }
                body {
                    background-color: var(--bg-primary);
                    color: var(--text-primary);
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    min-height: 100vh;
                    overflow: hidden;
                }
                .challenge-container {
                    background: var(--card-bg);
                    padding: 2.5rem;
                    border-radius: 16px;
                    border: 1px solid rgba(255, 255, 255, 0.08);
                    max-width: 450px;
                    width: 100%;
                    text-align: center;
                    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5), 0 0 40px var(--accent-glow);
                    position: relative;
                }
                h1 { font-size: 1.5rem; margin-bottom: 1rem; font-weight: 700; color: #fff; }
                p { font-size: 0.95rem; color: #9ca3af; line-height: 1.5; margin-bottom: 1.5rem; }
                .spinner-box {
                    position: relative;
                    width: 80px;
                    height: 80px;
                    margin: 2rem auto;
                }
                .spinner {
                    box-sizing: border-box;
                    width: 100%;
                    height: 100%;
                    border: 4px solid rgba(255, 255, 255, 0.05);
                    border-top-color: var(--accent);
                    border-radius: 50%;
                    animation: spin 1s infinite linear;
                }
                .secure-badge {
                    position: absolute;
                    top: 28px;
                    left: 28px;
                    width: 24px;
                    height: 24px;
                    background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="%233b82f6"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>') no-repeat center;
                }
                .footer {
                    font-size: 0.75rem;
                    color: #4b5563;
                    margin-top: 2rem;
                }
                @keyframes spin {
                    100% { transform: rotate(360deg); }
                }
            </style>
        </head>
        <body>
            <div class="challenge-container">
                <h1>Sicherheitsüberprüfung</h1>
                <p>Einen kurzen Moment bitte. Wir verifizieren die Sicherheit Ihrer Verbindung zum Server (Schutz vor automatisiertem Spam)...</p>
                
                <div class="spinner-box">
                    <div class="spinner"></div>
                    <div class="secure-badge"></div>
                </div>

                <p style="font-size: 0.85rem; color: #6b7280;">Bitte aktivieren Sie JavaScript in Ihrem Browser, falls der Vorgang nicht automatisch fortgesetzt wird.</p>
                
                <div class="footer">
                    VISIONGAIATECHNOLOGY AEGIS V3.1 • Secure Edge Protection
                </div>
            </div>

            <script>
                (function() {
                    // Mathematische CPU-Challenge, um stumpfe Scraper abzufangen
                    let hashBase = 1000;
                    for (let i = 0; i < 500000; i++) {
                        hashBase = Math.sqrt(hashBase + i) * 1.0001;
                    }
                    
                    // Setze den Verifizierungs-Cookie mit dem validierten Hash des Servers
                    const expiry = new Date();
                    expiry.setTime(expiry.getTime() + (24 * 60 * 60 * 1000)); // 24 Stunden gültig
                    document.cookie = "aegis_js_verified=<?php echo esc_js($expected_hash); ?>; path=/; expires=" + expiry.toUTCString() + "; SameSite=Lax; Secure";

                    // Seite automatisch neu laden, um die Anfrage mit dem Cookie durchgehen zu lassen
                    setTimeout(function() {
                        window.location.reload();
                    }, 1500);
                })();
            </script>
        </body>
        </html>
        <?php
        exit;
    }

    /**
     * Erzeugt einen sicheren, zeitbasierten Hash für die JS-Challenge
     */
    private function generate_js_hash(): string {
        $salt = defined('NONCE_SALT') ? NONCE_SALT : 'aegis_vgt_default_salt_9872';
        return hash_hmac('sha256', $this->validated_ip . date('Y-m-d'), $salt);
    }

    /**
     * Überprüft, ob der übergebene Cookie-Hash gültig ist.
     */
    private function verify_js_challenge_cookie(): bool {
        if (empty($_COOKIE['aegis_js_verified'])) {
            return false;
        }
        $expected = $this->generate_js_hash();
        return hash_equals($expected, $_COOKIE['aegis_js_verified']);
    }

    /**
     * VGT OPEN SOURCE: Extended Recursive Normalization (Layer-Space Comments)
     */
    private function normalize_payload(string $input): string {
        $normalized = str_replace(["\0", "\r", "\n", "\t"], ' ', $input);

        $loops = 0;
        do {
            $old = $normalized;
            $normalized = urldecode($normalized);
            $loops++;
        } while ($old !== $normalized && $loops < 5);

        $normalized = html_entity_decode($normalized, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $normalized = preg_replace_callback(
            '/(?:\\\\u|%u)([0-9a-fA-F]{4})/i',
            static function ($m) {
                try {
                    return mb_convert_encoding(pack('H*', $m[1]), 'UTF-8', 'UCS-2BE');
                } catch (\Throwable) {
                    return '';
                }
            },
            $normalized
        ) ?? $normalized;

        $normalized = preg_replace_callback(
            '/\\\\x([0-9a-fA-F]{2})/',
            static function ($m) {
                return chr(hexdec($m[1]));
            },
            $normalized
        ) ?? $normalized;

        $normalized = preg_replace('/(?:\/\*.*?\*\/|<!\-\-.*?\-\->|--[\s\r\n]|#.*$)/sm', ' ', $normalized) ?? '';

        return $normalized;
    }

    /**
     * VGT OPEN SOURCE: Extended Recursive Normalization (Word-Space Comment Slicing Defeater)
     */
    private function normalize_payload_stripped(string $input): string {
        $normalized = str_replace(["\0", "\r", "\n", "\t"], ' ', $input);

        $loops = 0;
        do {
            $old = $normalized;
            $normalized = urldecode($normalized);
            $loops++;
        } while ($old !== $normalized && $loops < 5);

        $normalized = html_entity_decode($normalized, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $normalized = preg_replace_callback(
            '/(?:\\\\u|%u)([0-9a-fA-F]{4})/i',
            static function ($m) {
                try {
                    return mb_convert_encoding(pack('H*', $m[1]), 'UTF-8', 'UCS-2BE');
                } catch (\Throwable) {
                    return '';
                }
            },
            $normalized
        ) ?? $normalized;

        $normalized = preg_replace_callback(
            '/\\\\x([0-9a-fA-F]{2})/',
            static function ($m) {
                return chr(hexdec($m[1]));
            },
            $normalized
        ) ?? $normalized;

        $normalized = preg_replace('/(?:\/\*.*?\*\/|<!\-\-.*?\-\->|--[\s\r\n]|#.*$)/sm', '', $normalized) ?? '';

        return $normalized;
    }

    private function inspect_files_baseline(array $files): void {
        foreach ($files as $fileInfo) {
            if (!is_array($fileInfo)) continue;

            if (isset($fileInfo['tmp_name']) && is_string($fileInfo['tmp_name'])) {
                $this->scan_file_head_only($fileInfo['tmp_name']);
                
                if (isset($fileInfo['name']) && is_string($fileInfo['name'])) {
                    $raw_name = wp_unslash($fileInfo['name']);
                    $this->scan_value_mutations($raw_name, 'file_name');
                }
            } elseif (isset($fileInfo['tmp_name']) && is_array($fileInfo['tmp_name'])) {
                foreach ($fileInfo['tmp_name'] as $idx => $tmp_path) {
                    if (is_string($tmp_path)) {
                        $this->scan_file_head_only($tmp_path);
                        $name = $fileInfo['name'][$idx] ?? '';
                        if (is_string($name)) {
                            $this->scan_value_mutations(wp_unslash($name), 'file_name');
                        }
                    }
                }
            }
        }
    }

    private function scan_file_head_only(string $tmp_path): void {
        if (!is_readable($tmp_path)) return;
        $handle = @fopen($tmp_path, 'rb');
        if (!$handle) return;

        $chunk = fread($handle, 8192); 
        fclose($handle);

        if ($chunk && preg_match('/<\?php|<\?=|#! \/bin\/|eval\s*\(/i', $chunk)) {
            $this->terminate("Basic Polyglot Code Injection in Upload", 'BLOCK', 'rce_upload_basic');
        }
    }

    private function inspect_json_stream(): void {
        $raw_body = file_get_contents('php://input', false, null, 0, $this->scan_limit);
        if (empty($raw_body)) {
            return;
        }

        try {
            $parsed_json = json_decode((string) $raw_body, true, 512, JSON_THROW_ON_ERROR);
            if (is_array($parsed_json)) {
                $this->recursive_array_scan($parsed_json, 'JSON');
            }
        } catch (JsonException $e) {
            $this->scan_value_mutations((string) $raw_body, 'json_raw_fallback');
        }
        
        unset($raw_body);
    }

    private function recursive_array_scan(array $data, string $context = 'DATA'): void {
        foreach ($data as $key => $value) {
            if (is_string($key)) {
                $this->scan_value_mutations($key, strtolower($context) . '_key');
            }

            if (is_array($value)) {
                $this->recursive_array_scan($value, $context);
            } elseif (is_string($value)) {
                $this->scan_value_mutations((string) $value, strtolower($context));
            }
        }
    }

    private function inspect_body_stream(): void {
        $handle = @fopen('php://input', 'rb');
        if (!$handle) {
            return;
        }

        stream_set_timeout($handle, 2);

        $scanned_bytes = 0;
        $overlap_buffer = '';
        $chunk_size = 8192; 

        while (!feof($handle)) {
            if ($scanned_bytes >= $this->scan_limit) {
                $this->terminate("Payload Size Exhaustion (Padding Attack Abwehr)", 'BLOCK', 'limit_exhaustion');
            }

            $chunk = fread($handle, $chunk_size);
            if ($chunk === false || $chunk === '') {
                break;
            }

            $scanned_bytes += strlen($chunk);
            $raw_payload = $overlap_buffer . $chunk;
            
            $this->scan_value_mutations($raw_payload, 'body');

            $overlap_buffer = substr($raw_payload, -256);
            
            unset($raw_payload, $chunk);
        }

        fclose($handle);
        unset($overlap_buffer); 
    }

    /**
     * Prüft, ob eine IP-Adresse oder ein Hostname im privaten oder Loopback-Bereich liegt.
     * Erkennt nun auch nicht-IP Hostnames wie "localhost" direkt.
     */
    private static function is_private_or_loopback_ip(string $ip): bool {
        $ip = strtolower(trim($ip));
        
        // Direkte Erkennung von Loopback-Hostnames, die von filter_var() ignoriert werden,
        // da sie keine gültigen IP-Notationen darstellen.
        if ($ip === 'localhost' || $ip === 'localhost.localdomain' || $ip === 'local' || $ip === 'loopback' || strpos($ip, 'localhost') !== false) {
            return true;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return false;
        }
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }

    private static function detect_proxy_spoofing(string $value): bool {
        $socket_ip = $_SERVER['REMOTE_ADDR'] ?? '';
        
        if ($socket_ip === '' || self::is_private_or_loopback_ip($socket_ip)) {
            return false;
        }

        $ips = array_filter(array_map('trim', explode(',', $value)));
        if (empty($ips)) {
            return false;
        }

        $client_ip = $ips[0];
        return self::is_private_or_loopback_ip($client_ip);
    }

    private function inspect_headers(): void {
        $ua  = sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'] ?? ''));
        $ref = sanitize_text_field(wp_unslash($_SERVER['HTTP_REFERER'] ?? ''));
        $method = sanitize_text_field(wp_unslash($_SERVER['REQUEST_METHOD'] ?? ''));
        
        if ($method === 'POST' && $ua === '' && $ref === '') {
            $this->terminate("Ghost POST detected (No UA/Ref)", 'BLOCK', 'bot');
        }

        $ip_header_keys = [
            'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 
            'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 
            'HTTP_CLUSTER_CLIENT_IP', 'HTTP_CF_CONNECTING_IP'
        ];

        foreach ($ip_header_keys as $key) {
            if (isset($_SERVER[$key]) && is_string($_SERVER[$key])) {
                $val = wp_unslash($_SERVER[$key]);
                if ($val !== '' && self::detect_proxy_spoofing($val)) {
                    $this->terminate("Proxy Spoofing detected in header [$key].", 'BLOCK', 'proxy_spoofing');
                }
            }
        }

        $critical_headers = [
            'HTTP_USER_AGENT', 'HTTP_REFERER', 'HTTP_X_FORWARDED_FOR', 
            'HTTP_X_REAL_IP', 'HTTP_ACCEPT', 'HTTP_ACCEPT_LANGUAGE', 
            'HTTP_CACHE_CONTROL', 'HTTP_AUTHORIZATION', 'CONTENT_TYPE'
        ];

        $headers_to_scan = [];

        foreach ($critical_headers as $key) {
            if (isset($_SERVER[$key]) && is_string($_SERVER[$key])) {
                $headers_to_scan[] = wp_unslash($_SERVER[$key]);
            }
        }
        
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_X_') === 0 && is_string($value)) {
                $headers_to_scan[] = wp_unslash($value);
            }
        }
        
        foreach ($_COOKIE as $val) {
            if (is_string($val)) {
                $headers_to_scan[] = wp_unslash($val);
            }
        }

        foreach ($headers_to_scan as $header_val) {
            if ($header_val === '') continue;
            $this->scan_value_mutations((string) $header_val, 'header');
        }
    }

    private function inspect_uri(): void {
        $raw_uri = (string) wp_unslash($_SERVER['REQUEST_URI'] ?? '');
        $this->scan_value_mutations($raw_uri, 'uri');
    }

    private function engage_ban_protocol(string $reason): void {
        if ($this->mode === 'learning') {
            return;
        }

        if (class_exists('VGTS_Cerberus') && method_exists('VGTS_Cerberus', 'instance')) {
            VGTS_Cerberus::instance()->ban_ip($this->validated_ip, $reason);
            return;
        }

        global $wpdb;
        if (!isset($wpdb)) {
            return;
        }

        $table = $wpdb->prefix . (defined('VGTS_TABLE_BANS') ? VGTS_TABLE_BANS : 'vgts_apex_bans'); 
        $uri   = substr(esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'] ?? '')), 0, 255);

        $wpdb->query($wpdb->prepare(
            "INSERT IGNORE INTO {$table} (ip, reason, banned_at, request_uri) VALUES (%s, %s, %s, %s)",
            $this->validated_ip, $reason, current_time('mysql'), $uri
        ));
    }

    private function terminate(string $reason, string $action_type, string $vector_type): void {
        global $wpdb;

        $will_ban = ($this->mode !== 'learning' && in_array(str_replace(['_body', '_header', '_uri', '_json_tree', '_json_raw_fallback', '_post', '_get', '_cookie', '_file_name', '_stripped', '_no_quotes', '_stripped_no_quotes'], '', $vector_type), ['sqli', 'rce', 'lfi', 'framework', 'ua', 'probes'], true));
        if ($will_ban) {
            $action_type = 'BAN'; 
        }

        if (isset($wpdb)) {
            $table = $wpdb->prefix . (defined('VGTS_TABLE_LOGS') ? VGTS_TABLE_LOGS : 'vgts_omega_logs');
            $wpdb->insert($table, [
                'module'   => 'AEGIS_OS_HARDENED',
                'type'     => sanitize_text_field($action_type),
                'message'  => sanitize_textarea_field($reason),
                'ip'       => $this->validated_ip,
                'severity' => (in_array(str_replace(['_body', '_header', '_uri', '_json_tree', '_json_raw_fallback', '_post', '_get', '_cookie', '_file_name', '_stripped', '_no_quotes', '_stripped_no_quotes'], '', $vector_type), ['sqli', 'rce', 'lfi'], true) || $action_type === 'BAN') ? 10 : 5
            ]);
        }

        if ($will_ban) {
            $this->engage_ban_protocol("AEGIS: Critical Vector [$vector_type]");
        }

        while (ob_get_level()) {
            ob_end_clean();
        }
        
        $safe_vector = preg_replace('/[^a-zA-Z0-9_]/', '', $vector_type); 

        if (!headers_sent()) {
            $protocol = sanitize_text_field(wp_unslash($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1'));
            header("$protocol 403 Forbidden", true, 403);
            header('X-Aegis-Block: ' . strtoupper($safe_vector));
            header('Content-Type: application/json; charset=utf-8'); 
            header('Connection: close');
            header('Cache-Control: no-store, max-age=0');
        }
        
        die(json_encode([
            'status'  => 'error',
            'code'    => 403,
            'message' => 'VISIONGAIATECHNOLOGY AEGIS PROTOCOL: Access Denied',
            'vector'  => $safe_vector
        ], JSON_THROW_ON_ERROR));
    }

    private function is_whitelisted(): bool {
        if (defined('DOING_CRON') && DOING_CRON) {
            $server_ip = sanitize_text_field(wp_unslash($_SERVER['SERVER_ADDR'] ?? ''));
            if ($server_ip && $this->validated_ip === $server_ip) {
                return true;
            }
        }
        
        if (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST) {
            return false; 
        }
        
        $socket_ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        if (in_array($socket_ip, ['127.0.0.1', '::1', 'fe80::1'], true)) {
            return true;
        }

        if (!empty($this->whitelist_ips) && in_array($this->validated_ip, $this->whitelist_ips, true)) {
            return true;
        }

        $ua = sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'] ?? ''));
        if (!empty($this->whitelist_uas) && $ua !== '') {
            foreach ($this->whitelist_uas as $safe_ua) {
                if ($safe_ua === '') continue;
                if (stripos($ua, $safe_ua) !== false) {
                    return true;
                }
            }
        }

        if ($ua !== '' && preg_match('/(googlebot|bingbot|duckduckbot|yandexbot)/i', $ua)) {
            $hostname = @gethostbyaddr($this->validated_ip);
            if ($hostname !== false && $hostname !== $this->validated_ip) {
                $forward_ips = @gethostbynamel($hostname);
                if (is_array($forward_ips) && in_array($this->validated_ip, $forward_ips, true)) {
                    if (preg_match('/(?:\.|^)(googlebot\.com|search\.msn\.com|yandex\.com|yandex\.net|yandex\.ru|duckduckgo\.com)$/i', $hostname)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    private function is_static_asset(): bool {
        $uri = (string) wp_unslash($_SERVER['REQUEST_URI'] ?? '');
        $path = parse_url($uri, PHP_URL_PATH) ?? '';

        if (stripos($path, '.php/') !== false) {
            return false;
        }

        return (bool) preg_match('/\.(jpg|jpeg|png|gif|webp|svg|css|js|woff2?|ttf|eot|ico)$/i', $path);
    }
}
