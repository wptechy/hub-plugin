<?php
/**
 * Sync Configuration Admin View - V2 with Tabs
 *
 * @package WPT_Optica_Core
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap wpt-sync-config-page">
    <h1><?php _e('Global Sync Configuration', 'wpt-optica-core'); ?></h1>

    <p class="description">
        <?php _e('Select which Custom Post Types, Taxonomies, and ACF Fields will be synchronized to NEW tenants by default. You can customize this per-tenant in the tenant edit page.', 'wpt-optica-core'); ?>
    </p>

    <form id="wpt-sync-config-form" method="post">
        <?php wp_nonce_field('wpt_sync_config_action', 'wpt_sync_config_nonce'); ?>

        <!-- Tabs Navigation -->
        <h2 class="nav-tab-wrapper wpt-sync-tabs">
            <a href="#tab-cpts" class="nav-tab nav-tab-active" data-tab="cpts">
                <?php _e('Custom Post Types', 'wpt-optica-core'); ?>
            </a>
            <a href="#tab-taxonomies" class="nav-tab" data-tab="taxonomies">
                <?php _e('Taxonomies', 'wpt-optica-core'); ?>
            </a>
            <a href="#tab-acf" class="nav-tab" data-tab="acf">
                <?php _e('ACF Field Groups', 'wpt-optica-core'); ?>
            </a>
        </h2>

        <!-- Tab Content -->
        <div class="wpt-tab-content">

            <!-- CPTs Tab -->
            <div id="tab-cpts" class="wpt-tab-panel active">
                <div class="wpt-compact-grid">
                    <?php if (empty($available_cpts)): ?>
                        <p class="no-items"><?php _e('No custom post types found.', 'wpt-optica-core'); ?></p>
                    <?php else: ?>
                        <?php foreach ($available_cpts as $cpt_slug => $cpt_data): ?>
                            <label class="wpt-compact-item">
                                <input
                                    type="checkbox"
                                    name="cpts[]"
                                    value="<?php echo esc_attr($cpt_slug); ?>"
                                    <?php checked(in_array($cpt_slug, $config['cpts'])); ?>
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
            <div id="tab-taxonomies" class="wpt-tab-panel">
                <div class="wpt-compact-grid">
                    <?php if (empty($available_taxonomies)): ?>
                        <p class="no-items"><?php _e('No taxonomies found.', 'wpt-optica-core'); ?></p>
                    <?php else: ?>
                        <?php foreach ($available_taxonomies as $tax_slug => $tax_data): ?>
                            <label class="wpt-compact-item">
                                <input
                                    type="checkbox"
                                    name="taxonomies[]"
                                    value="<?php echo esc_attr($tax_slug); ?>"
                                    <?php checked(in_array($tax_slug, $config['taxonomies'])); ?>
                                />
                                <span class="item-content">
                                    <strong><?php echo esc_html($tax_data['label']); ?></strong>
                                    <span class="item-slug"><?php echo esc_html($tax_slug); ?></span>
                                    <?php if (!empty($tax_data['post_types'])): ?>
                                        <span class="item-meta"><?php echo esc_html(implode(', ', $tax_data['post_types'])); ?></span>
                                    <?php endif; ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ACF Tab -->
            <div id="tab-acf" class="wpt-tab-panel">
                <?php if (empty($field_groups)): ?>
                    <p class="no-items"><?php _e('No ACF field groups found. Make sure Advanced Custom Fields Pro is installed and activated.', 'wpt-optica-core'); ?></p>
                <?php else: ?>
                    <div class="wpt-acf-groups">
                        <?php foreach ($field_groups as $group_key => $group_data): ?>
                            <div class="wpt-acf-group-item" data-group-key="<?php echo esc_attr($group_key); ?>">
                                <label class="wpt-group-header">
                                    <input
                                        type="checkbox"
                                        name="field_groups[]"
                                        value="<?php echo esc_attr($group_key); ?>"
                                        class="field-group-checkbox"
                                        <?php checked(in_array($group_key, $config['field_groups'])); ?>
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

        <!-- Save Button -->
        <div class="wpt-save-section">
            <button type="submit" class="button button-primary button-large" id="save-config-btn">
                <span class="dashicons dashicons-yes"></span>
                <?php _e('Save Global Configuration', 'wpt-optica-core'); ?>
            </button>
            <span class="save-status"></span>
            <p class="description">
                <?php _e('This configuration will be applied to all new tenants. Existing tenants keep their current configuration.', 'wpt-optica-core'); ?>
            </p>
        </div>
    </form>
</div>

<style>
/* Compact Tabs Layout */
.wpt-sync-config-page {
    max-width: 1400px;
}

