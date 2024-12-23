(function($) {
    'use strict';

    class NCDAdmin {
        constructor() {
            this.initializeComponents();
        }

        initializeComponents() {
            try {

                if ($('.ncd-tabs').length > 0 && typeof NCDTabManager !== 'undefined') {
                    window.ncdTabManager = new NCDTabManager();
                }

                if ($('.ncd-customers-table').length > 0 && typeof NCDCustomerManager !== 'undefined') {
                    window.ncdCustomerManager = new NCDCustomerManager();
                }

                if (window.ncdAdmin && window.ncdAdmin.debug) {
                    console.log('Components initialized successfully');
                }
            } catch (error) {
                console.error('Error initializing components:', error);
            }
        }
    }

    // Warte auf DOMContentLoaded
    $(document).ready(() => {
        try {
            if (typeof window.ncdAdmin === 'undefined' || !window.ncdAdmin.nonce) {
                throw new Error('Required ncdAdmin configuration missing');
            }

            new NCDAdmin();
        } catch (error) {
            console.error('NCDAdmin initialization failed:', error);
        }
    });

})(jQuery);