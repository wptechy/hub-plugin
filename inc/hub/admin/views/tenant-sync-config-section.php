<?php
/**
 * Tenant Sync Configuration Section
 * To be included in tenant edit page
 *
 * @package WPT_Optica_Core
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Only show on edit mode
if ($action !== 'edit' || !isset($tenant)) {
    return;
}

// Load tenant-specific configuration
$tenant_config = get_option('wpt_tenant_sync_config_' . $tenant_id, array());

// If tenant config doesn't exist, load from global as default
if (empty($tenant_config)) {
    $tenant_config = get_option('wpt_sync_configuration', array(
        'cpts' => array(),
        'taxonomies' => array(),
        'field_groups' => array(),
        'fields' => array()
    ));

    // Map global config to tenant format
    $tenant_config = array(
        'enabled_cpts' => $tenant_config['cpts'] ?? array(),
        'enabled_taxonomies' => $tenant_config['taxonomies'] ?? array(),
        'enabled_field_groups' => $tenant_config['field_groups'] ?? array(),
        'enabled_fields' => $tenant_config['fields'] ?? array(),
    );
}

// Get available items (same as global config)
$available_cpts = array();
$registered_cpts = get_post_types(array('_builtin' => false), 'objects');
foreach ($registered_cpts as $cpt_slug => $cpt_object) {
    $available_cpts[$cpt_slug] = array(
        'label' => $cpt_object->labels->name,
        'singular' => $cpt_object->labels->singular_name,
    );
}

$available_taxonomies = array();
$registered_taxonomies = get_taxonomies(array('_builtin' => false), 'objects');
foreach ($registered_taxonomies as $tax_slug => $tax_object) {
    $available_taxonomies[$tax_slug] = array(
        'label' => $tax_object->labels->name,
        'post_types' => $tax_object->object_type
    );
}

$field_groups = array();
if (function_exists('acf_get_field_groups')) {
    $groups = acf_get_field_groups();
    foreach ($groups as $group) {
        $field_groups[$group['key']] = array(
            'title' => $group['title'],
            'key' => $group['key'],
        );
    }

    // Sort alphabetically by title
    uasort($field_groups, function($a, $b) {
        return strcasecmp($a['title'], $b['title']);
    });
}
?>

<div class="wpt-tenant-sync-section">
    <p class="description" style="margin-bottom: 15px;">
        <?php _e('Configure which content types will be synchronized to this specific tenant. Changes are only applied when you click "Push to Tenant".', 'wpt-optica-core'); ?>
    </p>

    <!-- Tabs Navigation -->
    <h3 class="nav-tab-wrapper wpt-tenant-sync-tabs" style="margin-top: 0;">
        <a href="#tenant-tab-cpts" class="nav-tab nav-tab-active" data-tab="cpts">
            <?php _e('Custom Post Types', 'wpt-optica-core'); ?>
        </a>
        <a href="#tenant-tab-taxonomies" class="nav-tab" data-tab="taxonomies">
            <?php _e('Taxonomies', 'wpt-optica-core'); ?>
        </a>
        <a href="#tenant-tab-acf" class="nav-tab" data-tab="acf">
            <?php _e('ACF Field Groups', 'wpt-optica-core'); ?>
        </a>
    </h3>

    <!-- Tab Content -->
    <div class="wpt-sync-tab-content">

        <!-- CPTs Tab -->
        <div id="tenant-tab-cpts" class="wpt-sync-tab-panel active">
            <div class="wpt-compact-grid">
                <?php if (empty($available_cpts)): ?>
                    <p class="no-items"><?php _e('No custom post types available.', 'wpt-optica-core'); ?></p>
                <?php else: ?>
                    <?php foreach ($available_cpts as $cpt_slug => $cpt_data): ?>
                        <label class="wpt-compact-item">
                            <input
                                type="checkbox"
                                name="tenant_sync_cpts[]"
                                value="<?php echo esc_attr($cpt_slug); ?>"
                                <?php checked(in_array($cpt_slug, $tenant_config['enabled_cpts'] ?? array())); ?>
                            />
                            <span class="item-content">
                                <strong><?php echo esc_html($cpt_data['label']); ?></strong>
                                <span class="item-slug"><?php echo esc_html($cpt_slug); ?></span>
                            </span>
                        </label>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Taxonomies Tab -->
        <div id="tenant-tab-taxonomies" class="wpt-sync-tab-panel">
            <div class="wpt-compact-grid">
                <?php if (empty($available_taxonomies)): ?>
                    <p class="no-items"><?php _e('No taxonomies available.', 'wpt-optica-core'); ?></p>
                <?php else: ?>
                    <?php foreach ($available_taxonomies as $tax_slug => $tax_data): ?>
                        <label class="wpt-compact-item">
                            <input
                                type="checkbox"
                                name="tenant_sync_taxonomies[]"
                                value="<?php echo esc_attr($tax_slug); ?>"
                                <?php checked(in_array($tax_slug, $tenant_config['enabled_taxonomies'] ?? array())); ?>
                            />
                            <span class="item-content">
                                <strong><?php echo esc_html($tax_data['label']); ?></strong>
                                <span class="item-slug"><?php echo esc_html($tax_slug); ?></span>
                            </span>
                        </label>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- ACF Tab -->
        <div id="tenant-tab-acf" class="wpt-sync-tab-panel">
            <?php if (empty($field_groups)): ?>
                <p class="no-items"><?php _e('No ACF field groups available.', 'wpt-optica-core'); ?></p>
            <?php else: ?>
                <div class="wpt-acf-groups">
                    <?php foreach ($field_groups as $group_key => $group_data): ?>
                        <div class="wpt-acf-group-item" data-group-key="<?php echo esc_attr($group_key); ?>">
                            <label class="wpt-group-header">
                                <input
                                    type="checkbox"
                                    name="tenant_sync_field_groups[]"
                                    value="<?php echo esc_attr($group_key); ?>"
                                    class="tenant-field-group-checkbox"
                                    <?php checked(in_array($group_key, $tenant_config['enabled_field_groups'] ?? array())); ?>
                                />
                                <span class="group-title">
                                    <strong><?php echo esc_html($group_data['title']); ?></strong>
                                    <span class="group-key"><?php echo esc_html($group_key); ?></span>
                                </span>
                                <span class="dashicons dashicons-arrow-down-alt2 toggle-icon"></span>
                            </label>

                            <div class="wpt-group-fields" style="display: none;">
                                <div class="fields-loading">
                                    <span class="spinner is-active"></span>
                                    <?php _e('Loading fields...', 'wpt-optica-core'); ?>
                                </div>
                                <div class="fields-list"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- Push to Tenant Button -->
    <div class="wpt-tenant-sync-actions">
        <button type="button" class="button button-secondary button-large" id="push-to-tenant-btn" data-tenant-id="<?php echo esc_attr($tenant_id); ?>">
            <span class="dashicons dashicons-upload"></span>
            <?php _e('Push Configuration to Tenant', 'wpt-optica-core'); ?>
        </button>
        <span class="push-status"></span>

        <p class="description">
            <?php _e('This will send the selected configuration to the tenant site and update its CPTs, taxonomies, and ACF fields.', 'wpt-optica-core'); ?>
        </p>
    </div>
</div>

<input type="hidden" name="tenant_sync_config_section" value="1">

<style>
/* Tenant Sync Section Styles */
.wpt-tenant-sync-section {
    margin-top: 0;
    padding: 0;
    background: transparent;
    border: none;
}

