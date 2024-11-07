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

       $asset_version = WP_DEBUG ? time() : NCD_VERSION;

       if (strpos($hook, 'new-customers-templates') !== false) {
           wp_enqueue_style('wp-color-picker');
           
           wp_enqueue_style(
               'ncd-admin-templates',
               NCD_PLUGIN_URL . 'assets/css/admin-templates.css',
               [],
               $asset_version
           );

           wp_enqueue_script(
               'ncd-admin-templates',
               NCD_PLUGIN_URL . 'assets/js/admin-templates.js',
               ['jquery', 'wp-color-picker'],
               $asset_version,
               true
           );
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
     * Handler für Template-Vorschau - wird von AJAX aufgerufen
     * 
     * @param array $data Die POST-Daten
     */
    public function handle_preview_template($data) {
        if (!$this->check_admin_permissions()) {
            return;
        }
    
        try {
            parse_str($data['data'], $form_data);
            
            // Korrekte Template-ID aus dem Formular holen
            $template_id = isset($form_data['template_id']) ? 
                sanitize_text_field($form_data['template_id']) : 'modern';
                
            $settings = isset($form_data['settings']) ? $form_data['settings'] : [];
            $sanitized_settings = $this->sanitize_template_settings($settings);
    
            // Debug-Log
            if (WP_DEBUG) {
                error_log('Preview request for template: ' . $template_id);
                error_log('With settings: ' . print_r($sanitized_settings, true));
            }
    
            $preview = $this->email_sender->render_preview($template_id, $sanitized_settings);
            wp_send_json_success(['html' => $preview]);
            
        } catch (Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handler für Template-Wechsel - wird von AJAX aufgerufen
     * 
     * @param array $data Die POST-Daten
     */
    public function handle_switch_template($data) {
        if (!$this->check_admin_permissions()) {
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

    public function handle_get_template_settings($data) {
        if (!$this->check_admin_permissions()) {
            return;
        }
    
        try {
            if (!isset($data['template_id'])) {
                throw new Exception(__('Keine Template-ID angegeben.', 'newcustomer-discount'));
            }
    
            $template_id = sanitize_text_field($data['template_id']);
            
            // Debug Log
            if (WP_DEBUG) {
                error_log('Getting settings for template: ' . $template_id);
            }
    
            // Lade das Template mit seinen Einstellungen
            $template = $this->email_sender->load_template($template_id);
            
            // Sende die Template-Einstellungen zurück
            wp_send_json_success([
                'settings' => $template['settings']
            ]);
    
        } catch (Exception $e) {
            if (WP_DEBUG) {
                error_log('Error getting template settings: ' . $e->getMessage());
            }
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Handler für Template-Einstellungen speichern - wird von AJAX aufgerufen
     * 
     * @param array $data Die POST-Daten
     */
    public function handle_save_template_settings($data) {
        if (!$this->check_admin_permissions()) {
            return;
        }
    
        try {
            $template_id = sanitize_text_field($data['template_id']);
            parse_str($data['settings'], $settings);
            
            // Nur die relevanten Einstellungen extrahieren
            $template_settings = isset($settings['settings']) ? $settings['settings'] : [];
            $sanitized_settings = $this->sanitize_template_settings($template_settings);
    
            // Template-spezifisch speichern
            $this->email_sender->save_template_settings($template_id, $sanitized_settings);
            
            // Vorschau mit den gespeicherten Einstellungen rendern
            $preview = $this->email_sender->render_preview($template_id);
    
            wp_send_json_success([
                'message' => __('Template-Einstellungen gespeichert.', 'newcustomer-discount'),
                'html' => $preview
            ]);
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

   /**
    * Verarbeitet Template POST-Anfragen
    *
    * @return bool
    */
    private function handle_template_post() {
        if (!isset($_POST['template_id'])) {
            return false;
        }
    
        $template_id = sanitize_text_field($_POST['template_id']);
        
        // Hole aktuelle gespeicherte Einstellungen
        $saved_settings = get_option('ncd_template_' . $template_id . '_settings', []);
        
        // Hole das Template mit Standardeinstellungen
        $template = $this->email_sender->load_template($template_id);
        $default_settings = $template['settings'];
        
        // Wenn neue Einstellungen gesendet wurden, diese verwenden
        if (isset($_POST['settings'])) {
            $new_settings = $this->sanitize_template_settings($_POST['settings']);
            // Merge in der richtigen Reihenfolge: defaults -> saved -> new
            $settings = wp_parse_args($new_settings, wp_parse_args($saved_settings, $default_settings));
        } else {
            // Ansonsten nur gespeicherte mit defaults mergen
            $settings = wp_parse_args($saved_settings, $default_settings);
        }
        
        // Speichere die gemergten Einstellungen
        update_option('ncd_template_' . $template_id . '_settings', $settings);
        
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
  
   public function handle_activate_template($data) {
    if (!isset($data['template_id'])) {
        wp_send_json_error([
            'message' => __('Keine Template-ID angegeben.', 'newcustomer-discount')
        ]);
        return;
    }

    $template_id = sanitize_text_field($data['template_id']);

    try {
        // Lade Template um zu prüfen ob es existiert
        $template = $this->email_sender->load_template($template_id);
        
        // Aktiviere Template
        update_option('ncd_active_template', $template_id);
        
        wp_send_json_success([
            'message' => sprintf(
                __('Template "%s" wurde aktiviert und wird nun für alle E-Mails verwendet.', 'newcustomer-discount'),
                $template['name']
            ),
            'template_name' => $template['name']
        ]);
    } catch (Exception $e) {
        $this->log_error('Template activation failed', [
            'template_id' => $template_id,
            'error' => $e->getMessage()
        ]);
        wp_send_json_error([
            'message' => $e->getMessage()
        ]);
      }
   }
}