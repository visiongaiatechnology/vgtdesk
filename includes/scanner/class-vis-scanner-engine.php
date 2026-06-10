<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * ENGINE: SCANNER CORE (HYBRID FUSION + MALWARE SIGNATURE ENGINE)
 * Status: OMEGA HARDENED V5.2 (PHP-VAULT / WP.ORG COMPLIANT / EMBEDDED SIGNATURES)
 * * Security Upgrades (V5.2 - REDTEAM PATCHED):
 * - Fixed CWE-290: Removed size/mtime pre-filter trust (Time-stomping evasion patched).
 * - Fixed CWE-185: ReDoS limits trigger fail-closed evasion detection.
 * * Security Upgrades (V5.1 - REDTEAM PATCHED):
 * - Fixed CWE-424: Strict relative path prefix matching for exclude directories to prevent evasion.
 * - Fixed CWE-184: Removed size limitations on polyglot signatures (greedy wildcard bypass patched).
 * * Security Upgrades (V5.0):
 * - Embedded Malware Signature Database (Zero-IO, OPcache-resident)
 * - Two-stage scan: Integrity check first, signature scan only on suspicious files
 * - New change type: MALWARE (distinct from MODIFIED/NEW)
 * - PCRE fail-closed protection on signature matching
 */
class VGTS_Scanner_Engine {

    // MEF: Exakte relative Pfade definieren, um directory shadowing zu verhindern.
    private array $exclude_dirs = [
        'node_modules', '.git', 'wp-content/cache', 'wp-content/upgrade', 'wp-content/languages', 'wp-content/uploads/vgts-vault-omega'
    ];
    
    private array $monitored_extensions = ['php', 'php5', 'phtml', 'html', 'htm', 'js', 'htaccess', 'py', 'pl'];
    
    private int $time_limit = 5; 
    private int $batch_size = 500; 
    private float $start_time;

    // HARDENING: Manifest ist nun eine PHP Datei
    private string $manifest_file;

    // Signature scanner config
    private const SIGNATURE_SCAN_MAX_FILESIZE = 5242880; // 5MB hard limit per file

