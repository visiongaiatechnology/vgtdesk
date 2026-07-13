<?php
declare(strict_types=1);

namespace VisionGaia\WPDesk;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Pure normalize/validate for desk widget_positions payloads.
 *
 * Rejects empty widget ids and oversized coordinates (hard fail).
 * Soft-skips non-array entries and non-numeric garbage edges (calc/auto).
 */
final class WPDeskWidgetLayout
{
    public const MAX_WIDGETS = 40;
    public const MAX_COORD_PX = 20000;

    /**
     * @param mixed $raw
     * @return array{ok:bool,code:string,positions:array<string,array<string,mixed>>}
     */
    public static function normalize_positions($raw): array
    {
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                return ['ok' => false, 'code' => 'malformed_json', 'positions' => []];
            }
            $raw = $decoded;
        }
        if (!is_array($raw)) {
            return ['ok' => false, 'code' => 'not_array', 'positions' => []];
        }
        // Empty object is a valid full-replace (reset).
        if ($raw === []) {
            return ['ok' => true, 'code' => 'ok', 'positions' => []];
        }
        if (count($raw) > self::MAX_WIDGETS) {
            return ['ok' => false, 'code' => 'too_many_widgets', 'positions' => []];
        }

        $out = [];
        foreach ($raw as $id => $pos) {
            // Explicit empty id rejection (plan AC: reject empty widget id).
            if ($id === '' || $id === null || (is_string($id) && trim($id) === '')) {
                return ['ok' => false, 'code' => 'empty_widget_id', 'positions' => []];
            }
            $wid = self::pure_key((string) $id);
            if ($wid === '') {
                return ['ok' => false, 'code' => 'empty_widget_id', 'positions' => []];
            }
            if (!is_array($pos)) {
                continue;
            }
            $entry = [];
            foreach (['left', 'right', 'top', 'bottom'] as $edge) {
                if (!array_key_exists($edge, $pos)) {
                    continue;
                }
                $result = self::normalize_px_detailed($pos[$edge]);
                if ($result['status'] === 'oversized') {
                    return ['ok' => false, 'code' => 'oversized_coord', 'positions' => []];
                }
                if ($result['status'] === 'ok' && $result['value'] !== null) {
                    $entry[$edge] = $result['value'];
                }
                // status invalid (calc/auto/garbage): skip edge only
            }
            if (array_key_exists('visible', $pos)) {
                $entry['visible'] = (
                    $pos['visible'] === true
                    || $pos['visible'] === 'true'
                    || $pos['visible'] === 1
                    || $pos['visible'] === '1'
                );
            }
            // Prefer left OR right, top OR bottom — drop opposites if both set.
            if (isset($entry['left'], $entry['right'])) {
                unset($entry['right']);
            }
            if (isset($entry['top'], $entry['bottom'])) {
                unset($entry['bottom']);
            }
            // Keep entries that have at least one coord or a visibility flag.
            if ($entry === []) {
                continue;
            }
            $out[$wid] = $entry;
        }

        return ['ok' => true, 'code' => 'ok', 'positions' => $out];
    }

    /**
     * @param mixed $value
     */
    public static function normalize_px($value): ?string
    {
        $r = self::normalize_px_detailed($value);
        return $r['status'] === 'ok' ? $r['value'] : null;
    }

    /**
     * @param mixed $value
     * @return array{status:string,value:?string} status: ok|invalid|oversized
     */
    public static function normalize_px_detailed($value): array
    {
        if (is_int($value) || is_float($value)) {
            $n = (float) $value;
        } elseif (is_string($value)) {
            $value = trim($value);
            if ($value === '' || $value === 'auto' || stripos($value, 'calc') !== false) {
                return ['status' => 'invalid', 'value' => null];
            }
            if (!preg_match('/^(-?\d+(?:\.\d+)?)(px)?$/i', $value, $m)) {
                return ['status' => 'invalid', 'value' => null];
            }
            $n = (float) $m[1];
        } else {
            return ['status' => 'invalid', 'value' => null];
        }
        if (!is_finite($n)) {
            return ['status' => 'invalid', 'value' => null];
        }
        if (abs($n) > self::MAX_COORD_PX) {
            return ['status' => 'oversized', 'value' => null];
        }
        return ['status' => 'ok', 'value' => ((string) (int) round($n)) . 'px'];
    }

    public static function pure_key(string $key): string
    {
        if (function_exists('sanitize_key')) {
            return sanitize_key($key);
        }
        $key = strtolower($key);
        return (string) preg_replace('/[^a-z0-9_\-]/', '', $key);
    }
}
