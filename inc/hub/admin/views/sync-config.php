<?php
/**
 * Sync Configuration Admin View
 *
 * @package WPT_Optica_Core
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap wpt-sync-config-page">
    <h1><?php _e('Sync Configuration', 'wpt-optica-core'); ?></h1>

    <p class="description">
        <?php _e('Select which Custom Post Types, Taxonomies, and ACF Fields should be synchronized to client sites.', 'wpt-optica-core'); ?>
    </p>

    <form id="wpt-sync-config-form" method="post">
        <?php wp_nonce_field('wpt_sync_config_action', 'wpt_sync_config_nonce'); ?>

        <!-- Custom Post Types Section -->
        <div class="wpt-config-section">
            <h2><?php _e('Custom Post Types', 'wpt-optica-core'); ?></h2>
            <p class="description">
                <?php _e('Select which custom post types should be synchronized to client sites.', 'wpt-optica-core'); ?>
            </p>

            <div class="wpt-checkbox-grid">
                <?php if (empty($available_cpts)): ?>
                    <p class="no-items"><?php _e('No custom post types found.', 'wpt-optica-core'); ?></p>
                <?php else: ?>
                    <?php foreach ($available_cpts as $cpt_slug => $cpt_data): ?>
                        <label class="wpt-checkbox-item">
                            <input
                                type="checkbox"
                                name="cpts[]"
                                value="<?php echo esc_attr($cpt_slug); ?>"
                                <?php checked(in_array($cpt_slug, $config['cpts'])); ?>
                            />
                            <span class="item-label">
                                <strong><?php echo esc_html($cpt_data['label']); ?></strong>
                                <span class="item-slug">(<?php echo esc_html($cpt_slug); ?>)</span>
                            </span>
                            <?php if (!empty($cpt_data['description'])): ?>
                                <span class="item-description"><?php echo esc_html($cpt_data['description']); ?></span>
                            <?php endif; ?>
                        </label>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Taxonomies Section -->
        <div class="wpt-config-section">
            <h2><?php _e('Taxonomies', 'wpt-optica-core'); ?></h2>
            <p class="description">
                <?php _e('Select which taxonomies should be synchronized to client sites.', 'wpt-optica-core'); ?>
            </p>

            <div class="wpt-checkbox-grid">
                <?php if (empty($available_taxonomies)): ?>
                    <p class="no-items"><?php _e('No taxonomies found.', 'wpt-optica-core'); ?></p>
                <?php else: ?>
                    <?php foreach ($available_taxonomies as $tax_slug => $tax_data): ?>
                        <label class="wpt-checkbox-item">
                            <input
                                type="checkbox"
                                name="taxonomies[]"
                                value="<?php echo esc_attr($tax_slug); ?>"
                                <?php checked(in_array($tax_slug, $config['taxonomies'])); ?>
                            />
                            <span class="item-label">
                                <strong><?php echo esc_html($tax_data['label']); ?></strong>
                                <span class="item-slug">(<?php echo esc_html($tax_slug); ?>)</span>
                            </span>
                            <?php if (!empty($tax_data['post_types'])): ?>
                                <span class="item-description">
                                    <?php echo esc_html(sprintf(__('Used by: %s', 'wpt-optica-core'), implode(', ', $tax_data['post_types']))); ?>
                                </span>
                            <?php endif; ?>
                        </label>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- ACF Field Groups Section -->
        <div class="wpt-config-section">
            <h2><?php _e('ACF Field Groups', 'wpt-optica-core'); ?></h2>
            <p class="description">
                <?php _e('Select which ACF field groups should be synchronized to client sites. You can then select individual fields from each group.', 'wpt-optica-core'); ?>
            </p>

            <div class="wpt-field-groups-container">
                <?php if (empty($field_groups)): ?>
                    <p class="no-items"><?php _e('No ACF field groups found. Make sure Advanced Custom Fields Pro is installed and activated.', 'wpt-optica-core'); ?></p>
                <?php else: ?>
                    <?php foreach ($field_groups as $group_key => $group_data): ?>
                        <div class="wpt-field-group-item" data-group-key="<?php echo esc_attr($group_key); ?>">
                            <label class="wpt-checkbox-item group-checkbox">
                                <input
                                    type="checkbox"
                                    name="field_groups[]"
                                    value="<?php echo esc_attr($group_key); ?>"
                                    class="field-group-checkbox"
                                    <?php checked(in_array($group_key, $config['field_groups'])); ?>
                                />
                                <span class="item-label">
                                    <strong><?php echo esc_html($group_data['title']); ?></strong>
                                    <span class="item-slug">(<?php echo esc_html($group_key); ?>)</span>
                                </span>
                            </label>

                            <!-- Fields container (loaded via AJAX) -->
                            <div class="wpt-fields-container" style="display: none;">
                                <div class="fields-loading">
                                    <span class="spinner is-active"></span>
                                    <?php _e('Loading fields...', 'wpt-optica-core'); ?>
                                </div>
                                <div class="fields-list"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Save Configuration -->
        <div class="wpt-config-actions">
            <button type="submit" class="button button-primary button-large" id="save-config-btn">
                <span class="dashicons dashicons-yes"></span>
                <?php _e('Save Configuration', 'wpt-optica-core'); ?>
            </button>

            <span class="save-status"></span>
        </div>
    </form>

    <!-- Push to Client Section -->
    <div class="wpt-config-section wpt-push-section">
        <h2><?php _e('Push Configuration to Client', 'wpt-optica-core'); ?></h2>
        <p class="description">
            <?php _e('After saving your configuration, push it to a specific client site. This will create the CPTs, taxonomies, and ACF fields on the client site.', 'wpt-optica-core'); ?>
        </p>

        <div class="wpt-push-controls">
            <label for="tenant-select">
                <strong><?php _e('Select Client Site:', 'wpt-optica-core'); ?></strong>
            </label>

            <select id="tenant-select" class="regular-text">
                <option value=""><?php _e('-- Select a client site --', 'wpt-optica-core'); ?></option>
                <?php
                // Get all tenants
                global $wpdb;
                $tenants = $wpdb->get_results("SELECT id, brand_name, site_url FROM {$wpdb->prefix}wpt_tenants WHERE status = 'active' ORDER BY brand_name");

                foreach ($tenants as $tenant):
                ?>
                    <option value="<?php echo esc_attr($tenant->id); ?>">
                        <?php echo esc_html($tenant->brand_name); ?> (<?php echo esc_html($tenant->site_url); ?>)
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="button" class="button button-secondary button-large" id="push-config-btn" disabled>
                <span class="dashicons dashicons-upload"></span>
                <?php _e('Push to Client', 'wpt-optica-core'); ?>
            </button>

            <span class="push-status"></span>
        </div>

        <div class="wpt-push-info" style="display: none;">
            <div class="notice notice-info inline">
                <p>
                    <strong><?php _e('What will be pushed:', 'wpt-optica-core'); ?></strong>
                </p>
                <ul>
                    <li><?php _e('Selected Custom Post Types and their settings', 'wpt-optica-core'); ?></li>
                    <li><?php _e('Selected Taxonomies and their relationships', 'wpt-optica-core'); ?></li>
                    <li><?php _e('ACF Field Groups as JSON files', 'wpt-optica-core'); ?></li>
                    <li><?php _e('Field mapping configuration', 'wpt-optica-core'); ?></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
.wpt-sync-config-page {
    max-width: 1200px;
}

.wpt-config-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    padding: 20px;
    margin: 20px 0;
    border-radius: 4px;
}

.wpt-config-section h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.wpt-checkbox-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 15px;
    margin-top: 20px;
}

.wpt-checkbox-item {
    display: flex;
    flex-direction: column;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s;
}

.wpt-checkbox-item:hover {
    border-color: #2271b1;
    background: #f6f7f7;
}

.wpt-checkbox-item input[type="checkbox"] {
    margin-right: 8px;
}

.wpt-checkbox-item .item-label {
    display: flex;
    align-items: center;
    gap: 6px;
}

.wpt-checkbox-item .item-slug {
    color: #666;
    font-size: 12px;
}

.wpt-checkbox-item .item-description {
    margin-top: 6px;
    margin-left: 24px;
    color: #666;
    font-size: 12px;
}

.wpt-field-groups-container {
    margin-top: 20px;
}

.wpt-field-group-item {
    margin-bottom: 20px;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 15px;
}

.wpt-field-group-item.active {
    border-color: #2271b1;
}

.wpt-fields-container {
    margin-left: 30px;
    margin-top: 15px;
    padding: 15px;
    background: #f6f7f7;
    border-radius: 4px;
}

.fields-loading {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #666;
}

.fields-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 10px;
}

.wpt-config-actions {
    margin: 30px 0;
    padding: 20px;
    background: #f6f7f7;
    border-radius: 4px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.wpt-config-actions .dashicons {
    margin-right: 5px;
}

.save-status,
.push-status {
    font-weight: 500;
}

.save-status.success,
.push-status.success {
    color: #00a32a;
}

.save-status.error,
.push-status.error {
    color: #d63638;
}

.wpt-push-section {
    background: #f0f6fc;
    border-color: #c3e0f7;
}

.wpt-push-controls {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-top: 20px;
}

.wpt-push-controls .dashicons {
    margin-right: 5px;
}

.wpt-push-info {
    margin-top: 20px;
}

.wpt-push-info ul {
    margin: 10px 0 0 20px;
}

.no-items {
    color: #666;
    font-style: italic;
}
</style>
