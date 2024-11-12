(function($) {
    'use strict';

    class NCDTemplateManager {
        constructor() {
            this.ajax = new NCDAjaxHandler();
            
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
            $('.preview-test-email').on('click', () => this.handleTestEmailClick());
        }

        handleTestEmailClick() {
            $('.notice.inline').remove();

            const emailForm = `
                <div class="notice notice-info inline" style="margin-top: 15px;">
                    <p>
                        <input type="email"
                            id="quick-test-email"
                            placeholder="${ncdAdmin.messages.enter_email || 'Enter email address'}"
                            class="regular-text"
                        />
                        <button type="button" class="button button-primary" id="send-quick-test">
                            ${ncdAdmin.messages.send_test || 'Send test email'}
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
                }
                this.sendTestEmail(email);
            });
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
                }, error => {
                    NCDBase.showNotice(error, 'error');
                });
            });
        }

        handleTemplateActivation(e) {
            e.preventDefault();
            $('.notice.inline').remove();

            const confirmationNotice = `
                <div class="notice notice-warning inline">
                    <p>
                        ${ncdAdmin.messages.confirm_template_activation || 'Do you want to activate this template?'}
                        <button type="button" class="button button-primary confirm-activation">
                            ${ncdAdmin.messages.yes || 'Yes'}
                        </button>
                        <button type="button" class="button cancel-activation">
                            ${ncdAdmin.messages.no || 'No'}
                        </button>
                    </p>
                </div>
            `;
            
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
            this.ajax.post('activate_template', {
                template_id: this.currentTemplateId
            }).then(response => {
                this.ajax.handleResponse(response, data => {
                    this.activeTemplateId = this.currentTemplateId;
                    this.updateActivateButtonState();
                    this.updateActiveTemplateInfo(data.template_name);
                    NCDBase.showNotice(data.message, 'success');
                }, error => {
                    NCDBase.showNotice(error, 'error');
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
                    NCDBase.showNotice(ncdAdmin.messages.settings_saved, 'success');
                    this.updatePreview();
                }, error => {
                    NCDBase.showNotice(error, 'error');
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
    }

    window.NCDTemplateManager = NCDTemplateManager;

})(jQuery);