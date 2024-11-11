<?php
/**
 * Admin Customers Page Template
 *
 * @package NewCustomerDiscount
 * @since 0.0.1
 */

if (!defined('ABSPATH')) {
    exit;
}

$days = isset($_GET['days_filter']) ? (int)$_GET['days_filter'] : 30;
$only_new = isset($_GET['only_new']);

$stats = $this->customer_tracker->get_statistics();
?>

<div class="wrap ncd-wrap">
    <h1><?php _e('New Customer Overview', 'newcustomer-discount'); ?></h1>
    <?php settings_errors(); ?>
    <div class="ncd-info-boxes">
        <div class="ncd-info-box">
            <div class="ncd-info-box-inner">
                <div class="ncd-info-icon">
                    <span class="dashicons dashicons-groups"></span>
                </div>
                <div class="ncd-info-content">
                    <span class="ncd-info-label"><?php _e('Total', 'newcustomer-discount'); ?></span>
                    <span class="ncd-info-value"><?php echo esc_html($stats['total']); ?></span>
                </div>
            </div>
        </div>
        <div class="ncd-info-box">
            <div class="ncd-info-box-inner">
                <div class="ncd-info-icon">
                    <span class="dashicons dashicons-clock"></span>
                </div>
                <div class="ncd-info-content">
                    <span class="ncd-info-label"><?php _e('Pending', 'newcustomer-discount'); ?></span>
                    <span class="ncd-info-value"><?php echo esc_html($stats['pending']); ?></span>
                </div>
            </div>
        </div>
        <div class="ncd-info-box">
            <div class="ncd-info-box-inner">
                <div class="ncd-info-icon">
                    <span class="dashicons dashicons-email-alt"></span>
                </div>
                <div class="ncd-info-content">
                    <span class="ncd-info-label"><?php _e('Discount Sent', 'newcustomer-discount'); ?></span>
                    <span class="ncd-info-value"><?php echo esc_html($stats['sent']); ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="ncd-card">
        <form method="get" class="ncd-filter-form">
            <input type="hidden" name="page" value="new-customers">
            
            <select name="days_filter" class="ncd-select">
                <option value="7" <?php selected($days, 7); ?>>
                    <?php _e('Last 7 Days', 'newcustomer-discount'); ?>
                </option>
                <option value="30" <?php selected($days, 30); ?>>
                    <?php _e('Last 30 Days', 'newcustomer-discount'); ?>
                </option>
                <option value="90" <?php selected($days, 90); ?>>
                    <?php _e('Last 90 Days', 'newcustomer-discount'); ?>
                </option>
                <option value="365" <?php selected($days, 365); ?>>
                    <?php _e('Last Year', 'newcustomer-discount'); ?>
                </option>
            </select>

            <label class="ncd-checkbox-label">
                <input type="checkbox" 
                       name="only_new" 
                       value="1" 
                       <?php checked($only_new); ?>>
                <?php _e('New Customers Only', 'newcustomer-discount'); ?>
            </label>

            <button type="submit" class="button button-secondary">
                <?php _e('Apply Filter', 'newcustomer-discount'); ?>
            </button>
        </form>
    </div>

    <div class="notice notice-info">
        <p>
            <?php printf(
                __('New customers are counted as all customers who have not placed an order before %s.', 'newcustomer-discount'),
                date_i18n(get_option('date_format'), strtotime(get_option('ncd_cutoff_date', '2024-01-01')))
            ); ?>
        </p>
    </div>

    <div class="ncd-card">
        <table class="wp-list-table widefat fixed striped ncd-customers-table">
            <thead>
                <tr>
                    <th scope="col" class="ncd-col-email">
                        <?php _e('Email', 'newcustomer-discount'); ?>
                    </th>
                    <th scope="col" class="ncd-col-name">
                        <?php _e('Name', 'newcustomer-discount'); ?>
                    </th>
                    <th scope="col" class="ncd-col-date">
                        <?php _e('Order Date', 'newcustomer-discount'); ?>
                    </th>
                    <th scope="col" class="ncd-col-status">
                        <?php _e('Status', 'newcustomer-discount'); ?>
                    </th>
                    <th scope="col" class="ncd-col-code">
                        <?php _e('Discount Code', 'newcustomer-discount'); ?>
                    </th>
                    <th scope="col" class="ncd-col-sent">
                        <?php _e('Sent On', 'newcustomer-discount'); ?>
                    </th>
                    <th scope="col" class="ncd-col-actions">
                        <?php _e('Actions', 'newcustomer-discount'); ?>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($customers)): ?>
                    <tr>
                        <td colspan="7" class="ncd-no-items">
                            <?php _e('No customers found.', 'newcustomer-discount'); ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($customers as $customer): 
                        $is_new = $this->customer_tracker->is_new_customer($customer['customer_email']);
                        $has_coupon = !empty($customer['coupon_code']);
                    ?>
                        <tr>
                            <td class="ncd-col-email">
                                <?php echo esc_html($customer['customer_email']); ?>
                            </td>
                            <td class="ncd-col-name">
                                <?php echo esc_html($customer['customer_first_name'] . ' ' . $customer['customer_last_name']); ?>
                            </td>
                            <td class="ncd-col-date">
                                <?php echo date_i18n(
                                    get_option('date_format') . ' ' . get_option('time_format'), 
                                    strtotime($customer['created_at'])
                                ); ?>
                            </td>
                            <td class="ncd-col-status">
                                <?php if ($is_new): ?>
                                    <span class="ncd-status ncd-status-new">
                                        <?php _e('New Customer', 'newcustomer-discount'); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="ncd-status ncd-status-existing">
                                        <?php _e('Existing Customer', 'newcustomer-discount'); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="ncd-col-code">
                                <?php echo $has_coupon ? esc_html($customer['coupon_code']) : '-'; ?>
                            </td>
                            <td class="ncd-col-sent">
                                <?php echo !empty($customer['discount_email_sent']) 
                                    ? date_i18n(
                                        get_option('date_format') . ' ' . get_option('time_format'), 
                                        strtotime($customer['discount_email_sent'])
                                      ) 
                                    : '-'; ?>
                            </td>
                            <td class="ncd-col-actions">
                                <?php if ($is_new && !$has_coupon): ?>
                                    <button type="button" 
                                            class="button button-primary ncd-send-discount"
                                            data-email="<?php echo esc_attr($customer['customer_email']); ?>"
                                            data-first-name="<?php echo esc_attr($customer['customer_first_name']); ?>"
                                            data-last-name="<?php echo esc_attr($customer['customer_last_name']); ?>">
                                        <?php _e('Send Discount Code', 'newcustomer-discount'); ?>
                                    </button>
                                <?php elseif ($has_coupon): ?>
                                    <span class="ncd-sent-info" 
                                          title="<?php esc_attr_e('Discount code has been sent', 'newcustomer-discount'); ?>">
                                        ✓
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    new NCDCustomerManager();
});
</script>