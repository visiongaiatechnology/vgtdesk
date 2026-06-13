<?php
declare(strict_types=1);

namespace VGT\BookReader\Frontend;

// Strikte Isolations-Direktive
if ( ! \defined( 'ABSPATH' ) ) {
    exit( 'VGT Protocol: Access Denied.' );
}

/**
 * Frontend Engine. Implementiert O(1) DOM-Overhead.
 * Status: Diamant Supreme (Gehärtete Iframe-Infrastruktur & Anti-Frame-Busting)
 */
final class FrontendEngine {

    private bool $is_reader_active_on_page = false;

    public function init(): void {
        \add_action( 'wp_enqueue_scripts', [$this, 'register_assets'] );
        \add_shortcode( 'vgt_reader', [$this, 'render_shortcode'] );
        \add_action( 'wp_footer', [$this, 'render_global_overlay'], 9999 );
    }

    public function register_assets(): void {
        $version = \defined('VGT_BOOK_READER_VERSION') ? VGT_BOOK_READER_VERSION : '1.0.1';
        
        \wp_register_style(
            'vgt-frontend-style',
            VGT_BOOK_READER_URL . 'assets/css/vgt-frontend.css',
            [],
            $version
        );

        \wp_register_script(
            'vgt-frontend-script',
            VGT_BOOK_READER_URL . 'assets/js/vgt-frontend.js',
            [],
            $version,
            true
        );
    }

    /**
     * @param array<string, mixed>|string $atts
     */
    public function render_shortcode( $atts ): string {
        if ( ! \is_array( $atts ) ) {
            $atts = [];
        }

        $atts = \shortcode_atts( ['id' => 0], $atts, 'vgt_reader' );
        $post_id = (int) $atts['id'];

        if ( $post_id === 0 || \get_post_type( $post_id ) !== 'vgt_book' ) {
            return '';
        }

        $this->is_reader_active_on_page = true;
        \wp_enqueue_style( 'vgt-frontend-style' );
        \wp_enqueue_script( 'vgt-frontend-script' );

        $title            = (string) \get_the_title( $post_id );
        $pdf_url          = (string) \get_post_meta( $post_id, '_vgt_pdf_url', true );
        $btn_text         = (string) \get_post_meta( $post_id, '_vgt_btn_text', true ) ?: 'Lesen';
        $btn_color        = (string) \get_post_meta( $post_id, '_vgt_btn_color', true ) ?: '#00f0ff';
        
        // Style Whitelist Check
        $allowed_styles   = ['glass', 'solid', 'outline'];
        $raw_style        = (string) \get_post_meta( $post_id, '_vgt_btn_style', true );
        $btn_style        = \in_array( $raw_style, $allowed_styles, true ) ? $raw_style : 'glass';
        
        $buy_enabled      = (string) \get_post_meta( $post_id, '_vgt_buy_enabled', true ) === 'yes' ? 'true' : 'false';
        $buy_link         = (string) \get_post_meta( $post_id, '_vgt_buy_link', true );
        $buy_text         = (string) \get_post_meta( $post_id, '_vgt_buy_text', true );
        $download_enabled = (string) \get_post_meta( $post_id, '_vgt_download_enabled', true ) === 'yes' ? 'true' : 'false';

        \ob_start();
        ?>
        <button 
            class="vgt-trigger-btn vgt-style-<?php echo \esc_attr( $btn_style ); ?>"
            style="--vgt-accent: <?php echo \esc_attr( $btn_color ); ?>;"
            data-title="<?php echo \esc_attr( $title ); ?>"
            data-pdf="<?php echo \esc_url( $pdf_url ); ?>"
            data-buy-enabled="<?php echo \esc_attr( $buy_enabled ); ?>"
            data-buy-link="<?php echo \esc_url( $buy_link ); ?>"
            data-buy-text="<?php echo \esc_attr( $buy_text ); ?>"
            data-download-enabled="<?php echo \esc_attr( $download_enabled ); ?>"
        >
            <span class="vgt-btn-glow"></span>
            <svg class="vgt-btn-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>
            <span class="vgt-btn-label"><?php echo \esc_html( $btn_text ); ?></span>
        </button>
        <?php
        return (string) \ob_get_clean();
    }

    public function render_global_overlay(): void {
        if ( ! $this->is_reader_active_on_page ) {
            return;
        }
        ?>
        <div id="vgt-reader-overlay" class="vgt-reader-hidden">
            <div class="vgt-ambient-orb"></div>
            
            <header class="vgt-reader-topbar">
                <div class="vgt-reader-title-group">
                    <div class="vgt-icon-book">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>
                    </div>
                    <h2 id="vgt-reader-title"></h2>
                </div>
                
                <div class="vgt-reader-actions">
                    <a id="vgt-reader-buy-btn" href="#" target="_blank" rel="noopener noreferrer" class="vgt-action-btn store" style="display: none;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                        <span id="vgt-reader-buy-text">Kaufen</span>
                    </a>
                    
                    <a id="vgt-reader-download-btn" href="#" download rel="noopener noreferrer" class="vgt-action-btn default" style="display: none;" title="Download PDF">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                    </a>
                    
                    <div class="vgt-action-divider"></div>
                    
                    <button id="vgt-reader-close" class="vgt-action-btn danger" title="Schließen (ESC)">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                </div>
            </header>

            <main class="vgt-reader-viewport">
                <div id="vgt-reader-loader" class="vgt-loader-wrapper">
                    <div class="vgt-spinner"></div>
                    <span>Initialisiere hermetisches Dokument...</span>
                </div>
                
                <div class="vgt-iframe-container">
                    <!-- VGT FIX: Entfernung des sandbox-Attributs. Chromium PDF-Viewer benötigt Plugin-Rechte, die durch Sandbox blockiert werden. -->
                    <iframe id="vgt-reader-iframe" src="" title="VGT PDF Reader Engine" referrerpolicy="no-referrer" loading="lazy"></iframe>
                </div>
            </main>
        </div>
        <?php
    }
}