<?php
/**
 * Tenant Add-on AJAX Handlers
 * Handle AJAX requests for managing tenant add-ons
 *
 * @package WPT_Optica_Core
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPT_Tenant_Addon_Ajax {

    /**
     * Initialize AJAX handlers
     */
    public static function init() {
        // Add tenant addon
        add_action('wp_ajax_wpt_add_tenant_addon', array(__CLASS__, 'add_tenant_addon'));

        // Update addon quantity
        add_action('wp_ajax_wpt_update_addon_quantity', array(__CLASS__, 'update_addon_quantity'));

        // Deactivate addon
        add_action('wp_ajax_wpt_deactivate_tenant_addon', array(__CLASS__, 'deactivate_tenant_addon'));
    }

    /**
     * Add addon to tenant
     */
    public static function add_tenant_addon() {
        // Check nonce
        check_ajax_referer('wpt_manage_tenant_addons', 'nonce');

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permisiuni insuficiente.', 'wpt-optica-core')));
        }

        $tenant_id = isset($_POST['tenant_id']) ? intval($_POST['tenant_id']) : 0;
        $addon_key = isset($_POST['addon_key']) ? sanitize_text_field($_POST['addon_key']) : '';
        $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

        // Validate
        if (!$tenant_id || !$addon_key || $quantity < 1) {
            wp_send_json_error(array('message' => __('Date invalide.', 'wpt-optica-core')));
        }

        // Check if tenant exists
        $tenant = WPT_Tenant_Manager::get_tenant($tenant_id);
        if (!$tenant) {
            wp_send_json_error(array('message' => __('Tenant nu există.', 'wpt-optica-core')));
        }

        // Check if addon exists
        $addon = WPT_Addon_Manager::get_addon($addon_key);
        if (!$addon) {
            wp_send_json_error(array('message' => __('Add-on nu există.', 'wpt-optica-core')));
        }

        // Check if addon is already active for this tenant
        global $wpdb;
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpt_tenant_addons
            WHERE tenant_id = %d AND addon_key = %s",
            $tenant_id,
            $addon_key
        ));

        if ($existing) {
            wp_send_json_error(array('message' => __('Add-on deja activ pentru acest tenant.', 'wpt-optica-core')));
        }

        // Insert addon
        $result = $wpdb->insert(
            $wpdb->prefix . 'wpt_tenant_addons',
            array(
                'tenant_id' => $tenant_id,
                'addon_key' => $addon_key,
                'quantity' => $quantity,
                'status' => 'active',
                'activated_at' => current_time('mysql'),
            ),
            array('%d', '%s', '%d', '%s', '%s')
        );

        if ($result) {
            wp_send_json_success(array(
                'message' => __('Add-on activat cu succes!', 'wpt-optica-core'),
                'addon_id' => $wpdb->insert_id,
            ));
        } else {
            wp_send_json_error(array('message' => __('Eroare la activarea add-on-ului.', 'wpt-optica-core')));
        }
    }

    /**
     * Update addon quantity
     */
    public static function update_addon_quantity() {
        // Check nonce
        check_ajax_referer('wpt_manage_tenant_addons', 'nonce');

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permisiuni insuficiente.', 'wpt-optica-core')));
        }

        $addon_id = isset($_POST['addon_id']) ? intval($_POST['addon_id']) : 0;
        $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

        // Validate
        if (!$addon_id || $quantity < 1) {
            wp_send_json_error(array('message' => __('Date invalide.', 'wpt-optica-core')));
        }

        // Update quantity
        global $wpdb;
        $result = $wpdb->update(
            $wpdb->prefix . 'wpt_tenant_addons',
            array('quantity' => $quantity),
            array('id' => $addon_id),
            array('%d'),
            array('%d')
        );

        if ($result !== false) {
            wp_send_json_success(array(
                'message' => __('Cantitate actualizată cu succes!', 'wpt-optica-core'),
            ));
        } else {
            wp_send_json_error(array('message' => __('Eroare la actualizarea cantității.', 'wpt-optica-core')));
        }
    }

    /**
     * Deactivate addon
     */
    public static function deactivate_tenant_addon() {
        // Check nonce
        check_ajax_referer('wpt_manage_tenant_addons', 'nonce');

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permisiuni insuficiente.', 'wpt-optica-core')));
        }

        $addon_id = isset($_POST['addon_id']) ? intval($_POST['addon_id']) : 0;

        // Validate
        if (!$addon_id) {
            wp_send_json_error(array('message' => __('Date invalide.', 'wpt-optica-core')));
        }

        // Deactivate addon (soft delete by setting status to inactive)
        global $wpdb;
        $result = $wpdb->update(
            $wpdb->prefix . 'wpt_tenant_addons',
            array('status' => 'inactive'),
            array('id' => $addon_id),
            array('%s'),
            array('%d')
        );

        if ($result !== false) {
            wp_send_json_success(array(
                'message' => __('Add-on dezactivat cu succes!', 'wpt-optica-core'),
            ));
        } else {
            wp_send_json_error(array('message' => __('Eroare la dezactivarea add-on-ului.', 'wpt-optica-core')));
        }
    }
}

// Initialize AJAX handlers
WPT_Tenant_Addon_Ajax::init();