.wpt-sync-tabs {
    margin: 20px 0;
    border-bottom: 1px solid #ccd0d4;
}

.wpt-tab-content {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-top: none;
    padding: 20px;
    min-height: 400px;
}

.wpt-tab-panel {
    display: none;
}

.wpt-tab-panel.active {
    display: block;
}

/* Compact Grid - 4 columns */
.wpt-compact-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
}

.wpt-compact-item {
    display: flex;
    align-items: flex-start;
    padding: 10px 12px;
    border: 1px solid #dcdcde;
    border-radius: 3px;
    cursor: pointer;
    transition: all 0.15s;
    background: #fff;
}

.wpt-compact-item:hover {
    border-color: #2271b1;
    background: #f6f7f7;
}

.wpt-compact-item input[type="checkbox"] {
    margin: 2px 10px 0 0;
    flex-shrink: 0;
}

.wpt-compact-item .item-content {
    display: flex;
    flex-direction: column;
    gap: 3px;
    font-size: 13px;
}

.wpt-compact-item .item-slug {
    color: #646970;
    font-size: 11px;
    font-family: monospace;
}

.wpt-compact-item .item-meta {
    color: #646970;
    font-size: 11px;
}

/* ACF Groups */
.wpt-acf-groups {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.wpt-acf-group-item {
    border: 1px solid #dcdcde;
    border-radius: 3px;
    background: #fff;
}

.wpt-group-header {
    display: flex;
    align-items: center;
    padding: 12px 15px;
    cursor: pointer;
    user-select: none;
}

.wpt-group-header:hover {
    background: #f6f7f7;
}

.wpt-group-header input[type="checkbox"] {
    margin: 0 10px 0 0;
}

.wpt-group-header .group-title {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 8px;
}

.wpt-group-header .group-key {
    color: #646970;
    font-size: 11px;
    font-family: monospace;
}

.wpt-group-header .toggle-icon {
    color: #646970;
    transition: transform 0.2s;
}

.wpt-acf-group-item.expanded .toggle-icon {
    transform: rotate(180deg);
}

.wpt-group-fields {
    padding: 15px;
    background: #f6f7f7;
    border-top: 1px solid #dcdcde;
}

.fields-loading {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #646970;
    font-size: 13px;
}

.fields-list {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
}

.wpt-field-item {
    padding: 8px 10px !important;
    font-size: 12px !important;
}

/* Save Section */
.wpt-save-section {
    margin: 20px 0;
    padding: 20px;
    background: #f6f7f7;
    border: 1px solid #dcdcde;
    border-radius: 3px;
}

.wpt-save-section .button {
    margin-right: 15px;
}

.wpt-save-section .dashicons {
    margin-right: 5px;
    line-height: inherit;
}

.save-status {
    font-weight: 500;
    font-size: 14px;
}

.save-status.success {
    color: #00a32a;
}

.save-status.error {
    color: #d63638;
}

.no-items {
    color: #646970;
    font-style: italic;
    padding: 40px;
    text-align: center;
}

/* Responsive */
@media (max-width: 1200px) {
    .wpt-compact-grid {
        grid-template-columns: repeat(3, 1fr);
    }
    .fields-list {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 782px) {
    .wpt-compact-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .fields-list {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Tab switching
    $('.wpt-sync-tabs .nav-tab').on('click', function(e) {
        e.preventDefault();

        const $tab = $(this);
        const tabId = $tab.data('tab');

        // Update tabs
        $('.wpt-sync-tabs .nav-tab').removeClass('nav-tab-active');
        $tab.addClass('nav-tab-active');

        // Update panels
        $('.wpt-tab-panel').removeClass('active');
        $('#tab-' + tabId).addClass('active');
    });

    // ACF group toggle
    $('.wpt-group-header').on('click', function(e) {
        if ($(e.target).is('input[type="checkbox"]')) {
            return; // Let checkbox handle its own click
        }

        const $item = $(this).closest('.wpt-acf-group-item');
        const $fields = $item.find('.wpt-group-fields');

        $item.toggleClass('expanded');
        $fields.slideToggle(200);

        // Load fields if not loaded yet
        if ($item.hasClass('expanded') && !$item.data('fields-loaded')) {
            WPTSyncConfig.loadFields($item);
            $item.data('fields-loaded', true);
        }
    });
});
</script>
