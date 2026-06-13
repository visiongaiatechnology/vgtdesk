<?php
declare(strict_types=1);

namespace VGT\BookReader\Admin;

// Strikte Isolations-Direktive
if ( ! \defined( 'ABSPATH' ) ) {
    exit( 'VGT Protocol: Access Denied.' );
}

/**
 * VGT Dashboard Controller.
 * Kapselt das Backend-Interface und entkoppelt es von nativen WP-Meta-Boxen.
 */
final class Dashboard {

    private const PAGE_SLUG = 'vgt-book-reader';

    public function init(): void {
        \add_action( 'admin_menu', [$this, 'register_dashboard_page'] );
        \add_action( 'admin_enqueue_scripts', [$this, 'enqueue_assets'] );
    }

    public function register_dashboard_page(): void {
        \add_submenu_page(
            'vgt-build-center',
            'VGT Book Reader',
            'VGT Book Reader',
            'manage_options',
            self::PAGE_SLUG,
            [$this, 'render_dashboard_shell']
        );
    }

    public function enqueue_assets( string $hook ): void {
        if ( \strpos( $hook, self::PAGE_SLUG ) === false ) {
            return;
        }

        $version = \defined('VGT_BOOK_READER_VERSION') ? VGT_BOOK_READER_VERSION : '1.0.0';

        \wp_enqueue_style(
            'vgt-admin-css',
            VGT_BOOK_READER_URL . 'assets/css/vgt-admin.css',
            [],
            $version
        );

        \wp_enqueue_script(
            'vgt-admin-js',
            VGT_BOOK_READER_URL . 'assets/js/vgt-admin.js',
            [],
            $version,
            true
        );

        \wp_localize_script( 'vgt-admin-js', 'vgtConfig', [
            'ajaxUrl' => \admin_url( 'admin-ajax.php' ),
            'nonce'   => \wp_create_nonce( 'vgt_dashboard_action' ),
        ]);
    }

