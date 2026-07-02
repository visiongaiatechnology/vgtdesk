<?php
declare(strict_types=1);

/**
 * Module Name: VGT Book Reader
 * Module URI: https://visiongaiatechnology.de
 * Module Description: State-of-the-Art PDF Reader Engine mit Dark-Mode Interface und kompromissloser Performance. (Strict Typed / Namespaced)
 * Module Version: 1.0.1
 * Module Author: VISIONGAIATECHNOLOGY
 * Module Domain: vgt-book-reader
 * LICENSE: AGPLv3 (OPEN SOURCE) - GLOBAL PROLIFERATION PROTOCOL
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

namespace VGT\BookReader;

// Strikte Isolations-Direktive: Direkter Zugriff verboten.
if ( ! \defined( 'ABSPATH' ) ) {
    exit( 'VGT Protocol: Access Denied.' );
}

\define( 'VGT_BOOK_READER_PATH', \plugin_dir_path( __FILE__ ) );
\define( 'VGT_BOOK_READER_URL', \plugin_dir_url( __FILE__ ) );
\define( 'VGT_BOOK_READER_VERSION', '1.0.1' );

use VGT\BookReader\Core\BookCPT;
use VGT\BookReader\Admin\Dashboard;
use VGT\BookReader\Admin\API;
use VGT\BookReader\Frontend\FrontendEngine;

/**
 * Singleton Bootstrapper Class
 * Sichert die Systemintegrität und verhindert Mehrfach-Instanziierungen.
 */
final class Bootstrap {

    private static ?Bootstrap $instance = null;

    public static function get_instance(): Bootstrap {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_dependencies();
        $this->initialize_subsystems();
    }

     private function load_dependencies(): void {
        require_once VGT_BOOK_READER_PATH . 'includes/class-vgt-book-cpt.php';
        require_once VGT_BOOK_READER_PATH . 'includes/class-vgt-dashboard.php';
        require_once VGT_BOOK_READER_PATH . 'includes/class-vgt-api.php';
        require_once VGT_BOOK_READER_PATH . 'includes/class-vgt-frontend.php';
    }

    private function initialize_subsystems(): void {
        // Initiiere die Data-Layer (Custom Post Type)
        $cpt = new BookCPT();
        $cpt->init();

        // Initiiere Backend-UI nur im Admin-Bereich
        if ( \is_admin() ) {
            $dashboard = new Dashboard();
            $dashboard->init();

            $api = new API();
            $api->init();
        }

        // Initiiere Frontend-Engine immer (für Shortcodes & Ajax)
        $frontend = new FrontendEngine();
        $frontend->init();
    }
}

// Genesis Initialisierung
function run_system(): void {
    Bootstrap::get_instance();
}
run_system();