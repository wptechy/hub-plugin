<?php
/**
 * Roles & Permissions System
 * Manages custom WordPress roles and capabilities
 *
 * @package WPT_Optica_Core
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPT_Roles {

    public function __construct() {
        add_action('init', array($this, 'register_custom_capabilities'));
    }

    /**
     * Register custom roles and capabilities
     * Called on plugin activation
     */
    public static function register_custom_roles() {
        if (WPT_IS_HUB) {
            self::register_hub_roles();
        } else {
            self::register_site_roles();
        }
    }

    /**
     * Register HUB-specific roles
     */
    private static function register_hub_roles() {
        // Role: optica (Optical Shop Owner)
        add_role('optica', __('Optica', 'wpt-optica-core'), array(
            'read' => true,
            'edit_posts' => true,
            'delete_posts' => true,
            'publish_posts' => true,
            'upload_files' => true,
            'edit_published_posts' => true,
            'delete_published_posts' => true,
            'edit_brands' => true,
            'edit_published_brands' => true,
            'publish_brands' => true,
            'delete_brands' => true,
            'edit_locatii' => true,
            'edit_published_locatii' => true,
            'publish_locatii' => true,
            'delete_locatii' => true,
            'edit_oferte' => true,
            'edit_published_oferte' => true,
            'publish_oferte' => true,
            'delete_oferte' => true,
            'view_wpt_transactions' => true, // Custom capability
        ));

        // Role: medic (Doctor)
        add_role('medic', __('Medic', 'wpt-optica-core'), array(
            'read' => true,
            'edit_posts' => true,
            'delete_posts' => true,
            'edit_medici' => true,
            'edit_published_medici' => true,
            'edit_program_medici' => true,
            'edit_published_program_medici' => true,
        ));

        // Role: furnizor (Supplier)
        add_role('furnizor', __('Furnizor', 'wpt-optica-core'), array(
            'read' => true,
            'edit_posts' => true,
            'delete_posts' => true,
            'edit_produse' => true,
            'edit_published_produse' => true,
            'publish_produse' => true,
            'delete_produse' => true,
        ));

        // Role: candidat (Job Applicant)
        add_role('candidat', __('Candidat', 'wpt-optica-core'), array(
            'read' => true,
            'edit_candidati' => true,
            'edit_published_candidati' => true,
        ));
    }

    /**
     * Register Site Optica-specific roles
     */
    private static function register_site_roles() {
        // Role: website_manager (Site Administrator)
        add_role('website_manager', __('Website Manager', 'wpt-optica-core'), array(
            'read' => true,
            'edit_posts' => true,
            'delete_posts' => true,
            'publish_posts' => true,
            'upload_files' => true,
            'edit_published_posts' => true,
            'delete_published_posts' => true,
            'edit_pages' => true,
            'publish_pages' => true,
            'edit_published_pages' => true,
            'delete_pages' => true,
            'delete_published_pages' => true,
            'edit_brands' => true,
            'edit_published_brands' => true,
            'publish_brands' => true,
            'edit_locatii' => true,
            'edit_published_locatii' => true,
            'publish_locatii' => true,
            'edit_oferte' => true,
            'edit_published_oferte' => true,
            'publish_oferte' => true,
            'edit_jobs' => true,
            'edit_published_jobs' => true,
            'publish_jobs' => true,
            'edit_medici' => true,
            'edit_published_medici' => true,
            'publish_medici' => true,
            'manage_website_config' => true, // Custom capability
            'purchase_modules' => true, // Custom capability
            'view_wpt_logs' => true, // Custom capability
            'view_wpt_transactions' => true, // Custom capability
        ));

        // Role: shop_manager (WooCommerce shop manager)
        // We enhance the existing shop_manager role with our custom capabilities
        $shop_manager = get_role('shop_manager');
        if ($shop_manager) {
            $shop_manager->add_cap('manage_website_config');
            $shop_manager->add_cap('purchase_modules');
            $shop_manager->add_cap('view_wpt_logs');
            $shop_manager->add_cap('view_wpt_transactions');
        }

        // Role: editor (Employee - Content Manager)
        // WordPress 'editor' role already exists, we just add custom capabilities
        $editor = get_role('editor');
        if ($editor) {
            $editor->add_cap('edit_brands');
            $editor->add_cap('edit_published_brands');
            $editor->add_cap('edit_locatii');
            $editor->add_cap('edit_published_locatii');
            $editor->add_cap('edit_oferte');
            $editor->add_cap('edit_published_oferte');
            $editor->add_cap('edit_jobs');
            $editor->add_cap('edit_published_jobs');
            $editor->add_cap('edit_medici');
            $editor->add_cap('edit_published_medici');
            // NOTE: Editor does NOT have 'purchase_modules' by default
            // Admin can grant it per-user via _wpt_can_purchase meta
        }
    }

    /**
     * Add custom capabilities to administrator role
     */
    public function register_custom_capabilities() {
        $admin = get_role('administrator');
        if ($admin) {
            $admin->add_cap('manage_website_config');
            $admin->add_cap('purchase_modules');
            $admin->add_cap('view_wpt_logs');
            $admin->add_cap('view_wpt_transactions');
        }
    }

    /**
     * Check if user can purchase modules
     * Checks both role capability AND per-user meta override
     */
    public static function user_can_purchase($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        if (!$user_id) {
            return false;
        }

        // Check role-based capability
        if (user_can($user_id, 'purchase_modules')) {
            return true;
        }

        // Check per-user meta override
        $can_purchase = get_user_meta($user_id, '_wpt_can_purchase', true);
        return (bool) $can_purchase;
    }

    /**
     * Grant purchase capability to specific user
     */
    public static function grant_purchase_capability($user_id) {
        update_user_meta($user_id, '_wpt_can_purchase', 1);
    }

    /**
     * Revoke purchase capability from specific user
     */
    public static function revoke_purchase_capability($user_id) {
        delete_user_meta($user_id, '_wpt_can_purchase');
    }

    /**
     * Remove custom roles on plugin deactivation
     */
    public static function remove_custom_roles() {
        if (WPT_IS_HUB) {
            remove_role('optica');
            remove_role('medic');
            remove_role('furnizor');
            remove_role('candidat');
        } else {
            remove_role('website_manager');

            // Remove custom capabilities from existing roles
            $editor = get_role('editor');
            if ($editor) {
                $editor->remove_cap('edit_brands');
                $editor->remove_cap('edit_published_brands');
                $editor->remove_cap('edit_locatii');
                $editor->remove_cap('edit_published_locatii');
                $editor->remove_cap('edit_oferte');
                $editor->remove_cap('edit_published_oferte');
                $editor->remove_cap('edit_jobs');
                $editor->remove_cap('edit_published_jobs');
                $editor->remove_cap('edit_medici');
                $editor->remove_cap('edit_published_medici');
            }

            $shop_manager = get_role('shop_manager');
            if ($shop_manager) {
                $shop_manager->remove_cap('manage_website_config');
                $shop_manager->remove_cap('purchase_modules');
                $shop_manager->remove_cap('view_wpt_logs');
                $shop_manager->remove_cap('view_wpt_transactions');
            }
        }

        // Remove from administrator
        $admin = get_role('administrator');
        if ($admin) {
            $admin->remove_cap('manage_website_config');
            $admin->remove_cap('purchase_modules');
            $admin->remove_cap('view_wpt_logs');
            $admin->remove_cap('view_wpt_transactions');
        }
    }
}

new WPT_Roles();