    /**
     * VGT MALWARE SIGNATURE DATABASE — EMBEDDED FOR ZERO-IO PERFORMANCE
     */
    private const MALWARE_SIGNATURES = [
        // ─── WORDPRESS-SPECIFIC MALWARE FAMILIES ───
        'wp_vcd_injector'      => '/eval\s*\(\s*base64_decode\s*\(\s*gzinflate/i',
        'wp_vcd_marker'        => '/wp_vcd|wp_cd_code|wp_tmp_code/i',
        'pharma_hack'          => '/\$\w{1,3}\s*=\s*Array\s*\(\s*[\'"]\w+[\'"]\s*,\s*[\'"]\w+[\'"]\s*\)/',
        'wp_filemanager_bd'    => '/\$_(?:GET|POST|REQUEST)\[[\'"]\w+[\'"]\]\s*\(\s*\$_(?:GET|POST|REQUEST)/i',
        
        // ─── GENERIC OBFUSCATION PATTERNS ───
        'eval_base64_dropper'  => '/eval\s*\(\s*base64_decode\s*\(\s*[\'"][A-Za-z0-9+\/]{100,}[\'"]/i',
        'eval_gzinflate'       => '/eval\s*\(\s*gzinflate\s*\(\s*base64_decode/i',
        'eval_str_rot13'       => '/eval\s*\(\s*str_rot13\s*\(/i',
        'eval_pack_hex'        => '/eval\s*\(\s*pack\s*\(\s*[\'"]H\*[\'"]/i',
        'eval_chr_concat'      => '/eval\s*\(\s*chr\s*\(\s*\d+\s*\)\s*\.\s*chr/i',
        'goto_obfuscation'     => '/goto\s+\w+;\s*\w+:\s*goto\s+\w+;\s*\w+:/i',
        
        // ─── KNOWN WEBSHELLS (Signature-Based) ───
        'webshell_c99'         => '/c99sh|c99shell|c99madShell/i',
        'webshell_r57'         => '/r57shell|r57\.gen|r57\.php/i',
        'webshell_phpspy'      => '/phpspy|PhpSpy|PHPSpy/i',
        'webshell_webacoo'     => '/webacoo|WeBaCoo/i',
        'webshell_b374k'       => '/b374k|B374K/i',
        'webshell_wso'         => '/WSOsetcookie|WSOlogin|wso_(?:ex|login)/i',
        'webshell_filesman'    => '/FilesMan|class\s+filesman/i',
        'webshell_alfa'        => '/ALFA_DATA|alfaCmd|AlfaTeaM/i',
        'webshell_marijuana'   => '/Marijuana\s+Shell|MarijuanaShell/i',
        
        // ─── POLYGLOT FILE INDICATORS ───
        // MEF: .*{0,500} Limitierung entfernt. Ungreedy .*? nutzen. PCRE Limit greift als Fallback.
        'gif_php_polyglot'     => '/^GIF89a.*?<\?(?:php|=)/s',
        'jpg_php_polyglot'     => '/\xFF\xD8\xFF.*?<\?(?:php|=)/s',
        'png_php_polyglot'     => '/\x89PNG\x0D\x0A.*?<\?(?:php|=)/s',
        
        // ─── DIRECT SHELL ACCESS VIA USER INPUT ───
        'shell_exec_userinput' => '/(?:shell_exec|passthru|system|exec|popen|proc_open)\s*\(\s*\$_(?:GET|POST|REQUEST|COOKIE|SERVER)/i',
        'preg_replace_eval'    => '/preg_replace\s*\(\s*[\'"][^\'\"]*\/e[\'"]/',
        'eval_userinput'       => '/eval\s*\(\s*(?:stripslashes\s*\()?\s*\$_(?:GET|POST|REQUEST|COOKIE)/i',
        
        // ─── REMOTE FILE INCLUSION ───
        'remote_include'       => '/(?:include|require)(?:_once)?\s*\(\s*[\'"]https?:\/\//i',
        'data_wrapper_exec'    => '/(?:include|require)(?:_once)?\s*\(\s*[\'"]data:\/\//i',
        'php_input_exec'       => '/(?:include|require)(?:_once)?\s*\(\s*[\'"]php:\/\/input/i',
        'php_filter_exec'      => '/(?:include|require)(?:_once)?\s*\(\s*[\'"]php:\/\/filter/i',
        
        // ─── BACKDOOR PATTERNS ───
        'create_function_bd'   => '/create_function\s*\(\s*[\'"][\'"]?\s*,\s*\$_/i',
        'assert_userinput'     => '/assert\s*\(\s*\$_(?:GET|POST|REQUEST|COOKIE)/i',
        'fopen_remote_write'   => '/fopen\s*\(\s*[\'"]https?:\/\/[^\'"]+[\'"]\s*,\s*[\'"]w/i',
        'auth_pass_md5'        => '/\$auth_pass\s*=\s*[\'"][a-f0-9]{32}[\'"]/i',
        'fake_class_marker'    => '/class\s+(?:WSOcommerce|FilesMan|WSO|x509|ClassWriter|VonneAdo|Diakov)/i',
        
        // ─── CRYPTOMINER INDICATORS ───
        'crypto_miner_js'      => '/coinhive|cryptonight|stratum\+tcp|monero|xmr\.omine|coinimp|minr\.js|webminerpool/i',
        'crypto_miner_proc'    => '/(?:xmrig|cpuminer|minerd|cgminer|bfgminer)\s*[\-\s]/i',
        
        // ─── SUSPICIOUS HEADER MANIPULATION ───
        'header_redirect_b64'  => '/header\s*\(\s*[\'"]Location:\s*[\'"]\s*\.\s*base64_decode/i',
        'wp_user_injection'    => '/wp_(?:set_current_user|insert_user|create_user)\s*\([^)]*\$_(?:GET|POST|REQUEST)/i',
    ];

    public function __construct() {
        $this->start_time = microtime(true);
        
        // Sicherer Pfaddefinition
        $upload_dir = wp_upload_dir();
        $vault_dir  = $upload_dir['basedir'] . '/vgts-vault-omega';
        $this->manifest_file = $vault_dir . '/integrity_matrix.php';
    }

