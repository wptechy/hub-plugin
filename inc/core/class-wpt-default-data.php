<?php
/**
 * Default Data Setup
 * Populates database with initial plans, module categories, and modules
 *
 * @package WPT_Optica_Core
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPT_Default_Data {

    /**
     * Install all default data
     * Called on plugin activation (HUB only)
     */
    public static function install() {
        if (!WPT_IS_HUB) {
            return; // Only run on HUB
        }

        self::install_plans();
        self::install_module_categories();
        self::install_modules();
    }

    /**
     * Install default subscription plans
     */
    private static function install_plans() {
        global $wpdb;

        // Check if plans already exist
        $existing = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wpt_plans");
        if ($existing > 0) {
            return; // Already installed
        }

        $plans = array(
            array(
                'slug' => 'starter',
                'name' => 'Starter',
                'price' => 49.00,
                'billing_period' => 'monthly',
                'features' => json_encode(array(
                    'website' => true,
                    'custom_domain' => false,
                    'brand_listing' => true,
                    'locations' => 1,
                    'products' => 50,
                    'offers' => 5,
                    'jobs' => 2,
                    'doctors' => 1,
                    'storage_gb' => 5,
                    'support' => 'email',
                    'modules_included' => array('programari-online'),
                    'modules_available' => array('reviews', 'newsletter', 'analytics'),
                )),
                'is_active' => 1,
                'created_at' => current_time('mysql'),
            ),
            array(
                'slug' => 'business',
                'name' => 'Business',
                'price' => 99.00,
                'billing_period' => 'monthly',
                'features' => json_encode(array(
                    'website' => true,
                    'custom_domain' => true,
                    'brand_listing' => true,
                    'locations' => 5,
                    'products' => 200,
                    'offers' => 20,
                    'jobs' => 10,
                    'doctors' => 5,
                    'storage_gb' => 20,
                    'support' => 'priority',
                    'modules_included' => array('programari-online', 'reviews', 'newsletter'),
                    'modules_available' => array('analytics', 'seo-tools', 'marketing-automation'),
                )),
                'is_active' => 1,
                'created_at' => current_time('mysql'),
            ),
            array(
                'slug' => 'enterprise',
                'name' => 'Enterprise',
                'price' => 199.00,
                'billing_period' => 'monthly',
                'features' => json_encode(array(
                    'website' => true,
                    'custom_domain' => true,
                    'brand_listing' => true,
                    'locations' => 999, // unlimited
                    'products' => 999, // unlimited
                    'offers' => 999, // unlimited
                    'jobs' => 999, // unlimited
                    'doctors' => 999, // unlimited
                    'storage_gb' => 100,
                    'support' => 'dedicated',
                    'modules_included' => array('programari-online', 'reviews', 'newsletter', 'analytics', 'seo-tools'),
                    'modules_available' => array('marketing-automation', 'crm-advanced', 'custom-integrations'),
                )),
                'is_active' => 1,
                'created_at' => current_time('mysql'),
            ),
        );

        foreach ($plans as $plan) {
            $wpdb->insert(
                $wpdb->prefix . 'wpt_plans',
                $plan,
                array('%s', '%s', '%f', '%s', '%s', '%d', '%s')
            );
        }
    }

    /**
     * Install module categories
     */
    private static function install_module_categories() {
        global $wpdb;

        // Check if categories already exist
        $existing = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wpt_module_categories");
        if ($existing > 0) {
            return; // Already installed
        }

        $categories = array(
            array(
                'slug' => 'essential',
                'name' => 'Esențiale',
                'description' => 'Module esențiale pentru orice site optica',
                'sort_order' => 1,
            ),
            array(
                'slug' => 'marketing',
                'name' => 'Marketing',
                'description' => 'Module pentru promovare și marketing',
                'sort_order' => 2,
            ),
            array(
                'slug' => 'analytics',
                'name' => 'Analytics & Rapoarte',
                'description' => 'Module pentru analiză și raportare',
                'sort_order' => 3,
            ),
            array(
                'slug' => 'integrations',
                'name' => 'Integrări',
                'description' => 'Integrări cu servicii externe',
                'sort_order' => 4,
            ),
            array(
                'slug' => 'advanced',
                'name' => 'Avansate',
                'description' => 'Module avansate pentru nevoi specifice',
                'sort_order' => 5,
            ),
        );

        foreach ($categories as $category) {
            $wpdb->insert(
                $wpdb->prefix . 'wpt_module_categories',
                $category,
                array('%s', '%s', '%s', '%d')
            );
        }
    }

    /**
     * Install available modules
     */
    private static function install_modules() {
        global $wpdb;

        // Check if modules already exist
        $existing = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wpt_available_modules");
        if ($existing > 0) {
            return; // Already installed
        }

        // Get category IDs
        $cat_essential = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}wpt_module_categories WHERE slug = 'essential'");
        $cat_marketing = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}wpt_module_categories WHERE slug = 'marketing'");
        $cat_analytics = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}wpt_module_categories WHERE slug = 'analytics'");
        $cat_integrations = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}wpt_module_categories WHERE slug = 'integrations'");
        $cat_advanced = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}wpt_module_categories WHERE slug = 'advanced'");

        $modules = array(
            // Essential
            array(
                'category_id' => $cat_essential,
                'slug' => 'programari-online',
                'title' => 'Programări Online',
                'description' => 'Permite clienților să facă programări online pentru consultații oftalmologice',
                'icon' => 'calendar-alt',
                'price' => 0.00, // Included in Starter
                'is_active' => 1,
                'created_at' => current_time('mysql'),
            ),
            array(
                'category_id' => $cat_essential,
                'slug' => 'contact-forms',
                'title' => 'Formulare Contact',
                'description' => 'Formulare personalizabile pentru contact clienți',
                'icon' => 'envelope',
                'price' => 0.00,
                'is_active' => 1,
                'created_at' => current_time('mysql'),
            ),

            // Marketing
            array(
                'category_id' => $cat_marketing,
                'slug' => 'reviews',
                'title' => 'Recenzii Clienți',
                'description' => 'Sistem de colectare și afișare recenzii clienți',
                'icon' => 'star',
                'price' => 15.00,
                'is_active' => 1,
                'created_at' => current_time('mysql'),
            ),
            array(
                'category_id' => $cat_marketing,
                'slug' => 'newsletter',
                'title' => 'Newsletter',
                'description' => 'Sistem de newsletter cu integrare MailChimp/SendGrid',
                'icon' => 'mail-bulk',
                'price' => 20.00,
                'is_active' => 1,
                'created_at' => current_time('mysql'),
            ),
            array(
                'category_id' => $cat_marketing,
                'slug' => 'seo-tools',
                'title' => 'SEO Tools',
                'description' => 'Instrumente pentru optimizare SEO (meta tags, sitemap, schema.org)',
                'icon' => 'search',
                'price' => 25.00,
                'is_active' => 1,
                'created_at' => current_time('mysql'),
            ),
            array(
                'category_id' => $cat_marketing,
                'slug' => 'marketing-automation',
                'title' => 'Marketing Automation',
                'description' => 'Email automation, campanii automate, remarketing',
                'icon' => 'robot',
                'price' => 40.00,
                'is_active' => 1,
                'created_at' => current_time('mysql'),
            ),

            // Analytics
            array(
                'category_id' => $cat_analytics,
                'slug' => 'analytics',
                'title' => 'Analytics Avansat',
                'description' => 'Google Analytics 4 + heatmaps + conversion tracking',
                'icon' => 'chart-line',
                'price' => 30.00,
                'is_active' => 1,
                'created_at' => current_time('mysql'),
            ),
            array(
                'category_id' => $cat_analytics,
                'slug' => 'reports',
                'title' => 'Rapoarte Personalizate',
                'description' => 'Generare rapoarte PDF pentru vânzări, clienți, performanță',
                'icon' => 'file-pdf',
                'price' => 25.00,
                'is_active' => 1,
                'created_at' => current_time('mysql'),
            ),

            // Integrations
            array(
                'category_id' => $cat_integrations,
                'slug' => 'whatsapp-chat',
                'title' => 'WhatsApp Chat',
                'description' => 'Widget WhatsApp pentru chat direct cu clienții',
                'icon' => 'whatsapp',
                'price' => 10.00,
                'is_active' => 1,
                'created_at' => current_time('mysql'),
            ),
            array(
                'category_id' => $cat_integrations,
                'slug' => 'facebook-pixel',
                'title' => 'Facebook Pixel',
                'description' => 'Integrare Facebook Pixel pentru tracking și remarketing',
                'icon' => 'facebook',
                'price' => 10.00,
                'is_active' => 1,
                'created_at' => current_time('mysql'),
            ),
            array(
                'category_id' => $cat_integrations,
                'slug' => 'smartbill-integration',
                'title' => 'SmartBill Integration',
                'description' => 'Integrare cu SmartBill pentru facturare automată',
                'icon' => 'file-invoice',
                'price' => 35.00,
                'is_active' => 1,
                'created_at' => current_time('mysql'),
            ),

            // Advanced
            array(
                'category_id' => $cat_advanced,
                'slug' => 'crm-advanced',
                'title' => 'CRM Avansat',
                'description' => 'CRM complet cu pipeline vânzări, task-uri, activity log',
                'icon' => 'users-cog',
                'price' => 50.00,
                'is_active' => 1,
                'created_at' => current_time('mysql'),
            ),
            array(
                'category_id' => $cat_advanced,
                'slug' => 'custom-integrations',
                'title' => 'Integrări Custom',
                'description' => 'Dezvoltare integrări personalizate cu sisteme externe',
                'icon' => 'plug',
                'price' => 0.00, // Price on request
                'is_active' => 1,
                'created_at' => current_time('mysql'),
            ),
        );

        foreach ($modules as $module) {
            $wpdb->insert(
                $wpdb->prefix . 'wpt_available_modules',
                $module,
                array('%d', '%s', '%s', '%s', '%s', '%f', '%d', '%s')
            );
        }
    }

    /**
     * Uninstall all default data
     */
    public static function uninstall() {
        global $wpdb;

        // Clear tables
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}wpt_available_modules");
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}wpt_module_categories");
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}wpt_plans");
    }
}
