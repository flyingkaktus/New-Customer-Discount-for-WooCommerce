(function($) {
    'use strict';

    class NCDFeedbackManager {
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
            this.$form = $('.ncd-feedback-form');
            this.$type = $('#feedback_type');
            this.$content = $('#feedback_content');
            this.$systemInfo = $('#include_system_info');
            this.$submitButton = this.$form.find('button[type="submit"]');
            this.maxLength = 2000;

            // Messages aus ncdAdmin übernehmen
            this.messages = window.ncdAdmin.messages || {
                error: 'Ein Fehler ist aufgetreten',
                sending: 'Sende...',
                feedback_required: 'Bitte geben Sie Ihr Feedback ein.',
                submit_feedback: 'Feedback senden'
            };

            this.bindEvents();
            this.init();
        }

        init() {
            this.handleFeedbackType();
            this.toggleSystemInfo();
        }

        bindEvents() {
            this.$type.on('change', () => this.handleFeedbackType());
            this.$systemInfo.on('change', () => this.toggleSystemInfo());
            this.$content.on('input', () => this.updateCharCounter());
            this.$form.on('submit', (e) => this.handleSubmit(e));
        }

        handleFeedbackType() {
            const type = this.$type.val();
            
            $('.feedback-hint').fadeOut(200, () => {
                $(`.feedback-hint.${type}`).fadeIn(200);
            });
            
            $('.bug-specific')[type === 'bug' ? 'slideDown' : 'slideUp']();
        }

        toggleSystemInfo() {
            $('#system-info-preview')[
                this.$systemInfo.prop('checked') ? 'slideDown' : 'slideUp'
            ]();
        }

        updateCharCounter() {
            const currentLength = this.$content.val().length;
            let $counter = $('#feedback-char-counter');
            
            if (!$counter.length) {
                this.$content.after(`
                    <p class="description" id="feedback-char-counter">
                        <span class="count">${currentLength}</span>/${this.maxLength} Zeichen
                    </p>
                `);
                $counter = $('#feedback-char-counter');
            } else {
                $counter.find('.count').text(currentLength);
            }
            
            $counter.toggleClass('char-limit-warning', currentLength >= this.maxLength * 0.9);
        }

        validateForm() {
            const content = this.$content.val().trim();
            
            if (!content) {
                alert(this.messages.feedback_required);
                return false;
            }
            
            return true;
        }

        handleSubmit(e) {
            e.preventDefault();
            
            if (!this.validateForm()) {
                return;
            }
            
            this.submitFeedback();
        }

        submitFeedback() {
            this.$submitButton
                .prop('disabled', true)
                .addClass('updating-message')
                .text(this.messages.sending);
            
            $.post(ajaxurl, {
                action: 'ncd_submit_feedback',
                nonce: window.ncdAdmin.nonce,
                feedback_type: this.$type.val(),
                feedback_content: this.$content.val(),
                include_system_info: this.$systemInfo.prop('checked') ? 1 : 0,
                bug_version: $('#bug_version').val()
            })
            .done((response) => {
                if (response.success) {
                    alert(response.data.message || 'Vielen Dank für Ihr Feedback!');
                    this.resetForm();
                } else {
                    alert(response.data.message || this.messages.error);
                }
            })
            .fail(() => {
                alert(this.messages.error);
            })
            .always(() => {
                this.$submitButton
                    .prop('disabled', false)
                    .removeClass('updating-message')
                    .text(this.messages.submit_feedback);
            });
        }

        resetForm() {
            this.$form[0].reset();
            this.handleFeedbackType();
            this.toggleSystemInfo();
            $('#feedback-char-counter').remove();
        }
    }

    // Globale Verfügbarkeit für andere Module
    window.NCDFeedbackManager = NCDFeedbackManager;

    // Initialisierung nur wenn ncdAdmin verfügbar ist
    $(document).ready(() => {
        if (typeof window.ncdAdmin !== 'undefined' && window.ncdAdmin.nonce) {
            window.ncdFeedbackManager = new NCDFeedbackManager();
        } else {
            console.error('Required ncdAdmin configuration missing');
        }
    });

})(jQuery);