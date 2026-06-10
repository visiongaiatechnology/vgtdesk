<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * MODULE: FILESYSTEM GUARD (Datensicherheit)
 * STATUS: PLATIN STATUS (WP.ORG COMPLIANT)
 * Scanned Datei-Rechte und vergleicht sie mit Soll-Werten.
 */
class VGTS_Filesystem_Guard {

    /**
     * Gibt die Liste der kritischen Pfade mit übersetzten Labels zurück.
     * * @return array
     */
    private function get_critical_paths(): array {
        return [
            ['path' => '/',                     'type' => 'dir',  'rec' => '0755', 'label' => __('Root Directory', 'vgt-sentinel-ce')],
            ['path' => 'wp-includes/',          'type' => 'dir',  'rec' => '0755', 'label' => __('WP Core Includes', 'vgt-sentinel-ce')],
            ['path' => '.htaccess',             'type' => 'file', 'rec' => '0644', 'label' => __('Server Config (.htaccess)', 'vgt-sentinel-ce')],
            ['path' => 'wp-admin/index.php',    'type' => 'file', 'rec' => '0644', 'label' => __('Admin Entry Point', 'vgt-sentinel-ce')],
            ['path' => 'wp-admin/js/',          'type' => 'dir',  'rec' => '0755', 'label' => __('Admin Assets', 'vgt-sentinel-ce')],
            ['path' => 'wp-content/themes/',    'type' => 'dir',  'rec' => '0755', 'label' => __('Theme Directory', 'vgt-sentinel-ce')],
            ['path' => 'wp-content/plugins/',   'type' => 'dir',  'rec' => '0755', 'label' => __('Plugin Directory', 'vgt-sentinel-ce')],
            ['path' => 'wp-admin/',             'type' => 'dir',  'rec' => '0755', 'label' => __('WP Admin Area', 'vgt-sentinel-ce')],
            ['path' => 'wp-content/',           'type' => 'dir',  'rec' => '0755', 'label' => __('Content Area', 'vgt-sentinel-ce')],
            ['path' => 'wp-config.php',         'type' => 'file', 'rec' => '0400', 'label' => __('WP Config (Critical)', 'vgt-sentinel-ce')]
        ];
    }

    /**
     * Scannt die Berechtigungen der kritischen Systempfade.
     * * @return array
     */
    public function scan_permissions(): array {
        $results = [];
        $root    = ABSPATH;
        $paths   = $this->get_critical_paths();

        foreach ($paths as $item) {
            $full_path = $root . $item['path'];
            $exists    = file_exists($full_path);
            
            // Berechtigungen oktal extrahieren (z.B. 0755)
            $perms = $exists ? substr(sprintf('%o', fileperms($full_path)), -4) : 'N/A';
            
            // Status-Logik (Standard: Sicher)
            $status = 'secure';
            $msg    = __('No action required', 'vgt-sentinel-ce');

            if (!$exists) {
                $status = 'missing';
                $msg    = __('File or folder not found', 'vgt-sentinel-ce');
            } elseif ($perms !== $item['rec']) {
                // Sonderfall wp-config.php: Akzeptiere 0400, 0440, 0600, 0640, 0644 je nach Härtungsgrad
                if ($item['path'] === 'wp-config.php' && in_array($perms, ['0400', '0440', '0600', '0640', '0644'], true)) {
                    $status = 'secure';
                } else {
                    $status = 'warning';
                    /* translators: %s: Recommended permission string (e.g. 0755) */
                    $msg = sprintf(__('Correct permissions to %s', 'vgt-sentinel-ce'), $item['rec']);
                }
            }

            $results[] = [
                'label'  => $item['label'],
                'path'   => $full_path, 
                'perms'  => $perms,
                'rec'    => $item['rec'],
                'status' => $status,
                'msg'    => $msg
            ];
        }

        return $results;
    }
}