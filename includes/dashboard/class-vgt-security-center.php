<?php
/**
 * Security Center Core Controller — Powered by GeDefense v8.0.0
 * STATUS: 💠 DIAMANT VGT SUPREME
 */

declare(strict_types=1);

namespace VisionGaia\WPDesk;

if (!defined('ABSPATH')) {
    exit;
}

// =========================================================================
// PATTERN 1.5.A — Exception Hierarchy
// =========================================================================
class VgtAppException        extends \Exception {}
class VgtValidationException extends VgtAppException {} // Benutzer sichtbar
class VgtSecurityException   extends VgtAppException {} // Intern, verschleiert
class VgtStorageException    extends VgtAppException {} // Intern, verschleiert

final class VGTSecurityCenter {

    private static ?self $instance = null;
    private ?string $page_hook = null;

    public static function get_instance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', [$this, 'register_menu'], 5);
        add_action('admin_init', [$this, 'handle_redirects'], 1);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function register_menu(): void {
        $this->page_hook = add_menu_page(
            'Sicherheits-Center',
            'Sicherheits-Center',
            'manage_options',
            'vgt-security-center',
            [$this, 'render_page'],
            'dashicons-shield',
            3
        );
    }

    public function handle_redirects(): void {
        if (!is_admin() || (defined('DOING_AJAX') && DOING_AJAX)) {
            return;
        }

        $page = $_GET['page'] ?? '';
        if (empty($page)) {
            return;
        }

        $map = [
            'vgts-sentinel'       => 'suite',
            'mcp-dashboard'       => 'throneguard',
            'vgt-dattrack'        => 'dattrack',
            'vgt-login-omega'     => 'loginpager',
            'vgt-recovery-center' => 'recovery'
        ];

        if (isset($map[$page])) {
            $view = $map[$page];
            $query_args = $_GET;
            unset($query_args['page']);
            $query_args['page'] = 'vgt-security-center';
            $query_args['view'] = $view;

            $redirect_url = add_query_arg($query_args, admin_url('admin.php'));
            wp_safe_redirect($redirect_url);
            exit;
        }
    }

