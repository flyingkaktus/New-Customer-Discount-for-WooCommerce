<?php
/**
 * Plugin Name: Neukunden Rabatt System
 * Plugin URI: https://comingsoon.de
 * Description: Automatisches Rabattsystem für Neukunden mit E-Mail-Versand
 * Version: 0.0.3
 * Author: Maciej Suchowski
 * Author URI: https://comingsoon.de
 * License: GPLv2 or later
 * Text Domain: newcustomer-discount
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 *
 * @package NewCustomerDiscount
 */

if (!defined('ABSPATH')) {
    exit;
}

// Plugin-Konstanten definieren
define('NCD_VERSION', '0.0.3');
define('NCD_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('NCD_PLUGIN_URL', plugin_dir_url(__FILE__));
define('NCD_INCLUDES_DIR', NCD_PLUGIN_DIR . 'includes/');
define('NEWCUSTOMER_CUTOFF_DATE', '2024-01-01 00:00:00');

// Basis-Klassen laden
require_once NCD_INCLUDES_DIR . 'class-ncd-customer-tracker.php';
require_once NCD_INCLUDES_DIR . 'class-ncd-email-sender.php';
require_once NCD_INCLUDES_DIR . 'class-ncd-logo-manager.php';
require_once NCD_INCLUDES_DIR . 'class-ncd-coupon-generator.php';
require_once NCD_INCLUDES_DIR . 'class-ncd-updater.php';

// Admin-Klassen in richtiger Reihenfolge laden
require_once NCD_INCLUDES_DIR . 'admin/core/class-ncd-admin-base.php';
require_once NCD_INCLUDES_DIR . 'admin/core/class-ncd-admin-menu.php';
require_once NCD_INCLUDES_DIR . 'admin/class-ncd-admin.php';
require_once NCD_INCLUDES_DIR . 'admin/settings/class-ncd-admin-settings.php';
require_once NCD_INCLUDES_DIR . 'admin/templates/class-ncd-admin-templates.php';
require_once NCD_INCLUDES_DIR . 'admin/statistics/class-ncd-admin-statistics.php';
require_once NCD_INCLUDES_DIR . 'admin/customers/class-ncd-admin-customers.php';
require_once NCD_INCLUDES_DIR . 'admin/ajax/class-ncd-admin-ajax.php';

// Definiere Admin-Verzeichnis Konstante
define('NCD_ADMIN_DIR', NCD_INCLUDES_DIR . 'admin/');

// GitHub Updater initialisieren
if (is_admin()) {
    error_log('Attempting to initialize NCD_Updater');  // Neuer Debug-Log
    $updater = new NCD_Updater(__FILE__);
    error_log('NCD_Updater initialized');  // Neuer Debug-Log
}

/**
 * Plugin Initialisierung
 */
function ncd_init() {
    // Prüfe WooCommerce Abhängigkeit
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'ncd_woocommerce_notice');
        return;
    }
    
    // Lade Textdomain
    load_plugin_textdomain(
        'newcustomer-discount',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
    
    // Initialisiere Admin Bereich
    if (is_admin()) {
        new NCD_Admin();
    }
}
add_action('plugins_loaded', 'ncd_init');

/**
 * WooCommerce Abhängigkeitshinweis
 */
function ncd_woocommerce_notice() {
    ?>
    <div class="notice notice-error">
        <p>
            <?php _e('Das Neukunden Rabatt System benötigt WooCommerce. Bitte installieren und aktivieren Sie WooCommerce.', 'newcustomer-discount'); ?>
        </p>
    </div>
    <?php
}

