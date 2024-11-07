<?php
/**
 * Email Sender Class
 *
 * Verwaltet das Erstellen und Versenden von E-Mails
 *
 * @package NewCustomerDiscount
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class NCD_Email_Sender {
    /**
     * Template Verzeichnis
     *
     * @var string
     */
    private $template_directory;

    /**
     * Default E-Mail-Einstellungen
     *
     * @var array
     */
    private $default_settings = [
        'from_name' => '',
        'from_email' => '',
        'subject' => 'Ihr Rabattgutschein',
        'template' => 'modern'
    ];

    /**
     * Default E-Mail-Texte
     *
     * @var array
     */
    private $default_email_texts = [
        'heading' => 'Ihr exklusiver Willkommensrabatt',
        'greeting' => 'Sehr geehrter Kunde,',
        'intro' => 'vielen Dank für Ihr Interesse an {shop_name}. Als besonderes Willkommensgeschenk haben wir einen exklusiven Rabattgutschein für Sie erstellt.',
        'coupon_info' => 'Besuchen Sie unseren Shop und geben Sie den Gutscheincode beim Checkout ein.',
        'footer' => 'Dies ist eine automatisch generierte E-Mail.'
    ];

    /**
     * Constructor
     */
    public function __construct() {
        $this->template_directory = NCD_PLUGIN_DIR . 'templates/email/base/';
        $this->default_settings['from_name'] = get_bloginfo('name');
        $this->default_settings['from_email'] = get_option('admin_email');

        add_filter('wp_mail_content_type', [$this, 'set_html_content_type']);

        if (!get_option('ncd_email_texts')) {
            add_option('ncd_email_texts', $this->default_email_texts);
        }
    }

    /**
     * Gibt verfügbare Templates zurück
     *
     * @return array
     */
    public function get_template_list() {
        return [
            'modern' => [
                'file' => $this->template_directory . 'modern.php',
                'preview' => NCD_PLUGIN_URL . 'templates/email/previews/modern.jpg'
            ],
            'classic' => [
                'file' => $this->template_directory . 'classic.php',
                'preview' => NCD_PLUGIN_URL . 'templates/email/previews/classic.jpg'
            ],
            'minimal' => [
                'file' => $this->template_directory . 'minimal.php',
                'preview' => NCD_PLUGIN_URL . 'templates/email/previews/minimal.jpg'
            ]
        ];
    }

    /**
     * Lädt ein Template
     *
     * @param string $template_id Template ID
     * @return array Template-Daten
     */
    public function load_template($template_id = 'modern') {
        $templates = $this->get_template_list();
        
        if (!isset($templates[$template_id])) {
            error_log("Template '$template_id' nicht gefunden, verwende 'modern'");
            $template_id = 'modern';
        }
        
        $template_file = $templates[$template_id]['file'];
        if (!file_exists($template_file)) {
            error_log("Template-Datei nicht gefunden: $template_file");
            
            if ($template_id === 'modern') {
                throw new Exception(sprintf(
                    __('Basis-Template-Dateien fehlen. Bitte stellen Sie sicher, dass %s existiert.', 'newcustomer-discount'),
                    'templates/email/base/modern.php'
                ));
            }
            
            return $this->load_template('modern');
        }
        
        $template = include $template_file;
        // Template-spezifische Einstellungen laden
        $saved_settings = get_option('ncd_template_' . $template_id . '_settings', []);
        // Zusammenführen mit Standard-Einstellungen des Templates
        $template['settings'] = wp_parse_args($saved_settings, $template['settings']);
        
        if (WP_DEBUG) {
            error_log('Template-spezifische Einstellungen für ' . $template_id . ': ' . print_r($template['settings'], true));
        }
        
        return $template;
    }

    /**
     * Sendet eine Rabatt-E-Mail
     *
     * @param string $email Empfänger E-Mail
     * @param array $data E-Mail-Daten
     * @param array $settings Optionale E-Mail-Einstellungen
     * @return bool|WP_Error
     */
    public function send_discount_email($email, $data, $settings = []) {
        try {
            if (!is_email($email)) {
                throw new Exception(__('Ungültige E-Mail-Adresse', 'newcustomer-discount'));
            }
    
            $settings = wp_parse_args($settings, $this->default_settings);
            
            // Explizit das aktive Template laden
            $template_id = get_option('ncd_active_template', 'modern');
            
            // Debug-Logging hinzufügen
            if (WP_DEBUG) {
                error_log('Sending email with template: ' . $template_id);
            }
    
            // Template rendern
            $content = $this->render_template($template_id, $data);
            if (is_wp_error($content)) {
                throw new Exception($content->get_error_message());
            }
    
            $headers = $this->generate_email_headers($settings);
            $subject = get_option('ncd_email_subject', $settings['subject']);
    
            $sent = wp_mail($email, $subject, $content, $headers);
    
            if (!$sent) {
                throw new Exception(__('E-Mail konnte nicht gesendet werden', 'newcustomer-discount'));
            }
    
            $this->log_email_sent($email, $data, $template_id); // Template-ID zum Logging hinzufügen
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
     * Rendert ein Template
     *
     * @param string $template_id Template ID
     * @param array $data Template-Daten
     * @return string Gerendertes Template
     */
    public function render_template($template_id, $data) {
        $template = $this->load_template($template_id);
        
        $styles = strtr($template['styles'], [
            'var(--primary-color)' => $template['settings']['primary_color'],
            'var(--secondary-color)' => $template['settings']['secondary_color'],
            'var(--text-color)' => $template['settings']['text_color'],
            'var(--background-color)' => $template['settings']['background_color'],
            'var(--font-family)' => $template['settings']['font_family']
        ]);

        $html = "<style>{$styles}</style>" . $template['html'];
        
        return $this->parse_template($html, $data);
    }

    /**
     * Fügt Styles inline in das HTML ein mittels Emogrifier
     *
     * @param string $html Das Template HTML
     * @param string $styles Die CSS Styles
     * @return string HTML mit inline Styles
     * @throws Exception Wenn die Konvertierung fehlschlägt
     */
    private function inline_styles($html, $styles) {
        try {
            // Prüfe ob Emogrifier verfügbar ist
            if (!class_exists('\\Pelago\\Emogrifier\\CssInliner')) {
                throw new Exception('Emogrifier ist nicht verfügbar');
            }

            // Bereite das HTML vor (stelle sicher, dass DOCTYPE und Meta-Tags erhalten bleiben)
            $emogrifier = \Pelago\Emogrifier\CssInliner::fromHtml($html)
                ->inlineCss($styles);

            // Disabling style block removal to preserve media queries
            $emogrifier->addExcludedSelector('style');

            // CSS-Klassen beibehalten für eventuelle JavaScript-Funktionalität
            $emogrifier->keepOriginalStyles();

            // Optimierungen für E-Mail-Clients
            $domDocument = $emogrifier->getDomDocument();
            $finalHtml = $emogrifier->render();

            // Füge die ursprünglichen Styles am Ende hinzu (für E-Mail-Clients die CSS unterstützen)
            $finalHtml = str_replace('</head>', "<style>{$styles}</style></head>", $finalHtml);

            // Debug-Logging
            if (WP_DEBUG) {
                error_log('Emogrifier conversion completed');
                error_log('Original styles preserved');
                error_log('Final HTML length: ' . strlen($finalHtml));
            }

            return $finalHtml;

        } catch (Exception $e) {
            if (WP_DEBUG) {
                error_log('Emogrifier error: ' . $e->getMessage());
            }
            // Fallback: Wenn Emogrifier fehlschlägt, füge Styles einfach im <style> Tag hinzu
            return "<style>{$styles}</style>" . $html;
        }
    }

    /**
     * Parst das Template und ersetzt Variablen
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
     * Generiert E-Mail-Header
     *
     * @param array $settings E-Mail-Einstellungen
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
     * Setzt den Content-Type auf HTML
     *
     * @return string
     */
    public function set_html_content_type() {
        return 'text/html';
    }

    /**
     * Sendet eine Test-E-Mail
     *
     * @param string $email Test-Empfänger
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
     * Speichert Template-Einstellungen
     *
     * @param string $template_id Template ID
     * @param array $settings Einstellungen
     * @return bool
     */
    public function save_template_settings($template_id, $settings) {
        $option_name = 'ncd_template_' . $template_id . '_settings';
        // Bestehende Einstellungen laden
        $existing_settings = get_option($option_name, []);
        // Neue Einstellungen mit bestehenden zusammenführen
        $settings = wp_parse_args($settings, $existing_settings);
        return update_option($option_name, $settings);
    }

    /**
     * Holt Template-Einstellungen
     *
     * @param string $template_id Template ID
     * @return array
     */
    public function get_template_settings($template_id) {
        $template = $this->load_template($template_id);
        return $template['settings'];
    }

    /**
     * Speichert E-Mail-Texte
     *
     * @param array $texts Die zu speichernden Texte
     * @return bool
     */
    public function save_email_texts($texts) {
        return update_option('ncd_email_texts', wp_parse_args($texts, $this->default_email_texts));
    }

    /**
     * Gibt die aktuellen E-Mail-Texte zurück
     *
     * @return array
     */
    public function get_email_texts() {
        return get_option('ncd_email_texts', $this->default_email_texts);
    }

    /**
     * Gibt die verfügbaren Template-Variablen zurück
     *
     * @return array
     */
    public function get_available_variables() {
        return [
            '{coupon_code}' => __('Der generierte Gutscheincode', 'newcustomer-discount'),
            '{shop_name}' => __('Name des Shops', 'newcustomer-discount'),
            '{discount_amount}' => __('Rabatthöhe in Prozent', 'newcustomer-discount'),
            '{expiry_date}' => __('Ablaufdatum des Gutscheins', 'newcustomer-discount'),
            '{shop_url}' => __('URL des Shops', 'newcustomer-discount'),
            '{logo_url}' => __('URL des Shop-Logos', 'newcustomer-discount'),
            '{current_year}' => __('Aktuelles Jahr', 'newcustomer-discount'),
            '{min_order_amount}' => __('Mindestbestellwert', 'newcustomer-discount'),
            '{currency_symbol}' => __('Währungssymbol', 'newcustomer-discount')
        ];
    }

    /**
     * Speichert E-Mail-Versand in der Datenbank
     *
     * @param string $email Empfänger E-Mail
     * @param array $data E-Mail-Daten
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
                'template_version' => $template_id // Template-ID speichern
            ],
            ['%s', '%s', '%s', '%s', '%s']
        );
    }

    /**
     * Erstellt die E-Mail-Log Tabelle
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
     * Gibt die E-Mail-Logs zurück
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
 * Bereinigt alte Log-Einträge
 *
 * @param int $days Alter in Tagen
 * @return int Anzahl der gelöschten Einträge
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
 * Loggt Fehler für Debugging
 *
 * @param string $message Fehlermeldung
 * @param array $context Zusätzliche Kontext-Informationen
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
 * Rendert eine Template-Vorschau
 *
 * @param string $template_id Template ID
 * @param array $settings Temporäre Einstellungen
 * @return string HTML der Vorschau
 */
public function render_preview($template_id, $settings = []) {
    try {
        // Lade das spezifische Template
        $template = $this->load_template($template_id);
        
        if (empty($template)) {
            throw new Exception('Template nicht gefunden');
        }

        // Wenn keine benutzerdefinierten Einstellungen, verwende die Template-Standardeinstellungen
        $settings = !empty($settings) ? $settings : $template['settings'];

        $test_data = [
            'coupon_code' => 'TESTCODE123',
            'expiry_date' => date('Y-m-d', strtotime('+30 days'))
        ];

        // Wende die Template-spezifischen Styles an
        $styles = strtr($template['styles'], [
            'var(--primary-color)' => $settings['primary_color'],
            'var(--secondary-color)' => $settings['secondary_color'],
            'var(--text-color)' => $settings['text_color'],
            'var(--background-color)' => $settings['background_color'],
            'var(--font-family)' => $settings['font_family']
        ]);

        // Kombiniere Styles und HTML
        $preview = "<style>{$styles}</style>" . $this->parse_template($template['html'], $test_data);
        
        return $preview;

    } catch (Exception $e) {
        if (WP_DEBUG) {
            error_log('Template preview error: ' . $e->getMessage());
        }
        return '<div class="error">Fehler beim Laden der Vorschau</div>';
    }
}
}