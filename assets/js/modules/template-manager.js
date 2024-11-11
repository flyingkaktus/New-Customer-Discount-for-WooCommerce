(function($) {
    'use strict';

    class NCDTemplateManager {
        constructor() {
            // Ajax Handler initialisieren
            this.ajax = new NCDAjaxHandler();
            
            // DOM-Elemente
            this.$templateSelector = $('#template-selector');
            this.$activateButton = $('#activate-template');
            this.$previewFrame = $('.ncd-preview-frame');
            this.$settingsForm = $('#template-settings-form');
            this.$testEmailModal = $('#test-email-modal');

            this.activeTemplateId = this.$templateSelector.data('active-template');
            this.currentTemplateId = this.$templateSelector.val();
            
            this.initializeTemplateStatus();
            this.bindEvents();
            this.updatePreview();
        }

        initializeTemplateStatus() {
            this.updateActivateButtonState();
            this.$templateSelector.find('option').each((_, option) => {
                const $option = $(option);
                if ($option.val() === this.activeTemplateId) {
                    $option.addClass('active-template');
                }
            });
        }

        bindEvents() {
            this.$templateSelector.on('change', (e) => this.handleTemplateChange(e));
            this.$activateButton.on('click', (e) => this.handleTemplateActivation(e));
            this.$settingsForm.on('submit', (e) => this.handleSettingsSave(e));
            this.$settingsForm.find('input, select').on('change input', () => this.updatePreviewWithDelay());
            $('.preview-mode').on('click', (e) => this.handlePreviewMode(e));
            
            // Test Email Modal Events
            $('.preview-test-email').on('click', () => this.$testEmailModal.fadeIn(200));
            $('.ncd-modal-close').on('click', () => this.$testEmailModal.fadeOut(200));
            $(window).on('click', (e) => {
                if ($(e.target).is('.ncd-modal')) {
                    this.$testEmailModal.fadeOut(200);
                }
            });
            $('#test-email-form').on('submit', (e) => this.handleTestEmailSubmit(e));
        }

        updatePreviewWithDelay() {
            clearTimeout(this.previewTimer);
            this.previewTimer = setTimeout(() => this.updatePreview(), 300);
        }

        handleTemplateChange(e) {
            const templateId = $(e.target).val();
            this.currentTemplateId = templateId;
            this.$previewFrame.addClass('ncd-preview-loading');
            
            $('input[name="template_id"]').val(templateId);
            this.updateActivateButtonState();
            
            this.ajax.post('get_template_settings', {
                template_id: templateId
            }).then(response => {
                this.ajax.handleResponse(response, data => {
                    this.updateSettingsForm(data.settings);
                    this.updatePreview();
                });
            });
        }

        handleTemplateActivation(e) {
            e.preventDefault();
            
            if (!confirm(ncdAdmin.messages.confirm_template_activation)) {
                return;
            }
        
            this.ajax.post('activate_template', {
                template_id: this.currentTemplateId
            }).then(response => {
                this.ajax.handleResponse(response, data => {
                    this.activeTemplateId = this.currentTemplateId;
                    this.updateActivateButtonState();
                    this.updateActiveTemplateInfo(data.template_name);
                    this.showNotice(data.message, 'success');
                });
            });
        }

        handleSettingsSave(e) {
            e.preventDefault();
            
            this.ajax.post('save_template_settings', {
                template_id: this.$templateSelector.val(),
                settings: this.$settingsForm.serialize()
            }).then(response => {
                this.ajax.handleResponse(response, () => {
                    this.showNotice(ncdAdmin.messages.settings_saved, 'success');
                    this.updatePreview();
                });
            });
        }

        handleTestEmailSubmit(e) {
            e.preventDefault();
            
            this.ajax.post('send_test_email', {
                email: $('#test-email').val(),
                template_id: $('input[name="template_id"]').val()
            }).then(response => {
                this.ajax.handleResponse(response, data => {
                    alert(data.message);
                    this.$testEmailModal.fadeOut(200);
                });
            });
        }

        handlePreviewMode(e) {
            const $button = $(e.target);
            $('.preview-mode').removeClass('active');
            $button.addClass('active');
            this.$previewFrame.removeClass('desktop mobile').addClass($button.data('mode'));
        }

        updatePreview() {
            this.$previewFrame.addClass('ncd-preview-loading');
            
            this.ajax.post('preview_template', {
                data: this.$settingsForm.serialize()
            }).then(response => {
                this.ajax.handleResponse(response, data => {
                    this.$previewFrame.html(data.html);
                    this.applyPreviewStyles(this.getCurrentSettings());
                    this.$previewFrame.removeClass('ncd-preview-loading');
                });
            });
        }

        updateSettingsForm(settings) {
            Object.keys(settings).forEach(key => {
                const $input = $(`[name="settings[${key}]"]`);
                if ($input.length) {
                    $input.val(settings[key]);
                }
            });
        }

        getCurrentSettings() {
            return {
                primaryColor: $('#primary_color').val(),
                secondaryColor: $('#secondary_color').val(),
                textColor: $('#text_color').val(),
                backgroundColor: $('#background_color').val(),
                fontFamily: $('#font_family').val(),
                buttonStyle: $('#button_style').val(),
                layoutType: $('#layout_type').val()
            };
        }

        applyPreviewStyles(settings) {
            this.$previewFrame.css({
                '--primary-color': settings.primaryColor,
                '--secondary-color': settings.secondaryColor,
                '--text-color': settings.textColor,
                '--background-color': settings.backgroundColor,
                '--font-family': settings.fontFamily
            });
        
            this.$previewFrame.find('.button')
                .removeClass('minimal rounded pill')
                .addClass(settings.buttonStyle);
        
            this.$previewFrame.find('.email-wrapper')
                .removeClass('centered full-width')
                .addClass(settings.layoutType);
        
            this.$previewFrame.find('.ncd-email')
                .css('font-family', settings.fontFamily);
        }

        updateActivateButtonState() {
            const currentId = this.$templateSelector.val();
            this.$activateButton.prop('disabled', currentId === this.activeTemplateId);
        }

        updateActiveTemplateInfo(templateName) {
            $('.ncd-active-template-info strong').text(templateName);
            
            const currentId = this.$templateSelector.val();
            this.$templateSelector.find('option').each(function() {
                const $option = $(this);
                const isActive = $option.val() === currentId;
                
                $option.text($option.text().replace(' (Aktiv)', ''))
                      .removeClass('active-template');
                
                if (isActive) {
                    $option.text($option.text() + ' (Aktiv)')
                          .addClass('active-template');
                }
            });
        }

        showNotice(message, type = 'success') {
            const $notice = $(`
                <div class="notice notice-${type} is-dismissible">
                    <p>${message}</p>
                </div>
            `);
            
            $('.ncd-notices').html($notice);
            
            if (window.wp?.notices) {
                window.wp.notices.initialize();
            }
        }
    }

    // Globale Verfügbarkeit
    window.NCDTemplateManager = NCDTemplateManager;

})(jQuery);