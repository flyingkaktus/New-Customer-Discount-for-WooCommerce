<?php
/**
 * WooCommerce Integration
 *
 * @package NewCustomerDiscount
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Speichert den verwendeten Gutscheincode und aktualisiert den Status
 *
 * @param int $order_id Die Bestellungs-ID
 */
function ncd_save_used_coupon_code($order_id)
{
    $order = wc_get_order($order_id);
    if (!$order)
        return;

    $used_coupons = $order->get_coupon_codes();
    if (empty($used_coupons))
        return;

    global $wpdb;
    $tracking_table = $wpdb->prefix . 'customer_discount_tracking';

    foreach ($used_coupons as $coupon_code) {
        // Prüfe ob der Gutschein ein NCD-Gutschein ist
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tracking_table WHERE coupon_code = %s",
            $coupon_code
        ));

        if ($exists) {
            // Speichere den verwendeten Gutscheincode
            update_post_meta($order_id, '_used_coupon_code', $coupon_code);

            // Aktualisiere den Status in der Tracking-Tabelle
            $wpdb->update(
                $tracking_table,
                ['status' => 'used'],
                ['coupon_code' => $coupon_code],
                ['%s'],
                ['%s']
            );

            // Debug-Logging
            if (WP_DEBUG) {
                error_log('NCD Coupon used:');
                error_log('Order ID: ' . $order_id);
                error_log('Coupon Code: ' . $coupon_code);
            }

            break; // Wir haben den NCD-Gutschein gefunden und verarbeitet
        }
    }
}
add_action('woocommerce_new_order', 'ncd_save_used_coupon_code', 10);