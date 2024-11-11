<?php
/**
 * Discount Generator Class
 *
 * Manage discount code generation 
 *
 * @package NewCustomerDiscount
 * @since 0.0.1
 */

if (!defined('ABSPATH')) {
    exit;
}

class NCD_Discount_Generator {
    /**
     * Character sets for code generation
     *
     * @var array
     */
    private $character_sets = [
        'numbers' => '0123456789',
        'uppercase' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
        'lowercase' => 'abcdefghijklmnopqrstuvwxyz'
    ];

    /**
     * Characters for code generation
     *
     * @var string
     */
    private $characters;

    /**
     * Discount code prefix
     *
     * @var string
     */
    private $prefix;

    /**
     * Discount code length
     *
     * @var int
     */
    private $code_length;

    /**
     * Default coupon settings
     *
     * @var array
     */
    private $default_coupon_settings = [
        'discount_type' => 'percent',
        'discount_amount' => 20,
        'individual_use' => 'yes',
        'usage_limit' => 1,
        'expiry_days' => 30
    ];

    /**
     * Constructor
     *
     * @param array $settings Optional settings to override defaults
     */
    public function __construct($settings = []) {
        $saved_discount = get_option('ncd_discount_amount', 20);
        $saved_expiry = get_option('ncd_expiry_days', 30);

        $this->default_coupon_settings['discount_amount'] = $saved_discount;
        $this->default_coupon_settings['expiry_days'] = $saved_expiry;

        $this->default_coupon_settings = wp_parse_args($settings, $this->default_coupon_settings);

        $this->init_code_settings();

        if (WP_DEBUG) {
            error_log('NCD Gutschein Generator initialized with settings:');
            error_log('Prefix: ' . $this->prefix);
            error_log('Code length: ' . $this->code_length);
            error_log('Characters: ' . $this->characters);
            error_log('Discount amount: ' . $this->default_coupon_settings['discount_amount']);
            error_log('Expiry days: ' . $this->default_coupon_settings['expiry_days']);
        }
    }

    /**
     * Initializes code settings
     */
    private function init_code_settings() {
        $this->prefix = get_option('ncd_code_prefix', 'NL');
        $this->code_length = (int)get_option('ncd_code_length', 6);
        $char_types = (array)get_option('ncd_code_chars', ['numbers', 'uppercase']);

        $this->characters = '';
        foreach ($char_types as $type) {
            if (isset($this->character_sets[$type])) {
                $this->characters .= $this->character_sets[$type];
            }
        }

        if (empty($this->characters)) {
            $this->characters = $this->character_sets['numbers'] . $this->character_sets['uppercase'];
        }
    }

    /**
     * Generates a unique discount code
     *
     * @return string
     */
    public function generate_unique_code() {
        $max_attempts = 20;
        $attempt = 0;

        do {
            $code = $this->prefix;
            for ($i = 0; $i < $this->code_length; $i++) {
                $code .= $this->characters[rand(0, strlen($this->characters) - 1)];
            }
            $exists = $this->coupon_exists($code);
            $attempt++;
        } while ($exists && $attempt < $max_attempts);

        if ($attempt >= $max_attempts) {
            throw new Exception(__('Could not generate a unique code.', 'newcustomer-discount'));
        }

        return $code;
    }

    /**
     * Creates a new coupon
     *
     * @param string $email E-Mail of the customer
     * @param array $settings Optional coupon settings
     * @return array|WP_Error Array with coupon details or WP_Error on failure
     */
    public function create_coupon($email, $settings = []) {
        try {
            if (!is_email($email)) {
                throw new Exception(__('Invalid email address.', 'newcustomer-discount'));
            }

            $settings = wp_parse_args($settings, $this->default_coupon_settings);
            $code = $this->generate_unique_code();

            $discount = [
                'post_title' => $code,
                'post_content' => '',
                'post_status' => 'publish',
                'post_author' => get_current_user_id(),
                'post_type' => 'shop_coupon'
            ];

            $coupon_id = wp_insert_post($discount, true);

            if (is_wp_error($coupon_id)) {
                throw new Exception($coupon_id->get_error_message());
            }

            $this->set_coupon_meta($coupon_id, $email, $settings);

            $min_amount = get_option('ncd_min_order_amount', 0);
            if ($min_amount > 0) {
                update_post_meta($coupon_id, 'minimum_amount', $min_amount);
            }

            $excluded_cats = get_option('ncd_excluded_categories', []);
            if (!empty($excluded_cats)) {
                update_post_meta($coupon_id, 'exclude_product_categories', $excluded_cats);
            }

            return [
                'id' => $coupon_id,
                'code' => $code,
                'settings' => $settings,
                'expiry_date' => date('Y-m-d', strtotime("+{$settings['expiry_days']} days"))
            ];

        } catch (Exception $e) {
            $this->log_error('Gutschein creation failed', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            return new WP_Error('coupon_creation_failed', $e->getMessage());
        }
    }

    /**
     * Sets coupon meta data
     *
     * @param int $coupon_id Post ID of the discount
     * @param string $email E-Mail of the customer
     * @param array $settings Discount settings
     */
    private function set_coupon_meta($coupon_id, $email, $settings) {
        $meta_data = [
            'discount_type' => $settings['discount_type'],
            'coupon_amount' => $settings['discount_amount'],
            'individual_use' => $settings['individual_use'],
            'usage_limit' => $settings['usage_limit'],
            'expiry_date' => date('Y-m-d', strtotime("+{$settings['expiry_days']} days")),
            'customer_email' => [$email],
            'exclude_sale_items' => 'no',
            'free_shipping' => 'no',
            '_ncd_generated' => 'yes',
            '_ncd_customer_email' => $email,
            '_ncd_creation_date' => current_time('mysql')
        ];

        foreach ($meta_data as $key => $value) {
            update_post_meta($coupon_id, $key, $value);
        }
    }

    /**
     * Checks if a coupon exists
     *
     * @param string $code Discount code
     * @return bool
     */
    public function coupon_exists($code) {
        global $wpdb;
        
        $count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*)
            FROM {$wpdb->posts}
            WHERE post_type = 'shop_coupon'
            AND post_title = %s
            AND post_status IN ('publish', 'draft', 'pending', 'private')
        ", $code));

        return $count > 0;
    }

