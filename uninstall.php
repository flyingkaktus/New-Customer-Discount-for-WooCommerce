<?php
/**
 * Uninstall Script
 *
 * @package NewCustomerDiscount
 * @since 0.0.1
 */

// Wenn WordPress die Datei nicht direkt aufruft, abbrechen
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Deinstallationsoptionen aus der Datenbank holen
$delete_all = get_option('ncd_delete_all_on_uninstall', false);

// Wenn alle Daten gelöscht werden sollen
if ($delete_all) {
    global $wpdb;

    // Tracking Tabelle löschen
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}customer_discount_tracking");
    
    // Email Log Tabelle löschen
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}ncd_email_log");

    // Alle vom Plugin erstellten Gutscheine löschen
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

    // Logo-spezifische Optionen löschen
    delete_option('ncd_logo_base64');  // Das gespeicherte Base64-Logo

    // E-Mail Template Texte löschen
    delete_option('ncd_email_texts');  // Die benutzerdefinierten E-Mail-Texte
    
    // Template-spezifische Einstellungen für jedes Template löschen
    $templates = ['modern', 'classic', 'minimal'];
    foreach ($templates as $template) {
        delete_option('ncd_template_' . $template . '_settings');
    }

    // Allgemeine Plugin Optionen löschen
    $options = [
        'ncd_logo_base64',
        'ncd_delete_all_on_uninstall',
        'ncd_email_subject',
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
        'ncd_excluded_categories',
        'ncd_email_texts'
    ];

    foreach ($options as $option) {
        delete_option($option);
    }

    // Transients löschen
    $wpdb->query(
        "DELETE FROM {$wpdb->options}
         WHERE option_name LIKE '_transient_ncd_%'
         OR option_name LIKE '_transient_timeout_ncd_%'"
    );

    // Bereinige Benutzerberechtigungen
    $role = get_role('administrator');
    if ($role) {
        $role->remove_cap('manage_customer_discounts');
    }

    // Bereinige Upload-Verzeichnis
    $upload_dir = wp_upload_dir();
    $plugin_upload_dir = $upload_dir['basedir'] . '/newcustomer-discount';
    if (file_exists($plugin_upload_dir)) {
        require_once(ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php');
        require_once(ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php');
        $filesystem = new WP_Filesystem_Direct(null);
        $filesystem->rmdir($plugin_upload_dir, true);
    }
}

// Cache leeren
wp_cache_flush();