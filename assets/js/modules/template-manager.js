// assets/js/modules/template-manager.js
(function($) {
    'use strict';

    class NCDTemplateManager {
        constructor() {
            this.$templateSelector = $('#template-selector');
            this.$previewFrame = $('.ncd-preview-frame');
            this.$settingsForm = $('#template-settings-form');
            this.$testEmailModal = $('#test-email-modal');
            this.bindEvents();
        }

        bindEvents() {
            // Template Switcher
            this.$templateSelector.on('change', (e) => this.handleTemplateSwitch(e));

            // Settings Changes
            let previewTimer;
            this.$settingsForm.find('input, select').on('change input', () => {
                clearTimeout(previewTimer);
                previewTimer = setTimeout(() => this.updatePreview(), 500);
            });

            // Preview Modes
            $('.preview-mode').on('click', (e) => this.handlePreviewMode(e));

            // Test Email Modal
            $('.preview-test-email').on('click', () => this.$testEmailModal.fadeIn(200));
            $('.ncd-modal-close').on('click', () => this.$testEmailModal.fadeOut(200));
            $(window).on('click', (e) => {
                if ($(e.target).is('.ncd-modal')) {
                    this.$testEmailModal.fadeOut(200);
                }
            });

            // Test Email Form
            $('#test-email-form').on('submit', (e) => this.handleTestEmailSubmit(e));
        }

        handleTemplateSwitch(e) {
            const templateId = $(e.target).val();
            this.$previewFrame.addClass('ncd-preview-loading');
            
            $.post(ajaxurl, {
                action: 'ncd_switch_template',
                nonce: $('#ncd_template_nonce').val(),
                template_id: templateId
            }, (response) => this.handleTemplateSwitchResponse(response));
        }

        handleTemplateSwitchResponse(response) {
            if (response.success) {
                $('input[name="template_id"]').val(this.$templateSelector.val());
                
                this.updatePreview();
            } else {
                alert(response.data.message || ncdAdmin.messages.error);
                this.$previewFrame.removeClass('ncd-preview-loading');
            }
        }

        updatePreview() {
            const formData = this.$settingsForm.serialize();
            this.$previewFrame.addClass('ncd-preview-loading');
            
            $.post(ajaxurl, {
                action: 'ncd_preview_template',
                nonce: $('#ncd_template_nonce').val(),
                data: formData
            }, (response) => {
                if (response.success) {
                    this.$previewFrame.html(response.data.html);
                }
                this.$previewFrame.removeClass('ncd-preview-loading');
            });
        }

        handlePreviewMode(e) {
            const $button = $(e.target);
            $('.preview-mode').removeClass('active');
            $button.addClass('active');
            
            const mode = $button.data('mode');
            this.$previewFrame.removeClass('desktop mobile').addClass(mode);
        }

        handleTestEmailSubmit(e) {
            e.preventDefault();
            const email = $('#test-email').val();
            
            $.post(ajaxurl, {
                action: 'ncd_send_test_email',
                nonce: $('#ncd_template_nonce').val(),
                email: email,
                template_id: $('input[name="template_id"]').val()
            }, (response) => {
                if (response.success) {
                    alert(response.data.message);
                    this.$testEmailModal.fadeOut(200);
                } else {
                    alert(response.data.message || ncdAdmin.messages.error);
                }
            });
        }
    }

    // Exportiere für andere Module
    window.NCDTemplateManager = NCDTemplateManager;

})(jQuery);