<?php
/**
 * VGT Omega Login Engine — WP-Desk Module Adapter
 * 
 * Integriert die Login-Engine als Submodul in das VGT WP-Desk System.
 * Verwaltet Autoloading und Path-Auflösung.
 *
 * @package VisionGaia\WPDesk\Modules\LoginEngine
 */

declare(strict_types=1);

namespace VisionGaia\WPDesk\Modules\LoginEngine;

if (!defined('ABSPATH')) {
    exit;
}

// Modul-Pfade
define('VGT_LOGIN_MODULE_PATH', plugin_dir_path(__FILE__));
define('VGT_LOGIN_MODULE_URL', plugin_dir_url(__FILE__));

// Submodule laden
require_once VGT_LOGIN_MODULE_PATH . 'class-vgt-login-injector.php';
require_once VGT_LOGIN_MODULE_PATH . 'class-vgt-login-settings.php';

/**
 * STATUS: DIAMANT VGT SUPREME
 * Singleton-Boot-Controller für das Login-Engine-Modul.
 */
final class LoginEngine
{
    private static ?self $instance = null;

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        \VGTLoginInjector::init();
        \VGTLoginSettings::init();
    }

    // Sicherheits-Gate: Anti-Clone & Anti-Deserialize
    private function __clone() {}

    public function __wakeup(): void
    {
        throw new \LogicException('Deserialization of VGT Login Engine disabled. [VGT-SEC-01]');
    }

    public function __unserialize(array $data): void
    {
        throw new \LogicException('Unserialize of VGT Login Engine disabled. [VGT-SEC-02]');
    }
}

// Initialisierung der Boot-Sequenz
LoginEngine::getInstance();

