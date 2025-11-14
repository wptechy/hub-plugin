<?php
/**
 * HUB API Server
 * Handles REST API endpoints for Site Optica communication
 *
 * @package WPT_Optica_Core
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPT_API_Server {

    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Register REST API routes
     */
    public function register_routes() {
        // TODO: Implement API endpoints from 06-API-CONTRACTS.md
        // - GET /wpt/v1/modules
        // - POST /wpt/v1/modules/activate
        // - POST /wpt/v1/sync/user
        // - GET /wpt/v1/updates/check
        // etc.
    }
}

new WPT_API_Server();
