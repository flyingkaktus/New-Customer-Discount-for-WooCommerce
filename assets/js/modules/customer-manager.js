(function($) {
    'use strict';

    class NCDCustomerManager {
        constructor() {
            // Überprüfe ob ncdAdmin verfügbar ist
            if (typeof window.ncdAdmin === 'undefined') {
                console.error('ncdAdmin object not found');
                return;
            }

            if (!window.ncdAdmin.nonce) {
                console.error('ncdAdmin.nonce not found');
                return;
            }

            // DOM-Elemente
            this.$testEmailForm = $('.ncd-test-email-form');
            this.$customerTable = $('.ncd-customers-table');
            this.$filterForm = $('.ncd-filter-form');
            this.originalButtonText = $('.ncd-send-discount').text();
            
            // Speichere nonce direkt aus ncdAdmin
            this.nonce = window.ncdAdmin.nonce;
            
            // Messages aus ncdAdmin übernehmen
            this.messages = window.ncdAdmin.messages || {
                error: 'Ein Fehler ist aufgetreten',
                email_required: 'Bitte geben Sie eine E-Mail-Adresse ein',
                confirm_test: 'Möchten Sie eine Test-E-Mail senden?',
                confirm_send: 'Möchten Sie einen Rabattcode senden?',
                sending: 'Sende...'
            };

            this.bindEvents();
            
            if (window.console && window.console.log) {
                console.log('NCDCustomerManager initialized with:', {
                    nonce: this.nonce ? 'present' : 'missing',
                    messages: this.messages
                });
            }
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
                alert(this.messages.email_required);
                return;
            }

            if (!confirm(this.messages.confirm_test)) {
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

            if (!confirm(this.messages.confirm_send)) {
                return;
            }

            $button.prop('disabled', true)
                .addClass('updating-message')
                .text(this.messages.sending);

            $.post(ajaxurl, {
                action: 'ncd_send_discount',
                nonce: this.nonce,
                email: email,
                first_name: firstName,
                last_name: lastName
            })
            .done((response) => {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data?.message || this.messages.error);
                    this.resetButton($button);
                }
            })
            .fail(() => {
                alert(this.messages.error);
                this.resetButton($button);
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
            const $submitButton = $form.find('button[type="submit"]');
            const originalText = $submitButton.text();

            $submitButton.prop('disabled', true)
                .addClass('updating-message')
                .text(this.messages.sending);

            $.post(ajaxurl, {
                action: 'ncd_send_test_email',
                nonce: this.nonce,
                email: $form.find('input[name="test_email"]').val()
            })
            .done((response) => {
                if (response.success) {
                    alert(response.data.message);
                    $form.find('input[name="test_email"]').val('');
                } else {
                    alert(response.data.message || this.messages.error);
                }
            })
            .fail(() => {
                alert(this.messages.error);
            })
            .always(() => {
                $submitButton.prop('disabled', false)
                    .removeClass('updating-message')
                    .text(originalText);
            });
        }

        resetButton($button) {
            $button.prop('disabled', false)
                .removeClass('updating-message')
                .text(this.originalButtonText);
        }
    }

    // Globale Verfügbarkeit für andere Module
    window.NCDCustomerManager = NCDCustomerManager;

    // Initialisierung nur wenn ncdAdmin verfügbar ist
    $(document).ready(() => {
        if (typeof window.ncdAdmin !== 'undefined' && window.ncdAdmin.nonce) {
            window.ncdCustomerManager = new NCDCustomerManager();
        } else {
            console.error('Required ncdAdmin configuration missing');
        }
    });

})(jQuery);