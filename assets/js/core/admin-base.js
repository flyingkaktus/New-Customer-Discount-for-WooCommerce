(function($) {
    'use strict';

    class NCDBase {
        static showNotice(message, type = 'success') {
            const $notice = $(`
                <div class="notice notice-${type} is-dismissible">
                    <p>${message}</p>
                </div>
            `);
            
            const $target = $('#wpbody-content').find('.wrap:first');
            if ($target.length) {
                $target.prepend($notice);
                
                if (window.wp?.notices) {
                    window.wp.notices.initialize();
                }

                setTimeout(() => {
                    $notice.fadeOut(() => $notice.remove());
                }, 5000);
            } else {
                $('#wpbody-content').prepend($notice);
            }
        }
    }

    window.NCDBase = NCDBase;

})(jQuery);