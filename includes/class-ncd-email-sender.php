<?php
/**
 * Email Sender Class
 *
 * Manage email sending and templates
 *
 * @package NewCustomerDiscount
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class NCD_Email_Sender {
    /**
     * Template Directory
     *
     * @var string
     */
    private $template_directory;

    /**
     * Default E-Mail-Settings
     *
     * @var array
     */
    private $default_settings = [
        'from_name' => '',
        'from_email' => '',
        'subject' => '',
        'template' => 'modern'
    ];

    /**
     * Default E-Mail-Text
     *
     * @var array
     */
    private $default_email_texts = [
        'heading' => '',
        'greeting' => '',
        'intro' => '',
        'coupon_info' => '',
        'footer' => ''
    ];

    /**
     * Constructor
     */
    public function __construct() {
        $this->default_settings['subject'] = __('Your Discount Coupon', 'newcustomer-discount');

        $this->default_email_texts = [
            'heading' => __('Your Exclusive Welcome Discount', 'newcustomer-discount'),
            'greeting' => __('Dear Customer,', 'newcustomer-discount'),
            'intro' => __('thank you for your interest in {shop_name}. As a special welcome gift, we have created an exclusive discount coupon for you.', 'newcustomer-discount'),
            'coupon_info' => __('Visit our shop and enter the coupon code at checkout.', 'newcustomer-discount'),
            'footer' => __('This is an automatically generated email.', 'newcustomer-discount')
        ];

        $this->template_directory = NCD_PLUGIN_DIR . 'templates/email/base/';
        $this->default_settings['from_name'] = get_bloginfo('name');
        $this->default_settings['from_email'] = get_option('admin_email');

        add_filter('wp_mail_content_type', [$this, 'set_html_content_type']);

        if (!get_option('ncd_email_texts')) {
            add_option('ncd_email_texts', $this->default_email_texts);
        }
    }

    /**
     * Returns a list of available templates
     *
     * @return array
     */
    public function get_template_list() {
        return [
            'modern' => [
                'file' => $this->template_directory . 'modern.php'
            ],
            'classic' => [
                'file' => $this->template_directory . 'classic.php'
            ],
            'minimal' => [
                'file' => $this->template_directory . 'minimal.php'
            ]
        ];
    }

    /**
     * Loads a template
     *
     * @param string $template_id Template ID
     * @return array Template-Data
     */
    public function load_template($template_id) {
        try {
            $templates = $this->get_template_list();
            
            if (!isset($templates[$template_id])) {
                throw new Exception("Template '$template_id' not found");
            }
            
            $template_file = $templates[$template_id]['file'];
            if (!file_exists($template_file)) {
                throw new Exception("Template file not found: $template_file");
            }
            
            $default_settings = [
                'primary_color' => '#4F46E5',
                'secondary_color' => '#818CF8',
                'text_color' => '#1F2937',
                'background_color' => '#F9FAFB',
                'button_style' => 'rounded',
                'layout_type' => 'full-width',
                'font_family' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
            ];

            $saved_settings = get_option('ncd_template_' . $template_id . '_settings', []);
            
            $settings = wp_parse_args($saved_settings, $default_settings);
            
            if (WP_DEBUG) {
                error_log("Loading template $template_id");
                error_log("Default settings: " . print_r($default_settings, true));
                error_log("Saved settings: " . print_r($saved_settings, true));
                error_log("Final settings: " . print_r($settings, true));
            }
            
            $template = include $template_file;
            
            if ($template === false) {
                throw new Exception("Error loading the template");
            }
            
            return $template;
            
        } catch (Exception $e) {
            if (WP_DEBUG) {
                error_log('Template loading error: ' . $e->getMessage());
            }
            throw $e;
        }
    }

    /**
     * Sends a discount email
     *
     * @param string $email Recipient E-Mail
     * @param array $data E-Mail-data
     * @param array $settings optional E-Mail settings
     * @return bool|WP_Error
     */
    public function send_discount_email($email, $data, $settings = []) {
        try {
            if (!is_email($email)) {
                throw new Exception(__('Ungültige E-Mail-Adresse', 'newcustomer-discount'));
            }
    
            $settings = wp_parse_args($settings, $this->default_settings);
            $template_id = get_option('ncd_active_template', 'modern');
            $saved_settings = get_option('ncd_template_' . $template_id . '_settings', []);
            $template = $this->load_template($template_id);
            $template_settings = wp_parse_args($saved_settings, $template['settings']); 
            
            // Debug-Logging hinzufügen
            if (WP_DEBUG) {
                error_log('Sending email with template: ' . $template_id);
                error_log('Default template settings: ' . print_r($template['settings'], true));
                error_log('Saved custom settings: ' . print_r($saved_settings, true));
                error_log('Final merged settings: ' . print_r($template_settings, true));
            }

            $content = $this->render_template($template_id, $data, $template_settings);
            if (is_wp_error($content)) {
                throw new Exception($content->get_error_message());
            }
    
            $headers = $this->generate_email_headers($settings);
            $subject = get_option('ncd_email_subject', $settings['subject']);
    
            $sent = wp_mail($email, $subject, $content, $headers);
    
            if (!$sent) {
                throw new Exception(__('Email could not be sent', 'newcustomer-discount'));
            }
    
            $this->log_email_sent($email, $data, $template_id);
            return true;
    
        } catch (Exception $e) {
            $this->log_error('Email sending failed', [
                'email' => $email,
                'template_id' => $template_id,
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            return new WP_Error('email_sending_failed', $e->getMessage());
        }
    }

    /**
     * Renders a template
     *
     * @param string $template_id Template ID
     * @param array $data Template-Data
     * @return string Rendered HTML
     */
    public function render_template($template_id, $data, $settings = []) {
        $template = $this->load_template($template_id);

        $default_settings = [
            'primary_color' => '#4F46E5',
            'secondary_color' => '#818CF8',
            'text_color' => '#1F2937',
            'background_color' => '#F9FAFB',
            'button_style' => 'rounded',
            'layout_type' => 'centered',
            'font_family' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
        ];

        $settings = wp_parse_args($settings, wp_parse_args($template['settings'], $default_settings));

        $styles = strtr($template['styles'], [
            'var(--primary-color)' => $settings['primary_color'],
            'var(--secondary-color)' => $settings['secondary_color'],
            'var(--text-color)' => $settings['text_color'],
            'var(--background-color)' => $settings['background_color'],
            'var(--font-family)' => $settings['font_family']
        ]);
    
        $body_style = "background-color: " . $settings['background_color'] . ";";
        
        $html = str_replace(
            [
                '{$settings[\'font_family\']}',
                '{$settings[\'layout_type\']}',
                '{$settings[\'button_style\']}'
            ],
            [
                $settings['font_family'],
                $settings['layout_type'],
                $settings['button_style']
            ],
            $template['html']
        );
        
        $html = "<style>{$styles}</style><body style='{$body_style}'>" . $html . "</body>";
        
        return $this->parse_template($html, $data);
    }

    /**
     * Parses a template string
     *
     * @param string $template Template-String
     * @param array $data Template-Daten
     * @return string
     */
    private function parse_template($template, $data) {
        $min_order_amount = get_option('ncd_min_order_amount', 0);
        $min_order_text = $min_order_amount > 0 ? wc_price($min_order_amount) : '0,00 €';
        $saved_email_texts = get_option('ncd_email_texts');
        $email_texts = wp_parse_args($saved_email_texts, $this->default_email_texts);
        foreach ($email_texts as $key => $text) {
            $email_texts[$key] = strtr($text, [
                '{shop_name}' => get_bloginfo('name'),
                '{coupon_code}' => $data['coupon_code'],
                '{discount_amount}' => get_option('ncd_discount_amount', 20),
                '{expiry_date}' => isset($data['expiry_date']) ? 
                    date_i18n(get_option('date_format'), strtotime($data['expiry_date'])) : 
                    date_i18n(get_option('date_format'), strtotime('+30 days')),
                '{min_order_amount}' => $min_order_text
            ]);
        }

        $replacements = [
            '{coupon_code}' => $data['coupon_code'],
            '{shop_name}' => get_bloginfo('name'),
            '{discount_amount}' => get_option('ncd_discount_amount', 20),
            '{expiry_date}' => isset($data['expiry_date']) ? 
                date_i18n(get_option('date_format'), strtotime($data['expiry_date'])) : 
                date_i18n(get_option('date_format'), strtotime('+30 days')),
            '{shop_url}' => home_url(),
            '{logo_url}' => NCD_Logo_Manager::get_logo(),
            '{current_year}' => date('Y'),
            '{min_order_amount}' => $min_order_text,
            '{currency_symbol}' => get_woocommerce_currency_symbol(),
            '{email_greeting}' => $email_texts['greeting'],
            '{email_intro}' => $email_texts['intro'],
            '{email_coupon_info}' => $email_texts['coupon_info'],
            '{email_footer}' => $email_texts['footer'],
            '{email_heading}' => $email_texts['heading']
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    /**
     * Generates E-Mail-Header
     *
     * @param array $settings E-Mail-Settings
     * @return array
     */
    private function generate_email_headers($settings) {
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            sprintf('From: %s <%s>', $settings['from_name'], $settings['from_email'])
        ];

        return apply_filters('ncd_email_headers', $headers, $settings);
    }

    /**
     * Sets the content type for HTML E-Mails
     *
     * @return string
     */
    public function set_html_content_type() {
        return 'text/html';
    }

    /**
     * Sends a test E-Mail
     *
     * @param string $email Test-Recipient
     * @param string $template_id Template ID
     * @return bool|WP_Error
     */
    public function send_test_email($email, $template_id = 'modern') {
        $test_data = [
            'coupon_code' => 'TESTCODE123',
            'expiry_date' => date('Y-m-d', strtotime('+30 days'))
        ];

        return $this->send_discount_email($email, $test_data, [
            'subject' => '[TEST] ' . $this->default_settings['subject'],
            'template' => $template_id
        ]);
    }

    /**
     * Saves template settings
     *
     * @param string $template_id Template ID
     * @param array $settings Settings to save
     * @return bool
     */
    public function save_template_settings($template_id, $settings) {
        $option_name = 'ncd_template_' . $template_id . '_settings';
        $existing_settings = get_option($option_name, []);
        $settings = wp_parse_args($settings, $existing_settings);

        return update_option($option_name, $settings);
    }

    /**
     * Gets template settings
     *
     * @param string $template_id Template ID
     * @return array
     */
    public function get_template_settings($template_id) {
        $template = $this->load_template($template_id);
        return $template['settings'];
    }

    /**
     * Saves E-Mail-Texts
     *
     * @param array $texts to save E-Mail-Texts
     * @return bool
     */
    public function save_email_texts($texts) {
        return update_option('ncd_email_texts', wp_parse_args($texts, $this->default_email_texts));
    }

    /**
     * Returns E-Mail-Texts
     *
     * @return array
     */
    public function get_email_texts() {
        return get_option('ncd_email_texts', $this->default_email_texts);
    }

    /**
     * Returns available variables
     *
     * @return array
     */
    public function get_available_variables() {
        return [
            '{coupon_code}' => __('The generated coupon code', 'newcustomer-discount'),
            '{shop_name}' => __('Shop name', 'newcustomer-discount'),
            '{discount_amount}' => __('Discount percentage', 'newcustomer-discount'),
            '{expiry_date}' => __('Coupon expiry date', 'newcustomer-discount'),
            '{shop_url}' => __('Shop URL', 'newcustomer-discount'),
            '{logo_url}' => __('Shop logo URL', 'newcustomer-discount'),
            '{current_year}' => __('Current year', 'newcustomer-discount'),
            '{min_order_amount}' => __('Minimum order amount', 'newcustomer-discount'),
            '{currency_symbol}' => __('Currency symbol', 'newcustomer-discount')
        ];
    }

    /**
     * Saves E-Mail-Databased on E-Mail-Log
     *
     * @param string $email E-Mail-Recipient
     * @param array $data E-Mail-Data
     * @return void
     */
    private function log_email_sent($email, $data, $template_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'ncd_email_log';
    
        $wpdb->insert(
            $table,
            [
                'email' => $email,
                'coupon_code' => $data['coupon_code'],
                'sent_date' => current_time('mysql'),
                'status' => 'sent',
                'template_version' => $template_id
            ],
            ['%s', '%s', '%s', '%s', '%s']
        );
    }

    /**
     * Creates the E-Mail-Log table
     *
     * @return void
     */
    public static function create_log_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ncd_email_log';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            email varchar(255) NOT NULL,
            coupon_code varchar(50) NOT NULL,
            sent_date datetime DEFAULT NULL,
            status varchar(20) NOT NULL,
            template_version varchar(50) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY email (email),
            KEY coupon_code (coupon_code),
            KEY sent_date (sent_date)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Returns E-Mail-Logs
     *
     * @param array $args Query-Argumente
     * @return array
     */
    public function get_email_logs($args = []) {
        global $wpdb;
        
        $defaults = [
            'limit' => 50,
            'offset' => 0,
            'orderby' => 'sent_date',
            'order' => 'DESC'
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        $table = $wpdb->prefix . 'ncd_email_log';
        
        return $wpdb->get_results($wpdb->prepare("
        SELECT *
        FROM $table
        ORDER BY {$args['orderby']} {$args['order']}
        LIMIT %d OFFSET %d
    ", $args['limit'], $args['offset']));
    }

    /**
     * Cleans up old E-Mail-Logs
     *
     * @param int $days Age in days
     * @return int Number of deleted logs
     */
    public function cleanup_logs($days = 90) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ncd_email_log';
        
        return $wpdb->query($wpdb->prepare("
            DELETE FROM $table
            WHERE sent_date < DATE_SUB(NOW(), INTERVAL %d DAY)
        ", $days));
    }

    /**
     * Error logging for debugging
     *
     * @param string $message Error message
     * @param array $context additional context
     * @return void
     */
    private function log_error($message, $context = []) {
        if (WP_DEBUG) {
            error_log(sprintf(
                '[NewCustomerDiscount] Email Sender Error: %s | Context: %s',
                $message,
                json_encode($context)
            ));
        }
    }

    /**
     * Renders a template preview
     *
     * @param string $template_id Template ID
     * @param array $settings temporary settings
     * @return string HTML preview
     */
    public function render_preview($template_id, $settings = []) {
        try {
            $template = $this->load_template($template_id);
            
            if (empty($template)) {
                throw new Exception('Template not found');
            }

            $settings = !empty($settings) ? $settings : $template['settings'];

            $test_data = [
                'coupon_code' => 'TESTCODE123',
                'expiry_date' => date('Y-m-d', strtotime('+30 days'))
            ];

            $styles = strtr($template['styles'], [
                'var(--primary-color)' => $settings['primary_color'],
                'var(--secondary-color)' => $settings['secondary_color'],
                'var(--text-color)' => $settings['text_color'],
                'var(--background-color)' => $settings['background_color'],
                'var(--font-family)' => $settings['font_family']
            ]);

            // Füge body-Style für Hintergrundfarbe hinzu
            $preview = "
                <style>{$styles}</style>
                <div style='background-color: {$settings['background_color']}; padding: 20px; min-height: 100%;'>
                    " . $this->parse_template($template['html'], $test_data) . "
                </div>";
            
            return $preview;

        } catch (Exception $e) {
            if (WP_DEBUG) {
                error_log('Template preview error: ' . $e->getMessage());
            }
            return '<div class="error">Error loading preview</div>';
        }
    }
}