<?php
/**
 * Pure tests: frame policy, widget layout, portal URL guards.
 * Run: php tests/run-portal-harden-tests.php
 *
 * Drives SHIPPED helpers only (WPDeskFramePolicy, WPDeskWidgetLayout, WPDeskIframePolicy).
 */

declare(strict_types=1);

require_once __DIR__ . '/bootstrap-pure.php';

$root = dirname(__DIR__);
require_once $root . '/includes/core/class-vgt-wpdesk-frame-policy.php';
require_once $root . '/includes/core/class-vgt-wpdesk-widget-layout.php';
require_once $root . '/includes/core/class-vgt-wpdesk-iframe-policy.php';

use VisionGaia\WPDesk\WPDeskFramePolicy;
use VisionGaia\WPDesk\WPDeskWidgetLayout;
use VisionGaia\WPDesk\WPDeskIframePolicy;

$failed = 0;
$passed = 0;

function assert_true(bool $cond, string $msg): void
{
    global $failed, $passed;
    if ($cond) {
        $passed++;
        echo "PASS: {$msg}\n";
    } else {
        $failed++;
        echo "FAIL: {$msg}\n";
    }
}

// =====================================================================
// 1) Frame policy decision — single value, never stack
// =====================================================================
assert_true(
    WPDeskFramePolicy::x_frame_options_value(true, false, false) === WPDeskFramePolicy::ALLOW_SAMEORIGIN,
    'admin → SAMEORIGIN'
);
assert_true(
    WPDeskFramePolicy::x_frame_options_value(false, true, false) === WPDeskFramePolicy::ALLOW_SAMEORIGIN,
    'desk embed → SAMEORIGIN'
);
assert_true(
    WPDeskFramePolicy::x_frame_options_value(false, false, true) === WPDeskFramePolicy::DENY,
    'public front → DENY'
);
assert_true(
    WPDeskFramePolicy::x_frame_options_value(true, true, false) === WPDeskFramePolicy::ALLOW_SAMEORIGIN,
    'admin+embed → SAMEORIGIN (never DENY stack)'
);

$v1 = WPDeskFramePolicy::x_frame_options_value(false, false, true);
$v2 = WPDeskFramePolicy::x_frame_options_value(true, false, false);
assert_true($v1 === 'DENY' && $v2 === 'SAMEORIGIN' && $v1 !== $v2, 'front DENY vs admin SAMEORIGIN are distinct singles');

// =====================================================================
// 2) Portal URL accept / reject (gaiacom.de front must NOT be portal)
// =====================================================================
assert_true(
    WPDeskFramePolicy::is_admin_portal_url('https://gaiacom.de/wp-admin/edit.php?post_type=page') === true,
    'pages admin URL is portal'
);
assert_true(
    WPDeskFramePolicy::is_admin_portal_url('https://gaiacom.de/') === false,
    'site root is NOT a portal URL'
);
assert_true(
    WPDeskFramePolicy::is_admin_portal_url('https://gaiacom.de/index.php') === false,
    'front index is NOT a portal URL'
);
assert_true(
    WPDeskFramePolicy::is_admin_portal_url('plugins.php', 'https://gaiacom.de/wp-admin/') === true,
    'relative plugins.php under admin base is portal'
);
assert_true(
    WPDeskFramePolicy::is_admin_portal_url('edit.php?post_type=page') === true,
    'bare edit.php?post_type=page recognized'
);
assert_true(
    WPDeskFramePolicy::is_admin_portal_url('themes.php') === true,
    'themes.php recognized'
);

$forced = WPDeskFramePolicy::force_admin_portal_url(
    'edit.php?post_type=page',
    'https://gaiacom.de/wp-admin/'
);
assert_true(
    str_contains($forced, 'wp-admin') && str_contains($forced, 'post_type=page'),
    'force rebuilds pages list under admin base'
);
$forcedRoot = WPDeskFramePolicy::force_admin_portal_url('https://gaiacom.de/', 'https://gaiacom.de/wp-admin/');
assert_true($forcedRoot === '', 'force refuses bare homepage');

assert_true(
    WPDeskFramePolicy::is_desk_embed_request(true, false, false) === true,
    'vgt_iframe param is embed'
);
assert_true(
    WPDeskFramePolicy::is_desk_embed_request(false, true, false) === true,
    'Sec-Fetch-Dest iframe is embed'
);
assert_true(
    WPDeskFramePolicy::is_desk_embed_request(false, false, true) === true,
    'referer vgt_iframe is embed'
);
assert_true(
    WPDeskFramePolicy::is_desk_embed_request(false, false, false) === false,
    'no signals → not embed'
);

// Single XFO key overwrite pattern
$headers = [
    'X-Frame-Options' => 'DENY',
    'X-Content-Type-Options' => 'nosniff',
];
$headers['X-Frame-Options'] = WPDeskFramePolicy::x_frame_options_value(true, true, false);
assert_true(
    $headers['X-Frame-Options'] === 'SAMEORIGIN'
    && count(array_filter(array_keys($headers), static fn($k) => strtolower((string) $k) === 'x-frame-options')) === 1,
    'single XFO key after consolidation'
);

