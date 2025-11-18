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
            __('Platforma WPT', 'wpt-optica-core'),
            __('Platforma WPT', 'wpt-optica-core'),
            'manage_website_config',
            'wpt-platform',
            array($this, 'render_dashboard'),
            'dashicons-networking',
            3
        );

        // Dashboard (rename main menu item)
        add_submenu_page(
            'wpt-platform',
            __('Tablou de bord', 'wpt-optica-core'),
            __('Tablou de bord', 'wpt-optica-core'),
            'manage_website_config',
            'wpt-platform',
            array($this, 'render_dashboard')
        );

        // Tenants Management
        add_submenu_page(
            'wpt-platform',
            __('Tenanți', 'wpt-optica-core'),
            __('Tenanți', 'wpt-optica-core'),
            'manage_website_config',
            'wpt-tenants',
            array($this, 'render_tenants')
        );

        // Modules Marketplace
        add_submenu_page(
            'wpt-platform',
            __('Module', 'wpt-optica-core'),
            __('Module', 'wpt-optica-core'),
            'manage_website_config',
            'wpt-modules',
            array($this, 'render_modules')
        );

        // Plans & Pricing
        add_submenu_page(
            'wpt-platform',
            __('Planuri și tarife', 'wpt-optica-core'),
            __('Planuri și tarife', 'wpt-optica-core'),
            'manage_website_config',
            'wpt-plans',
            array($this, 'render_plans')
        );

        // Releases Manager
        add_submenu_page(
            'wpt-platform',
            __('Versiuni', 'wpt-optica-core'),
            __('Versiuni', 'wpt-optica-core'),
            'manage_website_config',
            'wpt-releases',
            array($this, 'render_releases')
        );

        // Analytics
        add_submenu_page(
            'wpt-platform',
            __('Statistici', 'wpt-optica-core'),
            __('Statistici', 'wpt-optica-core'),
            'manage_website_config',
            'wpt-analytics',
            array($this, 'render_analytics')
        );

        // Settings
        add_submenu_page(
            'wpt-platform',
            __('Setări', 'wpt-optica-core'),
            __('Setări', 'wpt-optica-core'),
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
            WPT_HUB_URL . 'assets/css/admin.css',
            array(),
            WPT_HUB_VERSION
        );

        wp_enqueue_script(
            'wpt-admin',
            WPT_HUB_URL . 'assets/js/admin.js',
            array('jquery'),
            WPT_HUB_VERSION,
            true
        );

        wp_localize_script('wpt-admin', 'wptAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpt_admin_nonce'),
            'i18n' => array(
                'confirmDelete' => __('Sigur vrei să ștergi acest element?', 'wpt-optica-core'),
                'saved' => __('Salvat cu succes', 'wpt-optica-core'),
                'error' => __('A apărut o eroare', 'wpt-optica-core'),
            ),
        ));
    }

    /**
     * Render Dashboard page
     */
    public function render_dashboard() {
        require_once WPT_HUB_DIR . 'inc/hub/admin/views/dashboard.php';
    }

    /**
     * Render Tenants page
     */
    public function render_tenants() {
        require_once WPT_HUB_DIR . 'inc/hub/admin/views/tenants.php';
    }

    /**
     * Render Modules page
     */
    public function render_modules() {
        require_once WPT_HUB_DIR . 'inc/hub/admin/views/modules.php';
    }

    /**
     * Render Plans & Pricing page
     */
    public function render_plans() {
        require_once WPT_HUB_DIR . 'inc/hub/admin/views/plans.php';
    }

    /**
     * Render Releases page
     */
    public function render_releases() {
        require_once WPT_HUB_DIR . 'inc/hub/admin/views/releases.php';
    }

    /**
     * Render Analytics page
     */
    public function render_analytics() {
        require_once WPT_HUB_DIR . 'inc/hub/admin/views/analytics.php';
    }

    /**
     * Render Settings page
     */
    public function render_settings() {
        require_once WPT_HUB_DIR . 'inc/hub/admin/views/settings.php';
    }

    /**
     * AJAX handler to get brands for a specific user
     */
    public function ajax_get_user_brands() {
        check_ajax_referer('wpt_get_user_brands', 'nonce');

        if (!current_user_can('manage_website_config')) {
            wp_send_json_error(array('message' => __('Permisiuni insuficiente', 'wpt-optica-core')));
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
