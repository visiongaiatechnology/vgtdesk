<?php
/**
 * Plugin Name:       VGT WP-Desk — Premium Slim Desktop (Modular)
 * Plugin URI:        https://visiongaiatechnology.de
 * Description:       Ein eleganter, modularer Desktop-Mode für das WordPress-Backend. Schlank, unzerstörbar und hochkompatibel.
 * Version:           2.0.0-beta.1
 * Author:            VisionGaiaTechnology
 * Author URI:        https://visiongaiatechnology.de
 * License:           AGPLv3
 * License URI:       https://www.gnu.org/licenses/agpl-3.0.html
 * Text Domain:       vgt-wp-desk
 *
 * --- LICENSE & COPYRIGHT INFORMATION ---
 * (c) 2024-2026 VisionGaiaTechnology. All Rights Reserved.
 * This software is open-source under the terms of the GNU Affero General Public License v3 (AGPLv3).
 * Any modification, redistribution or integration into other suites must comply with the AGPLv3.
 */

declare(strict_types=1);

namespace VisionGaia\WPDesk;

if (!defined('ABSPATH')) {
    exit;
}

// Pfad-Definitionen
define('VGT_WPDESK_PATH', plugin_dir_path(__FILE__));
define('VGT_WPDESK_URL', plugin_dir_url(__FILE__));
/** Semver for assets / internals */
define('VGT_WPDESK_VERSION', '2.0.0-beta.1');
/** Public marketing label shown in UI */
define('VGT_WPDESK_VERSION_LABEL', 'V2.0 Beta v1');

// KERNEL EXCEPTION HIERARCHY (PATTERN 1.5.A)
class WPDeskException     extends \Exception {}
class ValidationException extends WPDeskException {} // User-Facing
class SecurityException   extends WPDeskException {} // Internal Opaque Log, Generic Client Response
class StorageException    extends WPDeskException {} // Infrastructure Failure

// Trait vor der Klassen-Kompilierung laden (Vermeidung von Fatal-Errors)
if (file_exists(VGT_WPDESK_PATH . 'includes/trait-vgt-ajax-handlers.php')) {
    require_once VGT_WPDESK_PATH . 'includes/trait-vgt-ajax-handlers.php';
}

// Lade modulare Core-Klassen
require_once VGT_WPDESK_PATH . 'includes/core/class-vgt-wpdesk-security.php';
require_once VGT_WPDESK_PATH . 'includes/core/class-vgt-wpdesk-settings.php';
require_once VGT_WPDESK_PATH . 'includes/core/class-vgt-wpdesk-ban-store.php';
require_once VGT_WPDESK_PATH . 'includes/core/class-vgt-wpdesk-ajax-guard.php';
require_once VGT_WPDESK_PATH . 'includes/core/class-vgt-wpdesk-module-registry.php';
require_once VGT_WPDESK_PATH . 'includes/core/class-vgt-wpdesk-recovery.php';
require_once VGT_WPDESK_PATH . 'includes/core/class-vgt-wpdesk-iframe-policy.php';
require_once VGT_WPDESK_PATH . 'includes/core/class-vgt-wpdesk-frame-policy.php';
require_once VGT_WPDESK_PATH . 'includes/core/class-vgt-wpdesk-widget-layout.php';
require_once VGT_WPDESK_PATH . 'includes/core/class-vgt-wpdesk-design-system.php';
require_once VGT_WPDESK_PATH . 'includes/core/class-vgt-wpdesk-audit.php';
require_once VGT_WPDESK_PATH . 'includes/core/class-vgt-wpdesk-app-builder.php';
require_once VGT_WPDESK_PATH . 'includes/core/class-vgt-wpdesk-plugin.php';

// Registriere Aktivierungs-Hook
register_activation_hook(__FILE__, [WPDeskPlugin::class, 'activate']);

// Single frame-header policy (prevents DENY + SAMEORIGIN stacks on admin portals)
WPDeskFramePolicy::boot();

// Starte das Desktop-System
WPDeskPlugin::getInstance();
