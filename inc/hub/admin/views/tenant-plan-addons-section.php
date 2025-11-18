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

// Handle plan change submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_plan'])) {
    check_admin_referer('wpt_change_tenant_plan', 'wpt_plan_nonce');

    $new_plan_id = isset($_POST['plan_id']) ? intval($_POST['plan_id']) : 0;
    $submit_tenant_id = isset($_POST['tenant_id']) ? intval($_POST['tenant_id']) : 0;

    // Validate
    if ($submit_tenant_id !== $tenant_id) {
        echo '<div class="notice notice-error is-dismissible"><p>' . __('Eroare: Tenant ID invalid.', 'wpt-optica-core') . '</p></div>';
    } elseif (!$new_plan_id) {
        echo '<div class="notice notice-error is-dismissible"><p>' . __('Eroare: Selectează un plan.', 'wpt-optica-core') . '</p></div>';
    } else {
        // Verify plan exists
        $new_plan = WPT_Plan_Manager::get_plan($new_plan_id);

        if (!$new_plan) {
            echo '<div class="notice notice-error is-dismissible"><p>' . __('Eroare: Planul selectat nu există.', 'wpt-optica-core') . '</p></div>';
        } else {
            // Update tenant plan
            $result = $wpdb->update(
                $wpdb->prefix . 'wpt_tenants',
                array('plan_id' => $new_plan_id),
                array('id' => $tenant_id),
                array('%d'),
                array('%d')
            );

            if ($result !== false) {
                echo '<div class="notice notice-success is-dismissible"><p>' . sprintf(__('Plan schimbat cu succes la %s!', 'wpt-optica-core'), '<strong>' . esc_html($new_plan->name) . '</strong>') . '</p></div>';

                // Reload tenant data to show updated plan
                $tenant = WPT_Tenant_Manager::get_tenant($tenant_id);
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>' . __('Eroare la actualizarea planului.', 'wpt-optica-core') . '</p></div>';
            }
        }
    }
}

// Get tenant's current plan
$plan = null;
if ($tenant->plan_id) {
    $plan = WPT_Plan_Manager::get_plan($tenant->plan_id);
}