    public function perform_scan_batch(int $offset = 0, array $partial_state = []): array {
        $root     = wp_normalize_path(ABSPATH);
        $baseline = $this->load_manifest();
        
        $directory = new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator  = new RecursiveIteratorIterator($directory);
        
        $count     = 0;
        $new_state = $partial_state;
        $completed = false;

        try {
            foreach ($iterator as $file) {
                if ($count < $offset) { 
                    $count++; 
                    continue; 
                }

                if ((microtime(true) - $this->start_time) > $this->time_limit) {
                    return [
                        'status'        => 'processing',
                        'offset'        => $count,
                        /* translators: %d: Number of files analyzed */
                        'message'       => sprintf(esc_html__('Scanning Sector: %d files analyzed...', 'vgt-sentinel-ce'), $count),
                        'current_state' => $new_state
                    ];
                }

                if ($file->isLink()) { 
                    $count++; 
                    continue; 
                }

                $path = wp_normalize_path($file->getPathname());
                // MEF: Path-Generierung vorziehen. Trimmen von führenden Slashes für striktes Prefix-Matching.
                $rel_path = ltrim(str_replace($root, '', $path), '/');

                foreach ($this->exclude_dirs as $ex) {
                    // MEF: An den relativen Start des Pfades binden. Verhindert Shadowing in Sub-Ordnern.
                    if (str_starts_with($rel_path, $ex . '/')) {
                        $count++; 
                        continue 2; 
                    }
                }

                $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                if (!in_array($ext, $this->monitored_extensions, true)) { 
                    $count++; 
                    continue; 
                }

                $mtime    = $file->getMTime();
                $size     = $file->getSize();

                // MEF: Metadaten-Trust komplett entfernt (CWE-290). Hashing ist zwingend.
                $hash = hash_file('sha256', $path);
                $needs_signature_scan = false;
                
                if (!isset($baseline[$rel_path]) || $baseline[$rel_path]['hash'] !== $hash) {
                    // File NEW or MODIFIED — full scan with signature check
                    $needs_signature_scan = true;
                }

                $entry = [
                    'hash'  => $hash,
                    'mtime' => $mtime,
                    'size'  => $size
                ];
                
                // SIGNATURE SCAN — only on suspicious (new/modified) files
                if ($needs_signature_scan) {
                    $sig_hits = $this->scan_signatures($path);
                    if (!empty($sig_hits)) {
                        $entry['malware_hits'] = $sig_hits;
                    }
                }

                $new_state[$rel_path] = $entry;

                $count++;
            }
            $completed = true;

        } catch (Exception $e) {
            return ['status' => 'error', 'message' => sanitize_text_field($e->getMessage())];
        }

        if ($completed) {
            return $this->finalize_scan($baseline, $new_state);
        }

        return ['status' => 'error', 'message' => esc_html__('Unknown scan interruption.', 'vgt-sentinel-ce')];
    }

    /**
     * VGT MALWARE SIGNATURE SCANNER
     * * Reads the file content once and matches it against the embedded signature
     * database. PCRE failures are logged but do not interrupt the scan.
     * * @param string $path Absolute file path
     * @return array Array of matched signature names (empty if clean)
     */
    private function scan_signatures(string $path): array {
        // Size guard — never scan oversized files (DoS prevention)
        $size = @filesize($path);
        if ($size === false || $size === 0 || $size > self::SIGNATURE_SCAN_MAX_FILESIZE) {
            return [];
        }
        
        // Read file content (memory-safe — already capped at 5MB)
        $content = @file_get_contents($path);
        if ($content === false || $content === '') {
            return [];
        }
        
        // PCRE limits — set high for signature matching, restored after loop
        $old_backtrack = ini_set('pcre.backtrack_limit', '1000000');
        $old_recursion = ini_set('pcre.recursion_limit', '1000000');
        
        $hits = [];
        foreach (self::MALWARE_SIGNATURES as $sig_name => $pattern) {
            $result = @preg_match($pattern, $content);
            if ($result === 1) {
                $hits[] = $sig_name;
            } elseif ($result === false) {
                // Fail-Closed: Aktive Scanner-Evasion blockieren (CWE-185)
                $hits[] = $sig_name . '_evasion_detected';
                error_log("[VGTS Scanner] PCRE failure/evasion on signature: $sig_name in $path");
            }
        }
        
        // Restore PCRE limits
        if ($old_backtrack !== false) ini_set('pcre.backtrack_limit', $old_backtrack);
        if ($old_recursion !== false) ini_set('pcre.recursion_limit', $old_recursion);
        
        return $hits;
    }

