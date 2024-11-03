(function($) {
    'use strict';

    class NCDCustomerManager {
        constructor() {
            this.$testEmailForm = $('.ncd-test-email-form');
            this.$customerTable = $('.ncd-customers-table');
            this.$filterForm = $('.ncd-filter-form');
            this.originalButtonText = $('.ncd-send-discount').text();
            this.bindEvents();
        }

        bindEvents() {
            this.$testEmailForm.on('submit', (e) => this.handleTestEmailSubmit(e));
            this.$customerTable.on('click', '.ncd-send-discount', (e) => this.handleDiscountSend(e));
            this.$filterForm.on('change', 'select, input', () => this.handleFilterChange());
        }

        handleTestEmailSubmit(e) {
            e.preventDefault();
            const $form = $(e.currentTarget);
            const email = $form.find('input[name="test_email"]').val();

            if (!this.validateEmail(email)) {
                NCDBase.showNotice('Bitte geben Sie eine gültige E-Mail-Adresse ein.', 'error');
                return;
            }

            if (!confirm(`Möchten Sie eine Test-E-Mail an ${email} senden?`)) {
                return;
            }

            this.submitForm($form);
        }

        handleDiscountSend(e) {
            e.preventDefault();
            const $button = $(e.currentTarget);
            const email = $button.data('email');
            const firstName = $button.data('first-name');
            const lastName = $button.data('last-name');

            if (!confirm(ncdAdmin.messages.confirm_send)) {
                return;
            }

            $button.prop('disabled', true)
                .addClass('updating-message')
                .text(ncdAdmin.messages.sending);

            $.post(ajaxurl, {
                action: 'ncd_send_discount',
                nonce: ncdAdmin.nonce,
                email: email,
                first_name: firstName,
                last_name: lastName
            })
            .done((response) => {
                if (response.success) {
                    location.reload();
                } else {
                    NCDBase.showNotice(response.data.message || ncdAdmin.messages.error, 'error');
                    $button.prop('disabled', false)
                        .removeClass('updating-message')
                        .text(this.originalButtonText);
                }
            })
            .fail(() => {
                NCDBase.showNotice(ncdAdmin.messages.error, 'error');
                $button.prop('disabled', false)
                    .removeClass('updating-message')
                    .text(this.originalButtonText);
            });
        }

        handleFilterChange() {
            this.$filterForm.submit();
        }

        validateEmail(email) {
            const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
            return re.test(email.toLowerCase());
        }

        submitForm($form) {
            $form.addClass('ncd-loading');
            $form.submit();
        }
    }

    // Exportiere für andere Module
    window.NCDCustomerManager = NCDCustomerManager;

})(jQuery);