<?php
/**
 * Module Manager (HUB)
 * Manages available modules and tenant module activations
 *
 * @package WPT_Optica_Core
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPT_Module_Manager {

    public function __construct() {
        // TODO: Implement module management hooks
    }

    /**
     * Get available modules
     */
    public static function get_available_modules() {
        global $wpdb;

        return $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}wpt_available_modules
            WHERE is_active = 1
            ORDER BY category_id, title"
        );
    }

    /**
     * Get tenant active modules
     */
    public static function get_tenant_modules($tenant_id) {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT m.*, tm.status, tm.activated_at
            FROM {$wpdb->prefix}wpt_tenant_modules tm
            JOIN {$wpdb->prefix}wpt_available_modules m ON tm.module_id = m.id
            WHERE tm.tenant_id = %d AND tm.status = 'active'",
            $tenant_id
        ));
    }
}

new WPT_Module_Manager();