.wpt-tenant-sync-tabs {
    margin: 0;
    border-bottom: 1px solid #ccd0d4;
    background: transparent;
}

.wpt-sync-tab-content {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-top: none;
    padding: 20px;
    min-height: 300px;
}

.wpt-sync-tab-panel {
    display: none;
}

.wpt-sync-tab-panel.active {
    display: block;
}

.wpt-tenant-sync-actions {
    margin-top: 20px;
    padding: 15px;
    background: #fff;
    border: 1px solid #dcdcde;
    border-radius: 4px;
}

.wpt-tenant-sync-actions .button {
    margin-right: 15px;
}

.wpt-tenant-sync-actions .dashicons {
    margin-right: 5px;
    line-height: inherit;
}

.wpt-tenant-sync-actions .push-status {
    font-weight: 500;
    font-size: 14px;
}

.wpt-tenant-sync-actions .push-status.success {
    color: #00a32a;
}

.wpt-tenant-sync-actions .push-status.error {
    color: #d63638;
}

/* Compact Grid for CPTs/Taxonomies */
.wpt-tenant-sync-section .wpt-compact-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
}

.wpt-tenant-sync-section .wpt-compact-item {
    display: flex;
    align-items: flex-start;
    padding: 10px 12px;
    border: 1px solid #dcdcde;
    border-radius: 3px;
    cursor: pointer;
    transition: all 0.15s;
    background: #fff;
}

