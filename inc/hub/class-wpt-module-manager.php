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
        // Hook for module deactivation
        add_action('wpt_module_deactivated', array($this, 'handle_module_deactivation'), 10, 2);
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

    /**
     * Activate module for tenant
     *
     * @param int $tenant_id
     * @param int $module_id
     * @param string $activated_by 'admin' or 'tenant'
     * @return bool|WP_Error
     */
    public static function activate_tenant_module($tenant_id, $module_id, $activated_by = 'tenant') {
        global $wpdb;

        // Check if module exists and is active
        $module = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpt_available_modules WHERE id = %d AND is_active = 1",
            $module_id
        ));

        if (!$module) {
            return new WP_Error('module_not_found', __('Module not found or inactive.', 'wpt-optica-core'));
        }

        // Check if already activated
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpt_tenant_modules
            WHERE tenant_id = %d AND module_id = %d",
            $tenant_id,
            $module_id
        ));

        if ($existing) {
            if ($existing->status === 'active') {
                return true; // Already active
            }

            // Reactivate
            $result = $wpdb->update(
                $wpdb->prefix . 'wpt_tenant_modules',
                array(
                    'status' => 'active',
                    'activated_by' => $activated_by,
                    'activated_at' => current_time('mysql'),
                    'deactivated_by' => null,
                    'deactivated_at' => null,
                ),
                array('id' => $existing->id),
                array('%s', '%s', '%s', '%s', '%s'),
                array('%d')
            );

            return $result !== false;
        }

        // Insert new module activation
        $result = $wpdb->insert(
            $wpdb->prefix . 'wpt_tenant_modules',
            array(
                'tenant_id' => $tenant_id,
                'module_id' => $module_id,
                'status' => 'active',
                'activated_by' => $activated_by,
                'activated_at' => current_time('mysql'),
            ),
            array('%d', '%d', '%s', '%s', '%s')
        );

        return $result !== false;
    }

    /**
     * Deactivate module for tenant
     *
     * @param int $tenant_id
     * @param int|string $module_identifier Module ID or slug
     * @param string $deactivated_by 'admin' or 'tenant'
     * @return bool|WP_Error
     */
    public static function deactivate_tenant_module($tenant_id, $module_identifier, $deactivated_by = 'tenant') {
        global $wpdb;

        // Get module ID if slug provided
        if (!is_numeric($module_identifier)) {
            $module = $wpdb->get_row($wpdb->prepare(
                "SELECT id, slug FROM {$wpdb->prefix}wpt_available_modules WHERE slug = %s",
                $module_identifier
            ));
            if (!$module) {
                return new WP_Error('module_not_found', __('Module not found.', 'wpt-optica-core'));
            }
            $module_id = $module->id;
            $module_slug = $module->slug;
        } else {
            $module = $wpdb->get_row($wpdb->prepare(
                "SELECT id, slug FROM {$wpdb->prefix}wpt_available_modules WHERE id = %d",
                $module_identifier
            ));
            if (!$module) {
                return new WP_Error('module_not_found', __('Module not found.', 'wpt-optica-core'));
            }
            $module_id = $module->id;
            $module_slug = $module->slug;
        }

        // Deactivate module
        $result = $wpdb->update(
            $wpdb->prefix . 'wpt_tenant_modules',
            array(
                'status' => 'inactive',
                'deactivated_by' => $deactivated_by,
                'deactivated_at' => current_time('mysql'),
            ),
            array(
                'tenant_id' => $tenant_id,
                'module_id' => $module_id,
            ),
            array('%s', '%s', '%s'),
            array('%d', '%d')
        );

        if ($result !== false) {
            // Trigger action hook for dependent addons
            do_action('wpt_module_deactivated', $tenant_id, $module_slug);
            return true;
        }

        return false;
    }

    /**
     * Handle module deactivation - auto-deactivate dependent addons
     *
     * @param int $tenant_id
     * @param string $module_slug
     */
    public function handle_module_deactivation($tenant_id, $module_slug) {
        // Get addons that require this module
        $dependent_addons = WPT_Addon_Manager::get_addons_requiring_module($module_slug);

        if (empty($dependent_addons)) {
            return;
        }

        // Deactivate each dependent addon for this tenant
        foreach ($dependent_addons as $addon) {
            $result = WPT_Addon_Manager::deactivate_tenant_addon($tenant_id, $addon->addon_slug);

            // Log the auto-deactivation
            if ($result) {
                WPT_Logger::log(
                    'module_dependency',
                    sprintf(
                        'Auto-deactivated addon "%s" for tenant %d because required module "%s" was deactivated',
                        $addon->addon_name,
                        $tenant_id,
                        $module_slug
                    ),
                    array(
                        'tenant_id' => $tenant_id,
                        'addon_slug' => $addon->addon_slug,
                        'module_slug' => $module_slug,
                    )
                );
            }
        }
    }
}

new WPT_Module_Manager();
