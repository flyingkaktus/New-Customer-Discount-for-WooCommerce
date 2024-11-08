<?php
/**
* Admin Statistics Class
*
* Verwaltet die Statistik-Funktionalität im WordPress Admin-Bereich
*
* @package NewCustomerDiscount
* @subpackage Admin\Statistics
* @since 0.0.1
*/

if (!defined('ABSPATH')) {
   exit;
}

class NCD_Admin_Statistics extends NCD_Admin_Base {

   /**
    * Constructor
    */
   public function __construct() {
       parent::__construct();
       add_action('admin_post_ncd_export_statistics', [$this, 'handle_export_statistics']);
   }

   /**
    * Rendert die Statistik-Seite
    */
    public function render_page() {
        if (!$this->check_admin_permissions()) {
            return;
        }
    
        $stats = [
            'customers' => $this->customer_tracker->get_statistics(),
            'coupons' => $this->get_coupon_statistics(),
            'emails' => $this->get_email_statistics()
        ];
    
        include NCD_PLUGIN_DIR . 'templates/admin/statistics-page.php';
    }

   /**
    * Holt Gutschein-Statistiken
    *
    * @return array
    */
   private function get_coupon_statistics() {
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

       $stats['avg_order_value'] = $this->calculate_average_order_value();

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
   private function get_email_statistics() {
       $logs = $this->email_sender->get_email_logs();

       return [
           'total_sent' => count($logs),
           'last_sent' => !empty($logs) ? $logs[0]->sent_date : null,
           'success_rate' => $this->calculate_email_success_rate($logs),
           'monthly_stats' => $this->get_monthly_email_stats($logs)
       ];
   }

   /**
    * Berechnet die E-Mail-Erfolgsrate
    *
    * @param array $logs
    * @return float
    */
   private function calculate_email_success_rate($logs) {
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
    * @param array $logs
    * @return array
    */
   private function get_monthly_email_stats($logs) {
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
    * Berechnet den durchschnittlichen Bestellwert
    *
    * @return float
    */
   private function calculate_average_order_value() {
       global $wpdb;
       
       if (WP_DEBUG) {
           error_log('======= Starting average order value calculation =======');
       }

       $tracking_table = $this->customer_tracker->get_table_name();

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
   private function calculate_roi() {
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
   private function analyze_trends() {
       $monthly_stats = $this->get_monthly_email_stats($this->email_sender->get_email_logs());
       
       return [
           'email_trend' => $this->calculate_trend($monthly_stats, 'sent'),
           'conversion_trend' => $this->calculate_conversion_trend(),
           'recommendations' => $this->generate_recommendations()
       ];
   }

   /**
    * Berechnet einen Trend
    *
    * @param array $data
    * @param string $key
    * @return float
    */
   private function calculate_trend($data, $key) {
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

       return ($n * $sum_xy - $sum_x * $sum_y) / ($n * $sum_xx - $sum_x * $sum_x);
   }

   /**
    * Berechnet den Konversionstrend
    *
    * @return float
    */
   private function calculate_conversion_trend() {
       $stats = $this->get_coupon_statistics();
       return $stats['total'] > 0 ? ($stats['used'] / $stats['total']) * 100 : 0;
   }

   /**
    * Generiert Empfehlungen basierend auf den Statistiken
    *
    * @return array
    */
   private function generate_recommendations() {
       $recommendations = [];
       $stats = $this->get_coupon_statistics();
       $conversion_rate = $this->calculate_conversion_trend();

       if ($conversion_rate < 10) {
           $recommendations[] = __('Die Konversionsrate ist niedrig. Erwägen Sie eine Erhöhung des Rabatts oder eine Verlängerung der Gültigkeitsdauer.', 'newcustomer-discount');
       }

       if ($stats['expired'] > $stats['used']) {
           $recommendations[] = __('Viele Gutscheine laufen ungenutzt ab. Überdenken Sie die Gültigkeitsdauer oder senden Sie Erinnerungen.', 'newcustomer-discount');
       }

       return $recommendations;
   }

   /**
    * Exportiert Statistiken als CSV
    */
   public function handle_export_statistics() {
       if (!$this->check_admin_permissions()) {
           return;
       }

       check_admin_referer('ncd_export_statistics', 'ncd_export_nonce');

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

       // Daten schreiben
       foreach ($stats as $category => $metrics) {
           foreach ($metrics as $key => $value) {
               if (!is_array($value)) {
                   fputcsv($output, [$category, $key, $value]);
               }
           }
       }

       fclose($output);
       exit;
   }
}