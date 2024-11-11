(function($) {
    'use strict';

    class NCDTemplateManager {
        constructor() {
<<<<<<< Updated upstream
            this.ajax = new NCDAjaxHandler();
            
            this.ajax = new NCDAjaxHandler();
            
=======
            // Ajax Handler initialisieren
            this.ajax = new NCDAjaxHandler();
            
            // DOM-Elemente
>>>>>>> Stashed changes
            this.$templateSelector = $('#template-selector');
            this.$activateButton = $('#activate-template');
            this.$previewFrame = $('.ncd-preview-frame');
            this.$settingsForm = $('#template-settings-form');

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
<<<<<<< Updated upstream
                if ($option.val() === this.activeTemplateId) {
=======
>>>>>>> Stashed changes
                    $option.addClass('active-template');
                }
            });
        }

        bindEvents() {
            this.$templateSelector.on('change', (e) => this.handleTemplateChange(e));
            this.$activateButton.on('click', (e) => this.handleTemplateActivation(e));
            this.$settingsForm.on('submit', (e) => this.handleSettingsSave(e));
            this.$settingsForm.find('input, select').on('change input', () => this.updatePreviewWithDelay());
<<<<<<< Updated upstream
            this.$settingsForm.find('input, select').on('change input', () => this.updatePreviewWithDelay());
            $('.preview-mode').on('click', (e) => this.handlePreviewMode(e));
            $('.preview-test-email').on('click', () => this.handleTestEmailClick());
        }

        handleTestEmailClick() {
            $('.notice.inline').remove();

            const emailForm = `
            <div class="notice notice-info inline" style="margin-top: 15px;">
                <p>
                    <input type="email"
                        id="quick-test-email"
                        placeholder="${
                            ncdAdmin.messages.enter_email || 
                            esc_attr__('Enter email address', 'newcustomer-discount')
                        }"
                        class="regular-text"
                    />
                    <button type="button" class="button button-primary" id="send-quick-test">
                        ${
                            ncdAdmin.messages.send_test || 
                            esc_html__('Send test email', 'newcustomer-discount')
                        }
                    </button>
                </p>
            </div>
        `;
            
            $('.ncd-preview-header').after(emailForm);
            
            $('#send-quick-test').on('click', () => {
                const email = $('#quick-test-email').val();
                if (!email) {
                    NCDBase.showNotice(ncdAdmin.messages.email_required, 'error');
                    return;
=======
            $('.preview-mode').on('click', (e) => this.handlePreviewMode(e));
            
            // Test Email Modal Events
            $('.preview-test-email').on('click', () => this.$testEmailModal.fadeIn(200));
            $('.ncd-modal-close').on('click', () => this.$testEmailModal.fadeOut(200));
            $(window).on('click', (e) => {
                if ($(e.target).is('.ncd-modal')) {
                    this.$testEmailModal.fadeOut(200);
>>>>>>> Stashed changes
                }
                this.sendTestEmail(email);
            });
<<<<<<< Updated upstream
        }

        sendTestEmail(email) {
            this.ajax.post('send_test_email', {
                email: email,
                template_id: $('input[name="template_id"]').val()
            }).then(response => {
                this.ajax.handleResponse(response, data => {
                    NCDBase.showNotice(data.message, 'success');
                    $('.notice.inline').slideUp(() => $(this).remove());
                }, error => {
                    NCDBase.showNotice(error, 'error');
                });
            });
        }

        updatePreviewWithDelay() {
            clearTimeout(this.previewTimer);
            this.previewTimer = setTimeout(() => this.updatePreview(), 300);
            this.previewTimer = setTimeout(() => this.updatePreview(), 300);
=======
            $('#test-email-form').on('submit', (e) => this.handleTestEmailSubmit(e));
        }

        updatePreviewWithDelay() {
            clearTimeout(this.previewTimer);
            this.previewTimer = setTimeout(() => this.updatePreview(), 300);
>>>>>>> Stashed changes
        }

        handleTemplateChange(e) {
            const templateId = $(e.target).val();
            this.currentTemplateId = templateId;
<<<<<<< Updated upstream
            this.currentTemplateId = templateId;
=======
>>>>>>> Stashed changes
            this.$previewFrame.addClass('ncd-preview-loading');
            
            $('input[name="template_id"]').val(templateId);
            this.updateActivateButtonState();
<<<<<<< Updated upstream
            this.updateActivateButtonState();
            
            this.ajax.post('get_template_settings', {
            this.ajax.post('get_template_settings', {
=======
            
            this.ajax.post('get_template_settings', {
>>>>>>> Stashed changes
                template_id: templateId
            }).then(response => {
                this.ajax.handleResponse(response, data => {
                    this.updateSettingsForm(data.settings);
<<<<<<< Updated upstream
            }).then(response => {
                this.ajax.handleResponse(response, data => {
                    this.updateSettingsForm(data.settings);
                    this.updatePreview();
                }, error => {
                    NCDBase.showNotice(error, 'error');
                });
                }, error => {
                    NCDBase.showNotice(error, 'error');
=======
                    this.updatePreview();
>>>>>>> Stashed changes
                });
            });
        }

        handleTemplateActivation(e) {
            e.preventDefault();

            $('.notice.inline').remove();

            const confirmationNotice = `
            <div class="notice notice-warning inline">
                <p>
                    ${
                        ncdAdmin.messages.confirm_template_activation || 
                        esc_html__('Do you want to activate this template?', 'newcustomer-discount')
                    }
                    <button type="button" class="button button-primary confirm-activation">
                        ${
                            ncdAdmin.messages.yes || 
                            esc_html__('Yes', 'newcustomer-discount')
                        }
                    </button>
                    <button type="button" class="button cancel-activation">
                        ${
                            ncdAdmin.messages.no || 
                            esc_html__('No', 'newcustomer-discount')
                        }
                    </button>
                </p>
            </div>
        `;
            
<<<<<<< Updated upstream
            $(e.target).after(confirmationNotice);
            
            $('.confirm-activation').on('click', () => {
                this.activateTemplate();
                $('.notice.inline').remove();
            });
            
            $('.cancel-activation').on('click', () => {
                $('.notice.inline').remove();
            });
        }

        activateTemplate() {
=======
            if (!confirm(ncdAdmin.messages.confirm_template_activation)) {
                return;
            }
        
>>>>>>> Stashed changes
            this.ajax.post('activate_template', {
                template_id: this.currentTemplateId
            }).then(response => {
                this.ajax.handleResponse(response, data => {
                    this.activeTemplateId = this.currentTemplateId;
                    this.updateActivateButtonState();
                    this.updateActiveTemplateInfo(data.template_name);
<<<<<<< Updated upstream
                    NCDBase.showNotice(data.message, 'success');
                }, error => {
                    NCDBase.showNotice(error, 'error');
                });
                    this.updateActiveTemplateInfo(data.template_name);
                    NCDBase.showNotice(data.message, 'success');
                }, error => {
                    NCDBase.showNotice(error, 'error');
=======
                    this.showNotice(data.message, 'success');
>>>>>>> Stashed changes
                });
            });
        }

        handleSettingsSave(e) {
            e.preventDefault();

            $('.notice').remove();
            
            this.ajax.post('save_template_settings', {
                template_id: this.$templateSelector.val(),
                settings: this.$settingsForm.serialize()
            }).then(response => {
                this.ajax.handleResponse(response, () => {
<<<<<<< Updated upstream
                    NCDBase.showNotice(ncdAdmin.messages.settings_saved, 'success');

            $('.notice').remove();
            
            this.ajax.post('save_template_settings', {
                template_id: this.$templateSelector.val(),
                settings: this.$settingsForm.serialize()
            }).then(response => {
                this.ajax.handleResponse(response, () => {
                    NCDBase.showNotice(ncdAdmin.messages.settings_saved, 'success');
                    this.updatePreview();
                }, error => {
                    NCDBase.showNotice(error, 'error');
                });
                }, error => {
                    NCDBase.showNotice(error, 'error');
=======
                    this.showNotice(ncdAdmin.messages.settings_saved, 'success');
                    this.updatePreview();
>>>>>>> Stashed changes
                });
            });
        }

<<<<<<< Updated upstream
        handlePreviewMode(e) {
            const $button = $(e.target);
            $('.preview-mode').removeClass('active');
            $button.addClass('active');
            this.$previewFrame.removeClass('desktop mobile').addClass($button.data('mode'));
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
                }, error => {
                    NCDBase.showNotice(error, 'error');
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

            this.ajax.post('preview_template', {
                data: this.$settingsForm.serialize()
            }).then(response => {
                this.ajax.handleResponse(response, data => {
                    this.$previewFrame.html(data.html);
                    this.applyPreviewStyles(this.getCurrentSettings());
                    this.$previewFrame.removeClass('ncd-preview-loading');
                }, error => {
                    NCDBase.showNotice(error, 'error');
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

=======
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

>>>>>>> Stashed changes
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
<<<<<<< Updated upstream
            this.$previewFrame.find('.button')
                .removeClass('minimal rounded pill')
                .addClass(settings.buttonStyle);
=======
>>>>>>> Stashed changes
        
            this.$previewFrame.find('.email-wrapper')
                .removeClass('centered full-width')
                .addClass(settings.layoutType);
<<<<<<< Updated upstream
            this.$previewFrame.find('.email-wrapper')
                .removeClass('centered full-width')
                .addClass(settings.layoutType);
        
            this.$previewFrame.find('.ncd-email')
                .css('font-family', settings.fontFamily);
            this.$previewFrame.find('.ncd-email')
                .css('font-family', settings.fontFamily);
=======
        
            this.$previewFrame.find('.ncd-email')
                .css('font-family', settings.fontFamily);
>>>>>>> Stashed changes
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
<<<<<<< Updated upstream
                $option.text($option.text().replace(' (Aktiv)', ''))
                      .removeClass('active-template');
=======
>>>>>>> Stashed changes
                
                if (isActive) {
                    $option.text($option.text() + ' (Aktiv)')
                          .addClass('active-template');
<<<<<<< Updated upstream
                    $option.text($option.text() + ' (Aktiv)')
                          .addClass('active-template');
                }
            });
        }
=======
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
>>>>>>> Stashed changes
    }

    // Globale Verfügbarkeit
    window.NCDTemplateManager = NCDTemplateManager;

})(jQuery);