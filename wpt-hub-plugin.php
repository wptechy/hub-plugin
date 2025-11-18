<?php
/**
 * Plugin Name: WPT Hub Plugin
 * Plugin URI: https://opticamedicala.ro
 * Description: Plugin HUB pentru OpticaMedicala.ro - gestionare tenants, API server, module management
 * Version: 1.0.0
 * Author: WPT Team
 * Author URI: https://opticamedicala.ro
 * Text Domain: wpt-hub-plugin
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Plugin version and paths
define('WPT_HUB_VERSION', '1.0.0');
define('WPT_HUB_FILE', __FILE__);
define('WPT_HUB_DIR', plugin_dir_path(__FILE__));
define('WPT_HUB_URL', plugin_dir_url(__FILE__));
define('WPT_HUB_BASENAME', plugin_basename(__FILE__));

// Legacy constants for backward compatibility
define('WPT_CORE_VERSION', WPT_HUB_VERSION);
define('WPT_CORE_FILE', WPT_HUB_FILE);
define('WPT_CORE_DIR', WPT_HUB_DIR);
define('WPT_CORE_URL', WPT_HUB_URL);
define('WPT_CORE_BASENAME', WPT_HUB_BASENAME);
define('WPT_VERSION', WPT_HUB_VERSION);
define('WPT_PLUGIN_DIR', WPT_HUB_DIR);
define('WPT_PLUGIN_URL', WPT_HUB_URL);

// This is ALWAYS the HUB
define('WPT_IS_HUB', true);

/**
 * Main Hub Plugin Class
 */
final class WPT_Hub_Plugin {

    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * Get singleton instance
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor - initialize plugin
     */
    private function __construct() {
        $this->load_dependencies();
        $this->define_hooks();
    }

    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        // Load core classes (needed by HUB)
        require_once WPT_HUB_DIR . 'inc/core/class-wpt-database.php';
        require_once WPT_HUB_DIR . 'inc/core/class-wpt-custom-post-types.php';
        require_once WPT_HUB_DIR . 'inc/core/class-wpt-taxonomies.php';
        require_once WPT_HUB_DIR . 'inc/core/class-wpt-acf.php';
        require_once WPT_HUB_DIR . 'inc/core/class-wpt-helpers.php';
        require_once WPT_HUB_DIR . 'inc/core/class-wpt-roles.php';
        require_once WPT_HUB_DIR . 'inc/core/class-wpt-default-data.php';
        require_once WPT_HUB_DIR . 'inc/core/class-wpt-hub-translations.php';

        // Load HUB-specific components
        $this->load_hub_components();
    }

    /**
     * Load HUB-specific components
     */
    private function load_hub_components() {
        // API Server
        require_once WPT_HUB_DIR . 'inc/hub/api/class-wpt-api-server.php';

        // Quota API
        require_once WPT_HUB_DIR . 'inc/hub/api/class-wpt-quota-api.php';

        // AI Tokens API
        require_once WPT_HUB_DIR . 'inc/hub/class-wpt-ai-tokens-api.php';

        // Admin Menus
        if (is_admin()) {
            require_once WPT_HUB_DIR . 'inc/hub/class-wpt-admin-menus.php';
            require_once WPT_HUB_DIR . 'inc/hub/admin/class-wpt-tenant-addon-ajax.php';
        }

        // Tenant Management
        require_once WPT_HUB_DIR . 'inc/hub/class-wpt-tenant-manager.php';

        // Module Management
        require_once WPT_HUB_DIR . 'inc/hub/class-wpt-module-manager.php';

        // Plan Management
        require_once WPT_HUB_DIR . 'inc/hub/class-wpt-plan-manager.php';

        // Addon Management
        require_once WPT_HUB_DIR . 'inc/hub/class-wpt-addon-manager.php';

        // Feature Mapping Management
        require_once WPT_HUB_DIR . 'inc/hub/class-wpt-feature-mapping-manager.php';

        // Release Management
        require_once WPT_HUB_DIR . 'inc/hub/admin/class-wpt-release-manager.php';

        // Provisioning
        require_once WPT_HUB_DIR . 'inc/hub/class-wpt-provisioning.php';

        // Sync Configuration Admin
        if (is_admin()) {
            require_once WPT_HUB_DIR . 'inc/hub/admin/class-wpt-sync-config-admin.php';
        }

        // Module Categories Admin
        if (is_admin()) {
            require_once WPT_HUB_DIR . 'inc/hub/admin/class-wpt-module-categories-admin.php';
        }
    }

    /**
     * Define plugin hooks
     */
    private function define_hooks() {
        // Activation hook
        register_activation_hook(WPT_HUB_FILE, array($this, 'activate'));

        // Deactivation hook
        register_deactivation_hook(WPT_HUB_FILE, array($this, 'deactivate'));

        // Load text domain
        add_action('plugins_loaded', array($this, 'load_textdomain'));
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables (HUB tables)
        WPT_Database::create_tables();

        // Register custom roles (HUB roles)
        WPT_Roles::register_custom_roles();

        // Install default data (plans, modules, categories)
        WPT_Default_Data::install();

        // Flush rewrite rules
        flush_rewrite_rules();

        // Set default options
        if (!get_option('wpt_version')) {
            update_option('wpt_version', WPT_HUB_VERSION);
        }
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Load plugin text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'wpt-hub-plugin',
            false,
            dirname(WPT_HUB_BASENAME) . '/languages'
        );
    }
}

/**
 * Initialize plugin
 */
function wpt_hub_plugin() {
    return WPT_Hub_Plugin::instance();
}

// Kick off
wpt_hub_plugin();
