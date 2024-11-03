(function($) {
    'use strict';

    class NCDAdmin {
        constructor() {
            this.initializeComponents();
        }

        initializeComponents() {
            // Initialisiere Tab Manager falls benötigt
            if ($('.ncd-tabs').length > 0) {
                window.ncdTabManager = new NCDTabManager();
            }

            // Initialisiere Customer Manager falls benötigt
            if ($('.ncd-customers-table').length > 0) {
                new NCDCustomerManager();
            }
        }
    }

    // Initialisiere beim Document Ready
    $(document).ready(() => {
        window.ncdAdmin = new NCDAdmin();
    });

})(jQuery);