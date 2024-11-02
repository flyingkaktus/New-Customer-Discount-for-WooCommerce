<?php
/**
* Admin Main Class
*
* Hauptklasse für den WordPress Admin-Bereich
*
* @package NewCustomerDiscount
* @subpackage Admin
* @since 0.0.1
*/

if (!defined('ABSPATH')) {
   exit;
}

class NCD_Admin {
   /**
    * Menu Manager Instanz
    *
    * @var NCD_Admin_Menu
    */
   private $menu;

   /**
    * Admin Seiten
    *
    * @var array
    */
   private $pages = [];

   /**
    * AJAX Handler
    *
    * @var NCD_Admin_Ajax
    */
   private $ajax;

   /**
    * Constructor
    */
   public function __construct() {
       if (WP_DEBUG) {
           error_log('Initializing NCD Admin');
       }

       try {
           $this->init_components();
           $this->init_hooks();

           if (WP_DEBUG) {
               error_log('NCD Admin initialized successfully');
           }
       } catch (Exception $e) {
           if (WP_DEBUG) {
               error_log('NCD Admin initialization failed: ' . $e->getMessage());
           }
       }
   }

   /**
    * Initialisiert die Admin-Komponenten
    */
   private function init_components() {
       // Menu Manager initialisieren
       $this->menu = new NCD_Admin_Menu();

       // Tab Manager initialisieren
       $this->tab_manager = new NCD_Admin_Tab_Manager();
        
       // Admin Seiten initialisieren
       $this->init_pages();

       // AJAX Handler initialisieren
       $this->ajax = new NCD_Admin_Ajax();

       if (WP_DEBUG) {
           error_log('Admin components initialized');
       }
   }

   /**
    * Initialisiert die Admin-Seiten
    */
    private function init_pages() {
        try {
            // Seiten erstellen
            $pages = [
                'customers' => new NCD_Admin_Customers(),
                'templates' => new NCD_Admin_Templates(),
                'settings' => new NCD_Admin_Settings(),
                'statistics' => new NCD_Admin_Statistics()
            ];
    
            // Seiten zum Menu Manager hinzufügen
            foreach ($pages as $key => $page) {
                $this->pages[$key] = $page;
                $this->menu->add_page($key, $page);
            }
    
            if (WP_DEBUG) {
                error_log('Admin pages initialized: ' . implode(', ', array_keys($this->pages)));
            }
        } catch (Exception $e) {
            if (WP_DEBUG) {
                error_log('Failed to initialize admin pages: ' . $e->getMessage());
            }
            throw $e;
        }
    }

   /**
    * Initialisiert die WordPress Hooks
    */
   private function init_hooks() {
       // Menu Registration
       add_action('admin_menu', [$this->menu, 'register_menus']);

       // Asset Loading
       add_action('admin_enqueue_scripts', [$this, 'enqueue_common_assets']);

       if (WP_DEBUG) {
           error_log('Admin hooks initialized');
       }
   }

    /**
     * Gibt den Tab Manager zurück
     *
     * @return NCD_Admin_Tab_Manager
     */
    public function get_tab_manager() {
        return $this->tab_manager;
    }

