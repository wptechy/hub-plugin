<?php
/**
 * Custom Post Types Registration
 *
 * @package WPT_Optica_Core
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPT_Custom_Post_Types {

    public function __construct() {
        // COMMENTED OUT - CPTs now managed via ACF JSON (see /Local Sites/export-acf-json/)
        // add_action('init', array($this, 'register_post_types'));
    }

    /**
     * Register all custom post types
     */
    public function register_post_types() {
        // Common CPTs (both HUB and Site)
        $this->register_brand();
        $this->register_location();
        $this->register_offer();
        $this->register_job();
        $this->register_doctor();

        // HUB-only CPTs
        if (WPT_IS_HUB) {
            $this->register_doctor_schedule();
            $this->register_candidate();
            $this->register_supplier();
            $this->register_product();
        }
    }

    /**
     * Register Brand CPT
     */
    private function register_brand() {
        $labels = array(
            'name' => __('Brand', 'wpt-optica-core'),
            'singular_name' => __('Brand', 'wpt-optica-core'),
            'add_new' => __('Adaugă Brand', 'wpt-optica-core'),
            'add_new_item' => __('Adaugă Brand Nou', 'wpt-optica-core'),
            'edit_item' => __('Editează Brand', 'wpt-optica-core'),
            'view_item' => __('Vezi Brand', 'wpt-optica-core'),
            'all_items' => WPT_IS_HUB ? __('Toate Brandurile', 'wpt-optica-core') : __('Brandul Meu', 'wpt-optica-core'),
            'search_items' => __('Caută Branduri', 'wpt-optica-core'),
        );

        // On Client site, disable creating new brands (only edit existing)
        $capabilities = array(
            'create_posts' => WPT_IS_HUB ? 'edit_posts' : 'do_not_allow',
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true, // Show on both HUB and Client
            'show_in_menu' => true, // Show on both HUB and Client
            'query_var' => true,
            'rewrite' => array('slug' => 'brand'),
            'capability_type' => 'post',
            'capabilities' => $capabilities,
            'map_meta_cap' => true,
            'has_archive' => WPT_IS_HUB, // Archive doar pe HUB
            'hierarchical' => false,
            'menu_position' => 5,
            'menu_icon' => 'dashicons-building',
            'show_in_rest' => true,
            'supports' => array('title', 'editor', 'thumbnail'),
        );

        register_post_type('brand', $args);
    }

    /**
     * Register Location CPT
     */
    private function register_location() {
        $labels = array(
            'name' => __('Locații', 'wpt-optica-core'),
            'singular_name' => __('Locație', 'wpt-optica-core'),
            'add_new' => __('Adaugă Locație', 'wpt-optica-core'),
            'add_new_item' => __('Adaugă Locație Nouă', 'wpt-optica-core'),
            'edit_item' => __('Editează Locația', 'wpt-optica-core'),
            'view_item' => __('Vezi Locația', 'wpt-optica-core'),
            'all_items' => __('Toate Locațiile', 'wpt-optica-core'),
            'search_items' => __('Caută Locații', 'wpt-optica-core'),
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'locatie'),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => 6,
            'menu_icon' => 'dashicons-location',
            'show_in_rest' => true,
            'supports' => array('title', 'editor', 'thumbnail'),
        );

        register_post_type('locatie', $args);
    }

    /**
     * Register Offer CPT
     */
    private function register_offer() {
        $labels = array(
            'name' => __('Oferte', 'wpt-optica-core'),
            'singular_name' => __('Ofertă', 'wpt-optica-core'),
            'add_new' => __('Adaugă Ofertă', 'wpt-optica-core'),
            'add_new_item' => __('Adaugă Ofertă Nouă', 'wpt-optica-core'),
            'edit_item' => __('Editează Oferta', 'wpt-optica-core'),
            'view_item' => __('Vezi Oferta', 'wpt-optica-core'),
            'all_items' => __('Toate Ofertele', 'wpt-optica-core'),
            'search_items' => __('Caută Oferte', 'wpt-optica-core'),
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'oferta'),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => 7,
            'menu_icon' => 'dashicons-tag',
            'show_in_rest' => true,
            'supports' => array('title', 'editor', 'thumbnail'),
        );

        register_post_type('oferta', $args);
    }

    /**
     * Register Job CPT
     */
    private function register_job() {
        $labels = array(
            'name' => __('Joburi', 'wpt-optica-core'),
            'singular_name' => __('Job', 'wpt-optica-core'),
            'add_new' => __('Adaugă Job', 'wpt-optica-core'),
            'add_new_item' => __('Adaugă Job Nou', 'wpt-optica-core'),
            'edit_item' => __('Editează Jobul', 'wpt-optica-core'),
            'view_item' => __('Vezi Jobul', 'wpt-optica-core'),
            'all_items' => __('Toate Joburile', 'wpt-optica-core'),
            'search_items' => __('Caută Joburi', 'wpt-optica-core'),
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'job'),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => 8,
            'menu_icon' => 'dashicons-id',
            'show_in_rest' => true,
            'supports' => array('title', 'editor', 'thumbnail'),
        );

        register_post_type('job', $args);
    }

    /**
     * Register Doctor CPT
     */
    private function register_doctor() {
        $labels = array(
            'name' => __('Medici', 'wpt-optica-core'),
            'singular_name' => __('Medic', 'wpt-optica-core'),
            'add_new' => __('Adaugă Medic', 'wpt-optica-core'),
            'add_new_item' => __('Adaugă Medic Nou', 'wpt-optica-core'),
            'edit_item' => __('Editează Medicul', 'wpt-optica-core'),
            'view_item' => __('Vezi Medicul', 'wpt-optica-core'),
            'all_items' => __('Toți Medicii', 'wpt-optica-core'),
            'search_items' => __('Caută Medici', 'wpt-optica-core'),
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'medic'),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => 9,
            'menu_icon' => 'dashicons-admin-users',
            'show_in_rest' => true,
            'supports' => array('title', 'editor', 'thumbnail'),
        );

        register_post_type('medic', $args);
    }

    /**
     * Register Doctor Schedule CPT (HUB only)
     */
    private function register_doctor_schedule() {
        $labels = array(
            'name' => __('Program Medici', 'wpt-optica-core'),
            'singular_name' => __('Program Medic', 'wpt-optica-core'),
            'add_new' => __('Adaugă Program', 'wpt-optica-core'),
            'add_new_item' => __('Adaugă Program Nou', 'wpt-optica-core'),
            'edit_item' => __('Editează Programul', 'wpt-optica-core'),
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'program-medic'),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => 10,
            'menu_icon' => 'dashicons-calendar-alt',
            'show_in_rest' => true,
            'supports' => array('title', 'editor'),
        );

        register_post_type('program_medic', $args);
    }

    /**
     * Register Candidate CPT (HUB only)
     */
    private function register_candidate() {
        $labels = array(
            'name' => __('Candidați', 'wpt-optica-core'),
            'singular_name' => __('Candidat', 'wpt-optica-core'),
            'add_new' => __('Adaugă Candidat', 'wpt-optica-core'),
            'add_new_item' => __('Adaugă Candidat Nou', 'wpt-optica-core'),
            'edit_item' => __('Editează Candidatul', 'wpt-optica-core'),
        );

        $args = array(
            'labels' => $labels,
            'public' => false,
            'publicly_queryable' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'candidat'),
            'capability_type' => 'post',
            'has_archive' => false,
            'hierarchical' => false,
            'menu_position' => 11,
            'menu_icon' => 'dashicons-businessman',
            'show_in_rest' => false,
            'supports' => array('title', 'editor'),
        );

        register_post_type('candidat', $args);
    }

    /**
     * Register Supplier CPT (HUB only)
     */
    private function register_supplier() {
        $labels = array(
            'name' => __('Furnizori', 'wpt-optica-core'),
            'singular_name' => __('Furnizor', 'wpt-optica-core'),
            'add_new' => __('Adaugă Furnizor', 'wpt-optica-core'),
            'add_new_item' => __('Adaugă Furnizor Nou', 'wpt-optica-core'),
            'edit_item' => __('Editează Furnizorul', 'wpt-optica-core'),
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'furnizor'),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => 12,
            'menu_icon' => 'dashicons-cart',
            'show_in_rest' => true,
            'supports' => array('title', 'editor', 'thumbnail'),
        );

        register_post_type('furnizor', $args);
    }

    /**
     * Register Product CPT (HUB only)
     */
    private function register_product() {
        $labels = array(
            'name' => __('Produse', 'wpt-optica-core'),
            'singular_name' => __('Produs', 'wpt-optica-core'),
            'add_new' => __('Adaugă Produs', 'wpt-optica-core'),
            'add_new_item' => __('Adaugă Produs Nou', 'wpt-optica-core'),
            'edit_item' => __('Editează Produsul', 'wpt-optica-core'),
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'produs'),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => 13,
            'menu_icon' => 'dashicons-products',
            'show_in_rest' => true,
            'supports' => array('title', 'editor', 'thumbnail'),
        );

        register_post_type('produs', $args);
    }
}

new WPT_Custom_Post_Types();
