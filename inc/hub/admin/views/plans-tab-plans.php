<?php
/**
 * Plans Tab - List and Edit Plans
 *
 * @package WPT_Optica_Core
 */

if (!defined('ABSPATH')) {
    exit;
}

if ($action === 'list') {
    // List all plans
    $plans = WPT_Plan_Manager::get_plans();
    ?>

    <div class="wpt-plans-grid">
        <?php foreach ($plans as $plan): ?>
            <?php
            $features = json_decode($plan->features, true);
            $tenant_count = WPT_Plan_Manager::get_plan_tenants_count($plan->id);
            $revenue = WPT_Plan_Manager::get_plan_revenue($plan->id);
            ?>
            <div class="wpt-plan-card <?php echo $plan->is_active ? 'plan-active' : ''; ?>">
                <div class="plan-header">
                    <h3 class="plan-name"><?php echo esc_html($plan->name); ?></h3>
                    <div class="plan-price">
                        <?php echo number_format($plan->price, 0); ?> <small>RON/lună</small>
                    </div>
                </div>

                <div class="plan-body">
                    <div class="plan-stats">
                        <div class="plan-stat">
                            <span class="plan-stat-value"><?php echo $tenant_count; ?></span>
                            <span class="plan-stat-label"><?php _e('Active Tenants', 'wpt-optica-core'); ?></span>
                        </div>
                        <div class="plan-stat">
                            <span class="plan-stat-value"><?php echo number_format($revenue, 0); ?></span>
                            <span class="plan-stat-label"><?php _e('RON/lună', 'wpt-optica-core'); ?></span>
                        </div>
                    </div>

                    <ul class="plan-features">
                        <li>
                            <span>
                                <span class="dashicons dashicons-location-alt"></span>
                                <?php _e('Locations', 'wpt-optica-core'); ?>
                            </span>
                            <strong><?php echo isset($features['locations']) && $features['locations'] >= 999 ? __('Unlimited', 'wpt-optica-core') : $features['locations']; ?></strong>
                        </li>
                        <li>
                            <span>
                                <span class="dashicons dashicons-megaphone"></span>
                                <?php _e('Offers/month', 'wpt-optica-core'); ?>
                            </span>
                            <strong><?php echo isset($features['offers']) ? $features['offers'] : 0; ?></strong>
                        </li>
                        <li>
                            <span>
                                <span class="dashicons dashicons-businessman"></span>
                                <?php _e('Jobs/month', 'wpt-optica-core'); ?>
                            </span>
                            <strong><?php echo isset($features['jobs']) ? $features['jobs'] : 0; ?></strong>
                        </li>
                        <li>
                            <span>
                                <?php if (isset($features['candidati_access']) && $features['candidati_access']): ?>
                                    <span class="dashicons dashicons-yes"></span>
                                <?php else: ?>
                                    <span class="dashicons dashicons-no"></span>
                                <?php endif; ?>
                                <?php _e('Candidați Access', 'wpt-optica-core'); ?>
                            </span>
                        </li>
                        <li>
                            <span>
                                <?php if (isset($features['furnizori_access']) && $features['furnizori_access']): ?>
                                    <span class="dashicons dashicons-yes"></span>
                                <?php else: ?>
                                    <span class="dashicons dashicons-no"></span>
                                <?php endif; ?>
                                <?php _e('Furnizori Access', 'wpt-optica-core'); ?>
                            </span>
                        </li>
                    </ul>
                </div>

                <div class="plan-footer">
                    <span class="wpt-status-badge <?php echo $plan->is_active ? 'wpt-status-active' : 'wpt-status-inactive'; ?>">
                        <?php echo $plan->is_active ? __('Active', 'wpt-optica-core') : __('Inactive', 'wpt-optica-core'); ?>
                    </span>
                    <div class="button-group">
                        <a href="<?php echo admin_url('admin.php?page=wpt-plans&tab=plans&action=edit&id=' . $plan->id); ?>" class="button button-small">
                            <?php _e('Edit', 'wpt-optica-core'); ?>
                        </a>
                        <?php if ($tenant_count === 0): ?>
                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=wpt-plans&tab=plans&action=delete&id=' . $plan->id), 'delete_item_' . $plan->id); ?>"
                               class="button button-small button-link-delete"
                               onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete this plan?', 'wpt-optica-core'); ?>');">
                                <?php _e('Delete', 'wpt-optica-core'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php
} elseif ($action === 'new' || $action === 'edit') {
    // Edit or create plan
    $plan = null;
    $features = array();

    if ($action === 'edit' && $item_id > 0) {
        $plan = WPT_Plan_Manager::get_plan($item_id);
        $features = json_decode($plan->features, true);
    }

    $is_new = ($action === 'new');
    ?>

    <p><a href="<?php echo admin_url('admin.php?page=wpt-plans&tab=plans'); ?>">&larr; <?php _e('Back to Plans', 'wpt-optica-core'); ?></a></p>

    <h2><?php echo $is_new ? __('Create New Plan', 'wpt-optica-core') : __('Edit Plan', 'wpt-optica-core'); ?></h2>

    <form method="post" action="<?php echo admin_url('admin.php?page=wpt-plans&tab=plans&action=' . $action . ($item_id > 0 ? '&id=' . $item_id : '')); ?>">
        <?php wp_nonce_field('wpt_save_item', 'wpt_nonce'); ?>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="name"><?php _e('Plan Name', 'wpt-optica-core'); ?> <span class="required">*</span></label>
                </th>
                <td>
                    <input type="text" id="name" name="name" class="regular-text" value="<?php echo isset($plan) ? esc_attr($plan->name) : ''; ?>" required>
                    <p class="description"><?php _e('e.g., FREE, PREMIUM, BUSINESS', 'wpt-optica-core'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="slug"><?php _e('Slug', 'wpt-optica-core'); ?> <span class="required">*</span></label>
                </th>
                <td>
                    <input type="text" id="slug" name="slug" class="regular-text" value="<?php echo isset($plan) ? esc_attr($plan->slug) : ''; ?>" required>
                    <p class="description"><?php _e('Unique identifier (e.g., free, premium, business)', 'wpt-optica-core'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="price"><?php _e('Price (RON/month)', 'wpt-optica-core'); ?></label>
                </th>
                <td>
                    <input type="number" id="price" name="price" class="small-text" value="<?php echo isset($plan) ? esc_attr($plan->price) : '0'; ?>" step="0.01" min="0">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="billing_period"><?php _e('Billing Period', 'wpt-optica-core'); ?></label>
                </th>
                <td>
                    <select id="billing_period" name="billing_period">
                        <option value="monthly" <?php echo isset($plan) && $plan->billing_period === 'monthly' ? 'selected' : ''; ?>><?php _e('Monthly', 'wpt-optica-core'); ?></option>
                        <option value="yearly" <?php echo isset($plan) && $plan->billing_period === 'yearly' ? 'selected' : ''; ?>><?php _e('Yearly', 'wpt-optica-core'); ?></option>
                    </select>
                </td>
            </tr>
        </table>

        <h3><?php _e('Plan Features', 'wpt-optica-core'); ?></h3>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="locations"><?php _e('Locations Quota', 'wpt-optica-core'); ?></label>
                </th>
                <td>
                    <input type="number" id="locations" name="locations" class="small-text" value="<?php echo isset($features['locations']) ? esc_attr($features['locations']) : '1'; ?>" min="1">
                    <p class="description"><?php _e('Use 999 for unlimited', 'wpt-optica-core'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="location_type"><?php _e('Location Type', 'wpt-optica-core'); ?></label>
                </th>
                <td>
                    <select id="location_type" name="location_type">
                        <option value="standard" <?php echo isset($features['location_type']) && $features['location_type'] === 'standard' ? 'selected' : ''; ?>><?php _e('Standard (read-only)', 'wpt-optica-core'); ?></option>
                        <option value="premium" <?php echo isset($features['location_type']) && $features['location_type'] === 'premium' ? 'selected' : ''; ?>><?php _e('Premium (full editing)', 'wpt-optica-core'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="offers"><?php _e('Offers Quota/month', 'wpt-optica-core'); ?></label>
                </th>
                <td>
                    <input type="number" id="offers" name="offers" class="small-text" value="<?php echo isset($features['offers']) ? esc_attr($features['offers']) : '0'; ?>" min="0">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="jobs"><?php _e('Jobs Quota/month', 'wpt-optica-core'); ?></label>
                </th>
                <td>
                    <input type="number" id="jobs" name="jobs" class="small-text" value="<?php echo isset($features['jobs']) ? esc_attr($features['jobs']) : '0'; ?>" min="0">
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Access Features', 'wpt-optica-core'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="candidati_access" value="1" <?php echo isset($features['candidati_access']) && $features['candidati_access'] ? 'checked' : ''; ?>>
                        <?php _e('Candidați Access', 'wpt-optica-core'); ?>
                    </label><br>
                    <label>
                        <input type="checkbox" name="furnizori_access" value="1" <?php echo isset($features['furnizori_access']) && $features['furnizori_access'] ? 'checked' : ''; ?>>
                        <?php _e('Furnizori Access', 'wpt-optica-core'); ?>
                    </label><br>
                    <label>
                        <input type="checkbox" name="brand_listing" value="1" <?php echo !isset($features['brand_listing']) || $features['brand_listing'] ? 'checked' : ''; ?>>
                        <?php _e('Brand Listing on Hub', 'wpt-optica-core'); ?>
                    </label><br>
                    <label>
                        <input type="checkbox" name="tenant_site" value="1" <?php echo isset($features['tenant_site']) && $features['tenant_site'] ? 'checked' : ''; ?>>
                        <?php _e('Tenant Site Included (usually addon)', 'wpt-optica-core'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="support"><?php _e('Support Level', 'wpt-optica-core'); ?></label>
                </th>
                <td>
                    <select id="support" name="support">
                        <option value="community" <?php echo isset($features['support']) && $features['support'] === 'community' ? 'selected' : ''; ?>><?php _e('Community', 'wpt-optica-core'); ?></option>
                        <option value="email" <?php echo isset($features['support']) && $features['support'] === 'email' ? 'selected' : ''; ?>><?php _e('Email', 'wpt-optica-core'); ?></option>
                        <option value="priority" <?php echo isset($features['support']) && $features['support'] === 'priority' ? 'selected' : ''; ?>><?php _e('Priority', 'wpt-optica-core'); ?></option>
                        <option value="dedicated" <?php echo isset($features['support']) && $features['support'] === 'dedicated' ? 'selected' : ''; ?>><?php _e('Dedicated', 'wpt-optica-core'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Status', 'wpt-optica-core'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="is_active" value="1" <?php echo !isset($plan) || $plan->is_active ? 'checked' : ''; ?>>
                        <?php _e('Active', 'wpt-optica-core'); ?>
                    </label>
                </td>
            </tr>
        </table>

        <p class="submit">
            <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo $is_new ? __('Create Plan', 'wpt-optica-core') : __('Update Plan', 'wpt-optica-core'); ?>">
            <a href="<?php echo admin_url('admin.php?page=wpt-plans&tab=plans'); ?>" class="button"><?php _e('Cancel', 'wpt-optica-core'); ?></a>
        </p>
    </form>

    <?php
}
