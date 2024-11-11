<?php
/**
 * Admin Base Class
 *
 * Grundlegende Admin-Funktionalität und Initialisierung
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
     * Customer Tracker Instanz
     * 
     * @var NCD_Customer_Tracker
     */
    protected $customer_tracker;

    /**
     * Gutschein Generator Instanz
     * 
     * @var NCD_Discount_Generator
     */
    protected $coupon_generator;

    /**
     * Email Sender Instanz
     * 
     * @var NCD_Email_Sender
     */
    protected $email_sender;

    /**
     * AJAX Handler Instanz
     * 
     * @var NCD_Admin_Ajax
     */
    protected $ajax_handler;
    /**
     * Constructor
     */
    public function __construct() {
        $this->ajax_handler = new NCD_Admin_Ajax();
        $this->init_dependencies();
        $this->init_hooks();

    }

    /**
     * Initialisiert die Abhängigkeiten
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
     * Initialisiert die WordPress Hooks
     */
    protected function init_hooks() {
        add_action('admin_notices', [$this, 'display_admin_notices']);
    }

    /**
     * Lädt seitenspezifische Assets
     *
     * @param string $hook Der aktuelle Admin-Seiten-Hook
     */
    public function enqueue_assets($hook) {
        // Diese Methode kann von Unterklassen überschrieben werden
        // um seitenspezifische Assets zu laden
    }

    /**
     * Zeigt Admin-Benachrichtigungen an
     */
    public function display_admin_notices() {
        settings_errors('ncd_messages');
    }

    /**
     * Fügt Admin-Benachrichtigung hinzu
     *
     * @param string $message Nachricht
     * @param string $type Typ der Nachricht (success, error, warning, info)
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
     * Loggt Fehler für Debugging
     *
     * @param string $message Fehlermeldung
     * @param array $context Zusätzliche Kontext-Informationen
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
     * Prüft die Admin-Berechtigung
     *
     * @return bool
     */
    protected function check_admin_permissions() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Sie haben nicht die erforderlichen Berechtigungen für diese Aktion.', 'newcustomer-discount'));
            return false;
        }
        return true;
    }

    /**
     * Gibt eine Instanz einer Admin-Komponente zurück
     *
     * @param string $component Name der Komponente
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
     * Debug-Ausgabe der geladenen Stylesheets
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