<?php
/**
 * HUB Admin Menus
 * Creates admin menu structure for HUB platform management
 *
 * @package WPT_Optica_Core
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPT_Admin_Menus {

    public function __construct() {
        add_action('admin_menu', array($this, 'register_menus'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_ajax_wpt_get_user_brands', array($this, 'ajax_get_user_brands'));
    }

    /**
     * Register admin menus
     */
    public function register_menus() {
        // Main menu
        add_menu_page(
            __('WPT Platform', 'wpt-optica-core'),
            __('WPT Platform', 'wpt-optica-core'),
            'manage_website_config',
            'wpt-platform',
            array($this, 'render_dashboard'),
            'dashicons-networking',
            3
        );

        // Dashboard (rename main menu item)
        add_submenu_page(
            'wpt-platform',
            __('Dashboard', 'wpt-optica-core'),
            __('Dashboard', 'wpt-optica-core'),
            'manage_website_config',
            'wpt-platform',
            array($this, 'render_dashboard')
        );

        // Tenants Management
        add_submenu_page(
            'wpt-platform',
            __('Tenants', 'wpt-optica-core'),
            __('Tenants', 'wpt-optica-core'),
            'manage_website_config',
            'wpt-tenants',
            array($this, 'render_tenants')
        );

        // Modules Marketplace
        add_submenu_page(
            'wpt-platform',
            __('Modules', 'wpt-optica-core'),
            __('Modules', 'wpt-optica-core'),
            'manage_website_config',
            'wpt-modules',
            array($this, 'render_modules')
        );

        // Releases Manager
        add_submenu_page(
            'wpt-platform',
            __('Releases', 'wpt-optica-core'),
            __('Releases', 'wpt-optica-core'),
            'manage_website_config',
            'wpt-releases',
            array($this, 'render_releases')
        );

        // Analytics
        add_submenu_page(
            'wpt-platform',
            __('Analytics', 'wpt-optica-core'),
            __('Analytics', 'wpt-optica-core'),
            'manage_website_config',
            'wpt-analytics',
            array($this, 'render_analytics')
        );

        // Settings
        add_submenu_page(
            'wpt-platform',
            __('Settings', 'wpt-optica-core'),
            __('Settings', 'wpt-optica-core'),
            'manage_website_config',
            'wpt-settings',
            array($this, 'render_settings')
        );
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only load on WPT pages
        if (strpos($hook, 'wpt-') === false) {
            return;
        }

        wp_enqueue_style(
            'wpt-admin',
            WPT_CORE_URL . 'assets/css/admin.css',
            array(),
            WPT_CORE_VERSION
        );

        wp_enqueue_script(
            'wpt-admin',
            WPT_CORE_URL . 'assets/js/admin.js',
            array('jquery'),
            WPT_CORE_VERSION,
            true
        );

        wp_localize_script('wpt-admin', 'wptAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpt_admin_nonce'),
            'i18n' => array(
                'confirmDelete' => __('Are you sure you want to delete this?', 'wpt-optica-core'),
                'saved' => __('Saved successfully', 'wpt-optica-core'),
                'error' => __('An error occurred', 'wpt-optica-core'),
            ),
        ));
    }

    /**
     * Render Dashboard page
     */
    public function render_dashboard() {
        require_once WPT_CORE_DIR . 'inc/hub/admin/views/dashboard.php';
    }

    /**
     * Render Tenants page
     */
    public function render_tenants() {
        require_once WPT_CORE_DIR . 'inc/hub/admin/views/tenants.php';
    }

    /**
     * Render Modules page
     */
    public function render_modules() {
        require_once WPT_CORE_DIR . 'inc/hub/admin/views/modules.php';
    }

    /**
     * Render Releases page
     */
    public function render_releases() {
        require_once WPT_CORE_DIR . 'inc/hub/admin/views/releases.php';
    }

    /**
     * Render Analytics page
     */
    public function render_analytics() {
        require_once WPT_CORE_DIR . 'inc/hub/admin/views/analytics.php';
    }

    /**
     * Render Settings page
     */
    public function render_settings() {
        require_once WPT_CORE_DIR . 'inc/hub/admin/views/settings.php';
    }

    /**
     * AJAX handler to get brands for a specific user
     */
    public function ajax_get_user_brands() {
        check_ajax_referer('wpt_get_user_brands', 'nonce');

        if (!current_user_can('manage_website_config')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'wpt-optica-core')));
        }

        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;

        if (!$user_id) {
            wp_send_json_error(array('message' => __('Invalid user ID', 'wpt-optica-core')));
        }

        $brands = get_posts(array(
            'post_type' => 'brand',
            'posts_per_page' => -1,
            'author' => $user_id,
            'orderby' => 'title',
            'order' => 'ASC',
            'post_status' => 'publish'
        ));

        $brands_data = array();
        foreach ($brands as $brand) {
            $brands_data[] = array(
                'id' => $brand->ID,
                'title' => $brand->post_title
            );
        }

        wp_send_json_success(array('brands' => $brands_data));
    }
}

new WPT_Admin_Menus();