    /**
     * Checks if a coupon is valid
     *
     * @param string $code coupon code
     * @return array status-information
     */
    public function get_coupon_status($code) {
        $discount = new WC_Coupon($code);
        
        if (!$discount->get_id()) {
            return [
                'exists' => false,
                'valid' => false,
                'message' => __('Coupon does not exist.', 'newcustomer-discount')
            ];
        }

        $status = [
            'exists' => true,
            'valid' => true,
            'usage_count' => $discount->get_usage_count(),
            'usage_limit' => $discount->get_usage_limit(),
            'expiry_date' => $discount->get_date_expires() ? $discount->get_date_expires()->date('Y-m-d') : null,
            'is_expired' => $discount->get_date_expires() && $discount->get_date_expires()->getTimestamp() < time(),
            'customer_email' => $discount->get_email_restrictions(),
            'minimum_amount' => $discount->get_minimum_amount(),
            'excluded_categories' => $discount->get_excluded_product_categories()
        ];

        if ($status['is_expired']) {
            $status['valid'] = false;
            $status['message'] = __('Coupon has expired.', 'newcustomer-discount');
        } elseif ($status['usage_count'] >= $status['usage_limit']) {
            $status['valid'] = false;
            $status['message'] = __('Coupon has already been redeemed.', 'newcustomer-discount');
        }

        return $status;
    }

    /**
     * Deactivates a coupon
     *
     * @param string $code coupon code
     * @return bool
     */
    public function deactivate_coupon($code) {
        $coupon_id = wc_get_coupon_id_by_code($code);
        if (!$coupon_id) {
            return false;
        }

        return wp_update_post([
            'ID' => $coupon_id,
            'post_status' => 'trash'
        ]);
    }

    /**
     * Returns all generated coupons
     *
     * @param array $args Query-Argumente
     * @return array
     */
    public function get_generated_coupons($args = []) {
        $defaults = [
            'posts_per_page' => -1,
            'post_type' => 'shop_coupon',
            'post_status' => 'publish',
            'meta_query' => [
                [
                    'key' => '_ncd_generated',
                    'value' => 'yes'
                ]
            ],
            'orderby' => 'date',
            'order' => 'DESC'
        ];

        $args = wp_parse_args($args, $defaults);
        $discounte = get_posts($args);

        return array_map(function($discount) {
            return [
                'id' => $discount->ID,
                'code' => $discount->post_title,
                'email' => get_post_meta($discount->ID, '_ncd_customer_email', true),
                'created' => get_post_meta($discount->ID, '_ncd_creation_date', true),
                'discount_amount' => get_post_meta($discount->ID, 'coupon_amount', true),
                'minimum_amount' => get_post_meta($discount->ID, 'minimum_amount', true),
                'expiry_date' => get_post_meta($discount->ID, 'expiry_date', true),
                'status' => $this->get_coupon_status($discount->post_title)
            ];
        }, $discounte);
    }

    /**
     * Cleans up expired coupons
     *
     * @return int Number of deleted coupons
     */
    public function cleanup_expired_coupons() {
        $expired_coupons = get_posts([
            'post_type' => 'shop_coupon',
            'post_status' => 'publish',
            'meta_query' => [
                [
                    'key' => '_ncd_generated',
                    'value' => 'yes'
                ],
                [
                    'key' => 'expiry_date',
                    'value' => date('Y-m-d'),
                    'compare' => '<',
                    'type' => 'DATE'
                ]
            ],
            'posts_per_page' => -1
        ]);

        $count = 0;
        foreach ($expired_coupons as $discount) {
            if (wp_update_post(['ID' => $discount->ID, 'post_status' => 'trash'])) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Logging for errors
     *
     * @param string $message error message
     * @param array $context additional context
     * @return void
     */
    private function log_error($message, $context = []) {
        if (WP_DEBUG) {
            error_log(sprintf(
                '[NewCustomerDiscount] Discount Generator Error: %s | Context: %s',
                $message,
                json_encode($context)
            ));
        }
    }

    /**
     * Returns available character sets
     *
     * @return array
     */
    public function get_available_character_sets() {
        return $this->character_sets;
    }

    /**
     * Validates a coupon code format
     *
     * @param string $code to validate code
     * @return bool|WP_Error
     */
    public function validate_code_format($code) {
        if (strlen($code) < strlen($this->prefix) + 4 || strlen($code) > strlen($this->prefix) + 12) {
            return new WP_Error(
                'invalid_length',
                __('Invalid coupon length', 'newcustomer-discount')
            );
        }

        if (strpos($code, $this->prefix) !== 0) {
            return new WP_Error(
                'invalid_prefix',
                __('Invalid coupon prefix', 'newcustomer-discount')
            );
        }

        $code_part = substr($code, strlen($this->prefix));
        if (!preg_match('/^[' . preg_quote($this->characters, '/') . ']+$/', $code_part)) {
            return new WP_Error(
                'invalid_characters',
                __('Invalid characters in code', 'newcustomer-discount')
            );
        }

        return true;
    }
}