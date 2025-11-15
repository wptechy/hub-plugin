<?php
/**
 * HUB API Server
 * Handles REST API endpoints for Site Optica communication
 *
 * See docs/06-API-CONTRACTS.md for complete API documentation
 *
 * @package WPT_Optica_Core
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPT_API_Server {

    const API_NAMESPACE = 'wpt/v1';

    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    public function register_routes() {
        // Ping - Test connection
        register_rest_route(self::API_NAMESPACE, '/ping', array(
            'methods' => 'GET',
            'callback' => array($this, 'ping'),
            'permission_callback' => array($this, 'verify_api_key'),
        ));

        // Tenant Info - Get tenant and brand info
        register_rest_route(self::API_NAMESPACE, '/tenant/info', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_tenant_info'),
            'permission_callback' => array($this, 'verify_api_key'),
        ));

        // Module Management
        register_rest_route(self::API_NAMESPACE, '/modules', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_modules'),
            'permission_callback' => array($this, 'verify_api_key'),
        ));

        register_rest_route(self::API_NAMESPACE, '/modules/activate', array(
            'methods' => 'POST',
            'callback' => array($this, 'activate_module'),
            'permission_callback' => array($this, 'verify_api_key'),
        ));

        // Updates
        register_rest_route(self::API_NAMESPACE, '/updates/check', array(
            'methods' => 'GET',
            'callback' => array($this, 'check_updates'),
            'permission_callback' => array($this, 'verify_api_key'),
        ));

        // User Sync
        register_rest_route(self::API_NAMESPACE, '/sync/user', array(
            'methods' => 'POST',
            'callback' => array($this, 'sync_user'),
            'permission_callback' => array($this, 'verify_api_key'),
        ));

        // Post Sync
        register_rest_route(self::API_NAMESPACE, '/sync/post', array(
            'methods' => 'POST',
            'callback' => array($this, 'sync_post'),
            'permission_callback' => array($this, 'verify_api_key'),
        ));

        register_rest_route(self::API_NAMESPACE, '/sync/post/delete', array(
            'methods' => 'POST',
            'callback' => array($this, 'sync_post_delete'),
            'permission_callback' => array($this, 'verify_api_key'),
        ));

        // License
        register_rest_route(self::API_NAMESPACE, '/license/activate', array(
            'methods' => 'POST',
            'callback' => array($this, 'activate_license'),
            'permission_callback' => '__return_true',
        ));

        // Analytics
        register_rest_route(self::API_NAMESPACE, '/analytics/report', array(
            'methods' => 'POST',
            'callback' => array($this, 'receive_analytics'),
            'permission_callback' => array($this, 'verify_api_key'),
        ));
    }

    public function verify_api_key($request) {
        $api_key = $request->get_header('X-WPT-API-Key');
        if (empty($api_key)) {
            return new WP_Error('unauthorized', 'Missing API key', array('status' => 401));
        }

        $tenant = $this->get_tenant_by_api_key($api_key);
        if (!$tenant || $tenant->status !== 'active') {
            return new WP_Error('unauthorized', 'Invalid API key', array('status' => 401));
        }

        $request->set_param('_tenant', $tenant);
        return true;
    }

    private function get_tenant_by_api_key($api_key) {
        global $wpdb;

        // Get tenant by API key directly from tenants table
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpt_tenants WHERE api_key = %s",
            $api_key
        ));
    }

    // Endpoint handlers

    /**
     * Ping endpoint - Test connection
     */
    public function ping($request) {
        $tenant = $request->get_param('_tenant');

        return $this->success_response(array(
            'message' => 'Connection successful',
            'tenant_key' => $tenant->tenant_key,
            'status' => $tenant->status,
            'timestamp' => current_time('mysql'),
        ));
    }

    /**
     * Get tenant info including brand data
     */
    public function get_tenant_info($request) {
        $tenant = $request->get_param('_tenant');

        // Get brand post
        $brand = get_post($tenant->brand_id);

        if (!$brand) {
            return $this->error_response('Brand not found', 404);
        }

        // Prepare brand data
        $brand_data = array(
            'id' => $brand->ID,
            'title' => $brand->post_title,
            'content' => $brand->post_content,
            'excerpt' => $brand->post_excerpt,
        );

        // Get ACF fields if available
        if (function_exists('get_fields')) {
            $acf_fields = get_fields($brand->ID);
            if ($acf_fields) {
                $brand_data['acf_fields'] = $acf_fields;
            }
        }

        // Get featured image
        if (has_post_thumbnail($brand->ID)) {
            $brand_data['featured_image_id'] = get_post_thumbnail_id($brand->ID);
            $brand_data['featured_image_url'] = get_the_post_thumbnail_url($brand->ID, 'full');
        }

        // Auto-push sync configuration on first connection
        $this->auto_push_sync_config_if_needed($tenant);

        return $this->success_response(array(
            'tenant' => array(
                'id' => $tenant->id,
                'tenant_key' => $tenant->tenant_key,
                'status' => $tenant->status,
                'site_url' => $tenant->site_url,
            ),
            'brand' => $brand_data,
        ));
    }

    public function get_modules($request) {
        global $wpdb;

        // Get tenant from request (set by verify_api_key)
        $tenant = $request->get_param('_tenant');
        $tenant_id = $tenant ? $tenant->id : 0;

        // Get all categories
        $categories = $wpdb->get_results("
            SELECT
                id,
                name,
                slug,
                icon,
                description,
                sort_order
            FROM {$wpdb->prefix}wpt_module_categories
            ORDER BY sort_order ASC
        ", ARRAY_A);

        // Get all active modules with their categories
        // Include modules that are either:
        // 1. Available to all tenants (availability_mode = 'all_tenants')
        // 2. Available to specific tenants AND this tenant has access (via wp_wpt_tenant_modules)
        $modules = $wpdb->get_results($wpdb->prepare("
            SELECT DISTINCT
                m.id,
                m.title as name,
                m.slug,
                m.description,
                m.logo,
                m.category_id,
                m.price,
                m.is_active,
                m.availability_mode,
                c.name as category_name,
                c.slug as category_slug,
                c.icon as category_icon
            FROM {$wpdb->prefix}wpt_available_modules m
            LEFT JOIN {$wpdb->prefix}wpt_module_categories c ON m.category_id = c.id
            LEFT JOIN {$wpdb->prefix}wpt_tenant_modules tm ON m.id = tm.module_id AND tm.tenant_id = %d
            WHERE m.is_active = 1
              AND (
                  m.availability_mode = 'all_tenants'
                  OR (m.availability_mode = 'specific_tenants' AND tm.id IS NOT NULL)
              )
            ORDER BY c.sort_order, m.title
        ", $tenant_id), ARRAY_A);

        if (empty($modules)) {
            $modules = array();
        }

        return $this->success_response(array(
            'modules' => $modules,
            'categories' => $categories
        ));
    }

    public function activate_module($request) {
        return $this->success_response(array('activated' => true));
    }

    public function check_updates($request) {
        return $this->success_response(array('updates' => array()));
    }

    public function sync_user($request) {
        return $this->success_response(array('synced' => true));
    }

    /**
     * Sync post from Client to HUB
     */
    public function sync_post($request) {
        error_log('[WPT DEBUG] sync_post endpoint called');
        error_log('[WPT DEBUG] Request params: ' . print_r($request->get_params(), true));

        $tenant = $request->get_param('_tenant');
        error_log('[WPT DEBUG] Tenant: ' . print_r($tenant, true));

        $post_type = $request->get_param('post_type');
        $post_data = $request->get_param('post_data');

        if (!$post_type || !$post_data) {
            error_log('[WPT ERROR] Missing post_type or post_data');
            return $this->error_response('Missing post_type or post_data', 400);
        }

        error_log('[WPT DEBUG] Syncing post_type: ' . $post_type . ', post_id: ' . $post_data['id']);

        // Find existing post by _client_post_id meta
        $existing_posts = get_posts(array(
            'post_type' => $post_type,
            'meta_query' => array(
                array(
                    'key' => '_client_post_id',
                    'value' => $post_data['id'],
                ),
                array(
                    'key' => '_tenant_id',
                    'value' => $tenant->id,
                ),
            ),
            'posts_per_page' => 1,
        ));

        if (!empty($existing_posts)) {
            // Update existing post
            $post_id = $existing_posts[0]->ID;
            wp_update_post(array(
                'ID' => $post_id,
                'post_title' => $post_data['title'],
                'post_content' => $post_data['content'],
                'post_excerpt' => $post_data['excerpt'],
                'post_status' => !empty($post_data['status']) ? $post_data['status'] : 'publish',
                'post_author' => $tenant->hub_user_id, // Set author to tenant user
            ));
        } else {
            // Create new post
            $post_id = wp_insert_post(array(
                'post_type' => $post_type,
                'post_title' => $post_data['title'],
                'post_content' => $post_data['content'],
                'post_excerpt' => $post_data['excerpt'],
                'post_status' => !empty($post_data['status']) ? $post_data['status'] : 'publish',
                'post_author' => $tenant->hub_user_id, // Set author to tenant user
            ));

            if (is_wp_error($post_id)) {
                return $this->error_response('Failed to create post', 500);
            }

            // Store client post ID and tenant ID
            update_post_meta($post_id, '_client_post_id', $post_data['id']);
            update_post_meta($post_id, '_tenant_id', $tenant->id);
        }

        // Update ACF fields if present
        if (!empty($post_data['acf_fields']) && function_exists('update_field')) {
            foreach ($post_data['acf_fields'] as $field_key => $field_value) {
                update_field($field_key, $field_value, $post_id);
            }
        }

        // Update featured image if present
        if (!empty($post_data['featured_image'])) {
            set_post_thumbnail($post_id, $post_data['featured_image']);
        }

        error_log('[WPT SUCCESS] Post synced successfully. HUB post_id: ' . $post_id);

        return $this->success_response(array(
            'post_id' => $post_id,
            'message' => 'Post synced successfully',
        ));
    }

    /**
     * Sync post deletion from Client to HUB
     */
    public function sync_post_delete($request) {
        $tenant = $request->get_param('_tenant');
        $post_id = $request->get_param('post_id');
        $post_type = $request->get_param('post_type');

        if (!$post_id || !$post_type) {
            return $this->error_response('Missing post_id or post_type', 400);
        }

        // Find post by _client_post_id meta
        $existing_posts = get_posts(array(
            'post_type' => $post_type,
            'meta_query' => array(
                array(
                    'key' => '_client_post_id',
                    'value' => $post_id,
                ),
                array(
                    'key' => '_tenant_id',
                    'value' => $tenant->id,
                ),
            ),
            'posts_per_page' => 1,
        ));

        if (!empty($existing_posts)) {
            wp_delete_post($existing_posts[0]->ID, true);
            return $this->success_response(array('deleted' => true));
        }

        return $this->error_response('Post not found', 404);
    }

    public function activate_license($request) {
        return $this->success_response(array('activated' => true, 'api_key' => wp_generate_password(32, false)));
    }

    public function receive_analytics($request) {
        return $this->success_response(array('recorded' => true));
    }

    /**
     * Auto-push sync configuration to tenant on first connection
     */
    private function auto_push_sync_config_if_needed($tenant) {
        error_log('[WPT SYNC] Auto-push check for tenant ID: ' . $tenant->id);

        // Check if configuration was already pushed
        $last_push = get_option('wpt_tenant_config_pushed_' . $tenant->id);

        // If already pushed, skip
        if ($last_push) {
            error_log('[WPT SYNC] Already pushed at: ' . $last_push . ', skipping');
            return;
        }

        // Load WPT_Sync_Config_Admin class if not loaded
        if (!class_exists('WPT_Sync_Config_Admin')) {
            error_log('[WPT SYNC] Loading WPT_Sync_Config_Admin class');
            require_once WPT_PLUGIN_DIR . 'inc/hub/admin/class-wpt-sync-config-admin.php';
        }

        // Check if class loaded successfully
        if (!class_exists('WPT_Sync_Config_Admin')) {
            error_log('[WPT SYNC] Failed to load WPT_Sync_Config_Admin class');
            return;
        }

        // Get tenant configuration (or global if not set)
        $config = get_option('wpt_tenant_sync_config_' . $tenant->id);
        if (empty($config)) {
            error_log('[WPT SYNC] No tenant-specific config, checking global config');
            $config = get_option('wpt_sync_configuration', array());
        }

        // If no configuration available, skip
        if (empty($config)) {
            error_log('[WPT SYNC] No configuration found, skipping auto-push');
            return;
        }

        error_log('[WPT SYNC] Found config: ' . print_r($config, true));

        // Push configuration to tenant (use singleton instance)
        $sync_admin = WPT_Sync_Config_Admin::get_instance();
        error_log('[WPT SYNC] Calling do_push_config_to_tenant for tenant ID: ' . $tenant->id);
        $push_result = $sync_admin->do_push_config_to_tenant($tenant->id);

        // Mark as pushed if successful
        if (is_wp_error($push_result)) {
            error_log('[WPT SYNC] Push failed: ' . $push_result->get_error_message());
        } else {
            error_log('[WPT SYNC] Push successful, marking as pushed');
            update_option('wpt_tenant_config_pushed_' . $tenant->id, current_time('mysql'));
        }
    }

    private function success_response($data, $status = 200) {
        return new WP_REST_Response(array('success' => true, 'data' => $data), $status);
    }

    private function error_response($message, $status = 400) {
        return new WP_REST_Response(array('success' => false, 'message' => $message), $status);
    }
}

new WPT_API_Server();
