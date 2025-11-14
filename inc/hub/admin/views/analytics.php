<?php
/**
 * Analytics Dashboard View
 *
 * @package WPT_Optica_Core
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Get date range
$days = isset($_GET['days']) ? intval($_GET['days']) : 30;
$start_date = date('Y-m-d', strtotime("-{$days} days"));

// Aggregate analytics data
$analytics = $wpdb->get_results($wpdb->prepare("
    SELECT
        DATE(period) as date,
        COALESCE(SUM(visitors), 0) as visitors,
        COALESCE(SUM(pageviews), 0) as pageviews,
        COALESCE(SUM(appointments), 0) as appointments,
        COALESCE(SUM(offers), 0) as offers,
        COALESCE(SUM(jobs), 0) as jobs
    FROM {$wpdb->prefix}wpt_analytics
    WHERE period >= %s
    GROUP BY DATE(period)
    ORDER BY date ASC
", $start_date));

// Calculate totals
$totals = $wpdb->get_row($wpdb->prepare("
    SELECT
        COALESCE(SUM(visitors), 0) as visitors,
        COALESCE(SUM(pageviews), 0) as pageviews,
        COALESCE(SUM(appointments), 0) as appointments,
        COALESCE(SUM(offers), 0) as offers,
        COALESCE(SUM(jobs), 0) as jobs
    FROM {$wpdb->prefix}wpt_analytics
    WHERE period >= %s
", $start_date));

// Get top tenants
$top_tenants = $wpdb->get_results($wpdb->prepare("
    SELECT
        t.site_url,
        COALESCE(SUM(a.pageviews), 0) as total_pageviews,
        COALESCE(SUM(a.visitors), 0) as total_visitors
    FROM {$wpdb->prefix}wpt_analytics a
    INNER JOIN {$wpdb->prefix}wpt_tenants t ON a.tenant_id = t.id
    WHERE a.period >= %s
    GROUP BY a.tenant_id
    ORDER BY total_pageviews DESC
    LIMIT 10
", $start_date));

// Prepare chart data
$chart_labels = array();
$chart_visitors = array();
$chart_pageviews = array();

foreach ($analytics as $day) {
    $chart_labels[] = date_i18n('M j', strtotime($day->date));
    $chart_visitors[] = intval($day->visitors);
    $chart_pageviews[] = intval($day->pageviews);
}
?>

<div class="wrap wpt-analytics">
    <h1><?php _e('Platform Analytics', 'wpt-optica-core'); ?></h1>

    <div class="wpt-analytics-filters">
        <a href="<?php echo admin_url('admin.php?page=wpt-analytics&days=7'); ?>" 
           class="button <?php echo $days === 7 ? 'button-primary' : 'button-secondary'; ?>">
            <?php _e('Last 7 days', 'wpt-optica-core'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=wpt-analytics&days=30'); ?>" 
           class="button <?php echo $days === 30 ? 'button-primary' : 'button-secondary'; ?>">
            <?php _e('Last 30 days', 'wpt-optica-core'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=wpt-analytics&days=90'); ?>" 
           class="button <?php echo $days === 90 ? 'button-primary' : 'button-secondary'; ?>">
            <?php _e('Last 90 days', 'wpt-optica-core'); ?>
        </a>
    </div>

    <div class="wpt-stats-grid">
        <div class="wpt-stat-card">
            <div class="wpt-stat-icon">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="wpt-stat-content">
                <h3><?php _e('Total Visitors', 'wpt-optica-core'); ?></h3>
                <div class="wpt-stat-number"><?php echo number_format($totals->visitors); ?></div>
            </div>
        </div>

        <div class="wpt-stat-card">
            <div class="wpt-stat-icon">
                <span class="dashicons dashicons-visibility"></span>
            </div>
            <div class="wpt-stat-content">
                <h3><?php _e('Page Views', 'wpt-optica-core'); ?></h3>
                <div class="wpt-stat-number"><?php echo number_format($totals->pageviews); ?></div>
            </div>
        </div>

        <div class="wpt-stat-card">
            <div class="wpt-stat-icon">
                <span class="dashicons dashicons-calendar-alt"></span>
            </div>
            <div class="wpt-stat-content">
                <h3><?php _e('Appointments', 'wpt-optica-core'); ?></h3>
                <div class="wpt-stat-number"><?php echo number_format($totals->appointments); ?></div>
            </div>
        </div>

        <div class="wpt-stat-card">
            <div class="wpt-stat-icon">
                <span class="dashicons dashicons-tag"></span>
            </div>
            <div class="wpt-stat-content">
                <h3><?php _e('Offers Viewed', 'wpt-optica-core'); ?></h3>
                <div class="wpt-stat-number"><?php echo number_format($totals->offers); ?></div>
            </div>
        </div>

        <div class="wpt-stat-card">
            <div class="wpt-stat-icon">
                <span class="dashicons dashicons-businessman"></span>
            </div>
            <div class="wpt-stat-content">
                <h3><?php _e('Job Applications', 'wpt-optica-core'); ?></h3>
                <div class="wpt-stat-number"><?php echo number_format($totals->jobs); ?></div>
            </div>
        </div>

        <div class="wpt-stat-card">
            <div class="wpt-stat-icon">
                <span class="dashicons dashicons-chart-line"></span>
            </div>
            <div class="wpt-stat-content">
                <h3><?php _e('Avg. Pages/Visit', 'wpt-optica-core'); ?></h3>
                <div class="wpt-stat-number">
                    <?php echo $totals->visitors > 0 ? number_format($totals->pageviews / $totals->visitors, 2) : '0'; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="wpt-dashboard-grid">
        <!-- Chart -->
        <div class="wpt-dashboard-section wpt-chart-section">
            <div class="wpt-section-header">
                <h2><?php _e('Traffic Overview', 'wpt-optica-core'); ?></h2>
            </div>
            <canvas id="wpt-analytics-chart" width="400" height="200"></canvas>
        </div>

        <!-- Top Tenants -->
        <div class="wpt-dashboard-section">
            <div class="wpt-section-header">
                <h2><?php _e('Top Performing Sites', 'wpt-optica-core'); ?></h2>
            </div>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Site', 'wpt-optica-core'); ?></th>
                        <th><?php _e('Visitors', 'wpt-optica-core'); ?></th>
                        <th><?php _e('Page Views', 'wpt-optica-core'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($top_tenants)): ?>
                        <tr>
                            <td colspan="3"><?php _e('No data available', 'wpt-optica-core'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($top_tenants as $tenant): ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($tenant->site_url ?: __('N/A', 'wpt-optica-core')); ?></strong>
                                </td>
                                <td><?php echo number_format($tenant->total_visitors); ?></td>
                                <td><?php echo number_format($tenant->total_pageviews); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    const ctx = document.getElementById('wpt-analytics-chart').getContext('2d');
    
    // Simple line chart implementation (can be enhanced with Chart.js later)
    const labels = <?php echo json_encode($chart_labels); ?>;
    const visitors = <?php echo json_encode($chart_visitors); ?>;
    const pageviews = <?php echo json_encode($chart_pageviews); ?>;

    // Placeholder for chart - integrate Chart.js library for production
    console.log('Chart data:', {labels, visitors, pageviews});
});
</script>
