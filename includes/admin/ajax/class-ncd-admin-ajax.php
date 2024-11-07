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
     * Handler-Instanzen
     * 
     * @var array
     */
    private $handlers = [];

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        
        // Handler initialisieren
        $this->init_handlers();
        
        // AJAX Actions registrieren
        $this->init_ajax_handlers();
    }

    /**
     * Initialisiert die Handler-Klassen
     */
    private function init_handlers() {
        $this->handlers = [
            'templates' => new NCD_Admin_Templates(),
            'customers' => new NCD_Admin_Customers()
        ];
    }

    /**
     * Initialisiert die AJAX Handler
     */
    private function init_ajax_handlers() {
        // Template bezogene Aktionen
        $this->register_ajax_action('ncd_preview_template', 'templates', 'handle_preview_template');
        $this->register_ajax_action('ncd_switch_template', 'templates', 'handle_switch_template');
        $this->register_ajax_action('ncd_save_template_settings', 'templates', 'handle_save_template_settings');
        $this->register_ajax_action('ncd_activate_template', 'templates', 'handle_activate_template');
        $this->register_ajax_action('ncd_get_template_settings', 'templates', 'handle_get_template_settings');
        
        // Kunden bezogene Aktionen
        $this->register_ajax_action('ncd_send_test_email', 'customers', 'handle_send_test_email');
        $this->register_ajax_action('ncd_send_discount', 'customers', 'handle_send_discount');
    }

    /**
     * Registriert eine AJAX Action
     *
     * @param string $action Die AJAX Action
     * @param string $handler Der Handler-Typ (templates, customers etc.)
     * @param string $method Die aufzurufende Methode
     */
    private function register_ajax_action($action, $handler, $method) {
        add_action('wp_ajax_' . $action, function() use ($handler, $method) {
            $this->handle_ajax_request($handler, $method);
        });
    }

    /**
     * Verarbeitet eine AJAX-Anfrage
     *
     * @param string $handler Der Handler-Typ
     * @param string $method Die aufzurufende Methode
     */
    private function handle_ajax_request($handler, $method) {
        if (!isset($this->handlers[$handler])) {
            wp_send_json_error([
                'message' => __('Ungültiger Handler.', 'newcustomer-discount')
            ]);
            return;
        }

        try {
            if (WP_DEBUG) {
                error_log("Handling AJAX request: $handler::$method");
                error_log("POST data: " . print_r($_POST, true));
            }

            // Security checks are handled in the handler methods
            call_user_func([$this->handlers[$handler], $method], $_POST);

        } catch (Exception $e) {
            if (WP_DEBUG) {
                error_log("AJAX error in $handler::$method: " . $e->getMessage());
            }
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Überprüft AJAX-Anfragen
     * Überschreibt die Methode aus der Basisklasse
     *
     * @param string $action Die Nonce-Action
     * @param string $nonce_field Das Nonce-Feld
     * @return bool
     */
    protected function check_ajax_request($action = 'ncd-admin-nonce', $nonce_field = 'nonce') {
        if (!check_ajax_referer($action, $nonce_field, false)) {
            wp_send_json_error([
                'message' => __('Sicherheitsüberprüfung fehlgeschlagen.', 'newcustomer-discount')
            ]);
            return false;
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => __('Keine Berechtigung.', 'newcustomer-discount')
            ]);
            return false;
        }

        return true;
    }
}