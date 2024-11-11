<?php
/**
 * Admin Menu Class
 *
 * Manages the admin menu and pages
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
     * Register Pages
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
     * Adds a page to the menu
     * 
     * @param string $key pages identifier
     * @param object $page page object
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
     * Register Menus
     * 
     * @return void
     */
    public function register_menus() {
        if (WP_DEBUG) {
            error_log('Registering NCD admin menus');
            error_log('Registered pages: ' . print_r(array_keys($this->pages), true));
        }

        add_menu_page(
            __('New-Customers', 'newcustomer-discount'),
            __('New-Customers', 'newcustomer-discount'),
            'manage_options',
            'new-customers',
            [$this->pages['customers'], 'render_page'],
            'dashicons-groups',
            30
        );

        if (isset($this->pages['customers'])) {
            add_submenu_page(
                $this->menu_prefix,
                __('Overview', 'newcustomer-discount'),
                __('Overview', 'newcustomer-discount'),
                'manage_options',
                $this->menu_prefix,
                [$this->pages['customers'], 'render_page']
            );
        }

        $submenus = [
            'templates' => [
                'title' => __('E-Mail Templates', 'newcustomer-discount'),
                'menu_title' => __('Templates', 'newcustomer-discount')
            ],
            'settings' => [
                'title' => __('Settings', 'newcustomer-discount'),
                'menu_title' => __('Settings', 'newcustomer-discount')
            ],
            'statistics' => [
                'title' => __('Statistics', 'newcustomer-discount'),
                'menu_title' => __('Statistics', 'newcustomer-discount')
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
     * Render Main Page
     * 
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
     * Render Fallback Page
     * 
     * @return void
     */
    private function render_fallback_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('New-Customer-Discount for WooCommerce', 'newcustomer-discount'); ?></h1>
            <div class="notice notice-warning">
                <p>
                    <?php _e('The main page could not be loaded. Please check the installation.', 'newcustomer-discount'); ?>
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Checks if a page exists
     * 
     * @param string $key Seitenidentifikator
     * @return bool
     */
    public function page_exists($key) {
        return isset($this->pages[$key]);
    }

    /**
     * Returns a page by key
     * 
     * @param string $key Seitenidentifikator
     * @return object|null
     */
    public function get_page($key) {
        return isset($this->pages[$key]) ? $this->pages[$key] : null;
    }

    /**
     * Returns all pages
     * 
     * @return array
     */
    public function get_pages() {
        return $this->pages;
    }

    /**
     * Returns the menu slug
     * 
     * @param string $page Optional. Page identifier
     * @return string
     */
    public function get_menu_slug($page = '') {
        return empty($page) ? $this->menu_prefix : $this->menu_prefix . '-' . $page;
    }
}