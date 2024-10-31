<?php
/**
* Admin Templates Class
*
* Verwaltet die E-Mail-Templates im WordPress Admin-Bereich
*
* @package NewCustomerDiscount
* @subpackage Admin\Templates
* @since 0.0.1
*/

if (!defined('ABSPATH')) {
   exit;
}

class NCD_Admin_Templates extends NCD_Admin_Base {

   /**
    * Constructor
    */
   public function __construct() {
       parent::__construct();
   }

   /**
    * Überschreibt die Asset-Einbindung der Basisklasse
    *
    * @param string $hook
    */
   public function enqueue_assets($hook) {
       parent::enqueue_assets($hook);

       if (strpos($hook, 'new-customers-templates') !== false) {
           wp_enqueue_style('wp-color-picker');
           
           wp_enqueue_style(
               'ncd-admin-templates',
               NCD_PLUGIN_URL . 'assets/css/admin-templates.css',
               [],
               NCD_VERSION
           );

           wp_enqueue_script(
               'ncd-admin-templates',
               NCD_PLUGIN_URL . 'assets/js/admin-templates.js',
               ['jquery', 'wp-color-picker'],
               NCD_VERSION,
               true
           );

           wp_localize_script('ncd-admin-templates', 'ncdTemplates', [
               'ajaxurl' => admin_url('admin-ajax.php'),
               'nonce' => wp_create_nonce('ncd_template_nonce'),
               'messages' => [
                   'save_success' => __('Template-Einstellungen wurden gespeichert.', 'newcustomer-discount'),
                   'save_error' => __('Fehler beim Speichern der Einstellungen.', 'newcustomer-discount'),
                   'preview_error' => __('Fehler beim Generieren der Vorschau.', 'newcustomer-discount')
               ]
           ]);
       }
   }

   /**
    * Rendert die Templates-Verwaltungsseite
    */
    public function render_page() {
        if (!$this->check_admin_permissions()) {
            return;
        }
    
        if ($this->handle_template_post()) {
            $this->add_admin_notice(
                __('Template-Einstellungen wurden gespeichert.', 'newcustomer-discount'),
                'success'
            );
        }
    
        $available_templates = $this->email_sender->get_template_list();
        $current_template_id = get_option('ncd_active_template', 'modern');
        $current_template = $this->email_sender->load_template($current_template_id);
    
        include NCD_PLUGIN_DIR . 'templates/admin/templates-page.php';
    }

