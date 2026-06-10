<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * MODULE: STYX LITE (Outbound Telemetry Control)
 * STATUS: DIAMANT VGT SUPREME (WP.ORG COMPLIANT)
 * Logic: Kappt native WordPress-Telemetrie auf Netzwerkebene durch struktur-perfekte Phantom-Responses.
 * Fix: Eliminierung von Core-Warnings und Fatal Errors (array_walk) durch exaktes Payload-Matching.
 */
class VGTS_Styx_Lite {

    /**
     * @var bool Status der Telemetrie-Unterdrückung
     */
    private bool $kill_telemetry;

    /**
     * @param array $options Zentrale VGT Konfigurations-Matrix
     */
    public function __construct(array $options) {
        $this->kill_telemetry = !empty($options['styx_kill_telemetry']);

        if ($this->kill_telemetry) {
            // Priorität 999: STYX hat das absolute letzte Wort vor dem tatsächlichen Netzwerk-Call.
            add_filter('pre_http_request', [$this, 'intercept_outbound_traffic'], 999, 3);
        }
    }

    /**
     * Interzeptiert ausgehenden Traffic und injiziert Phantom-Daten für WP-Core Domains.
     * * @param false|array|WP_Error $preempt Ob der Request vorzeitig abgebrochen werden soll.
     * @param array                $parsed_args Die Argumente des Requests.
     * @param string               $url Die Ziel-URL.
     * @return array|bool|WP_Error
     */
    public function intercept_outbound_traffic($preempt, array $parsed_args, string $url) {
        $host = parse_url($url, PHP_URL_HOST);
        
        if (!is_string($host)) {
            return $preempt;
        }

        // VGT Zero-Trust Blacklist (Telemetrie & Update-Server)
        $blocked_domains = [
            'api.wordpress.org',
            'downloads.wordpress.org',
            's.w.org' // WP Stats & Telemetry
        ];

        if (in_array($host, $blocked_domains, true)) {
            
            // VGT OMEGA FIX: PHANTOM RESPONSE MATRIX 2.0 (APEX STATE)
            // Der WP Core erwartet bei API Calls strikt definierte JSON-Strukturen. 
            // Fehlen die Keys 'plugins' oder 'themes', wirft der Core Fatal Errors bei array_walk().
            // Wir injizieren ein perfektes, leeres Datenmodell. Der Core interpretiert dies als "System ist aktuell".
            
            $mock_data = [
                'plugins'      => [], // Zwingend erforderlich für /plugins/update-check/
                'themes'       => [], // Zwingend erforderlich für /themes/update-check/
                'translations' => [], // Zwingend für Translations-Updates
                'update'       => [], // Legacy / Fallback
                'no_update'    => [], // Legacy / Fallback
                'offers'       => []  // Zwingend erforderlich für /core/version-check/
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
                    'message' => 'OK'
                ],
                'cookies'  => [],
                'filename' => null
            ];
        }

        // Nicht-Telemetrie-Traffic ungehindert passieren lassen
        return $preempt; 
    }
}