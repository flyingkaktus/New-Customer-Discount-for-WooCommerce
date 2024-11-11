<?php
/**
* Admin Customers Class
*
* Manages the customers admin page
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
     * renders the customers page
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
     * Handler for the send discount AJAX request
     * 
     * @param array $data AJAX request data
     */
    public function handle_send_discount($data) {
        if (!$this->ajax_handler->check_ajax_request()) {
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
                throw new Exception(__('The customer is not a new customer.', 'newcustomer-discount'));
            }
    
            if (WP_DEBUG) {
                error_log('Creating discount for: ' . $email);
            }
    
            $discount = $this->coupon_generator->create_coupon($email);
            if (is_wp_error($discount)) {
                error_log('Discount creation failed: ' . $discount->get_error_message());
                throw new Exception($discount->get_error_message());
            }
    
            if (WP_DEBUG) {
                error_log('Sending email with discount: ' . print_r($discount, true));
            }
    
            $result = $this->email_sender->send_discount_email($email, [
                'coupon_code' => $discount['code'],
                'first_name' => $first_name,
                'last_name' => $last_name
            ]);
    
            if (is_wp_error($result)) {
                error_log('Email sending failed: ' . $result->get_error_message());
                $this->coupon_generator->deactivate_coupon($discount['code']);
                throw new Exception($result->get_error_message());
            }
    
            if (WP_DEBUG) {
                error_log('Updating customer status');
            }
    
            $this->customer_tracker->update_customer_status($email, 'sent', $discount['code']);
    
            wp_send_json_success([
                'message' => sprintf(
                    __('The discount code %s was successfully sent to %s.', 'newcustomer-discount'),
                    $discount['code'],
                    $email
                ),
                'coupon_code' => $discount['code'],
                'sent_date' => date_i18n(
                    get_option('date_format') . ' ' . get_option('time_format'),
                    current_time('timestamp')
                )
            ]);
    
        } catch (Exception $e) {
            error_log('Discount email sending failed: ' . $e->getMessage());
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

   /**
    * Marks a customer as new customer
    *
    * @param string $email
    * @return bool|WP_Error
    */
   public function mark_as_new_customer($email) {
       try {
           if (!is_email($email)) {
               throw new Exception(__('Invalid email address.', 'newcustomer-discount'));
           }

           $result = $this->customer_tracker->add_customer($email);
           if (!$result) {
               throw new Exception(__('Customer could not be added.', 'newcustomer-discount'));
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
    * Removes a customer from the new customer list
    *
    * @param string $email
    * @return bool|WP_Error
    */
   public function remove_new_customer($email) {
       try {
           if (!is_email($email)) {
               throw new Exception(__('Invalid email address.', 'newcustomer-discount'));
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
    * Checks if a customer is a new customer
    *
    * @param string $email
    * @return array status
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
    * Gets the tracking info for a customer
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