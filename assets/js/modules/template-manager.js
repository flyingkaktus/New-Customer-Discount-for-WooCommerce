(function($) {
    'use strict';

    class NCDTemplateManager {
        constructor() {
            this.$templateSelector = $('#template-selector');
            this.$activateButton = $('#activate-template');
            this.$previewFrame = $('.ncd-preview-frame');
            this.$settingsForm = $('#template-settings-form');
            this.$testEmailModal = $('#test-email-modal');

            this.activeTemplateId = this.$templateSelector.data('active-template');
            this.currentTemplateId = this.$templateSelector.val();
            
            this.initializeTemplateStatus();
            this.bindEvents();
        }

        initializeTemplateStatus() {
            // Setze Button Status
            this.updateActivateButtonState();
            
            // Setze Dropdown Status
            this.$templateSelector.find('option').each((_, option) => {
                const $option = $(option);
                const isActive = $option.val() === this.activeTemplateId;
                
                if (isActive) {
                    $option.addClass('active-template');
                }
            });
        }

        bindEvents() {
            // Template Selector und Aktivierung
            this.$templateSelector.on('change', (e) => this.handleTemplateChange(e));
            this.$activateButton.on('click', (e) => this.handleTemplateActivation(e));
            
            // Settings Form und Preview
            this.$settingsForm.on('submit', (e) => this.handleSettingsSave(e));
            let previewTimer;
            this.$settingsForm.find('input, select').on('change input', () => {
                clearTimeout(previewTimer);
                previewTimer = setTimeout(() => this.updatePreview(), 500);
            });

            // Preview Modi
            $('.preview-mode').on('click', (e) => this.handlePreviewMode(e));

            // Test Email Modal
            $('.preview-test-email').on('click', () => this.$testEmailModal.fadeIn(200));
            $('.ncd-modal-close').on('click', () => this.$testEmailModal.fadeOut(200));
            $(window).on('click', (e) => {
                if ($(e.target).is('.ncd-modal')) {
                    this.$testEmailModal.fadeOut(200);
                }
            });

            // Test Email Formular
            $('#test-email-form').on('submit', (e) => this.handleTestEmailSubmit(e));
        }

        handleTemplateChange(e) {
            const templateId = $(e.target).val();
            this.currentTemplateId = templateId; // Diese Zeile fehlt aktuell
            this.$previewFrame.addClass('ncd-preview-loading');
            
            // Aktualisiere verstecktes Template-ID Feld
            $('input[name="template_id"]').val(templateId);
            
            // Update Button Status
            this.updateActivateButtonState(); // Diese Zeile sollte direkt nach der ID-Aktualisierung kommen
            
            // Hole Template-spezifische Einstellungen
            $.post(ajaxurl, {
                action: 'ncd_get_template_settings',
                nonce: $('#ncd_template_nonce').val(),
                template_id: templateId
            }, (response) => {
                if (response.success) {
                    this.updateSettingsForm(response.data.settings);
                    this.updatePreview();
                }
            });
        }

        updateSettingsForm(settings) {
            // Aktualisiere alle Einstellungsfelder mit den template-spezifischen Werten
            Object.keys(settings).forEach(key => {
                const $input = $(`[name="settings[${key}]"]`);
                if ($input.length) {
                    $input.val(settings[key]);
                }
            });
        }
        
        handleTemplateActivation(e) {
            e.preventDefault();
            
            if (!confirm(ncdAdmin.messages.confirm_template_activation)) {
                return;
            }
        
            const templateId = this.currentTemplateId; // Nutze die gespeicherte currentTemplateId
            
            $.post(ajaxurl, {
                action: 'ncd_activate_template',
                nonce: $('#ncd_template_nonce').val(),
                template_id: templateId
            }, (response) => {
                if (response.success) {
                    this.activeTemplateId = templateId;
                    this.updateActivateButtonState();
                    this.updateActiveTemplateInfo(response.data.template_name);
                    this.showNotice(response.data.message, 'success');
                } else {
                    this.showNotice(response.data.message || ncdAdmin.messages.error, 'error');
                }
            });
        }

        handleSettingsSave(e) {
            e.preventDefault();
            
            this.saveTemplateSettings().then((response) => {
                if (response.success) {
                    this.showNotice(ncdAdmin.messages.settings_saved, 'success');
                    this.updatePreview();
                } else {
                    this.showNotice(response.data.message, 'error');
                }
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

        saveTemplateSettings() {
            return $.post(ajaxurl, {
                action: 'ncd_save_template_settings',
                nonce: $('#ncd_template_nonce').val(),
                template_id: this.$templateSelector.val(),
                settings: this.$settingsForm.serialize()
            });
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

        updateActivateButtonState() {
            const currentId = this.$templateSelector.val();
            this.$activateButton.prop('disabled', currentId === this.activeTemplateId);
        }

        updateActiveTemplateInfo(templateName) {
            // Aktualisiere Status-Anzeige
            $('.ncd-active-template-info strong').text(templateName);
            
            // Aktualisiere Dropdown-Markierungen
            const currentId = this.$templateSelector.val();
            this.$templateSelector.find('option').each(function() {
                const $option = $(this);
                const isActive = $option.val() === currentId;
                
                // Entferne "(Aktiv)" von allen Optionen und CSS-Klasse
                $option.text($option.text().replace(' (Aktiv)', ''));
                $option.removeClass('active-template');
                
                // Füge "(Aktiv)" und CSS-Klasse zur neu aktivierten Option hinzu
                if (isActive) {
                    $option.text($option.text() + ' (Aktiv)');
                    $option.addClass('active-template');
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
            
            if (window.wp && window.wp.notices) {
                window.wp.notices.initialize();
            }
        }
    }

    window.NCDTemplateManager = NCDTemplateManager;

})(jQuery);