<?php
/**
 * Tenant Modules Configuration Section
 * Shows all modules with availability and activation status
 *
 * @package WPT_Optica_Core
 */

if (!defined('ABSPATH')) {
    exit;
}

// Only show on edit mode
if ($action !== 'edit' || !isset($tenant)) {
    return;
}

global $wpdb;

// Get filter
$filter = isset($_GET['module_filter']) ? sanitize_text_field($_GET['module_filter']) : 'all';

// Get all modules with availability and activation info
$modules = $wpdb->get_results($wpdb->prepare("
    SELECT
        m.*,
        c.name as category_name,
        c.icon as category_icon,
        tm.id as tenant_module_id,
        tm.status as tenant_status,
        tm.activated_by,
        tm.deactivated_by,
        tm.activated_at,
        tm.deactivated_at,
        CASE
            WHEN m.availability_mode = 'all_tenants' THEN 1
            WHEN ma.tenant_id IS NOT NULL THEN 1
            ELSE 0
        END as is_available,
        CASE
            WHEN tm.status = 'active' THEN tm.activated_at
            WHEN tm.status = 'inactive' THEN tm.deactivated_at
            ELSE NULL
        END as last_action_date,
        CASE
            WHEN tm.status = 'active' THEN tm.activated_by
            WHEN tm.status = 'inactive' THEN tm.deactivated_by
            ELSE NULL
        END as last_action_by
    FROM {$wpdb->prefix}wpt_available_modules m
    LEFT JOIN {$wpdb->prefix}wpt_module_categories c ON m.category_id = c.id
    LEFT JOIN {$wpdb->prefix}wpt_module_availability ma ON m.id = ma.module_id AND ma.tenant_id = %d
    LEFT JOIN {$wpdb->prefix}wpt_tenant_modules tm ON m.id = tm.module_id AND tm.tenant_id = %d
    WHERE m.is_active = 1
    ORDER BY c.sort_order ASC, m.title ASC
", $tenant_id, $tenant_id));

// Apply filter
$filtered_modules = array_filter($modules, function($module) use ($filter) {
    if ($filter === 'available') {
        return $module->is_available == 1;
    } elseif ($filter === 'active') {
        return $module->tenant_status === 'active';
    }
    return true; // 'all'
});

// Count stats
$total_modules = count($modules);
$available_modules = count(array_filter($modules, fn($m) => $m->is_available == 1));
$active_modules = count(array_filter($modules, fn($m) => $m->tenant_status === 'active'));
?>

<div class="wpt-tenant-modules-section">
    <div class="wpt-modules-header">
        <div class="wpt-modules-stats">
            <div class="stat-item">
                <span class="stat-number"><?php echo $total_modules; ?></span>
                <span class="stat-label"><?php _e('Total Module', 'wpt-optica-core'); ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?php echo $available_modules; ?></span>
                <span class="stat-label"><?php _e('Disponibile', 'wpt-optica-core'); ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?php echo $active_modules; ?></span>
                <span class="stat-label"><?php _e('Active', 'wpt-optica-core'); ?></span>
            </div>
        </div>

        <div class="wpt-modules-filters">
            <a href="<?php echo admin_url('admin.php?page=wpt-tenants&action=edit&id=' . $tenant_id . '&tab=modules&module_filter=all'); ?>"
               class="filter-btn <?php echo $filter === 'all' ? 'active' : ''; ?>">
                <?php _e('All', 'wpt-optica-core'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=wpt-tenants&action=edit&id=' . $tenant_id . '&tab=modules&module_filter=available'); ?>"
               class="filter-btn <?php echo $filter === 'available' ? 'active' : ''; ?>">
                <?php _e('Available Only', 'wpt-optica-core'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=wpt-tenants&action=edit&id=' . $tenant_id . '&tab=modules&module_filter=active'); ?>"
               class="filter-btn <?php echo $filter === 'active' ? 'active' : ''; ?>">
                <?php _e('Active Only', 'wpt-optica-core'); ?>
            </a>
        </div>
    </div>

    <?php if (empty($filtered_modules)): ?>
        <div class="wpt-no-modules">
            <span class="dashicons dashicons-info"></span>
            <p><?php _e('No modules match the selected filter.', 'wpt-optica-core'); ?></p>
        </div>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped wpt-modules-table">
            <thead>
                <tr>
                    <th style="width: 60px;"><?php _e('Logo', 'wpt-optica-core'); ?></th>
                    <th><?php _e('Module', 'wpt-optica-core'); ?></th>
                    <th><?php _e('Categorie', 'wpt-optica-core'); ?></th>
                    <th><?php _e('Disponibil', 'wpt-optica-core'); ?></th>
                    <th><?php _e('Status', 'wpt-optica-core'); ?></th>
                    <th><?php _e('Ultima acțiune', 'wpt-optica-core'); ?></th>
                    <th><?php _e('Data', 'wpt-optica-core'); ?></th>
                    <th><?php _e('Acțiune', 'wpt-optica-core'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($filtered_modules as $module): ?>
                    <tr class="module-row <?php echo $module->tenant_status === 'active' ? 'module-active' : ''; ?>">
                        <td class="module-logo-cell">
                            <?php if (!empty($module->logo)): ?>
                                <img src="<?php echo esc_url($module->logo); ?>" alt="<?php echo esc_attr($module->title); ?>" class="module-logo-img">
                            <?php else: ?>
                                <span class="dashicons dashicons-admin-generic module-logo-placeholder"></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?php echo esc_html($module->title); ?></strong>
                            <?php if ($module->price > 0): ?>
                                <br><span class="module-price"><?php echo number_format($module->price, 0); ?> RON/lună</span>
                            <?php else: ?>
                                <br><span class="module-price-free"><?php _e('Gratis', 'wpt-optica-core'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="dashicons dashicons-<?php echo esc_attr($module->category_icon); ?>"></span>
                            <?php echo esc_html($module->category_name); ?>
                        </td>
                        <td>
                            <?php if ($module->is_available): ?>
                                <span class="wpt-badge wpt-badge-success"><?php _e('Yes', 'wpt-optica-core'); ?></span>
                            <?php else: ?>
                                <span class="wpt-badge wpt-badge-inactive"><?php _e('No', 'wpt-optica-core'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($module->tenant_status === 'active'): ?>
                                <span class="wpt-status-badge wpt-status-active"><?php _e('Activ', 'wpt-optica-core'); ?></span>
                            <?php elseif ($module->tenant_status === 'inactive'): ?>
                                <span class="wpt-status-badge wpt-status-inactive"><?php _e('Inactiv', 'wpt-optica-core'); ?></span>
                            <?php else: ?>
                                <span class="wpt-status-badge wpt-status-never"><?php _e('—', 'wpt-optica-core'); ?></span>
                            <?php endif; ?>

                            <?php if ($module->last_action_by): ?>
                                <?php if ($module->last_action_by === 'admin'): ?>
                                    <br><span class="wpt-badge wpt-badge-admin"><?php _e('Forced by Admin', 'wpt-optica-core'); ?></span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($module->last_action_by): ?>
                                <?php if ($module->last_action_by === 'admin'): ?>
                                    <span class="wpt-badge wpt-badge-warning"><?php _e('Admin', 'wpt-optica-core'); ?></span>
                                <?php else: ?>
                                    <span class="wpt-badge wpt-badge-info"><?php _e('Tenant', 'wpt-optica-core'); ?></span>
                                <?php endif; ?>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($module->last_action_date): ?>
                                <?php echo esc_html(date_i18n('d M Y, H:i', strtotime($module->last_action_date))); ?>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($module->is_available): ?>
                                <button type="button"
                                        class="button button-small wpt-toggle-module"
                                        data-module-id="<?php echo esc_attr($module->id); ?>"
                                        data-tenant-id="<?php echo esc_attr($tenant_id); ?>"
                                        data-current-status="<?php echo esc_attr($module->tenant_status ?: 'inactive'); ?>">
                                    <?php if ($module->tenant_status === 'active'): ?>
                                        <?php _e('Force Deactivate', 'wpt-optica-core'); ?>
                                    <?php else: ?>
                                        <?php _e('Force Activate', 'wpt-optica-core'); ?>
                                    <?php endif; ?>
                                </button>
                            <?php else: ?>
                                <span class="description"><?php _e('Not available', 'wpt-optica-core'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<style>
.wpt-tenant-modules-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
}

.wpt-modules-header {
    padding: 20px;
    border-bottom: 1px solid #dcdcde;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
}

.wpt-modules-stats {
    display: flex;
    gap: 30px;
}

.stat-item {
    text-align: center;
}

.stat-number {
    display: block;
    font-size: 28px;
    font-weight: 700;
    color: #2271b1;
    line-height: 1;
}

.stat-label {
    display: block;
    font-size: 12px;
    color: #646970;
    margin-top: 5px;
    text-transform: uppercase;
}

.wpt-modules-filters {
    display: flex;
    gap: 10px;
}

.filter-btn {
    padding: 8px 16px;
    border: 1px solid #c3c4c7;
    border-radius: 3px;
    text-decoration: none;
    color: #2c3338;
    background: #fff;
    transition: all 0.2s;
}

.filter-btn:hover {
    background: #f6f7f7;
    border-color: #2271b1;
    color: #2271b1;
}

.filter-btn.active {
    background: #2271b1;
    border-color: #2271b1;
    color: #fff;
    font-weight: 600;
}

.wpt-modules-table {
    margin: 0;
}

.module-logo-cell {
    text-align: center;
}

.module-logo-img {
    max-width: 40px;
    max-height: 40px;
    display: block;
    margin: 0 auto;
}

.module-logo-placeholder {
    font-size: 40px;
    color: #dcdcde;
}

.module-price {
    font-size: 12px;
    color: #2271b1;
    font-weight: 600;
}

.module-price-free {
    font-size: 12px;
    color: #00a32a;
    font-weight: 600;
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
    background: #d5f4e6;
    color: #00633b;
}

.wpt-badge-inactive {
    background: #f0f0f1;
    color: #646970;
}

.wpt-badge-info {
    background: #e5f5fa;
    color: #006088;
}

.wpt-badge-warning {
    background: #fcf3e6;
    color: #8b5a00;
}

.wpt-badge-admin {
    background: #fcf3e6;
    color: #8b5a00;
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

.wpt-status-never {
    background: transparent;
    color: #a7aaad;
}

.module-row.module-active {
    background: #f6fcf7;
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
</style>

<script>
jQuery(document).ready(function($) {
    $('.wpt-toggle-module').on('click', function() {
        const $btn = $(this);
        const moduleId = $btn.data('module-id');
        const tenantId = $btn.data('tenant-id');
        const currentStatus = $btn.data('current-status');
        const newStatus = currentStatus === 'active' ? 'inactive' : 'active';

        $btn.prop('disabled', true).text('<?php esc_js(_e('Processing...', 'wpt-optica-core')); ?>');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wpt_toggle_tenant_module',
                nonce: '<?php echo wp_create_nonce('wpt_toggle_module'); ?>',
                module_id: moduleId,
                tenant_id: tenantId,
                new_status: newStatus
            },
            success: function(response) {
                if (response.success) {
                    // Reload page to show updated data
                    location.reload();
                } else {
                    alert(response.data.message || '<?php esc_js(_e('Error toggling module', 'wpt-optica-core')); ?>');
                    $btn.prop('disabled', false);
                }
            },
            error: function() {
                alert('<?php esc_js(_e('Error toggling module', 'wpt-optica-core')); ?>');
                $btn.prop('disabled', false);
            }
        });
    });
});
</script>
