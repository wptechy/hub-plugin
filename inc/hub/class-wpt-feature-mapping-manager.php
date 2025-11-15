<?php
/**
 * Feature Mapping Manager (HUB)
 * Manages feature mappings - defines what each feature key represents
 *
 * @package WPT_Optica_Core
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPT_Feature_Mapping_Manager {

    /**
     * Get all feature mappings
     *
     * @return array
     */
    public static function get_mappings() {
        global $wpdb;

        return $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}wpt_feature_mappings
            ORDER BY feature_name ASC"
        );
    }

    /**
     * Get mapping by feature key
     *
     * @param string $feature_key
     * @return object|null
     */
    public static function get_mapping($feature_key) {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpt_feature_mappings WHERE feature_key = %s",
            $feature_key
        ));
    }

    /**
     * Get quota features (numeric features that limit resources)
     *
     * @return array
     */
    public static function get_quota_features() {
        global $wpdb;

        return $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}wpt_feature_mappings
            WHERE is_quota = 1
            ORDER BY feature_name ASC"
        );
    }

    /**
     * Get boolean features (access control features)
     *
     * @return array
     */
    public static function get_boolean_features() {
        global $wpdb;

        return $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}wpt_feature_mappings
            WHERE feature_type = 'boolean'
            ORDER BY feature_name ASC"
        );
    }

    /**
     * Create or update feature mapping
     *
     * @param array $data
     * @return int|false Mapping ID or false on failure
     */
    public static function save_mapping($data) {
        global $wpdb;

        // Validate required fields
        if (empty($data['feature_key']) || empty($data['feature_name']) || empty($data['feature_type'])) {
            return false;
        }

        $mapping_data = array(
            'feature_key' => sanitize_key($data['feature_key']),
            'feature_name' => sanitize_text_field($data['feature_name']),
            'feature_type' => sanitize_text_field($data['feature_type']),
            'target_identifier' => !empty($data['target_identifier']) ? sanitize_text_field($data['target_identifier']) : null,
            'is_quota' => isset($data['is_quota']) ? 1 : 0,
            'description' => !empty($data['description']) ? sanitize_textarea_field($data['description']) : null,
            'updated_at' => current_time('mysql'),
        );

        // Check if mapping exists
        $existing = self::get_mapping($data['feature_key']);

        if ($existing) {
            // Update existing mapping
            $result = $wpdb->update(
                $wpdb->prefix . 'wpt_feature_mappings',
                $mapping_data,
                array('feature_key' => $data['feature_key']),
                array('%s', '%s', '%s', '%s', '%d', '%s', '%s'),
                array('%s')
            );

            return $result !== false ? $existing->id : false;
        } else {
            // Create new mapping
            $mapping_data['created_at'] = current_time('mysql');

            $result = $wpdb->insert(
                $wpdb->prefix . 'wpt_feature_mappings',
                $mapping_data,
                array('%s', '%s', '%s', '%s', '%d', '%s', '%s')
            );

            return $result !== false ? $wpdb->insert_id : false;
        }
    }

    /**
     * Delete feature mapping
     *
     * @param string $feature_key
     * @return bool
     */
    public static function delete_mapping($feature_key) {
        global $wpdb;

        $result = $wpdb->delete(
            $wpdb->prefix . 'wpt_feature_mappings',
            array('feature_key' => $feature_key),
            array('%s')
        );

        return $result !== false;
    }

    /**
     * Get feature value from plan with mapping context
     *
     * @param int $plan_id
     * @param string $feature_key
     * @return mixed
     */
    public static function get_plan_feature_value($plan_id, $feature_key) {
        $plan = WPT_Plan_Manager::get_plan($plan_id);

        if (!$plan || empty($plan->features)) {
            return null;
        }

        $features = json_decode($plan->features, true);
        return isset($features[$feature_key]) ? $features[$feature_key] : null;
    }

    /**
     * Get human-readable feature value
     *
     * @param string $feature_key
     * @param mixed $value
     * @return string
     */
    public static function format_feature_value($feature_key, $value) {
        $mapping = self::get_mapping($feature_key);

        if (!$mapping) {
            return $value;
        }

        switch ($mapping->feature_type) {
            case 'boolean':
                return $value ? __('Da', 'wpt-optica-core') : __('Nu', 'wpt-optica-core');

            case 'numeric':
            case 'post_type':
                if ($mapping->is_quota) {
                    return $value == 999 ? __('Nelimitat', 'wpt-optica-core') : intval($value);
                }
                return intval($value);

            case 'capability':
                return $value ? __('Activat', 'wpt-optica-core') : __('Dezactivat', 'wpt-optica-core');

            default:
                return $value;
        }
    }

    /**
     * Get feature icon based on type
     *
     * @param string $feature_type
     * @return string Dashicon class
     */
    public static function get_feature_icon($feature_type) {
        $icons = array(
            'post_type' => 'dashicons-admin-post',
            'taxonomy' => 'dashicons-tag',
            'capability' => 'dashicons-admin-users',
            'boolean' => 'dashicons-yes-alt',
            'numeric' => 'dashicons-chart-bar',
        );

        return isset($icons[$feature_type]) ? $icons[$feature_type] : 'dashicons-admin-generic';
    }
}
