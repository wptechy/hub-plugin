<?php
/**
 * Module Categories Admin
 * Manages CRUD operations for module categories
 *
 * @package WPT_Optica_Core
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPT_Module_Categories_Admin {

    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'), 21);
        add_action('wp_ajax_wpt_reorder_categories', array($this, 'handle_reorder'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'wpt-platform',
            __('Categorii de module', 'wpt-optica-core'),
            __('Categorii de module', 'wpt-optica-core'),
            'manage_options',
            'wpt-module-categories',
            array($this, 'render_page')
        );
    }

    /**
     * Render admin page
     */
    public function render_page( $args = array() ) {
        global $wpdb;

        $defaults = array(
            'embed' => false,
            'base'  => array(
                'page'  => 'wpt-module-categories',
                'query' => array(),
            ),
        );
        $args = wp_parse_args( $args, $defaults );

        if ( $args['embed'] ) {
            $GLOBALS['wpt_module_categories_embed'] = true;
            $GLOBALS['wpt_module_categories_base']  = $args['base'];
        } else {
            unset( $GLOBALS['wpt_module_categories_embed'], $GLOBALS['wpt_module_categories_base'] );
        }

        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        $category_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['wpt_category_nonce']) && wp_verify_nonce($_POST['wpt_category_nonce'], 'wpt_save_category')) {

            $category_data = array(
                'slug' => sanitize_title($_POST['slug']),
                'name' => sanitize_text_field($_POST['name']),
                'description' => wp_kses_post($_POST['description']),
                'icon' => sanitize_text_field($_POST['icon']),
                'sort_order' => intval($_POST['sort_order'])
            );

            if ($category_id > 0) {
                // Update existing category
                $wpdb->update(
                    $wpdb->prefix . 'wpt_module_categories',
                    $category_data,
                    array('id' => $category_id),
                    array('%s', '%s', '%s', '%s', '%d'),
                    array('%d')
                );
                echo '<div class="notice notice-success"><p>' . __('Categoria a fost actualizată cu succes', 'wpt-optica-core') . '</p></div>';
            } else {
                // Create new category
                $wpdb->insert(
                    $wpdb->prefix . 'wpt_module_categories',
                    $category_data,
                    array('%s', '%s', '%s', '%s', '%d')
                );
                $category_id = $wpdb->insert_id;
                echo '<div class="notice notice-success"><p>' . __('Categoria a fost creată cu succes', 'wpt-optica-core') . '</p></div>';

                // Redirect to edit page
                $target_page = ( isset( $_GET['page'] ) && $_GET['page'] === 'wpt-modules' )
                    ? admin_url( 'admin.php?page=wpt-modules&tab=categories&action=edit&id=' . $category_id )
                    : admin_url( 'admin.php?page=wpt-module-categories&action=edit&id=' . $category_id );
                echo '<script>window.location.href = "' . $target_page . '";</script>';
            }

            // Refresh category data if editing
            if ($action === 'edit' && $category_id > 0) {
                $category = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}wpt_module_categories WHERE id = %d",
                    $category_id
                ));
            }
        }

        // Handle delete
        if ($action === 'delete' && $category_id > 0) {
            check_admin_referer('wpt_delete_category_' . $category_id);

            // Check if category has modules
            $module_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}wpt_available_modules WHERE category_id = %d",
                $category_id
            ));

            if ($module_count > 0) {
                echo '<div class="notice notice-error"><p>' . sprintf(__('Categoria nu poate fi ștearsă. Are %d module asociate. Realocă sau șterge-le înainte.', 'wpt-optica-core'), $module_count) . '</p></div>';
            } else {
                $wpdb->delete(
                    $wpdb->prefix . 'wpt_module_categories',
                    array('id' => $category_id),
                    array('%d')
                );
                echo '<div class="notice notice-success"><p>' . __('Categoria a fost ștearsă cu succes', 'wpt-optica-core') . '</p></div>';
            }

            $action = 'list';
        }

        include WPT_HUB_DIR . 'inc/hub/admin/views/module-categories.php';

        if ( $args['embed'] ) {
            unset( $GLOBALS['wpt_module_categories_embed'], $GLOBALS['wpt_module_categories_base'] );
        }
    }

    /**
     * Handle AJAX category reordering
     */
    public function handle_reorder() {
        check_ajax_referer('wpt_reorder_categories', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permisiuni insuficiente', 'wpt-optica-core')));
        }

        $order = isset($_POST['order']) ? $_POST['order'] : array();

        if (empty($order) || !is_array($order)) {
            wp_send_json_error(array('message' => __('Date de ordonare invalide', 'wpt-optica-core')));
        }

        global $wpdb;

        foreach ($order as $index => $category_id) {
            $wpdb->update(
                $wpdb->prefix . 'wpt_module_categories',
                array('sort_order' => $index + 1),
                array('id' => intval($category_id)),
                array('%d'),
                array('%d')
            );
        }

        wp_send_json_success(array('message' => __('Ordinea categoriilor a fost actualizată', 'wpt-optica-core')));
    }
}

// Initialize
WPT_Module_Categories_Admin::get_instance();
