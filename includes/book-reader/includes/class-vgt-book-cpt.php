<?php
declare(strict_types=1);

namespace VGT\BookReader\Core;

// Strikte Isolations-Direktive
if ( ! \defined( 'ABSPATH' ) ) {
    exit( 'VGT Protocol: Access Denied.' );
}

/**
 * Verwaltet die Datenstruktur (Custom Post Type) f«är die VGT B«ächer.
 */
final class BookCPT {

    public function init(): void {
        \add_action( 'init', [$this, 'register_post_type'] );
    }

    public function register_post_type(): void {
        $labels = [
            'name'                  => \_x( 'VGT B«ächer', 'Post type general name', 'vgt-book-reader' ),
            'singular_name'         => \_x( 'VGT Buch', 'Post type singular name', 'vgt-book-reader' ),
            'menu_name'             => \_x( 'VGT Reader', 'Admin Menu text', 'vgt-book-reader' ),
            'name_admin_bar'        => \_x( 'Buch', 'Add New on Toolbar', 'vgt-book-reader' ),
            'add_new'               => \__( 'Neues Buch', 'vgt-book-reader' ),
            'add_new_item'          => \__( 'Neues Buch hinzuf«ägen', 'vgt-book-reader' ),
            'new_item'              => \__( 'Neues Buch', 'vgt-book-reader' ),
            'edit_item'             => \__( 'Buch bearbeiten', 'vgt-book-reader' ),
            'view_item'             => \__( 'Buch ansehen', 'vgt-book-reader' ),
            'all_items'             => \__( 'Alle B«ächer', 'vgt-book-reader' ),
        ];

        $args = [
            'labels'             => $labels,
            'public'             => false, 
            'publicly_queryable' => false,
            'show_ui'            => false, // VGT Update: Natives WP-UI komplett deaktiviert
            'show_in_menu'       => false, // VGT Update: Eigenes Men«ä unterdr«äckt
            'query_var'          => false,
            'rewrite'            => false,
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => 20,
            'menu_icon'          => 'dashicons-book', // WP Native Icon
            'supports'           => ['title'], // Nur Titel, Rest l«£uft «äber Custom Meta
            'show_in_rest'       => false, // Block-Editor deaktivieren, erzwingt Clean-UI
        ];

        \register_post_type( 'vgt_book', $args );
    }
}