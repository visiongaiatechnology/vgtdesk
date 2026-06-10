<?php
declare(strict_types=1);

/**
 * VISIONGAIATECHNOLOGY OMEGA PROTOCOL
 * Core Module: UNINSTALL KERNEL
 * Status: PLATIN VGT STATUS
 * * Diese Datei wird von WordPress AUSSCHLIESSLICH dann aufgerufen, 
 * wenn das Plugin im Backend über den "Löschen" Button deinstalliert wird.
 */

// VGT HARDENING: Absolute Verifikation des Ausführungskontextes.
// Wenn diese Konstante fehlt, versucht jemand das Script direkt über die URL aufzurufen.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    header('HTTP/1.0 403 Forbidden');
    exit('VGT OMEGA PROTOCOL: DIRECT ACCESS DENIED.');
}

// =========================================================================================
// 1. CHIRURGISCHE ENTFERNUNG DES MU-LOADERS (PRE-BOOT INTERCEPTION)
// =========================================================================================
$mu_dir  = defined('WPMU_PLUGIN_DIR') ? WPMU_PLUGIN_DIR : wp_normalize_path(WP_CONTENT_DIR . '/mu-plugins');
$mu_file = $mu_dir . '/0-vgts-sentinel-loader.php';

if (file_exists($mu_file)) {
    // Rechte temporär anheben, falls der Webserver die Datei gelockt hat, dann terminieren.
    @chmod($mu_file, 0664); 
    @unlink($mu_file);
}

// =========================================================================================
// 2. SYSTEM-CLEANSING: VAULT & METADATA (OPTIONAL ABER EMPFOHLEN)
// =========================================================================================
// Ein echtes VGT-System hinterlässt keine Spuren, wenn der Meister die Deinstallation befiehlt.

// A. Optionen und Config löschen
delete_option('vgts_config');
delete_option('vgts_scan_report');
delete_option('vgts_ghost_trap_manifest');

// B. Cronjobs terminieren (Sicherheitsnetz, falls Deactivation Hook versagt hat)
wp_clear_scheduled_hook('vgts_hourly_scan_event');

// C. Datenbank-Tabellen vernichten (Tabula Rasa)
global $wpdb;
$table_bans = defined('VGTS_TABLE_BANS') ? VGTS_TABLE_BANS : 'vgts_apex_bans';
$table_logs = defined('VGTS_TABLE_LOGS') ? VGTS_TABLE_LOGS : 'vgts_omega_logs';

// Wir droppen die Tabellen direkt, um den Speicher des Hosts freizugeben.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
$wpdb->query("DROP TABLE IF EXISTS " . $wpdb->prefix . $table_bans);
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
$wpdb->query("DROP TABLE IF EXISTS " . $wpdb->prefix . $table_logs);

// D. VGT Vault (Integritäts-Matrix) vom Dateisystem brennen
$upload_dir = wp_upload_dir();
$vault_dir = wp_normalize_path($upload_dir['basedir'] . '/vgts-vault-omega');

if (is_dir($vault_dir)) {
    $files = array_diff(scandir($vault_dir), ['.', '..']);
    foreach ($files as $file) {
        $file_path = "$vault_dir/$file";
        @chmod($file_path, 0664);
        @unlink($file_path);
    }
    @rmdir($vault_dir);
}
