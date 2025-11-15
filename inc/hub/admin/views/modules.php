<?php
/**
 * Modules Marketplace View
 * Organized by categories with vertical tabs
 *
 * @package WPT_Optica_Core
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
$module_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get all categories
$categories = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wpt_module_categories ORDER BY sort_order ASC");

// Get active category (default to first)
$active_category = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : ($categories[0]->slug ?? '');

// Find active category object
$active_cat_obj = null;
foreach ($categories as $cat) {
    if ($cat->slug === $active_category) {
        $active_cat_obj = $cat;
        break;
    }
}

if (!$active_cat_obj && !empty($categories)) {
    $active_cat_obj = $categories[0];
    $active_category = $active_cat_obj->slug;
}
?>

<div class="wrap wpt-modules">
    <h1 class="wp-heading-inline"><?php _e('Modules', 'wpt-optica-core'); ?></h1>

    <?php if ($action === 'list'): ?>
        <a href="<?php echo admin_url('admin.php?page=wpt-modules&action=new'); ?>" class="page-title-action">
            <?php _e('Add New Module', 'wpt-optica-core'); ?>
        </a>
        <hr class="wp-header-end">

        <div class="wpt-modules-container">
            <!-- Vertical Category Tabs -->
            <div class="wpt-category-tabs">
                <?php foreach ($categories as $category): ?>
                    <?php
                    // Count modules in this category
                    $module_count = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$wpdb->prefix}wpt_available_modules WHERE category_id = %d",
                        $category->id
                    ));
                    $is_active = ($category->slug === $active_category);
                    ?>
                    <a href="<?php echo admin_url('admin.php?page=wpt-modules&category=' . urlencode($category->slug)); ?>"
                       class="wpt-category-tab <?php echo $is_active ? 'active' : ''; ?>">
                        <span class="dashicons dashicons-<?php echo esc_attr($category->icon); ?>"></span>
                        <div class="wpt-tab-content">
                            <strong><?php echo esc_html($category->name); ?></strong>
                            <span class="wpt-module-count"><?php echo $module_count; ?> module<?php echo $module_count != 1 ? '' : ''; ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Modules List for Active Category -->
            <div class="wpt-modules-list">
                <?php if ($active_cat_obj): ?>
                    <div class="wpt-category-header">
                        <h2>
                            <span class="dashicons dashicons-<?php echo esc_attr($active_cat_obj->icon); ?>"></span>
                            <?php echo esc_html($active_cat_obj->name); ?>
                        </h2>
                        <p class="description"><?php echo esc_html($active_cat_obj->description); ?></p>
                    </div>

                    <?php
                    // Get modules for this category with stats
                    $modules = $wpdb->get_results($wpdb->prepare("
                        SELECT
                            m.*,
                            COUNT(DISTINCT tm.tenant_id) as active_tenants,
                            COUNT(DISTINCT CASE WHEN tm.status = 'active' THEN tm.tenant_id END) as active_count
                        FROM {$wpdb->prefix}wpt_available_modules m
                        LEFT JOIN {$wpdb->prefix}wpt_tenant_modules tm ON m.id = tm.module_id
                        WHERE m.category_id = %d
                        GROUP BY m.id
                        ORDER BY m.title ASC
                    ", $active_cat_obj->id));
                    ?>

                    <?php if (empty($modules)): ?>
                        <div class="wpt-no-modules">
                            <span class="dashicons dashicons-info"></span>
                            <p><?php _e('Nu există module în această categorie', 'wpt-optica-core'); ?></p>
                        </div>
                    <?php else: ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th style="width: 60px;"><?php _e('Logo', 'wpt-optica-core'); ?></th>
                                    <th><?php _e('Titlu', 'wpt-optica-core'); ?></th>
                                    <th><?php _e('Preț', 'wpt-optica-core'); ?></th>
                                    <th><?php _e('Disponibilitate', 'wpt-optica-core'); ?></th>
                                    <th><?php _e('Tenants Activi', 'wpt-optica-core'); ?></th>
                                    <th><?php _e('Status', 'wpt-optica-core'); ?></th>
                                    <th><?php _e('Acțiuni', 'wpt-optica-core'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($modules as $module): ?>
                                    <tr>
                                        <td class="wpt-module-logo">
                                            <?php if (!empty($module->logo)): ?>
                                                <img src="<?php echo esc_url($module->logo); ?>" alt="<?php echo esc_attr($module->title); ?>" style="max-width: 40px; max-height: 40px;">
                                            <?php else: ?>
                                                <span class="dashicons dashicons-admin-generic" style="font-size: 40px; color: #ccc;"></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo esc_html($module->title); ?></strong>
                                            <br>
                                            <span class="description"><?php echo esc_html(wp_trim_words($module->description, 15)); ?></span>
                                        </td>
                                        <td>
                                            <?php if ($module->price > 0): ?>
                                                <strong><?php echo number_format($module->price, 0); ?> RON/lună</strong>
                                            <?php else: ?>
                                                <span class="wpt-badge wpt-badge-success"><?php _e('Gratis', 'wpt-optica-core'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($module->availability_mode === 'all_tenants'): ?>
                                                <span class="wpt-badge wpt-badge-info"><?php _e('Toți tenants', 'wpt-optica-core'); ?></span>
                                            <?php else: ?>
                                                <span class="wpt-badge wpt-badge-warning"><?php _e('Specific tenants', 'wpt-optica-core'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo intval($module->active_count); ?></strong> activi
                                        </td>
                                        <td>
                                            <?php if ($module->is_active): ?>
                                                <span class="wpt-status-badge wpt-status-active"><?php _e('Activ', 'wpt-optica-core'); ?></span>
                                            <?php else: ?>
                                                <span class="wpt-status-badge wpt-status-inactive"><?php _e('Inactiv', 'wpt-optica-core'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="<?php echo admin_url('admin.php?page=wpt-modules&action=edit&id=' . $module->id); ?>" class="button button-small">
                                                <?php _e('Edit', 'wpt-optica-core'); ?>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

    <?php elseif ($action === 'new' || $action === 'edit'): ?>
        <hr class="wp-header-end">

        <p><a href="<?php echo admin_url('admin.php?page=wpt-modules'); ?>">&larr; <?php _e('Back to Modules', 'wpt-optica-core'); ?></a></p>

        <?php
        // Redirect to new detail page structure
        if ($action === 'edit' && $module_id > 0) {
            // Include the detail page with tabs
            include WPT_HUB_DIR . 'inc/hub/admin/views/module-edit.php';
        } else {
            // Include the new module form
            include WPT_HUB_DIR . 'inc/hub/admin/views/module-edit.php';
        }
        ?>

    <?php endif; ?>
</div>

<style>
.wpt-modules-container {
    display: flex;
    gap: 20px;
    margin-top: 20px;
}

.wpt-category-tabs {
    flex: 0 0 250px;
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
}

.wpt-category-tab {
    display: flex;
    align-items: center;
    padding: 12px 16px;
    text-decoration: none;
    color: #2271b1;
    border-bottom: 1px solid #f0f0f1;
    transition: background 0.2s;
}

.wpt-category-tab:hover {
    background: #f6f7f7;
}

.wpt-category-tab.active {
    background: #2271b1;
    color: #fff;
}

.wpt-category-tab .dashicons {
    font-size: 20px;
    width: 20px;
    height: 20px;
    margin-right: 12px;
}

.wpt-tab-content {
    flex: 1;
}

.wpt-tab-content strong {
    display: block;
    font-size: 14px;
}

.wpt-module-count {
    font-size: 12px;
    opacity: 0.8;
}

.wpt-modules-list {
    flex: 1;
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
}

.wpt-category-header {
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f0f1;
}

.wpt-category-header h2 {
    display: flex;
    align-items: center;
    margin: 0 0 8px 0;
}

.wpt-category-header .dashicons {
    margin-right: 10px;
    color: #2271b1;
}

.wpt-no-modules {
    text-align: center;
    padding: 60px 20px;
    color: #646970;
}

.wpt-no-modules .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    opacity: 0.3;
}

.wpt-badge {
    display: inline-block;
    padding: 4px 8px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    border-radius: 3px;
}

.wpt-badge-success {
    background: #00a32a;
    color: #fff;
}

.wpt-badge-info {
    background: #2271b1;
    color: #fff;
}

.wpt-badge-warning {
    background: #dba617;
    color: #fff;
}

.wpt-status-badge {
    display: inline-block;
    padding: 4px 10px;
    font-size: 12px;
    border-radius: 3px;
    font-weight: 500;
}

.wpt-status-active {
    background: #d5f4e6;
    color: #00633b;
}

.wpt-status-inactive {
    background: #f0f0f1;
    color: #646970;
}

.wpt-module-logo {
    text-align: center;
}
</style>
