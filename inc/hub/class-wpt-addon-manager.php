<?php
/**
 * Addon Manager (HUB)
 * Manages addon pricing and tenant addon subscriptions
 *
 * @package WPT_Optica_Core
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPT_Addon_Manager {

    public function __construct() {
        // Hook pentru admin features
    }

    /**
     * Get all addon prices
     *
     * @param bool $active_only Return only active addons
     * @return array
     */
    public static function get_addon_prices($active_only = false) {
        global $wpdb;

        $where = $active_only ? 'WHERE is_active = 1' : '';

        return $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}wpt_addon_prices
            {$where}
            ORDER BY monthly_price ASC"
        );
    }

    /**
     * Alias for get_addon_prices() - returns all addons
     *
     * @return array
     */
    public static function get_addons() {
        return self::get_addon_prices(true); // Only active addons
    }

    /**
     * Get addon by slug or ID
     *
     * @param string|int $identifier Addon slug or ID
     * @return object|null
     */
    public static function get_addon($identifier) {
        global $wpdb;

        // If numeric, search by ID
        if (is_numeric($identifier)) {
            return $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}wpt_addon_prices WHERE id = %d",
                $identifier
            ));
        }

        // Otherwise search by slug
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpt_addon_prices WHERE addon_slug = %s",
            $identifier
        ));
    }

    /**
     * Get addon price by slug
     *
     * @param string $slug
     * @return object|null
     */
    public static function get_addon_by_slug($slug) {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpt_addon_prices WHERE addon_slug = %s",
            $slug
        ));
    }

    /**
     * Get tenant active addons
     *
     * @param int $tenant_id
     * @return array
     */
    public static function get_tenant_addons($tenant_id) {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT ta.*, ap.addon_name, ap.description
            FROM {$wpdb->prefix}wpt_tenant_addons ta
            LEFT JOIN {$wpdb->prefix}wpt_addon_prices ap ON ta.addon_slug = ap.addon_slug
            WHERE ta.tenant_id = %d AND ta.status = 'active'
            ORDER BY ta.activated_at DESC",
            $tenant_id
        ));
    }

    /**
     * Get tenants count for specific addon
     *
     * @param string $addon_slug
     * @return int
     */
    public static function get_addon_tenants_count($addon_slug) {
        global $wpdb;

        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT tenant_id) FROM {$wpdb->prefix}wpt_tenant_addons
            WHERE addon_slug = %s AND status = 'active'",
            $addon_slug
        ));
    }

    /**
     * Get monthly revenue for addon
     *
     * @param string $addon_slug
     * @return float
     */
    public static function get_addon_revenue($addon_slug) {
        global $wpdb;

        $addon = self::get_addon_by_slug($addon_slug);
        if (!$addon) {
            return 0;
        }

        // For quantity-based addons (extra-offers, extra-jobs, premium-location)
        if (in_array($addon_slug, array('extra-offers', 'extra-jobs', 'premium-location'))) {
            // Count total quantity across all tenants
            $total_quantity = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}wpt_tenant_addons
                WHERE addon_slug = %s AND status = 'active'",
                $addon_slug
            ));

            return $addon->monthly_price * $total_quantity;
        }

        // For flat-rate addons (tenant-site)
        $tenant_count = self::get_addon_tenants_count($addon_slug);
        return $addon->monthly_price * $tenant_count;
    }

    /**
     * Check if tenant has required modules active for an addon
     *
     * @param int $tenant_id
     * @param string $addon_slug
     * @return array Array with 'valid' (bool) and 'missing_modules' (array)
     */
    public static function check_addon_module_requirements($tenant_id, $addon_slug) {
        global $wpdb;

        // Get addon details
        $addon = self::get_addon_by_slug($addon_slug);
        if (!$addon) {
            return array(
                'valid' => false,
                'missing_modules' => array(),
                'error' => 'Addon not found',
            );
        }

        // If no required modules, addon can be activated
        if (empty($addon->required_modules)) {
            return array(
                'valid' => true,
                'missing_modules' => array(),
            );
        }

        // Decode required modules
        $required_module_slugs = json_decode($addon->required_modules, true);
        if (!is_array($required_module_slugs) || empty($required_module_slugs)) {
            return array(
                'valid' => true,
                'missing_modules' => array(),
            );
        }

        // Get tenant's active modules
        $active_modules = $wpdb->get_col($wpdb->prepare(
            "SELECT m.slug
            FROM {$wpdb->prefix}wpt_tenant_modules tm
            JOIN {$wpdb->prefix}wpt_available_modules m ON tm.module_id = m.id
            WHERE tm.tenant_id = %d AND tm.status = 'active'",
            $tenant_id
        ));

        // Check which required modules are missing
        $missing_modules = array_diff($required_module_slugs, $active_modules);

        return array(
            'valid' => empty($missing_modules),
            'missing_modules' => array_values($missing_modules),
            'required_modules' => $required_module_slugs,
            'active_modules' => $active_modules,
        );
    }

    /**
     * Get addons that depend on a specific module
     *
     * @param string $module_slug
     * @return array Array of addon objects that require this module
     */
    public static function get_addons_requiring_module($module_slug) {
        global $wpdb;

        $addons = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}wpt_addon_prices
            WHERE required_modules IS NOT NULL
            AND is_active = 1"
        );

        $dependent_addons = array();
        foreach ($addons as $addon) {
            $required_modules = json_decode($addon->required_modules, true);
            if (is_array($required_modules) && in_array($module_slug, $required_modules)) {
                $dependent_addons[] = $addon;
            }
        }

        return $dependent_addons;
    }

    /**
     * Activate addon for tenant
     *
     * @param int $tenant_id
     * @param string $addon_slug
     * @param float $price Optional - override default price
     * @return bool|WP_Error
     */
    public static function activate_tenant_addon($tenant_id, $addon_slug, $price = null) {
        global $wpdb;

        // Get addon details
        $addon = self::get_addon_by_slug($addon_slug);
        if (!$addon) {
            return new WP_Error('addon_not_found', __('Addon not found.', 'wpt-optica-core'));
        }

        // Check module requirements
        $requirements = self::check_addon_module_requirements($tenant_id, $addon_slug);
        if (!$requirements['valid']) {
            $missing_names = array();
            foreach ($requirements['missing_modules'] as $module_slug) {
                $module = $wpdb->get_row($wpdb->prepare(
                    "SELECT title FROM {$wpdb->prefix}wpt_available_modules WHERE slug = %s",
                    $module_slug
                ));
                $missing_names[] = $module ? $module->title : $module_slug;
            }

            return new WP_Error(
                'missing_required_modules',
                sprintf(
                    __('Cannot activate addon. Required modules must be active first: %s', 'wpt-optica-core'),
                    implode(', ', $missing_names)
                ),
                array('missing_modules' => $requirements['missing_modules'])
            );
        }

        // Use provided price or default
        $addon_price = $price !== null ? floatval($price) : $addon->monthly_price;

        // Check if already active
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpt_tenant_addons
            WHERE tenant_id = %d AND addon_slug = %s",
            $tenant_id,
            $addon_slug
        ));

        if ($existing) {
            // Reactivate if suspended
            if ($existing->status === 'suspended') {
                return $wpdb->update(
                    $wpdb->prefix . 'wpt_tenant_addons',
                    array(
                        'status' => 'active',
                        'addon_price' => $addon_price,
                    ),
                    array('id' => $existing->id),
                    array('%s', '%f'),
                    array('%d')
                ) !== false;
            }

            return true; // Already active
        }

        // Insert new addon
        $result = $wpdb->insert(
            $wpdb->prefix . 'wpt_tenant_addons',
            array(
                'tenant_id' => $tenant_id,
                'addon_slug' => $addon_slug,
                'addon_price' => $addon_price,
                'status' => 'active',
                'activated_at' => current_time('mysql'),
            ),
            array('%d', '%s', '%f', '%s', '%s')
        );

        if ($result === false) {
            return new WP_Error('activation_failed', __('Failed to activate addon.', 'wpt-optica-core'));
        }

        return true;
    }

    /**
     * Deactivate addon for tenant
     *
     * @param int $tenant_id
     * @param string $addon_slug
     * @return bool
     */
    public static function deactivate_tenant_addon($tenant_id, $addon_slug) {
        global $wpdb;

        $result = $wpdb->update(
            $wpdb->prefix . 'wpt_tenant_addons',
            array('status' => 'suspended'),
            array(
                'tenant_id' => $tenant_id,
                'addon_slug' => $addon_slug,
            ),
            array('%s'),
            array('%d', '%s')
        );

        return $result !== false;
    }

    /**
     * Get tenant monthly addon cost
     *
     * @param int $tenant_id
     * @return float
     */
    public static function get_tenant_addon_cost($tenant_id) {
        global $wpdb;

        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(addon_price) FROM {$wpdb->prefix}wpt_tenant_addons
            WHERE tenant_id = %d AND status = 'active'",
            $tenant_id
        ));

        return floatval($total);
    }

    /**
     * Update addon price
     *
     * @param int $addon_id
     * @param array $data
     * @return bool
     */
    public static function update_addon($addon_id, $data) {
        global $wpdb;

        // Validate required fields
        if (empty($data['addon_name']) || empty($data['addon_slug'])) {
            return false;
        }

        $update_data = array(
            'addon_name' => sanitize_text_field($data['addon_name']),
            'addon_slug' => sanitize_title($data['addon_slug']),
            'monthly_price' => floatval($data['monthly_price']),
            'description' => wp_kses_post($data['description']),
            'is_active' => isset($data['is_active']) ? 1 : 0,
            'updated_at' => current_time('mysql'),
        );

        // Handle required_modules
        if (isset($data['required_modules'])) {
            if (is_array($data['required_modules'])) {
                $update_data['required_modules'] = json_encode($data['required_modules']);
            } elseif (is_string($data['required_modules'])) {
                $update_data['required_modules'] = $data['required_modules'];
            } else {
                $update_data['required_modules'] = null;
            }
        }

        $formats = array('%s', '%s', '%f', '%s', '%d', '%s');
        if (isset($update_data['required_modules'])) {
            $formats[] = '%s';
        }

        $result = $wpdb->update(
            $wpdb->prefix . 'wpt_addon_prices',
            $update_data,
            array('id' => $addon_id),
            $formats,
            array('%d')
        );

        return $result !== false;
    }

    /**
     * Create new addon
     *
     * @param array $data
     * @return int|false Addon ID or false on failure
     */
    public static function create_addon($data) {
        global $wpdb;

        // Validate required fields
        if (empty($data['addon_name']) || empty($data['addon_slug'])) {
            return false;
        }

        $insert_data = array(
            'addon_name' => sanitize_text_field($data['addon_name']),
            'addon_slug' => sanitize_title($data['addon_slug']),
            'monthly_price' => floatval($data['monthly_price'] ?? 0),
            'description' => wp_kses_post($data['description'] ?? ''),
            'is_active' => isset($data['is_active']) ? 1 : 0,
            'created_at' => current_time('mysql'),
        );

        // Handle required_modules
        if (isset($data['required_modules'])) {
            if (is_array($data['required_modules'])) {
                $insert_data['required_modules'] = json_encode($data['required_modules']);
            } elseif (is_string($data['required_modules'])) {
                $insert_data['required_modules'] = $data['required_modules'];
            }
        }

        $formats = array('%s', '%s', '%f', '%s', '%d', '%s');
        if (isset($insert_data['required_modules'])) {
            $formats[] = '%s';
        }

        $result = $wpdb->insert(
            $wpdb->prefix . 'wpt_addon_prices',
            $insert_data,
            $formats
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Delete addon (only if no active tenants)
     *
     * @param int $addon_id
     * @return bool|WP_Error
     */
    public static function delete_addon($addon_id) {
        global $wpdb;

        $addon = self::get_addon($addon_id);
        if (!$addon) {
            return false;
        }

        // Check if addon has active tenants
        $tenant_count = self::get_addon_tenants_count($addon->addon_slug);

        if ($tenant_count > 0) {
            return new WP_Error(
                'addon_has_tenants',
                sprintf(__('Cannot delete addon. %d tenants are using this addon.', 'wpt-optica-core'), $tenant_count)
            );
        }

        $result = $wpdb->delete(
            $wpdb->prefix . 'wpt_addon_prices',
            array('id' => $addon_id),
            array('%d')
        );

        return $result !== false;
    }
}

new WPT_Addon_Manager();
