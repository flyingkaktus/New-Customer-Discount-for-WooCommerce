<?php
/**
 * Admin Base Class
 *
 * Basic functionality for admin classes
 *
 * @package NewCustomerDiscount
 * @subpackage Admin\Core
 * @since 0.0.1
 */

if (!defined('ABSPATH')) {
    exit;
}

class NCD_Admin_Base {
    /**
     * Customer Tracker Instance
     * 
     * @var NCD_Customer_Tracker
     */
    protected $customer_tracker;

    /**
     * Discound Generator Instance
     * 
     * @var NCD_Discount_Generator
     */
    protected $coupon_generator;

    /**
     * Email Sender Instance
     * 
     * @var NCD_Email_Sender
     */
    protected $email_sender;

    /**
<<<<<<< Updated upstream
     * AJAX Handler Instance
=======
     * AJAX Handler Instanz
>>>>>>> Stashed changes
     * 
     * @var NCD_Admin_Ajax
     */
    protected $ajax_handler;
    /**
     * Constructor
     */
    public function __construct() {
        $this->ajax_handler = new NCD_Admin_Ajax();
<<<<<<< Updated upstream
        $this->ajax_handler = new NCD_Admin_Ajax();
        $this->init_dependencies();
        $this->init_hooks();


=======
        $this->init_dependencies();
        $this->init_hooks();

>>>>>>> Stashed changes
    }

    /**
     * Init Admin Base Dependencies
     */
    protected function init_dependencies() {
        try {
            $this->customer_tracker = new NCD_Customer_Tracker();
            $this->coupon_generator = new NCD_Discount_Generator();
            $this->email_sender = new NCD_Email_Sender();

            if (WP_DEBUG) {
                error_log('NCD Admin Base dependencies initialized successfully');
            }
        } catch (Exception $e) {
            if (WP_DEBUG) {
                error_log('Error initializing NCD Admin Base dependencies: ' . $e->getMessage());
            }
        }
    }

    /**
     * Init Admin Base Hooks
     */
    protected function init_hooks() {
        add_action('admin_notices', [$this, 'display_admin_notices']);
    }

    /**
     * Loads Admin Assets
     *
     * @param string $hook Der aktuelle Admin-Seiten-Hook
     */
    public function enqueue_assets($hook) {

    }

    /**
     * Shows Admin Notices
     */
    public function display_admin_notices() {
        settings_errors('ncd_messages');
    }

    /**
     * Add an Admin Notice
     *
     * @param string $message Message to display
     * @param string $type Message type (success, error, warning, info)
     */
    protected function add_admin_notice($message, $type = 'success') {
        add_settings_error(
            'ncd_messages',
            'ncd_message',
            $message,
            $type
        );
    }

    /**
     * Logging for Debugging
     *
     * @param string $message Error message
     * @param array $context Additional context
     */
    protected function log_error($message, $context = []) {
        if (WP_DEBUG) {
            error_log(sprintf(
                '[NewCustomerDiscount] Admin Base Error: %s | Context: %s',
                $message,
                json_encode($context)
            ));
        }
    }

    /**
     * Checks if the current user has the required permissions
     *
     * @return bool
     */
    protected function check_admin_permissions() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have the required permissions for this action.', 'newcustomer-discount'));
            return false;
        }
        return true;
    }

    /**
     * Returns a component by name
     *
     * @param string $component component name
     * @return mixed|null
     */
    protected function get_component($component) {
        $components = [
            'customer_tracker' => $this->customer_tracker,
            'coupon_generator' => $this->coupon_generator,
            'email_sender' => $this->email_sender
        ];

        return isset($components[$component]) ? $components[$component] : null;
    }

    /**
<<<<<<< Updated upstream
     * Debug-Output for loaded styles
=======
     * Debug-Ausgabe der geladenen Stylesheets
>>>>>>> Stashed changes
     */
    protected function debug_loaded_styles() {
        if (!WP_DEBUG) {
            return;
        }

        global $wp_styles;
        error_log('Loaded NCD Admin Styles:');
        foreach ($wp_styles->queue as $handle) {
            if (strpos($handle, 'ncd-admin') !== false) {
                error_log(' - ' . $handle);
            }
        }
    }
}