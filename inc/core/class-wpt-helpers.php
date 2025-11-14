<?php
/**
 * Helper Functions
 * Utility functions used throughout the plugin
 *
 * @package WPT_Optica_Core
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPT_Helpers {

    /**
     * Generate a secure random API key
     */
    public static function generate_api_key($length = 64) {
        return bin2hex(random_bytes($length / 2));
    }

    /**
     * Generate a unique tenant key
     */
    public static function generate_tenant_key() {
        return 'tenant_' . bin2hex(random_bytes(16));
    }

    /**
     * Verify API authentication
     */
    public static function verify_api_auth($tenant_key, $api_key) {
        global $wpdb;

        $tenant = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpt_tenants
            WHERE tenant_key = %s AND api_key = %s AND status = 'active'",
            $tenant_key,
            $api_key
        ));

        return $tenant ? $tenant : false;
    }

    /**
     * Get tenant by user ID
     */
    public static function get_tenant_by_user($user_id) {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpt_tenants WHERE hub_user_id = %d",
            $user_id
        ));
    }

    /**
     * Get current tenant (for Site Optica)
     * Note: wp_wpt_tenants table only exists on HUB, not on Client sites
     */
    public static function get_current_tenant() {
        static $tenant = null;

        if ($tenant === null) {
            // On Client sites, wp_wpt_tenants table doesn't exist
            // We only store tenant credentials in wp_options
            if (!WPT_IS_HUB) {
                $tenant = false; // Return false on Client sites
                return $tenant;
            }

            $tenant_key = get_option('wpt_tenant_key');
            if ($tenant_key) {
                global $wpdb;
                $tenant = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}wpt_tenants WHERE tenant_key = %s",
                    $tenant_key
                ));
            }
        }

        return $tenant;
    }

    /**
     * Log info message
     */
    public static function log($log_type, $message, $context = null) {
        // On Client sites, use simple error_log for debugging
        if (!WPT_IS_HUB) {
            $log_message = sprintf(
                '[WPT INFO] %s: %s',
                $log_type,
                $message
            );
            if ($context) {
                $log_message .= ' | Context: ' . json_encode($context);
            }
            error_log($log_message);
            return;
        }

        // On HUB, could log to database if needed
        // For now, just use error_log
        $log_message = sprintf('[WPT INFO] %s: %s', $log_type, $message);
        if ($context) {
            $log_message .= ' | Context: ' . json_encode($context);
        }
        error_log($log_message);
    }

    /**
     * Log error to custom table
     */
    public static function log_error($error_type, $message, $context = null, $url = null) {
        // On Client sites, use simple error_log for debugging
        if (!WPT_IS_HUB) {
            $error_message = sprintf(
                '[WPT ERROR] %s: %s',
                $error_type,
                $message
            );
            if ($context) {
                $error_message .= ' | Context: ' . json_encode($context);
            }
            error_log($error_message);
            return;
        }

        // On HUB, log to database
        global $wpdb;

        $tenant_id = null;
        $tenant = self::get_current_tenant();
        $tenant_id = $tenant ? $tenant->id : null;

        $wpdb->insert(
            $wpdb->prefix . 'wpt_error_logs',
            array(
                'tenant_id' => $tenant_id,
                'error_type' => $error_type,
                'message' => $message,
                'context' => is_array($context) ? json_encode($context) : $context,
                'user_id' => get_current_user_id(),
                'url' => $url ? $url : $_SERVER['REQUEST_URI'],
                'created_at' => current_time('mysql'),
            ),
            array('%d', '%s', '%s', '%s', '%d', '%s', '%s')
        );
    }

    /**
     * Add item to sync queue
     */
    public static function add_to_sync_queue($post_id, $post_type, $action) {
        global $wpdb;

        // Check if already queued
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}wpt_sync_queue
            WHERE post_id = %d AND post_type = %s AND status IN ('pending', 'processing')",
            $post_id,
            $post_type
        ));

        if ($existing) {
            // Update existing
            $wpdb->update(
                $wpdb->prefix . 'wpt_sync_queue',
                array(
                    'action' => $action,
                    'updated_at' => current_time('mysql'),
                ),
                array('id' => $existing),
                array('%s', '%s'),
                array('%d')
            );
        } else {
            // Insert new
            $wpdb->insert(
                $wpdb->prefix . 'wpt_sync_queue',
                array(
                    'post_id' => $post_id,
                    'post_type' => $post_type,
                    'action' => $action,
                    'status' => 'pending',
                    'attempts' => 0,
                    'created_at' => current_time('mysql'),
                ),
                array('%d', '%s', '%s', '%s', '%d', '%s')
            );
        }
    }

    /**
     * Get pending sync items
     */
    public static function get_pending_sync_items($limit = 10) {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpt_sync_queue
            WHERE status = 'pending' AND attempts < 3
            ORDER BY created_at ASC
            LIMIT %d",
            $limit
        ));
    }

    /**
     * Update sync item status
     */
    public static function update_sync_status($id, $status, $error = null) {
        global $wpdb;

        $data = array(
            'status' => $status,
            'updated_at' => current_time('mysql'),
        );

        if ($status === 'failed') {
            $data['attempts'] = $wpdb->get_var($wpdb->prepare(
                "SELECT attempts FROM {$wpdb->prefix}wpt_sync_queue WHERE id = %d",
                $id
            )) + 1;
            $data['last_error'] = $error;
        }

        $wpdb->update(
            $wpdb->prefix . 'wpt_sync_queue',
            $data,
            array('id' => $id),
            array('%s', '%s', '%d', '%s'),
            array('%d')
        );
    }

    /**
     * Format Romanian date
     */
    public static function format_ro_date($date, $format = 'j F Y') {
        $months = array(
            'January' => 'ianuarie',
            'February' => 'februarie',
            'March' => 'martie',
            'April' => 'aprilie',
            'May' => 'mai',
            'June' => 'iunie',
            'July' => 'iulie',
            'August' => 'august',
            'September' => 'septembrie',
            'October' => 'octombrie',
            'November' => 'noiembrie',
            'December' => 'decembrie',
        );

        $formatted = date_i18n($format, strtotime($date));
        return str_replace(array_keys($months), array_values($months), $formatted);
    }

    /**
     * Sanitize phone number (Romanian format)
     */
    public static function sanitize_phone($phone) {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        // Add +40 prefix if missing
        if (substr($phone, 0, 1) === '0') {
            $phone = '+4' . $phone;
        } elseif (substr($phone, 0, 3) !== '+40') {
            $phone = '+40' . $phone;
        }

        return $phone;
    }

    /**
     * Get HUB URL from options
     */
    public static function get_hub_url() {
        return get_option('wpt_hub_url', 'https://opticamedicalaro.local');
    }

    /**
     * Check if current site has active modules
     */
    public static function has_active_module($module_slug) {
        if (WPT_IS_HUB) {
            return false; // Modules only on Site
        }

        $active_modules = get_option('wpt_active_modules', array());
        return in_array($module_slug, $active_modules);
    }

    /**
     * Get brand by user ID (HUB only)
     */
    public static function get_user_brand($user_id) {
        if (!WPT_IS_HUB) {
            return null;
        }

        global $wpdb;
        $tenant = $wpdb->get_row($wpdb->prepare(
            "SELECT brand_id FROM {$wpdb->prefix}wpt_tenants WHERE hub_user_id = %d",
            $user_id
        ));

        return $tenant ? $tenant->brand_id : null;
    }

    /**
     * Check if user has website addon
     */
    public static function user_has_website($user_id) {
        if (!WPT_IS_HUB) {
            return true; // Pe Site, evident că are website
        }

        $tenant = self::get_tenant_by_user($user_id);
        return $tenant && !empty($tenant->site_url);
    }

    /**
     * Get site URL for tenant
     */
    public static function get_tenant_site_url($user_id) {
        $tenant = self::get_tenant_by_user($user_id);
        return $tenant ? $tenant->site_url : null;
    }
}