.wpt-tenant-sync-section .wpt-compact-item:hover {
    border-color: #2271b1;
    background: #f6f7f7;
}

.wpt-tenant-sync-section .wpt-compact-item input[type="checkbox"] {
    margin: 2px 10px 0 0;
    flex-shrink: 0;
}

.wpt-tenant-sync-section .wpt-compact-item .item-content {
    display: flex;
    flex-direction: column;
    gap: 3px;
    font-size: 13px;
}

.wpt-tenant-sync-section .wpt-compact-item .item-slug {
    color: #646970;
    font-size: 11px;
    font-family: monospace;
}

.wpt-tenant-sync-section .wpt-compact-item .item-meta {
    color: #646970;
    font-size: 11px;
}

/* ACF Groups */
.wpt-tenant-sync-section .wpt-acf-groups {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.wpt-tenant-sync-section .wpt-acf-group-item {
    border: 1px solid #dcdcde;
    border-radius: 3px;
    background: #fff;
}

.wpt-tenant-sync-section .wpt-group-header {
    display: flex;
    align-items: center;
    padding: 12px 15px;
    cursor: pointer;
    user-select: none;
}

.wpt-tenant-sync-section .wpt-group-header:hover {
    background: #f6f7f7;
}

.wpt-tenant-sync-section .wpt-group-header input[type="checkbox"] {
    margin: 0 10px 0 0;
}

.wpt-tenant-sync-section .wpt-group-header .group-title {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 8px;
}

.wpt-tenant-sync-section .wpt-group-header .group-key {
    color: #646970;
    font-size: 11px;
    font-family: monospace;
}

.wpt-tenant-sync-section .wpt-group-header .toggle-icon {
    color: #646970;
    transition: transform 0.2s;
}

.wpt-tenant-sync-section .wpt-acf-group-item.expanded .toggle-icon {
    transform: rotate(180deg);
}

.wpt-tenant-sync-section .wpt-group-fields {
    padding: 15px;
    background: #f6f7f7;
    border-top: 1px solid #dcdcde;
}

.wpt-tenant-sync-section .fields-loading {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #646970;
    font-size: 13px;
}

.wpt-tenant-sync-section .fields-list {
    display: block;
}

.wpt-tenant-sync-section .fields-list .wpt-compact-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
}

.wpt-tenant-sync-section .no-items {
    color: #646970;
    font-style: italic;
    padding: 20px;
    text-align: center;
}

