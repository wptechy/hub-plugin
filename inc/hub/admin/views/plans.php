<?php
/**
 * Plans & Pricing Management View
 * Tab-based interface: Plans | Add-ons
 *
 * @package WPT_Optica_Core
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'plans';
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
$item_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['wpt_nonce']) && wp_verify_nonce($_POST['wpt_nonce'], 'wpt_save_item')) {

    if ($active_tab === 'plans') {
        // Save Plan
        $plan_data = array(
            'name' => sanitize_text_field($_POST['name']),
            'slug' => sanitize_title($_POST['slug']),
            'price' => floatval($_POST['price']),
            'billing_period' => sanitize_text_field($_POST['billing_period']),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'features' => json_encode(array(
                'brand_listing' => isset($_POST['brand_listing']),
                'locations' => intval($_POST['locations']),
                'location_type' => sanitize_text_field($_POST['location_type']),
                'offers' => intval($_POST['offers']),
                'jobs' => intval($_POST['jobs']),
                'candidati_access' => isset($_POST['candidati_access']),
                'furnizori_access' => isset($_POST['furnizori_access']),
                'tenant_site' => isset($_POST['tenant_site']),
                'support' => sanitize_text_field($_POST['support']),
            )),
        );

        if ($action === 'edit' && $item_id > 0) {
            WPT_Plan_Manager::update_plan($item_id, $plan_data);
            echo '<div class="notice notice-success"><p>' . __('Plan updated successfully', 'wpt-optica-core') . '</p></div>';
        } else {
            $new_id = WPT_Plan_Manager::create_plan($plan_data);
            if ($new_id) {
                echo '<div class="notice notice-success"><p>' . __('Plan created successfully', 'wpt-optica-core') . '</p></div>';
                echo '<script>window.location.href = "' . admin_url('admin.php?page=wpt-plans&tab=plans&action=edit&id=' . $new_id) . '";</script>';
            }
        }
    } else {
        // Save Addon
        $addon_data = array(
            'addon_name' => sanitize_text_field($_POST['addon_name']),
            'addon_slug' => sanitize_title($_POST['addon_slug']),
            'monthly_price' => floatval($_POST['monthly_price']),
            'description' => wp_kses_post($_POST['description']),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        );

        if ($action === 'edit' && $item_id > 0) {
            WPT_Addon_Manager::update_addon($item_id, $addon_data);
            echo '<div class="notice notice-success"><p>' . __('Addon updated successfully', 'wpt-optica-core') . '</p></div>';
        } else {
            $new_id = WPT_Addon_Manager::create_addon($addon_data);
            if ($new_id) {
                echo '<div class="notice notice-success"><p>' . __('Addon created successfully', 'wpt-optica-core') . '</p></div>';
                echo '<script>window.location.href = "' . admin_url('admin.php?page=wpt-plans&tab=addons&action=edit&id=' . $new_id) . '";</script>';
            }
        }
    }
}

// Handle delete action
if ($action === 'delete' && $item_id > 0 && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'delete_item_' . $item_id)) {
    if ($active_tab === 'plans') {
        $result = WPT_Plan_Manager::delete_plan($item_id);
        if (is_wp_error($result)) {
            echo '<div class="notice notice-error"><p>' . $result->get_error_message() . '</p></div>';
        } else {
            echo '<div class="notice notice-success"><p>' . __('Plan deleted successfully', 'wpt-optica-core') . '</p></div>';
            echo '<script>window.location.href = "' . admin_url('admin.php?page=wpt-plans&tab=plans') . '";</script>';
        }
    } else {
        $result = WPT_Addon_Manager::delete_addon($item_id);
        if (is_wp_error($result)) {
            echo '<div class="notice notice-error"><p>' . $result->get_error_message() . '</p></div>';
        } else {
            echo '<div class="notice notice-success"><p>' . __('Addon deleted successfully', 'wpt-optica-core') . '</p></div>';
            echo '<script>window.location.href = "' . admin_url('admin.php?page=wpt-plans&tab=addons') . '";</script>';
        }
    }
}
?>

<div class="wrap wpt-plans-pricing">
    <h1 class="wp-heading-inline"><?php _e('Plans & Pricing', 'wpt-optica-core'); ?></h1>

    <?php if ($action === 'list'): ?>
        <a href="<?php echo admin_url('admin.php?page=wpt-plans&tab=' . $active_tab . '&action=new'); ?>" class="page-title-action">
            <?php echo $active_tab === 'plans' ? __('Add New Plan', 'wpt-optica-core') : __('Add New Addon', 'wpt-optica-core'); ?>
        </a>
    <?php endif; ?>

    <hr class="wp-header-end">

    <!-- Tabs -->
    <h2 class="nav-tab-wrapper">
        <a href="<?php echo admin_url('admin.php?page=wpt-plans&tab=plans'); ?>"
           class="nav-tab <?php echo $active_tab === 'plans' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Planuri', 'wpt-optica-core'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=wpt-plans&tab=addons'); ?>"
           class="nav-tab <?php echo $active_tab === 'addons' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Add-on-uri', 'wpt-optica-core'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=wpt-plans&tab=mappings'); ?>"
           class="nav-tab <?php echo $active_tab === 'mappings' ? 'nav-tab-active' : ''; ?>">
            📋 <?php _e('Mapări funcționalități', 'wpt-optica-core'); ?>
        </a>
    </h2>

    <div class="wpt-tab-content">
        <?php
        if ($active_tab === 'plans') {
            include __DIR__ . '/plans-tab-plans.php';
        } elseif ($active_tab === 'mappings') {
            include __DIR__ . '/plans-tab-mappings.php';
        } else {
            include __DIR__ . '/plans-tab-addons.php';
        }
        ?>
    </div>
</div>

<style>
.wpt-plans-pricing {
    max-width: 1400px;
}

.wpt-tab-content {
    background: #fff;
    padding: 20px;
    margin-top: 0;
    border: 1px solid #ccd0d4;
    border-top: none;
}

.wpt-plans-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 24px;
    margin-top: 20px;
}

.wpt-plan-card {
    background: #fff;
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    padding: 0;
    transition: all 0.3s ease;
    overflow: hidden;
}

.wpt-plan-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
    border-color: #2271b1;
}

.wpt-plan-card.plan-active {
    border-color: #00a32a;
    background: linear-gradient(to bottom, #f6fcf7 0%, #fff 100%);
}

.wpt-plan-card.plan-active:before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #00a32a 0%, #00d084 100%);
}

.plan-header {
    padding: 24px;
    text-align: center;
    border-bottom: 1px solid #f0f0f1;
    background: linear-gradient(135deg, #f6f7f7 0%, #fff 100%);
}

.plan-name {
    font-size: 24px;
    font-weight: 700;
    color: #1d2327;
    margin: 0 0 12px 0;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.plan-price {
    font-size: 48px;
    font-weight: 700;
    color: #2271b1;
    line-height: 1;
}

.plan-price small {
    font-size: 18px;
    font-weight: 400;
    color: #646970;
}

.plan-body {
    padding: 24px;
}

.plan-features {
    list-style: none;
    margin: 0;
    padding: 0;
}

.plan-features li {
    padding: 12px 0;
    border-bottom: 1px solid #f0f0f1;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.plan-features li:last-child {
    border-bottom: none;
}

.plan-features .dashicons {
    color: #00a32a;
    margin-right: 8px;
}

.plan-features .dashicons-no {
    color: #d63638;
}

.plan-footer {
    padding: 20px 24px;
    background: #f9f9f9;
    border-top: 1px solid #f0f0f1;
    display: flex;
    gap: 12px;
    justify-content: space-between;
}

.plan-stats {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-bottom: 16px;
}

.plan-stat {
    text-align: center;
    padding: 12px;
    background: #f6f7f7;
    border-radius: 8px;
}

.plan-stat-value {
    display: block;
    font-size: 28px;
    font-weight: 700;
    color: #2271b1;
}

.plan-stat-label {
    display: block;
    font-size: 12px;
    color: #646970;
    text-transform: uppercase;
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

.button-group {
    display: flex;
    gap: 8px;
}
</style>
