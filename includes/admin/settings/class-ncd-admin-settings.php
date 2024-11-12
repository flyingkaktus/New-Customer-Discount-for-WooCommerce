<?php
/**
 * Admin Settings Class
 *
 * Manages the settings page
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

        add_action('admin_init', [$this, 'register_settings']);
    }

    /**
     * Register Settings
     */
    public function register_settings() {
        // General Settings
        register_setting('ncd_general_settings', 'ncd_delete_all_on_uninstall');
    
        // Email Settings
        register_setting('ncd_email_settings', 'ncd_email_subject');
    
        // Discount Settings
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
    
        // Email Text Settings
        register_setting('ncd_email_settings', 'ncd_email_texts', [
            'type' => 'array',
            'sanitize_callback' => [$this, 'sanitize_email_texts']
        ]);
    }

    public function sanitize_email_texts($texts) {
        $sanitized = [];
        $allowed_html = [
            'p' => [],
            'br' => [],
            'strong' => [],
            'em' => [],
            'span' => []
        ];
        
        foreach ($texts as $key => $text) {
            $sanitized[$key] = wp_kses($text, $allowed_html);
        }
        
        return $sanitized;
    }

    /**
     * Renders the settings page
     */
    public function render_page() {
        if (!$this->check_admin_permissions()) {
            return;
        }
    
        if (isset($_POST['ncd_reset_nonce']) && check_admin_referer('ncd_reset_settings', 'ncd_reset_nonce')) {
            $reset_count = $this->perform_reset_actions();
            
            if($reset_count > 0) {
                $this->add_admin_notice(
                    sprintf(
                        __('Successfully reset %d entries.', 'newcustomer-discount'), 
                        $reset_count
                    ),
                    'success'
                );
            } else {
                $this->add_admin_notice(
                    __('No data found to reset.', 'newcustomer-discount'),
                    'info'
                );
            }
        }

        if ($this->handle_settings_post()) {
            $this->add_admin_notice(
                __('Settings have been saved.', 'newcustomer-discount'),
                'success'
            );
        }
    
        include NCD_PLUGIN_DIR . 'templates/admin/settings-page.php';
    }

    /**
     * Processes settings form submissions
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
     * Processes Logo-Updates
     *
     * @return bool
     */
    private function handle_logo_update() {
        $success = false;
        
        if (!empty($_FILES['logo_file']['name'])) {
            $success = NCD_Logo_Manager::save_logo($_FILES['logo_file']);
        } elseif (!empty($_POST['logo_base64'])) {
            $success = NCD_Logo_Manager::save_base64($_POST['logo_base64']);
        }
    
        if ($success) {
            $this->add_admin_notice(
                __('Logo has been successfully saved.', 'newcustomer-discount'),
                'success'
            );
        } else {
            $this->add_admin_notice(
                __('Logo could not be saved.', 'newcustomer-discount'),
                'error'
            );
        }
    
        return $success;
    }

    /**
     * Processes Logo-Deletions
     *
     * @return bool
     */
    private function handle_logo_delete() {
        return NCD_Logo_Manager::delete_logo();
    }

    /**
     * Processes E-Mail-Settings
     *
     * @return bool
     */
    private function handle_email_settings() {
        try {

            update_option('ncd_email_subject', sanitize_text_field($_POST['email_subject']));
    
            if (isset($_POST['email_texts']) && is_array($_POST['email_texts'])) {

                $existing_texts = get_option('ncd_email_texts', []);
                $new_texts = [];
                foreach ($_POST['email_texts'] as $key => $value) {
                    if ($key === 'heading') {
                        $new_texts[$key] = sanitize_text_field($value);
                    } else {
                        $new_texts[$key] = wp_kses_post($value);
                    }
                }

                $email_texts = array_merge($existing_texts, $new_texts);
    
                update_option('ncd_email_texts', $email_texts);
            }
    
    
            return true;
    
        } catch (Exception $e) {
            if (WP_DEBUG) {
                error_log('Error saving email settings: ' . $e->getMessage());
            }
            return false;
        }
    }

    /**
     * Processes Coupon-Settings
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
     * Processes Code-Settings
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
     * Processes Customer-Settings
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
     * Handles the reset data action
     */
    public function handle_reset_data() {
        if (!$this->check_admin_permissions()) {
            return;
        }

        if (!isset($_POST['ncd_reset_nonce']) || !wp_verify_nonce($_POST['ncd_reset_nonce'], 'ncd_reset_settings')) {
            wp_die(__('Security check failed.', 'newcustomer-discount'));
        }

        $reset_count = $this->perform_reset_actions();

        if($reset_count > 0) {
            $this->add_admin_notice(
                sprintf(
                    __('Successfully reset %d entries.', 'newcustomer-discount'), 
                    $reset_count
                ),
                'success'
            );
        } else {
            $this->add_admin_notice(
                __('No data found to reset.', 'newcustomer-discount'),
                'info'
            );
        }
    }

    /**
     * Executes the reset actions
     *
     * @return int Number of reset entries
     */
    private function perform_reset_actions() {
        if (!isset($_POST['reset_actions']) || !isset($_POST['confirm_reset']) || $_POST['confirm_reset'] != '1') {
            return 0;
        }

        $actions = (array) $_POST['reset_actions'];
        $reset_count = 0;

        if (in_array('Gutscheine', $actions)) {
            $reset_count += $this->reset_coupons();
        }

        if (in_array('customers', $actions)) {
            $reset_count += $this->reset_customers();
        }

        return $reset_count;
    }

    /**
     * Sets all generated coupons back to default
     *
     * @return int
     */
    private function reset_coupons() {
        global $wpdb;
        $count = 0;

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
     * Sets all customer tracking data back to default
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
     * Sanitize-Function for discount amount
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

    /**
     * Processes the feedback form submission
     *
     * @param array $data POST-Daten
     */
    public function handle_submit_feedback($data) {
        try {
            if (empty($data['feedback_content'])) {
                throw new Exception(__('Please enter your feedback.', 'newcustomer-discount'));
            }
    
            $feedback = [
                'type' => sanitize_text_field($data['feedback_type']),
                'content' => wp_kses_post($data['feedback_content']),
                'system_info' => !empty($data['include_system_info']) ? $this->get_system_info() : '',
                'version' => !empty($data['bug_version']) ? sanitize_text_field($data['bug_version']) : '',
                'user_email' => wp_get_current_user()->user_email,
                'site_url' => get_site_url()
            ];
    
            $sent = $this->send_feedback($feedback);
    
            if ($sent) {
                wp_send_json_success([
                    'message' => __('Thank you for your feedback!', 'newcustomer-discount'),
                    'type' => 'success'
                ]);
            } else {
                throw new Exception(__('Feedback could not be sent', 'newcustomer-discount'));
            }
    
        } catch (Exception $e) {
            if (WP_DEBUG) {
                error_log('Feedback submission error: ' . $e->getMessage());
            }
            wp_send_json_error([
                'message' => $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    /**
     * Sends the feedback via email
     *
     * @param array $feedback
     * @return bool
     */
    private function send_feedback($feedback) {
        $to = 'suchowski@aol.com';
        $subject = sprintf(
            '[NCD Feedback] %s from %s',
            $feedback['type'],
            parse_url($feedback['site_url'], PHP_URL_HOST)
        );

        $message = sprintf(
            "Type: %s\nVersion: %s\nFrom: %s\nSite: %s\n\nMessage:\n%s",
            $feedback['type'],
            $feedback['version'],
            $feedback['user_email'],
            $feedback['site_url'],
            $feedback['content']
        );

        if (!empty($feedback['system_info'])) {
            $message .= "\n\nSystem Info:\n" . $feedback['system_info'];
        }

        $headers = [
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>',
            'Reply-To: ' . $feedback['user_email']
        ];

        return wp_mail($to, $subject, $message, $headers);
    }

    /**
     * Collecting system information
     *
     * @return string
     */
    private function get_system_info() {
        global $wp_version;
        
        return sprintf(
            "WordPress: %s\nPHP: %s\nPlugin Version: %s\nWooCommerce: %s\nTheme: %s",
            $wp_version,
            phpversion(),
            NCD_VERSION,
            WC()->version,
            wp_get_theme()->get('Name')
        );
    }
    
}