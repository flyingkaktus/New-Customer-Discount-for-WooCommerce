<?php
/**
 * Admin Settings Class
 *
 * Verwaltet die Plugin-Einstellungen im WordPress Admin-Bereich
 *
 * @package NewCustomerDiscount
 * @subpackage Admin\Settings
 * @since 0.0.1
 */

if (!defined('ABSPATH')) {
    exit;
}

class NCD_Admin_Settings extends NCD_Admin_Base {

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        
        // Zusätzliche Hooks für Einstellungen
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_post_ncd_reset_data', [$this, 'handle_reset_data']);
    }

    /**
     * Registriert die Plugin-Einstellungen
     */
    public function register_settings() {
        // General Settings
        register_setting('ncd_general_settings', 'ncd_delete_all_on_uninstall');

        // Email Settings
        register_setting('ncd_email_settings', 'ncd_email_subject');

        // Coupon Settings
        register_setting('ncd_coupon_settings', 'ncd_discount_amount', [
            'type' => 'integer',
            'sanitize_callback' => [$this, 'sanitize_discount_amount']
        ]);
        register_setting('ncd_coupon_settings', 'ncd_expiry_days', [
            'type' => 'integer',
            'sanitize_callback' => [$this, 'sanitize_expiry_days']
        ]);

        // Code Settings
        register_setting('ncd_code_settings', 'ncd_code_prefix', [
            'type' => 'string',
            'sanitize_callback' => [$this, 'sanitize_code_prefix']
        ]);
        register_setting('ncd_code_settings', 'ncd_code_length', [
            'type' => 'integer',
            'sanitize_callback' => [$this, 'sanitize_code_length']
        ]);
        register_setting('ncd_code_settings', 'ncd_code_chars');

        // Customer Settings
        register_setting('ncd_customer_settings', 'ncd_cutoff_date', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field'
        ]);
        register_setting('ncd_customer_settings', 'ncd_order_count', [
            'type' => 'integer',
            'sanitize_callback' => [$this, 'sanitize_order_count']
        ]);
        register_setting('ncd_customer_settings', 'ncd_check_period');
        register_setting('ncd_customer_settings', 'ncd_min_order_amount', [
            'type' => 'number',
            'sanitize_callback' => [$this, 'sanitize_min_order_amount']
        ]);
        register_setting('ncd_customer_settings', 'ncd_excluded_categories');
    }

    /**
     * Rendert die Einstellungsseite
     */
    public function render_page() {
        if (!$this->check_admin_permissions()) {
            return;
        }
    
        if ($this->handle_settings_post()) {
            $this->add_admin_notice(
                __('Einstellungen wurden gespeichert.', 'newcustomer-discount'),
                'success'
            );
        }
    
        include NCD_PLUGIN_DIR . 'templates/admin/settings-page.php';
    }

    /**
     * Verarbeitet POST-Anfragen der Einstellungsseite
     *
     * @return bool
     */
    private function handle_settings_post() {
        if (!isset($_POST['ncd_settings_nonce'])) {
            return false;
        }

        check_admin_referer('ncd_settings', 'ncd_settings_nonce');

        if (isset($_POST['update_logo'])) {
            return $this->handle_logo_update();
        }

        if (isset($_POST['delete_logo'])) {
            return $this->handle_logo_delete();
        }

        if (isset($_POST['save_email_settings'])) {
            return $this->handle_email_settings();
        }

        if (isset($_POST['save_coupon_settings'])) {
            return $this->handle_coupon_settings();
        }

        if (isset($_POST['save_code_settings'])) {
            return $this->handle_code_settings();
        }

        if (isset($_POST['save_customer_settings'])) {
            return $this->handle_customer_settings();
        }

        return false;
    }

    /**
     * Verarbeitet Logo-Updates
     *
     * @return bool
     */
    private function handle_logo_update() {
        if (!empty($_FILES['logo_file']['name'])) {
            return NCD_Logo_Manager::save_logo($_FILES['logo_file']);
        } elseif (!empty($_POST['logo_base64'])) {
            return NCD_Logo_Manager::save_base64($_POST['logo_base64']);
        }
        return false;
    }

    /**
     * Verarbeitet Logo-Löschungen
     *
     * @return bool
     */
    private function handle_logo_delete() {
        return NCD_Logo_Manager::delete_logo();
    }

    /**
     * Verarbeitet E-Mail-Einstellungen
     *
     * @return bool
     */
    private function handle_email_settings() {
        update_option('ncd_email_subject', sanitize_text_field($_POST['email_subject']));
        return true;
    }

    /**
     * Verarbeitet Gutschein-Einstellungen
     *
     * @return bool
     */
    private function handle_coupon_settings() {
        $discount_amount = $this->sanitize_discount_amount($_POST['discount_amount']);
        $expiry_days = $this->sanitize_expiry_days($_POST['expiry_days']);

        update_option('ncd_discount_amount', $discount_amount);
        update_option('ncd_expiry_days', $expiry_days);

        return true;
    }

    /**
     * Verarbeitet Code-Einstellungen
     *
     * @return bool
     */
    private function handle_code_settings() {
        $prefix = $this->sanitize_code_prefix($_POST['code_prefix']);
        $length = $this->sanitize_code_length($_POST['code_length']);
        $chars = isset($_POST['code_chars']) ? (array) $_POST['code_chars'] : ['numbers', 'uppercase'];

        update_option('ncd_code_prefix', $prefix);
        update_option('ncd_code_length', $length);
        update_option('ncd_code_chars', $chars);

        return true;
    }

    /**
     * Verarbeitet Kunden-Einstellungen
     *
     * @return bool
     */
    private function handle_customer_settings() {
        $cutoff_date = sanitize_text_field($_POST['cutoff_date']);
        $order_count = $this->sanitize_order_count($_POST['order_count']);
        $check_period = sanitize_text_field($_POST['check_period']);
        $min_amount = $this->sanitize_min_order_amount($_POST['min_order_amount']);
        $excluded_cats = isset($_POST['exclude_categories']) ?
            array_map('absint', $_POST['exclude_categories']) : [];

        update_option('ncd_cutoff_date', $cutoff_date);
        update_option('ncd_order_count', $order_count);
        update_option('ncd_check_period', $check_period);
        update_option('ncd_min_order_amount', $min_amount);
        update_option('ncd_excluded_categories', $excluded_cats);

        return true;
    }

    /**
     * Verarbeitet Reset-Anfragen
     */
    public function handle_reset_data() {
        if (!$this->check_admin_permissions()) {
            return;
        }

        if (!isset($_POST['ncd_reset_nonce']) || !wp_verify_nonce($_POST['ncd_reset_nonce'], 'ncd_reset_settings')) {
            wp_die(__('Sicherheitsüberprüfung fehlgeschlagen.', 'newcustomer-discount'));
        }

        $reset_count = $this->perform_reset_actions();

        wp_redirect(add_query_arg([
            'page' => 'new-customers-settings',
            'tab' => 'reset',
            'message' => $reset_count > 0 ? 'reset-success' : 'no-data',
            'count' => $reset_count
        ], admin_url('admin.php')));
        exit;
    }

    /**
     * Führt die Reset-Aktionen aus
     *
     * @return int Anzahl der zurückgesetzten Elemente
     */
    private function perform_reset_actions() {
        if (!isset($_POST['reset_actions']) || !isset($_POST['confirm_reset']) || $_POST['confirm_reset'] != '1') {
            return 0;
        }

        $actions = (array) $_POST['reset_actions'];
        $reset_count = 0;

        if (in_array('coupons', $actions)) {
            $reset_count += $this->reset_coupons();
        }

        if (in_array('customers', $actions)) {
            $reset_count += $this->reset_customers();
        }

        return $reset_count;
    }

    /**
     * Setzt alle erstellten Gutscheine zurück
     *
     * @return int
     */
    private function reset_coupons() {
        global $wpdb;
        $count = 0;

        // Für WooCommerce 8.0+ HPOS Tabellen
        if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}wc_coupons'") === "{$wpdb->prefix}wc_coupons") {
            $coupon_ids = $wpdb->get_col("
                SELECT coupon_id 
                FROM {$wpdb->prefix}wc_coupons c
                JOIN {$wpdb->prefix}wc_coupon_meta cm ON c.id = cm.coupon_id
                WHERE cm.meta_key = '_ncd_generated'
                AND cm.meta_value = 'yes'
            ");

            foreach ($coupon_ids as $coupon_id) {
                $wpdb->delete($wpdb->prefix . 'wc_coupon_meta', ['coupon_id' => $coupon_id]);
                $wpdb->delete($wpdb->prefix . 'wc_coupons', ['id' => $coupon_id]);
                $count++;
            }
        } else {
            // Alte WordPress Tabellenstruktur
            $coupon_ids = $wpdb->get_col("
                SELECT ID 
                FROM {$wpdb->posts} p
                JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                WHERE p.post_type = 'shop_coupon'
                AND pm.meta_key = '_ncd_generated'
                AND pm.meta_value = 'yes'
            ");

            foreach ($coupon_ids as $coupon_id) {
                wp_delete_post($coupon_id, true);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Setzt alle Neukunden-Tracking-Daten zurück
     *
     * @return int
     */
    private function reset_customers() {
        global $wpdb;
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}customer_discount_tracking");
        
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}customer_discount_tracking");
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}ncd_email_log");

        if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}wc_orders_meta'") === "{$wpdb->prefix}wc_orders_meta") {
            $wpdb->query("DELETE FROM {$wpdb->prefix}wc_orders_meta WHERE meta_key LIKE '_ncd_%'");
        }

        $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_ncd_%'");
        
        return (int) $count;
    }

    /**
     * Sanitize-Funktionen für Einstellungen
     */
    public function sanitize_discount_amount($value) {
        return min(max(absint($value), 1), 100);
    }

    public function sanitize_expiry_days($value) {
        return min(max(absint($value), 1), 365);
    }

    public function sanitize_code_prefix($value) {
        return substr(sanitize_text_field($value), 0, 5);
    }

    public function sanitize_code_length($value) {
        return min(max(absint($value), 4), 12);
    }

    public function sanitize_order_count($value) {
        return min(max(absint($value), 0), 10);
    }

    public function sanitize_min_order_amount($value) {
        return max(floatval($value), 0);
    }
}