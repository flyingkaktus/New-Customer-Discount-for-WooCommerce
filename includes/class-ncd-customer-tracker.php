<?php
/**
 * Customer Tracker Class
 *
 * Verwaltet das Tracking von Neukunden und deren Rabatt-Status
 *
 * @package NewCustomerDiscount
 * @since 0.0.1
 */

if (!defined('ABSPATH')) {
    exit;
}

class NCD_Customer_Tracker
{
    /**
     * Name der Datenbank-Tabelle
     *
     * @var string
     */
    private static $table_name;

    /**
     * Gibt den Tabellennamen zurück
     *
     * @return string
     */
    public static function get_table_name()
    {
        if (self::$table_name === null) {
            global $wpdb;
            self::$table_name = $wpdb->prefix . 'customer_discount_tracking';
        }
        return self::$table_name;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('wp_scheduled_delete', [$this, 'cleanup_old_entries']);
    }

    /**
     * Plugin-Aktivierung
     *
     * @return void
     */
    public static function activate()
    {
        self::create_database_table();
        wp_schedule_event(time(), 'daily', 'cleanup_tracking_entries');
    }

    /**
     * Plugin-Deaktivierung
     *
     * @return void
     */
    public static function deactivate()
    {
        wp_clear_scheduled_hook('cleanup_tracking_entries');
    }

    /**
     * Erstellt die Tracking-Tabelle in der Datenbank
     *
     * @return void
     */
    private static function create_database_table()
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS " . self::get_table_name() . " (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            customer_email varchar(255) NOT NULL,
            customer_first_name varchar(255),
            customer_last_name varchar(255),
            discount_email_sent datetime DEFAULT NULL,
            coupon_code varchar(10),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            status enum('pending', 'sent', 'used', 'expired') DEFAULT 'pending',
            PRIMARY KEY (id),
            UNIQUE KEY customer_email (customer_email),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Prüft ob ein Kunde ein Neukunde ist
     *
     * @param string $email E-Mail-Adresse des Kunden
     * @param string $cutoff_date Optional. Stichtag für die Prüfung
     * @return bool
     */
    // In class-ncd-customer-tracker.php
    public function is_new_customer($email)
    {
        global $wpdb;

        $cutoff_date = get_option('ncd_cutoff_date', '2024-01-01');
        $max_orders = get_option('ncd_order_count', 0);

        $count = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*)
        FROM {$wpdb->prefix}posts as p
        JOIN {$wpdb->prefix}postmeta as pm ON p.ID = pm.post_id
        WHERE p.post_type = 'shop_order'
        AND p.post_date < %s
        AND pm.meta_key = '_billing_email'
        AND pm.meta_value = %s
    ", $cutoff_date, $email));

        return $count <= $max_orders;
    }

