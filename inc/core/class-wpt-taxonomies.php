<?php
/**
 * Taxonomies Registration
 *
 * @package WPT_Optica_Core
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPT_Taxonomies {

    public function __construct() {
        // COMMENTED OUT - Taxonomies now managed via ACF JSON (see /Local Sites/export-acf-json/)
        // add_action('init', array($this, 'register_taxonomies'));
    }

    /**
     * Register all taxonomies
     */
    public function register_taxonomies() {
        // Common taxonomies
        $this->register_serviciu();
        $this->register_brand_produs();
        $this->register_localitate();
        $this->register_specializare();

        // HUB-only taxonomies
        if (WPT_IS_HUB) {
            $this->register_categorie_produs();
        }
    }

    /**
     * Register Serviciu taxonomy
     */
    private function register_serviciu() {
        $labels = array(
            'name' => __('Servicii', 'wpt-optica-core'),
            'singular_name' => __('Serviciu', 'wpt-optica-core'),
            'search_items' => __('Caută Servicii', 'wpt-optica-core'),
            'all_items' => __('Toate Serviciile', 'wpt-optica-core'),
            'edit_item' => __('Editează Serviciul', 'wpt-optica-core'),
            'update_item' => __('Actualizează Serviciul', 'wpt-optica-core'),
            'add_new_item' => __('Adaugă Serviciu Nou', 'wpt-optica-core'),
            'new_item_name' => __('Nume Serviciu Nou', 'wpt-optica-core'),
        );

        $args = array(
            'labels' => $labels,
            'hierarchical' => true,
            'public' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_tagcloud' => true,
            'show_in_rest' => true,
            'rewrite' => array('slug' => 'serviciu'),
        );

        register_taxonomy('serviciu', array('oferta'), $args);
    }

    /**
     * Register Brand Produs taxonomy
     */
    private function register_brand_produs() {
        $labels = array(
            'name' => __('Branduri Produse', 'wpt-optica-core'),
            'singular_name' => __('Brand Produs', 'wpt-optica-core'),
            'search_items' => __('Caută Branduri', 'wpt-optica-core'),
            'all_items' => __('Toate Brandurile', 'wpt-optica-core'),
            'edit_item' => __('Editează Brandul', 'wpt-optica-core'),
            'update_item' => __('Actualizează Brandul', 'wpt-optica-core'),
            'add_new_item' => __('Adaugă Brand Nou', 'wpt-optica-core'),
            'new_item_name' => __('Nume Brand Nou', 'wpt-optica-core'),
        );

        $args = array(
            'labels' => $labels,
            'hierarchical' => false,
            'public' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_tagcloud' => true,
            'show_in_rest' => true,
            'rewrite' => array('slug' => 'brand-produs'),
        );

        register_taxonomy('brand_produs', array('produs', 'oferta'), $args);
    }

    /**
     * Register Localitate taxonomy
     */
    private function register_localitate() {
        $labels = array(
            'name' => __('Localități', 'wpt-optica-core'),
            'singular_name' => __('Localitate', 'wpt-optica-core'),
            'search_items' => __('Caută Localități', 'wpt-optica-core'),
            'all_items' => __('Toate Localitățile', 'wpt-optica-core'),
            'edit_item' => __('Editează Localitatea', 'wpt-optica-core'),
            'update_item' => __('Actualizează Localitatea', 'wpt-optica-core'),
            'add_new_item' => __('Adaugă Localitate Nouă', 'wpt-optica-core'),
            'new_item_name' => __('Nume Localitate Nouă', 'wpt-optica-core'),
        );

        $args = array(
            'labels' => $labels,
            'hierarchical' => true,
            'public' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_tagcloud' => false,
            'show_in_rest' => true,
            'rewrite' => array('slug' => 'localitate'),
        );

        register_taxonomy('localitate', array('locatie', 'brand'), $args);
    }

    /**
     * Register Specializare taxonomy
     */
    private function register_specializare() {
        $labels = array(
            'name' => __('Specializări', 'wpt-optica-core'),
            'singular_name' => __('Specializare', 'wpt-optica-core'),
            'search_items' => __('Caută Specializări', 'wpt-optica-core'),
            'all_items' => __('Toate Specializările', 'wpt-optica-core'),
            'edit_item' => __('Editează Specializarea', 'wpt-optica-core'),
            'update_item' => __('Actualizează Specializarea', 'wpt-optica-core'),
            'add_new_item' => __('Adaugă Specializare Nouă', 'wpt-optica-core'),
            'new_item_name' => __('Nume Specializare Nouă', 'wpt-optica-core'),
        );

        $args = array(
            'labels' => $labels,
            'hierarchical' => false,
            'public' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_tagcloud' => true,
            'show_in_rest' => true,
            'rewrite' => array('slug' => 'specializare'),
        );

        register_taxonomy('specializare', array('medic'), $args);
    }

    /**
     * Register Categorie Produs taxonomy (HUB only)
     */
    private function register_categorie_produs() {
        $labels = array(
            'name' => __('Categorii Produse', 'wpt-optica-core'),
            'singular_name' => __('Categorie Produs', 'wpt-optica-core'),
            'search_items' => __('Caută Categorii', 'wpt-optica-core'),
            'all_items' => __('Toate Categoriile', 'wpt-optica-core'),
            'edit_item' => __('Editează Categoria', 'wpt-optica-core'),
            'update_item' => __('Actualizează Categoria', 'wpt-optica-core'),
            'add_new_item' => __('Adaugă Categorie Nouă', 'wpt-optica-core'),
            'new_item_name' => __('Nume Categorie Nouă', 'wpt-optica-core'),
        );

        $args = array(
            'labels' => $labels,
            'hierarchical' => true,
            'public' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_tagcloud' => false,
            'show_in_rest' => true,
            'rewrite' => array('slug' => 'categorie-produs'),
        );

        register_taxonomy('categorie_produs', array('produs'), $args);
    }
}

new WPT_Taxonomies();
