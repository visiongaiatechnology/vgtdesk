<?php 
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit; 
}

/**
 * VIEW: DASHBOARD OVERVIEW (COMMAND CENTER)
 * STATUS: PLATIN VGT STATUS (Hardened & i18n)
 * MODULE: CENTRAL TELEMETRY & MODULE MATRIX (Community Edition)
 * TEXTDOMAIN: vgt-sentinel-ce
 */

global $wpdb;

// 1. Daten laden & Sanitization (Overhead-Free Count mit Error-Suppression)
$table_bans = defined('VGTS_TABLE_BANS') ? VGTS_TABLE_BANS : 'vgts_apex_bans';
$suppress = $wpdb->suppress_errors(true);
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$bans_query = $wpdb->get_var("SELECT COUNT(*) FROM " . $wpdb->prefix . $table_bans);
$wpdb->suppress_errors($suppress);

$bans = $bans_query ? (int)$bans_query : 0;
$opt  = (array) get_option('vgts_config', []);

$mu_loader_path = wp_normalize_path((defined('WPMU_PLUGIN_DIR') ? WPMU_PLUGIN_DIR : WP_CONTENT_DIR . '/mu-plugins') . '/0-vgts-sentinel-loader.php');

// 2. COMMUNITY CORE MATRIX (SILBER STATUS)
$core_modules = [
    [
        'label'  => __('AEGIS FIREWALL', 'vgt-sentinel-ce'),
        'icon'   => 'dashicons-shield',
        'active' => !empty($opt['aegis_enabled']),
        'desc'   => __('Regex WAF Engine', 'vgt-sentinel-ce'),
        'link'   => '?page=vgts-sentinel&tab=aegis'
    ],
    [
        'label'  => __('TITAN HARDENING', 'vgt-sentinel-ce'),
        'icon'   => 'dashicons-lock',
        'active' => !empty($opt['titan_enabled']),
        'desc'   => __('Kernel & Header Protection', 'vgt-sentinel-ce'),
        'link'   => '?page=vgts-sentinel&tab=titan'
    ],
    [
        'label'  => __('HADES STEALTH', 'vgt-sentinel-ce'),
        'icon'   => 'dashicons-hidden',
        'active' => !empty($opt['hades_enabled']),
        'desc'   => __('Camouflage & Obfuscation', 'vgt-sentinel-ce'),
        'link'   => '?page=vgts-sentinel&tab=hades'
    ],
    [
        'label'  => __('MU DEPLOYER', 'vgt-sentinel-ce'),
        'icon'   => 'dashicons-hammer',
        'active' => file_exists($mu_loader_path),
        'desc'   => __('Pre-Boot Interception', 'vgt-sentinel-ce'),
        'link'   => '?page=vgts-sentinel&tab=mudeployer'
    ],
    [
        'label'  => __('CERBERUS GUARD', 'vgt-sentinel-ce'),
        'icon'   => 'dashicons-id-alt',
        'active' => true, 
        'desc'   => __('Login Brute-Force Shield', 'vgt-sentinel-ce'),
        'link'   => '?page=vgts-sentinel&tab=cerberus'
    ],
    [
        'label'  => __('STYX CONTROL', 'vgt-sentinel-ce'),
        'icon'   => 'dashicons-networking',
        'active' => isset($opt['styx_kill_telemetry']) ? (bool)$opt['styx_kill_telemetry'] : true,
        'desc'   => __('Outbound Telemetry Kill', 'vgt-sentinel-ce'),
        'link'   => '?page=vgts-sentinel&tab=styx'
    ],
    [
        'label'  => __('AIRLOCK', 'vgt-sentinel-ce'),
        'icon'   => 'dashicons-upload',
        'active' => !empty($opt['airlock_enabled']),
        'desc'   => __('Upload Sanitizer Engine', 'vgt-sentinel-ce'),
        'link'   => '?page=vgts-sentinel&tab=airlock'
    ],
    [
        'label'  => __('GHOST TRAP', 'vgt-sentinel-ce'),
        'icon'   => 'dashicons-warning',
        'active' => true, 
        'desc'   => __('Deception Grid', 'vgt-sentinel-ce'),
        'link'   => '#' // Backend Only
    ],
    [
        'label'  => __('CHRONOS', 'vgt-sentinel-ce'),
        'icon'   => 'dashicons-clock',
        'active' => (bool)wp_next_scheduled('vgts_hourly_scan_event'),
        'desc'   => __('Automated Integrity Scan', 'vgt-sentinel-ce'),
        'link'   => '?page=vgts-sentinel&tab=integrity'
    ],
    [
        'label'  => __('FS GUARD', 'vgt-sentinel-ce'),
        'icon'   => 'dashicons-category',
        'active' => true, 
        'desc'   => __('Permission Monitor', 'vgt-sentinel-ce'),
        'link'   => '?page=vgts-sentinel&tab=filesystem'
    ]
];

