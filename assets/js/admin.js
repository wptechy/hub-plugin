/**
 * WPT Hub Plugin - Admin JavaScript
 */

(function($) {
    'use strict';

    const WPT_Admin = {
        init: function() {
            this.bindEvents();
            this.initTabs();
            this.initModals();
        },

        bindEvents: function() {
            // Confirm delete actions
            $('.wpt-delete-btn').on('click', function(e) {
                if (!confirm(wptAdmin.i18n.confirmDelete)) {
                    e.preventDefault();
                    return false;
                }
            });

            // Handle AJAX forms
            $('.wpt-ajax-form').on('submit', this.handleAjaxForm);

            // Module activation
            $('.wpt-activate-module').on('click', this.activateModule);
            $('.wpt-deactivate-module').on('click', this.deactivateModule);
        },

        initTabs: function() {
            $('.wpt-tabs a').on('click', function(e) {
                e.preventDefault();
                const target = $(this).attr('href');

                $('.wpt-tabs a').removeClass('active');
                $(this).addClass('active');

                $('.wpt-tab-content').hide();
                $(target).show();
            });

            // Show first tab by default
            $('.wpt-tabs a:first').click();
        },

        initModals: function() {
            $('.wpt-modal-trigger').on('click', function(e) {
                e.preventDefault();
                const modalId = $(this).data('modal');
                $('#' + modalId).fadeIn();
            });

            $('.wpt-modal-close, .wpt-modal-overlay').on('click', function() {
                $(this).closest('.wpt-modal').fadeOut();
            });
        },

        handleAjaxForm: function(e) {
            e.preventDefault();
            const $form = $(this);
            const $submit = $form.find('[type="submit"]');
            const originalText = $submit.text();

            $.ajax({
                url: wptAdmin.ajaxUrl,
                type: 'POST',
                data: $form.serialize(),
                beforeSend: function() {
                    $submit.prop('disabled', true).text('Saving...');
                },
                success: function(response) {
                    if (response.success) {
                        WPT_Admin.showNotice('success', wptAdmin.i18n.saved);
                        if (response.data.reload) {
                            location.reload();
                        }
                    } else {
                        WPT_Admin.showNotice('error', response.data.message || wptAdmin.i18n.error);
                    }
                },
                error: function() {
                    WPT_Admin.showNotice('error', wptAdmin.i18n.error);
                },
                complete: function() {
                    $submit.prop('disabled', false).text(originalText);
                }
            });
        },

        activateModule: function(e) {
            e.preventDefault();
            const $btn = $(this);
            const moduleSlug = $btn.data('module');

            $.ajax({
                url: wptAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wpt_activate_module',
                    nonce: wptAdmin.nonce,
                    module_slug: moduleSlug
                },
                beforeSend: function() {
                    $btn.prop('disabled', true);
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        WPT_Admin.showNotice('error', response.data.message);
                        $btn.prop('disabled', false);
                    }
                },
                error: function() {
                    WPT_Admin.showNotice('error', wptAdmin.i18n.error);
                    $btn.prop('disabled', false);
                }
            });
        },

        deactivateModule: function(e) {
            e.preventDefault();
            const $btn = $(this);
            const moduleSlug = $btn.data('module');

            if (!confirm('Are you sure you want to deactivate this module?')) {
                return;
            }

            $.ajax({
                url: wptAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wpt_deactivate_module',
                    nonce: wptAdmin.nonce,
                    module_slug: moduleSlug
                },
                beforeSend: function() {
                    $btn.prop('disabled', true);
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        WPT_Admin.showNotice('error', response.data.message);
                        $btn.prop('disabled', false);
                    }
                },
                error: function() {
                    WPT_Admin.showNotice('error', wptAdmin.i18n.error);
                    $btn.prop('disabled', false);
                }
            });
        },

        showNotice: function(type, message) {
            const $notice = $('<div class="wpt-notice wpt-notice-' + type + '">' + message + '</div>');
            $('.wrap > h1').after($notice);

            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 3000);
        },

        copyToClipboard: function(text) {
            const $temp = $('<input>');
            $('body').append($temp);
            $temp.val(text).select();
            document.execCommand('copy');
            $temp.remove();
            this.showNotice('success', 'Copied to clipboard!');
        }
    };

    // Initialize when DOM is ready
    $(document).ready(function() {
        WPT_Admin.init();
    });

    // Expose to window
    window.WPT_Admin = WPT_Admin;

})(jQuery);