// =====================================================================
// 3) Widget layout — valid multi-widget + reject empty id + oversized
// =====================================================================
$ok = WPDeskWidgetLayout::normalize_positions([
    'widget-clock'  => ['left' => '120.4px', 'top' => 80, 'right' => '10px', 'visible' => true],
    'widget-system' => ['left' => 'calc(100% - 20px)', 'top' => '40px'], // calc edge dropped, top kept
]);
assert_true($ok['ok'] === true, 'normalize accepts valid multi-widget payload');
assert_true(
    isset($ok['positions']['widget-clock']['left'])
    && $ok['positions']['widget-clock']['left'] === '120px'
    && $ok['positions']['widget-clock']['top'] === '80px'
    && !isset($ok['positions']['widget-clock']['right']),
    'coords integer-px + drop opposing right'
);
assert_true(
    isset($ok['positions']['widget-system']['top'])
    && !isset($ok['positions']['widget-system']['left']),
    'calc left dropped, top kept'
);

$empty = WPDeskWidgetLayout::normalize_positions([]);
assert_true($empty['ok'] === true && $empty['positions'] === [], 'empty full-replace ok');

// REJECT empty widget id (plan step 2 / AC)
$emptyId = WPDeskWidgetLayout::normalize_positions([
    '' => ['left' => '10px', 'top' => '20px'],
]);
assert_true(
    $emptyId['ok'] === false && $emptyId['code'] === 'empty_widget_id',
    'empty widget id rejected'
);

// REJECT whitespace-only / pure_key-empty id
$badKey = WPDeskWidgetLayout::normalize_positions([
    '!!!' => ['left' => '10px', 'top' => '20px'],
]);
assert_true(
    $badKey['ok'] === false && $badKey['code'] === 'empty_widget_id',
    'non-key widget id rejected as empty_widget_id'
);

// REJECT oversized coordinate payload (plan step 2 / AC)
$oversized = WPDeskWidgetLayout::normalize_positions([
    'widget-clock' => ['left' => (WPDeskWidgetLayout::MAX_COORD_PX + 1) . 'px', 'top' => '20px'],
]);
assert_true(
    $oversized['ok'] === false && $oversized['code'] === 'oversized_coord',
    'oversized coordinate rejected'
);

$oversizedNeg = WPDeskWidgetLayout::normalize_positions([
    'widget-clock' => ['left' => '10px', 'top' => (-1 * (WPDeskWidgetLayout::MAX_COORD_PX + 50)) . 'px'],
]);
assert_true(
    $oversizedNeg['ok'] === false && $oversizedNeg['code'] === 'oversized_coord',
    'oversized negative coordinate rejected'
);

$tooMany = [];
for ($i = 0; $i < WPDeskWidgetLayout::MAX_WIDGETS + 1; $i++) {
    $tooMany['w' . $i] = ['left' => '1px', 'top' => '1px'];
}
$tooManyResult = WPDeskWidgetLayout::normalize_positions($tooMany);
assert_true(
    $tooManyResult['ok'] === false && $tooManyResult['code'] === 'too_many_widgets',
    'too many widgets rejected'
);

$json = WPDeskWidgetLayout::normalize_positions('{"widget-notes":{"left":"10px","top":"20px","visible":"true"}}');
assert_true(
    $json['ok'] && ($json['positions']['widget-notes']['visible'] ?? false) === true,
    'JSON string + visible string true'
);

$bad = WPDeskWidgetLayout::normalize_positions('not-json');
assert_true($bad['ok'] === false && $bad['code'] === 'malformed_json', 'malformed json rejected');

$px = WPDeskWidgetLayout::normalize_px('99.6px');
assert_true($px === '100px', 'normalize_px rounds');
assert_true(WPDeskWidgetLayout::normalize_px('auto') === null, 'auto rejected');
assert_true(WPDeskWidgetLayout::normalize_px('calc(100% - 10px)') === null, 'calc rejected');
$detail = WPDeskWidgetLayout::normalize_px_detailed((WPDeskWidgetLayout::MAX_COORD_PX + 5) . 'px');
assert_true($detail['status'] === 'oversized', 'normalize_px_detailed flags oversized');

// =====================================================================
// 4) Iframe-ok classification (pages/themes stay embeddable)
// =====================================================================
assert_true(
    WPDeskIframePolicy::classify('edit.php?post_type=page', 'edit_php_post_type_page') === WPDeskIframePolicy::MODE_IFRAME_OK,
    'pages list stays iframe-ok'
);
assert_true(
    WPDeskIframePolicy::classify('themes.php', 'themes_php') === WPDeskIframePolicy::MODE_IFRAME_OK,
    'themes list stays iframe-ok'
);
assert_true(
    WPDeskIframePolicy::classify('https://example.com/wp-admin/plugins.php', 'plugins_php') === WPDeskIframePolicy::MODE_IFRAME_OK,
    'plugins list stays iframe-ok by default'
);
assert_true(
    WPDeskIframePolicy::classify('customize.php', 'customize_php') === WPDeskIframePolicy::MODE_CLASSIC_REQUIRED,
    'customizer classic-required'
);

echo "\n---\nPassed: {$passed}, Failed: {$failed}\n";
exit($failed > 0 ? 1 : 0);
