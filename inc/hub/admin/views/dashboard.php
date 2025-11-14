<?php
/**
 * HUB Dashboard View
 *
 * @package WPT_Optica_Core
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Get statistics
$stats = array(
    'tenants' => array(
        'total' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wpt_tenants"),
        'active' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wpt_tenants WHERE status = 'active'"),
        'pending' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wpt_tenants WHERE status = 'pending'"),
        'suspended' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wpt_tenants WHERE status = 'suspended'"),
    ),
    'modules' => array(
        'total' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wpt_available_modules"),
        'active' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wpt_available_modules WHERE is_active = 1"),
    ),
    'releases' => array(
        'total' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wpt_releases"),
        'latest' => $wpdb->get_row("SELECT * FROM {$wpdb->prefix}wpt_releases ORDER BY created_at DESC LIMIT 1"),
    ),
);

// Recent tenants with brand and user info
$recent_tenants = $wpdb->get_results("
    SELECT
        t.*,
        b.post_title as brand_name,
        u.display_name as user_name,
        p.name as plan_name
    FROM {$wpdb->prefix}wpt_tenants t
    LEFT JOIN {$wpdb->prefix}posts b ON t.brand_id = b.ID
    LEFT JOIN {$wpdb->prefix}users u ON t.hub_user_id = u.ID
    LEFT JOIN {$wpdb->prefix}wpt_plans p ON t.plan_id = p.id
    ORDER BY t.created_at DESC
    LIMIT 5
");

// Recent analytics (placeholder - will be populated by actual data)
$total_pageviews = $wpdb->get_var("SELECT SUM(pageviews) FROM {$wpdb->prefix}wpt_analytics WHERE period >= DATE_SUB(NOW(), INTERVAL 30 DAY)") ?? 0;
$total_visitors = $wpdb->get_var("SELECT SUM(visitors) FROM {$wpdb->prefix}wpt_analytics WHERE period >= DATE_SUB(NOW(), INTERVAL 30 DAY)") ?? 0;
?>

<div class="wrap wpt-dashboard">
    <h1><?php _e('WPT Platform Dashboard', 'wpt-optica-core'); ?></h1>

    <div class="wpt-stats-grid">
        <!-- Tenants Stats -->
        <div class="wpt-stat-card">
            <div class="wpt-stat-icon">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="wpt-stat-content">
                <h3><?php _e('Tenants', 'wpt-optica-core'); ?></h3>
                <div class="wpt-stat-number"><?php echo esc_html($stats['tenants']['total']); ?></div>
                <div class="wpt-stat-details">
                    <span class="wpt-stat-active"><?php echo esc_html($stats['tenants']['active']); ?> <?php _e('active', 'wpt-optica-core'); ?></span>
                    <span class="wpt-stat-pending"><?php echo esc_html($stats['tenants']['pending']); ?> <?php _e('pending', 'wpt-optica-core'); ?></span>
                    <span class="wpt-stat-suspended"><?php echo esc_html($stats['tenants']['suspended']); ?> <?php _e('suspended', 'wpt-optica-core'); ?></span>
                </div>
            </div>
        </div>

        <!-- Modules Stats -->
        <div class="wpt-stat-card">
            <div class="wpt-stat-icon">
                <span class="dashicons dashicons-admin-plugins"></span>
            </div>
            <div class="wpt-stat-content">
                <h3><?php _e('Modules', 'wpt-optica-core'); ?></h3>
                <div class="wpt-stat-number"><?php echo esc_html($stats['modules']['total']); ?></div>
                <div class="wpt-stat-details">
                    <span class="wpt-stat-active"><?php echo esc_html($stats['modules']['active']); ?> <?php _e('active', 'wpt-optica-core'); ?></span>
                </div>
            </div>
        </div>

        <!-- Analytics Stats -->
        <div class="wpt-stat-card">
            <div class="wpt-stat-icon">
                <span class="dashicons dashicons-chart-bar"></span>
            </div>
            <div class="wpt-stat-content">
                <h3><?php _e('Analytics (30 days)', 'wpt-optica-core'); ?></h3>
                <div class="wpt-stat-number"><?php echo number_format($total_pageviews); ?></div>
                <div class="wpt-stat-details">
                    <span><?php echo number_format($total_visitors); ?> <?php _e('visitors', 'wpt-optica-core'); ?></span>
                </div>
            </div>
        </div>

        <!-- Releases Stats -->
        <div class="wpt-stat-card">
            <div class="wpt-stat-icon">
                <span class="dashicons dashicons-update"></span>
            </div>
            <div class="wpt-stat-content">
                <h3><?php _e('Releases', 'wpt-optica-core'); ?></h3>
                <div class="wpt-stat-number"><?php echo esc_html($stats['releases']['total']); ?></div>
                <div class="wpt-stat-details">
                    <?php if ($stats['releases']['latest']): ?>
                        <span><?php _e('Latest:', 'wpt-optica-core'); ?> v<?php echo esc_html($stats['releases']['latest']->version); ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="wpt-dashboard-grid">
        <!-- Recent Tenants -->
        <div class="wpt-dashboard-section">
            <div class="wpt-section-header">
                <h2><?php _e('Recent Tenants', 'wpt-optica-core'); ?></h2>
                <a href="<?php echo admin_url('admin.php?page=wpt-tenants'); ?>" class="button button-secondary">
                    <?php _e('View All', 'wpt-optica-core'); ?>
                </a>
            </div>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Brand', 'wpt-optica-core'); ?></th>
                        <th><?php _e('User', 'wpt-optica-core'); ?></th>
                        <th><?php _e('Site URL', 'wpt-optica-core'); ?></th>
                        <th><?php _e('Plan', 'wpt-optica-core'); ?></th>
                        <th><?php _e('Status', 'wpt-optica-core'); ?></th>
                        <th><?php _e('Created', 'wpt-optica-core'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent_tenants)): ?>
                        <tr>
                            <td colspan="6"><?php _e('No tenants found', 'wpt-optica-core'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recent_tenants as $tenant): ?>
                            <tr>
                                <td>
                                    <strong>
                                        <a href="<?php echo admin_url('admin.php?page=wpt-tenants&action=edit&id=' . $tenant->id); ?>">
                                            <?php echo esc_html($tenant->brand_name ?: __('N/A', 'wpt-optica-core')); ?>
                                        </a>
                                    </strong>
                                </td>
                                <td><?php echo esc_html($tenant->user_name ?: __('N/A', 'wpt-optica-core')); ?></td>
                                <td>
                                    <?php if ($tenant->site_url): ?>
                                        <a href="<?php echo esc_url($tenant->site_url); ?>" target="_blank">
                                            <?php echo esc_html($tenant->site_url); ?>
                                        </a>
                                    <?php else: ?>
                                        <em><?php _e('Pending', 'wpt-optica-core'); ?></em>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($tenant->plan_name ?: __('None', 'wpt-optica-core')); ?></td>
                                <td>
                                    <span class="wpt-status-badge wpt-status-<?php echo esc_attr($tenant->status); ?>">
                                        <?php echo esc_html(ucfirst(str_replace('_', ' ', $tenant->status))); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($tenant->created_at))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Quick Actions -->
        <div class="wpt-dashboard-section">
            <div class="wpt-section-header">
                <h2><?php _e('Quick Actions', 'wpt-optica-core'); ?></h2>
            </div>
            <div class="wpt-quick-actions">
                <a href="<?php echo admin_url('admin.php?page=wpt-tenants&action=new'); ?>" class="wpt-action-button">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php _e('Add New Tenant', 'wpt-optica-core'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=wpt-modules&action=new'); ?>" class="wpt-action-button">
                    <span class="dashicons dashicons-admin-plugins"></span>
                    <?php _e('Add New Module', 'wpt-optica-core'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=wpt-releases&action=new'); ?>" class="wpt-action-button">
                    <span class="dashicons dashicons-upload"></span>
                    <?php _e('Upload New Release', 'wpt-optica-core'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=wpt-analytics'); ?>" class="wpt-action-button">
                    <span class="dashicons dashicons-chart-line"></span>
                    <?php _e('View Analytics', 'wpt-optica-core'); ?>
                </a>
            </div>

            <!-- System Status -->
            <div class="wpt-section-header" style="margin-top: 30px;">
                <h2><?php _e('System Status', 'wpt-optica-core'); ?></h2>
            </div>
            <div class="wpt-system-status">
                <div class="wpt-status-item">
                    <span class="wpt-status-label"><?php _e('Plugin Version:', 'wpt-optica-core'); ?></span>
                    <span class="wpt-status-value"><?php echo esc_html(WPT_CORE_VERSION); ?></span>
                </div>
                <div class="wpt-status-item">
                    <span class="wpt-status-label"><?php _e('Database Version:', 'wpt-optica-core'); ?></span>
                    <span class="wpt-status-value"><?php echo esc_html(get_option('wpt_db_version', '1.0.0')); ?></span>
                </div>
                <div class="wpt-status-item">
                    <span class="wpt-status-label"><?php _e('REST API:', 'wpt-optica-core'); ?></span>
                    <span class="wpt-status-value wpt-status-ok"><?php _e('Active', 'wpt-optica-core'); ?></span>
                </div>
                <div class="wpt-status-item">
                    <span class="wpt-status-label"><?php _e('ACF Pro:', 'wpt-optica-core'); ?></span>
                    <span class="wpt-status-value <?php echo function_exists('acf') ? 'wpt-status-ok' : 'wpt-status-error'; ?>">
                        <?php echo function_exists('acf') ? __('Active', 'wpt-optica-core') : __('Not Active', 'wpt-optica-core'); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
