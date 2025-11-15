<?php
/**
 * WPT Sync Configuration Admin
 *
 * Manages the admin interface for configuring what CPTs, taxonomies,
 * and ACF fields should be synced to client sites
 *
 * @package WPT_Optica_Core
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPT_Sync_Config_Admin {

    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'), 20);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_wpt_save_sync_config', array($this, 'save_sync_config'));
        add_action('wp_ajax_wpt_push_config_to_tenant', array($this, 'push_config_to_tenant'));
        add_action('wp_ajax_wpt_get_acf_fields', array($this, 'get_acf_fields_ajax'));
        add_action('wp_ajax_wpt_push_modules_to_tenant', array($this, 'push_modules_to_tenant'));
        add_action('wp_ajax_wpt_toggle_tenant_module', array($this, 'toggle_tenant_module'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'wpt-platform',
            __('Sync Configuration', 'wpt-optica-core'),
            __('Sync Configuration', 'wpt-optica-core'),
            'manage_options',
            'wpt-sync-config',
            array($this, 'render_page')
        );
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_scripts($hook) {
        // Load on sync config page AND tenants page
        if ('wpt-platform_page_wpt-sync-config' !== $hook && 'wpt-platform_page_wpt-tenants' !== $hook) {
            return;
        }

        wp_enqueue_style(
            'wpt-sync-config',
            WPT_HUB_URL . 'assets/css/sync-config.css',
            array(),
            WPT_HUB_VERSION
        );

        wp_enqueue_script(
            'wpt-sync-config',
            WPT_HUB_URL . 'assets/js/sync-config-v2.js',
            array('jquery'),
            WPT_HUB_VERSION,
            true
        );

        // Get saved configuration
        $saved_config = get_option('wpt_sync_configuration', array());

        wp_localize_script('wpt-sync-config', 'wptSyncConfig', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpt_sync_config_nonce'),
            'savedConfig' => $saved_config, // Pass saved configuration to JavaScript
            'strings' => array(
                'saving' => __('Saving...', 'wpt-optica-core'),
                'saved' => __('Configuration saved!', 'wpt-optica-core'),
                'error' => __('Error saving configuration', 'wpt-optica-core'),
                'pushing' => __('Pushing to tenant...', 'wpt-optica-core'),
                'pushed' => __('Configuration pushed successfully!', 'wpt-optica-core'),
                'pushError' => __('Error pushing configuration', 'wpt-optica-core'),
            )
        ));
    }

    /**
     * Render admin page
     */
    public function render_page() {
        // Get current configuration
        $config = get_option('wpt_sync_configuration', array(
            'cpts' => array(),
            'taxonomies' => array(),
            'field_groups' => array(),
            'fields' => array()
        ));

        // Get available CPTs (registered by plugin)
        $available_cpts = $this->get_available_cpts();

        // Get available taxonomies
        $available_taxonomies = $this->get_available_taxonomies();

        // Get ACF field groups
        $field_groups = $this->get_acf_field_groups();

        include WPT_HUB_DIR . 'inc/hub/admin/views/sync-config-v2.php';
    }

    /**
     * Get available CPTs registered by the plugin
     */
    private function get_available_cpts() {
        $cpts = array();

        // Get registered CPTs from our plugin
        $registered_cpts = get_post_types(array('_builtin' => false), 'objects');

        foreach ($registered_cpts as $cpt_slug => $cpt_object) {
            $cpts[$cpt_slug] = array(
                'label' => $cpt_object->labels->name,
                'singular' => $cpt_object->labels->singular_name,
                'description' => $cpt_object->description
            );
        }

        return $cpts;
    }

    /**
     * Get available taxonomies registered by the plugin
     */
    private function get_available_taxonomies() {
        $taxonomies = array();

        // Get registered taxonomies from our plugin
        $registered_taxonomies = get_taxonomies(array('_builtin' => false), 'objects');

        foreach ($registered_taxonomies as $tax_slug => $tax_object) {
            $taxonomies[$tax_slug] = array(
                'label' => $tax_object->labels->name,
                'singular' => $tax_object->labels->singular_name,
                'post_types' => $tax_object->object_type
            );
        }

        return $taxonomies;
    }

    /**
     * Get ACF field groups
     */
    private function get_acf_field_groups() {
        if (!function_exists('acf_get_field_groups')) {
            return array();
        }

        $groups = acf_get_field_groups();
        $field_groups = array();

        foreach ($groups as $group) {
            $field_groups[$group['key']] = array(
                'title' => $group['title'],
                'key' => $group['key'],
                'ID' => $group['ID']
            );
        }

        // Sort alphabetically by title
        uasort($field_groups, function($a, $b) {
            return strcasecmp($a['title'], $b['title']);
        });

        return $field_groups;
    }

    /**
     * Get ACF fields for a field group (AJAX)
     */
    public function get_acf_fields_ajax() {
        check_ajax_referer('wpt_sync_config_nonce', 'nonce');

        $group_key = isset($_POST['group_key']) ? sanitize_text_field($_POST['group_key']) : '';

        if (empty($group_key)) {
            wp_send_json_error(array('message' => 'Group key is required'));
        }

        $fields = $this->get_acf_fields_by_group($group_key);

        wp_send_json_success(array('fields' => $fields));
    }

    /**
     * Get ACF fields for a specific group
     */
    private function get_acf_fields_by_group($group_key) {
        if (!function_exists('acf_get_fields')) {
            return array();
        }

        $fields = acf_get_fields($group_key);
        $field_list = array();

        if ($fields) {
            foreach ($fields as $field) {
                $field_list[$field['key']] = array(
                    'label' => $field['label'],
                    'name' => $field['name'],
                    'type' => $field['type'],
                    'key' => $field['key']
                );
            }
        }

        return $field_list;
    }

    /**
     * Save sync configuration (AJAX)
     */
    public function save_sync_config() {
        check_ajax_referer('wpt_sync_config_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }

        // Get posted data
        $cpts = isset($_POST['cpts']) ? array_map('sanitize_text_field', $_POST['cpts']) : array();
        $taxonomies = isset($_POST['taxonomies']) ? array_map('sanitize_text_field', $_POST['taxonomies']) : array();
        $field_groups = isset($_POST['field_groups']) ? array_map('sanitize_text_field', $_POST['field_groups']) : array();
        $fields = isset($_POST['fields']) ? $_POST['fields'] : array();

        // Sanitize fields array
        $sanitized_fields = array();
        foreach ($fields as $group_key => $group_fields) {
            $sanitized_fields[sanitize_text_field($group_key)] = array_map('sanitize_text_field', $group_fields);
        }

        // Build configuration
        $config = array(
            'cpts' => $cpts,
            'taxonomies' => $taxonomies,
            'field_groups' => $field_groups,
            'fields' => $sanitized_fields,
            'updated_at' => current_time('mysql'),
            'updated_by' => get_current_user_id()
        );

        // Save configuration
        update_option('wpt_sync_configuration', $config);

        wp_send_json_success(array(
            'message' => 'Configuration saved successfully',
            'config' => $config
        ));
    }

    /**
     * Push configuration to tenant site (AJAX)
     */
    public function push_config_to_tenant() {
        check_ajax_referer('wpt_sync_config_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }

        $tenant_id = isset($_POST['tenant_id']) ? intval($_POST['tenant_id']) : 0;

        if (empty($tenant_id)) {
            wp_send_json_error(array('message' => 'Tenant ID is required'));
        }

        // Get configuration - either from POST (tenant edit) or from global (sync config page)
        $tenant_config = null;
        if (isset($_POST['config']) && !empty($_POST['config'])) {
            $tenant_config = $_POST['config'];
        }

        // Call the core push method
        $result = $this->do_push_config_to_tenant($tenant_id, $tenant_config);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array(
            'message' => 'Configuration pushed successfully to tenant',
            'response' => $result
        ));
    }

    /**
     * Push configuration to tenant (can be called programmatically or via AJAX)
     *
     * @param int $tenant_id Tenant ID
     * @param array|null $tenant_config Optional configuration array
     * @return array|WP_Error Result or error
     */
    public function do_push_config_to_tenant($tenant_id, $tenant_config = null) {
        if (empty($tenant_id)) {
            return new WP_Error('invalid_tenant', 'Tenant ID is required');
        }

        // Process tenant configuration if provided
        if ($tenant_config && !empty($tenant_config)) {
            // Save this tenant-specific configuration
            update_option('wpt_tenant_sync_config_' . $tenant_id, array(
                'enabled_cpts' => $tenant_config['enabled_cpts'],
                'enabled_taxonomies' => $tenant_config['enabled_taxonomies'],
                'enabled_field_groups' => $tenant_config['enabled_field_groups'],
                'enabled_fields' => $tenant_config['enabled_fields'],
                'last_pushed' => current_time('mysql')
            ));

            // Map to the format expected by push
            $config = array(
                'cpts' => $tenant_config['enabled_cpts'],
                'taxonomies' => $tenant_config['enabled_taxonomies'],
                'field_groups' => $tenant_config['enabled_field_groups'],
                'fields' => $tenant_config['enabled_fields']
            );
        } else {
            // Get from tenant-specific config or fall back to global
            $config = get_option('wpt_tenant_sync_config_' . $tenant_id, array());

            if (empty($config)) {
                $config = get_option('wpt_sync_configuration', array());
            }

            // If still empty, error
            if (empty($config)) {
                return new WP_Error('no_config', 'No configuration to push');
            }

            // Map tenant config format to expected format
            if (isset($config['enabled_cpts'])) {
                $config = array(
                    'cpts' => $config['enabled_cpts'],
                    'taxonomies' => $config['enabled_taxonomies'] ?? array(),
                    'field_groups' => $config['enabled_field_groups'] ?? array(),
                    'fields' => $config['enabled_fields'] ?? array()
                );
            }
        }

        // Get tenant details
        global $wpdb;
        $tenant = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpt_tenants WHERE id = %d",
            $tenant_id
        ));

        if (!$tenant) {
            return new WP_Error('tenant_not_found', 'Tenant not found');
        }

        // Prepare data to push
        $push_data = array(
            'cpts' => !empty($config['cpts']) ? $this->get_cpt_definitions($config['cpts']) : array(),
            'taxonomies' => !empty($config['taxonomies']) ? $this->get_taxonomy_definitions($config['taxonomies']) : array(),
            'acf_json' => !empty($config['field_groups']) ? $this->get_acf_json_files($config['field_groups'], $config['fields']) : array(),
            'field_mappings' => $config['fields'] ?? array()
        );

        // Send to tenant via API
        $api_url = trailingslashit($tenant->site_url) . 'wp-json/wpt/v1/sync-config';

        $response = wp_remote_post($api_url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'X-WPT-Tenant-Key' => $tenant->tenant_key,
                'X-WPT-API-Key' => $tenant->api_key
            ),
            'body' => json_encode($push_data),
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            return new WP_Error('push_failed', 'Failed to push configuration: ' . $response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['success']) && $body['success']) {
            return $body;
        } else {
            return new WP_Error('push_error', isset($body['message']) ? $body['message'] : 'Unknown error');
        }
    }

    /**
     * Get CPT definitions for selected CPTs
     */
    private function get_cpt_definitions($cpt_slugs) {
        $definitions = array();

        foreach ($cpt_slugs as $cpt_slug) {
            $cpt_object = get_post_type_object($cpt_slug);

            if ($cpt_object) {
                // Extract registration arguments
                $definitions[$cpt_slug] = array(
                    'label' => $cpt_object->label,
                    'labels' => (array) $cpt_object->labels,
                    'public' => $cpt_object->public,
                    'has_archive' => $cpt_object->has_archive,
                    'show_in_rest' => $cpt_object->show_in_rest,
                    'supports' => get_all_post_type_supports($cpt_slug),
                    'rewrite' => $cpt_object->rewrite,
                    'capability_type' => $cpt_object->capability_type,
                    'menu_icon' => $cpt_object->menu_icon,
                    'menu_position' => $cpt_object->menu_position
                );
            }
        }

        return $definitions;
    }

    /**
     * Get taxonomy definitions for selected taxonomies
     */
    private function get_taxonomy_definitions($taxonomy_slugs) {
        $definitions = array();

        foreach ($taxonomy_slugs as $tax_slug) {
            $tax_object = get_taxonomy($tax_slug);

            if ($tax_object) {
                $definitions[$tax_slug] = array(
                    'label' => $tax_object->label,
                    'labels' => (array) $tax_object->labels,
                    'public' => $tax_object->public,
                    'hierarchical' => $tax_object->hierarchical,
                    'show_in_rest' => $tax_object->show_in_rest,
                    'rewrite' => $tax_object->rewrite,
                    'object_type' => $tax_object->object_type
                );
            }
        }

        return $definitions;
    }

    /**
     * Get ACF JSON files for selected field groups
     *
     * @param array $field_group_keys Selected field group keys
     * @param array $selected_fields Array of selected fields per group (format: ['group_key' => ['field_key1', 'field_key2']])
     */
    private function get_acf_json_files($field_group_keys, $selected_fields = array()) {
        $json_files = array();

        if (!function_exists('acf_get_field_group')) {
            return $json_files;
        }

        foreach ($field_group_keys as $group_key) {
            $group = acf_get_field_group($group_key);

            if ($group) {
                // Get all fields for this group
                $all_fields = acf_get_fields($group_key);

                // If specific fields are selected for this group, filter them
                if (!empty($selected_fields[$group_key])) {
                    $filtered_fields = array();
                    foreach ($all_fields as $field) {
                        if (in_array($field['key'], $selected_fields[$group_key])) {
                            $filtered_fields[] = $field;
                        }
                    }
                    $group['fields'] = $filtered_fields;
                } else {
                    // If no specific selection, include all fields
                    $group['fields'] = $all_fields;
                }

                // Convert to JSON format
                $json_files[$group_key] = $group;
            }
        }

        return $json_files;
    }

    /**
     * Push modules configuration to tenant (AJAX)
     */
    public function push_modules_to_tenant() {
        check_ajax_referer('wpt_sync_config_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }

        $tenant_id = isset($_POST['tenant_id']) ? intval($_POST['tenant_id']) : 0;
        $enabled_modules = isset($_POST['enabled_modules']) ? array_map('intval', $_POST['enabled_modules']) : array();

        if (empty($tenant_id)) {
            wp_send_json_error(array('message' => 'Tenant ID is required'));
        }

        // Get tenant details
        global $wpdb;
        $tenant = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpt_tenants WHERE id = %d",
            $tenant_id
        ));

        if (!$tenant) {
            wp_send_json_error(array('message' => 'Tenant not found'));
        }

        // Update tenant_modules table
        // First, get current modules for this tenant
        $current_modules = $wpdb->get_col($wpdb->prepare(
            "SELECT module_id FROM {$wpdb->prefix}wpt_tenant_modules WHERE tenant_id = %d",
            $tenant_id
        ));

        // Deactivate modules not in enabled list
        $to_deactivate = array_diff($current_modules, $enabled_modules);
        foreach ($to_deactivate as $module_id) {
            $wpdb->update(
                $wpdb->prefix . 'wpt_tenant_modules',
                array('status' => 'inactive'),
                array('tenant_id' => $tenant_id, 'module_id' => $module_id),
                array('%s'),
                array('%d', '%d')
            );
        }

        // Activate/insert enabled modules
        foreach ($enabled_modules as $module_id) {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}wpt_tenant_modules
                WHERE tenant_id = %d AND module_id = %d",
                $tenant_id,
                $module_id
            ));

            if ($exists) {
                // Update to active
                $wpdb->update(
                    $wpdb->prefix . 'wpt_tenant_modules',
                    array('status' => 'active'),
                    array('tenant_id' => $tenant_id, 'module_id' => $module_id),
                    array('%s'),
                    array('%d', '%d')
                );
            } else {
                // Insert new
                $wpdb->insert(
                    $wpdb->prefix . 'wpt_tenant_modules',
                    array(
                        'tenant_id' => $tenant_id,
                        'module_id' => $module_id,
                        'status' => 'active',
                        'activated_at' => current_time('mysql')
                    ),
                    array('%d', '%d', '%s', '%s')
                );
            }
        }

        // Get module details for push
        $modules_data = array();
        if (!empty($enabled_modules)) {
            $placeholders = implode(',', array_fill(0, count($enabled_modules), '%d'));
            $modules = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}wpt_available_modules WHERE id IN ($placeholders)",
                ...$enabled_modules
            ));

            foreach ($modules as $module) {
                $modules_data[] = array(
                    'id' => $module->id,
                    'slug' => $module->slug,
                    'title' => $module->title,
                    'description' => $module->description,
                    'category_id' => $module->category_id,
                    'price' => $module->price,
                    'icon' => $module->icon
                );
            }
        }

        // Push to tenant via API
        $api_url = trailingslashit($tenant->site_url) . 'wp-json/wpt/v1/modules-config';

        $response = wp_remote_post($api_url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'X-WPT-API-Key' => $tenant->api_key
            ),
            'body' => json_encode(array(
                'modules' => $modules_data
            )),
            'timeout' => 30,
            'sslverify' => false
        ));

        if (is_wp_error($response)) {
            wp_send_json_error(array(
                'message' => 'Failed to push modules: ' . $response->get_error_message()
            ));
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['success']) && $body['success']) {
            wp_send_json_success(array(
                'message' => 'Modules pushed successfully',
                'response' => $body
            ));
        } else {
            wp_send_json_error(array(
                'message' => isset($body['message']) ? $body['message'] : 'Unknown error pushing modules'
            ));
        }
    }

    /**
     * Push module info to all tenants who can see it
     * Called when module info changes (title, description, logo, price)
     *
     * @param int $module_id Module ID
     * @return array Results with success/failure per tenant
     */
    public function push_module_to_all_tenants($module_id) {
        global $wpdb;

        // Get module details
        $module = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpt_available_modules WHERE id = %d",
            $module_id
        ));

        if (!$module) {
            return array('error' => 'Module not found');
        }

        // Get all tenants who can see this module
        if ($module->availability_mode === 'all_tenants') {
            // Get all tenants
            $tenants = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wpt_tenants WHERE status = 'active'");
        } else {
            // Get only specific tenants
            $tenants = $wpdb->get_results($wpdb->prepare(
                "SELECT t.* FROM {$wpdb->prefix}wpt_tenants t
                INNER JOIN {$wpdb->prefix}wpt_module_availability ma ON t.id = ma.tenant_id
                WHERE ma.module_id = %d AND t.status = 'active'",
                $module_id
            ));
        }

        $results = array(
            'success' => array(),
            'failed' => array(),
            'total' => count($tenants)
        );

        foreach ($tenants as $tenant) {
            // Get all active modules for this tenant (including the updated one)
            $active_modules = $wpdb->get_results($wpdb->prepare(
                "SELECT m.* FROM {$wpdb->prefix}wpt_available_modules m
                INNER JOIN {$wpdb->prefix}wpt_tenant_modules tm ON m.id = tm.module_id
                WHERE tm.tenant_id = %d AND tm.status = 'active'",
                $tenant->id
            ));

            $modules_data = array();
            foreach ($active_modules as $mod) {
                $modules_data[] = array(
                    'id' => $mod->id,
                    'slug' => $mod->slug,
                    'title' => $mod->title,
                    'description' => $mod->description,
                    'logo' => $mod->logo,
                    'category_id' => $mod->category_id,
                    'price' => $mod->price
                );
            }

            // Push to tenant via API
            $api_url = trailingslashit($tenant->site_url) . 'wp-json/wpt/v1/modules-config';

            $response = wp_remote_post($api_url, array(
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'X-WPT-API-Key' => $tenant->api_key
                ),
                'body' => json_encode(array(
                    'modules' => $modules_data
                )),
                'timeout' => 30,
                'sslverify' => false
            ));

            if (is_wp_error($response)) {
                $results['failed'][] = array(
                    'tenant_id' => $tenant->id,
                    'site_url' => $tenant->site_url,
                    'error' => $response->get_error_message()
                );
            } else {
                $body = json_decode(wp_remote_retrieve_body($response), true);
                if (isset($body['success']) && $body['success']) {
                    $results['success'][] = array(
                        'tenant_id' => $tenant->id,
                        'site_url' => $tenant->site_url
                    );
                } else {
                    $results['failed'][] = array(
                        'tenant_id' => $tenant->id,
                        'site_url' => $tenant->site_url,
                        'error' => isset($body['message']) ? $body['message'] : 'Unknown error'
                    );
                }
            }
        }

        return $results;
    }

    /**
     * Toggle tenant module status (Force Activate/Deactivate) - AJAX
     */
    public function toggle_tenant_module() {
        check_ajax_referer('wpt_toggle_module', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }

        $module_id = isset($_POST['module_id']) ? intval($_POST['module_id']) : 0;
        $tenant_id = isset($_POST['tenant_id']) ? intval($_POST['tenant_id']) : 0;
        $new_status = isset($_POST['new_status']) ? sanitize_text_field($_POST['new_status']) : '';

        if (empty($module_id) || empty($tenant_id)) {
            wp_send_json_error(array('message' => 'Module ID and Tenant ID are required'));
        }

        if (!in_array($new_status, array('active', 'inactive'))) {
            wp_send_json_error(array('message' => 'Invalid status'));
        }

        global $wpdb;

        // Check if module is available for this tenant
        $module = $wpdb->get_row($wpdb->prepare(
            "SELECT m.*,
                CASE
                    WHEN m.availability_mode = 'all_tenants' THEN 1
                    WHEN ma.tenant_id IS NOT NULL THEN 1
                    ELSE 0
                END as is_available
            FROM {$wpdb->prefix}wpt_available_modules m
            LEFT JOIN {$wpdb->prefix}wpt_module_availability ma ON m.id = ma.module_id AND ma.tenant_id = %d
            WHERE m.id = %d",
            $tenant_id,
            $module_id
        ));

        if (!$module) {
            wp_send_json_error(array('message' => 'Module not found'));
        }

        if (!$module->is_available) {
            wp_send_json_error(array('message' => 'Module is not available for this tenant'));
        }

        // Check if tenant_module record exists
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpt_tenant_modules
            WHERE tenant_id = %d AND module_id = %d",
            $tenant_id,
            $module_id
        ));

        $timestamp = current_time('mysql');

        if ($existing) {
            // Update existing record
            $update_data = array(
                'status' => $new_status
            );

            if ($new_status === 'active') {
                $update_data['activated_by'] = 'admin';
                $update_data['activated_at'] = $timestamp;
            } else {
                $update_data['deactivated_by'] = 'admin';
                $update_data['deactivated_at'] = $timestamp;
            }

            $result = $wpdb->update(
                $wpdb->prefix . 'wpt_tenant_modules',
                $update_data,
                array(
                    'tenant_id' => $tenant_id,
                    'module_id' => $module_id
                ),
                array('%s', '%s', '%s'),
                array('%d', '%d')
            );
        } else {
            // Insert new record
            $insert_data = array(
                'tenant_id' => $tenant_id,
                'module_id' => $module_id,
                'status' => $new_status
            );

            if ($new_status === 'active') {
                $insert_data['activated_by'] = 'admin';
                $insert_data['activated_at'] = $timestamp;
            } else {
                $insert_data['deactivated_by'] = 'admin';
                $insert_data['deactivated_at'] = $timestamp;
            }

            $result = $wpdb->insert(
                $wpdb->prefix . 'wpt_tenant_modules',
                $insert_data,
                array('%d', '%d', '%s', '%s', '%s')
            );
        }

        if ($result === false) {
            wp_send_json_error(array('message' => 'Database error: ' . $wpdb->last_error));
        }

        // Get tenant details for push
        $tenant = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpt_tenants WHERE id = %d",
            $tenant_id
        ));

        if (!$tenant) {
            wp_send_json_error(array('message' => 'Tenant not found'));
        }

        // Get all active modules for this tenant to push
        $active_modules = $wpdb->get_results($wpdb->prepare(
            "SELECT m.* FROM {$wpdb->prefix}wpt_available_modules m
            INNER JOIN {$wpdb->prefix}wpt_tenant_modules tm ON m.id = tm.module_id
            WHERE tm.tenant_id = %d AND tm.status = 'active'",
            $tenant_id
        ));

        $modules_data = array();
        foreach ($active_modules as $mod) {
            $modules_data[] = array(
                'id' => $mod->id,
                'slug' => $mod->slug,
                'title' => $mod->title,
                'description' => $mod->description,
                'logo' => $mod->logo,
                'category_id' => $mod->category_id,
                'price' => $mod->price
            );
        }

        // Push updated modules to tenant
        $api_url = trailingslashit($tenant->site_url) . 'wp-json/wpt/v1/modules-config';

        $response = wp_remote_post($api_url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'X-WPT-API-Key' => $tenant->api_key
            ),
            'body' => json_encode(array(
                'modules' => $modules_data
            )),
            'timeout' => 30,
            'sslverify' => false
        ));

        // Don't fail if push fails, just log it
        $push_success = true;
        $push_message = '';
        if (is_wp_error($response)) {
            $push_success = false;
            $push_message = $response->get_error_message();
        } else {
            $body = json_decode(wp_remote_retrieve_body($response), true);
            if (!isset($body['success']) || !$body['success']) {
                $push_success = false;
                $push_message = isset($body['message']) ? $body['message'] : 'Unknown error';
            }
        }

        wp_send_json_success(array(
            'message' => 'Module ' . ($new_status === 'active' ? 'activated' : 'deactivated') . ' successfully',
            'new_status' => $new_status,
            'push_success' => $push_success,
            'push_message' => $push_message
        ));
    }
}

// Initialize
WPT_Sync_Config_Admin::get_instance();
