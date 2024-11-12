(function($) {
    'use strict';

    class NCDAjaxHandler {
        constructor() {
            this.ajaxurl = ajaxurl;
            this.nonce = ncdAdmin.nonce;
        }

        post(action, data = {}) {
            return $.post(this.ajaxurl, {
                ...data,
                action: `ncd_${action}`,
                nonce: this.nonce
            });
        }

        handleResponse(response, successCallback, errorCallback) {
            if (response.success) {
                if (successCallback) successCallback(response.data);
            } else {
                if (errorCallback) {
                    errorCallback(response.data?.message || ncdAdmin.messages.error);
                } else {
                    this.showError(response.data?.message || ncdAdmin.messages.error);
                }
            }
        }

        showError(message) {
            alert(message);
        }

        showSuccess(message) {
            alert(message);
        }

        getTemplateSettings(templateId) {
            return this.post('get_template_settings', { template_id: templateId });
        }

        activateTemplate(templateId) {
            return this.post('activate_template', { template_id: templateId });
        }

        saveTemplateSettings(templateId, settings) {
            return this.post('save_template_settings', {
                template_id: templateId,
                settings: settings
            });
        }

        previewTemplate(formData) {
            return this.post('preview_template', { data: formData });
        }

        sendTestEmail(email, templateId) {
            return this.post('send_test_email', {
                email: email,
                template_id: templateId
            });
        }
    }

    // Global verfügbar machen
    window.NCDAjaxHandler = NCDAjaxHandler;

})(jQuery);