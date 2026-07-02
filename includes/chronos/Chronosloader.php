<?php
/**
 * Module Name: VGT Chronos Engine
 * Module URI: https://visiongaiatechnology.de
 * Module Description: Diamant-Status Countdown Engine. Zero Dependencies. Strict Types. Uncompromising Performance.
 * Module Version: 1.1.0
 * Module Author: VISIONGAIATECHNOLOGY
 * Requires PHP: 8.1
 *LICENSE: AGPLv3 (OPEN SOURCE) - GLOBAL PROLIFERATION PROTOCOL
 * ==============================================================================
 * 
 * Copyright (c) 2026 VISIONGAIATECHNOLOGY
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 * ==============================================================================
 */

declare(strict_types=1);

namespace VGT\Chronos;

if (!defined('ABSPATH')) {
    exit('VGT PROTOCOL: Unauthorized Access Terminated.');
}

// STATUS: DIAMANT VGT SUPREME
// ARCHITEKTUR: Singleton Bootstrapper. Initialisiert das System verzögerungsfrei.

final class Bootstrapper
{
    private static ?Bootstrapper $instance = null;
    public const VERSION = '1.1.0';
    public const TABLE_NAME = 'vgt_countdowns';

    private function __construct()
    {
        $this->define_constants();
        $this->load_dependencies();
        $this->init_hooks();
    }

    public static function get_instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function define_constants(): void
    {
        define('VGT_CHRONOS_PATH', plugin_dir_path(__FILE__));
        define('VGT_CHRONOS_URL', plugin_dir_url(__FILE__));
    }

    private function load_dependencies(): void
    {
        require_once VGT_CHRONOS_PATH . 'inc/Database.php';
        require_once VGT_CHRONOS_PATH . 'inc/Admin.php';
        require_once VGT_CHRONOS_PATH . 'inc/Frontend.php';
    }

    private function init_hooks(): void
    {
        add_action('init', [self::class, 'maybe_create_db'], 5);

        add_action('plugins_loaded', function() {
            Admin::init();
            Frontend::init();
        });
    }

    public static function maybe_create_db(): void
    {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) !== $table_name) {
            Database::activate();
        }
    }
}

Bootstrapper::get_instance();