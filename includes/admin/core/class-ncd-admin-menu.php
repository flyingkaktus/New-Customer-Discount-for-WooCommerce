<?php
/**
 * Admin Menu Class
 *
 * Verwaltet die Menüstruktur im WordPress Admin-Bereich
 *
 * @package NewCustomerDiscount
 * @subpackage Admin\Core
 * @since 0.0.1
 */

if (!defined('ABSPATH')) {
    exit;
}

class NCD_Admin_Menu {
    /**
     * Registrierte Seiten
     * 
     * @var array
     */
    private $pages = [];

    /**
     * Menu Slug Prefix
     * 
     * @var string
     */
    private $menu_prefix = 'new-customers';

    /**
     * Constructor
     */
    public function __construct() {
        if (WP_DEBUG) {
            error_log('Initializing NCD Admin Menu');
        }
    }

    /**
     * Fügt eine neue Seite hinzu
     * 
     * @param string $key Seitenidentifikator
     * @param object $page Seitenobjekt
     * @return void
     */
    public function add_page($key, $page) {
        if (WP_DEBUG) {
            error_log("Adding page: $key");
        }
        
        if (!method_exists($page, 'render_page')) {
            error_log("Error: Page object for '$key' does not implement render_page method");
            return;
        }
        
        $this->pages[$key] = $page;
    }

    /**
     * Registriert die Menüs
     * 
     * @return void
     */
    public function register_menus() {
        if (WP_DEBUG) {
            error_log('Registering NCD admin menus');
            error_log('Registered pages: ' . print_r(array_keys($this->pages), true));
        }

        // Hauptmenü
        add_menu_page(
            __('Neukunden', 'newcustomer-discount'),
            __('Neukunden', 'newcustomer-discount'),
            'manage_options',
            'new-customers',
            [$this->pages['customers'], 'render_page'], // Geändert von render_customers_page
            'dashicons-groups',
            30
        );


        // Erste Seite als Hauptseite
        if (isset($this->pages['customers'])) {
            add_submenu_page(
                $this->menu_prefix,
                __('Übersicht', 'newcustomer-discount'),
                __('Übersicht', 'newcustomer-discount'),
                'manage_options',
                $this->menu_prefix,
                [$this->pages['customers'], 'render_page']
            );
        }

        // Untermenüs
        $submenus = [
            'templates' => [
                'title' => __('E-Mail Templates', 'newcustomer-discount'),
                'menu_title' => __('Templates', 'newcustomer-discount')
            ],
            'settings' => [
                'title' => __('Einstellungen', 'newcustomer-discount'),
                'menu_title' => __('Einstellungen', 'newcustomer-discount')
            ],
            'statistics' => [
                'title' => __('Statistiken', 'newcustomer-discount'),
                'menu_title' => __('Statistiken', 'newcustomer-discount')
            ]
        ];

        foreach ($submenus as $key => $menu) {
            if (isset($this->pages[$key])) {
                add_submenu_page(
                    $this->menu_prefix,
                    $menu['title'],
                    $menu['menu_title'],
                    'manage_options',
                    $this->menu_prefix . '-' . $key,
                    [$this->pages[$key], 'render_page']
                );
            } else {
                if (WP_DEBUG) {
                    error_log("Warning: Page handler for '$key' not found");
                }
            }
        }
    }

    /**
     * Rendert die Hauptseite
     * Wird nur aufgerufen, wenn keine Unterseite definiert ist
     * 
     * @return void
     */
    public function render_main_page() {
        if (isset($this->pages['customers'])) {
            $this->pages['customers']->render_page();
        } else {
            $this->render_fallback_page();
        }
    }

    /**
     * Rendert eine Fallback-Seite wenn keine Hauptseite verfügbar ist
     * 
     * @return void
     */
    private function render_fallback_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('New-Customer-Gutschein for WooCommerce', 'newcustomer-discount'); ?></h1>
            <div class="notice notice-warning">
                <p>
                    <?php _e('Die Hauptseite konnte nicht geladen werden. Bitte überprüfen Sie die Installation.', 'newcustomer-discount'); ?>
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Prüft ob eine bestimmte Seite existiert
     * 
     * @param string $key Seitenidentifikator
     * @return bool
     */
    public function page_exists($key) {
        return isset($this->pages[$key]);
    }

    /**
     * Gibt eine registrierte Seite zurück
     * 
     * @param string $key Seitenidentifikator
     * @return object|null
     */
    public function get_page($key) {
        return isset($this->pages[$key]) ? $this->pages[$key] : null;
    }

    /**
     * Gibt alle registrierten Seiten zurück
     * 
     * @return array
     */
    public function get_pages() {
        return $this->pages;
    }

    /**
     * Gibt den aktuellen Menü-Slug zurück
     * 
     * @param string $page Optional. Seitenidentifikator
     * @return string
     */
    public function get_menu_slug($page = '') {
        return empty($page) ? $this->menu_prefix : $this->menu_prefix . '-' . $page;
    }
}