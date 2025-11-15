<?php
/**
 * Tenant Details - Plan & Add-ons Section
 * Display tenant's subscription plan, active add-ons, and quota usage
 *
 * @package WPT_Optica_Core
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Get tenant data (using 'id' parameter from URL, consistent with tenants.php)
$tenant_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$tenant = WPT_Tenant_Manager::get_tenant($tenant_id);

if (!$tenant) {
    echo '<div class="notice notice-error"><p>' . __('Tenant invalid.', 'wpt-optica-core') . '</p></div>';
    return;
}

// Get tenant's current plan
$plan = null;
if ($tenant->plan_id) {
    $plan = WPT_Plan_Manager::get_plan($tenant->plan_id);
}

// Get active add-ons for this tenant
$active_addons = $wpdb->get_results($wpdb->prepare(
    "SELECT ta.*, ap.addon_key, ap.addon_name, ap.price, ap.unit
    FROM {$wpdb->prefix}wpt_tenant_addons ta
    JOIN {$wpdb->prefix}wpt_addon_prices ap ON ta.addon_key = ap.addon_key
    WHERE ta.tenant_id = %d AND ta.status = 'active'
    ORDER BY ap.addon_name ASC",
    $tenant_id
));

// Get all available add-ons
$all_addons = WPT_Addon_Manager::get_addons();

// Calculate total monthly cost
$monthly_cost = 0;
if ($plan) {
    $monthly_cost += floatval($plan->price);
}
foreach ($active_addons as $addon) {
    $monthly_cost += floatval($addon->price) * intval($addon->quantity);
}

// Get quota features for usage tracking
$quota_features = WPT_Feature_Mapping_Manager::get_quota_features();
?>

<div class="wpt-tenant-plan-addons">

    <!-- Current Plan Section -->
    <div class="wpt-section">
        <h3>📋 Plan Curent</h3>

        <?php if ($plan): ?>
            <div class="wpt-plan-card">
                <div class="wpt-plan-header">
                    <div class="wpt-plan-name">
                        <strong><?php echo esc_html($plan->plan_name); ?></strong>
                        <span class="wpt-plan-tier wpt-tier-<?php echo esc_attr(strtolower($plan->tier)); ?>">
                            <?php echo esc_html($plan->tier); ?>
                        </span>
                    </div>
                    <div class="wpt-plan-price">
                        <strong><?php echo number_format($plan->price, 2); ?> RON</strong>/lună
                    </div>
                </div>

                <?php if ($plan->description): ?>
                    <div class="wpt-plan-description">
                        <?php echo esc_html($plan->description); ?>
                    </div>
                <?php endif; ?>

                <!-- Plan Features -->
                <div class="wpt-plan-features">
                    <h4>✨ Features Incluse</h4>
                    <?php
                    $features = json_decode($plan->features, true);
                    if ($features && is_array($features)):
                        ?>
                        <ul class="wpt-feature-list">
                            <?php foreach ($features as $feature_key => $value): ?>
                                <?php
                                $mapping = WPT_Feature_Mapping_Manager::get_mapping($feature_key);
                                if (!$mapping) continue;

                                $formatted_value = WPT_Feature_Mapping_Manager::format_feature_value($feature_key, $value);
                                $icon = WPT_Feature_Mapping_Manager::get_feature_icon($mapping->feature_type);
                                ?>
                                <li>
                                    <span class="dashicons <?php echo esc_attr($icon); ?>"></span>
                                    <strong><?php echo esc_html($mapping->feature_name); ?>:</strong>
                                    <span class="wpt-feature-value"><?php echo esc_html($formatted_value); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p style="color: #999;">Niciun feature definit pentru acest plan.</p>
                    <?php endif; ?>
                </div>

                <!-- Quota Usage Tracking -->
                <?php if ($quota_features): ?>
                    <div class="wpt-quota-usage">
                        <h4>📊 Utilizare Quota</h4>
                        <?php foreach ($quota_features as $quota_feature): ?>
                            <?php
                            $feature_value = isset($features[$quota_feature->feature_key]) ? intval($features[$quota_feature->feature_key]) : 0;

                            // Get current usage (placeholder - will be implemented with actual tracking)
                            $current_usage = 0; // TODO: Implement actual quota tracking

                            // Skip unlimited quotas
                            if ($feature_value >= 999) {
                                continue;
                            }

                            $percentage = $feature_value > 0 ? min(100, ($current_usage / $feature_value) * 100) : 0;
                            $status_class = $percentage >= 90 ? 'danger' : ($percentage >= 70 ? 'warning' : 'success');
                            ?>
                            <div class="wpt-quota-item">
                                <div class="wpt-quota-header">
                                    <span><?php echo esc_html($quota_feature->feature_name); ?></span>
                                    <span class="wpt-quota-stats"><?php echo intval($current_usage); ?> / <?php echo intval($feature_value); ?></span>
                                </div>
                                <div class="wpt-quota-bar">
                                    <div class="wpt-quota-progress wpt-quota-<?php echo esc_attr($status_class); ?>"
                                         style="width: <?php echo floatval($percentage); ?>%;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            </div>
        <?php else: ?>
            <div class="notice notice-warning">
                <p>⚠️ Tenantul nu are un plan alocat. Selectează un plan din tab-ul "Detalii Generale".</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Active Add-ons Section -->
    <div class="wpt-section">
        <h3>🧩 Add-ons Active</h3>

        <?php if (!empty($active_addons)): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 30%;">Add-on</th>
                        <th style="width: 15%;">Cantitate</th>
                        <th style="width: 15%;">Preț Unitar</th>
                        <th style="width: 15%;">Total</th>
                        <th style="width: 15%;">Status</th>
                        <th style="width: 10%;">Acțiuni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($active_addons as $addon): ?>
                        <?php $total = floatval($addon->price) * intval($addon->quantity); ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($addon->addon_name); ?></strong>
                            </td>
                            <td>
                                <input type="number"
                                       class="wpt-addon-quantity"
                                       data-addon-id="<?php echo intval($addon->id); ?>"
                                       value="<?php echo intval($addon->quantity); ?>"
                                       min="1"
                                       style="width: 80px;">
                            </td>
                            <td>
                                <?php echo number_format($addon->price, 2); ?> RON/<?php echo esc_html($addon->unit); ?>
                            </td>
                            <td>
                                <strong><?php echo number_format($total, 2); ?> RON</strong>/lună
                            </td>
                            <td>
                                <span class="wpt-status-badge wpt-status-<?php echo esc_attr($addon->status); ?>">
                                    <?php echo esc_html(ucfirst($addon->status)); ?>
                                </span>
                            </td>
                            <td>
                                <button type="button"
                                        class="button button-small wpt-deactivate-addon"
                                        data-addon-id="<?php echo intval($addon->id); ?>"
                                        onclick="return confirm('Sigur vrei să dezactivezi acest add-on?');">
                                    🗑️ Dezactivează
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="notice notice-info">
                <p>ℹ️ Niciun add-on activ pentru acest tenant.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Available Add-ons Section -->
    <div class="wpt-section">
        <h3>➕ Adaugă Add-on</h3>

        <form id="wpt-add-addon-form" class="wpt-add-addon-form">
            <input type="hidden" name="tenant_id" id="tenant_id" value="<?php echo intval($tenant_id); ?>">

            <table class="form-table">
                <tr>
                    <th><label for="addon_key">Selectează Add-on</label></th>
                    <td>
                        <select id="addon_key" name="addon_key" class="regular-text" required>
                            <option value="">— Selectează —</option>
                            <?php
                            // Get already active addon keys
                            $active_keys = array_column($active_addons, 'addon_key');

                            foreach ($all_addons as $addon):
                                // Skip if already active
                                if (in_array($addon->addon_key, $active_keys)) continue;
                            ?>
                                <option value="<?php echo esc_attr($addon->addon_key); ?>"
                                        data-price="<?php echo esc_attr($addon->price); ?>"
                                        data-unit="<?php echo esc_attr($addon->unit); ?>">
                                    <?php echo esc_html($addon->addon_name); ?>
                                    (<?php echo number_format($addon->price, 2); ?> RON/<?php echo esc_html($addon->unit); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="quantity">Cantitate</label></th>
                    <td>
                        <input type="number"
                               id="quantity"
                               name="quantity"
                               value="1"
                               min="1"
                               class="regular-text"
                               required>
                        <p class="description">Numărul de unități pentru acest add-on</p>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <button type="submit" id="wpt-activate-addon-btn" class="button button-primary">
                    ✅ Activează Add-on
                </button>
            </p>
        </form>
    </div>

    <!-- Cost Summary -->
    <div class="wpt-section wpt-cost-summary">
        <h3>💰 Sumar Cost Lunar</h3>

        <table class="wpt-cost-table">
            <tr>
                <td><strong>Plan de bază:</strong></td>
                <td class="wpt-cost-value">
                    <?php echo $plan ? number_format($plan->price, 2) : '0.00'; ?> RON
                </td>
            </tr>
            <?php if (!empty($active_addons)): ?>
                <?php foreach ($active_addons as $addon): ?>
                    <tr>
                        <td><?php echo esc_html($addon->addon_name); ?> (<?php echo intval($addon->quantity); ?>x):</td>
                        <td class="wpt-cost-value">
                            <?php echo number_format(floatval($addon->price) * intval($addon->quantity), 2); ?> RON
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            <tr class="wpt-cost-total">
                <td><strong>TOTAL:</strong></td>
                <td class="wpt-cost-value">
                    <strong><?php echo number_format($monthly_cost, 2); ?> RON</strong>/lună
                </td>
            </tr>
        </table>
    </div>

</div>

<style>
.wpt-tenant-plan-addons {
    padding: 20px 0;
}

.wpt-section {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 20px;
}

.wpt-section h3 {
    margin-top: 0;
    margin-bottom: 15px;
    font-size: 16px;
}

.wpt-section h4 {
    margin-top: 15px;
    margin-bottom: 10px;
    font-size: 14px;
    color: #666;
}

/* Plan Card */
.wpt-plan-card {
    border: 2px solid #0073aa;
    border-radius: 6px;
    padding: 20px;
    background: #f9f9f9;
}