    public function render_dashboard_shell(): void {
        ?>
        <div id="vgt-app-root" class="vgt-dashboard-wrapper">
            <header class="vgt-topbar">
                <div class="vgt-brand">
                    <div class="vgt-logo-cube"></div>
                    <div>
                        <h1>VISION GAIA BOOK READER</h1>
                        <span>Reader Engine Dashboard</span>
                    </div>
                </div>
                <div class="vgt-topbar-actions">
                    <div class="vgt-status-badge">
                        <span class="pulse"></span> System Online
                    </div>
                </div>
            </header>

            <main class="vgt-main-layout">
                <aside class="vgt-sidebar">
                    <nav>
                        <button class="vgt-nav-item active" data-target="view-list">
                            <span class="icon">📚</span> Bibliothek
                        </button>
                        <button class="vgt-nav-item" data-target="view-create">
                            <span class="icon">✨</span> Neues Buch
                        </button>
                    </nav>
                    
                    <div class="vgt-sidebar-footer">
                        <div class="vgt-status-box">
                            <p class="label">System Core</p>
                            <p class="value">Platin Status</p>
                        </div>
                    </div>
                </aside>

                <section class="vgt-content-area">
                    
                    <!-- View: List -->
                    <div id="view-list" class="vgt-view active">
                        <div class="vgt-header-block">
                            <h2>Ihre Publikationen</h2>
                            <p>Verwalten Sie Ihre hochgeladenen Dokumente und Shortcodes in der Matrix.</p>
                        </div>
                        
                        <div class="vgt-book-grid" id="vgt-books-container">
                            <div class="vgt-loader" style="grid-column: 1/-1; text-align: center; color: #00f0ff;">System synchronisiert Daten...</div>
                        </div>
                    </div>

                    <!-- View: Create/Edit (VGT STATE OF ART UPGRADE) -->
                    <div id="view-create" class="vgt-view">
                        <div class="vgt-header-block">
                            <h2 id="vgt-form-title">Neues Buch anlegen</h2>
                            <p>Konfigurieren Sie Metadaten, Design-Parameter und Monetarisierungs-Bridges.</p>
                        </div>
                        
                        <form id="vgt-book-form" class="vgt-modern-form">
                            <input type="hidden" id="vgt-book-id" value="0">
                            
                            <div class="vgt-form-grid">
                                
                                <!-- LEFT COLUMN: CORE DATA -->
                                <div class="vgt-form-col">
                                    <div class="vgt-card">
                                        <div class="vgt-card-header">
                                            <div class="vgt-icon-wrap">📄</div>
                                            <h3>Core Dokument</h3>
                                        </div>
                                        <div class="vgt-card-body">
                                            <div class="vgt-input-group">
                                                <label>Titel der Publikation</label>
                                                <input type="text" id="vgt-title" class="vgt-input" placeholder="z.B. Omega Protocol Handbuch" required>
                                            </div>

                                            <div class="vgt-input-group">
                                                <label>Source (PDF URL)</label>
                                                <input type="url" id="vgt-pdf-url" class="vgt-input" placeholder="https://domain.com/buch.pdf" required>
                                                <small class="vgt-helper-text">Direktlink zur PDF-Datei. Wird hermetisch im Reader gerendert.</small>
                                            </div>

                                            <div class="vgt-divider"></div>

                                            <div class="vgt-toggle-row">
                                                <div class="vgt-toggle-info">
                                                    <label>PDF Download erlauben</label>
                                                    <span>Nutzer können das Dokument lokal speichern</span>
                                                </div>
                                                <label class="vgt-switch">
                                                    <input type="checkbox" id="vgt-download-enabled">
                                                    <span class="vgt-slider"></span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- RIGHT COLUMN: AESTHETICS & MONETIZATION -->
                                <div class="vgt-form-col">
                                    <div class="vgt-card">
                                        <div class="vgt-card-header">
                                            <div class="vgt-icon-wrap highlight">✨</div>
                                            <h3>Ästhetik & Trigger</h3>
                                        </div>
                                        <div class="vgt-card-body">
                                            <div class="vgt-input-group">
                                                <label>Call-to-Action Text</label>
                                                <input type="text" id="vgt-btn-text" class="vgt-input" value="Buch jetzt lesen">
                                            </div>
                                            
                                            <div class="vgt-row-grid">
                                                <div class="vgt-input-group">
                                                    <label>Akzentfarbe</label>
                                                    <div class="vgt-color-picker-wrapper">
                                                        <input type="color" id="vgt-btn-color" value="#00f0ff">
                                                    </div>
                                                </div>
                                                <div class="vgt-input-group">
                                                    <label>Render-Stil</label>
                                                    <div class="vgt-select-wrapper">
                                                        <select id="vgt-btn-style" class="vgt-select">
                                                            <option value="glass">Glassmorphism</option>
                                                            <option value="solid">Solid Accent</option>
                                                            <option value="outline">Neon Outline</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="vgt-card mt-20">
                                        <div class="vgt-card-header">
                                            <div class="vgt-icon-wrap store">🛒</div>
                                            <h3>Monetarisierung</h3>
                                        </div>
                                        <div class="vgt-card-body">
                                            <div class="vgt-toggle-row">
                                                <div class="vgt-toggle-info">
                                                    <label>Sales-Bridge aktivieren</label>
                                                    <span>Integriert einen 'Kaufen' Button direkt im Reader</span>
                                                </div>
                                                <label class="vgt-switch">
                                                    <input type="checkbox" id="vgt-buy-enabled">
                                                    <span class="vgt-slider"></span>
                                                </label>
                                            </div>
                                            
                                            <div id="vgt-buy-options" class="vgt-reveal-panel" style="display: none;">
                                                <div class="vgt-input-group">
                                                    <label>Shop URL (Target)</label>
                                                    <input type="url" id="vgt-buy-link" class="vgt-input" placeholder="https://amazon.de/...">
                                                </div>
                                                <div class="vgt-input-group">
                                                    <label>Button Beschriftung</label>
                                                    <input type="text" id="vgt-buy-text" class="vgt-input" value="Auf Amazon kaufen">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <div class="vgt-form-actions">
                                <button type="button" class="vgt-btn outline text-red" id="vgt-cancel-btn" style="display: none;">Operation abbrechen</button>
                                <button type="submit" class="vgt-btn primary glow">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:8px"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                    Logik in Matrix schreiben
                                </button>
                            </div>
                        </form>

                    </div>
                </section>
            </main>
        </div>
        <?php
        $nonce_attr = '';
        if (function_exists('vgt_get_csp_nonce')) {
            $nonce = vgt_get_csp_nonce();
            if (!empty($nonce)) {
                $nonce_attr = ' nonce="' . esc_attr($nonce) . '"';
            }
        }
        ?>
        <script<?php echo $nonce_attr; ?>>
        (function() {
            function syncAccent() {
                if (window.parent && window.parent.document && window.parent.document.documentElement) {
                    var parentEl = window.parent.document.documentElement;
                    var accentColor = parentEl.style.getPropertyValue('--vgt-accent-color');
                    var accentRgba15 = parentEl.style.getPropertyValue('--vgt-accent-rgba15');
                    var accentRgba8 = parentEl.style.getPropertyValue('--vgt-accent-rgba8');
                    
                    if (accentColor) {
                        document.documentElement.style.setProperty('--vgt-accent-color', accentColor);
                    }
                    if (accentRgba15) {
                        document.documentElement.style.setProperty('--vgt-accent-rgba15', accentRgba15);
                    }
                    if (accentRgba8) {
                        document.documentElement.style.setProperty('--vgt-accent-rgba8', accentRgba8);
                    }
                }
            }
            syncAccent();
            setInterval(syncAccent, 1000);
        })();
        </script>
        <?php
    }
}