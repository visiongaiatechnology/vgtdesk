<?php
/**
 * Pure tests: iframe/classic policy, audit trail, soft-disable authorize.
 * Run: php tests/run-harden-tests.php
 */

declare(strict_types=1);

require_once __DIR__ . '/bootstrap-pure.php';

$root = dirname(__DIR__);
require_once $root . '/includes/core/class-vgt-wpdesk-iframe-policy.php';
require_once $root . '/includes/core/class-vgt-wpdesk-audit.php';

use VisionGaia\WPDesk\WPDeskIframePolicy;
use VisionGaia\WPDesk\WPDeskAudit;

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

// --- iframe / classic classifier ---
assert_true(
    WPDeskIframePolicy::classify('https://example.com/wp-admin/edit.php', 'edit_php') === WPDeskIframePolicy::MODE_IFRAME_OK,
    'edit.php is iframe-ok'
);
assert_true(
    WPDeskIframePolicy::classify('https://example.com/wp-admin/customize.php', 'customize_php') === WPDeskIframePolicy::MODE_CLASSIC_REQUIRED,
    'customize.php is classic-required'
);
assert_true(
    WPDeskIframePolicy::classify('post.php?action=elementor', 'elementor') === WPDeskIframePolicy::MODE_CLASSIC_REQUIRED,
    'elementor editor is classic-required'
);
assert_true(
    WPDeskIframePolicy::classify('edit.php?post_type=page', 'edit_php_post_type_page') === WPDeskIframePolicy::MODE_IFRAME_OK,
    'pages list stays iframe-ok'
);
assert_true(
    WPDeskIframePolicy::classify('themes.php', 'themes_php') === WPDeskIframePolicy::MODE_IFRAME_OK,
    'themes list stays iframe-ok'
);
assert_true(
    WPDeskIframePolicy::classify('https://example.com/wp-admin/plugins.php', 'plugins_php', ['plugins_php' => true]) === WPDeskIframePolicy::MODE_CLASSIC_REQUIRED,
    'override forces classic-required'
);
assert_true(
    WPDeskIframePolicy::should_open_classic('customize.php') === true,
    'should_open_classic true for customizer'
);

// normalize classic_apps
$norm = WPDeskIframePolicy::normalize_classic_apps(['plugins_php' => 'true', 'bad' => false, '' => true]);
assert_true(isset($norm['plugins_php']) && $norm['plugins_php'] === true && !isset($norm['bad']), 'normalize classic_apps');

// --- audit pure ---
$entry = WPDeskAudit::normalize_entry('sentinel_soft_disable', 7, '203.0.113.10', ['enabled' => 'false']);
assert_true($entry['action'] === 'sentinel_soft_disable' && $entry['user_id'] === 7, 'normalize audit entry');

$ok = WPDeskAudit::validate_entry($entry);
assert_true($ok['ok'] === true, 'valid audit entry accepted');

$bad = WPDeskAudit::validate_entry(['action' => '', 'user_id' => 1, 'ip' => '1.1.1.1', 'context' => []]);
assert_true($bad['ok'] === false && $bad['code'] === 'empty_action', 'empty action rejected');

$hugeCtx = [];
for ($i = 0; $i < 50; $i++) {
    $hugeCtx['k' . $i] = str_repeat('x', 50);
}
$oversized = WPDeskAudit::normalize_entry('test_action', 1, '1.1.1.1', $hugeCtx);
assert_true(count($oversized['context']) <= WPDeskAudit::MAX_CONTEXT_KEYS, 'context keys capped');

$log = WPDeskAudit::append_to_log([], $entry, 2);
$log = WPDeskAudit::append_to_log($log, WPDeskAudit::normalize_entry('b', 1, '1.1.1.1'), 2);
$log = WPDeskAudit::append_to_log($log, WPDeskAudit::normalize_entry('c', 1, '1.1.1.1'), 2);
assert_true(count($log) === 2, 'audit log capped at max');

// --- soft-disable authorize ---
$auth = WPDeskAudit::authorize_control_mutation(true, true);
assert_true($auth['ok'] === true, 'soft-disable authorize ok');
$authCap = WPDeskAudit::authorize_control_mutation(false, true);
assert_true($authCap['ok'] === false && $authCap['code'] === 'capability', 'soft-disable rejects capability');
$authNonce = WPDeskAudit::authorize_control_mutation(true, false);
assert_true($authNonce['ok'] === false && $authNonce['code'] === 'invalid_nonce', 'soft-disable rejects nonce');

echo "\n---\nPassed: {$passed}, Failed: {$failed}\n";
exit($failed > 0 ? 1 : 0);
