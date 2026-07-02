<?php
declare(strict_types=1);
if (!defined('ABSPATH')) {
    exit;
}

/**
 * MODULE: AIRLOCK (CE HARDENED)
 * Status: PLATIN STATUS / Strict MIME, SVG, archive and payload inspection.
 */
class VGTS_Airlock {

    private bool $enabled = false;
    private int $max_memory_scan = 2097152;
    private int $max_upload_bytes = 26214400;

    private array $allowed_mimes = [
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png'  => ['image/png'],
        'gif'  => ['image/gif'],
        'webp' => ['image/webp'],
        'svg'  => ['image/svg+xml', 'text/plain'],
        'ico'  => ['image/x-icon', 'image/vnd.microsoft.icon'],
        'avif' => ['image/avif'],
        'pdf'  => ['application/pdf'],
        'doc'  => ['application/msword', 'application/octet-stream'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
        'xls'  => ['application/vnd.ms-excel', 'application/octet-stream'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
        'ppt'  => ['application/vnd.ms-powerpoint', 'application/octet-stream'],
        'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip'],
        'txt'  => ['text/plain'],
        'csv'  => ['text/plain', 'text/csv', 'application/csv'],
        'rtf'  => ['application/rtf', 'text/rtf'],
        'mp3'  => ['audio/mpeg'],
        'mp4'  => ['video/mp4'],
        'mov'  => ['video/quicktime'],
        'wav'  => ['audio/wav', 'audio/x-wav'],
        'avi'  => ['video/x-msvideo'],
        'zip'  => ['application/zip', 'application/x-zip-compressed'],
        'rar'  => ['application/vnd.rar', 'application/x-rar-compressed'],
    ];

    public function __construct(?array $options = null) {
        if ($options === null) {
            $options = get_option('vgts_config', []);
        }

        $this->enabled = !empty($options['airlock_enabled']);
        $this->max_upload_bytes = max(1048576, (int)($options['airlock_max_upload_bytes'] ?? $this->max_upload_bytes));

        if ($this->enabled) {
            add_filter('wp_handle_upload_prefilter', [$this, 'inspect_payload'], 10, 1);
        }
    }

    public function inspect_payload(array $file): array {
        if (empty($file['name']) || empty($file['tmp_name']) || !is_string($file['tmp_name'])) {
            return $file;
        }

        $tmp = $file['tmp_name'];
        if (!is_uploaded_file($tmp) || !is_readable($tmp)) {
            $file['error'] = 'APEX AIRLOCK: Upload origin validation failed.';
            return $file;
        }

        $real_size = filesize($tmp);
        if ($real_size === false || $real_size === 0 || $real_size > $this->max_upload_bytes) {
            $file['error'] = 'APEX AIRLOCK: Size boundary violation.';
            return $file;
        }

        $safe_name = sanitize_file_name((string)$file['name']);
        $ext = strtolower(pathinfo($safe_name, PATHINFO_EXTENSION));
        if (!isset($this->allowed_mimes[$ext])) {
            $file['error'] = 'APEX AIRLOCK: File extension rejected by zero-trust policy.';
            return $file;
        }

        $detected_mime = $this->detect_mime($tmp);
        if ($detected_mime === '' || !in_array($detected_mime, $this->allowed_mimes[$ext], true)) {
            $file['error'] = 'APEX AIRLOCK: MIME/type mismatch. Upload rejected.';
            return $file;
        }

        if (!$this->validate_typed_payload($tmp, $ext, $detected_mime)) {
            $this->log_threat($safe_name);
            $file['error'] = 'APEX AIRLOCK: Malicious or inconsistent payload rejected.';
            return $file;
        }

        return $file;
    }

    private function detect_mime(string $tmp): string {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $mime = finfo_file($finfo, $tmp);
                finfo_close($finfo);
                return is_string($mime) ? strtolower($mime) : '';
            }
        }
        return '';
    }

    private function validate_typed_payload(string $tmp, string $ext, string $mime): bool {
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            return $this->validate_image_payload($tmp, $mime);
        }
        if ($ext === 'svg') {
            return $this->validate_svg_payload($tmp);
        }
        if (in_array($ext, ['zip', 'docx', 'xlsx', 'pptx'], true)) {
            return $this->validate_zip_payload($tmp);
        }
        return !$this->payload_contains_code($tmp);
    }

    private function validate_image_payload(string $tmp, string $mime): bool {
        $image_info = @getimagesize($tmp);
        if (!is_array($image_info) || empty($image_info[2])) {
            return false;
        }

        $type_map = [
            'image/jpeg' => IMAGETYPE_JPEG,
            'image/png'  => IMAGETYPE_PNG,
            'image/webp' => defined('IMAGETYPE_WEBP') ? IMAGETYPE_WEBP : 18,
            'image/gif'  => IMAGETYPE_GIF,
        ];
        $expected_type = $type_map[$mime] ?? 0;
        if ($expected_type === 0 || $image_info[2] !== $expected_type) {
            return false;
        }

        $width = (int)($image_info[0] ?? 0);
        $height = (int)($image_info[1] ?? 0);
        $required = (int)(($width * $height * 4 * 1.5) + 10485760);
        $limit = wp_convert_hr_to_bytes((string)ini_get('memory_limit'));
        if ($width <= 0 || $height <= 0 || ($limit > 0 && $required > ($limit - memory_get_usage(true)))) {
            return false;
        }

        return !$this->payload_contains_code($tmp);
    }

    private function validate_svg_payload(string $tmp): bool {
        $content = $this->read_limited($tmp, 262144);
        if ($content === '') {
            return false;
        }
        $blocked = '/<\s*script\b|on[a-z]{3,}\s*=|javascript\s*:|data\s*:\s*text\/html|<\s*foreignObject\b|<\s*(?:iframe|object|embed)\b|xlink:href\s*=\s*["\']\s*(?:https?:|data:)/i';
        return preg_match($blocked, $content) !== 1 && !$this->payload_string_contains_code($content);
    }

    private function validate_zip_payload(string $tmp): bool {
        if (!class_exists('ZipArchive')) {
            return !$this->payload_contains_code($tmp);
        }
        $zip = new ZipArchive();
        if ($zip->open($tmp) !== true) {
            return false;
        }
        $blocked_ext = ['php', 'phtml', 'phar', 'cgi', 'pl', 'py', 'jsp', 'asp', 'aspx', 'sh', 'bat', 'cmd', 'exe', 'dll'];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string)$zip->getNameIndex($i);
            $normalized = str_replace('\\', '/', $name);
            if (strpos($normalized, '../') !== false || strpos($normalized, '/') === 0) {
                $zip->close();
                return false;
            }
            $ext = strtolower(pathinfo($normalized, PATHINFO_EXTENSION));
            if (in_array($ext, $blocked_ext, true)) {
                $zip->close();
                return false;
            }
        }
        $zip->close();
        return !$this->payload_contains_code($tmp);
    }

    private function payload_contains_code(string $path): bool {
        $content = $this->read_boundary_chunks($path);
        return $this->payload_string_contains_code($content);
    }

    private function payload_string_contains_code(string $content): bool {
        if ($content === '') {
            return false;
        }
        $patterns = [
            '/<\?php/i',
            '/<\?=/i',
            '/<script\s+language\s*=\s*["\']php["\']/i',
            '/<\%/i',
            '/base64_decode\s*\(/i',
            '/eval\s*\(/i',
            '/(?:system|shell_exec|passthru|proc_open)\s*\(/i',
        ];
        foreach ($patterns as $regex) {
            if (preg_match($regex, $content) === 1) {
                return true;
            }
        }
        return false;
    }

    private function read_limited(string $filepath, int $bytes): string {
        $handle = @fopen($filepath, 'rb');
        if (!$handle) {
            return '';
        }
        $content = fread($handle, $bytes) ?: '';
        fclose($handle);
        return $content;
    }

    private function read_boundary_chunks(string $filepath): string {
        if (!is_readable($filepath)) {
            return '';
        }
        $handle = @fopen($filepath, 'rb');
        if (!$handle) return '';
        $header = fread($handle, $this->max_memory_scan) ?: '';
        $size = filesize($filepath);
        $footer = '';
        if ($size !== false && $size > $this->max_memory_scan) {
            fseek($handle, -min(65536, $size), SEEK_END);
            $footer = fread($handle, 65536) ?: '';
        }
        fclose($handle);
        return $header . $footer;
    }

    private function log_threat(string $filename): void {
        $safe_filename = sanitize_file_name($filename);
        $safe_ip = class_exists('VGTS_Network') ? VGTS_Network::resolve_true_ip() : sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'));
        error_log('[VISIONGAIA AIRLOCK] Neutralized Threat: ' . $safe_filename . ' from ' . $safe_ip);

        global $wpdb;
        if (!isset($wpdb)) return;

        $table = $wpdb->prefix . (defined('VGTS_TABLE_LOGS') ? VGTS_TABLE_LOGS : 'vgts_omega_logs');
        $query = $wpdb->prepare("SHOW TABLES LIKE %s", $wpdb->esc_like($table));
        if ($wpdb->get_var($query) === $table) {
            $wpdb->insert(
                $table,
                [
                    'module'   => 'AIRLOCK',
                    'type'     => 'BLOCK',
                    'message'  => 'Malicious Payload in file: ' . $safe_filename,
                    'ip'       => $safe_ip,
                    'severity' => 10,
                ],
                ['%s', '%s', '%s', '%s', '%d']
            );
        }
    }
}
