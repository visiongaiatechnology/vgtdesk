<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VGT DATTRACK COMMAND CENTER & EXPORT KERNEL
 * TIER: PLATINUM / VGT SUPREME (CSRF HARDENED)
 * STATUS: 💠 DIAMANT VGT SUPREME
 */
if (!class_exists('VGT_Dashboard')) {
final class VGT_Dashboard {

    // --- VGT EXPORT KERNEL: DEDIZIERTE ROUTEN ---

    public static function process_live_sync(): void {
        if (!current_user_can('manage_options')) wp_die('Unauthorized');
        check_admin_referer('vgt_dt_action_nonce');
        
        if (class_exists('VGT_Aggregator')) {
            VGT_Aggregator::run_rollup();
        }
        wp_redirect(admin_url('admin.php?page=vgt-dattrack'));
        exit;
    }

    /**
     * VGT SUPREME CSV EXPORT: REAL-TIME DECRYPTION STREAM
     * Nutzt O(1) Memory Keyset Pagination und on-the-fly AES Entschlüsselung.
     * CSRF-Gehärtet via Nonce-Validierung.
     */
    public static function stream_csv(): void {
        if (!current_user_can('manage_options')) wp_die('Unauthorized');
        check_admin_referer('vgt_dt_action_nonce');

        // Absolute Puffer-Vernichtung zur Vermeidung verunreinigter Header
        while (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="vgt_telemetry_raw_vault_' . date('Y-m-d_H-i') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        
        // UTF-8 BOM erzwingen (Microsoft Excel Kompatibilität)
        fputs($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Semikolon Delimiter für EU-Standards
        fputcsv($output, [
            'Timestamp', 
            'Client Entity (HMAC-SHA256)', 
            'Ingestion Path', 
            'Document Title', 
            'Referrer Vector', 
            'Display Resolution', 
            'Payload Size (Bytes)',
            'Crypto State'
        ], ';');

        global $wpdb;
        $vault_table = $wpdb->prefix . 'vgt_dattrack_vault';
        
        $last_id = 0;
        $limit = 1000;
        
        while (true) {
            $query = $wpdb->prepare(
                "SELECT id, ip_hash, payload, iv, auth_tag, timestamp 
                 FROM {$vault_table} 
                 WHERE id > %d 
                 ORDER BY id ASC 
                 LIMIT %d",
                $last_id,
                $limit
            );
            
            $rows = $wpdb->get_results($query, ARRAY_A);
            if (empty($rows)) break;

            foreach ($rows as $row) {
                $last_id = (int)$row['id'];
                $decrypted = VGT_Crypto::decrypt_payload($row['payload'], $row['iv'], $row['auth_tag'], $row['ip_hash']);
                
                if ($decrypted) {
                    fputcsv($output, [
                        $row['timestamp'],
                        $row['ip_hash'],
                        $decrypted['p'] ?? '/',
                        $decrypted['t'] ?? 'UNKNOWN',
                        $decrypted['r'] ?? 'DIRECT_OR_HIDDEN',
                        $decrypted['s'] ?? 'UNKNOWN',
                        strlen($row['payload']),
                        'DECRYPTED_SUCCESS'
                    ], ';');
                } else {
                    fputcsv($output, [
                        $row['timestamp'],
                        $row['ip_hash'],
                        '[ENCRYPTED]',
                        '[ENCRYPTED]',
                        '[ENCRYPTED]',
                        '[ENCRYPTED]',
                        strlen($row['payload']),
                        'KEY_ROTATION_LOCKED'
                    ], ';');
                }
            }
            // Stream flushen, um OOM bei massiven Vaults zu verhindern
            flush();
        }
        
        fclose($output);
        exit;
    }

    /**
     * VGT SUPREME PDF EXPORT: ANALYTICAL REPORT MATRIX
     * CSRF-Gehärtet via Nonce-Validierung.
     */
    public static function render_print_view(): void {
        if (!current_user_can('manage_options')) wp_die('Unauthorized');
        check_admin_referer('vgt_dt_action_nonce');

        global $wpdb;
        $stats_table = $wpdb->prefix . 'vgt_dattrack_stats';
        $results = $wpdb->get_results("SELECT stat_date, events, unique_users, paths FROM {$stats_table} ORDER BY stat_date DESC LIMIT 30", ARRAY_A);

        $total_events = 0;
        $total_users = 0;
        if ($results) {
            foreach ($results as $r) {
                $total_events += (int)$r['events'];
                $total_users += (int)$r['unique_users'];
            }
        }
        ?>
        <!DOCTYPE html>
        <html lang="de">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>VGT Dattrack - Sovereign Report</title>
            <style>
                @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=JetBrains+Mono:wght@400;700&display=swap');
                
                :root {
                    --vgt-dark: #0a0a0c;
                    --vgt-gray: #f1f5f9;
                    --vgt-border: #cbd5e1;
                    --vgt-cyan: #008b96;
                }
                
                body { 
                    background: #ffffff; 
                    color: var(--vgt-dark); 
                    margin: 0; 
                    padding: 40px; 
                    font-family: 'Inter', system-ui, sans-serif; 
                    line-height: 1.5;
                    -webkit-print-color-adjust: exact; 
                    print-color-adjust: exact;
                }
                
                @page { margin: 1.5cm; size: A4 portrait; }

                .report-header { border-bottom: 2px solid var(--vgt-dark); padding-bottom: 20px; margin-bottom: 30px; }
                .report-header h1 { margin: 0; font-size: 24px; text-transform: uppercase; letter-spacing: -0.5px; display: flex; align-items: center; gap: 10px; }
                .report-header h1::before { content: ''; display: block; width: 12px; height: 12px; background: var(--vgt-dark); }
                .report-meta { display: flex; justify-content: space-between; margin-top: 10px; font-size: 12px; color: #475569; font-family: 'JetBrains Mono', monospace; }
                
                .summary-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 40px; }
                .summary-box { border: 1px solid var(--vgt-border); padding: 20px; background: var(--vgt-gray); border-radius: 4px; }
                .summary-label { font-size: 11px; text-transform: uppercase; font-weight: 700; color: #64748b; letter-spacing: 0.5px; }
                .summary-value { font-size: 32px; font-weight: 700; color: var(--vgt-dark); margin-top: 5px; }

                .data-section { margin-bottom: 40px; page-break-inside: avoid; }
                .data-date { font-size: 18px; font-weight: 700; background: var(--vgt-dark); color: #fff; padding: 10px 15px; margin: 0; display: flex; justify-content: space-between; align-items: center; }
                .data-date span { font-size: 14px; font-weight: 400; font-family: 'JetBrains Mono', monospace; }
                
                table { width: 100%; border-collapse: collapse; margin-top: 0; font-size: 13px; }
                th, td { border: 1px solid var(--vgt-border); padding: 10px 15px; text-align: left; }
                th { background: var(--vgt-gray); font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; color: #475569; }
                td.mono { font-family: 'JetBrains Mono', monospace; font-size: 12px; }
                td.num { text-align: right; font-variant-numeric: tabular-nums; font-weight: 600; width: 120px; }
                tr:nth-child(even) { background: #f8fafc; }
                
                .pct-bar-container { width: 100px; height: 6px; background: #e2e8f0; border-radius: 3px; overflow: hidden; display: inline-block; vertical-align: middle; margin-right: 10px; }
                .pct-bar { height: 100%; background: var(--vgt-cyan); }
            </style>
        </head>
        <body>
            <div class="report-header">
                <h1>VGT Sovereign Telemetry Report</h1>
                <div class="report-meta">
                    <span>Aegis-256-GCM Secure Enclave</span>
                    <span>Generiert: <?php echo date('Y-m-d H:i:s T'); ?></span>
                </div>
            </div>

            <div class="summary-grid">
                <div class="summary-box">
                    <div class="summary-label">Aggregierte Events (30 Tage)</div>
                    <div class="summary-value"><?php echo number_format($total_events, 0, ',', '.'); ?></div>
                </div>
                <div class="summary-box">
                    <div class="summary-label">Verifizierte Entitäten (30 Tage)</div>
                    <div class="summary-value"><?php echo number_format($total_users, 0, ',', '.'); ?></div>
                </div>
            </div>

            <?php if (empty($results)): ?>
                <p>Keine Telemetrie-Daten für diesen Zeitraum verfügbar.</p>
            <?php else: ?>
                <?php foreach ($results as $row): 
                    $paths = json_decode($row['paths'], true) ?: [];
                    arsort($paths);
                ?>
                    <div class="data-section">
                        <div class="data-date">
                            <?php echo date('d.m.Y', strtotime($row['stat_date'])); ?>
                            <span>Events: <?php echo $row['events']; ?> | Users: <?php echo $row['unique_users']; ?></span>
                        </div>
                        <table>
                            <thead>
                                <tr>
                                    <th>Zugriffs-Vektor (Pfad)</th>
                                    <th style="width: 150px;">Auslastung</th>
                                    <th class="num">Hits</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($paths)): ?>
                                    <tr><td colspan="3" class="mono">Keine Pfad-Vektoren extrahiert</td></tr>
                                <?php else: 
                                    $max_hits = reset($paths) ?: 1;
                                    foreach ($paths as $path => $count): 
                                        $pct = ($count / $max_hits) * 100;
                                ?>
                                    <tr>
                                        <td class="mono"><?php echo esc_html($path); ?></td>
                                        <td>
                                            <div class="pct-bar-container"><div class="pct-bar" style="width: <?php echo $pct; ?>%;"></div></div>
                                        </td>
                                        <td class="num"><?php echo number_format($count, 0, ',', '.'); ?></td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <script>
                window.onload = function() { window.print(); };
            </script>
        </body>
        </html>
        <?php
        exit;
    }

    // --- DASHBOARD LOGIC (INTERAKTIVE UI) ---

    public static function get_vault_metrics(): array {
        if (get_option('vgt_dattrack_enabled') !== 'true') {
            return [
                'today'     => ['events' => 0, 'unique_users' => 0, 'paths' => []],
                'yesterday' => ['events' => 0, 'unique_users' => 0, 'paths' => []],
                'last7'     => ['events' => 0, 'unique_users' => 0, 'paths' => []],
                'all'       => ['events' => 0, 'unique_users' => 0, 'paths' => [], 'timeline_chart' => []]
            ];
        }

        global $wpdb;
        $stats_table = $wpdb->prefix . 'vgt_dattrack_stats';
        
        $results = $wpdb->get_results("SELECT stat_date, events, unique_users, paths FROM {$stats_table} ORDER BY stat_date ASC", ARRAY_A);
        
        $now = current_time('timestamp');
        $today_date = date('Y-m-d', $now);
        $yesterday_date = date('Y-m-d', strtotime('-1 day', $now));
        $seven_days_ago = date('Y-m-d', strtotime('-7 days', $now));

        $buckets = [
            'today'     => ['events' => 0, 'unique_users' => 0, 'paths' => []],
            'yesterday' => ['events' => 0, 'unique_users' => 0, 'paths' => []],
            'last7'     => ['events' => 0, 'unique_users' => 0, 'paths' => []],
            'all'       => ['events' => 0, 'unique_users' => 0, 'paths' => [], 'timeline_chart' => []]
        ];

        $all_paths = [];
        $last7_paths = [];

        if ($results) {
            foreach ($results as $row) {
                $date = $row['stat_date'];
                $events = (int)$row['events'];
                $users = (int)$row['unique_users'];
                $paths = json_decode($row['paths'], true) ?: [];

                $buckets['all']['events'] += $events;
                $buckets['all']['unique_users'] += $users;
                $buckets['all']['timeline_chart'][] = ['date' => $date, 'count' => $events];
                foreach($paths as $p => $c) { $all_paths[$p] = ($all_paths[$p] ?? 0) + $c; }

                if ($date >= $seven_days_ago) {
                    $buckets['last7']['events'] += $events;
                    $buckets['last7']['unique_users'] += $users;
                    foreach($paths as $p => $c) { $last7_paths[$p] = ($last7_paths[$p] ?? 0) + $c; }
                }

                if ($date === $yesterday_date) {
                    $buckets['yesterday']['events'] = $events;
                    $buckets['yesterday']['unique_users'] = $users;
                    $buckets['yesterday']['paths'] = $paths;
                }

                if ($date === $today_date) {
                    $buckets['today']['events'] = $events;
                    $buckets['today']['unique_users'] = $users;
                    $buckets['today']['paths'] = $paths;
                }
            }
        }

        arsort($all_paths);
        $buckets['all']['paths'] = array_slice($all_paths, 0, 15);

        arsort($last7_paths);
        $buckets['last7']['paths'] = array_slice($last7_paths, 0, 15);

        return $buckets;
    }

    public static function render_sovereign_dashboard(): void {
        if (get_option('vgt_dattrack_enabled') !== 'true') {
            ?>
            <div class="vgt-dashboard-wrapper">
                <div class="vgt-no-print" style="background: rgba(0, 240, 255, 0.05); border: 1px solid rgba(0, 240, 255, 0.2); border-left: 4px solid #00f0ff; color: #f8fafc; padding: 24px; font-size: 14px; margin-bottom: 0;">
                    <h4 style="margin: 0 0 12px 0; color: #00f0ff; font-size: 16px; display: flex; align-items: center; gap: 8px;">
                        <span class="dashicons dashicons-chart-bar" style="font-size: 20px;"></span> VGT DATTRACK: SOVEREIGN TELEMETRY
                    </h4>
                    <p style="margin: 0 0 16px 0; color: #cbd5e1; line-height: 1.6;">
                        <strong>Dattrack ist derzeit deaktiviert.</strong> Aktivieren Sie die DSGVO-konforme sovereign Analytics Engine, um Leistungsdaten, Ladezeiten und Besucherströme Ende-zu-Ende verschlüsselt zu erfassen.
                    </p>
                    <script>
                        function activateDattrack() {
                            const btn = document.getElementById('activate-dt-btn');
                            if (btn) btn.disabled = true;
                            
                            const formData = new FormData();
                            formData.append('action', 'vgt_toggle_dattrack');
                            if (window.parent && window.parent.vgtConfig) {
                                formData.append('nonce', window.parent.vgtConfig.nonce);
                                fetch(window.parent.vgtConfig.ajaxUrl, {
                                    method: 'POST',
                                    body: formData
                                })
                                .then(res => res.json())
                                .then(data => {
                                    if (data.success) {
                                        window.parent.location.reload();
                                    } else {
                                        alert(data.data);
                                        if (btn) btn.disabled = false;
                                    }
                                })
                                .catch(err => {
                                    console.error(err);
                                    if (btn) btn.disabled = false;
                                });
                            } else {
                                alert('VGTDeskEngine Context nicht gefunden.');
                                if (btn) btn.disabled = false;
                            }
                        }
                    </script>
                    <button id="activate-dt-btn" onclick="activateDattrack()" class="vgt-badge vgt-action-btn" style="background: #00f0ff; color: #090d16; font-weight: bold; cursor: pointer; border: none; padding: 8px 16px; border-radius: 4px; display: inline-block;">Jetzt aktivieren</button>
                </div>
            </div>
            <?php
            return;
        }

        $is_nginx = strpos(strtolower($_SERVER['SERVER_SOFTWARE'] ?? ''), 'nginx') !== false;
        $master_key = VGT_Crypto::get_master_key();
        $vault_failed = empty($master_key);
        
        $upload_dir = wp_upload_dir();
        $safe_conf_path = $upload_dir['basedir'] . '/.vgt-keys/safe.conf';

        // VGT CSRF PROTECTION KERNEL: Signierte URLs generieren
        $url_csv  = wp_nonce_url(admin_url('admin-post.php?action=vgt_export_csv'), 'vgt_dt_action_nonce');
        $url_pdf  = wp_nonce_url(admin_url('admin-post.php?action=vgt_export_pdf'), 'vgt_dt_action_nonce');
        $url_sync = wp_nonce_url(admin_url('admin-post.php?action=vgt_sync'), 'vgt_dt_action_nonce');
        ?>
        <div class="vgt-dashboard-wrapper">
            <?php if ($vault_failed): ?>
                <div class="vgt-no-print" style="background: rgba(255, 0, 50, 0.05); border: 1px solid rgba(255, 0, 50, 0.4); border-left: 4px solid #ff0032; color: #f8fafc; padding: 24px; font-size: 14px; margin-bottom: 0; border-bottom: 0;">
                    <h4 style="margin: 0 0 12px 0; color: #ff0032; font-size: 16px; display: flex; align-items: center; gap: 8px;">
                        <span class="dashicons dashicons-warning" style="font-size: 20px;"></span> CRITICAL SYSTEM FAILURE: VAULT LOCKDOWN
                    </h4>
                    <p style="margin: 0 0 16px 0; color: #cbd5e1; line-height: 1.6;">
                        <strong>VGT Dattrack ist deaktiviert.</strong> Der kryptographische Kern konnte nicht initialisiert werden, da das System keine Schreibrechte im Verzeichnis <code><?php echo esc_html($upload_dir['basedir']); ?></code> besitzt. 
                    </p>
                </div>
            <?php elseif ($is_nginx && !defined('VGT_DATTRACK_MASTER_KEY')): ?>
                <div class="vgt-no-print" style="background: rgba(0, 240, 255, 0.05); border: 1px solid rgba(0, 240, 255, 0.2); border-left: 4px solid #00f0ff; color: #f8fafc; padding: 16px 24px; font-size: 13px; margin-bottom: 0; border-bottom: 0;">
                    <h4 style="margin: 0 0 10px 0; color: #00f0ff; font-size: 14px; display: flex; align-items: center; gap: 8px;">
                        <span class="dashicons dashicons-shield"></span> VGT SERVER PROTOCOL: NGINX DETEKTIERT
                    </h4>
                    <p style="margin: 0 0 12px 0; color: #94a3b8; line-height: 1.5;">
                        Der AES-Master-Key wurde isoliert im Filesystem generiert. Inkludiere folgende Direktive in deiner Nginx <code>server {}</code> Konfiguration:
                    </p>
                    <pre style="background: rgba(0,0,0,0.4); padding: 12px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.05); font-family: 'JetBrains Mono', monospace; font-size: 13px; color: #00f0ff; margin: 0;">include <?php echo esc_html($safe_conf_path); ?>;</pre>
                </div>
            <?php endif; ?>

            <div class="vgt-header">
                <div class="vgt-header-top">
                    <h1>SOVEREIGN ANALYTICS ENGINE</h1>
                    
                    <div style="display: flex; gap: 12px; align-items: center;" class="vgt-no-print">
                        <a href="<?php echo esc_url($url_csv); ?>" class="vgt-badge vgt-action-btn" title="Raw Vault Export">
                            <span class="dashicons dashicons-media-spreadsheet" style="font-size: 13px; width: 13px; height: 13px; margin-top: -1px; margin-right: 4px;"></span>Raw CSV
                        </a>
                        <a href="<?php echo esc_url($url_pdf); ?>" target="_blank" class="vgt-badge vgt-action-btn" title="Matrix Report">
                            <span class="dashicons dashicons-pdf" style="font-size: 13px; width: 13px; height: 13px; margin-top: -1px; margin-right: 4px;"></span>PDF Report
                        </a>
                        <a href="<?php echo esc_url($url_sync); ?>" class="vgt-badge vgt-action-btn vgt-action-sync">
                            <span class="dashicons dashicons-update" style="font-size: 13px; width: 13px; height: 13px; margin-top: -1px; margin-right: 4px;"></span>LIVE SYNC
                        </a>
                        <div class="vgt-badge" style="border-color: rgba(255,255,255,0.1); color: #cbd5e1;">Aegis-256-GCM</div>
                    </div>
                </div>
                
                <div class="vgt-tabs vgt-no-print" id="vgt-tab-nav">
                    <button class="vgt-tab active" data-target="today">Heute</button>
                    <button class="vgt-tab" data-target="yesterday">Gestern</button>
                    <button class="vgt-tab" data-target="last7">Letzte 7 Tage</button>
                    <button class="vgt-tab" data-target="all">Gesamt</button>
                </div>
            </div>

            <div class="vgt-grid">
                <div class="vgt-card">
                    <div class="vgt-stat-title">Entschlüsselte Events</div>
                    <div class="vgt-stat-value" id="stat-events">0</div>
                </div>
                <div class="vgt-card">
                    <div class="vgt-stat-title">Verifizierte Entitäten</div>
                    <div class="vgt-stat-value" id="stat-users">0</div>
                </div>
                <div class="vgt-card">
                    <div class="vgt-stat-title">Security State & Protocol</div>
                    <div class="vgt-stat-value vgt-pulse" style="color: var(--vgt-cyan); font-size: 22px; margin-top: 20px; text-shadow: 0 0 20px rgba(0,240,255,0.4);">ZERO KNOWLEDGE</div>
                </div>

                <div class="vgt-chart-container">
                    <div class="vgt-stat-title" style="margin-bottom: 16px;">Global Telemetry Timeline</div>
                    <canvas id="vgtTelemetryChart"></canvas>
                </div>

                <div class="vgt-table-container">
                    <div class="vgt-table-header vgt-stat-title" style="margin: 0;">Top Ingestion Paths</div>
                    <div id="vgt-paths-container"></div>
                </div>
            </div>

            <!-- DSGVO OPT-OUT CONFIGURATION INSTRUCTIONS -->
            <div class="vgt-no-print" style="margin-top: 24px; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); border-radius: 12px; padding: 24px; color: #cbd5e1; box-sizing: border-box;">
                <h3 style="color: #ffffff; font-size: 14px; font-weight: 700; margin: 0 0 10px 0; display: flex; align-items: center; gap: 8px;">
                    <span class="dashicons dashicons-privacy" style="color: var(--vgt-cyan); font-size: 20px; width: 20px; height: 20px;"></span> DSGVO & Privacy: Dattrack Opt-Out einbinden
                </h3>
                <p style="font-size: 12.5px; line-height: 1.5; margin: 0 0 15px 0; color: #94a3b8;">
                    In Deutschland und der EU ist die Bereitstellung einer Opt-Out-Möglichkeit für Web-Analytics in der Datenschutzerklärung gesetzlich vorgeschrieben. Dattrack bietet dafür zwei einfache Integrationswege an:
                </p>
                
                <div style="display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 5px;">
                    <div style="flex: 1; min-width: 280px; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.03); border-radius: 8px; padding: 15px; box-sizing: border-box;">
                        <h4 style="color: #ffffff; font-size: 12px; font-weight: 700; margin: 0 0 8px 0; text-transform: uppercase; letter-spacing: 0.5px;">Weg 1: WordPress Shortcode</h4>
                        <p style="font-size: 11.5px; line-height: 1.4; color: #94a3b8; margin: 0 0 10px 0;">
                            Füge einfach diesen Shortcode an der gewünschten Stelle in deine Datenschutzerklärung ein:
                        </p>
                        <pre style="background: rgba(0,0,0,0.4); padding: 8px 12px; border-radius: 4px; border: 1px solid rgba(255,255,255,0.05); font-family: 'JetBrains Mono', monospace; font-size: 12px; color: #00f0ff; margin: 0;">[vgt_dattrack_optout]</pre>
                        <p style="font-size: 11px; color: #64748b; margin: 8px 0 0 0;">
                            Dies erzeugt ein fertiges, interaktives Kontrollzentrum für den Besucher zum Deaktivieren/Aktivieren.
                        </p>
                    </div>
                    
                    <div style="flex: 1; min-width: 280px; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.03); border-radius: 8px; padding: 15px; box-sizing: border-box;">
                        <h4 style="color: #ffffff; font-size: 12px; font-weight: 700; margin: 0 0 8px 0; text-transform: uppercase; letter-spacing: 0.5px;">Weg 2: Manuelles JavaScript / Link</h4>
                        <p style="font-size: 11.5px; line-height: 1.4; color: #94a3b8; margin: 0 0 10px 0;">
                            Alternativ kannst du einen eigenen Link erstellen, der die Opt-Out-Funktion über den Browser-Speicher steuert:
                        </p>
                        <pre style="background: rgba(0,0,0,0.4); padding: 8px 12px; border-radius: 4px; border: 1px solid rgba(255,255,255,0.05); font-family: 'JetBrains Mono', monospace; font-size: 11px; color: #00f0ff; margin: 0; white-space: pre-wrap; word-break: break-all;">&lt;a href="javascript:void(0);" onclick="localStorage.setItem('vgt_dt_consent', '0'); alert('Dattrack Analytics wurde deaktiviert.');"&gt;Hier klicken, um Dattrack zu deaktivieren&lt;/a&gt;</pre>
                        <p style="font-size: 11px; color: #64748b; margin: 8px 0 0 0;">
                            Dattrack blockiert daraufhin jegliche Ingestion-Pakete für diesen Browser sofort.
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
}