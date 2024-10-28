<?php
/**
 * Admin Template Management Page
 *
 * @package NewCustomerDiscount
 */

if (!defined('ABSPATH')) {
    exit;
}

// Hole verfügbare Templates und aktives Template
$email_sender = new NCD_Email_Sender();
$available_templates = $email_sender->get_template_list();
$current_template_id = get_option('ncd_active_template', 'modern');
$current_template = $email_sender->load_template($current_template_id);
?>

<div class="wrap ncd-wrap">
    <h1><?php _e('E-Mail Template Verwaltung', 'newcustomer-discount'); ?></h1>

    <?php settings_errors('ncd_template'); ?>

    <div class="ncd-template-selector">
        <h2><?php _e('Template auswählen', 'newcustomer-discount'); ?></h2>
        
        <div class="ncd-template-grid">
            <?php foreach ($available_templates as $id => $template): 
                $template_data = $email_sender->load_template($id);
            ?>
                <div class="ncd-template-card <?php echo $current_template_id === $id ? 'active' : ''; ?>"
                     data-template-id="<?php echo esc_attr($id); ?>">
                    <img src="<?php echo esc_url($template['preview']); ?>" 
                         alt="<?php echo esc_attr($template_data['name']); ?>"
                         class="ncd-template-preview-img">
                    
                    <div class="ncd-template-info">
                        <h3><?php echo esc_html($template_data['name']); ?></h3>
                        <p><?php echo esc_html($template_data['description']); ?></p>
                        <button type="button" class="button button-primary select-template">
                            <?php echo $current_template_id === $id ? 
                                __('Aktiv', 'newcustomer-discount') : 
                                __('Auswählen', 'newcustomer-discount'); ?>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="ncd-template-customizer">
        <div class="ncd-customizer-sidebar">
            <form method="post" id="template-settings-form">
                <?php wp_nonce_field('ncd_save_template', 'ncd_template_nonce'); ?>
                <input type="hidden" name="template_id" value="<?php echo esc_attr($current_template_id); ?>">
                
                <div class="ncd-settings-section">
                    <h3><?php _e('Farben', 'newcustomer-discount'); ?></h3>
                    
                    <div class="ncd-color-picker">
                        <div class="ncd-color-control">
                            <label for="primary_color">
                                <?php _e('Primärfarbe', 'newcustomer-discount'); ?>
                            </label>
                            <input type="color" 
                                   name="settings[primary_color]" 
                                   id="primary_color" 
                                   value="<?php echo esc_attr($current_template['settings']['primary_color']); ?>">
                        </div>

                        <div class="ncd-color-control">
                            <label for="secondary_color">
                                <?php _e('Sekundärfarbe', 'newcustomer-discount'); ?>
                            </label>
                            <input type="color" 
                                   name="settings[secondary_color]" 
                                   id="secondary_color" 
                                   value="<?php echo esc_attr($current_template['settings']['secondary_color']); ?>">
                        </div>

                        <div class="ncd-color-control">
                            <label for="text_color">
                                <?php _e('Textfarbe', 'newcustomer-discount'); ?>
                            </label>
                            <input type="color" 
                                   name="settings[text_color]" 
                                   id="text_color" 
                                   value="<?php echo esc_attr($current_template['settings']['text_color']); ?>">
                        </div>

                        <div class="ncd-color-control">
                            <label for="background_color">
                                <?php _e('Hintergrundfarbe', 'newcustomer-discount'); ?>
                            </label>
                            <input type="color" 
                                   name="settings[background_color]" 
                                   id="background_color" 
                                   value="<?php echo esc_attr($current_template['settings']['background_color']); ?>">
                        </div>
                    </div>
                </div>

                <div class="ncd-settings-section">
                    <h3><?php _e('Typografie', 'newcustomer-discount'); ?></h3>
                    
                    <div class="ncd-select-control">
                        <label for="font_family">
                            <?php _e('Schriftart', 'newcustomer-discount'); ?>
                        </label>
                        <select name="settings[font_family]" id="font_family">
                            <option value="Arial, sans-serif" <?php selected($current_template['settings']['font_family'], 'Arial, sans-serif'); ?>>
                                Arial
                            </option>
                            <option value="'Helvetica Neue', Helvetica, sans-serif" <?php selected($current_template['settings']['font_family'], "'Helvetica Neue', Helvetica, sans-serif"); ?>>
                                Helvetica
                            </option>
                            <option value="'Segoe UI', Tahoma, Geneva, sans-serif" <?php selected($current_template['settings']['font_family'], "'Segoe UI', Tahoma, Geneva, sans-serif"); ?>>
                                Segoe UI
                            </option>
                            <option value="Roboto, sans-serif" <?php selected($current_template['settings']['font_family'], 'Roboto, sans-serif'); ?>>
                                Roboto
                            </option>
                        </select>
                    </div>
                </div>

                <div class="ncd-settings-section">
                    <h3><?php _e('Layout', 'newcustomer-discount'); ?></h3>
                    
                    <div class="ncd-select-control">
                        <label for="button_style">
                            <?php _e('Button-Stil', 'newcustomer-discount'); ?>
                        </label>
                        <select name="settings[button_style]" id="button_style">
                            <option value="rounded" <?php selected($current_template['settings']['button_style'], 'rounded'); ?>>
                                <?php _e('Abgerundet', 'newcustomer-discount'); ?>
                            </option>
                            <option value="square" <?php selected($current_template['settings']['button_style'], 'square'); ?>>
                                <?php _e('Eckig', 'newcustomer-discount'); ?>
                            </option>
                            <option value="pill" <?php selected($current_template['settings']['button_style'], 'pill'); ?>>
                                <?php _e('Pill-Form', 'newcustomer-discount'); ?>
                            </option>
                        </select>
                    </div>

                    <div class="ncd-select-control">
                        <label for="layout_type">
                            <?php _e('Layout-Typ', 'newcustomer-discount'); ?>
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

                <div class="ncd-save-template">
                    <button type="submit" name="save_template" class="button button-primary">
                        <?php _e('Änderungen speichern', 'newcustomer-discount'); ?>
                    </button>
                </div>
            </form>
        </div>

        <div class="ncd-template-preview">
            <div class="ncd-preview-toolbar">
                <button type="button" class="button preview-desktop active">
                    <span class="dashicons dashicons-desktop"></span>
                </button>
                <button type="button" class="button preview-mobile">
                    <span class="dashicons dashicons-smartphone"></span>
                </button>
                <button type="button" class="button preview-test-email">
                    <span class="dashicons dashicons-email-alt"></span>
                    <?php _e('Test-E-Mail senden', 'newcustomer-discount'); ?>
                </button>
            </div>

            <div class="ncd-preview-frame">
                <?php 
                // Initial preview
                echo $email_sender->render_preview($current_template_id); 
                ?>
            </div>
        </div>
    </div>

    <div class="ncd-variables-info">
        <h3><?php _e('Verfügbare Template-Variablen', 'newcustomer-discount'); ?></h3>
        <div class="ncd-variables-list">
            <?php 
            $variables = $email_sender->get_available_variables();
            foreach ($variables as $var => $desc): 
            ?>
                <div>
                    <code><?php echo esc_html($var); ?></code> - 
                    <?php echo esc_html($desc); ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Test Email Modal -->
