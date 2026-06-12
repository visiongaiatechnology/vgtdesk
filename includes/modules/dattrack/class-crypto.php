<?php
/**
 * VGT Dattrack: Sovereign Analytics Crypto Module
 * STATUS: 💠 DIAMANT VGT SUPREME
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('VGT_Crypto')) {
final class VGT_Crypto {
    private const CIPHER_ALGO = 'aes-256-gcm';

    public static function init_vault(): void {
        $upload_dir = wp_upload_dir();
        $vault_dir = $upload_dir['basedir'] . '/.vgt-keys';
        
        if (!is_dir($vault_dir) && !@wp_mkdir_p($vault_dir)) {
            return;
        }

        $security_files = [
            '.htaccess' => "Order deny,allow\nDeny from all",
            'index.php' => "<?php\n// VGT Aegis Lock\nexit;",
            'safe.conf' => "location ~ /\.vgt-keys {\n    deny all;\n    return 404;\n}"
        ];

        foreach ($security_files as $filename => $content) {
            $filepath = $vault_dir . '/' . $filename;
            if (!file_exists($filepath)) {
                @file_put_contents($filepath, $content);
            }
        }

        $key_file = $vault_dir . '/master.php';
        if (!file_exists($key_file)) {
            $key = base64_encode(random_bytes(32));
            @file_put_contents($key_file, "<?php\ndefined('ABSPATH') || exit;\nreturn '$key';");
        }

        $salt_file = $vault_dir . '/salt.php';
        if (!file_exists($salt_file)) {
            $salt = base64_encode(random_bytes(32));
            @file_put_contents($salt_file, "<?php\ndefined('ABSPATH') || exit;\nreturn '$salt';");
        }
    }

    public static function execute_aegis_protocol(): void {
        $upload_dir = wp_upload_dir();
        $vault_dir = $upload_dir['basedir'] . '/.vgt-keys';
        
        $key_file = $vault_dir . '/master.php';
        $salt_file = $vault_dir . '/salt.php';

        if (!is_writable($vault_dir) || !file_exists($key_file)) {
            return;
        }

        if (class_exists('VGT_Aggregator')) {
            VGT_Aggregator::run_rollup();
        }

        $new_key = base64_encode(random_bytes(32));
        $new_salt = base64_encode(random_bytes(32));

        $key_written = @file_put_contents($key_file, "<?php\ndefined('ABSPATH') || exit;\nreturn '$new_key';");
        $salt_written = @file_put_contents($salt_file, "<?php\ndefined('ABSPATH') || exit;\nreturn '$new_salt';");

        if ($key_written && $salt_written) {
            if (function_exists('opcache_invalidate')) {
                @opcache_invalidate($key_file, true);
                @opcache_invalidate($salt_file, true);
            }

            global $wpdb;
            $vault_table = $wpdb->prefix . 'vgt_dattrack_vault';
            $wpdb->query("TRUNCATE TABLE {$vault_table}");
        }
    }

    public static function get_master_key(): string {
        if (defined('VGT_DATTRACK_MASTER_KEY')) {
            return base64_decode(VGT_DATTRACK_MASTER_KEY);
        }
        
        $upload_dir = wp_upload_dir();
        $key_file = $upload_dir['basedir'] . '/.vgt-keys/master.php';
        
        if (file_exists($key_file)) {
            $key = include $key_file;
            return base64_decode($key);
        }
        
        return '';
    }

    public static function get_dynamic_salt(): string {
        $upload_dir = wp_upload_dir();
        $salt_file = $upload_dir['basedir'] . '/.vgt-keys/salt.php';
        
        if (file_exists($salt_file)) {
            $salt = include $salt_file;
            return base64_decode($salt);
        }
        
        self::init_vault();
        if (file_exists($salt_file)) {
            $salt = include $salt_file;
            return base64_decode($salt);
        }

        return '';
    }

    public static function encrypt_payload(array $data, string $ip_hash): array {
        $key = self::get_master_key();
        
        if (empty($key)) {
            self::init_vault();
            $key = self::get_master_key();
            if(empty($key)) return ['payload' => '', 'iv' => '', 'tag' => ''];
        }

        $salt = self::get_dynamic_salt();
        if(empty($salt)) return ['payload' => '', 'iv' => '', 'tag' => ''];

        $iv_length = openssl_cipher_iv_length(self::CIPHER_ALGO) ?: 12;
        $iv = random_bytes($iv_length);
        $tag = '';
        
        $aad = hash('sha256', $ip_hash . $salt);
        $json_payload = wp_json_encode($data);
        $ciphertext = openssl_encrypt($json_payload, self::CIPHER_ALGO, $key, OPENSSL_RAW_DATA, $iv, $tag, $aad);
        
        if ($ciphertext === false) {
            return ['payload' => '', 'iv' => '', 'tag' => ''];
        }

        return [
            'payload' => base64_encode($ciphertext),
            'iv'      => base64_encode($iv),
            'tag'     => base64_encode($tag)
        ];
    }

    public static function decrypt_payload(string $encrypted_payload, string $iv_base64, string $tag_base64, string $ip_hash): ?array {
        $key = self::get_master_key();
        $salt = self::get_dynamic_salt();
        if(empty($key) || empty($salt)) return null;

        $iv = base64_decode($iv_base64);
        $tag = base64_decode($tag_base64);
        
        $aad = hash('sha256', $ip_hash . $salt);

        $decrypted = openssl_decrypt(
            base64_decode($encrypted_payload), 
            self::CIPHER_ALGO, 
            $key, 
            OPENSSL_RAW_DATA, 
            $iv, 
            $tag, 
            $aad
        );

        if ($decrypted === false) {
            return null;
        }

        return json_decode($decrypted, true);
    }
}
}