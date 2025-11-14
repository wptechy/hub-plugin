<?php
/**
 * Release Manager (HUB Admin)
 * Manages plugin/theme releases and versioning
 *
 * @package WPT_Optica_Core
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPT_Release_Manager {

    public function __construct() {
        // TODO: Implement release management admin pages
    }

    /**
     * Get latest release
     */
    public static function get_latest_release($package_type, $package_name) {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpt_releases
            WHERE package_type = %s AND package_name = %s AND status = 'available'
            ORDER BY published_at DESC
            LIMIT 1",
            $package_type,
            $package_name
        ));
    }
}

new WPT_Release_Manager();
