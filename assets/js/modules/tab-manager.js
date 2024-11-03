(function($) {
    'use strict';

    class NCDTabManager {
        constructor() {
            this.$tabs = $('.ncd-tabs');
            this.$tabButtons = $('.nav-tab-wrapper .nav-tab');
            this.$tabContents = $('.ncd-tab-content');
            this.initialize();
        }

        initialize() {
            this.bindEvents();
            this.initializeActiveTab();
        }

        bindEvents() {
            this.$tabButtons.on('click', (e) => {
                e.preventDefault();
                const $target = $(e.currentTarget);
                const tabId = $target.attr('href');
                this.activateTab(tabId);
            });

            $(window).on('popstate', () => {
                this.initializeActiveTab();
            });
        }

        initializeActiveTab() {
            const activeTabId = window.location.hash || `#${ncdTabs.defaultTab}`;
            this.activateTab(activeTabId, false);
        }

        activateTab(tabId, updateHistory = true) {
            this.$tabButtons.removeClass('nav-tab-active');
            this.$tabContents.removeClass('active');

            this.$tabButtons.filter(`[href="${tabId}"]`).addClass('nav-tab-active');
            $(tabId).addClass('active');

            if (updateHistory) {
                history.pushState(null, null, tabId);
            }

            $(document).trigger('ncd_tab_changed', [tabId.replace('#', '')]);
            
            $('html, body').animate({ scrollTop: 0 }, 300);
        }
    }

    // Exportiere für andere Module
    window.NCDTabManager = NCDTabManager;

})(jQuery);