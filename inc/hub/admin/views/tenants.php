<?php
/**
 * Tenants Management View
 *
 * @package WPT_Optica_Core
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
$tenant_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['wpt_tenant_nonce']) && wp_verify_nonce($_POST['wpt_tenant_nonce'], 'wpt_save_tenant')) {
    $tenant_data = array(
        'hub_user_id' => intval($_POST['hub_user_id']),
        'brand_id' => intval($_POST['brand_id']),
        'site_url' => esc_url_raw($_POST['site_url']),
        'plan_id' => !empty($_POST['plan_id']) ? intval($_POST['plan_id']) : null,
        'status' => sanitize_text_field($_POST['status']),
    );

    if ($tenant_id > 0) {
        // Update existing tenant
        $wpdb->update(
            $wpdb->prefix . 'wpt_tenants',
            $tenant_data,
            array('id' => $tenant_id),
            array('%d', '%d', '%s', '%d', '%s'),
            array('%d')
        );
        $message = __('Tenant updated successfully', 'wpt-optica-core');
    } else {
        // Create new tenant - generate tenant_key and api_key
        $generated_tenant_key = 'wpt_' . wp_generate_password(32, false);
        $generated_api_key = wp_generate_password(64, false);

        // Rebuild array in correct column order for INSERT
        $insert_data = array(
            'hub_user_id' => $tenant_data['hub_user_id'],
            'brand_id' => $tenant_data['brand_id'],
            'site_url' => $tenant_data['site_url'],
            'tenant_key' => $generated_tenant_key,
            'api_key' => $generated_api_key,
            'plan_id' => $tenant_data['plan_id'],
            'status' => $tenant_data['status'],
            'created_at' => current_time('mysql'),
        );

        $wpdb->insert(
            $wpdb->prefix . 'wpt_tenants',
            $insert_data,
            array('%d', '%d', '%s', '%s', '%s', '%d', '%s', '%s')
        );
        $tenant_id = $wpdb->insert_id;

        $message = sprintf(
            __('Tenant created successfully! Tenant Key: %s | API Key: %s', 'wpt-optica-core'),
            '<code>' . $generated_tenant_key . '</code>',
            '<code>' . $generated_api_key . '</code>'
        );
    }

    echo '<div class="notice notice-success"><p>' . $message . '</p></div>';
}

// Handle delete action
if ($action === 'delete' && $tenant_id > 0) {
    check_admin_referer('wpt_delete_tenant_' . $tenant_id);

    $tenant = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}wpt_tenants WHERE id = %d",
        $tenant_id
    ));

    if ($tenant) {
        $wpdb->delete(
            $wpdb->prefix . 'wpt_tenants',
            array('id' => $tenant_id),
            array('%d')
        );
        echo '<div class="notice notice-success"><p>' . __('Tenant deleted successfully', 'wpt-optica-core') . '</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>' . __('Tenant not found', 'wpt-optica-core') . '</p></div>';
    }

    $action = 'list';
}

// Get all plans for dropdown
$plans = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wpt_plans ORDER BY name ASC");

// Get all users for dropdown
$users = get_users(array('orderby' => 'display_name', 'order' => 'ASC'));

// For edit mode, load brands for the selected user
$brands = array();
$selected_user_id = 0;

if ($action === 'edit' && $tenant_id > 0) {
    $tenant = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}wpt_tenants WHERE id = %d",
        $tenant_id
    ));

    if (!$tenant) {
        echo '<div class="notice notice-error"><p>' . __('Tenant not found', 'wpt-optica-core') . '</p></div>';
        $action = 'list';
    } else {
        $selected_user_id = $tenant->hub_user_id;
        // Load brands for this user
        $brands = get_posts(array(
            'post_type' => 'brand',
            'posts_per_page' => -1,
            'author' => $selected_user_id,
            'orderby' => 'title',
            'order' => 'ASC',
            'post_status' => 'publish'
        ));
    }
}
?>

<div class="wrap wpt-tenants">
    <h1 class="wp-heading-inline"><?php _e('Tenants Management', 'wpt-optica-core'); ?></h1>

    <?php if ($action === 'list'): ?>
        <a href="<?php echo admin_url('admin.php?page=wpt-tenants&action=new'); ?>" class="page-title-action">
            <?php _e('Add New', 'wpt-optica-core'); ?>
        </a>
        <hr class="wp-header-end">

        <?php
        // Get all tenants with pagination
        $per_page = 20;
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($current_page - 1) * $per_page;

        $total_tenants = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wpt_tenants");
        $tenants = $wpdb->get_results($wpdb->prepare(
            "SELECT t.*,
                    p.name as plan_name,
                    u.display_name as user_name,
                    u.user_email as user_email,
                    b.post_title as brand_name
            FROM {$wpdb->prefix}wpt_tenants t
            LEFT JOIN {$wpdb->prefix}wpt_plans p ON t.plan_id = p.id
            LEFT JOIN {$wpdb->prefix}users u ON t.hub_user_id = u.ID
            LEFT JOIN {$wpdb->prefix}posts b ON t.brand_id = b.ID
            ORDER BY t.created_at DESC
            LIMIT %d OFFSET %d",
            $per_page,
            $offset
        ));
        ?>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 50px;"><?php _e('ID', 'wpt-optica-core'); ?></th>
                    <th><?php _e('Brand', 'wpt-optica-core'); ?></th>
                    <th><?php _e('User', 'wpt-optica-core'); ?></th>
                    <th><?php _e('Site URL', 'wpt-optica-core'); ?></th>
                    <th><?php _e('Plan', 'wpt-optica-core'); ?></th>
                    <th><?php _e('Status', 'wpt-optica-core'); ?></th>
                    <th><?php _e('Created', 'wpt-optica-core'); ?></th>
                    <th><?php _e('Actions', 'wpt-optica-core'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tenants)): ?>
                    <tr>
                        <td colspan="8"><?php _e('No tenants found', 'wpt-optica-core'); ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($tenants as $tenant): ?>
                        <tr>
                            <td><?php echo esc_html($tenant->id); ?></td>
                            <td>
                                <strong>
                                    <a href="<?php echo admin_url('admin.php?page=wpt-tenants&action=edit&id=' . $tenant->id); ?>">
                                        <?php echo esc_html($tenant->brand_name ?: __('N/A', 'wpt-optica-core')); ?>
                                    </a>
                                </strong>
                            </td>
                            <td>
                                <?php echo esc_html($tenant->user_name ?: __('N/A', 'wpt-optica-core')); ?><br>
                                <small><?php echo esc_html($tenant->user_email ?: ''); ?></small>
                            </td>
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
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=wpt-tenants&action=edit&id=' . $tenant->id); ?>" class="button button-small">
                                    <?php _e('Edit', 'wpt-optica-core'); ?>
                                </a>
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=wpt-tenants&action=delete&id=' . $tenant->id), 'wpt_delete_tenant_' . $tenant->id); ?>"
                                   class="button button-small button-link-delete"
                                   onclick="return confirm('<?php echo esc_js(__('Are you sure you want to delete this tenant? This action cannot be undone.', 'wpt-optica-core')); ?>');">
                                    <?php _e('Delete', 'wpt-optica-core'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if ($total_tenants > $per_page): ?>
            <div class="tablenav">
                <div class="tablenav-pages">
                    <?php
                    $total_pages = ceil($total_tenants / $per_page);
                    echo paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => __('&laquo;'),
                        'next_text' => __('&raquo;'),
                        'total' => $total_pages,
                        'current' => $current_page,
                    ));
                    ?>
                </div>
            </div>
        <?php endif; ?>

    <?php elseif ($action === 'new' || $action === 'edit'): ?>
        <hr class="wp-header-end">

        <?php if ($action === 'edit'): ?>
            <!-- Tabs Navigation -->
            <h2 class="nav-tab-wrapper wpt-tenant-tabs">
                <a href="#tab-general" class="nav-tab nav-tab-active" data-tab="general">
                    <?php _e('General', 'wpt-optica-core'); ?>
                </a>
                <a href="#tab-credentials" class="nav-tab" data-tab="credentials">
                    <?php _e('Credentials', 'wpt-optica-core'); ?>
                </a>
                <a href="#tab-sync" class="nav-tab" data-tab="sync">
                    <?php _e('Sync Configuration', 'wpt-optica-core'); ?>
                </a>
                <a href="#tab-modules" class="nav-tab" data-tab="modules">
                    <?php _e('Modules', 'wpt-optica-core'); ?>
                </a>
            </h2>

            <!-- Tab Content -->
            <div class="wpt-tenant-tab-content">
                <!-- General Tab -->
                <div id="tab-general" class="wpt-tenant-tab-panel active">
                    <form method="post" action="<?php echo admin_url('admin.php?page=wpt-tenants&action=edit&id=' . $tenant_id); ?>">
                        <?php wp_nonce_field('wpt_save_tenant', 'wpt_tenant_nonce'); ?>

                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="hub_user_id"><?php _e('HUB User', 'wpt-optica-core'); ?> <span class="required">*</span></label>
                                </th>
                                <td>
                                    <select id="hub_user_id" name="hub_user_id" class="regular-text" required>
                                        <option value=""><?php _e('Select a user', 'wpt-optica-core'); ?></option>
                                        <?php foreach ($users as $user): ?>
                                            <option value="<?php echo esc_attr($user->ID); ?>"
                                                <?php echo (isset($tenant) && $tenant->hub_user_id == $user->ID) ? 'selected' : ''; ?>>
                                                <?php echo esc_html($user->display_name); ?> (<?php echo esc_html($user->user_email); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="description"><?php _e('WordPress user who owns this tenant', 'wpt-optica-core'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="brand_id"><?php _e('Brand', 'wpt-optica-core'); ?> <span class="required">*</span></label>
                                </th>
                                <td>
                                    <select id="brand_id" name="brand_id" class="regular-text" required>
                                        <option value=""><?php _e('Select a brand', 'wpt-optica-core'); ?></option>
                                        <?php foreach ($brands as $brand): ?>
                                            <option value="<?php echo esc_attr($brand->ID); ?>"
                                                <?php echo (isset($tenant) && $tenant->brand_id == $brand->ID) ? 'selected' : ''; ?>>
                                                <?php echo esc_html($brand->post_title); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="description"><?php _e('Brand post associated with this tenant', 'wpt-optica-core'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="site_url"><?php _e('Site URL', 'wpt-optica-core'); ?></label>
                                </th>
                                <td>
                                    <input type="url" id="site_url" name="site_url" class="regular-text"
                                           value="<?php echo isset($tenant) ? esc_url($tenant->site_url) : ''; ?>"
                                           placeholder="https://example.com">
                                    <p class="description"><?php _e('Full URL including https:// (can be set later)', 'wpt-optica-core'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="plan_id"><?php _e('Plan', 'wpt-optica-core'); ?></label>
                                </th>
                                <td>
                                    <select id="plan_id" name="plan_id">
                                        <option value=""><?php _e('No plan (optional)', 'wpt-optica-core'); ?></option>
                                        <?php foreach ($plans as $plan): ?>
                                            <option value="<?php echo esc_attr($plan->id); ?>"
                                                <?php echo (isset($tenant) && $tenant->plan_id == $plan->id) ? 'selected' : ''; ?>>
                                                <?php echo esc_html($plan->name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="status"><?php _e('Status', 'wpt-optica-core'); ?></label>
                                </th>
                                <td>
                                    <select id="status" name="status">
                                        <option value="pending_site" <?php echo (isset($tenant) && $tenant->status === 'pending_site') ? 'selected' : ''; ?>>
                                            <?php _e('Pending Site', 'wpt-optica-core'); ?>
                                        </option>
                                        <option value="active" <?php echo (isset($tenant) && $tenant->status === 'active') ? 'selected' : ''; ?>>
                                            <?php _e('Active', 'wpt-optica-core'); ?>
                                        </option>
                                        <option value="suspended" <?php echo (isset($tenant) && $tenant->status === 'suspended') ? 'selected' : ''; ?>>
                                            <?php _e('Suspended', 'wpt-optica-core'); ?>
                                        </option>
                                    </select>
                                </td>
                            </tr>
                        </table>

                        <p class="submit">
                            <input type="submit" name="submit" class="button button-primary"
                                   value="<?php _e('Update Tenant', 'wpt-optica-core'); ?>">
                            <a href="<?php echo admin_url('admin.php?page=wpt-tenants'); ?>" class="button button-secondary">
                                <?php _e('Cancel', 'wpt-optica-core'); ?>
                            </a>
                        </p>
                    </form>
                </div>

                <!-- Credentials Tab -->
                <div id="tab-credentials" class="wpt-tenant-tab-panel">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Tenant Key', 'wpt-optica-core'); ?></th>
                            <td>
                                <code><?php echo esc_html($tenant->tenant_key); ?></code>
                                <p class="description"><?php _e('Used to identify this tenant in API requests', 'wpt-optica-core'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('API Key', 'wpt-optica-core'); ?></th>
                            <td>
                                <code><?php echo esc_html($tenant->api_key); ?></code>
                                <p class="description"><?php _e('Used to authenticate API requests', 'wpt-optica-core'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Sync Configuration Tab -->
                <div id="tab-sync" class="wpt-tenant-tab-panel">
                    <?php
                    // Include Tenant Sync Configuration Section
                    include WPT_HUB_DIR . 'inc/hub/admin/views/tenant-sync-config-section.php';
                    ?>
                </div>

                <!-- Modules Tab -->
                <div id="tab-modules" class="wpt-tenant-tab-panel">
                    <?php
                    // Include Tenant Modules Section
                    include WPT_HUB_DIR . 'inc/hub/admin/views/tenant-modules-section.php';
                    ?>
                </div>
            </div>

        <?php else: ?>
            <!-- New Tenant Form (no tabs) -->
            <form method="post" action="<?php echo admin_url('admin.php?page=wpt-tenants&action=new'); ?>">
                <?php wp_nonce_field('wpt_save_tenant', 'wpt_tenant_nonce'); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="hub_user_id"><?php _e('HUB User', 'wpt-optica-core'); ?> <span class="required">*</span></label>
                        </th>
                        <td>
                            <select id="hub_user_id" name="hub_user_id" class="regular-text" required>
                                <option value=""><?php _e('Select a user', 'wpt-optica-core'); ?></option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo esc_attr($user->ID); ?>">
                                        <?php echo esc_html($user->display_name); ?> (<?php echo esc_html($user->user_email); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e('WordPress user who owns this tenant', 'wpt-optica-core'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="brand_id"><?php _e('Brand', 'wpt-optica-core'); ?> <span class="required">*</span></label>
                        </th>
                        <td>
                            <select id="brand_id" name="brand_id" class="regular-text" required>
                                <option value=""><?php _e('Select a brand', 'wpt-optica-core'); ?></option>
                            </select>
                            <p class="description"><?php _e('Brand post associated with this tenant', 'wpt-optica-core'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="site_url"><?php _e('Site URL', 'wpt-optica-core'); ?></label>
                        </th>
                        <td>
                            <input type="url" id="site_url" name="site_url" class="regular-text"
                                   placeholder="https://example.com">
                            <p class="description"><?php _e('Full URL including https:// (can be set later)', 'wpt-optica-core'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="plan_id"><?php _e('Plan', 'wpt-optica-core'); ?></label>
                        </th>
                        <td>
                            <select id="plan_id" name="plan_id">
                                <option value=""><?php _e('No plan (optional)', 'wpt-optica-core'); ?></option>
                                <?php foreach ($plans as $plan): ?>
                                    <option value="<?php echo esc_attr($plan->id); ?>">
                                        <?php echo esc_html($plan->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="status"><?php _e('Status', 'wpt-optica-core'); ?></label>
                        </th>
                        <td>
                            <select id="status" name="status">
                                <option value="pending_site"><?php _e('Pending Site', 'wpt-optica-core'); ?></option>
                                <option value="active" selected><?php _e('Active', 'wpt-optica-core'); ?></option>
                                <option value="suspended"><?php _e('Suspended', 'wpt-optica-core'); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <input type="submit" name="submit" class="button button-primary"
                           value="<?php _e('Create Tenant', 'wpt-optica-core'); ?>">
                    <a href="<?php echo admin_url('admin.php?page=wpt-tenants'); ?>" class="button button-secondary">
                        <?php _e('Cancel', 'wpt-optica-core'); ?>
                    </a>
                </p>
            </form>
        <?php endif; ?>

        <style>
        /* Tenant Management Tabs */
        .wpt-tenant-tabs {
            margin: 20px 0;
            border-bottom: 1px solid #ccd0d4;
        }

        .wpt-tenant-tab-content {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-top: none;
            padding: 20px;
            min-height: 400px;
        }

        .wpt-tenant-tab-panel {
            display: none;
        }

        .wpt-tenant-tab-panel.active {
            display: block;
        }
        </style>

        <script>
        jQuery(document).ready(function($) {
            // Tab switching for tenant management
            $('.wpt-tenant-tabs .nav-tab').on('click', function(e) {
                e.preventDefault();

                const $tab = $(this);
                const tabId = $tab.data('tab');

                // Update tabs
                $('.wpt-tenant-tabs .nav-tab').removeClass('nav-tab-active');
                $tab.addClass('nav-tab-active');

                // Update panels
                $('.wpt-tenant-tab-panel').removeClass('active');
                $('#tab-' + tabId).addClass('active');
            });

            var selectedUserId = <?php echo $selected_user_id; ?>;
            var selectedBrandId = <?php echo isset($tenant) ? $tenant->brand_id : 0; ?>;

            // Update brands when user changes
            $('#hub_user_id').on('change', function() {
                var userId = $(this).val();
                var $brandSelect = $('#brand_id');

                if (!userId) {
                    $brandSelect.html('<option value=""><?php _e("Select a user first", "wpt-optica-core"); ?></option>');
                    return;
                }

                // Show loading
                $brandSelect.prop('disabled', true);
                $brandSelect.html('<option value=""><?php _e("Loading...", "wpt-optica-core"); ?></option>');

                // Fetch brands for this user
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'wpt_get_user_brands',
                        user_id: userId,
                        nonce: '<?php echo wp_create_nonce("wpt_get_user_brands"); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            var options = '<option value=""><?php _e("Select a brand", "wpt-optica-core"); ?></option>';

                            if (response.data.brands.length === 0) {
                                options = '<option value=""><?php _e("No brands found for this user", "wpt-optica-core"); ?></option>';
                            } else {
                                response.data.brands.forEach(function(brand) {
                                    var selected = (selectedBrandId && brand.id == selectedBrandId) ? ' selected' : '';
                                    options += '<option value="' + brand.id + '"' + selected + '>' + brand.title + '</option>';
                                });
                            }

                            $brandSelect.html(options);
                        } else {
                            $brandSelect.html('<option value=""><?php _e("Error loading brands", "wpt-optica-core"); ?></option>');
                        }
                        $brandSelect.prop('disabled', false);
                    },
                    error: function() {
                        $brandSelect.html('<option value=""><?php _e("Error loading brands", "wpt-optica-core"); ?></option>');
                        $brandSelect.prop('disabled', false);
                    }
                });
            });

            // Trigger change on page load if user is selected
            if (selectedUserId > 0) {
                $('#hub_user_id').trigger('change');
            }
        });
        </script>

    <?php endif; ?>
</div>