@media (max-width: 1200px) {
    .wpt-tenant-sync-section .wpt-compact-grid {
        grid-template-columns: repeat(3, 1fr);
    }
    .wpt-tenant-sync-section .fields-list .wpt-compact-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 782px) {
    .wpt-tenant-sync-section .wpt-compact-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .wpt-tenant-sync-section .fields-list .wpt-compact-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Store tenant configuration in JavaScript
    const tenantSavedFields = <?php echo json_encode($tenant_config['enabled_fields'] ?? array()); ?>;

    // Tab switching for tenant sync
    $('.wpt-tenant-sync-tabs .nav-tab').on('click', function(e) {
        e.preventDefault();

        const $tab = $(this);
        const tabId = 'tenant-' + $tab.data('tab');

        $('.wpt-tenant-sync-tabs .nav-tab').removeClass('nav-tab-active');
        $tab.addClass('nav-tab-active');

        $('.wpt-sync-tab-panel').removeClass('active');
        $('#tenant-tab-' + $tab.data('tab')).addClass('active');
    });

    // ACF group toggle for tenant
    $('.wpt-tenant-sync-section .wpt-group-header').on('click', function(e) {
        if ($(e.target).is('input[type="checkbox"]')) {
            return;
        }

        const $item = $(this).closest('.wpt-acf-group-item');
        const $fields = $item.find('.wpt-group-fields');

        $item.toggleClass('expanded');
        $fields.slideToggle(200);

        // Load fields if not loaded
        if ($item.hasClass('expanded') && !$item.data('fields-loaded')) {
            loadTenantACFFields($item);
            $item.data('fields-loaded', true);
        }
    });

    // Load ACF fields for tenant
    function loadTenantACFFields($item) {
        const groupKey = $item.data('group-key');
        const $loadingDiv = $item.find('.fields-loading');
        const $fieldsList = $item.find('.fields-list');

        $loadingDiv.show();
        $fieldsList.empty();

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wpt_get_acf_fields',
                nonce: '<?php echo wp_create_nonce('wpt_sync_config_nonce'); ?>',
                group_key: groupKey
            },
            success: function(response) {
                $loadingDiv.hide();

                if (response.success && response.data.fields) {
                    renderTenantFields($fieldsList, groupKey, response.data.fields);
                } else {
                    $fieldsList.html('<p class="no-items">No fields found.</p>');
                }
            },
            error: function() {
                $loadingDiv.hide();
                $fieldsList.html('<p class="no-items error">Error loading fields.</p>');
            }
        });
    }

    // Render fields for tenant
    function renderTenantFields($container, groupKey, fields) {
        if (Object.keys(fields).length === 0) {
            $container.html('<p class="no-items">No fields found.</p>');
            return;
        }

        let html = '<div class="wpt-compact-grid" style="grid-template-columns: repeat(3, 1fr);">';

        // Get saved field selection for this group
        const savedFields = tenantSavedFields[groupKey] || [];

        $.each(fields, function(fieldKey, fieldData) {
            // Check if this field is in the saved configuration
            const isChecked = savedFields.length === 0 || savedFields.includes(fieldKey);

            html += `
                <label class="wpt-compact-item">
                    <input
                        type="checkbox"
                        name="tenant_sync_fields[${groupKey}][]"
                        value="${fieldKey}"
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

        html += '</div>';
        $container.html(html);
    }

    // Push to Tenant
    $('#push-to-tenant-btn').on('click', function(e) {
        e.preventDefault();

        const $btn = $(this);
        const $status = $('.push-status');
        const tenantId = $btn.data('tenant-id');

        if (!confirm('<?php _e('Are you sure you want to push this configuration to the tenant? This will update their site structure.', 'wpt-optica-core'); ?>')) {
            return;
        }

        // Save configuration first
        const configData = {
            enabled_cpts: [],
            enabled_taxonomies: [],
            enabled_field_groups: [],
            enabled_fields: {}
        };

        // Collect CPTs
        $('input[name="tenant_sync_cpts[]"]:checked').each(function() {
            configData.enabled_cpts.push($(this).val());
        });

        // Collect Taxonomies
        $('input[name="tenant_sync_taxonomies[]"]:checked').each(function() {
            configData.enabled_taxonomies.push($(this).val());
        });

        // Collect Field Groups
        $('input[name="tenant_sync_field_groups[]"]:checked').each(function() {
            configData.enabled_field_groups.push($(this).val());
        });

        // Collect Fields
        $('.wpt-tenant-sync-section input[name^="tenant_sync_fields"]:checked').each(function() {
            const name = $(this).attr('name');
            const match = name.match(/tenant_sync_fields\[([^\]]+)\]/);
            if (match) {
                const groupKey = match[1];
                if (!configData.enabled_fields[groupKey]) {
                    configData.enabled_fields[groupKey] = [];
                }
                configData.enabled_fields[groupKey].push($(this).val());
            }
        });

        $btn.prop('disabled', true);
        $status.removeClass('success error').text('<?php _e('Pushing to tenant...', 'wpt-optica-core'); ?>');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wpt_push_config_to_tenant',
                nonce: '<?php echo wp_create_nonce('wpt_sync_config_nonce'); ?>',
                tenant_id: tenantId,
                config: configData
            },
            success: function(response) {
                if (response.success) {
                    $status.addClass('success').text('<?php _e('Configuration pushed successfully!', 'wpt-optica-core'); ?>');
                    setTimeout(function() {
                        $status.text('');
                    }, 5000);
                } else {
                    $status.addClass('error').text(response.data.message || '<?php _e('Error pushing configuration', 'wpt-optica-core'); ?>');
                }
            },
            error: function() {
                $status.addClass('error').text('<?php _e('Error pushing configuration', 'wpt-optica-core'); ?>');
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });
});
</script>
