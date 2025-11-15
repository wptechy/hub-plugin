<?php
/**
 * Tenant Modules Configuration Section
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

// Get all available modules grouped by category
global $wpdb;

$modules_by_category = $wpdb->get_results("
    SELECT
        m.*,
        c.name as category_name,
        c.slug as category_slug,
        c.icon as category_icon,
        tm.status as tenant_status,
        tm.activated_at
    FROM {$wpdb->prefix}wpt_available_modules m
    LEFT JOIN {$wpdb->prefix}wpt_module_categories c ON m.category_id = c.id
    LEFT JOIN {$wpdb->prefix}wpt_tenant_modules tm ON m.id = tm.module_id AND tm.tenant_id = {$tenant_id}
    WHERE m.is_active = 1
    ORDER BY c.name ASC, m.title ASC
");

// Group modules by category
$grouped_modules = array();
foreach ($modules_by_category as $module) {
    $cat_slug = $module->category_slug ?: 'uncategorized';
    if (!isset($grouped_modules[$cat_slug])) {
        $grouped_modules[$cat_slug] = array(
            'name' => $module->category_name ?: 'Uncategorized',
            'icon' => $module->category_icon ?: 'admin-plugins',
            'modules' => array()
        );
    }
    $grouped_modules[$cat_slug]['modules'][] = $module;
}

// Get currently enabled modules for this tenant
$enabled_modules = $wpdb->get_col($wpdb->prepare("
    SELECT module_id
    FROM {$wpdb->prefix}wpt_tenant_modules
    WHERE tenant_id = %d AND status = 'active'
", $tenant_id));
?>

<div class="wpt-tenant-modules-section">
    <p class="description" style="margin-bottom: 20px;">
        <?php _e('Select which modules should be available for this tenant. Changes are only applied when you click "Push to Tenant".', 'wpt-optica-core'); ?>
    </p>

    <?php if (empty($grouped_modules)): ?>
        <p class="no-items"><?php _e('No modules available.', 'wpt-optica-core'); ?></p>
    <?php else: ?>
        <?php foreach ($grouped_modules as $cat_slug => $category): ?>
            <div class="wpt-module-category-section">
                <h3 class="wpt-category-header">
                    <span class="dashicons dashicons-<?php echo esc_attr($category['icon']); ?>"></span>
                    <?php echo esc_html($category['name']); ?>
                    <span class="wpt-category-count">(<?php echo count($category['modules']); ?>)</span>
                </h3>

                <div class="wpt-modules-grid-small">
                    <?php foreach ($category['modules'] as $module): ?>
                        <?php
                        $is_enabled = in_array($module->id, $enabled_modules);
                        $is_active_on_tenant = $module->tenant_status === 'active';
                        ?>
                        <label class="wpt-module-checkbox-item <?php echo $is_enabled ? 'enabled' : ''; ?>">
                            <input
                                type="checkbox"
                                name="enabled_modules[]"
                                value="<?php echo esc_attr($module->id); ?>"
                                <?php checked($is_enabled); ?>
                            />
                            <div class="module-info">
                                <div class="module-header">
                                    <span class="module-icon"><?php echo esc_html($module->icon ?: '📦'); ?></span>
                                    <strong class="module-title"><?php echo esc_html($module->title); ?></strong>
                                </div>
                                <div class="module-meta">
                                    <?php if ($module->price > 0): ?>
                                        <span class="module-price"><?php echo esc_html(number_format($module->price, 0)); ?> RON/lună</span>
                                    <?php else: ?>
                                        <span class="module-price free">Gratis</span>
                                    <?php endif; ?>
                                    <?php if ($is_active_on_tenant): ?>
                                        <span class="module-status active">● Activ</span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($module->description): ?>
                                    <p class="module-description"><?php echo esc_html(wp_trim_words($module->description, 12)); ?></p>
                                <?php endif; ?>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="wpt-module-push-section">
            <button type="button" id="push-modules-btn" class="button button-primary button-large">
                <span class="dashicons dashicons-update"></span>
                <?php _e('Push Modules to Tenant', 'wpt-optica-core'); ?>
            </button>
            <span class="push-modules-status"></span>
            <p class="description">
                <?php _e('This will activate/deactivate the selected modules on the tenant site.', 'wpt-optica-core'); ?>
            </p>
        </div>
    <?php endif; ?>
</div>

<style>
.wpt-tenant-modules-section {
    background: #fff;
    padding: 20px;
}

.wpt-module-category-section {
    margin-bottom: 30px;
    border: 1px solid #dcdcde;
    border-radius: 4px;
    overflow: hidden;
}

.wpt-category-header {
    margin: 0;
    padding: 15px 20px;
    background: #f6f7f7;
    border-bottom: 1px solid #dcdcde;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 16px;
    font-weight: 600;
}

.wpt-category-header .dashicons {
    color: #2271b1;
    font-size: 20px;
    width: 20px;
    height: 20px;
}

.wpt-category-count {
    color: #646970;
    font-size: 14px;
    font-weight: 400;
}

.wpt-modules-grid-small {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 12px;
    padding: 15px;
}

.wpt-module-checkbox-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px;
    border: 1px solid #dcdcde;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s;
    background: #fff;
}

.wpt-module-checkbox-item:hover {
    border-color: #2271b1;
    background: #f6f7f7;
}

.wpt-module-checkbox-item.enabled {
    border-color: #2271b1;
    background: #f0f6fc;
}

.wpt-module-checkbox-item input[type="checkbox"] {
    margin: 2px 0 0 0;
    flex-shrink: 0;
}

.module-info {
    flex: 1;
    min-width: 0;
}

.module-header {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 6px;
}

.module-icon {
    font-size: 18px;
    line-height: 1;
}

.module-title {
    font-size: 14px;
    color: #1d2327;
    line-height: 1.4;
}

.module-meta {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 6px;
    flex-wrap: wrap;
}

.module-price {
    font-size: 12px;
    font-weight: 600;
    color: #2271b1;
}

.module-price.free {
    color: #00a32a;
}

.module-status {
    font-size: 11px;
    color: #00a32a;
}

.module-description {
    margin: 0;
    font-size: 12px;
    color: #646970;
    line-height: 1.4;
}

.wpt-module-push-section {
    margin-top: 30px;
    padding: 20px;
    background: #f0f6fc;
    border: 1px solid #c3e0f7;
    border-radius: 4px;
    display: flex;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
}

.wpt-module-push-section .button-large {
    height: 40px;
    line-height: 40px;
    padding: 0 20px;
}

.wpt-module-push-section .dashicons {
    margin-right: 5px;
    vertical-align: middle;
    line-height: 40px;
}

.push-modules-status {
    font-weight: 500;
    font-size: 14px;
}

.push-modules-status.success {
    color: #00a32a;
}

.push-modules-status.success::before {
    content: "\2713 ";
    font-weight: bold;
}

.push-modules-status.error {
    color: #d63638;
}

.push-modules-status.error::before {
    content: "\2717 ";
    font-weight: bold;
}

@media (max-width: 782px) {
    .wpt-modules-grid-small {
        grid-template-columns: 1fr;
    }

    .wpt-module-push-section {
        flex-direction: column;
        align-items: stretch;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    $('#push-modules-btn').on('click', function() {
        const $btn = $(this);
        const $status = $('.push-modules-status');
        const tenantId = <?php echo intval($tenant_id); ?>;

        // Get selected modules
        const selectedModules = [];
        $('input[name="enabled_modules[]"]:checked').each(function() {
            selectedModules.push($(this).val());
        });

        // Disable button
        $btn.prop('disabled', true);
        $status.removeClass('success error').text('<?php esc_js(_e('Pushing...', 'wpt-optica-core')); ?>');

        // Send AJAX request
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wpt_push_modules_to_tenant',
                nonce: '<?php echo wp_create_nonce('wpt_sync_config_nonce'); ?>',
                tenant_id: tenantId,
                enabled_modules: selectedModules
            },
            success: function(response) {
                console.log('Push modules response:', response);

                if (response.success) {
                    $status.addClass('success').text('<?php esc_js(_e('Modules pushed successfully!', 'wpt-optica-core')); ?>');

                    setTimeout(function() {
                        $status.text('');
                    }, 5000);
                } else {
                    $status.addClass('error').text(response.data.message || '<?php esc_js(_e('Error pushing modules', 'wpt-optica-core')); ?>');
                }
            },
            error: function() {
                $status.addClass('error').text('<?php esc_js(_e('Error pushing modules', 'wpt-optica-core')); ?>');
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });
});
</script>
