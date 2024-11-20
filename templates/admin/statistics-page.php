<?php
/**
 * Admin Statistics Page Template
 *
 * @package NewCustomerDiscount
 * @since 0.0.1
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap ncd-wrap">
    <h1><?php _e('New Customer Discount Statistics', 'newcustomer-discount'); ?></h1>
    <?php settings_errors(); ?>
    <div class="ncd-stats-grid">
        <div class="ncd-stats-card">
            <h2><?php _e('Customers', 'newcustomer-discount'); ?></h2>
            <div class="ncd-stats-numbers">
                <div class="ncd-stat-item">
                    <span class="ncd-stat-label"><?php _e('Total', 'newcustomer-discount'); ?></span>
                    <span class="ncd-stat-value"><?php echo esc_html($stats['customers']['total']); ?></span>
                </div>
                <div class="ncd-stat-item">
                    <span class="ncd-stat-label"><?php _e('New Customers', 'newcustomer-discount'); ?></span>
                    <span class="ncd-stat-value"><?php echo esc_html($stats['customers']['pending']); ?></span>
                </div>
                <div class="ncd-stat-item">
                    <span class="ncd-stat-label"><?php _e('Discount Received', 'newcustomer-discount'); ?></span>
                    <span class="ncd-stat-value"><?php echo esc_html($stats['customers']['sent']); ?></span>
                </div>
            </div>
        </div>

        <div class="ncd-stats-card">
            <h2><?php _e('Vouchers', 'newcustomer-discount'); ?></h2>
            <div class="ncd-stats-numbers">
                <div class="ncd-stat-item">
                    <span class="ncd-stat-label"><?php _e('Created', 'newcustomer-discount'); ?></span>
                    <span class="ncd-stat-value"><?php echo esc_html($stats['Gutscheine']['total']); ?></span>
                </div>
                <div class="ncd-stat-item">
                    <span class="ncd-stat-label"><?php _e('Redeemed', 'newcustomer-discount'); ?></span>
                    <span class="ncd-stat-value"><?php echo esc_html($stats['Gutscheine']['used']); ?></span>
                </div>
                <div class="ncd-stat-item">
                    <span class="ncd-stat-label"><?php _e('Active', 'newcustomer-discount'); ?></span>
                    <span class="ncd-stat-value"><?php echo esc_html($stats['Gutscheine']['active']); ?></span>
                </div>
                <div class="ncd-stat-item">
                    <span class="ncd-stat-label"><?php _e('Expired', 'newcustomer-discount'); ?></span>
                    <span class="ncd-stat-value"><?php echo esc_html($stats['Gutscheine']['expired']); ?></span>
                </div>
            </div>
        </div>

        <div class="ncd-stats-card">
            <h2><?php _e('Emails', 'newcustomer-discount'); ?></h2>
            <div class="ncd-stats-numbers">
                <div class="ncd-stat-item">
                    <span class="ncd-stat-label"><?php _e('Sent', 'newcustomer-discount'); ?></span>
                    <span class="ncd-stat-value"><?php echo esc_html($stats['emails']['total_sent']); ?></span>
                </div>
                <div class="ncd-stat-item">
                    <span class="ncd-stat-label"><?php _e('Success Rate', 'newcustomer-discount'); ?></span>
                    <span class="ncd-stat-value"><?php echo number_format($stats['emails']['success_rate'], 1); ?>%</span>
                </div>
                <div class="ncd-stat-item">
                    <span class="ncd-stat-label"><?php _e('Last Sent', 'newcustomer-discount'); ?></span>
                    <span class="ncd-stat-value">
                        <?php echo $stats['emails']['last_sent'] ? 
                            date_i18n(get_option('date_format'), strtotime($stats['emails']['last_sent'])) : 
                            '-'; ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="ncd-performance-section">
        <h2><?php _e('Performance Metrics', 'newcustomer-discount'); ?></h2>
        <div class="ncd-stats-grid">
            <div class="ncd-stats-card">
                <h3><?php _e('Conversion Rate', 'newcustomer-discount'); ?></h3>
                <div class="ncd-big-number">
                    <?php 
                    $conversion_rate = ($stats['Gutscheine']['used'] / max(1, $stats['Gutscheine']['total'])) * 100;
                    echo number_format($conversion_rate, 1) . '%';
                    ?>
                </div>
                <p class="ncd-stats-description">
                    <?php _e('Percentage of redeemed vouchers', 'newcustomer-discount'); ?>
                </p>
            </div>

            <div class="ncd-stats-card">
                <h3><?php _e('Average Order Value', 'newcustomer-discount'); ?></h3>
                <div class="ncd-big-number">
                    <?php 
                    if (isset($stats['Gutscheine']['avg_order_value'])) {
                        echo wc_price($stats['Gutscheine']['avg_order_value']);
                    } else {
                        echo wc_price(0);
                    }
                    ?>
                </div>
                <p class="ncd-stats-description">
                    <?php _e('Average value of orders with voucher', 'newcustomer-discount'); ?>
                </p>
            </div>
        </div>
    </div>

    <div class="ncd-export-section">
        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
            <input type="hidden" name="action" value="ncd_export_statistics">
            <?php wp_nonce_field('ncd_export_statistics', 'ncd_export_nonce'); ?>
            <button type="submit" class="button button-primary">
                <?php _e('Export Statistics', 'newcustomer-discount'); ?>
            </button>
        </form>
    </div>

    <?php if (!empty($stats['emails']['monthly_stats'])): ?>
    <div class="ncd-trends-section">
        <h2><?php _e('Monthly Trends', 'newcustomer-discount'); ?></h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Month', 'newcustomer-discount'); ?></th>
                    <th><?php _e('Emails Sent', 'newcustomer-discount'); ?></th>
                    <th><?php _e('Successful Deliveries', 'newcustomer-discount'); ?></th>
                    <th><?php _e('Success Rate', 'newcustomer-discount'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stats['emails']['monthly_stats'] as $month => $month_stats): ?>
                <tr>
                    <td><?php echo date_i18n('F Y', strtotime($month)); ?></td>
                    <td><?php echo esc_html($month_stats['sent']); ?></td>
                    <td><?php echo esc_html($month_stats['success']); ?></td>
                    <td>
                        <?php 
                        $rate = ($month_stats['success'] / max(1, $month_stats['sent'])) * 100;
                        echo number_format($rate, 1) . '%';
                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>