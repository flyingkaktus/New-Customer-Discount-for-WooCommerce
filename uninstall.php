<?php
/**
 * Uninstall Script
 *
 * @package NewCustomerDiscount
 * @since 0.0.1
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Aktiviere Fehlerprotokollierung während der Deinstallation
if (WP_DEBUG) {
    error_log('Starting NCD plugin uninstallation...');
}

try {
    global $wpdb;

    if (WP_DEBUG) {
        error_log('NCD: Starting complete database cleanup...');
    }

    // Tracking Tabelle löschen
    $result = $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}customer_discount_tracking");
    if ($result === false && WP_DEBUG) {
        error_log('NCD: Failed to drop customer_discount_tracking table: ' . $wpdb->last_error);
    }
    
    // Email Log Tabelle löschen
    $result = $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}ncd_email_log");
    if ($result === false && WP_DEBUG) {
        error_log('NCD: Failed to drop ncd_email_log table: ' . $wpdb->last_error);
    }

    // Gutscheine löschen
    $coupon_ids = $wpdb->get_col(
        "SELECT ID FROM {$wpdb->posts} p
         JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
         WHERE p.post_type = 'shop_coupon'
         AND pm.meta_key = '_ncd_generated'
         AND pm.meta_value = 'yes'"
    );

    if (!empty($coupon_ids)) {
        foreach ($coupon_ids as $coupon_id) {
            wp_delete_post($coupon_id, true);
        }
    }

    // Alle Plugin-Optionen löschen
    $options = [
        'ncd_logo_base64',
        'ncd_email_subject',
        'ncd_email_texts',
        'ncd_discount_amount',
        'ncd_expiry_days',
        'ncd_active_template',
        'ncd_code_prefix',
        'ncd_code_length',
        'ncd_code_chars',
        'ncd_cutoff_date',
        'ncd_order_count',
        'ncd_check_period',
        'ncd_min_order_amount',
        'ncd_excluded_categories'
    ];

    foreach ($options as $option) {
        delete_option($option);
    }

    // Template-Einstellungen löschen
    $templates = ['modern', 'classic', 'minimal'];
    foreach ($templates as $template) {
        delete_option('ncd_template_' . $template . '_settings');
    }

    // Transients löschen
    $wpdb->query(
        "DELETE FROM {$wpdb->options}
         WHERE option_name LIKE '_transient_ncd_%'
         OR option_name LIKE '_transient_timeout_ncd_%'"
    );

    // Upload-Verzeichnis löschen
    $upload_dir = wp_upload_dir();
    $plugin_upload_dir = $upload_dir['basedir'] . '/new-customer-discount-for-woocommerce';
    
    if (file_exists($plugin_upload_dir)) {
        require_once(ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php');
        require_once(ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php');
        
        $filesystem = new WP_Filesystem_Direct(null);
        $filesystem->rmdir($plugin_upload_dir, true);
    }

    // Benutzerberechtigungen entfernen
    $role = get_role('administrator');
    if ($role) {
        $role->remove_cap('manage_customer_discounts');
    }

    // Cache leeren
    wp_cache_flush();

    if (WP_DEBUG) {
        error_log('NCD plugin uninstallation completed successfully');
    }

} catch (Exception $e) {
    if (WP_DEBUG) {
        error_log('NCD plugin uninstallation failed: ' . $e->getMessage());
    }
    throw $e;
}