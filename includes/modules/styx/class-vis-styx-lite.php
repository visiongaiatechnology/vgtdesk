<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * MODULE: STYX LITE (Outbound Telemetry Control)
 * STATUS: PLATIN VGT STATUS (WP.ORG COMPLIANT)
 * Logic: WP telemetry phantom responses plus optional local allow/deny egress policy.
 */
class VGTS_Styx_Lite {

    private bool $kill_telemetry;
    private array $deny_domains = [];
    private array $allow_domains = [];

    public function __construct(array $options) {
        $this->kill_telemetry = !empty($options['styx_kill_telemetry']);
        $this->deny_domains = $this->parse_domain_list((string)($options['styx_deny_domains'] ?? ''));
        $this->allow_domains = $this->parse_domain_list((string)($options['styx_allow_domains'] ?? ''));

        if ($this->kill_telemetry || !empty($this->deny_domains) || !empty($this->allow_domains)) {
            add_filter('pre_http_request', [$this, 'intercept_outbound_traffic'], 999, 3);
        }
    }

    public function intercept_outbound_traffic($preempt, array $parsed_args, string $url) {
        $host = parse_url($url, PHP_URL_HOST);
        if (!is_string($host)) {
            return $preempt;
        }
        $host = strtolower($host);

        if (!empty($this->allow_domains) && !$this->domain_matches($host, $this->allow_domains)) {
            return new WP_Error('vgts_styx_denied', esc_html__('STYX LITE: Outbound host not in allowlist.', 'vgt-sentinel-ce'));
        }

        if ($this->domain_matches($host, $this->deny_domains)) {
            return new WP_Error('vgts_styx_denied', esc_html__('STYX LITE: Outbound host blocked by policy.', 'vgt-sentinel-ce'));
        }

        $blocked_domains = [
            'api.wordpress.org',
            'downloads.wordpress.org',
            's.w.org',
        ];

        if ($this->kill_telemetry && in_array($host, $blocked_domains, true)) {
            $mock_data = [
                'plugins'      => [],
                'themes'       => [],
                'translations' => [],
                'update'       => [],
                'no_update'    => [],
                'offers'       => [],
            ];

            try {
                $mock_body = json_encode($mock_data, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                $mock_body = '{}';
            }

            return [
                'headers'  => [],
                'body'     => $mock_body,
                'response' => [
                    'code'    => 200,
                    'message' => 'OK',
                ],
                'cookies'  => [],
                'filename' => null,
            ];
        }

        return $preempt;
    }

    private function parse_domain_list(string $raw): array {
        $domains = [];
        foreach (preg_split('/\r\n|\r|\n|,/', $raw) ?: [] as $domain) {
            $domain = strtolower(trim($domain));
            if ($domain !== '' && preg_match('/^(?:\*\.)?[a-z0-9.-]+$/', $domain) === 1) {
                $domains[] = $domain;
            }
        }
        return array_values(array_unique($domains));
    }

    private function domain_matches(string $host, array $domains): bool {
        foreach ($domains as $domain) {
            if (strpos($domain, '*.') === 0) {
                $suffix = substr($domain, 1);
                if (substr($host, -strlen($suffix)) === $suffix) {
                    return true;
                }
                continue;
            }
            if (hash_equals($host, $domain)) {
                return true;
            }
        }
        return false;
    }
}
