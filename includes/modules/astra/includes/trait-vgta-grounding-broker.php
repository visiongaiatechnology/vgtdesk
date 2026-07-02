<?php

declare(strict_types=1);

// STATUS: DIAMANT VGT SUPREME

namespace VGTAstra\AgentSystem;

if (!defined('ABSPATH')) {
    exit;
}

trait GroundingBrokerTrait
{
    private function sanitizeGroundingMode(string $mode): string
    {
        $clean = \strtolower(\sanitize_key($mode));
        return \in_array($clean, ['off', 'cited', 'research', 'strict_allowlist'], true) ? $clean : 'off';
    }

    /**
     * @return array<string,mixed>
     */
    private function buildGroundingPack(string $query, string $mode, int $maxSources, string $allowedDomains): array
    {
        $mode = $this->sanitizeGroundingMode($mode);
        if ($mode === 'off') {
            return [];
        }

        $urls = \array_slice($this->extractGroundingUrls($query), 0, \min(self::MAX_GROUNDING_SOURCES, \max(1, $maxSources)));
        $allowlist = $this->sanitizeDomainAllowlist($allowedDomains);
        $sources = [];
        foreach ($urls as $url) {
            try {
                $source = $this->fetchGroundingSource($url, $mode, $allowlist);
                if ($source !== []) {
                    $sources[] = $source + ['id' => 'src_' . (string) (\count($sources) + 1)];
                }
            } catch (\Throwable $e) {
                $this->logInternalThrowable('GROUNDING', $this->buildOpaqueErrorCode($e), $e);
            }
        }

        $pack = [
            'query' => $this->sanitizeBoundedText($this->redactGroundingSecrets($query), 1200),
            'created_at' => \gmdate('c'),
            'mode' => $mode,
            'sources' => $sources,
        ];

        $json = \json_encode($pack, \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_INVALID_UTF8_SUBSTITUTE);
        if (\is_string($json) && \strlen($json) > self::MAX_GROUNDING_PACK_BYTES) {
            $pack['sources'] = \array_slice($sources, 0, 2);
        }

        return $pack;
    }

    /**
     * @return list<string>
     */
    private function extractGroundingUrls(string $query): array
    {
        if (\preg_match_all('/https?:\/\/[^\s<>"\']+/i', $query, $matches) !== 1) {
            return [];
        }

        $urls = [];
        foreach ($matches[0] as $url) {
            $clean = \esc_url_raw($url);
            if ($clean !== '') {
                $urls[$clean] = true;
            }
        }

        return \array_keys($urls);
    }

    /**
     * @param list<string> $allowlist
     * @return array<string,mixed>
     */
    private function fetchGroundingSource(string $url, string $mode, array $allowlist): array
    {
        $normalizedUrl = $this->validateGroundingUrl($url, $mode, $allowlist);
        $cached = $this->getCachedGroundingSource($normalizedUrl, $mode);
        if ($cached !== []) {
            return $cached;
        }

        $response = \wp_safe_remote_get($normalizedUrl, [
            'timeout' => 8,
            'redirection' => 2,
            'limit_response_size' => 250000,
            'reject_unsafe_urls' => true,
            'headers' => [
                'User-Agent' => 'VGTAstra-GroundingBroker/1.0; WordPress',
                'Accept' => 'text/html, text/plain, application/json;q=0.7',
            ],
        ]);

        if (\is_wp_error($response)) {
            throw new StorageException('Grounding HTTP request failed.');
        }

        $status = (int) \wp_remote_retrieve_response_code($response);
        $contentType = \strtolower((string) \wp_remote_retrieve_header($response, 'content-type'));
        $body = (string) \wp_remote_retrieve_body($response);
        if ($status < 200 || $status >= 300 || $body === '') {
            throw new ValidationException('Grounding source unavailable.');
        }

        $source = $this->sanitizeGroundingSource($normalizedUrl, $status, $contentType, $body);
        $this->cacheGroundingSource($normalizedUrl, $mode, $source);
        return $source;
    }

    /**
     * @param list<string> $allowlist
     */
    private function validateGroundingUrl(string $url, string $mode, array $allowlist): string
    {
        $normalized = \esc_url_raw($url, ['http', 'https']);
        $parts = \wp_parse_url($normalized);
        if (!\is_array($parts) || !isset($parts['scheme'], $parts['host'])) {
            $this->throwTypedException('Grounding URL validation failed.', 'security');
        }

        $scheme = \strtolower((string) $parts['scheme']);
        $host = \strtolower((string) $parts['host']);
        $path = isset($parts['path']) ? \strtolower((string) $parts['path']) : '';
        if (!\in_array($scheme, ['http', 'https'], true) || $this->isBlockedGroundingHost($host) || \str_contains($path, 'wp-config.php') || \str_contains($path, '/wp-admin/')) {
            $this->throwTypedException('Grounding URL validation failed.', 'security');
        }

        if ($mode === 'strict_allowlist' && !\in_array($host, $allowlist, true)) {
            $this->throwTypedException('Grounding origin validation failed.', 'security');
        }

        return $normalized;
    }

