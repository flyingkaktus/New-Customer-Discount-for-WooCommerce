<?php
/**
 * Admin Class
 *
 * Verwaltet alle Admin-bezogenen Funktionalitäten
 *
 * @package NewCustomerDiscount
 * @since 0.0.1
 */

if (!defined('ABSPATH')) {
    exit;
}

class NCD_Admin
{
    /**
     * @var NCD_Customer_Tracker
     */
    private $customer_tracker;

    /**
     * @var NCD_Coupon_Generator
     */
    private $coupon_generator;

    /**
     * @var NCD_Email_Sender
     */
    private $email_sender;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->init_dependencies();
        $this->init_hooks();
        $this->init_ajax_handlers();
        add_action('admin_post_ncd_reset_data', [$this, 'handle_reset_data']);
    }

    /**
     * Initialisiert die Abhängigkeiten
     */
    private function init_dependencies()
    {
        $this->customer_tracker = new NCD_Customer_Tracker();
        $this->coupon_generator = new NCD_Coupon_Generator();
        $this->email_sender = new NCD_Email_Sender();
    }

    /**
     * Initialisiert die WordPress Hooks
     */
    private function init_hooks()
    {
        add_action('admin_menu', [$this, 'add_menu_pages']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_ncd_send_test_email', [$this, 'ajax_send_test_email']);
        add_action('wp_ajax_ncd_send_discount', [$this, 'ajax_send_discount']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_notices', [$this, 'display_admin_notices']);
    }

    /**
     * Registriert die Plugin-Einstellungen
     */
    public function register_settings()
    {
        // General Settings
        register_setting('ncd_general_settings', 'ncd_delete_all_on_uninstall');

        // Email Settings
        register_setting('ncd_email_settings', 'ncd_email_subject');

        // Coupon Settings
        register_setting('ncd_coupon_settings', 'ncd_discount_amount');
        register_setting('ncd_coupon_settings', 'ncd_expiry_days');

        // Template Settings
        register_setting('ncd_template_settings', 'ncd_email_template');

        // Code Settings
        register_setting('ncd_code_settings', 'ncd_code_prefix');
        register_setting('ncd_code_settings', 'ncd_code_length');
        register_setting('ncd_code_settings', 'ncd_code_chars');

        // Customer Settings
        register_setting('ncd_customer_settings', 'ncd_cutoff_date');
        register_setting('ncd_customer_settings', 'ncd_order_count');
        register_setting('ncd_customer_settings', 'ncd_check_period');
        register_setting('ncd_customer_settings', 'ncd_min_order_amount');
        register_setting('ncd_customer_settings', 'ncd_excluded_categories');
    }

    /**
     * Fügt Menü-Einträge hinzu
     */
    public function add_menu_pages()
    {
        add_menu_page(
            __('Neukunden', 'newcustomer-discount'),
            __('Neukunden', 'newcustomer-discount'),
            'manage_options',
            'new-customers',
            [$this, 'render_customers_page'],
            'dashicons-groups',
            30
        );

        add_submenu_page(
            'new-customers',
            __('E-Mail Templates', 'newcustomer-discount'),
            __('Templates', 'newcustomer-discount'),
            'manage_options',
            'new-customers-templates',
            [$this, 'render_templates_page']
        );

        add_submenu_page(
            'new-customers',
            __('Einstellungen', 'newcustomer-discount'),
            __('Einstellungen', 'newcustomer-discount'),
            'manage_options',
            'new-customers-settings',
            [$this, 'render_settings_page']
        );

        add_submenu_page(
            'new-customers',
            __('Statistiken', 'newcustomer-discount'),
            __('Statistiken', 'newcustomer-discount'),
            'manage_options',
            'new-customers-statistics',
            [$this, 'render_statistics_page']
        );
    }

    /**
     * Lädt Admin Assets
     *
     * @param string $hook Der aktuelle Admin-Seiten-Hook
     */
    public function enqueue_assets($hook)
    {
        if (strpos($hook, 'new-customers') === false) {
            return;
        }

        // Basis Admin CSS
        wp_enqueue_style(
            'ncd-admin',
            NCD_PLUGIN_URL . 'assets/css/admin.css',
            [],
            NCD_VERSION
        );

        // Template-spezifisches CSS
        if (strpos($hook, 'new-customers-templates') !== false) {
            wp_enqueue_style(
                'ncd-admin-templates',
                NCD_PLUGIN_URL . 'assets/css/admin-templates.css',
                [],
                NCD_VERSION
            );
        }

        // Admin JavaScript
        wp_enqueue_script(
            'ncd-admin',
            NCD_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery'],
            NCD_VERSION,
            true
        );

        // JavaScript Lokalisierung
        wp_localize_script('ncd-admin', 'ncdAdmin', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ncd-admin-nonce'),
            'messages' => [
                'confirm_send' => __('Möchten Sie wirklich einen Rabattcode an diesen Kunden senden?', 'newcustomer-discount'),
                'confirm_test' => __('Möchten Sie eine Test-E-Mail an diese Adresse senden?', 'newcustomer-discount'),
                'error' => __('Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.', 'newcustomer-discount'),
                'email_required' => __('Bitte geben Sie eine E-Mail-Adresse ein.', 'newcustomer-discount')
            ]
        ]);
    }

    /**
     * Rendert die Hauptseite
     */
    public function render_customers_page()
    {
        // Filter-Parameter
        $days = isset($_GET['days_filter']) ? (int) $_GET['days_filter'] : 30;
        $only_new = isset($_GET['only_new']);

        // Hole Kundendaten
        $customers = $this->customer_tracker->get_customers([
            'days' => $days,
            'only_new' => $only_new
        ]);

        // Template laden
        include NCD_PLUGIN_DIR . 'templates/admin/customers-page.php';
    }

    /**
     * Rendert die Einstellungsseite
     */
    public function render_settings_page()
    {
        if ($this->handle_settings_post()) {
            $this->add_admin_notice(
                __('Einstellungen wurden gespeichert.', 'newcustomer-discount'),
                'success'
            );
        }

        // Template laden
        include NCD_PLUGIN_DIR . 'templates/admin/settings-page.php';
    }

    /**
     * Rendert die Statistikseite
     */
    public function render_statistics_page()
    {
        $stats = [
            'customers' => $this->customer_tracker->get_statistics(),
            'coupons' => $this->get_coupon_statistics(),
            'emails' => $this->get_email_statistics()
        ];

        // Template laden
        include NCD_PLUGIN_DIR . 'templates/admin/statistics-page.php';
    }

    /**
     * Verarbeitet AJAX Test-E-Mail Anfrage
     */
    public function ajax_send_test_email()
    {
        check_ajax_referer('ncd-admin-nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Keine Berechtigung.', 'newcustomer-discount')]);
        }

        $email = sanitize_email($_POST['email']);
        if (!is_email($email)) {
            wp_send_json_error(['message' => __('Ungültige E-Mail-Adresse.', 'newcustomer-discount')]);
        }

        $result = $this->email_sender->send_test_email($email);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success([
            'message' => sprintf(
                __('Test-E-Mail wurde an %s gesendet.', 'newcustomer-discount'),
                $email
            )
        ]);
    }

    /**
     * Verarbeitet AJAX Rabatt-E-Mail Anfrage
     */
    public function ajax_send_discount()
    {
        check_ajax_referer('ncd-admin-nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Keine Berechtigung.', 'newcustomer-discount')]);
        }

        $email = sanitize_email($_POST['email']);
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);

        // Erstelle Gutschein
        $coupon = $this->coupon_generator->create_coupon($email);
        if (is_wp_error($coupon)) {
            wp_send_json_error(['message' => $coupon->get_error_message()]);
        }

        // Sende E-Mail
        $result = $this->email_sender->send_discount_email($email, [
            'coupon_code' => $coupon['code'],
            'first_name' => $first_name,
            'last_name' => $last_name
        ]);

        if (is_wp_error($result)) {
            // Lösche Gutschein bei E-Mail-Fehler
            $this->coupon_generator->deactivate_coupon($coupon['code']);
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        // Aktualisiere Tracking
        $this->customer_tracker->update_customer_status($email, 'sent', $coupon['code']);

        wp_send_json_success([
            'message' => sprintf(
                __('Rabattcode %s wurde an %s gesendet.', 'newcustomer-discount'),
                $coupon['code'],
                $email
            )
        ]);
    }

    /**
     * Verarbeitet POST-Anfragen der Einstellungsseite
     *
     * @return bool
     */
    private function handle_settings_post()
    {
        if (!isset($_POST['ncd_settings_nonce'])) {
            return false;
        }

        check_admin_referer('ncd_settings', 'ncd_settings_nonce');

        if (isset($_POST['update_logo'])) {
            if (!empty($_FILES['logo_file']['name'])) {
                NCD_Logo_Manager::save_logo($_FILES['logo_file']);
            } elseif (!empty($_POST['logo_base64'])) {
                NCD_Logo_Manager::save_base64($_POST['logo_base64']);
            }
            return true;
        }

        if (isset($_POST['delete_logo'])) {
            NCD_Logo_Manager::delete_logo();
            return true;
        }

        if (isset($_POST['save_email_settings'])) {
            update_option('ncd_email_subject', sanitize_text_field($_POST['email_subject']));
            return true;
        }

        if (isset($_POST['save_coupon_settings'])) {
            $discount_amount = absint($_POST['discount_amount']);
            $expiry_days = absint($_POST['expiry_days']);

            // Validierung
            $discount_amount = min(max($discount_amount, 1), 100); // Zwischen 1 und 100
            $expiry_days = min(max($expiry_days, 1), 365); // Zwischen 1 und 365

            // Debug-Logging
            if (WP_DEBUG) {
                error_log('Saving coupon settings:');
                error_log('Discount amount: ' . $discount_amount);
                error_log('Expiry days: ' . $expiry_days);
            }

            update_option('ncd_discount_amount', $discount_amount);
            update_option('ncd_expiry_days', $expiry_days);

            // Cache leeren
            wp_cache_flush();

            // WooCommerce Transients leeren
            if (function_exists('wc_delete_shop_order_transients')) {
                wc_delete_shop_order_transients();
            }

            return true;
        }

        if (isset($_POST['save_code_settings'])) {
            $prefix = sanitize_text_field($_POST['code_prefix']);
            $length = absint($_POST['code_length']);
            $chars = isset($_POST['code_chars']) ? (array) $_POST['code_chars'] : ['numbers', 'uppercase'];

            // Validierung
            $prefix = substr($prefix, 0, 5);
            $length = min(max($length, 4), 12);
            $chars = array_intersect($chars, ['numbers', 'uppercase', 'lowercase']);

            update_option('ncd_code_prefix', $prefix);
            update_option('ncd_code_length', $length);
            update_option('ncd_code_chars', $chars);
            return true;
        }

        if (isset($_POST['save_customer_settings'])) {
            $cutoff_date = sanitize_text_field($_POST['cutoff_date']);
            $order_count = absint($_POST['order_count']);
            $check_period = sanitize_text_field($_POST['check_period']);
            $min_amount = floatval($_POST['min_order_amount']);
            $excluded_cats = isset($_POST['exclude_categories']) ?
                array_map('absint', $_POST['exclude_categories']) : [];

            // Validierung
            if (strtotime($cutoff_date) === false) {
                $cutoff_date = '2024-01-01';
            }
            $order_count = min(max($order_count, 0), 10);
            $min_amount = max($min_amount, 0);

            update_option('ncd_cutoff_date', $cutoff_date);
            update_option('ncd_order_count', $order_count);
            update_option('ncd_check_period', $check_period);
            update_option('ncd_min_order_amount', $min_amount);
            update_option('ncd_excluded_categories', $excluded_cats);
            return true;
        }

        if (isset($_POST['save_advanced_settings'])) {
            // Zusätzliche Sicherheitsvalidierung für Admin-Einstellungen
            if (!current_user_can('manage_options')) {
                return false;
            }

            $delete_data = isset($_POST['delete_all_on_uninstall']) ? 1 : 0;
            update_option('ncd_delete_all_on_uninstall', $delete_data);

            // Debug Mode Einstellung
            $debug_mode = isset($_POST['enable_debug_mode']) ? 1 : 0;
            update_option('ncd_debug_mode', $debug_mode);

            return true;
        }

        if (isset($_POST['do_reset']) && isset($_POST['ncd_reset_nonce'])) {

            error_log('Reset initiated');
            error_log('Reset actions: ' . print_r(isset($_POST['reset_actions']) ? $_POST['reset_actions'] : 'no actions selected', true));
            error_log('Nonce verification: ' . wp_verify_nonce($_POST['ncd_reset_nonce'], 'ncd_reset_settings'));
            error_log('Confirmation checked: ' . isset($_POST['confirm_reset']));

            if (!wp_verify_nonce($_POST['ncd_reset_nonce'], 'ncd_reset_settings')) {
                return false;
            }

            if (!isset($_POST['confirm_reset']) || $_POST['confirm_reset'] != '1') {
                $this->add_admin_notice(
                    __('Bitte bestätigen Sie den Reset-Vorgang.', 'newcustomer-discount'),
                    'error'
                );
                return false;
            }

            $actions = isset($_POST['reset_actions']) ? (array) $_POST['reset_actions'] : [];
            $reset_count = 0;

            // Reset Gutscheine
            if (in_array('coupons', $actions)) {
                $reset_count += $this->reset_coupons();
            }

            // Reset Neukunden
            if (in_array('customers', $actions)) {
                $reset_count += $this->reset_customers();
            }

            if ($reset_count > 0) {
                $this->add_admin_notice(
                    sprintf(
                        __('Reset erfolgreich durchgeführt. %d Einträge wurden zurückgesetzt.', 'newcustomer-discount'),
                        $reset_count
                    ),
                    'success'
                );
            } else {
                $this->add_admin_notice(
                    __('Keine Daten zum Zurücksetzen ausgewählt.', 'newcustomer-discount'),
                    'warning'
                );
            }

            if (in_array('coupons', $actions)) {
                error_log('Coupons reset count: ' . $this->reset_coupons());
            }
            if (in_array('customers', $actions)) {
                error_log('Customers reset count: ' . $this->reset_customers());
            }

            return true;
        }

        // Allgemeine Cache-Bereinigung nach allen Einstellungsänderungen
        wp_cache_flush();
        if (function_exists('wc_delete_shop_order_transients')) {
            wc_delete_shop_order_transients();
        }

        return false;
    }

    /**
     * Fügt Admin-Benachrichtigung hinzu
     *
     * @param string $message Nachricht
     * @param string $type Typ der Nachricht (success, error, warning, info)
     */
    private function add_admin_notice($message, $type = 'success')
    {
        add_settings_error(
            'ncd_messages',
            'ncd_message',
            $message,
            $type
        );
    }

    /**
     * Verarbeitet Reset-Anfragen
     */
    public function handle_reset_data()
    {
        error_log('NCD Reset handler called');

        if (!current_user_can('manage_options')) {
            wp_die(__('Keine Berechtigung.', 'newcustomer-discount'));
        }

        if (!isset($_POST['ncd_reset_nonce']) || !wp_verify_nonce($_POST['ncd_reset_nonce'], 'ncd_reset_settings')) {
            error_log('Reset nonce verification failed');
            wp_die(__('Sicherheitsüberprüfung fehlgeschlagen.', 'newcustomer-discount'));
        }

        error_log('Processing reset request');
        error_log('POST data: ' . print_r($_POST, true));

        if (!isset($_POST['confirm_reset']) || $_POST['confirm_reset'] != '1') {
            error_log('Reset not confirmed');
            wp_redirect(add_query_arg([
                'page' => 'new-customers-settings',
                'tab' => 'reset',
                'message' => 'not-confirmed'
            ], admin_url('admin.php')));
            exit;
        }

        $actions = isset($_POST['reset_actions']) ? (array) $_POST['reset_actions'] : [];
        $reset_count = 0;

        if (in_array('coupons', $actions)) {
            error_log('Resetting coupons');
            $coupon_count = $this->reset_coupons();
            error_log('Reset coupons count: ' . $coupon_count);
            $reset_count += $coupon_count;
        }

        if (in_array('customers', $actions)) {
            error_log('Resetting customers');
            $customer_count = $this->reset_customers();
            error_log('Reset customers count: ' . $customer_count);
            $reset_count += $customer_count;
        }

        error_log('Total reset count: ' . $reset_count);

        // Redirect zurück zur Einstellungsseite mit Statusmeldung
        wp_redirect(add_query_arg([
            'page' => 'new-customers-settings',
            'tab' => 'reset',
            'message' => $reset_count > 0 ? 'reset-success' : 'no-data',
            'count' => $reset_count
        ], admin_url('admin.php')));
        exit;
    }

    /**
     * Zeigt Admin-Benachrichtigungen an
     */
    public function display_admin_notices()
    {
        // Zeige bestehende Settings-Fehler
        settings_errors('ncd_messages');

        // Zeige Reset-bezogene Nachrichten
        if (isset($_GET['message']) && isset($_GET['page']) && strpos($_GET['page'], 'new-customers') !== false) {
            switch ($_GET['message']) {
                case 'reset-success':
                    $count = isset($_GET['count']) ? intval($_GET['count']) : 0;
                    $message = sprintf(
                        __('Reset erfolgreich durchgeführt. %d Einträge wurden zurückgesetzt.', 'newcustomer-discount'),
                        $count
                    );
                    $type = 'success';
                    break;
                case 'no-data':
                    $message = __('Keine Daten zum Zurücksetzen ausgewählt.', 'newcustomer-discount');
                    $type = 'warning';
                    break;
                case 'not-confirmed':
                    $message = __('Bitte bestätigen Sie den Reset-Vorgang.', 'newcustomer-discount');
                    $type = 'error';
                    break;
                default:
                    return;
            }

            printf(
                '<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
                esc_attr($type),
                esc_html($message)
            );
        }
    }

    /**
     * Holt Gutschein-Statistiken
     *
     * @return array
     */
    private function get_coupon_statistics()
    {
        if (WP_DEBUG) {
            error_log('======= Starting coupon statistics calculation =======');
        }
    
        $coupons = $this->coupon_generator->get_generated_coupons();
    
        $stats = [
            'total' => count($coupons),
            'used' => 0,
            'expired' => 0,
            'active' => 0,
            'total_amount' => 0
        ];
    
        foreach ($coupons as $coupon) {
            if (!$coupon['status']['valid']) {
                if ($coupon['status']['is_expired']) {
                    $stats['expired']++;
                } else {
                    $stats['used']++;
                }
            } else {
                $stats['active']++;
            }
            $stats['total_amount'] += floatval($coupon['discount_amount']);
        }
    
        // Berechne durchschnittlichen Bestellwert
        $avg_order_value = $this->calculate_average_order_value();
        $stats['avg_order_value'] = $avg_order_value;
    
        if (WP_DEBUG) {
            error_log('Coupon statistics calculated:');
            error_log(print_r($stats, true));
            error_log('======= End coupon statistics calculation =======');
        }
    
        return $stats;
    }

    /**
     * Holt E-Mail-Statistiken
     *
     * @return array
     */
    private function get_email_statistics()
    {
        $logs = $this->email_sender->get_email_logs();

        return [
            'total_sent' => count($logs),
            'last_sent' => !empty($logs) ? $logs[0]->sent_date : null,
            'success_rate' => $this->calculate_email_success_rate($logs),
            'monthly_stats' => $this->get_monthly_email_stats($logs)
        ];
    }

    /**
     * Berechnet die Erfolgsrate der E-Mail-Zustellung
     *
     * @param array $logs E-Mail-Logs
     * @return float
     */
    private function calculate_email_success_rate($logs)
    {
        if (empty($logs)) {
            return 0;
        }

        $successful = array_filter($logs, function ($log) {
            return $log->status === 'sent';
        });

        return (count($successful) / count($logs)) * 100;
    }

    /**
     * Erstellt monatliche E-Mail-Statistiken
     *
     * @param array $logs E-Mail-Logs
     * @return array
     */
    private function get_monthly_email_stats($logs)
    {
        $stats = [];

        foreach ($logs as $log) {
            $month = date('Y-m', strtotime($log->sent_date));

            if (!isset($stats[$month])) {
                $stats[$month] = [
                    'sent' => 0,
                    'success' => 0,
                    'failure' => 0
                ];
            }

            $stats[$month]['sent']++;
            if ($log->status === 'sent') {
                $stats[$month]['success']++;
            } else {
                $stats[$month]['failure']++;
            }
        }

        return $stats;
    }

    /**
     * Exportiert Statistiken als CSV
     */
    public function export_statistics()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('Keine Berechtigung zum Exportieren von Statistiken.', 'newcustomer-discount'));
        }

        $stats = [
            'customers' => $this->customer_tracker->get_statistics(),
            'coupons' => $this->get_coupon_statistics(),
            'emails' => $this->get_email_statistics()
        ];

        $filename = 'newcustomer-discount-stats-' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        // CSV Header
        fputcsv($output, [
            __('Kategorie', 'newcustomer-discount'),
            __('Metrik', 'newcustomer-discount'),
            __('Wert', 'newcustomer-discount')
        ]);

        // Kunden Statistiken
        foreach ($stats['customers'] as $key => $value) {
            fputcsv($output, ['Kunden', $key, $value]);
        }

        // Gutschein Statistiken
        foreach ($stats['coupons'] as $key => $value) {
            fputcsv($output, ['Gutscheine', $key, $value]);
        }

        // E-Mail Statistiken
        foreach ($stats['emails'] as $key => $value) {
            if (!is_array($value)) {
                fputcsv($output, ['E-Mails', $key, $value]);
            }
        }

        fclose($output);
        exit;
    }

    /**
     * Erstellt einen Performance-Bericht
     * 
     * @return array
     */
    private function generate_performance_report()
    {
        $report = [
            'conversion_rate' => $this->calculate_conversion_rate(),
            'avg_order_value' => $this->calculate_average_order_value(),
            'roi' => $this->calculate_roi(),
            'trends' => $this->analyze_trends()
        ];

        return $report;
    }

    /**
     * Berechnet die Konversionsrate
     * 
     * @return float
     */
    private function calculate_conversion_rate()
    {
        $stats = $this->get_coupon_statistics();

        if ($stats['total'] === 0) {
            return 0;
        }

        return ($stats['used'] / $stats['total']) * 100;
    }

    /**
     * Berechnet den durchschnittlichen Bestellwert von Bestellungen die tatsächlich einen Gutschein verwendet haben
     * 
     * @return float
     */
    private function calculate_average_order_value()
    {
        global $wpdb;
        $tracking_table = NCD_Customer_Tracker::get_table_name();
    
        if (WP_DEBUG) {
            error_log('======= Starting average order value calculation =======');
            error_log('Tracking table: ' . $tracking_table);
            
            // Log the tracking table contents
            $tracking_contents = $wpdb->get_results("SELECT * FROM {$tracking_table}");
            error_log('Tracking table contents: ' . print_r($tracking_contents, true));
            
            // Log orders with used coupons
            $used_coupons = $wpdb->get_results("
                SELECT * FROM {$wpdb->prefix}wc_orders_meta 
                WHERE meta_key = '_used_coupon_code'
            ");
            error_log('Orders with used coupons: ' . print_r($used_coupons, true));
        }
    
        $results = $wpdb->get_row("
            SELECT COUNT(*) as order_count, COALESCE(AVG(o.total_amount), 0) as avg_total
            FROM {$wpdb->prefix}wc_orders o
            JOIN {$wpdb->prefix}wc_orders_meta om ON o.id = om.order_id
            JOIN {$tracking_table} t ON t.coupon_code = om.meta_value
            WHERE o.type = 'shop_order'
            AND o.status IN ('wc-completed', 'wc-processing')
            AND om.meta_key = '_used_coupon_code'
            AND t.status = 'used'
        ");
    
        if (WP_DEBUG) {
            error_log('Query executed: ' . $wpdb->last_query);
            error_log('Results: ' . print_r($results, true));
            if ($wpdb->last_error) {
                error_log('Database error: ' . $wpdb->last_error);
            }
            error_log('======= End average order value calculation =======');
        }
    
        if (!$results || $results->order_count == 0) {
            return 0;
        }
    
        return floatval($results->avg_total);
    }

    /**
     * Berechnet den ROI
     * 
     * @return float
     */
    private function calculate_roi()
    {
        $stats = $this->get_coupon_statistics();
        $avg_order_value = $this->calculate_average_order_value();

        if ($stats['total'] === 0) {
            return 0;
        }

        $total_discount_value = $stats['total_amount'];
        $total_order_value = $stats['used'] * $avg_order_value;

        if ($total_discount_value === 0) {
            return 0;
        }

        return (($total_order_value - $total_discount_value) / $total_discount_value) * 100;
    }

    /**
     * Analysiert Trends
     * 
     * @return array
     */
    private function analyze_trends()
    {
        $monthly_stats = $this->get_monthly_email_stats($this->email_sender->get_email_logs());
        $trends = [
            'email_trend' => $this->calculate_trend($monthly_stats, 'sent'),
            'conversion_trend' => $this->calculate_conversion_trend(),
            'recommendations' => $this->generate_recommendations()
        ];

        return $trends;
    }

    /**
     * Berechnet einen Trend
     * 
     * @param array $data Datenpunkte
     * @param string $key Zu analysierender Schlüssel
     * @return float
     */
    private function calculate_trend($data, $key)
    {
        if (count($data) < 2) {
            return 0;
        }

        $points = array_map(function ($month, $stats) use ($key) {
            return [
                'x' => strtotime($month),
                'y' => $stats[$key]
            ];
        }, array_keys($data), array_values($data));

        // Lineare Regression
        $n = count($points);
        $sum_x = array_sum(array_column($points, 'x'));
        $sum_y = array_sum(array_column($points, 'y'));
        $sum_xy = array_sum(array_map(function ($point) {
            return $point['x'] * $point['y'];
        }, $points));
        $sum_xx = array_sum(array_map(function ($point) {
            return $point['x'] * $point['x'];
        }, $points));

        $slope = ($n * $sum_xy - $sum_x * $sum_y) / ($n * $sum_xx - $sum_x * $sum_x);

        return $slope;
    }

    /**
     * Berechnet den Konversionstrend
     * 
     * @return float
     */
    private function calculate_conversion_trend()
    {
        // Implementierung der Trendberechnung für Konversionen
        return 0;
    }

    /**
     * Generiert Empfehlungen basierend auf den Statistiken
     * 
     * @return array
     */
    private function generate_recommendations()
    {
        $recommendations = [];
        $stats = $this->get_coupon_statistics();
        $conversion_rate = $this->calculate_conversion_rate();

        if ($conversion_rate < 10) {
            $recommendations[] = __('Die Konversionsrate ist niedrig. Erwägen Sie eine Erhöhung des Rabatts oder eine Verlängerung der Gültigkeitsdauer.', 'newcustomer-discount');
        }

        if ($stats['expired'] > $stats['used']) {
            $recommendations[] = __('Viele Gutscheine laufen ungenutzt ab. Überdenken Sie die Gültigkeitsdauer oder senden Sie Erinnerungen.', 'newcustomer-discount');
        }

        return $recommendations;
    }

    /**
     * Setzt alle erstellten Gutscheine zurück
     *
     * @return int Anzahl der gelöschten Gutscheine
     */
    private function reset_coupons()
    {
        global $wpdb;
        $count = 0;

        // Für WooCommerce 8.0+ HPOS Tabellen
        if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}wc_coupons'") === "{$wpdb->prefix}wc_coupons") {
            // Hole alle vom Plugin erstellten Gutscheine
            $coupon_ids = $wpdb->get_col("
                SELECT coupon_id 
                FROM {$wpdb->prefix}wc_coupons c
                JOIN {$wpdb->prefix}wc_coupon_meta cm ON c.id = cm.coupon_id
                WHERE cm.meta_key = '_ncd_generated'
                AND cm.meta_value = 'yes'
            ");

            foreach ($coupon_ids as $coupon_id) {
                // Lösche Meta-Daten
                $wpdb->query($wpdb->prepare("
                    DELETE FROM {$wpdb->prefix}wc_coupon_meta
                    WHERE coupon_id = %d
                ", $coupon_id));

                // Lösche Gutschein
                $wpdb->query($wpdb->prepare("
                    DELETE FROM {$wpdb->prefix}wc_coupons
                    WHERE id = %d
                ", $coupon_id));
                $count++;
            }
        } else {
            // Alte WordPress Tabellenstruktur
            $coupon_ids = $wpdb->get_col("
                SELECT ID 
                FROM {$wpdb->posts} p
                JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                WHERE p.post_type = 'shop_coupon'
                AND pm.meta_key = '_ncd_generated'
                AND pm.meta_value = 'yes'
            ");

            foreach ($coupon_ids as $coupon_id) {
                $wpdb->query($wpdb->prepare("
                    DELETE FROM {$wpdb->postmeta}
                    WHERE post_id = %d
                ", $coupon_id));
                wp_delete_post($coupon_id, true);
                $count++;
            }
        }

        // Lösche Transients
        $wpdb->query("
            DELETE FROM {$wpdb->options}
            WHERE option_name LIKE '%_transient_wc_coupon_%'
            OR option_name LIKE '%_transient_timeout_wc_coupon_%'
        ");

        return $count;
    }

    /**
     * Setzt alle Neukunden-Tracking-Daten zurück
     *
     * @return int Anzahl der gelöschten Einträge
     */
    private function reset_customers()
    {
        global $wpdb;

        // Zähle Einträge vor dem Löschen
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}customer_discount_tracking");

        // Lösche Tracking-Tabelle
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}customer_discount_tracking");

        // Lösche Email-Log-Tabelle
        if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}ncd_email_log'") === "{$wpdb->prefix}ncd_email_log") {
            $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}ncd_email_log");
        }

        // Lösche zugehörige Meta-Daten in WooCommerce HPOS Tabellen
        if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}wc_orders_meta'") === "{$wpdb->prefix}wc_orders_meta") {
            $wpdb->query("
                DELETE FROM {$wpdb->prefix}wc_orders_meta
                WHERE meta_key LIKE '_ncd_%'
            ");
        }

        // Lösche alte Meta-Daten
        $wpdb->query("
            DELETE FROM {$wpdb->postmeta}
            WHERE meta_key LIKE '_ncd_%'
        ");

        // Lösche Transients
        $wpdb->query("
            DELETE FROM {$wpdb->options}
            WHERE option_name LIKE '%_transient_ncd_%'
            OR option_name LIKE '%_transient_timeout_ncd_%'
        ");

        // Cache leeren
        wp_cache_flush();

        return (int) $count;
    }

    /**
     * Initialisiert die AJAX Handler
     */
    private function init_ajax_handlers() {
        add_action('wp_ajax_ncd_preview_template', [$this, 'ajax_preview_template']);
        add_action('wp_ajax_ncd_reset_template', [$this, 'ajax_reset_template']);
    }

    /**
     * Rendert die Templates-Verwaltungsseite
     */
    public function render_templates_page() {
        if (isset($_POST['save_template']) && isset($_POST['template_content'])) {
            check_admin_referer('ncd_save_template', 'ncd_template_nonce');
            
            $template_content = wp_unslash($_POST['template_content']);
            $result = $this->email_sender->save_template($template_content);
            
            if (is_wp_error($result)) {
                add_settings_error(
                    'ncd_template',
                    'template_error',
                    $result->get_error_message(),
                    'error'
                );
            } else {
                add_settings_error(
                    'ncd_template',
                    'template_updated',
                    __('Template erfolgreich gespeichert.', 'newcustomer-discount'),
                    'success'
                );
            }
        }

        // Hole aktuelles Template
        $current_template = get_option('ncd_email_template', '');
        if (empty($current_template)) {
            $current_template = $this->email_sender->load_predefined_template();
            if (is_wp_error($current_template)) {
                $current_template = '';
            }
        }

        // Hole verfügbare Variablen
        $available_variables = $this->email_sender->get_available_variables();

        // Template laden
        include NCD_PLUGIN_DIR . 'templates/admin/templates-page.php';
    }

    /**
     * AJAX Handler für Template Preview
     */
    public function ajax_preview_template() {
        check_ajax_referer('ncd_save_template', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Keine Berechtigung.', 'newcustomer-discount')]);
        }

        $template = isset($_POST['template']) ? wp_unslash($_POST['template']) : '';
        
        // Validiere Template
        $validation = $this->email_sender->validate_template($template);
        if (is_wp_error($validation)) {
            wp_send_json_error(['message' => $validation->get_error_message()]);
        }

        // Parse Template mit Test-Daten
        $test_data = [
            'coupon_code' => 'TESTCODE123',
            'expiry_date' => date('Y-m-d', strtotime('+30 days'))
        ];

        $html = $this->email_sender->parse_template($template, $test_data);
        wp_send_json_success(['html' => $html]);
    }

    /**
     * AJAX Handler für Template Reset
     */
    public function ajax_reset_template() {
        check_ajax_referer('ncd_save_template', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Keine Berechtigung.', 'newcustomer-discount')]);
        }

        $default_template = $this->email_sender->load_predefined_template();
        if (is_wp_error($default_template)) {
            wp_send_json_error(['message' => $default_template->get_error_message()]);
        }

        wp_send_json_success(['template' => $default_template]);
    }
}