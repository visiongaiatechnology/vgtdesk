<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * VIEW: CERBERUS PERIMETER GUARD
 * STATUS: PLATIN VGT STATUS (Hardened & i18n)
 * MODULE: LAYER 1 PERIMETER DEFENSE & IP BAN MANAGEMENT
 * TEXTDOMAIN: vgt-sentinel-ce
 */

global $wpdb;
$table_name = defined('VGTS_TABLE_BANS') ? VGTS_TABLE_BANS : 'vgts_apex_bans';
$table_bans = $wpdb->prefix . $table_name;

$bans_per_page = 20; 
// [WP.ORG COMPLIANCE]: Strict type casting and sanitization for pagination
$current_page = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
$offset       = ($current_page - 1) * $bans_per_page;

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$total_bans   = (int) $wpdb->get_var("SELECT COUNT(id) FROM {$table_bans}");
$total_pages  = (int) ceil($total_bans / $bans_per_page);

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$bans = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$table_bans} ORDER BY banned_at DESC LIMIT %d OFFSET %d",
    $bans_per_page, 
    $offset
));
?>

<div class="vgts-cerberus-wrap">
    <header class="vgts-module-header vgts-cerberus-header">
        <h2><?php esc_html_e('Cerberus', 'vgt-sentinel-ce'); ?> <span><?php esc_html_e('Perimeter Guard', 'vgt-sentinel-ce'); ?></span></h2>
        <p><?php esc_html_e('Global IP ban management at the Opcache level.', 'vgt-sentinel-ce'); ?></p>
    </header>

    <div class="vgts-card">
        <div class="vgts-cerberus-stats">
            <div>
                <h3><?php esc_html_e('Active System Bans (Layer 1)', 'vgt-sentinel-ce'); ?></h3>
                <p style="margin-top: 5px; color: #94a3b8;">
                    <?php esc_html_e('These IPs have been permanently blocked by Cerberus or AEGIS and will be rejected before the WordPress boot process.', 'vgt-sentinel-ce'); ?>
                </p>
            </div>
            <div>
                <?php esc_html_e('Total blocked IPs:', 'vgt-sentinel-ce'); ?> 
                <span class="vgts-cerberus-stats-highlight"><?php echo esc_html(number_format_i18n($total_bans)); ?></span>
            </div>
        </div>
        
        <div class="vgts-cerberus-table-wrap">
            <table class="vgts-cerberus-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('IP Address', 'vgt-sentinel-ce'); ?></th>
                        <th><?php esc_html_e('Timestamp', 'vgt-sentinel-ce'); ?></th>
                        <th><?php esc_html_e('Block Reason (Isolated)', 'vgt-sentinel-ce'); ?></th>
                        <th style="text-align: right;"><?php esc_html_e('Action', 'vgt-sentinel-ce'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($bans)): ?>
                        <tr>
                            <td colspan="4" style="padding: 40px; text-align: center; color: #64748b;">
                                <span class="dashicons dashicons-shield" style="font-size: 40px; width: 40px; height: 40px; opacity: 0.5; margin-bottom: 15px;"></span><br>
                                <?php esc_html_e('Perimeter clean. No active blocks in the database.', 'vgt-sentinel-ce'); ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($bans as $ban): ?>
                            <tr>
                                <td>
                                    <span class="vgts-cerberus-ip"><?php echo esc_html((string)$ban->ip); ?></span>
                                </td>
                                <td>
                                    <span class="vgts-cerberus-time">
                                        <?php echo esc_html(wp_date(get_option('date_format') . ' H:i:s', strtotime($ban->banned_at))); ?>
                                    </span>
                                </td>
                                <td>
                                    <pre class="vgts-cerberus-payload"><?php 
                                        echo esc_html((string)$ban->reason); 
                                        if (!empty($ban->request_uri)) {
                                            echo "\n\n" . esc_html__('Target:', 'vgt-sentinel-ce') . " " . esc_html((string)$ban->request_uri);
                                        }
                                    ?></pre>
                                </td>
                                <td style="text-align: right;">
                                    <button type="button" class="vgts-cerberus-btn-unban" data-ip="<?php echo esc_attr((string)$ban->ip); ?>">
                                        <span class="dashicons dashicons-unlock"></span> <?php esc_html_e('Unban', 'vgt-sentinel-ce'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php 
        if ($total_pages > 1): 
            $pagination_links = paginate_links([
                'base'      => add_query_arg('paged', '%#%'),
                'format'    => '',
                'prev_text' => esc_html__('&laquo; Previous', 'vgt-sentinel-ce'),
                'next_text' => esc_html__('Next &raquo;', 'vgt-sentinel-ce'),
                'total'     => $total_pages,
                'current'   => $current_page,
                'type'      => 'plain'
            ]);
            
            if ($pagination_links) {
                echo '<div class="vgts-pagination">' . wp_kses_post($pagination_links) . '</div>';
            }
        endif; 
        ?>

    </div>
</div>
