<?php
declare(strict_types=1);

namespace VGT\BookReader\Admin;

// Strikte Isolations-Direktive
if ( ! \defined( 'ABSPATH' ) ) {
    exit( 'VGT Protocol: Access Denied.' );
}

/**
 * VGT AJAX API Controller.
 * Status: Diamant VGT Supreme (Mathematisch gehärtet gegen Type Juggling & Blind IDOR)
 */
final class API {

    public function init(): void {
        \add_action( 'wp_ajax_vgt_save_book', [$this, 'save_book'] );
        \add_action( 'wp_ajax_vgt_get_books', [$this, 'get_books'] );
        \add_action( 'wp_ajax_vgt_delete_book', [$this, 'delete_book'] );
    }

    private function verify_security(): void {
        if ( ! \check_ajax_referer( 'vgt_dashboard_action', 'nonce', false ) ) {
            \wp_send_json_error( 'VGT Security Exception: Invalid Cryptographic Nonce.', 403 );
        }
        if ( ! \current_user_can( 'manage_options' ) ) {
            \wp_send_json_error( 'VGT Security Exception: Unauthorized Clearance Level.', 403 );
        }
    }

    /**
     * VGT PLATIN FIX: Verhindert PHP 8.1+ Fatal Errors durch Array-Injection via POST.
     */
    private function get_post_string( string $key, string $default = '' ): string {
        return isset( $_POST[$key] ) && \is_string( $_POST[$key] ) ? $_POST[$key] : $default;
    }

    private function get_post_bool( string $key ): bool {
        return isset( $_POST[$key] ) && \is_string( $_POST[$key] ) && $_POST[$key] === 'true';
    }

    public function save_book(): void {
        $this->verify_security();

        $raw_post_id = $this->get_post_string( 'book_id', '0' );
        $post_id     = (int) $raw_post_id;
        
        $title = \sanitize_text_field( \wp_unslash( $this->get_post_string( 'title', 'Unbenanntes Buch' ) ) );

        // Strict URL Validation
        $raw_pdf_url = \wp_unslash( $this->get_post_string( 'pdf_url' ) );
        $pdf_url     = '';

        if ( $raw_pdf_url !== '' ) {
        $pdf_url = \wp_http_validate_url( $raw_pdf_url );
        if ( ! $pdf_url ) {
        \wp_send_json_error( 'VGT Security Exception: Invalid or malicious PDF URL schema.', 400 );
         }
        
        // VGT DIAMANT SUPREME FIX: Path & Extension Enforcement
        $parsed_url = \parse_url( $pdf_url, PHP_URL_PATH );
        if ( $parsed_url === null || \strtolower( \pathinfo( $parsed_url, PATHINFO_EXTENSION ) ) !== 'pdf' ) {
        \wp_send_json_error( 'VGT Security Exception: Target payload must be a strictly typed .pdf entity.', 400 );
        }
        }

        if ( $title === '' ) {
            $title = 'VGT Encrypted Node';
        }

        $post_data = [
            'post_title'  => $title,
            'post_type'   => 'vgt_book',
            'post_status' => 'publish',
        ];

        if ( $post_id > 0 ) {
            // Hermetischer Entity-Check verhindert Cross-Entity Overrides
            $existing_type = \get_post_type( $post_id );
            if ( $existing_type !== 'vgt_book' ) {
                \wp_send_json_error( 'VGT Security Exception: Cross-Entity Override Attempt blocked. Critical Alert Logged.', 403 );
            }
            
            $post_data['ID'] = $post_id;
            $updated_id = \wp_update_post( $post_data );
        } else {
            $updated_id = \wp_insert_post( $post_data );
        }

        if ( \is_wp_error( $updated_id ) || $updated_id === 0 ) {
            \wp_send_json_error( 'Database Synchronization Error', 500 );
        }

        // Whitelist Enum für Render-Stil (Verhindert CSS-Injection)
        $allowed_styles = ['glass', 'solid', 'outline'];
        $btn_style      = \sanitize_text_field( \wp_unslash( $this->get_post_string( 'btn_style', 'glass' ) ) );
        if ( ! \in_array( $btn_style, $allowed_styles, true ) ) {
            $btn_style = 'glass';
        }

        // Validate and sanitize the buy link URL
        $raw_buy_link = \wp_unslash( $this->get_post_string( 'buy_link' ) );
        $buy_link     = '';
        if ( $raw_buy_link !== '' ) {
            $buy_link = \wp_http_validate_url( $raw_buy_link );
            if ( ! $buy_link ) {
                \wp_send_json_error( 'VGT Security Exception: Invalid or malicious buy URL schema.', 400 );
            }
        }

        // Meta Daten hermetisch versiegeln (Alle Variablen sind sanitisiert & typensicher)
        $fields = [
            '_vgt_pdf_url'          => \esc_url_raw( (string) $pdf_url ),
            '_vgt_btn_text'         => \sanitize_text_field( \wp_unslash( $this->get_post_string( 'btn_text' ) ) ),
            '_vgt_btn_color'        => \sanitize_hex_color( \wp_unslash( $this->get_post_string( 'btn_color', '#00f0ff' ) ) ) ?: '#00f0ff',
            '_vgt_btn_style'        => $btn_style,
            '_vgt_buy_enabled'      => $this->get_post_bool( 'buy_enabled' ) ? 'yes' : 'no',
            '_vgt_buy_link'         => \esc_url_raw( (string) $buy_link ),
            '_vgt_buy_text'         => \sanitize_text_field( \wp_unslash( $this->get_post_string( 'buy_text' ) ) ),
            '_vgt_download_enabled' => $this->get_post_bool( 'download_enabled' ) ? 'yes' : 'no',
        ];

        foreach ( $fields as $key => $value ) {
            \update_post_meta( $updated_id, $key, $value );
        }

        \wp_send_json_success( ['id' => $updated_id, 'message' => 'Diamant-Speicherung erfolgreich.'] );
    }

    public function get_books(): void {
        $this->verify_security();

        $books = \get_posts([
            'post_type'      => 'vgt_book',
            'posts_per_page' => 500,
            'post_status'    => 'publish',
            'orderby'        => 'date',
            'order'          => 'DESC'
        ]);

        $payload = [];
        foreach ( $books as $book ) {
            $payload[] = [
                'id'       => $book->ID,
                'title'    => \esc_html( $book->post_title ),
                'pdf_url'  => \esc_url_raw( \get_post_meta( $book->ID, '_vgt_pdf_url', true ) ),
                'btn_text' => \esc_html( \get_post_meta( $book->ID, '_vgt_btn_text', true ) ),
            ];
        }

        \wp_send_json_success( $payload );
    }

    public function delete_book(): void {
        $this->verify_security();

        $raw_post_id = $this->get_post_string( 'book_id', '0' );
        $post_id     = (int) $raw_post_id;
        
        // Hermetischer Entity-Check
        if ( $post_id > 0 && \get_post_type( $post_id ) === 'vgt_book' ) {
            \wp_delete_post( $post_id, true );
            \wp_send_json_success( 'Entity mathematically destroyed.' );
        }
        
        \wp_send_json_error( 'Invalid Entity or access violation.' );
    }
}