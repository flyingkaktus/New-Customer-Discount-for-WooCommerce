<?php
/**
 * Admin Tab Manager Class
 *
 * Verwaltet die Tab-Navigation im WordPress Admin-Bereich
 *
 * @package NewCustomerDiscount
 * @subpackage Admin\Core
 * @since 0.0.1
 */

if (!defined('ABSPATH')) {
    exit;
}

class NCD_Admin_Tab_Manager {
    /**
     * Registrierte Tabs
     *
     * @var array
     */
    private $tabs = [];

    /**
     * Aktiver Tab
     *
     * @var string
     */
    private $active_tab;

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * Registriert einen neuen Tab
     *
     * @param string $id Tab ID
     * @param string $title Tab Titel
     * @param string $content_callback Callback für Tab-Inhalt
     * @param array $args Optionale Argumente
     */
    public function add_tab($id, $title, $content_callback, $args = []) {
        $this->tabs[$id] = [
            'title' => $title,
            'content_callback' => $content_callback,
            'args' => wp_parse_args($args, [
                'priority' => 10,
                'icon' => '',
                'class' => ''
            ])
        ];
    }

    /**
     * Rendert die Tab Navigation
     *
     * @param string $default_tab Standard aktiver Tab
     */
    public function render_tabs($default_tab = '') {
        $this->active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : $default_tab;
        
        // Sortiere Tabs nach Priorität
        uasort($this->tabs, function($a, $b) {
            return $a['args']['priority'] - $b['args']['priority'];
        });

        ?>
        <div class="ncd-tabs">
            <nav class="nav-tab-wrapper">
                <?php foreach ($this->tabs as $id => $tab): ?>
                    <a href="#<?php echo esc_attr($id); ?>" 
                       class="nav-tab <?php echo $id === $this->active_tab ? 'nav-tab-active' : ''; ?> <?php echo esc_attr($tab['args']['class']); ?>">
                        <?php if (!empty($tab['args']['icon'])): ?>
                            <span class="dashicons <?php echo esc_attr($tab['args']['icon']); ?>"></span>
                        <?php endif; ?>
                        <?php echo esc_html($tab['title']); ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <?php foreach ($this->tabs as $id => $tab): ?>
                <div id="<?php echo esc_attr($id); ?>" 
                     class="ncd-tab-content <?php echo $id === $this->active_tab ? 'active' : ''; ?>">
                    <?php call_user_func($tab['content_callback']); ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Lädt die benötigten Assets
     *
     * @param string $hook Der aktuelle Admin-Seiten-Hook
     */
    public function enqueue_assets($hook) {
        if (strpos($hook, 'new-customers') === false) {
            return;
        }

        wp_enqueue_style(
            'ncd-admin-tabs',
            NCD_PLUGIN_URL . 'assets/css/admin-tabs.css',
            [],
            NCD_VERSION
        );

        wp_enqueue_script(
            'ncd-admin-tabs',
            NCD_PLUGIN_URL . 'assets/js/admin-tabs.js',
            ['jquery'],
            NCD_VERSION,
            true
        );

        wp_localize_script('ncd-admin-tabs', 'ncdTabs', [
            'defaultTab' => $this->active_tab
        ]);
    }

    /**
     * Gibt den aktiven Tab zurück
     *
     * @return string
     */
    public function get_active_tab() {
        return $this->active_tab;
    }

    /**
     * Prüft ob ein Tab existiert
     *
     * @param string $id Tab ID
     * @return bool
     */
    public function tab_exists($id) {
        return isset($this->tabs[$id]);
    }
}