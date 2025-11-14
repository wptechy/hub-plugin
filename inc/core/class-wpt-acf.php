<?php
/**
 * ACF Integration
 * Sets up ACF JSON save/load paths
 *
 * @package WPT_Optica_Core
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPT_ACF {

    public function __construct() {
        // Set ACF JSON save/load paths
        add_filter('acf/settings/save_json', array($this, 'set_json_save_path'));
        add_filter('acf/settings/load_json', array($this, 'set_json_load_paths'));
    }

    /**
     * Set ACF JSON save path (in plugin)
     */
    public function set_json_save_path($path) {
        return WPT_CORE_DIR . 'acf-json';
    }

    /**
     * Set ACF JSON load paths (load from plugin, not theme)
     */
    public function set_json_load_paths($paths) {
        // Remove theme path
        unset($paths[0]);

        // Add plugin path
        $paths[] = WPT_CORE_DIR . 'acf-json';

        return $paths;
    }
}

new WPT_ACF();
