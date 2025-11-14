/**
 * WPT Sync Configuration V2 JavaScript
 *
 * @package WPT_Optica_Core
 * @since 1.0.0
 */

(function($) {
    'use strict';

    const WPTSyncConfig = {

        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
            this.loadInitialFields();
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            // Tab switching (already handled in inline JS in view)

            // Field group checkbox toggle
            $('.field-group-checkbox').on('change', this.handleFieldGroupToggle.bind(this));

            // Save configuration
            $('#save-config-btn').on('click', this.saveConfiguration.bind(this));
        },

        /**
         * Load fields for already checked field groups
         */
        loadInitialFields: function() {
            $('.field-group-checkbox:checked').each(function() {
                const $checkbox = $(this);
                const $item = $checkbox.closest('.wpt-acf-group-item');

                if (!$item.hasClass('expanded')) {
                    $item.addClass('expanded');
                    $item.find('.wpt-group-fields').show();
                }

                if (!$item.data('fields-loaded')) {
                    WPTSyncConfig.loadFields($item);
                    $item.data('fields-loaded', true);
                }
            });
        },

        /**
         * Handle field group checkbox toggle
         */
        handleFieldGroupToggle: function(e) {
            const $checkbox = $(e.target);
            const $item = $checkbox.closest('.wpt-acf-group-item');
            const $fieldsContainer = $item.find('.wpt-group-fields');

            if ($checkbox.is(':checked')) {
                $item.addClass('expanded');
                $fieldsContainer.slideDown();

                if (!$item.data('fields-loaded')) {
                    this.loadFields($item);
                    $item.data('fields-loaded', true);
                }
            } else {
                // Don't collapse, just uncheck all fields
                $fieldsContainer.find('input[type="checkbox"]').prop('checked', false);
            }
        },

        /**
         * Load fields for a field group
         */
        loadFields: function($item) {
            const groupKey = $item.data('group-key');
            const $fieldsContainer = $item.find('.wpt-group-fields');
            const $loadingDiv = $fieldsContainer.find('.fields-loading');
            const $fieldsList = $fieldsContainer.find('.fields-list');

            // Show loading
            $loadingDiv.show();
            $fieldsList.empty();

            // Load fields via AJAX
            $.ajax({
                url: wptSyncConfig.ajaxurl,
                type: 'POST',
                data: {
                    action: 'wpt_get_acf_fields',
                    nonce: wptSyncConfig.nonce,
                    group_key: groupKey
                },
                success: function(response) {
                    $loadingDiv.hide();

                    if (response.success && response.data.fields) {
                        WPTSyncConfig.renderFields($fieldsList, groupKey, response.data.fields);
                    } else {
                        $fieldsList.html('<p class="no-items">No fields found in this group.</p>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error loading fields:', error);
                    $loadingDiv.hide();
                    $fieldsList.html('<p class="no-items error">Error loading fields. Check console for details.</p>');
                }
            });
        },

        /**
         * Render fields checkboxes
         */
        renderFields: function($container, groupKey, fields) {
            if (Object.keys(fields).length === 0) {
                $container.html('<p class="no-items">No fields found in this group.</p>');
                return;
            }

            let html = '';

            // Get saved field selection for this group
            const savedFields = wptSyncConfig.savedConfig && wptSyncConfig.savedConfig.fields && wptSyncConfig.savedConfig.fields[groupKey]
                ? wptSyncConfig.savedConfig.fields[groupKey]
                : [];

            $.each(fields, function(fieldKey, fieldData) {
                // Check if this field is in the saved configuration
                const isChecked = savedFields.length === 0 || savedFields.includes(fieldKey);

                html += `
                    <label class="wpt-compact-item wpt-field-item">
                        <input
                            type="checkbox"
                            name="fields[${groupKey}][]"
                            value="${fieldKey}"
                            class="field-checkbox"
                            ${isChecked ? 'checked' : ''}
                        />
                        <span class="item-content">
                            <strong>${fieldData.label}</strong>
                            <span class="item-slug">${fieldData.name}</span>
                            <span class="item-meta">${fieldData.type}</span>
                        </span>
                    </label>
                `;
            });

            $container.html(html);
        },

        /**
         * Save configuration
         */
        saveConfiguration: function(e) {
            e.preventDefault();

            const $btn = $('#save-config-btn');
            const $status = $('.save-status');
            const $form = $('#wpt-sync-config-form');

            // Disable button
            $btn.prop('disabled', true);
            $status.removeClass('success error').text(wptSyncConfig.strings.saving);

            // Collect form data including nonce
            const formData = $form.serialize() + '&action=wpt_save_sync_config&nonce=' + wptSyncConfig.nonce;

            console.log('Saving configuration...', formData);

            $.ajax({
                url: wptSyncConfig.ajaxurl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    console.log('Save response:', response);

                    if (response.success) {
                        $status.addClass('success').text(wptSyncConfig.strings.saved);

                        setTimeout(function() {
                            $status.text('');
                        }, 3000);
                    } else {
                        $status.addClass('error').text(response.data.message || wptSyncConfig.strings.error);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Save error:', error, xhr.responseText);
                    $status.addClass('error').text(wptSyncConfig.strings.error + ': ' + error);
                },
                complete: function() {
                    $btn.prop('disabled', false);
                }
            });
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        if ($('#wpt-sync-config-form').length) {
            WPTSyncConfig.init();
        }
    });

})(jQuery);
