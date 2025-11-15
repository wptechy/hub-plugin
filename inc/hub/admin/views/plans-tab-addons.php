<?php
/**
 * Add-ons Tab - List and Edit Add-ons
 *
 * @package WPT_Optica_Core
 */

if (!defined('ABSPATH')) {
    exit;
}

if ($action === 'list') {
    // List all addons
    $addons = WPT_Addon_Manager::get_addon_prices();
    ?>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width: 50%;"><?php _e('Addon Name', 'wpt-optica-core'); ?></th>
                <th><?php _e('Price/month', 'wpt-optica-core'); ?></th>
                <th><?php _e('Active Tenants', 'wpt-optica-core'); ?></th>
                <th><?php _e('Revenue/month', 'wpt-optica-core'); ?></th>
                <th><?php _e('Status', 'wpt-optica-core'); ?></th>
                <th><?php _e('Actions', 'wpt-optica-core'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($addons as $addon): ?>
                <?php
                $tenant_count = WPT_Addon_Manager::get_addon_tenants_count($addon->addon_slug);
                $revenue = WPT_Addon_Manager::get_addon_revenue($addon->addon_slug);
                ?>
                <tr>
                    <td>
                        <strong><?php echo esc_html($addon->addon_name); ?></strong><br>
                        <span class="description"><?php echo esc_html($addon->addon_slug); ?></span><br>
                        <?php if (!empty($addon->description)): ?>
                            <span class="description"><?php echo esc_html(wp_trim_words($addon->description, 15)); ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong><?php echo number_format($addon->monthly_price, 2); ?> RON</strong>
                    </td>
                    <td>
                        <?php echo $tenant_count; ?>
                    </td>
                    <td>
                        <strong><?php echo number_format($revenue, 2); ?> RON</strong>
                    </td>
                    <td>
                        <span class="wpt-status-badge <?php echo $addon->is_active ? 'wpt-status-active' : 'wpt-status-inactive'; ?>">
                            <?php echo $addon->is_active ? __('Active', 'wpt-optica-core') : __('Inactive', 'wpt-optica-core'); ?>
                        </span>
                    </td>
                    <td>
                        <a href="<?php echo admin_url('admin.php?page=wpt-plans&tab=addons&action=edit&id=' . $addon->id); ?>" class="button button-small">
                            <?php _e('Edit', 'wpt-optica-core'); ?>
                        </a>
                        <?php if ($tenant_count === 0): ?>
                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=wpt-plans&tab=addons&action=delete&id=' . $addon->id), 'delete_item_' . $addon->id); ?>"
                               class="button button-small button-link-delete"
                               onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete this addon?', 'wpt-optica-core'); ?>');">
                                <?php _e('Delete', 'wpt-optica-core'); ?>
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php
} elseif ($action === 'new' || $action === 'edit') {
    // Edit or create addon
    $addon = null;

    if ($action === 'edit' && $item_id > 0) {
        $addon = WPT_Addon_Manager::get_addon($item_id);
    }

    $is_new = ($action === 'new');
    ?>

    <p><a href="<?php echo admin_url('admin.php?page=wpt-plans&tab=addons'); ?>">&larr; <?php _e('Back to Add-ons', 'wpt-optica-core'); ?></a></p>

    <h2><?php echo $is_new ? __('Create New Addon', 'wpt-optica-core') : __('Edit Addon', 'wpt-optica-core'); ?></h2>

    <form method="post" action="<?php echo admin_url('admin.php?page=wpt-plans&tab=addons&action=' . $action . ($item_id > 0 ? '&id=' . $item_id : '')); ?>">
        <?php wp_nonce_field('wpt_save_item', 'wpt_nonce'); ?>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="addon_name"><?php _e('Addon Name', 'wpt-optica-core'); ?> <span class="required">*</span></label>
                </th>
                <td>
                    <input type="text" id="addon_name" name="addon_name" class="regular-text" value="<?php echo isset($addon) ? esc_attr($addon->addon_name) : ''; ?>" required>
                    <p class="description"><?php _e('e.g., Tenant Site, Extra Offers, Premium Location', 'wpt-optica-core'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="addon_slug"><?php _e('Slug', 'wpt-optica-core'); ?> <span class="required">*</span></label>
                </th>
                <td>
                    <input type="text" id="addon_slug" name="addon_slug" class="regular-text" value="<?php echo isset($addon) ? esc_attr($addon->addon_slug) : ''; ?>" required>
                    <p class="description"><?php _e('Unique identifier (e.g., tenant-site, extra-offers, premium-location)', 'wpt-optica-core'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="monthly_price"><?php _e('Monthly Price (RON)', 'wpt-optica-core'); ?></label>
                </th>
                <td>
                    <input type="number" id="monthly_price" name="monthly_price" class="small-text" value="<?php echo isset($addon) ? esc_attr($addon->monthly_price) : '0'; ?>" step="0.01" min="0">
                    <p class="description">
                        <?php _e('For quantity-based addons (extra-offers, extra-jobs, premium-location), this is the price per unit.', 'wpt-optica-core'); ?><br>
                        <?php _e('For flat addons (tenant-site), this is the price per tenant.', 'wpt-optica-core'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="description"><?php _e('Description', 'wpt-optica-core'); ?></label>
                </th>
                <td>
                    <textarea id="description" name="description" rows="4" class="large-text"><?php echo isset($addon) ? esc_textarea($addon->description) : ''; ?></textarea>
                    <p class="description"><?php _e('Detailed description visible to users', 'wpt-optica-core'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Status', 'wpt-optica-core'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="is_active" value="1" <?php echo !isset($addon) || $addon->is_active ? 'checked' : ''; ?>>
                        <?php _e('Active', 'wpt-optica-core'); ?>
                    </label>
                </td>
            </tr>
        </table>

        <p class="submit">
            <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo $is_new ? __('Create Addon', 'wpt-optica-core') : __('Update Addon', 'wpt-optica-core'); ?>">
            <a href="<?php echo admin_url('admin.php?page=wpt-plans&tab=addons'); ?>" class="button"><?php _e('Cancel', 'wpt-optica-core'); ?></a>
        </p>
    </form>

    <?php
}
