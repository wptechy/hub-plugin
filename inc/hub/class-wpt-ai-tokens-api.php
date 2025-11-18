<?php
/**
 * AI Tokens API - Receives token usage from tenants
 *
 * REST API endpoint for logging AI token consumption from tenant sites.
 *
 * @package WPT_Hub
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPT_AI_Tokens_API {

    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Register REST API routes
     */
    public function register_routes() {
        register_rest_route('wpt-hub/v1', '/ai-tokens/log', array(
            'methods' => 'POST',
            'callback' => array($this, 'log_token_usage'),
            'permission_callback' => array($this, 'verify_tenant_request'),
        ));

        // Get tenant's current token usage
        register_rest_route('wpt-hub/v1', '/ai-tokens/usage', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_token_usage'),
            'permission_callback' => array($this, 'verify_tenant_request'),
        ));
    }

    /**
     * Verify tenant request using API key
     *
     * @param WP_REST_Request $request
     * @return bool
     */
    public function verify_tenant_request($request) {
        $api_key = $request->get_header('X-WPT-API-Key');
        $tenant_key = $request->get_header('X-WPT-Tenant-Key');

        if (empty($api_key) || empty($tenant_key)) {
            return new WP_Error(
                'missing_credentials',
                'Missing X-WPT-API-Key or X-WPT-Tenant-Key header',
                array('status' => 401)
            );
        }

        global $wpdb;

        $tenant = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}wpt_tenants
            WHERE tenant_key = %s AND api_key = %s",
            $tenant_key,
            $api_key
        ));

        if (empty($tenant)) {
            return new WP_Error(
                'invalid_credentials',
                'Invalid tenant_key or api_key',
                array('status' => 403)
            );
        }

        // Store tenant ID in request for later use
        $request->set_param('_tenant_id', $tenant->id);

        return true;
    }

    /**
     * Log token usage from tenant
     *
     * POST /wp-json/wpt-hub/v1/ai-tokens/log
     *
     * Headers:
     *   X-WPT-API-Key: {api_key}
     *   X-WPT-Tenant-Key: {tenant_key}
     *
     * Body:
     * {
     *   "tokens_input": 1000,
     *   "tokens_output": 500,
     *   "operation_type": "logo_analysis"
     * }
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function log_token_usage($request) {
        global $wpdb;

        $tenant_key = $request->get_header('X-WPT-Tenant-Key');
        $api_key = $request->get_header('X-WPT-API-Key');

        $params = $request->get_json_params();

        $tokens_input = isset($params['tokens_input']) ? intval($params['tokens_input']) : 0;
        $tokens_output = isset($params['tokens_output']) ? intval($params['tokens_output']) : 0;
        $operation_type = isset($params['operation_type']) ? sanitize_text_field($params['operation_type']) : 'unknown';

        $total_tokens = $tokens_input + $tokens_output;

        if ($total_tokens <= 0) {
            return new WP_Error(
                'invalid_token_count',
                'Invalid token count. Must be greater than 0.',
                array('status' => 400)
            );
        }

        // Get tenant
        $tenant = $wpdb->get_row($wpdb->prepare(
            "SELECT id, ai_tokens_used, site_url FROM {$wpdb->prefix}wpt_tenants
            WHERE tenant_key = %s AND api_key = %s",
            $tenant_key,
            $api_key
        ));

        if (!$tenant) {
            return new WP_Error(
                'tenant_not_found',
                'Tenant not found',
                array('status' => 404)
            );
        }

        // Update tenant's total tokens
        $new_total = $tenant->ai_tokens_used + $total_tokens;

        $update_result = $wpdb->update(
            $wpdb->prefix . 'wpt_tenants',
            array(
                'ai_tokens_used' => $new_total,
                'updated_at' => current_time('mysql')
            ),
            array('id' => $tenant->id),
            array('%d', '%s'),
            array('%d')
        );

        if ($update_result === false) {
            return new WP_Error(
                'update_failed',
                'Failed to update token count: ' . $wpdb->last_error,
                array('status' => 500)
            );
        }

        // Log to PHP error log for debugging
        error_log(sprintf(
            'WPT AI Tokens: Tenant #%d (%s) logged %d tokens (%s): %d input + %d output',
            $tenant->id,
            $tenant->site_url,
            $total_tokens,
            $operation_type,
            $tokens_input,
            $tokens_output
        ));

        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Token usage logged successfully',
            'data' => array(
                'tenant_id' => $tenant->id,
                'tokens_logged' => $total_tokens,
                'tokens_input' => $tokens_input,
                'tokens_output' => $tokens_output,
                'total_tokens' => $new_total,
                'operation_type' => $operation_type,
            )
        ), 200);
    }

    /**
     * Get current token usage for tenant
     *
     * GET /wp-json/wpt-hub/v1/ai-tokens/usage
     *
     * Headers:
     *   X-WPT-API-Key: {api_key}
     *   X-WPT-Tenant-Key: {tenant_key}
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function get_token_usage($request) {
        global $wpdb;

        $tenant_key = $request->get_header('X-WPT-Tenant-Key');
        $api_key = $request->get_header('X-WPT-API-Key');

        // Get tenant
        $tenant = $wpdb->get_row($wpdb->prepare(
            "SELECT id, ai_tokens_used, site_url, status FROM {$wpdb->prefix}wpt_tenants
            WHERE tenant_key = %s AND api_key = %s",
            $tenant_key,
            $api_key
        ));

        if (!$tenant) {
            return new WP_Error(
                'tenant_not_found',
                'Tenant not found',
                array('status' => 404)
            );
        }

        // Calculate estimated cost (Claude Sonnet pricing: $3/1M input, $15/1M output)
        // Using average estimate: $3/1M tokens
        $estimated_cost_usd = ($tenant->ai_tokens_used / 1000000) * 3.00;

        return new WP_REST_Response(array(
            'success' => true,
            'data' => array(
                'tenant_id' => $tenant->id,
                'site_url' => $tenant->site_url,
                'status' => $tenant->status,
                'tokens_used' => $tenant->ai_tokens_used,
                'estimated_cost_usd' => round($estimated_cost_usd, 4),
            )
        ), 200);
    }
}

// Initialize
new WPT_AI_Tokens_API();
