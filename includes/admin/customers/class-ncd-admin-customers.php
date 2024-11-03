<?php
/**
* Admin Customers Class
*
* Verwaltet die Kundenverwaltung im WordPress Admin-Bereich
*
* @package NewCustomerDiscount
* @subpackage Admin\Customers
* @since 0.0.1
*/

if (!defined('ABSPATH')) {
   exit;
}

class NCD_Admin_Customers extends NCD_Admin_Base {

   /**
    * Constructor
    */
   public function __construct() {
       parent::__construct();
   }

     /**
     * Rendert die Admin-Seite
     */
    public function render_page() {
        if (!$this->check_admin_permissions()) {
            return;
        }

        // Filter-Parameter
        $days = isset($_GET['days_filter']) ? (int)$_GET['days_filter'] : 30;
        $only_new = isset($_GET['only_new']);

        // Hole Kundendaten
        $customers = $this->customer_tracker->get_customers([
            'days' => $days,
            'only_new' => $only_new
        ]);

        include NCD_PLUGIN_DIR . 'templates/admin/customers-page.php';
    }

    /**
     * Handler für Test-E-Mail Versand
     * 
     * @param array $data Die POST-Daten
     */
    public function handle_send_test_email($data) {
        if (!$this->check_ajax_request()) {
            return;
        }

        $email = sanitize_email($data['email']);
        if (!is_email($email)) {
            wp_send_json_error([
                'message' => __('Ungültige E-Mail-Adresse.', 'newcustomer-discount')
            ]);
            return;
        }

        try {
            $result = $this->email_sender->send_test_email($email);

            if (is_wp_error($result)) {
                throw new Exception($result->get_error_message());
            }

            wp_send_json_success([
                'message' => sprintf(
                    __('Test-E-Mail wurde an %s gesendet.', 'newcustomer-discount'),
                    $email
                )
            ]);
        } catch (Exception $e) {
            $this->log_error('Test email sending failed', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * Handler für Rabatt-E-Mail Versand
     * 
     * @param array $data Die POST-Daten
     */
    public function handle_send_discount($data) {
        if (WP_DEBUG) {
            error_log('Starting handle_send_discount');
            error_log('Posted data: ' . print_r($data, true));
        }
    
        if (!$this->check_ajax_request()) {
            error_log('AJAX request check failed');
            return;
        }
    
        $email = sanitize_email($data['email']);
        $first_name = sanitize_text_field($data['first_name']);
        $last_name = sanitize_text_field($data['last_name']);
    
        try {
            if (WP_DEBUG) {
                error_log('Checking if new customer: ' . $email);
            }
    
            if (!$this->customer_tracker->is_new_customer($email)) {
                error_log('Not a new customer: ' . $email);
                throw new Exception(__('Der Kunde ist kein Neukunde.', 'newcustomer-discount'));
            }
    
            if (WP_DEBUG) {
                error_log('Creating coupon for: ' . $email);
            }
    
            $coupon = $this->coupon_generator->create_coupon($email);
            if (is_wp_error($coupon)) {
                error_log('Coupon creation failed: ' . $coupon->get_error_message());
                throw new Exception($coupon->get_error_message());
            }
    
            if (WP_DEBUG) {
                error_log('Sending email with coupon: ' . print_r($coupon, true));
            }
    
            $result = $this->email_sender->send_discount_email($email, [
                'coupon_code' => $coupon['code'],
                'first_name' => $first_name,
                'last_name' => $last_name
            ]);
    
            if (is_wp_error($result)) {
                error_log('Email sending failed: ' . $result->get_error_message());
                $this->coupon_generator->deactivate_coupon($coupon['code']);
                throw new Exception($result->get_error_message());
            }
    
            if (WP_DEBUG) {
                error_log('Updating customer status');
            }
    
            $this->customer_tracker->update_customer_status($email, 'sent', $coupon['code']);
    
            wp_send_json_success([
                'message' => sprintf(
                    __('Rabattcode %s wurde an %s gesendet.', 'newcustomer-discount'),
                    $coupon['code'],
                    $email
                )
            ]);
    
        } catch (Exception $e) {
            error_log('Discount email sending failed: ' . $e->getMessage());
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * Überprüft AJAX-Anfragen
     *
     * @param string $action Optional. Die Nonce-Action.
     * @param string $nonce_field Optional. Das Nonce-Feld.
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

   /**
    * Markiert einen Kunden als Neukunde
    *
    * @param string $email
    * @return bool|WP_Error
    */
   public function mark_as_new_customer($email) {
       try {
           if (!is_email($email)) {
               throw new Exception(__('Ungültige E-Mail-Adresse.', 'newcustomer-discount'));
           }

           $result = $this->customer_tracker->add_customer($email);
           if (!$result) {
               throw new Exception(__('Kunde konnte nicht hinzugefügt werden.', 'newcustomer-discount'));
           }

           return true;

       } catch (Exception $e) {
           $this->log_error('Mark as new customer failed', [
               'email' => $email,
               'error' => $e->getMessage()
           ]);
           return new WP_Error('mark_new_customer_failed', $e->getMessage());
       }
   }

   /**
    * Entfernt einen Kunden aus der Neukundenliste
    *
    * @param string $email
    * @return bool|WP_Error
    */
   public function remove_new_customer($email) {
       try {
           if (!is_email($email)) {
               throw new Exception(__('Ungültige E-Mail-Adresse.', 'newcustomer-discount'));
           }

           global $wpdb;
           $table = $this->customer_tracker->get_table_name();
           
           $result = $wpdb->delete(
               $table,
               ['customer_email' => $email],
               ['%s']
           );

           if ($result === false) {
               throw new Exception($wpdb->last_error);
           }

           return true;

       } catch (Exception $e) {
           $this->log_error('Remove new customer failed', [
               'email' => $email,
               'error' => $e->getMessage()
           ]);
           return new WP_Error('remove_new_customer_failed', $e->getMessage());
       }
   }

   /**
    * Prüft den Neukunden-Status eines Kunden
    *
    * @param string $email
    * @return array Status-Informationen
    */
   public function get_customer_status($email) {
       $is_new = $this->customer_tracker->is_new_customer($email);
       $tracking_info = $this->get_tracking_info($email);

       return [
           'is_new' => $is_new,
           'has_coupon' => !empty($tracking_info['coupon_code']),
           'coupon_code' => $tracking_info['coupon_code'] ?? '',
           'discount_sent' => $tracking_info['discount_email_sent'] ?? null,
           'status' => $tracking_info['status'] ?? 'unknown'
       ];
   }

   /**
    * Holt die Tracking-Informationen eines Kunden
    *
    * @param string $email
    * @return array|null
    */
   private function get_tracking_info($email) {
       global $wpdb;
       $table = $this->customer_tracker->get_table_name();

       return $wpdb->get_row(
           $wpdb->prepare(
               "SELECT * FROM $table WHERE customer_email = %s",
               $email
           ),
           ARRAY_A
       );
   }
}