// Get active add-ons for this tenant
$active_addons = $wpdb->get_results($wpdb->prepare(
    "SELECT ta.*, ap.addon_slug, ap.addon_name, ap.monthly_price as price
    FROM {$wpdb->prefix}wpt_tenant_addons ta
    JOIN {$wpdb->prefix}wpt_addon_prices ap ON ta.addon_slug = ap.addon_slug
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

    <!-- Plan Selection Section -->
    <div class="wpt-section">
        <h3>📋 Selectează Plan</h3>
        <p style="color: #666; margin-bottom: 20px;">Alege planul pentru acest tenant. Planul curent este evidențiat.</p>

        <form method="post" id="wpt-change-plan-form">
            <?php wp_nonce_field('wpt_change_tenant_plan', 'wpt_plan_nonce'); ?>
            <input type="hidden" name="tenant_id" value="<?php echo intval($tenant_id); ?>">

            <div class="wpt-plans-grid">
                <?php
                // Get all available plans
                $all_plans = WPT_Plan_Manager::get_plans();

                foreach ($all_plans as $available_plan):
                    $is_current = $plan && $plan->id === $available_plan->id;
                    $features = json_decode($available_plan->features, true);
                ?>
                    <div class="wpt-plan-option <?php echo $is_current ? 'wpt-plan-current' : ''; ?>"
                         data-plan-id="<?php echo intval($available_plan->id); ?>">

                        <input type="radio"
                               name="plan_id"
                               id="plan_<?php echo intval($available_plan->id); ?>"
                               value="<?php echo intval($available_plan->id); ?>"
                               <?php checked($is_current); ?>
                               class="wpt-plan-radio">

                        <label for="plan_<?php echo intval($available_plan->id); ?>" class="wpt-plan-label">

                            <?php if ($is_current): ?>
                                <div class="wpt-current-badge">✓ Plan Curent</div>
                            <?php endif; ?>

                            <div class="wpt-plan-header-mini">
                                <h4><?php echo esc_html($available_plan->name); ?></h4>
                                <div class="wpt-plan-price-large">
                                    <span class="wpt-price-amount"><?php echo number_format($available_plan->price, 0); ?></span>
                                    <span class="wpt-price-currency">RON/lună</span>
                                </div>
                            </div>

                            <ul class="wpt-plan-features-mini">
                                <?php if ($features && is_array($features)): ?>
                                    <?php
                                    $feature_count = 0;
                                    foreach ($features as $feature_key => $value):
                                        if ($feature_count >= 5) break; // Limit la primele 5 features
                                        $mapping = WPT_Feature_Mapping_Manager::get_mapping($feature_key);
                                        if (!$mapping) continue;
                                        $formatted_value = WPT_Feature_Mapping_Manager::format_feature_value($feature_key, $value);
                                        $feature_count++;
                                    ?>
                                        <li>
                                            ✓ <?php echo esc_html($mapping->feature_name); ?>:
                                            <strong><?php echo esc_html($formatted_value); ?></strong>
                                        </li>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </ul>

                        </label>
                    </div>
                <?php endforeach; ?>
            </div>

            <p class="submit" style="margin-top: 20px;">
                <button type="submit" name="change_plan" class="button button-primary button-large">
                    💾 Salvează Planul Selectat
                </button>
            </p>
        </form>
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
                                <?php echo number_format($addon->price, 2); ?> RON/lună
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
                    <th><label for="addon_slug">Selectează Add-on</label></th>
                    <td>
                        <select id="addon_slug" name="addon_slug" class="regular-text" required>
                            <option value="">— Selectează —</option>
                            <?php
                            // Get already active addon slugs
                            $active_slugs = array_column($active_addons, 'addon_slug');

                            foreach ($all_addons as $addon):
                                // Skip if already active
                                if (in_array($addon->addon_slug, $active_slugs)) continue;
                            ?>
                                <option value="<?php echo esc_attr($addon->addon_slug); ?>"
                                        data-price="<?php echo esc_attr($addon->monthly_price); ?>">
                                    <?php echo esc_html($addon->addon_name); ?>
                                    (<?php echo number_format($addon->monthly_price, 2); ?> RON/lună)
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

/* Plan Selection Grid */
.wpt-plans-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.wpt-plan-option {
    position: relative;
    border: 2px solid #ddd;
    border-radius: 8px;
    background: #fff;
    transition: all 0.3s ease;
    cursor: pointer;
}

.wpt-plan-option:hover {
    border-color: #0073aa;
    box-shadow: 0 4px 12px rgba(0, 115, 170, 0.15);
    transform: translateY(-2px);
}

.wpt-plan-option.wpt-plan-current {
    border-color: #46b450;
    border-width: 3px;
    background: #f0f8f0;
}

.wpt-plan-radio {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}

.wpt-plan-label {
    display: block;
    padding: 20px 20px 20px 50px;
    cursor: pointer;
    margin: 0;
}

.wpt-plan-radio:checked + .wpt-plan-label {
    /* Additional styling when radio is checked */
}

.wpt-current-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    background: #46b450;
    color: #fff;
    padding: 5px 12px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    z-index: 10;
}

.wpt-plan-header-mini {
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid #e5e5e5;
}

.wpt-plan-header-mini h4 {
    margin: 0 0 10px 0;
    font-size: 18px;
    color: #333;
}

.wpt-plan-price-large {
    display: flex;
    align-items: baseline;
    gap: 5px;
}

.wpt-price-amount {
    font-size: 32px;
    font-weight: 700;
    color: #0073aa;
    line-height: 1;
}

.wpt-price-currency {
    font-size: 14px;
    color: #666;
    font-weight: 500;
}

.wpt-plan-features-mini {
    list-style: none;
    margin: 0;
    padding: 0;
}

.wpt-plan-features-mini li {
    padding: 6px 0;
    font-size: 13px;
    color: #555;
    line-height: 1.4;
}

.wpt-plan-features-mini li strong {
    color: #0073aa;
}

/* Radio button visual indicator */
.wpt-plan-option::before {
    content: '';
    position: absolute;
    top: 15px;
    left: 15px;
    width: 20px;
    height: 20px;
    border: 2px solid #ddd;
    border-radius: 50%;
    background: #fff;
    transition: all 0.2s ease;
}

.wpt-plan-option.wpt-plan-current::before,
.wpt-plan-radio:checked + .wpt-plan-label::before,
.wpt-plan-option:has(.wpt-plan-radio:checked)::before {
    border-color: #46b450;
    background: #46b450;
    box-shadow: inset 0 0 0 4px #fff;
}

.wpt-plan-option:hover::before {
    border-color: #0073aa;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Handle add addon form submission
    $('#wpt-add-addon-form').on('submit', function(e) {
        e.preventDefault();

        var $form = $(this);
        var $button = $('#wpt-activate-addon-btn');
        var addonSlug = $('#addon_slug').val();
        var quantity = $('#quantity').val();
        var tenantId = $('#tenant_id').val();

        if (!addonSlug) {
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
                addon_slug: addonSlug,
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
