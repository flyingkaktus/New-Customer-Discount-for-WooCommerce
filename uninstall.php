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
    // Deinstallationsoptionen aus der Datenbank holen
    $delete_all = get_option('ncd_delete_all_on_uninstall', false);

    if ($delete_all) {
        global $wpdb;

        if (WP_DEBUG) {
            error_log('NCD: Starting database cleanup...');
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

        // Gutscheine löschen mit Fehlerprüfung
        $coupon_ids = $wpdb->get_col(
            "SELECT ID FROM {$wpdb->posts} p
             JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type = 'shop_coupon'
             AND pm.meta_key = '_ncd_generated'
             AND pm.meta_value = 'yes'"
        );

        if ($wpdb->last_error && WP_DEBUG) {
            error_log('NCD: Error fetching coupons: ' . $wpdb->last_error);
        }

        if (!empty($coupon_ids)) {
            foreach ($coupon_ids as $coupon_id) {
                $deleted = wp_delete_post($coupon_id, true);
                if (!$deleted && WP_DEBUG) {
                    error_log("NCD: Failed to delete coupon ID: $coupon_id");
                }
            }
        }

        // Optionen löschen mit Fehlerprüfung
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
            if (!delete_option($option) && WP_DEBUG) {
                error_log("NCD: Failed to delete option: $option");
            }
        }

        // Template-Einstellungen löschen
        $templates = ['modern', 'classic', 'minimal'];
        foreach ($templates as $template) {
            if (!delete_option('ncd_template_' . $template . '_settings') && WP_DEBUG) {
                error_log("NCD: Failed to delete template settings for: $template");
            }
        }

        // Transients mit Fehlerprüfung löschen
        $result = $wpdb->query(
            "DELETE FROM {$wpdb->options}
             WHERE option_name LIKE '_transient_ncd_%'
             OR option_name LIKE '_transient_timeout_ncd_%'"
        );
        
        if ($result === false && WP_DEBUG) {
            error_log('NCD: Failed to delete transients: ' . $wpdb->last_error);
        }

        // Verzeichnis-Bereinigung mit Fehlerprüfung
        $upload_dir = wp_upload_dir();
        $plugin_upload_dir = $upload_dir['basedir'] . '/newcustomer-discount';
        
        if (file_exists($plugin_upload_dir)) {
            require_once(ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php');
            require_once(ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php');
            
            try {
                $filesystem = new WP_Filesystem_Direct(null);
                if (!$filesystem->rmdir($plugin_upload_dir, true) && WP_DEBUG) {
                    error_log("NCD: Failed to remove upload directory: $plugin_upload_dir");
                }
            } catch (Exception $e) {
                if (WP_DEBUG) {
                    error_log('NCD: Filesystem error: ' . $e->getMessage());
                }
            }
        }

        // Benutzerberechtigungen entfernen
        $role = get_role('administrator');
        if ($role) {
            $role->remove_cap('manage_customer_discounts');
        }
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
    // Rethrow, damit WordPress die Fehlermeldung anzeigen kann
    throw $e;
}