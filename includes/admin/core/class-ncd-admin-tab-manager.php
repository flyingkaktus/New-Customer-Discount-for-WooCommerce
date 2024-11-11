<?php
/**
 * Admin Tab Manager Class
 *
 * Manages admin tabs
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
     * Registerd Tabs
     *
     * @var array
     */
    private $tabs = [];

    /**
     * Active Tab
     *
     * @var string
     */
    private $active_tab;

    /**
     * Register a new tab
     *
     * @param string $id Tab ID
     * @param string $title Tab Titel
     * @param string $content_callback Callback for tab content
     * @param array $args Optionale arguemnts
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
     * Renders the tabs
     *
     * @param string $default_tab Standard aktiver Tab
     */
    public function render_tabs($default_tab = '') {
        $this->active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : $default_tab;
        
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
     * Returns the active tab
     *
     * @return string
     */
    public function get_active_tab() {
        return $this->active_tab;
    }

    /**
     * checks if a tab exists
     *
     * @param string $id Tab ID
     * @return bool
     */
    public function tab_exists($id) {
        return isset($this->tabs[$id]);
    }
}