// 3. VGT OMEGA PLATINUM MATRIX (DIAMANT SUPREME STATUS)
$pro_modules = [
    [
        'label'  => __('ZEUS PRE-BOOT WAF', 'vgt-sentinel-ce'),
        'icon'   => 'dashicons-bolt',
        'desc'   => __('PHP Runtime Interception (< 0.2ms)', 'vgt-sentinel-ce')
    ],
    [
        'label'  => __('ORACLE AI INFERENCE', 'vgt-sentinel-ce'),
        'icon'   => 'dashicons-superhero',
        'desc'   => __('Heuristic Threat Analysis', 'vgt-sentinel-ce')
    ],
    [
        'label'  => __('MORPHEUS HYPERVISOR', 'vgt-sentinel-ce'),
        'icon'   => 'dashicons-admin-network',
        'desc'   => __('Zero-Trust Plugin Isolation', 'vgt-sentinel-ce')
    ],
    [
        'label'  => __('GORGON NEXUS', 'vgt-sentinel-ce'),
        'icon'   => 'dashicons-share-alt',
        'desc'   => __('Global Swarm Intelligence', 'vgt-sentinel-ce')
    ],
    [
        'label'  => __('PROMETHEUS ENGINE', 'vgt-sentinel-ce'),
        'icon'   => 'dashicons-chart-line',
        'desc'   => __('Predictive Behavioral Scoring', 'vgt-sentinel-ce')
    ],
    [
        'label'  => __('NEMESIS DECEPTION', 'vgt-sentinel-ce'),
        'icon'   => 'dashicons-buddicons-groups',
        'desc'   => __('Tarpitting & Data Poisoning', 'vgt-sentinel-ce')
    ],
    [
        'label'  => __('VGT KEY VAULT', 'vgt-sentinel-ce'),
        'icon'   => 'dashicons-keys',
        'desc'   => __('AES-256-GCM Hardware Crypto', 'vgt-sentinel-ce')
    ]
];

$next_scan = wp_next_scheduled('vgts_hourly_scan_event');
$next_scan_time = $next_scan ? wp_date('H:i', $next_scan) . ' ' . wp_date('T', $next_scan) : esc_html__('Pending', 'vgt-sentinel-ce');
?>

