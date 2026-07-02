<?php

declare(strict_types=1);

// STATUS: DIAMANT VGT SUPREME

namespace VGTAstra\AgentSystem;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * KERNEL: VGTASTRA CRYPTO VAULT
 */
final class CryptoVault
{
    private const ENCRYPTION_METHOD = 'aes-256-gcm';
    private const GCM_TAG_LENGTH = 16;
    private const HMAC_LENGTH = 32;
    private const VERSION_BYTE = "\x01";
    private const OPTION_SYSTEM_SALT = 'vgta_vault_system_salt';
    private const MASTER_INFO = 'vgta_agent_system_master_domain_v1';
    private const HKDF_SALT = 'vgta_hkdf_salt_binding_v1';

    private static function getMasterMaterial(): string
    {
        $salts = '';
        $keysToCheck = [
            'SECURE_AUTH_KEY',
            'AUTH_KEY',
            'LOGGED_IN_KEY',
            'NONCE_KEY',
            'SECURE_AUTH_SALT',
            'AUTH_SALT',
            'LOGGED_IN_SALT',
            'NONCE_SALT',
        ];

        foreach ($keysToCheck as $const) {
            if (\defined($const)) {
                $value = (string) \constant($const);
                if ($value !== '') {
                    $salts .= $value;
                }
            }
        }

        if ($salts !== '') {
            return $salts;
        }

        $stored = \get_option(self::OPTION_SYSTEM_SALT, '');
        if (\is_string($stored) && \preg_match('/\A[a-f0-9]{64}\z/i', $stored) === 1) {
            return $stored;
        }

        try {
            $generated = \bin2hex(\random_bytes(32));
        } catch (\Throwable $e) {
            throw new SecurityException('Cryptographic random source unavailable.');
        }

        if (\update_option(self::OPTION_SYSTEM_SALT, $generated, false) === false) {
            $current = \get_option(self::OPTION_SYSTEM_SALT, '');
            if ($current !== $generated) {
                throw new StorageException('Vault salt persistence failed.');
            }
        }

        return $generated;
    }

    private static function deriveKey(string $purpose): string
    {
        $key = \hash_hkdf(
            'sha256',
            self::getMasterMaterial(),
            32,
            self::MASTER_INFO . ':' . $purpose,
            self::HKDF_SALT
        );

        if (!\is_string($key) || \strlen($key) !== 32) {
            throw new SecurityException('Vault key derivation failed.');
        }

        return $key;
    }

    private static function normalizeContext(string $contextId): string
    {
        if (\preg_match('/\A[a-z0-9:_\-\.]{8,160}\z/i', $contextId) !== 1) {
            throw new SecurityException('Vault context binding rejected.');
        }

        return 'vgta:' . $contextId . ':' . \home_url();
    }

    /**
     * Pre-Flight Payload Verification
     * Validates structural alignment and properties without invoking openSSL decrypt engines.
     */
    public static function isValidPayload(string $payload): bool
    {
        if ($payload === '') {
            return false;
        }

        $data = \base64_decode($payload, true);
        if ($data === false) {
            return false;
        }

        $ivLength = \openssl_cipher_iv_length(self::ENCRYPTION_METHOD);
        if ($ivLength === false || $ivLength < 12) {
            return false;
        }

        $minimumLength = 1 + $ivLength + self::GCM_TAG_LENGTH + self::HMAC_LENGTH + 1;
        if (\strlen($data) < $minimumLength || $data[0] !== self::VERSION_BYTE) {
            return false;
        }

        return true;
    }

    public static function encrypt(string $plaintext, string $contextId): string
    {
        if ($plaintext === '') {
            return '';
        }

        $ivLength = \openssl_cipher_iv_length(self::ENCRYPTION_METHOD);
        if ($ivLength === false || $ivLength < 12) {
            throw new SecurityException('Invalid vault IV length.');
        }

        try {
            $iv = \random_bytes($ivLength);
        } catch (\Throwable $e) {
            throw new SecurityException('Cryptographic random source unavailable.');
        }

        $tag = '';
        $aad = self::normalizeContext($contextId);
        $ciphertext = \openssl_encrypt(
            $plaintext,
            self::ENCRYPTION_METHOD,
            self::deriveKey('encryption'),
            \OPENSSL_RAW_DATA,
            $iv,
            $tag,
            $aad,
            self::GCM_TAG_LENGTH
        );

        if ($ciphertext === false || \strlen($tag) !== self::GCM_TAG_LENGTH) {
            throw new SecurityException('Vault encryption failed.');
        }

        $macInput = self::VERSION_BYTE . $aad . $iv . $tag . $ciphertext;
        $mac = \hash_hmac('sha256', $macInput, self::deriveKey('authentication'), true);

        return \base64_encode(self::VERSION_BYTE . $iv . $tag . $mac . $ciphertext);
    }

    public static function decrypt(string $payload, string $contextId): string
    {
        if ($payload === '') {
            return '';
        }

        $data = \base64_decode($payload, true);
        if ($data === false) {
            throw new SecurityException('Vault payload decoding rejected.');
        }

        $ivLength = \openssl_cipher_iv_length(self::ENCRYPTION_METHOD);
        if ($ivLength === false || $ivLength < 12) {
            throw new SecurityException('Invalid vault IV length.');
        }

        $minimumLength = 1 + $ivLength + self::GCM_TAG_LENGTH + self::HMAC_LENGTH + 1;
        if (\strlen($data) < $minimumLength || $data[0] !== self::VERSION_BYTE) {
            throw new SecurityException('Vault payload structure rejected.');
        }

        $offset = 1;
        $iv = \substr($data, $offset, $ivLength);
        $offset += $ivLength;
        $tag = \substr($data, $offset, self::GCM_TAG_LENGTH);
        $offset += self::GCM_TAG_LENGTH;
        $mac = \substr($data, $offset, self::HMAC_LENGTH);
        $offset += self::HMAC_LENGTH;
        $ciphertext = \substr($data, $offset);

        $aad = self::normalizeContext($contextId);
        $expectedMac = \hash_hmac(
            'sha256',
            self::VERSION_BYTE . $aad . $iv . $tag . $ciphertext,
            self::deriveKey('authentication'),
            true
        );

        if (!\hash_equals($expectedMac, $mac)) {
            throw new SecurityException('Vault authentication rejected.');
        }

        $decrypted = \openssl_decrypt(
            $ciphertext,
            self::ENCRYPTION_METHOD,
            self::deriveKey('encryption'),
            \OPENSSL_RAW_DATA,
            $iv,
            $tag,
            $aad
        );

        if ($decrypted === false) {
            throw new SecurityException('Vault decryption rejected.');
        }

        return $decrypted;
    }
}