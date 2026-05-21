<?php
/**
 * Module: VGT Iframe Transformer
 * Description: Transformiert klassische WordPress-Tabellenansichten (Beiträge, Seiten, Kommentare, Plugins, Themes) in hochmoderne UI-Kartenlayouts im Iframe-Kontext.
 * Version: 1.2.0
 * Author: VisionGaiaTechnology
 */

declare(strict_types=1);

namespace VisionGaia\WPDesk;

if (!defined('ABSPATH')) {
    exit;
}

final class IframeTransformer
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
        // Hooks für die Stil-Injektion
        add_action('admin_head', [$this, 'inject_transform_styles'], 999);

        // NATIVE URL-SICHERHEITSGÜRTEL: Verhindert das "Ausbrechen" aus dem Iframe-Kontext bei Klicks & Redirects
        add_filter('admin_url', [$this, 'append_iframe_param_to_admin_urls'], 999, 3);
        add_filter('wp_redirect', [$this, 'append_iframe_param_to_redirect_urls'], 999, 1);
    }

    /**
     * Prüft, ob wir uns im VGT-Iframe-Kontext befinden.
     */
    private function is_iframe_context(): bool
    {
        if (isset($_GET['vgt_iframe']) && $_GET['vgt_iframe'] === 'true') {
            return true;
        }

        if (isset($_SERVER['HTTP_SEC_FETCH_DEST']) && $_SERVER['HTTP_SEC_FETCH_DEST'] === 'iframe') {
            return true;
        }

        if (isset($_SERVER['HTTP_REFERER'])) {
            $referer_query = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_QUERY);
            if ($referer_query && str_contains($referer_query, 'vgt_iframe=true')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Filtert alle Admin-URLs und fügt den Iframe-Parameter hinzu, um den State zu erhalten.
     */
    public function append_iframe_param_to_admin_urls(string $url, string $path, ?int $blog_id = null): string
    {
        if (!$this->is_iframe_context()) {
            return $url;
        }

        // Überspringe AJAX-Schnittstellen und Uploads, um Konflikte zu vermeiden
        if (str_contains($path, 'admin-ajax.php') || str_contains($path, 'async-upload.php')) {
            return $url;
        }

        return add_query_arg('vgt_iframe', 'true', $url);
    }

    /**
     * Filtert alle Server-Redirects und erhält den Iframe-Status aufrecht.
     */
    public function append_iframe_param_to_redirect_urls(string $location): string
    {
        if (!$this->is_iframe_context()) {
            return $location;
        }

        if (!str_contains($location, 'wp-admin')) {
            return $location;
        }

        return add_query_arg('vgt_iframe', 'true', $location);
    }

    /**
     * Injiziert das hochmoderne CSS-Layout-System für Beitrags-, Seiten-, Kommentar-, Plugin- und Theme-Listen.
     */
    public function inject_transform_styles(): void
    {
        if (!$this->is_iframe_context()) {
            return;
        }

        echo '<!-- VGT MULTI-SCREEN IFRAME TRANSFORMER ACTIVE -->';
        echo '<style>
            /* ==========================================================================
               1. GEMEINSAME DESIGN-TOKENS FÜR ALLE LISTENTABELLEN (RESET)
               ========================================================================== */
            .edit-php .wrap, 
            .edit-comments-php .wrap, 
            .plugins-php .wrap,
            .themes-php .wrap {
                max-width: 1400px !important;
                margin: 0 auto !important;
                padding: 16px !important;
            }

            /* Kopfzeilen-Optimierung */
            .edit-php h1.wp-heading-inline, 
            .edit-comments-php h1.wp-heading-inline, 
            .plugins-php h1.wp-heading-inline,
            .themes-php .wrap h1 {
                font-size: 22px !important;
                font-weight: 800 !important;
                letter-spacing: -0.025em !important;
                color: #ffffff !important;
                margin-bottom: 20px !important;
                display: inline-block !important;
            }

            /* Buttons & Aktionen */
            .edit-php .page-title-action, 
            .edit-comments-php .page-title-action, 
            .plugins-php .page-title-action,
            .themes-php .page-title-action,
            .themes-php .add-new-theme {
                background: #4f46e5 !important;
                border: none !important;
                color: #ffffff !important;
                padding: 6px 16px !important;
                border-radius: 8px !important;
                font-size: 11px !important;
                font-weight: 700 !important;
                text-decoration: none !important;
                display: inline-block !important;
                margin-left: 12px !important;
                transition: all 0.15s ease !important;
            }
            .edit-php .page-title-action:hover,
            .themes-php .page-title-action:hover {
                background: #4338ca !important;
                transform: translateY(-1px);
            }

            /* Suchmasken-Styling */
            .edit-php p.search-box, 
            .edit-comments-php p.search-box, 
            .plugins-php p.search-box,
            .themes-php p.search-box {
                float: none !important;
                margin: 16px 0 !important;
                display: flex !important;
                gap: 8px !important;
                width: 100% !important;
                max-width: 400px !important;
            }
            p.search-box input[type="search"] {
                background-color: #0f172a !important;
                border: 1px solid rgba(255, 255, 255, 0.1) !important;
                border-radius: 8px !important;
                color: #f1f5f9 !important;
                padding: 6px 12px !important;
                font-size: 12px !important;
                flex: 1 !important;
                height: 36px !important;
            }
            p.search-box input[type="submit"] {
                background: #1e293b !important;
                border: 1px solid rgba(255, 255, 255, 0.1) !important;
                color: #ffffff !important;
                border-radius: 8px !important;
                padding: 0 16px !important;
                font-weight: 700 !important;
                font-size: 11px !important;
                cursor: pointer !important;
                height: 36px !important;
                transition: all 0.15s ease !important;
            }
            p.search-box input[type="submit"]:hover {
                background: #334155 !important;
            }

            /* Segments-Pill-Navigation (Filterlinks) */
            .edit-php .subsubsub, 
            .edit-comments-php .subsubsub, 
            .plugins-php .subsubsub,
            .themes-php .subsubsub {
                display: flex !important;
                flex-wrap: wrap !important;
                gap: 6px !important;
                list-style: none !important;
                padding: 0 !important;
                margin: 16px 0 24px 0 !important;
                font-size: 11px !important;
            }
            .edit-php .subsubsub li, 
            .edit-comments-php .subsubsub li, 
            .plugins-php .subsubsub li,
            .themes-php .subsubsub li {
                display: inline-block !important;
                margin: 0 !important;
                color: transparent !important;
            }
            .edit-php .subsubsub li a, 
            .edit-comments-php .subsubsub li a, 
            .plugins-php .subsubsub li a,
            .themes-php .subsubsub li a {
                display: inline-block !important;
                background: rgba(255, 255, 255, 0.02) !important;
                border: 1px solid rgba(255, 255, 255, 0.05) !important;
                color: #94a3b8 !important;
                padding: 6px 14px !important;
                border-radius: 20px !important;
                text-decoration: none !important;
                transition: all 0.15s ease !important;
            }
            .edit-php .subsubsub li a.current, 
            .edit-comments-php .subsubsub li a.current, 
            .plugins-php .subsubsub li a.current,
            .themes-php .subsubsub li a.current {
                background: rgba(99, 102, 241, 0.15) !important;
                border-color: #6366f1 !important;
                color: #ffffff !important;
                font-weight: 700 !important;
            }
            .edit-php .subsubsub li a:hover:not(.current) {
                background: rgba(255, 255, 255, 0.06) !important;
                color: #ffffff !important;
            }
            .edit-php .subsubsub .count {
                color: #64748b !important;
                font-weight: normal !important;
                margin-left: 4px !important;
            }

            /* UNZERSTÖRBARER GRID-TRANSFORMER: Weicht die Tabellenstrukturen auf */
            body.edit-php table.wp-list-table, 
            body.post-type-post table.wp-list-table,
            body.post-type-page table.wp-list-table,
            body.edit-comments-php table.wp-list-table, 
            body.plugins-php table.wp-list-table {
                display: block !important;
                width: 100% !important;
                background: transparent !important;
                border: none !important;
                box-shadow: none !important;
            }
            
            /* Tbody wird zum direkten unfehlbaren Grid-Container */
            body.edit-php table.wp-list-table tbody,
            body.post-type-post table.wp-list-table tbody,
            body.post-type-page table.wp-list-table tbody,
            body.edit-comments-php table.wp-list-table tbody,
            body.plugins-php table.wp-list-table tbody,
            table.wp-list-table #the-list {
                display: grid !important;
                grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)) !important;
                gap: 18px !important;
                width: 100% !important;
                box-sizing: border-box !important;
            }
            
            table.wp-list-table thead, 
            table.wp-list-table tfoot {
                display: none !important;
            }

            /* Row-Aktionstasten im Card-Format */
            .row-actions {
                display: flex !important;
                flex-wrap: wrap !important;
                gap: 6px !important;
                opacity: 1 !important;
                position: static !important;
                margin: 12px 0 0 0 !important;
                padding: 0 !important;
            }
            .row-actions span {
                display: inline-block !important;
                margin: 0 !important;
                border: none !important;
                padding: 0 !important;
            }
            .row-actions span a {
                display: inline-block !important;
                background: rgba(255, 255, 255, 0.04) !important;
                border: 1px solid rgba(255, 255, 255, 0.08) !important;
                color: #cbd5e1 !important;
                padding: 5px 12px !important;
                border-radius: 8px !important;
                font-size: 11px !important;
                font-weight: 600 !important;
                text-decoration: none !important;
                transition: all 0.15s ease !important;
            }
            .row-actions span a:hover {
                background: rgba(255, 255, 255, 0.1) !important;
                border-color: rgba(255, 255, 255, 0.2) !important;
            }

            /* Tablenav & Bulk Actions */
            .tablenav {
                background: transparent !important;
                border: none !important;
                height: auto !important;
                min-height: 0 !important;
                margin: 12px 0 !important;
                padding: 0 !important;
            }
            .tablenav .actions {
                display: flex !important;
                gap: 8px !important;
                align-items: center !important;
            }
            .tablenav select {
                background-color: #0f172a !important;
                border: 1px solid rgba(255, 255, 255, 0.1) !important;
                color: #cbd5e1 !important;
                border-radius: 8px !important;
                padding: 4px 10px !important;
                font-size: 12px !important;
                height: 32px !important;
            }
            .tablenav .button {
                background: #1e293b !important;
                border: 1px solid rgba(255, 255, 255, 0.1) !important;
                color: #ffffff !important;
                border-radius: 8px !important;
                height: 32px !important;
                line-height: 30px !important;
                padding: 0 12px !important;
                font-size: 11px !important;
                font-weight: 700 !important;
                cursor: pointer !important;
            }
            .tablenav .button:hover {
                background: #334155 !important;
            }
            .tablenav .view-switch {
                display: none !important;
            }

            /* Checkboxen verstecken für makelloses Design */
            th.check-column, td.check-column {
                display: none !important;
            }


            /* ==========================================================================
               2. BEITRÄGE & SEITEN TRANSFORMER (.edit-php & .post-type-*)
               ========================================================================== */
            body.edit-php table.wp-list-table tr,
            body.post-type-post table.wp-list-table tr,
            body.post-type-page table.wp-list-table tr {
                display: flex !important;
                flex-direction: column !important;
                background: rgba(15, 23, 42, 0.5) !important;
                border: 1px solid rgba(255, 255, 255, 0.08) !important;
                border-radius: 14px !important;
                padding: 20px !important;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2) !important;
                transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease !important;
                box-sizing: border-box !important;
                position: relative !important;
                margin-bottom: 0 !important;
            }
            body.edit-php table.wp-list-table tr:hover,
            body.post-type-post table.wp-list-table tr:hover,
            body.post-type-page table.wp-list-table tr:hover {
                transform: translateY(-2px) !important;
                border-color: rgba(99, 102, 241, 0.3) !important;
                box-shadow: 0 8px 30px rgba(99, 102, 241, 0.12) !important;
            }

            /* Seitliche Akzente für Status */
            body.edit-php table.wp-list-table tr,
            body.post-type-post table.wp-list-table tr,
            body.post-type-page table.wp-list-table tr {
                border-left: 4px solid #10b981 !important; /* Standard: Veröffentlicht */
            }
            body.edit-php table.wp-list-table tr.status-draft,
            body.post-type-post table.wp-list-table tr.status-draft,
            body.post-type-page table.wp-list-table tr.status-draft {
                border-left: 4px solid #f59e0b !important; /* Entwurf */
                opacity: 0.85 !important;
            }
            body.edit-php table.wp-list-table tr.status-future,
            body.post-type-post table.wp-list-table tr.status-future,
            body.post-type-page table.wp-list-table tr.status-future {
                border-left: 4px solid #06b6d4 !important; /* Geplant */
            }
            body.edit-php table.wp-list-table tr.status-private,
            body.post-type-post table.wp-list-table tr.status-private,
            body.post-type-page table.wp-list-table tr.status-private {
                border-left: 4px solid #ef4444 !important; /* Privat */
            }

            /* Zellen aufräumen */
            body.edit-php table.wp-list-table td,
            body.post-type-post table.wp-list-table td,
            body.post-type-page table.wp-list-table td {
                display: block !important;
                padding: 0 !important;
                background: transparent !important;
                border: none !important;
                width: 100% !important;
                color: #94a3b8 !important;
                font-size: 12px !important;
            }

            /* Titel-Zelle */
            body.edit-php table.wp-list-table td.column-title,
            body.post-type-post table.wp-list-table td.column-title,
            body.post-type-page table.wp-list-table td.column-title {
                margin-bottom: 8px !important;
            }
            body.edit-php table.wp-list-table td.column-title strong a.row-title,
            body.post-type-post table.wp-list-table td.column-title strong a.row-title,
            body.post-type-page table.wp-list-table td.column-title strong a.row-title {
                font-size: 16px !important;
                font-weight: 700 !important;
                color: #ffffff !important;
                letter-spacing: -0.015em !important;
                text-decoration: none !important;
                transition: color 0.15s ease !important;
            }
            body.edit-php table.wp-list-table td.column-title strong a.row-title:hover,
            body.post-type-post table.wp-list-table td.column-title strong a.row-title:hover,
            body.post-type-page table.wp-list-table td.column-title strong a.row-title:hover {
                color: #818cf8 !important;
            }

            /* Post-Status Badges */
            body.edit-php .post-state,
            body.post-type-post .post-state,
            body.post-type-page .post-state {
                background: rgba(255, 255, 255, 0.06) !important;
                border: 1px solid rgba(255, 255, 255, 0.1) !important;
                color: #e2e8f0 !important;
                font-size: 9px !important;
                padding: 2px 8px !important;
                border-radius: 4px !important;
                font-weight: 700 !important;
                text-transform: uppercase !important;
                margin-left: 6px !important;
                display: inline-block !important;
                vertical-align: middle !important;
            }

            /* Kategorien / Tags (Pill-Styling) */
            body.edit-php table.wp-list-table td.column-categories,
            body.edit-php table.wp-list-table td.column-tags,
            body.post-type-post table.wp-list-table td.column-categories,
            body.post-type-post table.wp-list-table td.column-tags,
            body.post-type-page table.wp-list-table td.column-categories,
            body.post-type-page table.wp-list-table td.column-tags {
                margin: 6px 0 !important;
                font-size: 11px !important;
            }
            body.edit-php table.wp-list-table td.column-categories a,
            body.edit-php table.wp-list-table td.column-tags a,
            body.post-type-post table.wp-list-table td.column-categories a,
            body.post-type-post table.wp-list-table td.column-tags a,
            body.post-type-page table.wp-list-table td.column-categories a,
            body.post-type-page table.wp-list-table td.column-tags a {
                display: inline-block !important;
                background: rgba(99, 102, 241, 0.08) !important;
                color: #818cf8 !important;
                padding: 2px 8px !important;
                border-radius: 6px !important;
                margin-right: 4px !important;
                margin-bottom: 4px !important;
                text-decoration: none !important;
                font-weight: 500 !important;
            }

            /* Autor- & Datumszeile */
            body.edit-php table.wp-list-table td.column-author,
            body.edit-php table.wp-list-table td.column-date,
            body.post-type-post table.wp-list-table td.column-author,
            body.post-type-post table.wp-list-table td.column-date,
            body.post-type-page table.wp-list-table td.column-author,
            body.post-type-page table.wp-list-table td.column-date {
                font-size: 11px !important;
                color: #64748b !important;
                margin-top: 4px !important;
            }

            /* Kommentare-Zähler in Cards */
            body.edit-php table.wp-list-table td.column-comments,
            body.post-type-post table.wp-list-table td.column-comments,
            body.post-type-page table.wp-list-table td.column-comments {
                position: absolute !important;
                top: 20px !important;
                right: 20px !important;
                width: auto !important;
            }
            body.edit-php .post-com-count,
            body.post-type-post .post-com-count,
            body.post-type-page .post-com-count {
                background: rgba(255, 255, 255, 0.05) !important;
                border: 1px solid rgba(255, 255, 255, 0.08) !important;
                border-radius: 8px !important;
                padding: 4px 8px !important;
                display: flex !important;
                align-items: center !important;
                gap: 4px !important;
                color: #cbd5e1 !important;
                font-size: 11px !important;
                font-weight: 600 !important;
            }


            /* ==========================================================================
               3. KOMMENTARE FEED-TRANSFORMER (.edit-comments-php)
               ========================================================================== */
            body.edit-comments-php table.wp-list-table tr {
                display: flex !important;
                flex-direction: column !important;
                background: rgba(15, 23, 42, 0.5) !important;
                border: 1px solid rgba(255, 255, 255, 0.08) !important;
                border-radius: 14px !important;
                padding: 20px !important;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2) !important;
                transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease !important;
                box-sizing: border-box !important;
            }
            body.edit-comments-php table.wp-list-table tr:hover {
                transform: translateY(-2px) !important;
                border-color: rgba(99, 102, 241, 0.3) !important;
                box-shadow: 0 8px 30px rgba(99, 102, 241, 0.1) !important;
            }

            /* Status-Bänder für Kommentare */
            body.edit-comments-php table.wp-list-table tr {
                border-left: 4px solid #10b981 !important; /* Freigegeben */
            }
            body.edit-comments-php table.wp-list-table tr.unapproved {
                border-left: 4px solid #f59e0b !important; /* Wartend */
                background: rgba(245, 158, 11, 0.03) !important;
            }
            body.edit-comments-php table.wp-list-table tr.spam {
                border-left: 4px solid #ef4444 !important; /* Spam */
                opacity: 0.6 !important;
            }

            /* Zellen-Bereinigung */
            body.edit-comments-php table.wp-list-table td {
                display: block !important;
                padding: 0 !important;
                background: transparent !important;
                border: none !important;
                width: 100% !important;
                color: #cbd5e1 !important;
            }

            /* Autor-Block */
            body.edit-comments-php td.column-author {
                display: flex !important;
                align-items: center !important;
                gap: 12px !important;
                margin-bottom: 12px !important;
                width: 100% !important;
            }
            body.edit-comments-php td.column-author strong {
                font-size: 14px !important;
                color: #ffffff !important;
            }
            body.edit-comments-php td.column-author .avatar {
                border-radius: 50% !important;
                border: 2px solid rgba(255, 255, 255, 0.1) !important;
            }

            /* Kommentar-Text */
            body.edit-comments-php td.column-comment {
                font-size: 13px !important;
                line-height: 1.6 !important;
                color: #94a3b8 !important;
            }
            body.edit-comments-php td.column-comment p {
                margin: 0 0 10px 0 !important;
            }


            /* ==========================================================================
               4. PLUGINS APP-STORE TRANSFORMER (.plugins-php)
               ========================================================================== */
            body.plugins-php table.wp-list-table.plugins tr {
                display: flex !important;
                flex-direction: column !important;
                background: rgba(15, 23, 42, 0.5) !important;
                border: 1px solid rgba(255, 255, 255, 0.08) !important;
                border-radius: 14px !important;
                padding: 20px !important;
                margin-bottom: 0 !important;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2) !important;
                transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease !important;
                position: relative !important;
                box-sizing: border-box !important;
            }
            body.plugins-php table.wp-list-table.plugins tr:hover {
                transform: translateY(-2px) !important;
                border-color: rgba(99, 102, 241, 0.3) !important;
                box-shadow: 0 8px 30px rgba(99, 102, 241, 0.1) !important;
            }
            body.plugins-php table.wp-list-table.plugins tr.active {
                border-left: 4px solid #10b981 !important;
            }
            body.plugins-php table.wp-list-table.plugins tr.inactive {
                border-left: 4px solid #475569 !important;
                opacity: 0.9 !important;
            }

            /* Update Notice Cards */
            body.plugins-php table.wp-list-table.plugins tr.plugin-update-tr {
                background: rgba(245, 158, 11, 0.04) !important;
                border: 1px dashed rgba(245, 158, 11, 0.25) !important;
                border-left: 4px solid #f59e0b !important;
                border-radius: 12px !important;
                padding: 14px !important;
            }


            /* ==========================================================================
               5. DESIGN / THEMES TRANSFORMER (.themes-php)
               ========================================================================== */
            body.themes-php .theme-browser .themes {
                display: grid !important;
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)) !important;
                gap: 24px !important;
                padding: 10px 0 !important;
                background: transparent !important;
            }
            
            body.themes-php .theme-browser {
                background: transparent !important;
                padding: 0 !important;
                margin: 0 !important;
                box-shadow: none !important;
            }
            
            body.themes-php .theme-browser .theme {
                background: rgba(15, 23, 42, 0.5) !important;
                border: 1px solid rgba(255, 255, 255, 0.08) !important;
                border-radius: 16px !important;
                overflow: hidden !important;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2) !important;
                transition: transform 0.22s cubic-bezier(0.16, 1, 0.3, 1), border-color 0.2s ease, box-shadow 0.2s ease !important;
                width: 100% !important;
                margin: 0 !important;
                box-sizing: border-box !important;
                position: relative !important;
            }
            body.themes-php .theme-browser .theme:hover {
                transform: translateY(-4px) !important;
                border-color: rgba(99, 102, 241, 0.4) !important;
                box-shadow: 0 12px 30px rgba(99, 102, 241, 0.15) !important;
            }
            body.themes-php .theme-browser .theme .theme-screenshot {
                border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
                aspect-ratio: 4/3 !important;
                object-fit: cover !important;
                width: 100% !important;
                height: auto !important;
            }
            body.themes-php .theme-browser .theme .theme-author {
                display: none !important;
            }
            body.themes-php .theme-browser .theme .theme-id-container {
                background: rgba(15, 23, 42, 0.8) !important;
                padding: 14px 16px !important;
                display: flex !important;
                justify-content: space-between !important;
                align-items: center !important;
                border-top: 1px solid rgba(255, 255, 255, 0.04) !important;
            }
            body.themes-php .theme-browser .theme .theme-name {
                font-size: 14px !important;
                font-weight: 700 !important;
                color: #ffffff !important;
            }
            
            /* Aktives Theme markieren */
            body.themes-php .theme-browser .theme.active {
                border: 2px solid #10b981 !important;
                box-shadow: 0 4px 24px rgba(16, 185, 129, 0.15) !important;
            }
            body.themes-php .theme-browser .theme.active .theme-id-container {
                background: rgba(16, 185, 129, 0.1) !important;
            }
            body.themes-php .theme-browser .theme.active .theme-name {
                color: #34d399 !important;
            }

            /* Theme Hover Actions overlay */
            body.themes-php .theme-browser .theme .theme-actions {
                background: rgba(9, 13, 22, 0.85) !important;
                opacity: 0 !important;
                transition: opacity 0.2s ease !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                gap: 10px !important;
                position: absolute !important;
                inset: 0 !important;
                z-index: 10 !important;
                padding: 20px !important;
                box-sizing: border-box !important;
            }
            body.themes-php .theme-browser .theme:hover .theme-actions {
                opacity: 1 !important;
            }
            
            /* Theme Buttons stylen */
            body.themes-php .theme-browser .theme .theme-actions .button {
                background: #4f46e5 !important;
                border: none !important;
                color: #ffffff !important;
                padding: 8px 16px !important;
                border-radius: 8px !important;
                font-size: 11px !important;
                font-weight: 700 !important;
                box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3) !important;
                cursor: pointer !important;
                transition: background-color 0.15s ease !important;
            }
            body.themes-php .theme-browser .theme .theme-actions .button:hover {
                background: #4338ca !important;
            }
            body.themes-php .theme-browser .theme .theme-actions .button.activate {
                background: #10b981 !important;
                box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3) !important;
            }
            body.themes-php .theme-browser .theme .theme-actions .button.activate:hover {
                background: #059669 !important;
            }
            body.themes-php .theme-browser .theme .theme-actions .button.load-customize {
                background: #1e293b !important;
                border: 1px solid rgba(255, 255, 255, 0.1) !important;
                box-shadow: none !important;
            }
            body.themes-php .theme-browser .theme .theme-actions .button.load-customize:hover {
                background: #334155 !important;
            }

            /* Theme hinzufügen Kachel */
            body.themes-php .theme-browser .theme.add-new-theme {
                border: 2px dashed rgba(255, 255, 255, 0.12) !important;
                background: rgba(15, 23, 42, 0.25) !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                min-height: 250px !important;
                cursor: pointer !important;
            }
            body.themes-php .theme-browser .theme.add-new-theme:hover {
                border-color: #6366f1 !important;
                background: rgba(99, 102, 241, 0.05) !important;
            }

            /* Theme Details Overlay/Popups stylen */
            body.themes-php .theme-overlay {
                background: rgba(9, 13, 22, 0.98) !important;
                backdrop-filter: blur(25px) !important;
                -webkit-backdrop-filter: blur(25px) !important;
                border: 1px solid rgba(255, 255, 255, 0.1) !important;
                border-radius: 16px !important;
                box-shadow: 0 30px 60px -15px rgba(0, 0, 0, 0.8) !important;
            }
            body.themes-php .theme-overlay .theme-backdrop {
                background: transparent !important;
            }
            body.themes-php .theme-overlay .theme-header {
                background: rgba(15, 23, 42, 0.4) !important;
                border-bottom: 1px solid rgba(255, 255, 255, 0.05) !important;
                color: #ffffff !important;
            }
            body.themes-php .theme-overlay .theme-name {
                color: #ffffff !important;
            }
            body.themes-php .theme-overlay .theme-info {
                color: #cbd5e1 !important;
                background: transparent !important;
            }

            /* Theme Filterleiste & Suchleiste (Modern Dashboard look) */
            body.themes-php .wp-filter {
                background: rgba(15, 23, 42, 0.4) !important;
                border: 1px solid rgba(255, 255, 255, 0.08) !important;
                border-radius: 12px !important;
                padding: 12px 16px !important;
                margin-bottom: 24px !important;
                display: flex !important;
                align-items: center !important;
                justify-content: space-between !important;
                flex-wrap: wrap !important;
                gap: 12px !important;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15) !important;
            }
            body.themes-php .filter-links {
                display: flex !important;
                gap: 8px !important;
                margin: 0 !important;
                padding: 0 !important;
                list-style: none !important;
            }
            body.themes-php .filter-links li {
                margin: 0 !important;
            }
            body.themes-php .filter-links li a {
                background: rgba(255, 255, 255, 0.02) !important;
                border: 1px solid rgba(255, 255, 255, 0.05) !important;
                color: #94a3b8 !important;
                padding: 6px 14px !important;
                border-radius: 20px !important;
                text-decoration: none !important;
                font-size: 11px !important;
                transition: all 0.15s ease !important;
            }
            body.themes-php .filter-links li a.current {
                background: rgba(99, 102, 241, 0.15) !important;
                border-color: #6366f1 !important;
                color: #ffffff !important;
                font-weight: 700 !important;
            }
            body.themes-php .filter-links li a:hover:not(.current) {
                background: rgba(255, 255, 255, 0.06) !important;
                color: #ffffff !important;
            }
            body.themes-php .wp-filter-search {
                background-color: #0f172a !important;
                border: 1px solid rgba(255, 255, 255, 0.1) !important;
                border-radius: 8px !important;
                color: #f1f5f9 !important;
                padding: 6px 12px !important;
                font-size: 12px !important;
                height: 34px !important;
                margin: 0 !important;
                width: 100% !important;
                max-width: 300px !important;
            }
        </style>';
    }
}