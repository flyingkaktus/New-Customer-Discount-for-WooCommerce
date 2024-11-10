<?php
/**
 * Plugin Name: New-Customer-Gutschein for WooCommerce
 * Plugin URI: https://comingsoon.de
 * Description: Automatisches Rabattsystem für Neukunden mit E-Mail-Versand
 * Version: 1.0.0
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

if (file_exists(plugin_dir_path(__FILE__) . 'vendor/autoload.php')) {
    require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';
}

// Plugin-Konstanten definieren
define('NCD_VERSION', '1.0.0');
define('NCD_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('NCD_PLUGIN_URL', plugin_dir_url(__FILE__));
define('NCD_INCLUDES_DIR', NCD_PLUGIN_DIR . 'includes/');
define('NCD_ASSETS_URL', NCD_PLUGIN_URL . 'assets/');
define('NEWCUSTOMER_CUTOFF_DATE', '2024-01-01 00:00:00');

// Basis-Klassen laden
require_once NCD_INCLUDES_DIR . 'class-ncd-customer-tracker.php';
require_once NCD_INCLUDES_DIR . 'class-ncd-email-sender.php';
require_once NCD_INCLUDES_DIR . 'class-ncd-logo-manager.php';
require_once NCD_INCLUDES_DIR . 'class-ncd-discount-generator.php';
require_once NCD_INCLUDES_DIR . 'class-ncd-updater.php';

// Admin-Klassen in richtiger Reihenfolge laden
require_once NCD_INCLUDES_DIR . 'admin/core/class-ncd-admin-base.php';
require_once NCD_INCLUDES_DIR . 'admin/core/class-ncd-admin-menu.php';
require_once NCD_INCLUDES_DIR . 'admin/core/class-ncd-admin-tab-manager.php';
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
    $updater = new NCD_Updater(__FILE__);
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
        $admin = new NCD_Admin();
        
        // Hook für Asset Loading registrieren
        add_action('admin_enqueue_scripts', [$admin, 'enqueue_common_assets']);
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
            <?php _e('Das New-Customer-Gutschein for WooCommerce benötigt WooCommerce. Bitte installieren und aktivieren Sie WooCommerce.', 'newcustomer-discount'); ?>
        </p>
    </div>
    <?php
}

/**
* Aktivierungshook
*/
function ncd_activate() {
    if (WP_DEBUG) {
        error_log('Starting NCD plugin activation in DEBUG mode - forcing table recreation');
        // Force table drop in debug mode
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}ncd_email_log");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}customer_discount_tracking");
        error_log('Dropped existing tables');
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

        // Hole Upload Verzeichnis
        $upload_dir = wp_upload_dir();
        
        // Erstelle Verzeichnisstruktur
        $directories = [
            // Plugin Verzeichnisse
            NCD_PLUGIN_DIR . 'assets',
            NCD_PLUGIN_DIR . 'assets/css',
            NCD_PLUGIN_DIR . 'assets/css/admin',
            NCD_PLUGIN_DIR . 'assets/images',
            
            // Admin Verzeichnisse
            NCD_ADMIN_DIR . 'core',
            NCD_ADMIN_DIR . 'settings',
            NCD_ADMIN_DIR . 'templates',
            NCD_ADMIN_DIR . 'statistics',
            NCD_ADMIN_DIR . 'customers',
            NCD_ADMIN_DIR . 'ajax',
            
            // Upload Verzeichnis
            $upload_dir['basedir'] . '/newcustomer-discount'
        ];

        foreach ($directories as $dir) {
            if (!file_exists($dir)) {
                if (!wp_mkdir_p($dir)) {
                    throw new Exception(sprintf(
                        'Failed to create directory: %s (Permission: %s)', 
                        $dir,
                        substr(sprintf('%o', fileperms(dirname($dir))), -4)
                    ));
                }
            }
        }

        // Setze Berechtigungen für die Verzeichnisse
        foreach ($directories as $dir) {
            if (file_exists($dir)) {
                chmod($dir, 0755);
            }
        }

        if (WP_DEBUG) {
            error_log('Created plugin directories successfully');
            foreach ($directories as $dir) {
                error_log(sprintf(
                    'Directory %s exists: %s (Permission: %s)',
                    $dir,
                    file_exists($dir) ? 'yes' : 'no',
                    file_exists($dir) ? substr(sprintf('%o', fileperms($dir)), -4) : 'n/a'
                ));
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
 
        if (WP_DEBUG) {
            error_log('Tables recreated successfully');
            // Log table structure for verification
            global $wpdb;
            $email_log_structure = $wpdb->get_results("DESCRIBE {$wpdb->prefix}ncd_email_log");
            $tracking_structure = $wpdb->get_results("DESCRIBE {$wpdb->prefix}customer_discount_tracking");
            error_log('Email log table structure: ' . print_r($email_log_structure, true));
            error_log('Tracking table structure: ' . print_r($tracking_structure, true));
        }

    } catch (Exception $e) {
        if (WP_DEBUG) {
            error_log('NCD plugin activation failed: ' . $e->getMessage());
            
            // Zusätzliche Debug-Informationen
            error_log('Plugin Directory: ' . NCD_PLUGIN_DIR);
            error_log('Current user: ' . get_current_user());
            error_log('PHP process user: ' . (function_exists('posix_getpwuid') ? 
                posix_getpwuid(posix_geteuid())['name'] : 'unknown'));
            
            if (file_exists(NCD_PLUGIN_DIR)) {
                error_log('Plugin directory permissions: ' . 
                    substr(sprintf('%o', fileperms(NCD_PLUGIN_DIR)), -4));
            }
        }
        
        // Admin-Hinweis mit detaillierten Informationen
        add_action('admin_notices', function() use ($e) {
            ?>
            <div class="notice notice-error">
                <p>
                    <?php echo sprintf(
                        __('Fehler bei der Plugin-Aktivierung: %s', 'newcustomer-discount'),
                        esc_html($e->getMessage())
                    ); ?>
                </p>
                <?php if (current_user_can('manage_options')): ?>
                    <p>
                        <strong><?php _e('Debug-Informationen:', 'newcustomer-discount'); ?></strong><br>
                        Plugin-Verzeichnis: <?php echo esc_html(NCD_PLUGIN_DIR); ?><br>
                        Berechtigungen: <?php echo file_exists(NCD_PLUGIN_DIR) ? 
                            substr(sprintf('%o', fileperms(NCD_PLUGIN_DIR)), -4) : 'n/a'; ?>
                    </p>
                <?php endif; ?>
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
    // Alle möglichen Zeitpläne entfernen
    $schedules = array('hourly', 'daily', 'twicedaily');
    foreach ($schedules as $schedule) {
        $timestamp = wp_next_scheduled('ncd_daily_cleanup');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'ncd_daily_cleanup');
        }
    }
    
    wp_clear_scheduled_hook('ncd_daily_cleanup');
    wp_cache_flush();
}
register_deactivation_hook(__FILE__, 'ncd_deactivate');

/**
 * Täglicher Cleanup
 */
function ncd_do_daily_cleanup() {
    // Lockfile oder Transient prüfen
    $doing_cleanup = get_transient('ncd_doing_cleanup');
    if ($doing_cleanup) {
        return;
    }
    
    // Lock setzen
    set_transient('ncd_doing_cleanup', true, 15 * MINUTE_IN_SECONDS);
    
    try {
        $customer_tracker = new NCD_Customer_Tracker();
        $customer_tracker->cleanup_old_entries();
        wp_cache_flush();
    } catch (Exception $e) {
        error_log('Cleanup failed: ' . $e->getMessage());
    }
    
    // Lock entfernen
    delete_transient('ncd_doing_cleanup');
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