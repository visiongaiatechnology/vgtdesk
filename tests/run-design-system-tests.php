<?php
/**
 * Pure tests: VGT Design System paths + tab CSS alias.
 * Run: php tests/run-design-system-tests.php
 */

declare(strict_types=1);

require_once __DIR__ . '/bootstrap-pure.php';

$root = dirname(__DIR__);
require_once $root . '/includes/core/class-vgt-wpdesk-design-system.php';

use VisionGaia\WPDesk\WPDeskDesignSystem;

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

$paths = WPDeskDesignSystem::expected_paths();
assert_true(count($paths) === 4, 'four DS asset paths');
foreach ($paths as $rel) {
    assert_true(is_readable($root . '/' . $rel), "readable: {$rel}");
}

assert_true(
    WPDeskDesignSystem::sentinel_tab_css_rel('mudeployer') === 'assets/css/vgts-mu-deployer.css',
    'mudeployer aliases to vgts-mu-deployer.css'
);
assert_true(
    WPDeskDesignSystem::sentinel_tab_css_rel('overview') === 'assets/css/vgts-overview.css',
    'overview tab path unchanged'
);
assert_true(
    WPDeskDesignSystem::sentinel_tab_css_rel('') === '',
    'empty tab → empty path'
);

// Token file contains canonical brand accent
$tokens = (string) file_get_contents($root . '/assets/css/design-system/vgt-ds-tokens.css');
assert_true(str_contains($tokens, '--vgt-ds-accent: #6366f1'), 'tokens define indigo accent');
assert_true(str_contains($tokens, '--vgt-ds-bg:'), 'tokens define bg');

$compat = (string) file_get_contents($root . '/assets/css/design-system/vgt-ds-compat.css');
assert_true(str_contains($compat, '--vgts-accent: var(--vgt-ds-accent)'), 'compat maps vgts-accent');
assert_true(str_contains($compat, '.vgta-root'), 'compat maps astra root');

echo "\n---\nPassed: {$passed}, Failed: {$failed}\n";
exit($failed > 0 ? 1 : 0);
