<?php
/**
 * Admin Template Management Page
 *
 * @package NewCustomerDiscount
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap ncd-wrap">
    <h1><?php _e('E-Mail Template Verwaltung', 'newcustomer-discount'); ?></h1>

    <?php settings_errors('ncd_template'); ?>

    <div class="ncd-template-editor-wrapper">
        <form method="post" action="">
            <?php wp_nonce_field('ncd_save_template', 'ncd_template_nonce'); ?>

            <div class="ncd-template-toolbar">
                <div class="ncd-variables-list">
                    <h3><?php _e('E-Mail Template', 'newcustomer-discount'); ?></h3>
                    <textarea name="template_content" 
                              id="ncd_template_editor" 
                              class="large-text code" 
                              rows="20"><?php echo esc_textarea($current_template); ?></textarea>
                    
                    <h4><?php _e('Verfügbare Variablen:', 'newcustomer-discount'); ?></h4>
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th><?php _e('Variable', 'newcustomer-discount'); ?></th>
                                <th><?php _e('Beschreibung', 'newcustomer-discount'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($available_variables as $variable => $description): ?>
                            <tr>
                                <td><code><?php echo esc_html($variable); ?></code></td>
                                <td><?php echo esc_html($description); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <p class="submit">
                <button type="submit" name="save_template" class="button button-primary">
                    <?php _e('Template speichern', 'newcustomer-discount'); ?>
                </button>
                <button type="button" class="button ncd-preview-template">
                    <?php _e('Vorschau', 'newcustomer-discount'); ?>
                </button>
                <button type="button" class="button ncd-reset-template">
                    <?php _e('Auf Standard zurücksetzen', 'newcustomer-discount'); ?>
                </button>
            </p>
        </form>
    </div>

    <!-- Template Vorschau Modal -->
    <div id="ncd-template-preview-modal" class="ncd-modal" style="display:none;">
        <div class="ncd-modal-content">
            <span class="ncd-modal-close">&times;</span>
            <h2><?php _e('Template Vorschau', 'newcustomer-discount'); ?></h2>
            <div class="ncd-preview-frame"></div>
        </div>
    </div>
</div>


<script>
jQuery(document).ready(function($) {
    // Template Preview
    $('.ncd-preview-template').on('click', function(e) {
        e.preventDefault();
        var template = $('#ncd_template_editor').val();
        
        $.post(ajaxurl, {
            action: 'ncd_preview_template',
            template: template,
            nonce: $('#ncd_template_nonce').val()
        }, function(response) {
            if (response.success) {
                $('.ncd-preview-frame').html(response.data.html);
                $('#ncd-template-preview-modal').show();
            } else {
                alert(response.data.message || 'Fehler beim Laden der Vorschau');
            }
        });
    });

    // Template Reset
    $('.ncd-reset-template').on('click', function(e) {
        e.preventDefault();
        if (confirm('<?php _e("Möchten Sie das Template wirklich auf den Standard zurücksetzen?", "newcustomer-discount"); ?>')) {
            $.post(ajaxurl, {
                action: 'ncd_reset_template',
                nonce: $('#ncd_template_nonce').val()
            }, function(response) {
                if (response.success) {
                    $('#ncd_template_editor').val(response.data.template);
                } else {
                    alert(response.data.message || 'Fehler beim Zurücksetzen');
                }
            });
        }
    });

    // Modal Close
    $('.ncd-modal-close').on('click', function() {
        $('#ncd-template-preview-modal').hide();
    });

    $(window).on('click', function(e) {
        if ($(e.target).is('.ncd-modal')) {
            $('.ncd-modal').hide();
        }
    });
});
</script>