    private function finalize_scan(array $baseline, array $current_state): array {
        if (!$this->ensure_vault_exists()) {
             return [
                'status'  => 'error',
                'message' => esc_html__('CRITICAL: Vault Access Denied.', 'vgt-sentinel-ce')
            ];
        }

        $report = [];
        
        if (empty($baseline)) {
            // Initial baseline — but check for malware in the initial state
            $malware_findings = $this->extract_malware_findings($current_state);
            
            $this->save_manifest($current_state);
            
            if (!empty($malware_findings)) {
                $report = [
                    'status'    => 'critical',
                    'message'   => esc_html__('Initial manifest created — MALWARE DETECTED IN BASELINE!', 'vgt-sentinel-ce'),
                    'changes'   => $malware_findings,
                    'timestamp' => current_time('mysql')
                ];
            } else {
                $report = [
                    'status'    => 'init', 
                    'message'   => esc_html__('Initial manifest (Secure PHP Storage) created.', 'vgt-sentinel-ce'),
                    'changes'   => [],
                    'timestamp' => current_time('mysql')
                ];
            }
        } else {
            $diff = $this->compare_manifests($baseline, $current_state);
            
            if (empty($diff)) {
                $this->save_manifest($current_state); 
                $report = [
                    'status'    => 'clean',
                    'message'   => esc_html__('System integrity confirmed.', 'vgt-sentinel-ce'),
                    'changes'   => [],
                    'timestamp' => current_time('mysql')
                ];
            } else {
                // Determine severity — MALWARE findings escalate status to 'critical'
                $has_malware = false;
                foreach ($diff as $change) {
                    if ($change['type'] === 'MALWARE') {
                        $has_malware = true;
                        break;
                    }
                }
                
                $report = [
                    'status'    => $has_malware ? 'critical' : 'warning',
                    'message'   => $has_malware 
                        ? esc_html__('CRITICAL: Malware signatures detected!', 'vgt-sentinel-ce')
                        : esc_html__('Integrity violation detected!', 'vgt-sentinel-ce'),
                    'changes'   => $diff,
                    'timestamp' => current_time('mysql')
                ];
            }
        }

        update_option('vgts_scan_report', $report);
        return $report;
    }

    /**
     * Extract malware findings from current state for initial baseline reports.
     * Used when no baseline exists yet but signatures already match.
     */
    private function extract_malware_findings(array $state): array {
        $findings = [];
        foreach ($state as $path => $data) {
            if (!empty($data['malware_hits'])) {
                $findings[] = [
                    'type'  => 'MALWARE',
                    'file'  => $path,
                    'desc'  => sprintf(
                        /* translators: %s: comma-separated list of detected malware signatures */
                        esc_html__('Malware signatures matched: %s', 'vgt-sentinel-ce'),
                        implode(', ', $data['malware_hits'])
                    ),
                    'hits'  => $data['malware_hits']
                ];
            }
        }
        return $findings;
    }

    private function compare_manifests(array $old, array $new): array {
        $changes = [];
        
        foreach ($new as $path => $data) {
            $hash = $data['hash'];
            
            // PRIORITY 1: Malware detection (always reported, regardless of integrity status)
            if (!empty($data['malware_hits'])) {
                $changes[] = [
                    'type'  => 'MALWARE',
                    'file'  => $path,
                    'desc'  => sprintf(
                        /* translators: %s: comma-separated list of detected malware signatures */
                        esc_html__('Malware signatures matched: %s', 'vgt-sentinel-ce'),
                        implode(', ', $data['malware_hits'])
                    ),
                    'hits'  => $data['malware_hits']
                ];
                continue; // Don't double-report as NEW or MODIFIED
            }
            
            // PRIORITY 2: New file
            if (!isset($old[$path])) {
                $changes[] = [
                    'type' => 'NEW', 
                    'file' => $path, 
                    'desc' => esc_html__('New file detected', 'vgt-sentinel-ce')
                ];
            } 
            // PRIORITY 3: Modified file
            elseif ($old[$path]['hash'] !== $hash) {
                $changes[] = [
                    'type' => 'MODIFIED', 
                    'file' => $path, 
                    'desc' => esc_html__('Content modified (Hash mismatch)', 'vgt-sentinel-ce')
                ];
            }
        }
        
        // PRIORITY 4: Deleted files
        foreach ($old as $path => $data) {
            if (!isset($new[$path])) {
                $changes[] = [
                    'type' => 'DELETED', 
                    'file' => $path, 
                    'desc' => esc_html__('File removed', 'vgt-sentinel-ce')
                ];
            }
        }
        
        return $changes;
    }