    /**
     * Handler für Template-Vorschau
     * 
     * @param array $data Die POST-Daten
     */
    public function handle_preview_template($data) {
        if (!$this->check_ajax_request('ncd_template_nonce', 'nonce')) {
            return;
        }

        parse_str($data['data'], $form_data);
        
        $template_id = isset($form_data['template_id']) ? 
            sanitize_text_field($form_data['template_id']) : 'modern';
        $settings = isset($form_data['settings']) ? $form_data['settings'] : [];
        $sanitized_settings = $this->sanitize_template_settings($settings);

        try {
            $preview = $this->email_sender->render_preview($template_id, $sanitized_settings);
            wp_send_json_success(['html' => $preview]);
        } catch (Exception $e) {
            $this->log_error('Template preview failed', [
                'template_id' => $template_id,
                'error' => $e->getMessage()
            ]);
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * Handler für Template-Wechsel
     * 
     * @param array $data Die POST-Daten
     */
    public function handle_switch_template($data) {
        if (!$this->check_ajax_request('ncd_save_template', 'nonce')) {
            return;
        }

        $template_id = isset($data['template_id']) ? 
            sanitize_text_field($data['template_id']) : 'modern';

       try {
           $this->email_sender->load_template($template_id);
           update_option('ncd_active_template', $template_id);

           wp_send_json_success([
               'message' => __('Template erfolgreich gewechselt.', 'newcustomer-discount')
           ]);
       } catch (Exception $e) {
           $this->log_error('Template switch failed', [
               'template_id' => $template_id,
               'error' => $e->getMessage()
           ]);
           wp_send_json_error(['message' => $e->getMessage()]);
       }
   }

    /**
     * Handler für Template-Einstellungen speichern
     * 
     * @param array $data Die POST-Daten
     */
    public function handle_save_template_settings($data) {
        if (!$this->check_ajax_request('ncd_save_template', 'nonce')) {
            return;
        }

        $template_id = isset($data['template_id']) ? 
            sanitize_text_field($data['template_id']) : 'modern';
        $settings = isset($data['settings']) ? 
            $this->sanitize_template_settings($data['settings']) : [];

       try {
           $this->email_sender->save_template_settings($template_id, $settings);
           $preview = $this->email_sender->render_preview($template_id);

           wp_send_json_success([
               'message' => __('Einstellungen gespeichert.', 'newcustomer-discount'),
               'html' => $preview
           ]);
       } catch (Exception $e) {
           $this->log_error('Template settings save failed', [
               'template_id' => $template_id,
               'error' => $e->getMessage()
           ]);
           wp_send_json_error(['message' => $e->getMessage()]);
       }
   }

   /**
    * Verarbeitet Template POST-Anfragen
    *
    * @return bool
    */
   private function handle_template_post() {
       if (!isset($_POST['save_template']) || !isset($_POST['template_id'])) {
           return false;
       }

       check_admin_referer('ncd_save_template', 'ncd_template_nonce');
       
       $template_id = sanitize_text_field($_POST['template_id']);
       $settings = isset($_POST['settings']) ? $_POST['settings'] : [];
       $sanitized_settings = $this->sanitize_template_settings($settings);
       
       $this->email_sender->save_template_settings($template_id, $sanitized_settings);
       
       return true;
   }

   /**
    * Überprüft AJAX-Anfragen
    *
    * @param string $action
    * @param string $nonce_field
    * @return bool
    */
    protected function check_ajax_request($action = 'ncd-admin-nonce', $nonce_field = 'nonce') {  // Angepasste Parameter
        if (!check_ajax_referer($action, $nonce_field, false)) {
            wp_send_json_error(['message' => __('Sicherheitsüberprüfung fehlgeschlagen.', 'newcustomer-discount')]);
            return false;
        }
    
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Keine Berechtigung.', 'newcustomer-discount')]);
            return false;
        }
    
        return true;
    }

   /**
    * Sanitiert Template-Einstellungen
    *
    * @param array $settings
    * @return array
    */
   private function sanitize_template_settings($settings) {
       $sanitized = [];
       
       if (is_array($settings)) {
           // Farben
           $color_keys = ['primary_color', 'secondary_color', 'text_color', 'background_color'];
           foreach ($color_keys as $key) {
               if (isset($settings[$key])) {
                   $sanitized[$key] = sanitize_hex_color($settings[$key]) ?: '#000000';
               }
           }

           // Schriftart
           if (isset($settings['font_family'])) {
               $allowed_fonts = [
                   'Arial, sans-serif',
                   "'Helvetica Neue', Helvetica, sans-serif",
                   "'Segoe UI', Tahoma, Geneva, sans-serif",
                   'Roboto, sans-serif',
                   'Georgia, serif'
               ];
               $sanitized['font_family'] = in_array($settings['font_family'], $allowed_fonts)
                   ? $settings['font_family']
                   : 'Arial, sans-serif';
           }

           // Button Style
           if (isset($settings['button_style'])) {
               $sanitized['button_style'] = in_array($settings['button_style'], ['rounded', 'square', 'pill'])
                   ? $settings['button_style']
                   : 'rounded';
           }

           // Layout Type
           if (isset($settings['layout_type'])) {
               $sanitized['layout_type'] = in_array($settings['layout_type'], ['centered', 'full-width'])
                   ? $settings['layout_type']
                   : 'centered';
           }
       }

       return $sanitized;
   }
}