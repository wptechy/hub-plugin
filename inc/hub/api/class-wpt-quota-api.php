<?php
/**
 * WPT Quota API
 *
 * API endpoints for tenant quota information
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPT_Quota_API {

    /**
     * Initialize
     */
    public static function init() {
        add_action('rest_api_init', array(__CLASS__, 'register_routes'));
    }

    /**
     * Register REST API routes
     */
    public static function register_routes() {
        register_rest_route('wpt/v1', '/tenant/quota', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_quota_data'),
            'permission_callback' => array(__CLASS__, 'verify_api_key'),
        ));
    }

    /**
     * Verify API key (compatible with existing API server)
     *
     * @param WP_REST_Request $request Request object
     * @return bool|WP_Error True if tenant is authorized
     */
    public static function verify_api_key($request) {
        $api_key = $request->get_header('X-WPT-API-Key');

        if (empty($api_key)) {
            return new WP_Error('unauthorized', 'Missing API key', array('status' => 401));
        }

        // Verify tenant exists and API key matches
        global $wpdb;
        $tenant = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpt_tenants WHERE api_key = %s",
            $api_key
        ));

        if (!$tenant || $tenant->status !== 'active') {
            return new WP_Error('unauthorized', 'Invalid API key or inactive tenant', array('status' => 401));
        }

        return true;
    }

    /**
     * Get quota data for tenant
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object
     */
    public static function get_quota_data($request) {
        $api_key = $request->get_header('X-WPT-API-Key');

        // Get tenant by API key
        global $wpdb;
        $tenant = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpt_tenants WHERE api_key = %s",
            $api_key
        ));

        if (!$tenant) {
            return new WP_Error('tenant_not_found', 'Tenant not found', array('status' => 404));
        }

        // Get tenant's WordPress user (using hub_user_id column)
        $user = get_user_by('id', $tenant->hub_user_id);

        if (!$user) {
            return new WP_Error('user_not_found', 'Tenant user not found', array('status' => 404));
        }

        // Get quota limits from user meta
        $offer_limit = (int) get_user_meta($user->ID, 'wpt_offer_slots', true) ?: 3;
        $job_limit = (int) get_user_meta($user->ID, 'wpt_job_slots', true) ?: 5;
        $location_limit = (int) get_user_meta($user->ID, 'wpt_location_slots', true) ?: 5;

        // Count published items for this tenant
        $offer_count = self::count_tenant_posts($tenant->id, 'oferta');
        $job_count = self::count_tenant_posts($tenant->id, 'job');
        $location_count = self::count_tenant_posts($tenant->id, 'locatie');
        $medic_count = self::count_tenant_posts($tenant->id, 'medic');

        $response = array(
            'limits' => array(
                'oferta' => $offer_limit,
                'job' => $job_limit,
                'locatie' => $location_limit,
                'medic' => 0 // unlimited
            ),
            'published' => array(
                'oferta' => $offer_count,
                'job' => $job_count,
                'locatie' => $location_count,
                'medic' => $medic_count
            ),
            'remaining' => array(
                'oferta' => max(0, $offer_limit - $offer_count),
                'job' => max(0, $job_limit - $job_count),
                'locatie' => max(0, $location_limit - $location_count),
                'medic' => 999999
            )
        );

        return rest_ensure_response($response);
    }

    /**
     * Count published posts for tenant
     *
     * @param int $tenant_id Tenant ID
     * @param string $post_type Post type
     * @return int Count
     */
    private static function count_tenant_posts($tenant_id, $post_type) {
        $args = array(
            'post_type' => $post_type,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_wpt_tenant_id',
                    'value' => $tenant_id,
                    'compare' => '='
                )
            ),
            'fields' => 'ids'
        );

        $query = new WP_Query($args);
        return $query->found_posts;
    }
}

// Initialize
WPT_Quota_API::init();