    /**
     * Fügt einen neuen Kunden zum Tracking hinzu
     *
     * @param string $email E-Mail-Adresse
     * @param string $first_name Vorname
     * @param string $last_name Nachname
     * @return int|false ID des Eintrags oder false bei Fehler
     */
    public function add_customer($email, $first_name = '', $last_name = '')
    {
        global $wpdb;

        try {
            $result = $wpdb->insert(
                self::get_table_name(),
                [
                    'customer_email' => $email,
                    'customer_first_name' => $first_name,
                    'customer_last_name' => $last_name,
                    'status' => 'pending'
                ],
                ['%s', '%s', '%s', '%s']
            );

            if ($result === false) {
                throw new Exception($wpdb->last_error);
            }

            return $wpdb->insert_id;
        } catch (Exception $e) {
            $this->log_error('Failed to add customer', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Aktualisiert den Status eines Kunden
     *
     * @param string $email E-Mail-Adresse
     * @param string $status Neuer Status
     * @param string $coupon_code Optional. Gutscheincode
     * @return bool
     */
    public function update_customer_status($email, $status, $coupon_code = '')
    {
        global $wpdb;
    
        $data = [
            'status' => $status,
            'updated_at' => current_time('mysql')
        ];
    
        if ($status === 'sent' && !empty($coupon_code)) {
            $data['discount_email_sent'] = current_time('mysql');
            $data['coupon_code'] = $coupon_code;
        }
    
        // Debug logging
        if (WP_DEBUG) {
            error_log('Updating customer status:');
            error_log('Email: ' . $email);
            error_log('Status: ' . $status);
            error_log('Gutschein: ' . $coupon_code);
            error_log('Data: ' . print_r($data, true));
        }
    
        // Prüfen ob der Kunde bereits existiert
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM " . self::get_table_name() . " WHERE customer_email = %s",
            $email
        ));
    
        if (!$exists) {
            // Kunde existiert nicht, füge ihn hinzu
            $data['customer_email'] = $email;
            $result = $wpdb->insert(
                self::get_table_name(),
                $data,
                ['%s', '%s', '%s', '%s']
            );
        } else {
            // Kunde existiert, update
            $result = $wpdb->update(
                self::get_table_name(),
                $data,
                ['customer_email' => $email],
                ['%s', '%s', '%s', '%s'],
                ['%s']
            );
        }
    
        if (WP_DEBUG) {
            error_log('Update result: ' . ($result !== false ? 'success' : 'failed'));
            if ($result === false) {
                error_log('Database error: ' . $wpdb->last_error);
            }
        }
    
        return $result !== false;
    }

    /**
     * Holt Kundeninformationen
     *
     * @param array $args Query-Argumente
     * @return array
     */
    public function get_customers($args = [])
    {
        global $wpdb;

        $defaults = [
            'days' => 30,
            'status' => '',
            'only_new' => false,
            'orderby' => 'date_created',
            'order' => 'DESC',
            'limit' => 50,
            'offset' => 0
        ];

        $args = wp_parse_args($args, $defaults);
        $tracking_table = self::get_table_name();

        // Neue WooCommerce 8.0+ Tabellenstruktur
        if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}wc_orders'") === "{$wpdb->prefix}wc_orders") {
            $query = $wpdb->prepare("
                SELECT 
                    o.id as order_id,
                    o.billing_email as customer_email,
                    SUBSTRING_INDEX(SUBSTRING_INDEX(om.meta_value, ' ', 1), ' ', -1) as customer_first_name,
                    SUBSTRING_INDEX(SUBSTRING_INDEX(om.meta_value, ' ', 2), ' ', -1) as customer_last_name,
                    o.date_created_gmt as created_at,
                    t.discount_email_sent,
                    t.coupon_code
                FROM {$wpdb->prefix}wc_orders o
                LEFT JOIN {$wpdb->prefix}wc_orders_meta om ON o.id = om.order_id 
                    AND om.meta_key = '_billing_address_index'
                LEFT JOIN {$tracking_table} t ON o.billing_email = t.customer_email
                WHERE o.type = 'shop_order'
                AND o.status IN ('wc-completed', 'wc-processing')
                AND o.date_created_gmt >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL %d DAY)
                ORDER BY o.date_created_gmt DESC
                LIMIT %d OFFSET %d
            ", $args['days'], $args['limit'], $args['offset']);
        } else {
            // Alte WordPress Tabellenstruktur
            $query = $wpdb->prepare("
                SELECT 
                    o.ID as order_id,
                    MAX(CASE WHEN pm.meta_key = '_billing_email' THEN pm.meta_value END) as customer_email,
                    MAX(CASE WHEN pm.meta_key = '_billing_first_name' THEN pm.meta_value END) as customer_first_name,
                    MAX(CASE WHEN pm.meta_key = '_billing_last_name' THEN pm.meta_value END) as customer_last_name,
                    o.post_date as created_at,
                    t.discount_email_sent,
                    t.coupon_code
                FROM {$wpdb->prefix}posts o
                JOIN {$wpdb->prefix}postmeta pm ON o.ID = pm.post_id
                LEFT JOIN {$tracking_table} t ON t.customer_email = (
                    SELECT meta_value 
                    FROM {$wpdb->prefix}postmeta 
                    WHERE post_id = o.ID 
                    AND meta_key = '_billing_email' 
                    LIMIT 1
                )
                WHERE o.post_type = 'shop_order'
                AND o.post_status IN ('wc-completed', 'wc-processing')
                AND o.post_date >= DATE_SUB(NOW(), INTERVAL %d DAY)
                AND pm.meta_key IN ('_billing_email', '_billing_first_name', '_billing_last_name')
                GROUP BY o.ID
                ORDER BY o.post_date DESC
                LIMIT %d OFFSET %d
            ", $args['days'], $args['limit'], $args['offset']);
        }

        $orders = $wpdb->get_results($query, ARRAY_A);

        // Debug-Logging
        if (WP_DEBUG) {
            error_log('Customer Query: ' . $wpdb->last_query);
            error_log('Found customers: ' . print_r($orders, true));
        }

        return $orders;
    }

    /**
     * Bereinigt alte Einträge
     *
     * @return int Anzahl der gelöschten Einträge
     */
    public function cleanup_old_entries()
    {
        global $wpdb;

        return $wpdb->query($wpdb->prepare("
            DELETE FROM " . self::get_table_name() . "
            WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)
            AND (status = 'used' OR status = 'expired')
        ", 1460)); // Einträge älter als 4 Jahr
    }

    /**
     * Loggt Fehler für Debugging
     *
     * @param string $message Fehlermeldung
     * @param array $context Zusätzliche Kontext-Informationen
     * @return void
     */
    private function log_error($message, $context = [])
    {
        if (WP_DEBUG) {
            error_log(sprintf(
                '[NewCustomerDiscount] Customer Tracker Error: %s | Context: %s',
                $message,
                json_encode($context)
            ));
        }
    }

    /**
     * Gibt die Tabellen-Statistiken zurück
     *
     * @return array
     */
    public function get_statistics()
    {
        global $wpdb;

        // Prüfe ob Tabelle existiert
        $table_exists = $wpdb->get_var(
            "SHOW TABLES LIKE '" . self::get_table_name() . "'"
        ) === self::get_table_name();

        if (!$table_exists) {
            return [
                'total' => 0,
                'pending' => 0,
                'sent' => 0,
                'used' => 0,
                'expired' => 0
            ];
        }

        return [
            'total' => $wpdb->get_var("SELECT COUNT(*) FROM " . self::get_table_name()),
            'pending' => $wpdb->get_var("SELECT COUNT(*) FROM " . self::get_table_name() . " WHERE status = 'pending'"),
            'sent' => $wpdb->get_var("SELECT COUNT(*) FROM " . self::get_table_name() . " WHERE status = 'sent'"),
            'used' => $wpdb->get_var("SELECT COUNT(*) FROM " . self::get_table_name() . " WHERE status = 'used'"),
            'expired' => $wpdb->get_var("SELECT COUNT(*) FROM " . self::get_table_name() . " WHERE status = 'expired'")
        ];
    }
}