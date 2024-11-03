(function($) {
    'use strict';

    class NCDBase {
        static showNotice(message, type = 'success') {
            const $notice = $(`
                <div class="ncd-notice ncd-notice-${type} ncd-fade">
                    <p>${message}</p>
                </div>
            `);

            $('.ncd-notices').prepend($notice);
            
            setTimeout(() => {
                $notice.fadeOut(() => $notice.remove());
            }, 5000);
        }
    }

    // Exportiere für andere Module
    window.NCDBase = NCDBase;

})(jQuery);