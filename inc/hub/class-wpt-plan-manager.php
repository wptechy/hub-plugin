<?php
/**
 * Plan Manager (HUB)
 * Manages subscription plans and plan-based features
 *
 * @package WPT_Optica_Core
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPT_Plan_Manager {

    public function __construct() {
        // Hook pentru admin features
    }

    /**
     * Get all plans
     *
     * @param bool $active_only Return only active plans
     * @return array
     */
    public static function get_plans($active_only = false) {
        global $wpdb;

        $where = $active_only ? 'WHERE is_active = 1' : '';

        return $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}wpt_plans
            {$where}
            ORDER BY price ASC"
        );
    }

    /**
     * Get plan by ID
     *
     * @param int $plan_id
     * @return object|null
     */
    public static function get_plan($plan_id) {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpt_plans WHERE id = %d",
            $plan_id
        ));
    }

    /**
     * Get plan by slug
     *
     * @param string $slug
     * @return object|null
     */
    public static function get_plan_by_slug($slug) {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpt_plans WHERE slug = %s",
            $slug
        ));
    }

    /**
     * Get plan features (decoded from JSON)
     *
     * @param int $plan_id
     * @return array
     */
    public static function get_plan_features($plan_id) {
        $plan = self::get_plan($plan_id);

        if (!$plan || empty($plan->features)) {
            return array();
        }

        return json_decode($plan->features, true);
    }

    /**
     * Check if plan has specific feature
     *
     * @param int $plan_id
     * @param string $feature_key
     * @return mixed
     */
    public static function plan_has_feature($plan_id, $feature_key) {
        $features = self::get_plan_features($plan_id);
        return isset($features[$feature_key]) ? $features[$feature_key] : false;
    }

    /**
     * Get plan quota for specific resource
     *
     * @param int $plan_id
     * @param string $resource_type (offers, jobs, locations)
     * @return int
     */
    public static function get_plan_quota($plan_id, $resource_type) {
        $features = self::get_plan_features($plan_id);
        return isset($features[$resource_type]) ? intval($features[$resource_type]) : 0;
    }

    /**
     * Get total quota for tenant (plan + addons)
     *
     * @param int $tenant_id
     * @param string $resource_type
     * @return int
     */
    public static function get_tenant_quota($tenant_id, $resource_type) {
        global $wpdb;

        // Get tenant plan quota
        $tenant = $wpdb->get_row($wpdb->prepare(
            "SELECT plan_id FROM {$wpdb->prefix}wpt_tenants WHERE id = %d",
            $tenant_id
        ));

        if (!$tenant || !$tenant->plan_id) {
            return 0;
        }

        $plan_quota = self::get_plan_quota($tenant->plan_id, $resource_type);

        // Get addon quota (based on resource type)
        $addon_quota = 0;

        if ($resource_type === 'offers') {
            // Count extra-offers addons
            $extra_offers = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}wpt_tenant_addons
                WHERE tenant_id = %d AND addon_slug = 'extra-offers' AND status = 'active'",
                $tenant_id
            ));
            $addon_quota = intval($extra_offers);
        }

        if ($resource_type === 'jobs') {
            // Count extra-jobs addons
            $extra_jobs = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}wpt_tenant_addons
                WHERE tenant_id = %d AND addon_slug = 'extra-jobs' AND status = 'active'",
                $tenant_id
            ));
            $addon_quota = intval($extra_jobs);
        }

        return $plan_quota + $addon_quota;
    }

    /**
     * Get tenants count per plan
     *
     * @param int $plan_id
     * @return int
     */
    public static function get_plan_tenants_count($plan_id) {
        global $wpdb;

        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}wpt_tenants
            WHERE plan_id = %d AND status = 'active'",
            $plan_id
        ));
    }

    /**
     * Get monthly revenue for plan
     *
     * @param int $plan_id
     * @return float
     */
    public static function get_plan_revenue($plan_id) {
        $plan = self::get_plan($plan_id);
        $tenant_count = self::get_plan_tenants_count($plan_id);

        return $plan ? ($plan->price * $tenant_count) : 0;
    }

    /**
     * Update plan
     *
     * @param int $plan_id
     * @param array $data
     * @return bool
     */
    public static function update_plan($plan_id, $data) {
        global $wpdb;

        // Validate required fields
        if (empty($data['name']) || empty($data['slug'])) {
            return false;
        }

        // Prepare update data
        $update_data = array(
            'name' => sanitize_text_field($data['name']),
            'slug' => sanitize_title($data['slug']),
            'price' => floatval($data['price']),
            'billing_period' => sanitize_text_field($data['billing_period']),
            'is_active' => isset($data['is_active']) ? 1 : 0,
        );

        // Handle features JSON
        if (isset($data['features'])) {
            $update_data['features'] = is_string($data['features'])
                ? $data['features']
                : json_encode($data['features']);
        }

        $result = $wpdb->update(
            $wpdb->prefix . 'wpt_plans',
            $update_data,
            array('id' => $plan_id),
            array('%s', '%s', '%f', '%s', '%d', '%s'),
            array('%d')
        );

        return $result !== false;
    }

    /**
     * Create new plan
     *
     * @param array $data
     * @return int|false Plan ID or false on failure
     */
    public static function create_plan($data) {
        global $wpdb;

        // Validate required fields
        if (empty($data['name']) || empty($data['slug'])) {
            return false;
        }

        // Prepare insert data
        $insert_data = array(
            'name' => sanitize_text_field($data['name']),
            'slug' => sanitize_title($data['slug']),
            'price' => floatval($data['price'] ?? 0),
            'billing_period' => sanitize_text_field($data['billing_period'] ?? 'monthly'),
            'is_active' => isset($data['is_active']) ? 1 : 0,
            'created_at' => current_time('mysql'),
        );

        // Handle features JSON
        if (isset($data['features'])) {
            $insert_data['features'] = is_string($data['features'])
                ? $data['features']
                : json_encode($data['features']);
        }

        $result = $wpdb->insert(
            $wpdb->prefix . 'wpt_plans',
            $insert_data,
            array('%s', '%s', '%f', '%s', '%d', '%s', '%s')
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Delete plan (only if no active tenants)
     *
     * @param int $plan_id
     * @return bool|WP_Error
     */
    public static function delete_plan($plan_id) {
        global $wpdb;

        // Check if plan has active tenants
        $tenant_count = self::get_plan_tenants_count($plan_id);

        if ($tenant_count > 0) {
            return new WP_Error(
                'plan_has_tenants',
                sprintf(__('Cannot delete plan. %d active tenants are using this plan.', 'wpt-optica-core'), $tenant_count)
            );
        }

        $result = $wpdb->delete(
            $wpdb->prefix . 'wpt_plans',
            array('id' => $plan_id),
            array('%d')
        );

        return $result !== false;
    }
}

new WPT_Plan_Manager();