.wpt-plan-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid #ddd;
}

.wpt-plan-name {
    display: flex;
    align-items: center;
    gap: 10px;
}

.wpt-plan-tier {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.wpt-tier-free {
    background: #e8f5e9;
    color: #388e3c;
}

.wpt-tier-premium {
    background: #e3f2fd;
    color: #1976d2;
}

.wpt-tier-business {
    background: #fff3e0;
    color: #f57c00;
}

.wpt-plan-price {
    font-size: 18px;
    color: #0073aa;
}

.wpt-plan-description {
    margin-bottom: 15px;
    color: #666;
    font-style: italic;
}

/* Feature List */
.wpt-feature-list {
    list-style: none;
    margin: 0;
    padding: 0;
}

.wpt-feature-list li {
    display: flex;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #eee;
}

.wpt-feature-list li:last-child {
    border-bottom: none;
}

.wpt-feature-list .dashicons {
    margin-right: 10px;
    color: #0073aa;
}

.wpt-feature-value {
    margin-left: auto;
    color: #666;
    font-weight: 600;
}

/* Quota Usage */
.wpt-quota-item {
    margin-bottom: 15px;
}

.wpt-quota-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 5px;
    font-size: 13px;
}

.wpt-quota-stats {
    font-weight: 600;
}

.wpt-quota-bar {
    height: 20px;
    background: #f0f0f0;
    border-radius: 10px;
    overflow: hidden;
}