    public function regenerate_baseline(): bool {
        if (!$this->ensure_vault_exists()) return false;

        if (function_exists('set_time_limit') && !in_array('set_time_limit', explode(',', ini_get('disable_functions')), true)) {
            set_time_limit(0);
        }
        if (function_exists('ignore_user_abort')) {
            ignore_user_abort(true);
        }

        $root      = wp_normalize_path(ABSPATH);
        $directory = new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator  = new RecursiveIteratorIterator($directory);
        
        $new_state = [];
        $malware_findings = [];

        try {
            foreach ($iterator as $file) {
                if ($file->isLink()) continue;

                $path = wp_normalize_path($file->getPathname());
                // MEF: Konsistente Path-Generierung auch im Baseline-Regenerator.
                $rel_path = ltrim(str_replace($root, '', $path), '/');

                foreach ($this->exclude_dirs as $ex) {
                    // MEF: Strict Matching auch hier anwenden.
                    if (str_starts_with($rel_path, $ex . '/')) continue 2; 
                }

                $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                if (!in_array($ext, $this->monitored_extensions, true)) continue;
                
                $entry = [
                    'hash'  => hash_file('sha256', $path),
                    'mtime' => $file->getMTime(),
                    'size'  => $file->getSize()
                ];
                
                // Full signature scan during baseline regeneration
                $sig_hits = $this->scan_signatures($path);
                if (!empty($sig_hits)) {
                    $entry['malware_hits'] = $sig_hits;
                    $malware_findings[] = [
                        'type'  => 'MALWARE',
                        'file'  => $rel_path,
                        'desc'  => sprintf(
                            /* translators: %s: comma-separated list of detected malware signatures */
                            esc_html__('Malware signatures matched: %s', 'vgt-sentinel-ce'),
                            implode(', ', $sig_hits)
                        ),
                        'hits'  => $sig_hits
                    ];
                }
                
                $new_state[$rel_path] = $entry;
            }
        } catch (Exception $e) {
            return false;
        }

        $this->save_manifest($new_state);
        
        // Report reflects malware findings even on manual re-index
        if (!empty($malware_findings)) {
            update_option('vgts_scan_report', [
                'status'    => 'critical',
                'message'   => esc_html__('System re-indexed — MALWARE DETECTED!', 'vgt-sentinel-ce'),
                'changes'   => $malware_findings,
                'timestamp' => current_time('mysql')
            ]);
        } else {
            update_option('vgts_scan_report', [
                'status'    => 'clean',
                'message'   => esc_html__('System manually verified (Re-Indexed).', 'vgt-sentinel-ce'),
                'changes'   => [],
                'timestamp' => current_time('mysql')
            ]);
        }

        return true;
    }

    // --- HARDENED STORAGE LOGIC ---

    private function load_manifest(): array {
        if (file_exists($this->manifest_file)) {
            // SECURITY: Include statt file_get_contents. 
            // Die Datei muss validen PHP Code enthalten: <?php return [...];
            $data = include $this->manifest_file;
            return is_array($data) ? $data : [];
        }
        return [];
    }

    private function save_manifest(array $data): void {
        // ATOMIC WRITE OPERATION mit Locking
        // Wir speichern es als executable PHP, das das Array returned.
        // Das verhindert Direct-Access-Leaks via Browser.
        $content = "<?php defined('ABSPATH') || exit; return " . var_export($data, true) . ";";
        
        $temp_file = $this->manifest_file . '_tmp.php';
        
        if (file_put_contents($temp_file, $content, LOCK_EX) !== false) {
            rename($temp_file, $this->manifest_file);
            // Optional: OpCache invalidieren, damit Änderungen sofort greifen
            if (function_exists('opcache_invalidate') && file_exists($this->manifest_file)) {
                opcache_invalidate($this->manifest_file, true);
            }
        }
    }

    private function ensure_vault_exists(): bool {
        $dir = dirname($this->manifest_file);
        if (!file_exists($dir)) {
            // [WP.ORG COMPLIANCE]: Use wp_mkdir_p instead of @mkdir
            if (!wp_mkdir_p($dir)) {
                return false;
            }
            // Fallback Security (trotz PHP Storage)
            file_put_contents($dir . '/index.php', '<?php // SILENCE IS GOLDEN ?>');
            file_put_contents($dir . '/.htaccess', "Order Deny,Allow\nDeny from all");
        }
        return is_writable($dir);
    }
}
