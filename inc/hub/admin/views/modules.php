<?php
/**
 * Modules Marketplace View
 *
 * @package WPT_Optica_Core
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
$module_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['wpt_module_nonce']) && wp_verify_nonce($_POST['wpt_module_nonce'], 'wpt_save_module')) {
    $module_data = array(
        'title' => sanitize_text_field($_POST['title']),
        'slug' => sanitize_title($_POST['slug']),
        'description' => wp_kses_post($_POST['description']),
        'category_id' => intval($_POST['category_id']),
        'price' => floatval($_POST['price']),
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
        'icon' => sanitize_text_field($_POST['icon']),
    );

    if ($module_id > 0) {
        $wpdb->update(
            $wpdb->prefix . 'wpt_available_modules',
            $module_data,
            array('id' => $module_id),
            array('%s', '%s', '%s', '%d', '%f', '%d', '%s'),
            array('%d')
        );
        $message = __('Module updated successfully', 'wpt-optica-core');
    } else {
        $module_data['created_at'] = current_time('mysql');
        $wpdb->insert(
            $wpdb->prefix . 'wpt_available_modules',
            $module_data,
            array('%s', '%s', '%s', '%d', '%f', '%d', '%s', '%s')
        );
        $message = __('Module created successfully', 'wpt-optica-core');
    }

    echo '<div class="notice notice-success"><p>' . esc_html($message) . '</p></div>';
}

// Get categories for dropdown
$categories = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wpt_module_categories ORDER BY name ASC");

if ($action === 'edit' && $module_id > 0) {
    $module = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}wpt_available_modules WHERE id = %d",
        $module_id
    ));

    if (!$module) {
        echo '<div class="notice notice-error"><p>' . __('Module not found', 'wpt-optica-core') . '</p></div>';
        $action = 'list';
    }
}
?>

<div class="wrap wpt-modules">
    <h1 class="wp-heading-inline"><?php _e('Modules Marketplace', 'wpt-optica-core'); ?></h1>

    <?php if ($action === 'list'): ?>
        <a href="<?php echo admin_url('admin.php?page=wpt-modules&action=new'); ?>" class="page-title-action">
            <?php _e('Add New', 'wpt-optica-core'); ?>
        </a>
        <hr class="wp-header-end">

        <?php
        // Get all modules
        $modules = $wpdb->get_results("
            SELECT m.*, c.name as category_name
            FROM {$wpdb->prefix}wpt_available_modules m
            LEFT JOIN {$wpdb->prefix}wpt_module_categories c ON m.category_id = c.id
            ORDER BY m.title ASC
        ");
        ?>

        <div class="wpt-modules-grid">
            <?php if (empty($modules)): ?>
                <p><?php _e('No modules found', 'wpt-optica-core'); ?></p>
            <?php else: ?>
                <?php foreach ($modules as $module): ?>
                    <?php $status_class = $module->is_active ? 'active' : 'inactive'; ?>
                    <div class="wpt-module-card wpt-module-<?php echo esc_attr($status_class); ?>">
                        <div class="wpt-module-icon">
                            <span class="dashicons dashicons-<?php echo esc_attr($module->icon ?: 'admin-plugins'); ?>"></span>
                        </div>
                        <div class="wpt-module-header">
                            <h3><?php echo esc_html($module->title); ?></h3>
                            <span class="wpt-module-category"><?php echo esc_html($module->category_name ?: 'Uncategorized'); ?></span>
                        </div>
                        <div class="wpt-module-description">
                            <?php echo wp_kses_post(wp_trim_words($module->description, 20)); ?>
                        </div>
                        <div class="wpt-module-price">
                            <?php if ($module->price > 0): ?>
                                <strong><?php echo esc_html(number_format($module->price, 2)); ?> RON</strong>
                            <?php else: ?>
                                <strong><?php _e('Free', 'wpt-optica-core'); ?></strong>
                            <?php endif; ?>
                        </div>
                        <div class="wpt-module-status">
                            <span class="wpt-status-badge wpt-status-<?php echo esc_attr($status_class); ?>">
                                <?php echo esc_html($module->is_active ? __('Active', 'wpt-optica-core') : __('Inactive', 'wpt-optica-core')); ?>
                            </span>
                        </div>
                        <div class="wpt-module-actions">
                            <a href="<?php echo admin_url('admin.php?page=wpt-modules&action=edit&id=' . $module->id); ?>" class="button button-primary">
                                <?php _e('Edit', 'wpt-optica-core'); ?>
                            </a>
                            <a href="<?php echo admin_url('admin.php?page=wpt-modules&action=view&id=' . $module->id); ?>" class="button button-secondary">
                                <?php _e('View Stats', 'wpt-optica-core'); ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    <?php elseif ($action === 'new' || $action === 'edit'): ?>
        <hr class="wp-header-end">

        <form method="post" action="<?php echo admin_url('admin.php?page=wpt-modules' . ($action === 'edit' ? '&action=edit&id=' . $module_id : '&action=new')); ?>">
            <?php wp_nonce_field('wpt_save_module', 'wpt_module_nonce'); ?>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="title"><?php _e('Module Name', 'wpt-optica-core'); ?> <span class="required">*</span></label>
                    </th>
                    <td>
                        <input type="text" id="title" name="title" class="regular-text"
                               value="<?php echo isset($module) ? esc_attr($module->title) : ''; ?>" required>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="slug"><?php _e('Slug', 'wpt-optica-core'); ?> <span class="required">*</span></label>
                    </th>
                    <td>
                        <input type="text" id="slug" name="slug" class="regular-text" 
                               value="<?php echo isset($module) ? esc_attr($module->slug) : ''; ?>" required>
                        <p class="description"><?php _e('Unique identifier (e.g., appointments-pro)', 'wpt-optica-core'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="description"><?php _e('Description', 'wpt-optica-core'); ?></label>
                    </th>
                    <td>
                        <textarea id="description" name="description" rows="5" class="large-text"><?php echo isset($module) ? esc_textarea($module->description) : ''; ?></textarea>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="category_id"><?php _e('Category', 'wpt-optica-core'); ?> <span class="required">*</span></label>
                    </th>
                    <td>
                        <select id="category_id" name="category_id" required>
                            <option value=""><?php _e('Select a category', 'wpt-optica-core'); ?></option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo esc_attr($category->id); ?>" 
                                    <?php echo (isset($module) && $module->category_id == $category->id) ? 'selected' : ''; ?>>
                                    <?php echo esc_html($category->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="price"><?php _e('Price', 'wpt-optica-core'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="price" name="price" class="small-text" step="0.01" min="0"
                               value="<?php echo isset($module) ? esc_attr($module->price) : '0'; ?>"> RON
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="icon"><?php _e('Dashicon', 'wpt-optica-core'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="icon" name="icon" class="regular-text" 
                               value="<?php echo isset($module) ? esc_attr($module->icon) : 'admin-plugins'; ?>">
                        <p class="description">
                            <?php _e('Dashicon name without "dashicons-" prefix (e.g., "calendar-alt")', 'wpt-optica-core'); ?>
                            <a href="https://developer.wordpress.org/resource/dashicons/" target="_blank"><?php _e('View all icons', 'wpt-optica-core'); ?></a>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="is_active"><?php _e('Active', 'wpt-optica-core'); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" id="is_active" name="is_active" value="1"
                               <?php echo (isset($module) && $module->is_active) ? 'checked' : ''; ?>>
                        <label for="is_active"><?php _e('Enable this module', 'wpt-optica-core'); ?></label>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <input type="submit" name="submit" class="button button-primary" 
                       value="<?php echo $action === 'edit' ? __('Update Module', 'wpt-optica-core') : __('Create Module', 'wpt-optica-core'); ?>">
                <a href="<?php echo admin_url('admin.php?page=wpt-modules'); ?>" class="button button-secondary">
                    <?php _e('Cancel', 'wpt-optica-core'); ?>
                </a>
            </p>
        </form>

    <?php elseif ($action === 'view' && $module_id > 0): ?>
        <?php
        $module = $wpdb->get_row($wpdb->prepare(
            "SELECT m.*, c.name as category_name
            FROM {$wpdb->prefix}wpt_available_modules m
            LEFT JOIN {$wpdb->prefix}wpt_module_categories c ON m.category_id = c.id
            WHERE m.id = %d",
            $module_id
        ));

        // Get usage stats
        $total_activations = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}wpt_tenant_modules WHERE module_id = %d",
            $module_id
        ));

        $active_tenants = $wpdb->get_results($wpdb->prepare(
            "SELECT t.*, tm.activated_at 
            FROM {$wpdb->prefix}wpt_tenants t
            INNER JOIN {$wpdb->prefix}wpt_tenant_modules tm ON t.id = tm.tenant_id
            WHERE tm.module_id = %d AND tm.status = 'active'
            ORDER BY tm.activated_at DESC",
            $module_id
        ));
        ?>

        <hr class="wp-header-end">

        <div class="wpt-module-details">
            <div class="wpt-module-header-large">
                <span class="dashicons dashicons-<?php echo esc_attr($module->icon ?: 'admin-plugins'); ?>"></span>
                <div>
                    <h2><?php echo esc_html($module->title); ?></h2>
                    <p><?php echo esc_html($module->category_name); ?></p>
                </div>
            </div>

            <div class="wpt-stats-row">
                <div class="wpt-stat-box">
                    <div class="wpt-stat-number"><?php echo esc_html($total_activations); ?></div>
                    <div class="wpt-stat-label"><?php _e('Total Activations', 'wpt-optica-core'); ?></div>
                </div>
                <div class="wpt-stat-box">
                    <div class="wpt-stat-number"><?php echo esc_html(count($active_tenants)); ?></div>
                    <div class="wpt-stat-label"><?php _e('Active Users', 'wpt-optica-core'); ?></div>
                </div>
                <div class="wpt-stat-box">
                    <div class="wpt-stat-number"><?php echo number_format($module->price, 2); ?> RON</div>
                    <div class="wpt-stat-label"><?php _e('Price', 'wpt-optica-core'); ?></div>
                </div>
            </div>

            <h3><?php _e('Active Tenants', 'wpt-optica-core'); ?></h3>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Tenant', 'wpt-optica-core'); ?></th>
                        <th><?php _e('Domain', 'wpt-optica-core'); ?></th>
                        <th><?php _e('Activated', 'wpt-optica-core'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($active_tenants)): ?>
                        <tr>
                            <td colspan="3"><?php _e('No active tenants', 'wpt-optica-core'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($active_tenants as $tenant): ?>
                            <tr>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=wpt-tenants&action=edit&id=' . $tenant->id); ?>">
                                        <?php echo esc_html($tenant->name); ?>
                                    </a>
                                </td>
                                <td><?php echo esc_html($tenant->domain); ?></td>
                                <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($tenant->activated_at))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    <?php endif; ?>
</div>