    /**
     * Lädt gemeinsame Admin Assets
     *
     * @param string $hook Der aktuelle Admin-Seiten-Hook
     */
    public function enqueue_common_assets($hook) {
        // Nur auf Plugin-Seiten laden
        if (strpos($hook, 'new-customers') === false) {
            return;
        }

        // Debug-Ausgabe
        if (WP_DEBUG) {
            error_log('Loading admin assets on hook: ' . $hook);
            error_log('CSS URL: ' . NCD_PLUGIN_URL . 'assets/css/admin.css');
        }

        // Definiere Version für Assets
        $asset_version = WP_DEBUG ? time() : NCD_VERSION;

        // Base styles immer laden
        wp_enqueue_style(
            'ncd-admin-base', 
            NCD_ASSETS_URL . 'css/admin/base.css',
            [],
            $asset_version
        );

        // Tab styles immer laden
        wp_enqueue_style(
            'ncd-admin-tabs', 
            NCD_ASSETS_URL . 'css/admin/tabs.css',
            ['ncd-admin-base'],
            $asset_version
        );

        // Seitenspezifische Styles laden
        if (isset($_GET['page'])) {
            switch ($_GET['page']) {
                case 'new-customers':
                    wp_enqueue_style(
                        'ncd-admin-customers', 
                        NCD_ASSETS_URL . 'css/admin/customers.css',
                        ['ncd-admin-base'],
                        $asset_version
                    );
                    break;

                case 'new-customers-templates':
                    wp_enqueue_style(
                        'ncd-admin-templates', 
                        NCD_ASSETS_URL . 'css/admin/templates.css',
                        ['ncd-admin-base'],
                        $asset_version
                    );
                    break;

                case 'new-customers-settings':
                    wp_enqueue_style(
                        'ncd-admin-settings', 
                        NCD_ASSETS_URL . 'css/admin/settings.css',
                        ['ncd-admin-base'],
                        $asset_version
                    );
                    break;

                case 'new-customers-statistics':
                    wp_enqueue_style(
                        'ncd-admin-statistics', 
                        NCD_ASSETS_URL . 'css/admin/statistics.css',
                        ['ncd-admin-base'],
                        $asset_version
                    );
                    break;
            }
        }

        // Dashicons werden für die Icons benötigt
        wp_enqueue_style('dashicons');

        // Gemeinsames JavaScript
        wp_enqueue_script(
            'ncd-admin-common',
            NCD_ASSETS_URL . 'js/admin.js',
            ['jquery'],
            $asset_version,
            true
        );

        // Lokalisierung für JavaScript
        wp_localize_script('ncd-admin-common', 'ncdAdmin', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ncd-admin-nonce'),
            'messages' => [
                'error' => __('Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.', 'newcustomer-discount'),
                'confirm' => __('Sind Sie sicher?', 'newcustomer-discount'),
                'saving' => __('Speichern...', 'newcustomer-discount'),
                'saved' => __('Gespeichert!', 'newcustomer-discount'),
                'email_required' => __('Bitte geben Sie eine E-Mail-Adresse ein.', 'newcustomer-discount'),
                'confirm_test' => __('Möchten Sie eine Test-E-Mail an diese Adresse senden?', 'newcustomer-discount'),
                'confirm_send' => __('Möchten Sie wirklich einen Rabattcode an diesen Kunden senden?', 'newcustomer-discount')
            ]
        ]);

        // Debug-Ausgabe wenn aktiviert
        if (WP_DEBUG) {
            $this->debug_loaded_styles();
        }
    }

    /**
     * Debug-Ausgabe der geladenen Stylesheets
     */
    private function debug_loaded_styles() {
        global $wp_styles;
        error_log('Loaded NCD Admin Styles:');
        foreach ($wp_styles->queue as $handle) {
            if (strpos($handle, 'ncd-admin') !== false) {
                error_log(' - ' . $handle);
            }
        }
    }

   /**
    * Gibt eine Admin-Seite zurück
    *
    * @param string $page_key Der Schlüssel der Seite
    * @return mixed|null Die Seiten-Instanz oder null
    */
   public function get_page($page_key) {
       return isset($this->pages[$page_key]) ? $this->pages[$page_key] : null;
   }

   /**
    * Gibt den AJAX Handler zurück
    *
    * @return NCD_Admin_Ajax
    */
   public function get_ajax_handler() {
       return $this->ajax;
   }

   /**
    * Prüft ob eine bestimmte Admin-Seite aktiv ist
    *
    * @param string $page_key Der Schlüssel der Seite
    * @return bool
    */
   public function is_plugin_page($page_key) {
       $screen = get_current_screen();
       if (!$screen) {
           return false;
       }

       return strpos($screen->id, 'new-customers-' . $page_key) !== false;
   }
}