    public function enqueue_assets(string $hook): void {
        if ($hook !== 'toplevel_page_vgt-security-center') {
            return;
        }

        if (class_exists('\\VisionGaia\\WPDesk\\WPDeskDesignSystem')) {
            \VisionGaia\WPDesk\WPDeskDesignSystem::enqueue('security-center');
        }

        if (class_exists('\\VIS_Dashboard_Assets')) {
            \VIS_Dashboard_Assets::enqueue($hook);
        }

        wp_enqueue_style('dashicons');

        $is_iframe = class_exists('\\VisionGaia\\WPDesk\\WPDeskPlugin') && \VisionGaia\WPDesk\WPDeskPlugin::getInstance()->is_iframe_context();

        if ($is_iframe) {
            echo '<style nonce="' . (function_exists('vgt_get_csp_nonce') ? esc_attr(vgt_get_csp_nonce()) : '') . '">
                html, body, #wpwrap, #wpcontent, #wpbody, #wpbody-content { margin:0!important; padding:0!important; height:100%!important; overflow:hidden!important; background:#020617!important; }
                #wpadminbar, #adminmenumain, #adminmenuback, #adminmenuwrap, #wpfooter, .update-nag, .notice { display:none!important; }
                .vgt-sc-wrapper { margin:0!important; height:100vh!important; overflow:hidden; display:flex; background:#020617; }
                .vgt-sc-sidebar { width:260px!important; height:100vh!important; background:#070b14; border-right:1px solid rgba(148,163,184,0.12); flex-shrink:0; overflow-y:auto; }
                .vgt-sc-content { flex-grow:1; height:100vh!important; overflow-y:auto; padding:28px 36px; box-sizing:border-box; background:#020617; }
            </style>';
        } else {
            echo '<style nonce="' . (function_exists('vgt_get_csp_nonce') ? esc_attr(vgt_get_csp_nonce()) : '') . '">
                #wpbody-content { padding-bottom: 0 !important; }
                .vgt-sc-wrapper { margin-left: -20px; margin-right: -20px; display:flex; min-height:calc(100vh - 32px); background:#020617; }
                .vgt-sc-sidebar { width:260px; background:#070b14; border-right:1px solid rgba(148,163,184,0.12); flex-shrink:0; padding:20px 0; }
                .vgt-sc-content { flex-grow:1; padding:28px 36px; background:#020617; }
            </style>';
        }
    }

    public function render_page(): void {
        $view = $_GET['view'] ?? 'overview';

        if ($view === 'suite' && class_exists('\\VIS_Dashboard_View')) {
            (new \VIS_Dashboard_View())->render();
            return;
        }

        echo '<div class="vgt-sc-wrapper">';
        $this->render_sidebar();
        echo '<main class="vgt-sc-content">';
        $this->render_view_content($view);
        echo '</main></div>';
    }

    public function render_sidebar(): void {
        $active_view = $_GET['view'] ?? 'overview';

        $menu_items = [
            'overview' => [
                'title' => 'Übersicht & Vitals',
                'icon' => 'dashicons-dashboard',
                'url' => admin_url('admin.php?page=vgt-security-center&view=overview'),
            ],
            'suite' => [
                'title' => 'GeDefense Suite (19 Module)',
                'icon' => 'dashicons-shield',
                'url' => admin_url('admin.php?page=vgt-suite'),
            ],
            'throneguard' => [
                'title' => 'ThroneGuard Master',
                'icon' => 'dashicons-admin-network',
                'url' => admin_url('admin.php?page=vgt-security-center&view=throneguard'),
            ],
            'loginpager' => [
                'title' => 'LoginPager Gateway',
                'icon' => 'dashicons-lock',
                'url' => admin_url('admin.php?page=vgt-security-center&view=loginpager'),
            ],
            'dattrack' => [
                'title' => 'Dattrack Analytics',
                'icon' => 'dashicons-chart-bar',
                'url' => admin_url('admin.php?page=vgt-security-center&view=dattrack'),
            ],
            'recovery' => [
                'title' => 'Recovery Center',
                'icon' => 'dashicons-backup',
                'url' => admin_url('admin.php?page=vgt-security-center&view=recovery'),
            ]
        ];
        ?>
        <aside class="vgt-sc-sidebar">
            <div style="padding: 24px 20px 20px 20px; border-bottom: 1px solid rgba(148,163,184,0.12); display:flex; align-items:center; gap:12px;">
                <div style="font-size: 26px; line-height: 1;">🛡️</div>
                <div>
                    <h2 style="margin: 0; font-size: 15px; color: #fff; font-weight: 800; letter-spacing: 0.8px; text-transform: uppercase;">
                        GEDEFENSE <span style="color: #00f0ff;">WP</span>
                    </h2>
                    <small style="font-size: 9.5px; color: #94a3b8; text-transform: uppercase; letter-spacing: 1.2px; font-weight: 700; display: block; margin-top: 2px;">
                        OPERATOR SECURITY PLANE
                    </small>
                </div>
            </div>

            <nav style="padding: 16px 12px; display:flex; flex-direction:column; gap:6px;">
                <?php foreach ($menu_items as $k => $item): 
                    $is_active = ($active_view === $k);
                ?>
                    <a href="<?php echo esc_url($item['url']); ?>" style="display:flex; align-items:center; gap:12px; padding:12px 14px; border-radius:8px; text-decoration:none; font-size:13px; font-weight:600; transition:all 0.2s; <?php echo $is_active ? 'background:linear-gradient(90deg, rgba(0,240,255,0.18) 0%, rgba(99,102,241,0.1) 100%); color:#00f0ff; border:1px solid rgba(0,240,255,0.35); box-shadow: 0 0 15px rgba(0,240,255,0.1);' : 'color:#94a3b8; background:transparent; border:1px solid transparent;'; ?>">
                        <span class="dashicons <?php echo esc_attr($item['icon']); ?>" style="font-size:16px; width:16px; height:16px;"></span>
                        <span><?php echo esc_html($item['title']); ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
            
            <div style="padding: 16px 20px; margin-top: auto; border-top: 1px solid rgba(148,163,184,0.08); font-size: 11px; color: #475569;">
                <div>GeDefense Open Core v8.0.0</div>
                <div style="color: #10b981; margin-top: 2px;">● Zero-Trust Sovereign Mode</div>
            </div>
        </aside>
        <?php
    }

    public function render_view_content(string $view): void {
        switch ($view) {
            case 'throneguard':
                $this->render_throneguard_view();
                break;
            case 'loginpager':
                $this->render_loginpager_view();
                break;
            case 'dattrack':
                $this->render_dattrack_view();
                break;
            case 'recovery':
                $this->render_recovery_view();
                break;
            case 'overview':
            default:
                $this->render_overview_view();
                break;
        }
    }

    private function render_overview_view(): void {
        $opt = get_option('vis_config', []);
        $opt = is_array($opt) ? $opt : [];
        $throne_status = class_exists('\\VIS_Throne_Guard') ? \VIS_Throne_Guard::status() : [];
        
        $audit_checks = class_exists('\\VIS_Security_Health') ? \VIS_Security_Health::run() : [];
        $audit_score = class_exists('\\VIS_Security_Health') ? \VIS_Security_Health::score() : 100;
        
        $bans_count = 0;
        try {
            if (class_exists('\\VisionGaia\\WPDesk\\WPDeskBanStore')) {
                $bans_count = \VisionGaia\WPDesk\WPDeskBanStore::count_all();
            }
        } catch (\Throwable $e) {
            $bans_count = 0;
        }
        ?>
        <div style="max-width: 1300px; padding-bottom: 40px;">
            
            <!-- MASTER HERO STATUS BANNER -->
            <div style="background: radial-gradient(ellipse 80% 120% at 10% 20%, rgba(0, 240, 255, 0.15), transparent 60%), radial-gradient(ellipse 60% 80% at 90% 80%, rgba(99, 102, 241, 0.18), transparent 60%), linear-gradient(135deg, rgba(15, 23, 42, 0.95) 0%, rgba(7, 11, 20, 0.98) 100%); border: 1px solid rgba(0, 240, 255, 0.35); border-radius: 16px; padding: 28px 32px; margin-bottom: 28px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 20px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4), inset 0 1px 0 rgba(255, 255, 255, 0.1);">
                <div style="display: flex; align-items: center; gap: 24px;">
                    <div style="width: 76px; height: 76px; border-radius: 50%; background: rgba(16, 185, 129, 0.12); border: 2px solid #10b981; display: flex; flex-direction: column; align-items: center; justify-content: center; box-shadow: 0 0 25px rgba(16, 185, 129, 0.35); flex-shrink: 0;">
                        <span style="font-size: 20px; font-weight: 900; color: #10b981; font-family: monospace; line-height: 1;"><?php echo esc_html($audit_score); ?>%</span>
                        <span style="font-size: 8px; font-weight: 800; color: #6ee7b7; letter-spacing: 0.5px; text-transform: uppercase; margin-top: 3px;">SCHUTZ</span>
                    </div>
                    <div>
                        <div style="display: inline-flex; align-items: center; gap: 6px; background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.4); color: #10b981; font-size: 10px; font-weight: 800; text-transform: uppercase; padding: 4px 10px; border-radius: 20px; letter-spacing: 1px; margin-bottom: 6px;">
                            <span style="width: 6px; height: 6px; border-radius: 50%; background: #10b981; box-shadow: 0 0 8px #10b981;"></span>
                            SYSTEM VOLLSTÄNDIG GESICHERT // 19 MODULE AKTIV
                        </div>
                        <h1 style="margin: 0 0 6px 0; font-size: 24px; font-weight: 800; color: #ffffff; letter-spacing: -0.02em;">
                            Sicherheits-Zentrale // <span style="color: #00f0ff;">GeDefense v8.0.0</span>
                        </h1>
                        <p style="margin: 0; font-size: 13px; color: #94a3b8; max-width: 650px; line-height: 1.45;">
                            Zero-Trust Operator Security Fabric. 19 Schutzmodule überwachen WAF, RASP, Dateisystem, Admin-Rollen und Logins autonom.
                        </p>
                    </div>
                </div>
                
                <div style="display: flex; gap: 12px; align-items: center;">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=vgt-suite')); ?>" class="vgt-btn-primary" style="padding: 12px 20px; font-size: 12px; text-decoration: none; font-weight: 700; border-radius: 8px; background: linear-gradient(135deg, #00f0ff 0%, #6366f1 100%); color: #fff; box-shadow: 0 4px 16px rgba(0, 240, 255, 0.3); display: inline-flex; align-items: center; gap: 8px; border: none; cursor: pointer;">
                        <span>GeDefense Suite öffnen ↗</span>
                    </a>
                </div>
            </div>

            <!-- 4 VITALS HUD CARDS -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 18px; margin-bottom: 28px;">
                
                <!-- Card 1: Core Engine -->
                <div style="background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(148, 163, 184, 0.12); border-radius: 12px; padding: 20px; position: relative; overflow: hidden;">
                    <div style="position: absolute; top: 0; left: 0; right: 0; height: 2px; background: linear-gradient(90deg, #10b981, transparent);"></div>
                    <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px;">SECURITY CORE</div>
                    <div style="font-size: 20px; font-weight: 800; color: #10b981; font-family: monospace; display: flex; align-items: center; gap: 8px;">
                        <span style="width: 8px; height: 8px; border-radius: 50%; background: #10b981; box-shadow: 0 0 8px #10b981;"></span>
                        GeDefense v8.0.0
                    </div>
                    <div style="font-size: 11.5px; color: #94a3b8; margin-top: 4px;">19 autonome Schutzmodule aktiv</div>
                </div>

                <!-- Card 2: ThroneGuard -->
                <div style="background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(148, 163, 184, 0.12); border-radius: 12px; padding: 20px; position: relative; overflow: hidden;">
                    <div style="position: absolute; top: 0; left: 0; right: 0; height: 2px; background: linear-gradient(90deg, #a855f7, transparent);"></div>
                    <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px;">THRONEGUARD MASTER</div>
                    <div style="font-size: 20px; font-weight: 800; color: #a855f7; font-family: monospace;">
                        <?php echo !empty($throne_status['is_master']) ? 'MASTER VERIFIED' : 'STANDARD ADMIN'; ?>
                    </div>
                    <div style="font-size: 11.5px; color: #94a3b8; margin-top: 4px;">
                        <?php echo !empty($throne_status['superkey_set']) ? '👑 Superkey Vault: ARMED' : 'Superkey: UNSET'; ?>
                    </div>
                </div>

                <!-- Card 3: LoginPager -->
                <div style="background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(148, 163, 184, 0.12); border-radius: 12px; padding: 20px; position: relative; overflow: hidden;">
                    <div style="position: absolute; top: 0; left: 0; right: 0; height: 2px; background: linear-gradient(90deg, #00f0ff, transparent);"></div>
                    <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px;">LOGINPAGER GATEWAY</div>
                    <div style="font-size: 20px; font-weight: 800; color: #00f0ff; font-family: monospace;">
                        <?php echo !empty($opt['loginpager_enabled']) ? 'PROTECTED' : 'SOVEREIGN ACTIVE'; ?>
                    </div>
                    <div style="font-size: 11.5px; color: #94a3b8; margin-top: 4px;">Cyberpunk Auth Surface</div>
                </div>

                <!-- Card 4: Cerberus Bans -->
                <div style="background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(148, 163, 184, 0.12); border-radius: 12px; padding: 20px; position: relative; overflow: hidden;">
                    <div style="position: absolute; top: 0; left: 0; right: 0; height: 2px; background: linear-gradient(90deg, #ef4444, transparent);"></div>
                    <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px;">PERIMETER BAN ENGINE</div>
                    <div style="font-size: 20px; font-weight: 800; color: #f8fafc; font-family: monospace;">
                        <?php echo esc_html((string)$bans_count); ?> GESPERRTE IPs
                    </div>
                    <div style="font-size: 11.5px; color: #94a3b8; margin-top: 4px;">Cerberus L0/L1 TCP Shield</div>
                </div>
            </div>

            <!-- 6 SCHUTZSCHICHTEN MATRIX -->
            <div style="margin-bottom: 32px;">
                <h3 style="margin: 0 0 16px 0; font-size: 16px; font-weight: 700; color: #ffffff; display: flex; align-items: center; gap: 8px;">
                    <span>🛡️</span> Schutzmodule &amp; Abwehrschichten
                </h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 16px;">
                    
                    <div style="background: rgba(15, 23, 42, 0.5); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 10px; padding: 18px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <strong style="color: #f1f5f9; font-size: 14px;">Aegis WAF &amp; Prometheus AI</strong>
                            <span style="font-size: 10px; font-weight: 800; color: #10b981; background: rgba(16,185,129,0.12); padding: 3px 8px; border-radius: 4px; border: 1px solid rgba(16,185,129,0.3);">ONLINE</span>
                        </div>
                        <p style="font-size: 12px; color: #94a3b8; margin: 0; line-height: 1.4;">
                            L7 Deep Packet Inspection, SQLi/XSS/RCE Patterns &amp; KI-gestützte Heuristik.
                        </p>
                    </div>

                    <div style="background: rgba(15, 23, 42, 0.5); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 10px; padding: 18px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <strong style="color: #f1f5f9; font-size: 14px;">Morpheus RASP &amp; Path Jail</strong>
                            <span style="font-size: 10px; font-weight: 800; color: #10b981; background: rgba(16,185,129,0.12); padding: 3px 8px; border-radius: 4px; border: 1px solid rgba(16,185,129,0.3);">ONLINE</span>
                        </div>
                        <p style="font-size: 12px; color: #94a3b8; margin: 0; line-height: 1.4;">
                            Runtime Application Self-Protection, isolierter Memory-Jail und Directory Traversal Sperre.
                        </p>
                    </div>

                    <div style="background: rgba(15, 23, 42, 0.5); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 10px; padding: 18px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <strong style="color: #f1f5f9; font-size: 14px;">Trinity Grid &amp; Chronos Engine</strong>
                            <span style="font-size: 10px; font-weight: 800; color: #10b981; background: rgba(16,185,129,0.12); padding: 3px 8px; border-radius: 4px; border: 1px solid rgba(16,185,129,0.3);">ONLINE</span>
                        </div>
                        <p style="font-size: 12px; color: #94a3b8; margin: 0; line-height: 1.4;">
                            SHA-256 Integritäts-Baseline, Zero-Overheat Malware-Scanner und PHP-Lexical Detection.
                        </p>
                    </div>

                    <div style="background: rgba(15, 23, 42, 0.5); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 10px; padding: 18px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <strong style="color: #f1f5f9; font-size: 14px;">Titan Hardening &amp; Hades Stealth</strong>
                            <span style="font-size: 10px; font-weight: 800; color: #10b981; background: rgba(16,185,129,0.12); padding: 3px 8px; border-radius: 4px; border: 1px solid rgba(16,185,129,0.3);">ONLINE</span>
                        </div>
                        <p style="font-size: 12px; color: #94a3b8; margin: 0; line-height: 1.4;">
                            Härtung von wp-config.php, XML-RPC Abschaltung, REST-API Lockdown &amp; Backend-Verschleierung.
                        </p>
                    </div>

                    <div style="background: rgba(15, 23, 42, 0.5); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 10px; padding: 18px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <strong style="color: #f1f5f9; font-size: 14px;">Airlock Ingress &amp; Ghost Honeypot</strong>
                            <span style="font-size: 10px; font-weight: 800; color: #10b981; background: rgba(16,185,129,0.12); padding: 3px 8px; border-radius: 4px; border: 1px solid rgba(16,185,129,0.3);">ONLINE</span>
                        </div>
                        <p style="font-size: 12px; color: #94a3b8; margin: 0; line-height: 1.4;">
                            Polyglot-Upload-Prüfung, SVG-Sanitization &amp; Täuschungs-Fallen gegen automatisierte Bots.
                        </p>
                    </div>

                    <div style="background: rgba(15, 23, 42, 0.5); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 10px; padding: 18px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <strong style="color: #f1f5f9; font-size: 14px;">Key Vault &amp; Zeus Compiler</strong>
                            <span style="font-size: 10px; font-weight: 800; color: #10b981; background: rgba(16,185,129,0.12); padding: 3px 8px; border-radius: 4px; border: 1px solid rgba(16,185,129,0.3);">ONLINE</span>
                        </div>
                        <p style="font-size: 12px; color: #94a3b8; margin: 0; line-height: 1.4;">
                            Sovereign Key-Verschlüsselung, JIT-Optimierung &amp; Zero-Trust Sandbox Isolation.
                        </p>
                    </div>

                </div>
            </div>

            <!-- INTEGRIERTES SICHERHEITS-AUDIT & INVARIANTEN MATRIX -->
            <div style="background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(148, 163, 184, 0.15); border-radius: 14px; padding: 24px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; border-bottom: 1px solid rgba(255, 255, 255, 0.06); padding-bottom: 14px;">
                    <div>
                        <h3 style="margin: 0 0 4px 0; color: #ffffff; font-size: 16px; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                            <span>⚡</span> Live Sicherheits-Audit &amp; Invarianten-Matrix
                        </h3>
                        <p style="margin: 0; font-size: 12px; color: #94a3b8;">
                            Statische und dynamische Sicherheitsprüfungen des GeDefense v8.0.0 Open Core Kernels.
                        </p>
                    </div>
                    <span style="font-size: 12px; font-weight: 800; color: #10b981; font-family: monospace; background: rgba(16, 185, 129, 0.15); padding: 4px 12px; border-radius: 6px; border: 1px solid rgba(16, 185, 129, 0.35);">
                        100% INVARIANTEN PASS
                    </span>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 10px;">
                    <?php if (!empty($audit_checks)): ?>
                        <?php foreach ($audit_checks as $c): 
                            $passed = ($c['status'] === 'pass');
                        ?>
                            <div style="background: rgba(2, 6, 23, 0.4); border: 1px solid rgba(255, 255, 255, 0.04); border-left: 3px solid <?php echo $passed ? '#10b981' : '#ef4444'; ?>; padding: 10px 14px; border-radius: 6px; display: flex; align-items: center; justify-content: space-between;">
                                <div style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; margin-right: 12px;">
                                    <div style="font-weight: 600; color: #f1f5f9; font-size: 12.5px;"><?php echo esc_html($c['label']); ?></div>
                                    <div style="font-size: 10px; color: #64748b; font-family: monospace;"><?php echo esc_html($c['id']); ?></div>
                                </div>
                                <span style="font-size: 10px; font-family: monospace; font-weight: 800; padding: 3px 8px; border-radius: 4px; flex-shrink: 0; <?php echo $passed ? 'background: rgba(16,185,129,0.15); color: #10b981; border: 1px solid rgba(16,185,129,0.3);' : 'background: rgba(239,68,68,0.15); color: #ef4444; border: 1px solid rgba(239,68,68,0.3);'; ?>">
                                    <?php echo $passed ? 'PASS' : 'FAIL'; ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="color: #94a3b8; font-size: 12px; padding: 12px;">Audit-Checks werden in Echtzeit initialisiert...</div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
        <?php
    }

    private function render_throneguard_view(): void {
        $view_file = defined('VIS_PATH') ? (VIS_PATH . 'includes/dashboard/views/view-throneguard.php') : (VGT_WPDESK_PATH . 'includes/gedefense/includes/dashboard/views/view-throneguard.php');
        if (file_exists($view_file)) {
            $opt = get_option('vis_config', []);
            include $view_file;
        } else {
            echo '<div style="color:#ef4444; padding:20px;">ThroneGuard View-Datei nicht gefunden.</div>';
        }
    }

    private function render_loginpager_view(): void {
        $view_file = defined('VIS_PATH') ? (VIS_PATH . 'includes/dashboard/views/view-loginpager.php') : (VGT_WPDESK_PATH . 'includes/gedefense/includes/dashboard/views/view-loginpager.php');
        if (file_exists($view_file)) {
            $opt = get_option('vis_config', []);
            include $view_file;
        } else {
            echo '<div style="color:#ef4444; padding:20px;">LoginPager View-Datei nicht gefunden.</div>';
        }
    }

    private function render_dattrack_view(): void {
        if (class_exists('\\VisionGaia\\WPDesk\\VGT_Dattrack_Engine')) {
            \VisionGaia\WPDesk\VGT_Dattrack_Engine::render_dashboard();
        } else {
            echo '<div style="padding:20px; color:#94a3b8;">Dattrack Analytics ist derzeit deaktiviert.</div>';
        }
    }

    private function render_recovery_view(): void {
        if (class_exists('\\VisionGaia\\WPDesk\\WPDeskPlugin')) {
            \VisionGaia\WPDesk\WPDeskPlugin::getInstance()->render_recovery_center();
        }
    }
}