/**
* Aktivierungshook
*/
function ncd_activate() {
    if (WP_DEBUG) {
        error_log('Starting NCD plugin activation');
    }
 
    try {
        // Erstelle notwendige Datenbanktabellen
        NCD_Customer_Tracker::activate();
        
        // Erstelle E-Mail Log Tabelle
        NCD_Email_Sender::create_log_table();
        
        // Setze Standard-Optionen
        add_option('ncd_delete_all_on_uninstall', false);
        add_option('ncd_discount_amount', 20);
        add_option('ncd_expiry_days', 30);
        add_option('ncd_email_subject', __('Dein persönlicher Neukundenrabatt von comingsoon.de', 'newcustomer-discount'));
        
        // Setze Capabilities
        $role = get_role('administrator');
        if ($role) {
            $role->add_cap('manage_customer_discounts');
        }
        
        // Erstelle Upload-Verzeichnis falls nötig
        $upload_dir = wp_upload_dir();
        $plugin_upload_dir = $upload_dir['basedir'] . '/newcustomer-discount';
        if (!file_exists($plugin_upload_dir)) {
            if (!wp_mkdir_p($plugin_upload_dir)) {
                throw new Exception('Failed to create upload directory: ' . $plugin_upload_dir);
            }
        }
 
        // Erstelle Admin-Verzeichnisstruktur
        $admin_dirs = [
            'core',
            'settings',
            'templates',
            'statistics',
            'customers',
            'ajax'
        ];
        
        foreach ($admin_dirs as $dir) {
            $dir_path = NCD_ADMIN_DIR . $dir;
            if (!file_exists($dir_path)) {
                if (!wp_mkdir_p($dir_path)) {
                    throw new Exception('Failed to create admin directory: ' . $dir_path);
                }
            }
        }
 
        // Setze Standard-Template
        if (!get_option('ncd_active_template')) {
            update_option('ncd_active_template', 'modern');
        }
 
        // Code-Einstellungen
        if (!get_option('ncd_code_prefix')) {
            update_option('ncd_code_prefix', 'NL');
        }
        if (!get_option('ncd_code_length')) {
            update_option('ncd_code_length', 6);
        }
        if (!get_option('ncd_code_chars')) {
            update_option('ncd_code_chars', ['numbers', 'uppercase']);
        }
 
        // Kunden-Einstellungen
        if (!get_option('ncd_cutoff_date')) {
            update_option('ncd_cutoff_date', NEWCUSTOMER_CUTOFF_DATE);
        }
        if (!get_option('ncd_order_count')) {
            update_option('ncd_order_count', 0);
        }
        if (!get_option('ncd_check_period')) {
            update_option('ncd_check_period', 'all');
        }
        if (!get_option('ncd_min_order_amount')) {
            update_option('ncd_min_order_amount', 0);
        }
        if (!get_option('ncd_excluded_categories')) {
            update_option('ncd_excluded_categories', []);
        }
        
        // Setze Version in Datenbank
        update_option('ncd_version', NCD_VERSION);
        
        // Cleanup Schedule erstellen
        if (!wp_next_scheduled('ncd_daily_cleanup')) {
            wp_schedule_event(time(), 'daily', 'ncd_daily_cleanup');
        }
        
        // Cache leeren
        wp_cache_flush();
 
        if (WP_DEBUG) {
            error_log('NCD plugin activation completed successfully');
        }
 
    } catch (Exception $e) {
        if (WP_DEBUG) {
            error_log('NCD plugin activation failed: ' . $e->getMessage());
        }
        
        // Füge Admin-Hinweis hinzu
        add_action('admin_notices', function() use ($e) {
            ?>
            <div class="notice notice-error">
                <p>
                    <?php echo sprintf(
                        __('Fehler bei der Plugin-Aktivierung: %s', 'newcustomer-discount'),
                        esc_html($e->getMessage())
                    ); ?>
                </p>
            </div>
            <?php
        });
    }
 }
register_activation_hook(__FILE__, 'ncd_activate');

/**
 * Deaktivierungshook
 */
function ncd_deactivate() {
    // Cleanup Schedule entfernen
    wp_clear_scheduled_hook('ncd_daily_cleanup');
    
    // Cache leeren
    wp_cache_flush();
}
register_deactivation_hook(__FILE__, 'ncd_deactivate');

/**
 * Täglicher Cleanup
 */
function ncd_do_daily_cleanup() {
    // Alte Tracking-Einträge bereinigen
    $customer_tracker = new NCD_Customer_Tracker();
    $customer_tracker->cleanup_old_entries();
    
    // Cache leeren
    wp_cache_flush();
}
add_action('ncd_daily_cleanup', 'ncd_do_daily_cleanup');

/**
 * Upgrade-Routine
 */
function ncd_check_version() {
    if (get_option('ncd_version') !== NCD_VERSION) {
        ncd_activate();
    }
}
add_action('plugins_loaded', 'ncd_check_version');

/**
 * Debugging Helper
 */
function ncd_log($message, $context = []) {
    if (WP_DEBUG) {
        error_log(sprintf(
            '[NewCustomerDiscount] %s | Context: %s',
            $message,
            json_encode($context)
        ));
    }
}

// WooCommerce Integration
require_once NCD_PLUGIN_DIR . 'includes/woocommerce-integration.php';