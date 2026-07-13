<?php
/**
 * Pure tests for Operator-OS recovery + Gutenberg content helpers.
 * Run: php tests/run-operator-os-tests.php
 */

declare(strict_types=1);

require_once __DIR__ . '/bootstrap-pure.php';

$root = dirname(__DIR__);
require_once $root . '/includes/core/class-vgt-wpdesk-recovery.php';
require_once $root . '/includes/modules/astra/includes/class-vgta-gutenberg-content.php';

use VisionGaia\WPDesk\WPDeskRecovery;
use VGTAstra\AgentSystem\GutenbergContent;

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

// --- Recovery pure helpers ---
assert_true(WPDeskRecovery::is_allowed_action('force_classic'), 'force_classic allowed');
assert_true(WPDeskRecovery::is_allowed_action('disable_redirect'), 'disable_redirect allowed');
assert_true(!WPDeskRecovery::is_allowed_action('drop_database'), 'unknown action rejected');

$auth_ok = WPDeskRecovery::authorize_action('force_classic', true, true);
assert_true($auth_ok['ok'] === true && $auth_ok['code'] === 'authorized', 'recovery authorize success');

$auth_cap = WPDeskRecovery::authorize_action('force_classic', false, true);
assert_true($auth_cap['ok'] === false && $auth_cap['code'] === 'capability', 'recovery rejects missing capability');

$auth_nonce = WPDeskRecovery::authorize_action('disable_redirect', true, false);
assert_true($auth_nonce['ok'] === false && $auth_nonce['code'] === 'invalid_nonce', 'recovery rejects invalid nonce');

assert_true(WPDeskRecovery::is_recovery_page_slug('vgt-recovery-center'), 'recovery page slug recognized');
$spec = WPDeskRecovery::force_classic_cookie_spec();
assert_true($spec['name'] === 'vgt_desk_bypass' && $spec['value'] === '1' && $spec['ttl_seconds'] > 0, 'force classic cookie spec');

// --- Gutenberg pure helpers ---
assert_true(GutenbergContent::can_insert_content(true) === true, 'can insert when edit_posts');
assert_true(GutenbergContent::can_insert_content(false) === false, 'cannot insert without capability');

$html = GutenbergContent::generate_local_html('Hero section for the homepage');
assert_true(str_contains($html, 'Hero section'), 'local generate includes prompt text');
$good = GutenbergContent::sanitize_html_for_insert($html);
assert_true($good['ok'] === true && $good['html'] !== '', 'sanitize accepts generated HTML');

$bad_script = GutenbergContent::sanitize_html_for_insert('<p>x</p><script>alert(1)</script>');
assert_true($bad_script['ok'] === false && $bad_script['code'] === 'forbidden_tag', 'sanitize rejects script tag');

$bad_js = GutenbergContent::sanitize_html_for_insert('<a href="javascript:alert(1)">x</a>');
assert_true($bad_js['ok'] === false, 'sanitize rejects javascript: URI');

$blocks = GutenbergContent::to_block_markup($good['html']);
assert_true(str_contains($blocks, '<!-- wp:html -->'), 'block markup wraps html block');

echo "\n---\nPassed: {$passed}, Failed: {$failed}\n";
exit($failed > 0 ? 1 : 0);
