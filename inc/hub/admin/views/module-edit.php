<?php
/**
 * Module Edit/Add Page with Tabs
 * Tabs: General Info, Availability, Stats
 *
 * @package WPT_Optica_Core
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$module_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$is_new = ($module_id === 0);
$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';

// Get module data
$module = null;
if (!$is_new) {
    $module = $wpdb->get_row($wpdb->prepare(
        "SELECT m.*, c.name as category_name
        FROM {$wpdb->prefix}wpt_available_modules m
        LEFT JOIN {$wpdb->prefix}wpt_module_categories c ON m.category_id = c.id
        WHERE m.id = %d",
        $module_id
    ));

    if (!$module) {
        echo '<div class="notice notice-error"><p>' . __('Module not found', 'wpt-optica-core') . '</p></div>';
        return;
    }
}

// Get categories
$categories = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wpt_module_categories ORDER BY name ASC");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['wpt_module_nonce']) && wp_verify_nonce($_POST['wpt_module_nonce'], 'wpt_save_module')) {

    $module_data = array(
        'title' => sanitize_text_field($_POST['title']),
        'slug' => sanitize_title($_POST['slug']),
        'description' => wp_kses_post($_POST['description']),
        'short_description' => sanitize_text_field($_POST['short_description']),
        'long_description' => wp_kses_post($_POST['long_description']),
        'category_id' => intval($_POST['category_id']),
        'logo' => esc_url_raw($_POST['logo']),
        'price' => floatval($_POST['price']),
        'availability_mode' => sanitize_text_field($_POST['availability_mode']),
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
    );

    if ($is_new) {
        $module_data['created_at'] = current_time('mysql');
        $wpdb->insert(
            $wpdb->prefix . 'wpt_available_modules',
            $module_data,
            array('%s', '%s', '%s', '%s', '%s', '%d', '%s', '%f', '%s', '%d', '%s')
        );
        $module_id = $wpdb->insert_id;

        echo '<div class="notice notice-success"><p>' . __('Module created successfully', 'wpt-optica-core') . '</p></div>';

        // Redirect to edit page
        echo '<script>window.location.href = "' . admin_url('admin.php?page=wpt-modules&action=edit&id=' . $module_id) . '";</script>';
    } else {
        $module_data['updated_at'] = current_time('mysql');
        $wpdb->update(
            $wpdb->prefix . 'wpt_available_modules',
            $module_data,
            array('id' => $module_id),
            array('%s', '%s', '%s', '%s', '%s', '%d', '%s', '%f', '%s', '%d', '%s'),
            array('%d')
        );

        echo '<div class="notice notice-success"><p>' . __('Module updated successfully', 'wpt-optica-core') . '</p></div>';

        // Push module info to all tenants who can see it
        $sync_admin = WPT_Sync_Config_Admin::get_instance();
        $push_results = $sync_admin->push_module_to_all_tenants($module_id);

        if (isset($push_results['error'])) {
            echo '<div class="notice notice-error"><p>' . esc_html($push_results['error']) . '</p></div>';
        } else {
            $success_count = count($push_results['success']);
            $failed_count = count($push_results['failed']);
            $total = $push_results['total'];

            if ($total > 0) {
                if ($failed_count === 0) {
                    echo '<div class="notice notice-success"><p>' . sprintf(__('Module info pushed to all %d tenants successfully', 'wpt-optica-core'), $success_count) . '</p></div>';
                } else {
                    echo '<div class="notice notice-warning"><p>' . sprintf(__('Module info pushed to %d/%d tenants. %d failed.', 'wpt-optica-core'), $success_count, $total, $failed_count) . '</p></div>';
                    if (!empty($push_results['failed'])) {
                        echo '<div class="notice notice-error"><p><strong>' . __('Failed tenants:', 'wpt-optica-core') . '</strong><br>';
                        foreach ($push_results['failed'] as $failed) {
                            echo esc_html($failed['site_url']) . ': ' . esc_html($failed['error']) . '<br>';
                        }
                        echo '</p></div>';
                    }
                }
            }
        }

        // Refresh module data
        $module = $wpdb->get_row($wpdb->prepare(
            "SELECT m.*, c.name as category_name
            FROM {$wpdb->prefix}wpt_available_modules m
            LEFT JOIN {$wpdb->prefix}wpt_module_categories c ON m.category_id = c.id
            WHERE m.id = %d",
            $module_id
        ));
    }

    // Handle availability (specific tenants)
    $availability_changed = false;
    if (!$is_new && $module_data['availability_mode'] === 'specific_tenants') {
        // Delete old availability
        $wpdb->delete(
            $wpdb->prefix . 'wpt_module_availability',
            array('module_id' => $module_id),
            array('%d')
        );

        // Insert new availability
        if (isset($_POST['specific_tenants']) && is_array($_POST['specific_tenants'])) {
            foreach ($_POST['specific_tenants'] as $tenant_id) {
                $wpdb->insert(
                    $wpdb->prefix . 'wpt_module_availability',
                    array(
                        'module_id' => $module_id,
                        'tenant_id' => intval($tenant_id),
                        'created_at' => current_time('mysql')
                    ),
                    array('%d', '%d', '%s')
                );
            }
        }
        $availability_changed = true;
    } elseif (!$is_new) {
        // Clear availability if switched to all_tenants
        $old_availability = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}wpt_module_availability WHERE module_id = %d",
            $module_id
        ));

        if ($old_availability > 0) {
            $wpdb->delete(
                $wpdb->prefix . 'wpt_module_availability',
                array('module_id' => $module_id),
                array('%d')
            );
            $availability_changed = true;
        }
    }

    // Push to tenants if availability changed (only for existing modules that were already updated above)
    if (!$is_new && $availability_changed && !isset($push_results)) {
        $sync_admin = WPT_Sync_Config_Admin::get_instance();
        $push_results = $sync_admin->push_module_to_all_tenants($module_id);

        if (isset($push_results['error'])) {
            echo '<div class="notice notice-error"><p>' . esc_html($push_results['error']) . '</p></div>';
        } else {
            $success_count = count($push_results['success']);
            $failed_count = count($push_results['failed']);
            $total = $push_results['total'];

            if ($total > 0) {
                if ($failed_count === 0) {
                    echo '<div class="notice notice-success"><p>' . sprintf(__('Module availability updated and pushed to all %d affected tenants successfully', 'wpt-optica-core'), $success_count) . '</p></div>';
                } else {
                    echo '<div class="notice notice-warning"><p>' . sprintf(__('Module availability updated. Pushed to %d/%d tenants. %d failed.', 'wpt-optica-core'), $success_count, $total, $failed_count) . '</p></div>';
                }
            }
        }
    }
}

// Get all tenants for availability select
$tenants = $wpdb->get_results("SELECT id, site_url FROM {$wpdb->prefix}wpt_tenants ORDER BY site_url ASC");

// Get specific tenants if module exists
$specific_tenant_ids = array();
if (!$is_new) {
    $specific_tenant_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT tenant_id FROM {$wpdb->prefix}wpt_module_availability WHERE module_id = %d",
        $module_id
    ));
}
?>

<div class="wpt-module-edit">
    <h1><?php echo $is_new ? __('Add New Module', 'wpt-optica-core') : __('Edit Module', 'wpt-optica-core'); ?></h1>

    <?php if (!$is_new): ?>
        <h2 class="nav-tab-wrapper">
            <a href="<?php echo admin_url('admin.php?page=wpt-modules&action=edit&id=' . $module_id . '&tab=general'); ?>"
               class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>">
                <?php _e('General Info', 'wpt-optica-core'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=wpt-modules&action=edit&id=' . $module_id . '&tab=availability'); ?>"
               class="nav-tab <?php echo $active_tab === 'availability' ? 'nav-tab-active' : ''; ?>">
                <?php _e('Availability', 'wpt-optica-core'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=wpt-modules&action=edit&id=' . $module_id . '&tab=stats'); ?>"
               class="nav-tab <?php echo $active_tab === 'stats' ? 'nav-tab-active' : ''; ?>">
                <?php _e('Stats & Active Tenants', 'wpt-optica-core'); ?>
            </a>
        </h2>
    <?php endif; ?>

    <?php if ($active_tab === 'general' || $is_new): ?>
        <!-- TAB 1: General Info -->
        <form method="post" action="<?php echo admin_url('admin.php?page=wpt-modules&action=' . ($is_new ? 'new' : 'edit&id=' . $module_id . '&tab=general')); ?>">
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
                        <label for="description"><?php _e('Description (Internal)', 'wpt-optica-core'); ?></label>
                    </th>
                    <td>
                        <textarea id="description" name="description" rows="3" class="large-text"><?php echo isset($module) ? esc_textarea($module->description) : ''; ?></textarea>
                        <p class="description"><?php _e('Descriere internă pentru managementul modulelor', 'wpt-optica-core'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="short_description"><?php _e('Short Description', 'wpt-optica-core'); ?> <span class="required">*</span></label>
                    </th>
                    <td>
                        <input type="text" id="short_description" name="short_description" class="large-text" maxlength="255"
                               value="<?php echo isset($module) ? esc_attr($module->short_description) : ''; ?>" required>
                        <p class="description"><?php _e('Descriere scurtă (max 255 caractere) - afișată pe cardul modulului în site-ul tenant', 'wpt-optica-core'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="long_description"><?php _e('Long Description', 'wpt-optica-core'); ?></label>
                    </th>
                    <td>
                        <?php
                        $long_desc_content = isset($module) ? $module->long_description : '';
                        wp_editor($long_desc_content, 'long_description', array(
                            'textarea_name' => 'long_description',
                            'textarea_rows' => 10,
                            'media_buttons' => true,
                            'teeny' => false,
                            'tinymce' => array(
                                'toolbar1' => 'formatselect,bold,italic,bullist,numlist,link,unlink,blockquote',
                            ),
                        ));
                        ?>
                        <p class="description"><?php _e('Descriere detaliată - afișată în modal când utilizatorul tenant dorește să vadă detalii complete despre modul', 'wpt-optica-core'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="logo"><?php _e('Logo URL', 'wpt-optica-core'); ?></label>
                    </th>
                    <td>
                        <input type="url" id="logo" name="logo" class="regular-text"
                               value="<?php echo isset($module) ? esc_url($module->logo) : ''; ?>">
                        <button type="button" class="button" id="wpt-upload-logo"><?php _e('Upload Logo', 'wpt-optica-core'); ?></button>
                        <p class="description"><?php _e('PNG, SVG sau JPG - URL absolut (ex: https://opticamedicala.ro/wp-content/uploads/module-logo.png)', 'wpt-optica-core'); ?></p>
                        <?php if (isset($module) && !empty($module->logo)): ?>
                            <div style="margin-top: 10px;">
                                <img src="<?php echo esc_url($module->logo); ?>" style="max-width: 100px; max-height: 100px; border: 1px solid #ddd; padding: 5px;">
                            </div>
                        <?php endif; ?>
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
                        <label for="price"><?php _e('Price (RON/month)', 'wpt-optica-core'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="price" name="price" class="small-text" step="0.01" min="0"
                               value="<?php echo isset($module) ? esc_attr($module->price) : '0'; ?>"> RON/lună
                        <p class="description"><?php _e('0 = Free module', 'wpt-optica-core'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="is_active"><?php _e('Status', 'wpt-optica-core'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" id="is_active" name="is_active" value="1"
                                   <?php echo (isset($module) && $module->is_active) ? 'checked' : 'checked'; ?>>
                            <?php _e('Module is active', 'wpt-optica-core'); ?>
                        </label>
                    </td>
                </tr>
            </table>

            <!-- Hidden fields for new module -->
            <?php if ($is_new): ?>
                <input type="hidden" name="availability_mode" value="all_tenants">
            <?php endif; ?>

            <p class="submit">
                <input type="submit" name="submit" class="button button-primary"
                       value="<?php echo $is_new ? __('Create Module', 'wpt-optica-core') : __('Save Changes', 'wpt-optica-core'); ?>">
                <a href="<?php echo admin_url('admin.php?page=wpt-modules'); ?>" class="button button-secondary">
                    <?php _e('Cancel', 'wpt-optica-core'); ?>
                </a>
            </p>
        </form>

    <?php elseif ($active_tab === 'availability' && !$is_new): ?>
        <!-- TAB 2: Availability -->
        <form method="post" action="<?php echo admin_url('admin.php?page=wpt-modules&action=edit&id=' . $module_id . '&tab=availability'); ?>">
            <?php wp_nonce_field('wpt_save_module', 'wpt_module_nonce'); ?>

            <!-- Copy all general info as hidden fields to preserve on save -->
            <input type="hidden" name="title" value="<?php echo esc_attr($module->title); ?>">
            <input type="hidden" name="slug" value="<?php echo esc_attr($module->slug); ?>">
            <input type="hidden" name="description" value="<?php echo esc_attr($module->description); ?>">
            <input type="hidden" name="short_description" value="<?php echo esc_attr($module->short_description); ?>">
            <textarea name="long_description" style="display:none;"><?php echo esc_textarea($module->long_description); ?></textarea>
            <input type="hidden" name="category_id" value="<?php echo esc_attr($module->category_id); ?>">
            <input type="hidden" name="logo" value="<?php echo esc_url($module->logo); ?>">
            <input type="hidden" name="price" value="<?php echo esc_attr($module->price); ?>">
            <input type="hidden" name="is_active" value="<?php echo $module->is_active ? '1' : '0'; ?>">

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <?php _e('Availability Mode', 'wpt-optica-core'); ?>
                    </th>
                    <td>
                        <label>
                            <input type="radio" name="availability_mode" value="all_tenants"
                                   <?php checked($module->availability_mode, 'all_tenants'); ?>>
                            <?php _e('Available for all tenants', 'wpt-optica-core'); ?>
                        </label>
                        <br><br>
                        <label>
                            <input type="radio" name="availability_mode" value="specific_tenants"
                                   <?php checked($module->availability_mode, 'specific_tenants'); ?>>
                            <?php _e('Available only for specific tenants', 'wpt-optica-core'); ?>
                        </label>

                        <div id="specific-tenants-container" style="margin-top: 20px; padding: 15px; background: #f9f9f9; border: 1px solid #ddd; display: <?php echo $module->availability_mode === 'specific_tenants' ? 'block' : 'none'; ?>;">
                            <p><strong><?php _e('Select Tenants:', 'wpt-optica-core'); ?></strong></p>
                            <div style="max-height: 300px; overflow-y: auto; border: 1px solid #ccc; padding: 10px; background: #fff;">
                                <?php foreach ($tenants as $tenant): ?>
                                    <label style="display: block; margin: 5px 0;">
                                        <input type="checkbox" name="specific_tenants[]" value="<?php echo $tenant->id; ?>"
                                               <?php checked(in_array($tenant->id, $specific_tenant_ids)); ?>>
                                        <?php echo esc_html($tenant->site_url); ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>

            <div class="notice notice-info inline">
                <p><strong><?php _e('Important:', 'wpt-optica-core'); ?></strong> <?php _e('Orice modificare la informațiile modulului (titlu, descriere, preț, logo) va trimite update automat la toți tenants care pot vedea acest modul.', 'wpt-optica-core'); ?></p>
            </div>

            <p class="submit">
                <input type="submit" name="submit" class="button button-primary" value="<?php _e('Save Availability Settings', 'wpt-optica-core'); ?>">
            </p>
        </form>

        <script>
        jQuery(document).ready(function($) {
            $('input[name="availability_mode"]').on('change', function() {
                if ($(this).val() === 'specific_tenants') {
                    $('#specific-tenants-container').slideDown();
                } else {
                    $('#specific-tenants-container').slideUp();
                }
            });
        });
        </script>

    <?php elseif ($active_tab === 'stats' && !$is_new): ?>
        <!-- TAB 3: Stats & Active Tenants -->
        <?php
        // Get module activations with details
        $activations = $wpdb->get_results($wpdb->prepare("
            SELECT
                t.id as tenant_id,
                t.site_url,
                tm.status,
                tm.activated_by,
                tm.deactivated_by,
                tm.activated_at,
                tm.deactivated_at,
                CASE
                    WHEN tm.status = 'active' THEN tm.activated_at
                    ELSE tm.deactivated_at
                END as last_action_at,
                CASE
                    WHEN tm.status = 'active' THEN tm.activated_by
                    ELSE tm.deactivated_by
                END as last_action_by
            FROM {$wpdb->prefix}wpt_tenant_modules tm
            INNER JOIN {$wpdb->prefix}wpt_tenants t ON tm.tenant_id = t.id
            WHERE tm.module_id = %d
            ORDER BY last_action_at DESC
        ", $module_id));

        $total_tenants = count($tenants);
        $active_count = 0;
        $inactive_count = 0;

        foreach ($activations as $activation) {
            if ($activation->status === 'active') {
                $active_count++;
            } else {
                $inactive_count++;
            }
        }

        $available_count = ($module->availability_mode === 'all_tenants') ? $total_tenants : count($specific_tenant_ids);
        ?>

        <div class="wpt-stats-summary">
            <div class="wpt-stat-box">
                <div class="wpt-stat-number"><?php echo $available_count; ?></div>
                <div class="wpt-stat-label"><?php _e('Disponibil la tenants', 'wpt-optica-core'); ?></div>
            </div>
            <div class="wpt-stat-box">
                <div class="wpt-stat-number"><?php echo $active_count; ?></div>
                <div class="wpt-stat-label"><?php _e('Activ', 'wpt-optica-core'); ?></div>
            </div>
            <div class="wpt-stat-box">
                <div class="wpt-stat-number"><?php echo $inactive_count; ?></div>
                <div class="wpt-stat-label"><?php _e('Inactiv', 'wpt-optica-core'); ?></div>
            </div>
            <div class="wpt-stat-box">
                <div class="wpt-stat-number"><?php echo $active_count > 0 ? round(($active_count / $available_count) * 100) : 0; ?>%</div>
                <div class="wpt-stat-label"><?php _e('Rata de activare', 'wpt-optica-core'); ?></div>
            </div>
        </div>

        <h3><?php _e('Module Activations', 'wpt-optica-core'); ?></h3>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Tenant', 'wpt-optica-core'); ?></th>
                    <th><?php _e('Status', 'wpt-optica-core'); ?></th>
                    <th><?php _e('Ultima acțiune de', 'wpt-optica-core'); ?></th>
                    <th><?php _e('Data', 'wpt-optica-core'); ?></th>
                    <th><?php _e('Acțiuni', 'wpt-optica-core'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($activations)): ?>
                    <tr>
                        <td colspan="5"><?php _e('Modulul nu a fost încă activat de niciun tenant', 'wpt-optica-core'); ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($activations as $activation): ?>
                        <tr>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=wpt-tenants&action=edit&id=' . $activation->tenant_id); ?>">
                                    <?php echo esc_html($activation->site_url); ?>
                                </a>
                            </td>
                            <td>
                                <?php if ($activation->status === 'active'): ?>
                                    <span class="wpt-status-badge wpt-status-active"><?php _e('Activ', 'wpt-optica-core'); ?></span>
                                <?php else: ?>
                                    <span class="wpt-status-badge wpt-status-inactive"><?php _e('Inactiv', 'wpt-optica-core'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $action_by = $activation->last_action_by;
                                if ($action_by === 'admin') {
                                    echo '<span class="wpt-badge wpt-badge-warning">' . __('Admin', 'wpt-optica-core') . '</span>';
                                } else {
                                    echo '<span class="wpt-badge wpt-badge-info">' . __('Tenant', 'wpt-optica-core') . '</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($activation->last_action_at))); ?>
                            </td>
                            <td>
                                <!-- TODO: Add force activate/deactivate buttons -->
                                <a href="<?php echo admin_url('admin.php?page=wpt-tenants&action=edit&id=' . $activation->tenant_id . '&tab=modules'); ?>" class="button button-small">
                                    <?php _e('Manage', 'wpt-optica-core'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    // Media uploader for logo
    var mediaUploader;

    $('#wpt-upload-logo').on('click', function(e) {
        e.preventDefault();

        if (mediaUploader) {
            mediaUploader.open();
            return;
        }

        mediaUploader = wp.media({
            title: '<?php _e("Select Module Logo", "wpt-optica-core"); ?>',
            button: {
                text: '<?php _e("Use this image", "wpt-optica-core"); ?>'
            },
            multiple: false
        });

        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#logo').val(attachment.url);
        });

        mediaUploader.open();
    });
});
</script>

<style>
.wpt-stats-summary {
    display: flex;
    gap: 20px;
    margin: 20px 0;
}

.wpt-stat-box {
    flex: 1;
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    text-align: center;
}

.wpt-stat-number {
    font-size: 36px;
    font-weight: 700;
    color: #2271b1;
    margin-bottom: 8px;
}

.wpt-stat-label {
    font-size: 14px;
    color: #646970;
    text-transform: uppercase;
    font-weight: 500;
}

.wpt-badge {
    display: inline-block;
    padding: 4px 8px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    border-radius: 3px;
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
</style>
