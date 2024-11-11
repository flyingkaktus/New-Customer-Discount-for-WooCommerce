<?php
/**
 * Admin Template Management Page
 *
 * @package NewCustomerDiscount
 */

if (!defined('ABSPATH')) {
    exit;
}

// Hole verfügbare Templates & aktives Template
$email_sender = new NCD_Email_Sender();
$available_templates = $email_sender->get_template_list();
$current_template_id = get_option('ncd_active_template', 'modern');
$current_template = $email_sender->load_template($current_template_id);
$saved_settings = get_option('ncd_template_' . $current_template_id . '_settings', []);
$initial_preview = $email_sender->render_preview($current_template_id, $saved_settings);
?>

<div class="wrap ncd-wrap">
    <h1><?php _e('E-Mail Template Design', 'newcustomer-discount'); ?></h1>

    <?php settings_errors(); ?>

    <div class="ncd-template-manager">
        <!-- Template Selector Dropdown -->
        <div class="ncd-template-selector">
            <label for="template-selector" class="ncd-template-label">
                <?php _e('Template auswählen:', 'newcustomer-discount'); ?>
            </label>
            <div class="ncd-template-controls">
                <select id="template-selector" class="ncd-select" 
                data-active-template="<?php echo esc_attr(get_option('ncd_active_template', 'modern')); ?>">
                    <?php 
                    $active_template_id = get_option('ncd_active_template', 'modern');
                    foreach ($available_templates as $id => $template): 
                        $template_data = $email_sender->load_template($id);
                        $is_active = ($active_template_id === $id);
                    ?>
                        <option value="<?php echo esc_attr($id); ?>" 
                                <?php selected($active_template_id, $id); ?>>
                            <?php echo esc_html($template_data['name']); ?>
                            <?php if ($is_active): ?>
                                <?php _e(' (Aktiv)', 'newcustomer-discount'); ?>
                            <?php endif; ?>
                            - <?php echo esc_html($template_data['description']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="button" 
                        id="activate-template" 
                        class="button button-primary" 
                        <?php echo $current_template_id === $active_template_id ? 'disabled' : ''; ?>>
                    <?php _e('Template aktivieren', 'newcustomer-discount'); ?>
                </button>
            </div>
            <div class="ncd-template-status">
                <span class="ncd-active-template-info">
                    <?php printf(
                        __('Aktives Template: <strong>%s</strong>', 'newcustomer-discount'),
                        $email_sender->load_template($active_template_id)['name']
                    ); ?>
                </span>
            </div>
        </div>

        <div class="ncd-design-container">
            <!-- Settings Panel -->
            <div class="ncd-settings-panel">
                <form method="post" id="template-settings-form">
                    <?php wp_nonce_field('ncd_save_template', 'ncd_template_nonce'); ?>
                    <input type="hidden" name="template_id" value="<?php echo esc_attr($current_template_id); ?>">
                    
                    <div class="ncd-settings-group">
                        <h3><?php _e('Design Anpassen', 'newcustomer-discount'); ?></h3>
                        
                        <!-- Colors -->
                        <div class="ncd-color-controls">
                            <div class="ncd-color-row">
                                <label for="primary_color">
                                    <?php _e('Primärfarbe', 'newcustomer-discount'); ?>
                                </label>
                                <input type="color" 
                                       name="settings[primary_color]" 
                                       id="primary_color" 
                                       value="<?php echo esc_attr($current_template['settings']['primary_color']); ?>">
                            </div>

                            <div class="ncd-color-row">
                                <label for="secondary_color">
                                    <?php _e('Akzentfarbe', 'newcustomer-discount'); ?>
                                </label>
                                <input type="color" 
                                       name="settings[secondary_color]" 
                                       id="secondary_color" 
                                       value="<?php echo esc_attr($current_template['settings']['secondary_color']); ?>">
                            </div>

                            <div class="ncd-color-row">
                                <label for="text_color">
                                    <?php _e('Text', 'newcustomer-discount'); ?>
                                </label>
                                <input type="color" 
                                       name="settings[text_color]" 
                                       id="text_color" 
                                       value="<?php echo esc_attr($current_template['settings']['text_color']); ?>">
                            </div>

                            <div class="ncd-color-row">
                                <label for="background_color">
                                    <?php _e('Hintergrund', 'newcustomer-discount'); ?>
                                </label>
                                <input type="color" 
                                       name="settings[background_color]" 
                                       id="background_color" 
                                       value="<?php echo esc_attr($current_template['settings']['background_color']); ?>">
                            </div>
                        </div>

                        <!-- Typography -->
                        <div class="ncd-typography-control">
                            <label for="font_family">
                                <?php _e('Schriftart', 'newcustomer-discount'); ?>
                            </label>
                            <select name="settings[font_family]" id="font_family">
                                <option value="system-ui, -apple-system, sans-serif" <?php selected($current_template['settings']['font_family'], 'system-ui, -apple-system, sans-serif'); ?>>
                                    System Default
                                </option>
                                <option value="'Inter', sans-serif" <?php selected($current_template['settings']['font_family'], "'Inter', sans-serif"); ?>>
                                    Inter
                                </option>
                                <option value="'Roboto', sans-serif" <?php selected($current_template['settings']['font_family'], "'Roboto', sans-serif"); ?>>
                                    Roboto
                                </option>
                            </select>
                        </div>

                        <!-- Layout -->
                        <div class="ncd-layout-controls">
                            <div class="ncd-control-group">
                                <label for="button_style">
                                    <?php _e('Button Design', 'newcustomer-discount'); ?>
                                </label>
                                <select name="settings[button_style]" id="button_style">
                                    <option value="minimal" <?php selected($current_template['settings']['button_style'], 'minimal'); ?>>
                                        <?php _e('Minimalistisch', 'newcustomer-discount'); ?>
                                    </option>
                                    <option value="rounded" <?php selected($current_template['settings']['button_style'], 'rounded'); ?>>
                                        <?php _e('Abgerundet', 'newcustomer-discount'); ?>
                                    </option>
                                    <option value="pill" <?php selected($current_template['settings']['button_style'], 'pill'); ?>>
                                        <?php _e('Pill', 'newcustomer-discount'); ?>
                                    </option>
                                </select>
                            </div>

                            <div class="ncd-control-group">
                                <label for="layout_type">
                                    <?php _e('Layout', 'newcustomer-discount'); ?>
                                </label>
                                <select name="settings[layout_type]" id="layout_type">
                                    <option value="centered" <?php selected($current_template['settings']['layout_type'], 'centered'); ?>>
                                        <?php _e('Zentriert', 'newcustomer-discount'); ?>
                                    </option>
                                    <option value="full-width" <?php selected($current_template['settings']['layout_type'], 'full-width'); ?>>
                                        <?php _e('Volle Breite', 'newcustomer-discount'); ?>
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="ncd-action-bar">
                        <button type="submit" name="save_template" class="button button-primary">
                            <?php _e('Speichern', 'newcustomer-discount'); ?>
                        </button>
                        <button type="button" class="button preview-test-email">
                            <?php _e('Test-E-Mail', 'newcustomer-discount'); ?>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Preview Panel -->
            <div class="ncd-preview-panel">
                <div class="ncd-preview-header">
                    <h3><?php _e('Vorschau', 'newcustomer-discount'); ?></h3>
                    <div class="ncd-preview-controls">
                        <button type="button" class="preview-mode active" data-mode="desktop">
                            <span class="dashicons dashicons-desktop"></span>
                        </button>
                        <button type="button" class="preview-mode" data-mode="mobile">
                            <span class="dashicons dashicons-smartphone"></span>
                        </button>
                    </div>
                </div>
                
                <div class="ncd-preview-container">
                    <div class="ncd-preview-frame">
                        <?php echo $email_sender->render_preview($current_template_id); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    new NCDTemplateManager();
});
</script>