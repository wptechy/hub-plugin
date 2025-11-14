/**
 * WPT Platform Admin JavaScript
 * 
 * @package WPT_Optica_Core
 */

(function($) {
    'use strict';

    const WPTAdmin = {
        
        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
            this.initSlugGeneration();
            this.initConfirmDialogs();
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            // Add any global event listeners here
        },

        /**
         * Auto-generate slug from name
         */
        initSlugGeneration: function() {
            const $nameField = $('#name');
            const $slugField = $('#slug');

            if ($nameField.length && $slugField.length && !$slugField.val()) {
                $nameField.on('input', function() {
                    const slug = $(this).val()
                        .toLowerCase()
                        .replace(/[^a-z0-9\s-]/g, '')
                        .replace(/\s+/g, '-')
                        .replace(/-+/g, '-')
                        .trim();
                    $slugField.val(slug);
                });
            }
        },

        /**
         * Confirm dialogs for dangerous actions
         */
        initConfirmDialogs: function() {
            $('[data-confirm]').on('click', function(e) {
                const message = $(this).data('confirm');
                if (!confirm(message)) {
                    e.preventDefault();
                    return false;
                }
            });
        },

        /**
         * Show notification
         */
        showNotice: function(message, type = 'success') {
            const $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
            $('.wrap > h1').after($notice);
            
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        },

        /**
         * AJAX helper
         */
        ajax: function(action, data, successCallback, errorCallback) {
            $.ajax({
                url: wptAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: action,
                    nonce: wptAdmin.nonce,
                    ...data
                },
                success: function(response) {
                    if (response.success) {
                        if (typeof successCallback === 'function') {
                            successCallback(response.data);
                        }
                    } else {
                        if (typeof errorCallback === 'function') {
                            errorCallback(response.data);
                        } else {
                            WPTAdmin.showNotice(response.data.message || wptAdmin.i18n.error, 'error');
                        }
                    }
                },
                error: function() {
                    if (typeof errorCallback === 'function') {
                        errorCallback();
                    } else {
                        WPTAdmin.showNotice(wptAdmin.i18n.error, 'error');
                    }
                }
            });
        }
    };

    /**
     * Tenants Management
     */
    const WPTTenants = {
        
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // Add tenant-specific functionality here
        }
    };

    /**
     * Modules Management
     */
    const WPTModules = {
        
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // Add module-specific functionality here
        }
    };

    /**
     * Releases Management
     */
    const WPTReleases = {
        
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // Add release-specific functionality here
        }
    };

    /**
     * Analytics
     */
    const WPTAnalytics = {
        
        init: function() {
            this.initChart();
        },

        initChart: function() {
            // Chart initialization
            // Can integrate Chart.js library here for production
            const canvas = document.getElementById('wpt-analytics-chart');
            if (!canvas) return;

            // Placeholder for chart implementation
            console.log('Analytics chart ready for initialization');
        }
    };

    /**
     * Document Ready
     */
    $(document).ready(function() {
        WPTAdmin.init();

        // Initialize page-specific modules
        if ($('.wpt-tenants').length) {
            WPTTenants.init();
        }

        if ($('.wpt-modules').length) {
            WPTModules.init();
        }

        if ($('.wpt-releases').length) {
            WPTReleases.init();
        }

        if ($('.wpt-analytics').length) {
            WPTAnalytics.init();
        }
    });

})(jQuery);
