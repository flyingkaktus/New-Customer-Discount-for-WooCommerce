<?php
/**
 * Admin Ajax Class
 *
 * Central AJAX handler for admin functions
 *
 * @package NewCustomerDiscount
 * @subpackage Admin\Ajax
 * @since 0.0.1
 */

if (!defined('ABSPATH')) {
    exit;
}

class NCD_Admin_Ajax {
<<<<<<< Updated upstream
class NCD_Admin_Ajax {
=======
>>>>>>> Stashed changes
    /**
     * Handler-Instance
     * 
     * @var array
     */
    private $handlers = [];

    /**
     * Constructor
     */
    public function __construct() {
<<<<<<< Updated upstream
=======
        // Registriere die AJAX Handler erst wenn sie gebraucht werden
>>>>>>> Stashed changes
        add_action('admin_init', [$this, 'init_ajax_handlers']);
    }

    /**
<<<<<<< Updated upstream
     * Lazy Loading for Handler
=======
     * Lazy Loading für Handler
>>>>>>> Stashed changes
     */
    private function get_handler($type) {
        if (!isset($this->handlers[$type])) {
            switch ($type) {
                case 'templates':
                    $this->handlers[$type] = new NCD_Admin_Templates();
                    break;
                case 'customers':
                    $this->handlers[$type] = new NCD_Admin_Customers();
                    break;
                case 'settings':
                    $this->handlers[$type] = new NCD_Admin_Settings();
                    break;
            }
        }
        return $this->handlers[$type];
<<<<<<< Updated upstream
    private function get_handler($type) {
        if (!isset($this->handlers[$type])) {
            switch ($type) {
                case 'templates':
                    $this->handlers[$type] = new NCD_Admin_Templates();
                    break;
                case 'customers':
                    $this->handlers[$type] = new NCD_Admin_Customers();
                    break;
                case 'settings':
                    $this->handlers[$type] = new NCD_Admin_Settings();
                    break;
            }
        }
        return $this->handlers[$type];
=======
>>>>>>> Stashed changes
    }

    /**
     * Init AJAX Handler
     */
    public function init_ajax_handlers() {
<<<<<<< Updated upstream
    public function init_ajax_handlers() {
=======
>>>>>>> Stashed changes
        // Template bezogene Aktionen
        $this->register_ajax_action('ncd_preview_template', 'templates', 'handle_preview_template');
        $this->register_ajax_action('ncd_switch_template', 'templates', 'handle_switch_template');
        $this->register_ajax_action('ncd_save_template_settings', 'templates', 'handle_save_template_settings');
        $this->register_ajax_action('ncd_activate_template', 'templates', 'handle_activate_template');
        $this->register_ajax_action('ncd_get_template_settings', 'templates', 'handle_get_template_settings');
        
        // Kunden bezogene Aktionen
        $this->register_ajax_action('ncd_send_test_email', 'customers', 'handle_send_test_email');
        $this->register_ajax_action('ncd_send_discount', 'customers', 'handle_send_discount');

        // Feedback Handler
        $this->register_ajax_action('ncd_submit_feedback', 'settings', 'handle_submit_feedback');
    }

    /**
<<<<<<< Updated upstream
     * Register an AJAX Action
=======
     * Registriert eine AJAX Action
>>>>>>> Stashed changes
     */
    private function register_ajax_action($action, $handler_type, $method) {
        add_action('wp_ajax_' . $action, function() use ($handler_type, $method) {
            try {
                if (!$this->check_ajax_request()) {
                    return;
                }
<<<<<<< Updated upstream
    private function register_ajax_action($action, $handler_type, $method) {
        add_action('wp_ajax_' . $action, function() use ($handler_type, $method) {
            try {
                if (!$this->check_ajax_request()) {
                    return;
                }
=======
>>>>>>> Stashed changes

                $handler = $this->get_handler($handler_type);
                if (!$handler) {
                    throw new Exception(__('Ungültiger Handler.', 'newcustomer-discount'));
                }

                if (WP_DEBUG) {
                    error_log("Handling AJAX request: $handler_type::$method");
                    error_log("POST data: " . print_r($_POST, true));
                }
<<<<<<< Updated upstream
                $handler = $this->get_handler($handler_type);
                if (!$handler) {
                    throw new Exception(__('Ungültiger Handler.', 'newcustomer-discount'));
                }

                if (WP_DEBUG) {
                    error_log("Handling AJAX request: $handler_type::$method");
                    error_log("POST data: " . print_r($_POST, true));
                }

                call_user_func([$handler, $method], $_POST);
                call_user_func([$handler, $method], $_POST);

=======

                call_user_func([$handler, $method], $_POST);

>>>>>>> Stashed changes
            } catch (Exception $e) {
                if (WP_DEBUG) {
                    error_log("AJAX error in $handler_type::$method: " . $e->getMessage());
                }
                $this->send_ajax_error($e->getMessage());
            }
        });
    }

    /**
<<<<<<< Updated upstream
     * Checks AJAX-Requests
=======
     * Überprüft AJAX-Anfragen
>>>>>>> Stashed changes
     */
    public function check_ajax_request($action = 'ncd-admin-nonce', $nonce_field = 'nonce') {
        if (!check_ajax_referer($action, $nonce_field, false)) {
            wp_send_json_error([
                'message' => __('Security check failed.', 'newcustomer-discount')
            ]);
            return false;
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => __('Permission denied.', 'newcustomer-discount')
            ]);
            return false;
        }

        return true;
    }

    /**
<<<<<<< Updated upstream
     * Helper method for AJAX-Success response
=======
     * Hilfsmethode für AJAX-Erfolgsantwort
>>>>>>> Stashed changes
     */
    public function send_ajax_success($message, $data = []) {
        wp_send_json_success(array_merge(
            ['message' => $message],
            $data
        ));
    }

    /**
<<<<<<< Updated upstream
     * Helper method for AJAX-Error response
=======
     * Hilfsmethode für AJAX-Fehlerantwort
>>>>>>> Stashed changes
     */
    public function send_ajax_error($message, $data = []) {
        wp_send_json_error(array_merge(
            ['message' => $message],
            $data
        ));
    }
}