    private function isBlockedGroundingHost(string $host): bool
    {
        if ($host === 'localhost' || \str_ends_with($host, '.local')) {
            return true;
        }

        $ips = @\gethostbynamel($host);
        if (!\is_array($ips) || $ips === []) {
            return false;
        }

        foreach ($ips as $ip) {
            if (\filter_var($ip, \FILTER_VALIDATE_IP, \FILTER_FLAG_NO_PRIV_RANGE | \FILTER_FLAG_NO_RES_RANGE) === false) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string,mixed>
     */
    private function sanitizeGroundingSource(string $url, int $status, string $contentType, string $body): array
    {
        $body = \substr($body, 0, self::MAX_GROUNDING_SOURCE_BYTES);
        $body = \preg_replace('/<!--[\s\S]*?-->/', ' ', $body) ?? '';
        $body = \preg_replace('/<(script|style|iframe|object|embed|noscript|form)\b[\s\S]*?<\/\1>/i', ' ', $body) ?? '';
        $body = \preg_replace('/data:[a-z0-9\/+\-.]+;base64,[A-Za-z0-9+\/=]{80,}/i', ' ', $body) ?? '';
        $title = '';
        if (\preg_match('/<title[^>]*>([\s\S]*?)<\/title>/i', $body, $match) === 1) {
            $title = $this->sanitizeBoundedText(\wp_strip_all_tags((string) $match[1]), 180);
        }

        $text = \wp_strip_all_tags($body, true);
        $text = \preg_replace('/\s+/', ' ', (string) $text) ?? '';
        $excerpt = \substr($this->sanitizeBoundedText($text, self::MAX_GROUNDING_SOURCE_BYTES), 0, 6000);
        $domain = (string) \wp_parse_url($url, \PHP_URL_HOST);

        return [
            'url' => $url,
            'domain' => $domain,
            'title' => $title !== '' ? $title : $domain,
            'status' => $status,
            'content_type' => \substr(\sanitize_text_field($contentType), 0, 120),
            'retrieved_at' => \gmdate('c'),
            'excerpt' => $excerpt,
            'hash' => \hash('sha256', $excerpt),
        ];
    }

    /**
     * @return list<string>
     */
    private function sanitizeDomainAllowlist(string $domains): array
    {
        $safe = [];
        foreach (\preg_split('/[\s,]+/', $domains) ?: [] as $domain) {
            $clean = \strtolower(\sanitize_text_field($domain));
            if (\preg_match('/\A[a-z0-9.-]+\.[a-z]{2,}\z/', $clean) === 1) {
                $safe[$clean] = true;
            }
        }

        return \array_keys($safe);
    }

    private function formatGroundingPackForPrompt(array $pack): string
    {
        if ($pack === [] || empty($pack['sources'])) {
            return '';
        }

        $json = \json_encode($pack, \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_INVALID_UTF8_SUBSTITUTE);
        return \is_string($json) ? "GROUNDING CITATION PACK:\n" . $json . "\n" : '';
    }

    private function redactGroundingSecrets(string $query): string
    {
        return (string) \preg_replace('/(gsk_[A-Za-z0-9_\-]{12,}|sk-[A-Za-z0-9_\-]{12,}|nonce[=:]\S+|api[_-]?key[=:]\S+)/i', '[VGTA_REDACTED]', $query);
    }

    private function getGroundingCacheFile(string $url, string $mode): string
    {
        $root = $this->ensureWorkspaceDirectory($this->getSecureWorkspaceRoot(), self::GROUNDING_DIR_NAME);
        $file = $root . \DIRECTORY_SEPARATOR . \hash_hmac('sha256', $url . '|' . $mode, self::GROUNDING_CONTEXT) . '.json';
        if (!\str_starts_with($file, $root . \DIRECTORY_SEPARATOR)) {
            $this->throwTypedException('Path escaped jail.', 'security');
        }

        return $file;
    }

    /**
     * @return array<string,mixed>
     */
    private function getCachedGroundingSource(string $url, string $mode): array
    {
        $file = $this->getGroundingCacheFile($url, $mode);
        if (!\is_file($file) || \time() - (int) \filemtime($file) > self::GROUNDING_CACHE_TTL) {
            return [];
        }

        try {
            $decoded = \json_decode((string) \file_get_contents($file), true, 32, \JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            return [];
        }

        return \is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<string,mixed> $source
     */
    private function cacheGroundingSource(string $url, string $mode, array $source): void
    {
        try {
            $json = \json_encode($source, \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_INVALID_UTF8_SUBSTITUTE);
            $file = $this->getGroundingCacheFile($url, $mode);
            @\file_put_contents($file, $json, \LOCK_EX);
            @\chmod($file, 0600);
        } catch (\Throwable $e) {
            $this->logInternalThrowable('GROUNDING', $this->buildOpaqueErrorCode($e), $e);
        }
    }
}