<div id="test-email-modal" class="ncd-modal" style="display:none;">
    <div class="ncd-modal-content">
        <span class="ncd-modal-close">&times;</span>
        <h2><?php _e('Test-E-Mail senden', 'newcustomer-discount'); ?></h2>
        <p><?php _e('Geben Sie eine E-Mail-Adresse ein, um eine Test-E-Mail zu senden.', 'newcustomer-discount'); ?></p>
        <form id="test-email-form">
            <input type="email" id="test-email" required 
                   placeholder="<?php esc_attr_e('E-Mail-Adresse', 'newcustomer-discount'); ?>">
            <button type="submit" class="button button-primary">
                <?php _e('Senden', 'newcustomer-discount'); ?>
            </button>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Template-Auswahl
    $('.select-template').on('click', function() {
        const templateId = $(this).closest('.ncd-template-card').data('template-id');
        
        $.post(ajaxurl, {
            action: 'ncd_switch_template',
            nonce: $('#ncd_template_nonce').val(),
            template_id: templateId
        }, function(response) {
            if (response.success) {
                location.reload();
            }
        });
    });

    // Live-Vorschau
    let previewTimer;
    $('#template-settings-form input, #template-settings-form select').on('change input', function() {
        clearTimeout(previewTimer);
        previewTimer = setTimeout(updatePreview, 500);
    });

    function updatePreview() {
        const formData = $('#template-settings-form').serialize();
        $('.ncd-preview-frame').addClass('ncd-preview-loading');
        
        $.post(ajaxurl, {
            action: 'ncd_preview_template',
            nonce: $('#ncd_template_nonce').val(),
            data: formData
        }, function(response) {
            if (response.success) {
                $('.ncd-preview-frame').html(response.data.html);
            }
            $('.ncd-preview-frame').removeClass('ncd-preview-loading');
        });
    }

    // Vorschau-Modi
    $('.preview-desktop, .preview-mobile').on('click', function() {
        $('.ncd-preview-frame').removeClass('mobile desktop')
            .addClass($(this).hasClass('preview-desktop') ? 'desktop' : 'mobile');
        $('.ncd-preview-toolbar button').removeClass('active');
        $(this).addClass('active');
    });

    // Test-E-Mail Modal
    $('.preview-test-email').on('click', function() {
        $('#test-email-modal').show();
    });

    $('.ncd-modal-close').on('click', function() {
        $('#test-email-modal').hide();
    });

    $('#test-email-form').on('submit', function(e) {
        e.preventDefault();
        const email = $('#test-email').val();
        
        $.post(ajaxurl, {
            action: 'ncd_send_test_email',
            nonce: $('#ncd_template_nonce').val(),
            email: email,
            template_id: $('input[name="template_id"]').val()
        }, function(response) {
            if (response.success) {
                alert(response.data.message);
                $('#test-email-modal').hide();
            } else {
                alert(response.data.message || 'Ein Fehler ist aufgetreten.');
            }
        });
    });

    // Schließe Modal bei Klick außerhalb
    $(window).on('click', function(e) {
        if ($(e.target).is('.ncd-modal')) {
            $('.ncd-modal').hide();
        }
    });
});
</script>