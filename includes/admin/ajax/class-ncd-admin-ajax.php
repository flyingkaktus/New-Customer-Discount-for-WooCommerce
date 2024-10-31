<?php
/**
 * Admin Ajax Class
 *
 * Zentraler AJAX-Handler für Admin-Funktionen
 *
 * @package NewCustomerDiscount
 * @subpackage Admin\Ajax
 * @since 0.0.1
 */

if (!defined('ABSPATH')) {
    exit;
}

class NCD_Admin_Ajax extends NCD_Admin_Base {
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->init_ajax_handlers();
    }

    /**
     * Initialisiert die AJAX Handler
     */
    private function init_ajax_handlers() {
        // Template bezogene Aktionen
        add_action('wp_ajax_ncd_preview_template', function() {
            $this->delegate_ajax_request(
                'NCD_Admin_Templates',
                'handle_preview_template',
                $_POST
            );
        });

        add_action('wp_ajax_ncd_switch_template', function() {
            $this->delegate_ajax_request(
                'NCD_Admin_Templates',
                'handle_switch_template',
                $_POST
            );
        });

        add_action('wp_ajax_ncd_save_template_settings', function() {
            $this->delegate_ajax_request(
                'NCD_Admin_Templates',
                'handle_save_template_settings',
                $_POST
            );
        });

        // Kunden bezogene Aktionen
        add_action('wp_ajax_ncd_send_test_email', function() {
            $this->delegate_ajax_request(
                'NCD_Admin_Customers',
                'handle_send_test_email',
                $_POST
            );
        });

        add_action('wp_ajax_ncd_send_discount', function() {
            $this->delegate_ajax_request(
                'NCD_Admin_Customers',
                'handle_send_discount',
                $_POST
            );
        });
    }

    /**
     * Leitet AJAX-Anfragen an die entsprechenden Handler weiter
     *
     * @param string $handler_class Die Handler-Klasse
     * @param string $method Die aufzurufende Methode
     * @param array $data Die zu übergebenden Daten
     * @return mixed|false
     */
    private function delegate_ajax_request($handler_class, $method, $data) {
        if (class_exists($handler_class) && method_exists($handler_class, $method)) {
            $handler = new $handler_class();
            return call_user_func([$handler, $method], $data);
        }
        return false;
    }
}