.wpt-quota-progress {
    height: 100%;
    transition: width 0.3s ease;
}

.wpt-quota-success {
    background: linear-gradient(to right, #46b450, #5bc362);
}

.wpt-quota-warning {
    background: linear-gradient(to right, #ffb900, #ffc926);
}

.wpt-quota-danger {
    background: linear-gradient(to right, #dc3232, #e74c3c);
}

/* Status Badges */
.wpt-status-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.wpt-status-active {
    background: #e8f5e9;
    color: #388e3c;
}

.wpt-status-inactive {
    background: #f5f5f5;
    color: #999;
}

/* Cost Summary */
.wpt-cost-summary {
    background: #f0f8ff !important;
    border-color: #0073aa !important;
}

.wpt-cost-table {
    width: 100%;
    border-collapse: collapse;
}

.wpt-cost-table tr {
    border-bottom: 1px solid #ddd;
}

.wpt-cost-table tr:last-child {
    border-bottom: none;
}

.wpt-cost-table td {
    padding: 10px 0;
}

.wpt-cost-value {
    text-align: right;
    font-weight: 600;
    color: #0073aa;
}

.wpt-cost-total {
    border-top: 2px solid #0073aa;
    font-size: 16px;
}

.wpt-cost-total td {
    padding-top: 15px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Handle add addon form submission
    $('#wpt-add-addon-form').on('submit', function(e) {
        e.preventDefault();

        var $form = $(this);
        var $button = $('#wpt-activate-addon-btn');
        var addonKey = $('#addon_key').val();
        var quantity = $('#quantity').val();
        var tenantId = $('#tenant_id').val();

        if (!addonKey) {
            alert('Te rog selectează un add-on.');
            return;
        }

        if (quantity < 1) {
            alert('Cantitatea trebuie să fie cel puțin 1.');
            return;
        }

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wpt_add_tenant_addon',
                nonce: '<?php echo wp_create_nonce("wpt_manage_tenant_addons"); ?>',
                tenant_id: tenantId,
                addon_key: addonKey,
                quantity: quantity
            },
            beforeSend: function() {
                $button.prop('disabled', true).text('⏳ Activare...');
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert('Eroare: ' + response.data.message);
                }
            },
            error: function() {
                alert('Eroare la comunicarea cu serverul.');
            },
            complete: function() {
                $button.prop('disabled', false).text('✅ Activează Add-on');
            }
        });
    });

    // Handle addon quantity change
    $('.wpt-addon-quantity').on('change', function() {
        var $input = $(this);
        var addonId = $input.data('addon-id');
        var quantity = $input.val();

        if (quantity < 1) {
            alert('Cantitatea trebuie să fie cel puțin 1.');
            $input.val(1);
            return;
        }

        if (confirm('Sigur vrei să modifici cantitatea pentru acest add-on?')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'wpt_update_addon_quantity',
                    nonce: '<?php echo wp_create_nonce("wpt_manage_tenant_addons"); ?>',
                    addon_id: addonId,
                    quantity: quantity
                },
                beforeSend: function() {
                    $input.prop('disabled', true);
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert('Eroare: ' + response.data.message);
                    }
                },
                error: function() {
                    alert('Eroare la comunicarea cu serverul.');
                },
                complete: function() {
                    $input.prop('disabled', false);
                }
            });
        } else {
            // Reset value
            location.reload();
        }
    });

    // Handle addon deactivation
    $('.wpt-deactivate-addon').on('click', function(e) {
        e.preventDefault();

        var $button = $(this);
        var addonId = $button.data('addon-id');

        if (!confirm('Sigur vrei să dezactivezi acest add-on?')) {
            return;
        }

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wpt_deactivate_tenant_addon',
                nonce: '<?php echo wp_create_nonce("wpt_manage_tenant_addons"); ?>',
                addon_id: addonId
            },
            beforeSend: function() {
                $button.prop('disabled', true).text('⏳ Dezactivare...');
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert('Eroare: ' + response.data.message);
                }
            },
            error: function() {
                alert('Eroare la comunicarea cu serverul.');
            },
            complete: function() {
                $button.prop('disabled', false).text('🗑️ Dezactivează');
            }
        });
    });
});
</script>
