(function($) {
    'use strict';

    class NCDFeedbackManager {
        constructor() {
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

            this.messages = window.ncdAdmin.messages;

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
                        ${sprintf(
                            __('%1$s/%2$s characters', 'newcustomer-discount'),
                            '<span class="count">' + currentLength + '</span>',
                            this.maxLength
                        )}
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
                    NCDBase.showNotice(response.data.message, response.data.type);
                    this.resetForm();
                } else {
                    NCDBase.showNotice(response.data.message, response.data.type);
                }
            })
            .fail(() => {
                NCDBase.showNotice(this.messages.error, 'error');
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

    window.NCDFeedbackManager = NCDFeedbackManager;

    $(document).ready(() => {
        if (typeof window.ncdAdmin !== 'undefined' && window.ncdAdmin.nonce) {
            window.ncdFeedbackManager = new NCDFeedbackManager();
        } else {
            console.error('Required ncdAdmin configuration missing');
        }
    });

})(jQuery);