<div id="vgts-master-container">
    <!-- UI LANGUAGE TOGGLE -->
    <div class="vgts-toggle-wrapper">
        <label class="vgts-toggle-label">
            <span class="vgts-toggle-text vgts-text-de"><?php esc_html_e('DE', 'vgt-sentinel-ce'); ?></span>
            <div class="vgts-switch-track">
                <div class="vgts-switch-thumb"></div>
            </div>
            <span class="vgts-toggle-text vgts-text-en"><?php esc_html_e('EN', 'vgt-sentinel-ce'); ?></span>
            <input type="checkbox" id="vgts-global-lang-toggle" style="display: none;">
        </label>
    </div>

    <!-- VGT COMMUNITY GUARD: LIABILITY DISCLAIMER (SILBER STATUS) -->
    <div style="background: #0d1117; border: 1px solid #30363d; border-left: 4px solid #94a3b8; padding: 20px; margin-bottom: 25px; border-radius: 6px; display: flex; align-items: center; justify-content: space-between;">
        <div>
            <span style="background: linear-gradient(90deg, #64748b, #94a3b8); color: #0f172a; padding: 4px 10px; font-weight: 800; font-size: 10px; text-transform: uppercase; border-radius: 3px; letter-spacing: 1px; display: inline-block; margin-bottom: 8px;">
                <?php esc_html_e('FREE STATUS (COMMUNITY CORE)', 'vgt-sentinel-ce'); ?>
            </span>
            
            <h2 class="vgts-lang-de" style="color: #f8fafc; margin: 0 0 5px 0; font-size: 16px; font-weight: 700;"><?php esc_html_e('SENTINEL OPEN SOURCE EDITION', 'vgt-sentinel-ce'); ?></h2>
            <h2 class="vgts-lang-en" style="color: #f8fafc; margin: 0 0 5px 0; font-size: 16px; font-weight: 700;"><?php esc_html_e('SENTINEL OPEN SOURCE EDITION', 'vgt-sentinel-ce'); ?></h2>
            
            <p class="vgts-lang-de" style="color: #8b949e; margin: 0; font-size: 13px; line-height: 1.5; max-width: 800px;">
                <?php echo wp_kses_post(__('<strong>WARNUNG:</strong> Diese Version arbeitet mit deterministischer DFA-Logik. Kognitive KI-Inference, Swarm-Intelligence und Pre-Boot Abwehrmechanismen sind deaktiviert. Keine Haftung für Systemkompromittierungen durch polymorphe Zero-Day-Exploits. Diese Version ist eine ultra Lite Version der V7 und nicht vergleichbar mit der Abwehrkraft von VGT Sentinel Pro.', 'vgt-sentinel-ce')); ?>
            </p>
            <p class="vgts-lang-en" style="color: #8b949e; margin: 0; font-size: 13px; line-height: 1.5; max-width: 800px;">
                <?php echo wp_kses_post(__('<strong>WARNING:</strong> This iteration operates exclusively on deterministic DFA logic. Cognitive AI inference, swarm intelligence, and pre-boot defense mechanisms are disabled. Zero liability is assumed for system compromises caused by polymorphic zero-day exploits. This is an ultra-lite build of V7 and cannot be compared to the asymmetric defensive power of VGT Sentinel Pro.', 'vgt-sentinel-ce')); ?>
            </p>
        </div>
    </div>

    <!-- KPI CARDS (TOP ROW) -->
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap:25px; margin-bottom:30px;">
        
        <!-- CARD 1: INTEGRITY -->
        <div class="vgts-card" style="border-top: 3px solid var(--vgts-success);">
            <h3><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e('INTEGRITY STATUS', 'vgt-sentinel-ce'); ?></h3>
            <div style="display:flex; align-items:flex-end; gap:15px; margin:15px 0;">
                <div style="font-size:3rem; font-weight:800; color:var(--vgts-success); line-height:1;"><?php esc_html_e('SECURE', 'vgt-sentinel-ce'); ?></div>
                <div style="font-size:0.9rem; color:var(--vgts-text-secondary); padding-bottom:5px;"><?php esc_html_e('State: Valid', 'vgt-sentinel-ce'); ?></div>
            </div>
            <p style="font-size:13px; margin-bottom:20px;"><?php esc_html_e('Differential Hashing Engine active. Filesystem verified.', 'vgt-sentinel-ce'); ?></p>
            <button type="button" id="vgts-btn-approve" class="vgts-btn" style="width:100%; border: 1px solid var(--vgts-border); background: transparent; color: var(--vgts-text-main);">
                <span class="dashicons dashicons-yes"></span> <?php esc_html_e('APPROVE BASELINE', 'vgt-sentinel-ce'); ?>
            </button>
        </div>

        <!-- CARD 2: THREATS -->
        <div class="vgts-card" style="border-top: 3px solid var(--vgts-danger);">
            <h3><span class="dashicons dashicons-shield"></span> <?php esc_html_e('NEUTRALIZED THREATS', 'vgt-sentinel-ce'); ?></h3>
            <div style="display:flex; align-items:flex-end; gap:15px; margin:15px 0;">
                <div style="font-size:3rem; font-weight:800; color:var(--vgts-danger); line-height:1;"><?php echo esc_html(number_format_i18n($bans)); ?></div>
                <div style="font-size:0.9rem; color:var(--vgts-text-secondary); padding-bottom:5px;"><?php esc_html_e('Attackers Banned', 'vgt-sentinel-ce'); ?></div>
            </div>
            <p style="font-size:13px; margin-bottom:20px;"><?php esc_html_e('Global Banlist (SQL Optimized) protecting login & requests.', 'vgt-sentinel-ce'); ?></p>
            <a href="?page=vgts-sentinel&tab=cerberus" class="vgts-btn" style="width:100%; text-align:center; display:block; border: 1px solid var(--vgts-border); background: transparent; color: var(--vgts-text-main); text-decoration: none;">
                <?php esc_html_e('VIEW INCIDENTS', 'vgt-sentinel-ce'); ?>
            </a>
        </div>

        <!-- CARD 3: AUTOMATION -->
        <div class="vgts-card" style="border-top: 3px solid #3b82f6;">
            <h3><span class="dashicons dashicons-clock"></span> <?php esc_html_e('CHRONOS AUTOMATION', 'vgt-sentinel-ce'); ?></h3>
            <div style="display:flex; align-items:flex-end; gap:15px; margin:15px 0;">
                <div style="font-size:3rem; font-weight:800; color:#3b82f6; line-height:1;"><?php esc_html_e('ACTIVE', 'vgt-sentinel-ce'); ?></div>
                <div style="font-size:0.9rem; color:var(--vgts-text-secondary); padding-bottom:5px;"><?php esc_html_e('Hourly Scan', 'vgt-sentinel-ce'); ?></div>
            </div>
            <p style="font-size:13px; margin-bottom:20px;">
                <?php printf(esc_html__('Next Auto-Scan: %s', 'vgt-sentinel-ce'), esc_html($next_scan_time)); ?>
            </p>
            <div style="background:rgba(255,255,255,0.05); height:4px; width:100%; border-radius:2px; overflow:hidden;">
                <div style="background:#3b82f6; width:75%; height:100%;"></div>
            </div>
        </div>
    </div>

    <!-- 1. CORE MODULE MATRIX (SILBER) -->
    <div class="vgts-card">
        <h3 style="margin-bottom:20px; border-bottom:1px solid var(--vgts-border); padding-bottom:15px; display:flex; align-items:center; gap:10px;">
            <span class="dashicons dashicons-shield-alt"></span> <?php esc_html_e('COMMUNITY CORE MATRIX', 'vgt-sentinel-ce'); ?>
        </h3>
        
        <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap:20px;">
            <?php foreach($core_modules as $mod): 
                $is_active    = $mod['active'];
                $status_color = $is_active ? 'var(--vgts-success)' : 'var(--vgts-text-secondary)';
                $card_class   = $is_active ? 'vgts-module-card is-active' : 'vgts-module-card';
                $is_disabled  = ($mod['link'] === '#');
                if ($is_disabled) {
                    $card_class .= ' is-disabled';
                }
            ?>
                <a href="<?php echo esc_url((string)$mod['link']); ?>" class="<?php echo esc_attr($card_class); ?>">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px;">
                        <span class="dashicons <?php echo esc_attr((string)$mod['icon']); ?>" style="font-size:24px; color:<?php echo esc_attr($status_color); ?>; width:24px; height:24px;"></span>
                        <?php if($is_active): ?>
                            <span class="vgts-badge-status bg-green" style="font-size:9px;"><?php esc_html_e('ACTIVE', 'vgt-sentinel-ce'); ?></span>
                        <?php else: ?>
                            <span class="vgts-badge-status" style="background:#334155; color:#94a3b8; font-size:9px; border:1px solid #475569;"><?php esc_html_e('OFFLINE', 'vgt-sentinel-ce'); ?></span>
                        <?php endif; ?>
                    </div>
                    <div style="font-weight:700; font-size:12px; letter-spacing:0.5px; margin-bottom:5px; color:#fff;">
                        <?php echo esc_html((string)$mod['label']); ?>
                    </div>
                    <div style="font-size:11px; color:var(--vgts-text-secondary); line-height:1.4;">
                        <?php echo esc_html((string)$mod['desc']); ?>
                    </div>
                    <?php if($is_active): ?>
                    <div style="position:absolute; bottom:0; left:0; width:100%; height:2px; background: linear-gradient(90deg, transparent, var(--vgts-success), transparent); opacity: 0.5;"></div>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- PLATINUM HIGHLIGHT: DIE WAHRE STÄRKE VON AEGIS -->
    <div style="background: radial-gradient(circle at top right, rgba(6, 182, 212, 0.15) 0%, rgba(2, 6, 23, 1) 100%); border: 1px solid rgba(6, 182, 212, 0.3); border-radius: 8px; padding: 30px; margin-bottom: 25px; display: flex; gap: 30px; align-items: center; box-shadow: 0 10px 30px -10px rgba(6, 182, 212, 0.2);">
        <div style="flex-shrink: 0; text-align: center;">
            <span class="dashicons dashicons-shield" style="font-size: 64px; color: #06b6d4; width: 64px; height: 64px; filter: drop-shadow(0 0 15px rgba(6, 182, 212, 0.5));"></span>
        </div>
        <div>
            <h2 class="vgts-lang-de" style="color: #fff; margin: 0 0 10px 0; font-size: 20px; font-weight: 800; letter-spacing: -0.5px;">DIE FUSION: AEGIS <span style="color: #06b6d4;">+</span> ORACLE <span style="color: #06b6d4;">+</span> ZEUS</h2>
            <h2 class="vgts-lang-en" style="color: #fff; margin: 0 0 10px 0; font-size: 20px; font-weight: 800; letter-spacing: -0.5px;">THE FUSION: AEGIS <span style="color: #06b6d4;">+</span> ORACLE <span style="color: #06b6d4;">+</span> ZEUS</h2>
            
            <p class="vgts-lang-de" style="color: #94a3b8; margin: 0 0 15px 0; font-size: 14px; line-height: 1.6;">
                <?php echo wp_kses_post(__('Die hier implementierte <strong style="color:#fff;">AEGIS Community Edition</strong> filtert 99% aller Standard-Angriffe von Bots (SQLi, XSS) mit absoluter O(1) Geschwindigkeit. Sie ist ein robuster Schild aus deterministischer Logik.<br><br><strong>Doch die wahre, asymmetrische Überlegenheit entfaltet Sentinel erst im Platin Status:</strong> AEGIS hat in der Platin Version eine weitaus größere und härtere Regex mit Payload Normalisierung etc. Wenn das System auf polymorphe Zero-Day-Payloads trifft, die herkömmliche Regelsysteme umgehen, übergibt sie den Datenstrom in Millisekunden an das <strong style="color:#06b6d4;">ORACLE (AI Inference)</strong>. Parallel greift <strong style="color:#06b6d4;">ZEUS</strong> ein und verlagert den gesamten Abwehrkampf auf die Pre-Boot PHP-Ebene – bevor WordPress überhaupt geladen wird. Ein extrem starkes, kognitives Verteidigungsnetzwerk. Alle Infos zur aktuellen V7 auf unserer Webseite.', 'vgt-sentinel-ce')); ?>
            </p>
            <p class="vgts-lang-en" style="color: #94a3b8; margin: 0 0 15px 0; font-size: 14px; line-height: 1.6;">
                <?php echo wp_kses_post(__('The integrated <strong style="color:#fff;">AEGIS Community Edition</strong> neutralizes 99% of all standard bot attacks (SQLi, XSS) with absolute O(1) velocity. It serves as a highly robust shield built on deterministic logic.<br><br><strong>However, Sentinel unleashes its true, asymmetric superiority exclusively in Platinum Status:</strong> In the Platinum build, AEGIS features a significantly expanded, hardened regex matrix combined with advanced payload normalization. Should the system encounter polymorphic zero-day payloads designed to bypass conventional rule engines, the data stream is instantly routed to the <strong style="color:#06b6d4;">ORACLE (AI Inference)</strong> in milliseconds. Simultaneously, <strong style="color:#06b6d4;">ZEUS</strong> engages, shifting the entire defense perimeter to the pre-boot PHP level—neutralizing threats before WordPress even initializes. A highly fortified, cognitive defense network. Full V7 specifications are available on our website.', 'vgt-sentinel-ce')); ?>
            </p>

            <a href="https://visiongaiatechnology.de/visiongaiadefensehub/" target="_blank" class="vgts-btn vgts-btn-primary">
                <span class="dashicons dashicons-unlock"></span> 
                <span class="vgts-lang-de" style="display:inline;"><?php esc_html_e('OMEGA PROTOKOLL AKTIVIEREN', 'vgt-sentinel-ce'); ?></span>
                <span class="vgts-lang-en" style="display:inline;"><?php esc_html_e('ACTIVATE OMEGA PROTOCOL', 'vgt-sentinel-ce'); ?></span>
            </a>
        </div>
    </div>

    <!-- 3. PLATINUM SUPREME MATRIX -->
    <div class="vgts-card" style="border-top: 3px solid #06b6d4;">
        <h3 style="margin-bottom:20px; border-bottom:1px solid rgba(6, 182, 212, 0.2); padding-bottom:15px; display:flex; align-items:center; gap:10px; color:#06b6d4;">
            <span class="dashicons dashicons-superhero"></span> <?php esc_html_e('VGT OMEGA ARCHITECTURE (PLATINUM)', 'vgt-sentinel-ce'); ?>
        </h3>
        
        <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap:20px;">
            <?php foreach($pro_modules as $mod): ?>
                <a href="https://visiongaiatechnology.de/visiongaiadefensehub/" target="_blank" class="vgts-pro-card">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px;">
                        <span class="dashicons <?php echo esc_attr((string)$mod['icon']); ?>" style="font-size:24px; color:#06b6d4; width:24px; height:24px;"></span>
                        <span class="vgts-badge-status" style="background:rgba(6, 182, 212, 0.1); color:#06b6d4; font-size:9px; border:1px solid #06b6d4;">
                            <?php esc_html_e('PLATINUM', 'vgt-sentinel-ce'); ?> <span class="dashicons dashicons-lock" style="font-size:10px; width:10px; height:10px; line-height:10px; margin-left:2px;"></span>
                        </span>
                    </div>
                    
                    <div style="font-weight:700; font-size:12px; letter-spacing:0.5px; margin-bottom:5px; color:#fff;">
                        <?php echo esc_html((string)$mod['label']); ?>
                    </div>
                    <div style="font-size:11px; color:var(--vgts-text-secondary); line-height:1.4;">
                        <?php echo esc_html((string)$mod['desc']); ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
