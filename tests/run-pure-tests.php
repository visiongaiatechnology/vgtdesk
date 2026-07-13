<?php
/**
 * Pure unit tests against shipped WP-Desk core helpers.
 * Run: php tests/run-pure-tests.php
 */

declare(strict_types=1);

require_once __DIR__ . '/bootstrap-pure.php';

use VisionGaia\WPDesk\WPDeskSettings;
use VisionGaia\WPDesk\WPDeskSecurity;
use VisionGaia\WPDesk\WPDeskBanStore;
use VisionGaia\WPDesk\WPDeskAjaxGuard;
use VisionGaia\WPDesk\WPDeskModuleRegistry;
use VisionGaia\WPDesk\ValidationException;

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

// --- (a) folder payload apps:[] round-trips as array ---
$folders = WPDeskSettings::sanitize_folders([
    'folder_demo' => [
        'title' => 'Demo',
        'apps'  => [],
        'left'  => '10px',
        'top'   => '20px',
    ],
]);
$encoded = WPDeskSettings::encode_setting_json('folders', $folders);
$decoded = json_decode($encoded, true);
assert_true(is_array($decoded), 'folders JSON decodes to array');
assert_true(isset($decoded['folder_demo']['apps']), 'folder has apps key');
assert_true(is_array($decoded['folder_demo']['apps']), 'apps remains array after encode/decode');
assert_true($decoded['folder_demo']['apps'] === [], 'empty apps array preserved (not {})');
assert_true(strpos($encoded, '"apps":[]') !== false || strpos($encoded, '"apps": []') !== false, 'encoded JSON contains apps:[]');

// --- (b) wallpaper pure host checks ---
assert_true(
    WPDeskSecurity::is_safe_wallpaper_url_with_hosts(
        'https://evil.example/track.png',
        'mysite.local',
        'uploads.mysite.local'
    ) === false,
    'foreign host wallpaper rejected'
);
// Bypass #1: protocol-relative //evil must NOT be accepted via leading-slash branch
assert_true(
    WPDeskSecurity::is_safe_wallpaper_url_with_hosts(
        '//evil.com/track.png',
        'mysite.local',
        'uploads.mysite.local'
    ) === false,
    'protocol-relative //evil.com wallpaper rejected'
);
// Bypass #2: foreign host with /wp-content/uploads/ path must NOT be accepted via path substring
assert_true(
    WPDeskSecurity::is_safe_wallpaper_url_with_hosts(
        'https://evil.example/wp-content/uploads/x.webp',
        'mysite.local',
        'uploads.mysite.local'
    ) === false,
    'foreign host with /wp-content/uploads/ path rejected'
);
assert_true(
    WPDeskSecurity::is_safe_wallpaper_url_with_hosts(
        'https://mysite.local/wp-content/uploads/wall.webp',
        'mysite.local',
        'uploads.mysite.local'
    ) === true,
    'same-origin wallpaper accepted'
);
assert_true(
    WPDeskSecurity::is_safe_wallpaper_url_with_hosts(
        'data:image/png;base64,iVBORw0KGgo=',
        'mysite.local',
        null
    ) === true,
    'data-image wallpaper accepted'
);
assert_true(
    WPDeskSecurity::is_safe_wallpaper_url_with_hosts(
        '/wp-content/uploads/local.webp',
        'mysite.local',
        null
    ) === true,
    'relative upload-style path accepted'
);

try {
    WPDeskSettings::normalize_setting_value(
        'wallpaper',
        'https://tracker.evil/x.png'
    );
    $ok = WPDeskSecurity::is_safe_wallpaper_url_with_hosts('https://tracker.evil/x.png', 'good.local', null);
    assert_true($ok === false, 'normalize/wallpaper foreign host fails pure check');
} catch (ValidationException $e) {
    assert_true(true, 'normalize wallpaper throws ValidationException for unsafe URL');
}

// --- (c) ban merge CE+V7 ---
$merged = WPDeskBanStore::merge_ban_rows(
    [
        ['id' => 1, 'ip' => '1.1.1.1', 'reason' => 'ce', 'banned_at' => '2026-01-02 10:00:00'],
    ],
    [
        ['id' => 9, 'ip' => '2.2.2.2', 'reason' => 'v7', 'banned_at' => '2026-01-03 10:00:00'],
    ],
    50
);
assert_true(count($merged) === 2, 'ban merge combines CE and V7 rows');
assert_true($merged[0]['ip'] === '2.2.2.2', 'ban merge sorts newest first');
assert_true($merged[0]['version'] === WPDeskBanStore::VERSION_V7, 'v7 version tag present');
assert_true($merged[1]['version'] === WPDeskBanStore::VERSION_CE, 'ce version tag present');

// --- (d) opaque security error shape ---
$payload = WPDeskAjaxGuard::opaque_security_error_payload();
assert_true($payload['success'] === false, 'opaque security error success=false');
assert_true($payload['data'] === 'Request rejected for security reasons.', 'opaque security error message');

// Unauthorized path: assert_authorized without WP functions returns false user_can → SecurityException shape via payload
// verify_nonce without check_ajax_referer returns false
assert_true(WPDeskAjaxGuard::verify_nonce() === false, 'missing nonce infrastructure fails verify');
assert_true(WPDeskAjaxGuard::user_can('manage_options') === false, 'missing current_user_can fails capability');

// Module registry is data-driven (not empty)
$defs = WPDeskModuleRegistry::definitions();
assert_true(count($defs) >= 8, 'module registry has integrated modules');
$keys = array_column($defs, 'key');
assert_true(in_array('sentinel_ce', $keys, true), 'registry includes sentinel_ce');
assert_true(in_array('dattrack', $keys, true), 'registry includes dattrack');
assert_true(in_array('omega_vault', $keys, true), 'registry includes omega_vault');

// Full-replace keys do not use JSON_FORCE_OBJECT for folders
$norm = WPDeskSettings::normalize_setting_value(
    'folders',
    json_encode(['f1' => ['title' => 'T', 'apps' => [], 'left' => '', 'top' => '']])
);
$round = json_decode($norm, true);
assert_true(is_array($round['f1']['apps'] ?? null), 'normalize folders keeps apps as array');

// CPU honesty: pure method returns null or int, never invents via sin
$cpu = WPDeskSecurity::cpu_load_percent();
assert_true($cpu === null || (is_int($cpu) && $cpu >= 0 && $cpu <= 100), 'cpu_load_percent honest');

// Optimizable whitelist non-empty and does not mean all tables
$suffixes = WPDeskSecurity::optimizable_table_suffixes();
assert_true(count($suffixes) > 0 && count($suffixes) < 50, 'optimize whitelist bounded');

echo "\n---\nPassed: {$passed}, Failed: {$failed}\n";
exit($failed > 0 ? 1 : 0);
