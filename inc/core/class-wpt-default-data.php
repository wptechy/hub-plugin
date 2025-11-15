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
                'slug' => 'content-pages',
                'name' => 'Content & Pagini',
                'description' => 'Module pentru gestionarea conținutului și paginilor speciale',
                'icon' => 'admin-page',
                'sort_order' => 1,
            ),
            array(
                'slug' => 'ecommerce',
                'name' => 'E-commerce',
                'description' => 'Module pentru magazin online și vânzări',
                'icon' => 'cart',
                'sort_order' => 2,
            ),
            array(
                'slug' => 'marketing-promotions',
                'name' => 'Marketing & Promoții',
                'description' => 'Module pentru marketing, promoții și campanii',
                'icon' => 'megaphone',
                'sort_order' => 3,
            ),
            array(
                'slug' => 'analytics-reporting',
                'name' => 'Analytics & Raportare',
                'description' => 'Module pentru analiză și raportare',
                'icon' => 'chart-bar',
                'sort_order' => 4,
            ),
            array(
                'slug' => 'integrations',
                'name' => 'Integrări',
                'description' => 'Module pentru integrări cu servicii externe',
                'icon' => 'networking',
                'sort_order' => 5,
            ),
            array(
                'slug' => 'automation',
                'name' => 'Automatizare',
                'description' => 'Module pentru automatizare procese',
                'icon' => 'update',
                'sort_order' => 6,
            ),
            array(
                'slug' => 'clients-crm',
                'name' => 'Clienți & CRM',
                'description' => 'Module pentru gestionarea clienților și relații',
                'icon' => 'groups',
                'sort_order' => 7,
            ),
            array(
                'slug' => 'communication',
                'name' => 'Comunicare',
                'description' => 'Module pentru comunicare și notificări',
                'icon' => 'email',
                'sort_order' => 8,
            )
        );

        foreach ($categories as $category) {
            $wpdb->insert(
                $wpdb->prefix . 'wpt_module_categories',
                $category,
                array('%s', '%s', '%s', '%s', '%d')
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
        $cat_content = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}wpt_module_categories WHERE slug = 'content-pages'");
        $cat_ecommerce = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}wpt_module_categories WHERE slug = 'ecommerce'");
        $cat_marketing = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}wpt_module_categories WHERE slug = 'marketing-promotions'");
        $cat_analytics = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}wpt_module_categories WHERE slug = 'analytics-reporting'");
        $cat_integrations = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}wpt_module_categories WHERE slug = 'integrations'");
        $cat_automation = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}wpt_module_categories WHERE slug = 'automation'");
        $cat_crm = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}wpt_module_categories WHERE slug = 'clients-crm'");
        $cat_communication = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}wpt_module_categories WHERE slug = 'communication'");

        $modules = array(
            // Content & Pagini
            array(
                'category_id' => $cat_content,
                'slug' => 'team-members',
                'title' => 'Echipa',
                'description' => 'Gestionare membri echipă cu profiluri complete',
                'logo' => null,
                'price' => 0.00,
                'availability_mode' => 'all_tenants',
                'is_active' => 1,
                'created_at' => current_time('mysql'),
            ),
            array(
                'category_id' => $cat_content,
                'slug' => 'faq-system',
                'title' => 'FAQ',
                'description' => 'Sistem de întrebări frecvente',
                'logo' => null,
                'price' => 0.00,
                'availability_mode' => 'all_tenants',
                'is_active' => 1,
                'created_at' => current_time('mysql'),
            ),
            array(
                'category_id' => $cat_content,
                'slug' => 'testimonials',
                'title' => 'Testimoniale',
                'description' => 'Gestionare testimoniale clienți',
                'logo' => null,
                'price' => 0.00,
                'availability_mode' => 'all_tenants',
                'is_active' => 1,
                'created_at' => current_time('mysql'),
            ),
            array(
                'category_id' => $cat_content,
                'slug' => 'blog-system',
                'title' => 'Blog',
                'description' => 'Sistem complet de blog cu categorii și taguri',
                'logo' => null,
                'price' => 29.00,
                'availability_mode' => 'all_tenants',
                'is_active' => 1,
                'created_at' => current_time('mysql'),
            ),

            // E-commerce
            array(
                'category_id' => $cat_ecommerce,
                'slug' => 'woocommerce',
                'title' => 'WooCommerce',
                'description' => 'Magazin online complet',
                'logo' => null,
                'price' => 99.00,
                'availability_mode' => 'all_tenants',
                'is_active' => 1,
                'created_at' => current_time('mysql'),
            ),
            array(
                'category_id' => $cat_ecommerce,
                'slug' => 'product-catalog',
                'title' => 'Catalog Produse',
                'description' => 'Catalog produse fără funcționalitate de cumpărare',
                'logo' => null,
                'price' => 49.00,
                'availability_mode' => 'all_tenants',
                'is_active' => 1,
                'created_at' => current_time('mysql'),
            ),

            // Marketing & Promoții
            array(
                'category_id' => $cat_marketing,
                'slug' => 'promotions',
                'title' => 'Promoții',
                'description' => 'Sistem de promoții și oferte speciale',
                'logo' => null,
                'price' => 0.00,
                'availability_mode' => 'all_tenants',
                'is_active' => 1,
                'created_at' => current_time('mysql'),
            ),
            array(
                'category_id' => $cat_marketing,
                'slug' => 'newsletter',
                'title' => 'Newsletter',
                'description' => 'Sistem de newsletter și email marketing',
                'logo' => null,
                'price' => 39.00,
                'availability_mode' => 'all_tenants',
                'is_active' => 1,
                'created_at' => current_time('mysql'),
            ),
            array(
                'category_id' => $cat_marketing,
                'slug' => 'loyalty-program',
                'title' => 'Program Loialitate',
                'description' => 'Sistem de puncte și recompense',
                'logo' => null,
                'price' => 99.00,
                'availability_mode' => 'all_tenants',
                'is_active' => 1,
                'created_at' => current_time('mysql'),
            ),

            // Analytics & Raportare
            array(
                'category_id' => $cat_analytics,
                'slug' => 'google-analytics',
                'title' => 'Google Analytics',
                'description' => 'Integrare Google Analytics 4',
                'logo' => null,
                'price' => 0.00,
                'availability_mode' => 'all_tenants',
                'is_active' => 1,
                'created_at' => current_time('mysql'),
            ),
            array(
                'category_id' => $cat_analytics,
                'slug' => 'custom-reports',
                'title' => 'Rapoarte Custom',
                'description' => 'Rapoarte personalizate pentru business',
                'logo' => null,
                'price' => 59.00,
                'availability_mode' => 'all_tenants',
                'is_active' => 1,
                'created_at' => current_time('mysql'),
            ),

            // Integrări
            array(
                'category_id' => $cat_integrations,
                'slug' => 'google-maps',
                'title' => 'Google Maps',
                'description' => 'Integrare Google Maps pentru locații',
                'logo' => null,
                'price' => 0.00,
                'availability_mode' => 'all_tenants',
                'is_active' => 1,
                'created_at' => current_time('mysql'),
            ),
            array(
                'category_id' => $cat_integrations,
                'slug' => 'facebook-pixel',
                'title' => 'Facebook Pixel',
                'description' => 'Integrare Facebook Pixel pentru tracking',
                'logo' => null,
                'price' => 0.00,
                'availability_mode' => 'all_tenants',
                'is_active' => 1,
                'created_at' => current_time('mysql'),
            ),
            array(
                'category_id' => $cat_integrations,
                'slug' => 'mailchimp',
                'title' => 'Mailchimp',
                'description' => 'Integrare cu Mailchimp pentru email marketing',
                'logo' => null,
                'price' => 29.00,
                'availability_mode' => 'all_tenants',
                'is_active' => 1,
                'created_at' => current_time('mysql'),
            ),

            // Automatizare
            array(
                'category_id' => $cat_automation,
                'slug' => 'auto-backup',
                'title' => 'Backup Automat',
                'description' => 'Backup automat zilnic al site-ului',
                'logo' => null,
                'price' => 39.00,
                'availability_mode' => 'all_tenants',
                'is_active' => 1,
                'created_at' => current_time('mysql'),
            ),
            array(
                'category_id' => $cat_automation,
                'slug' => 'scheduled-posts',
                'title' => 'Postări Programate',
                'description' => 'Sistem avansat de programare postări',
                'logo' => null,
                'price' => 19.00,
                'availability_mode' => 'all_tenants',
                'is_active' => 1,
                'created_at' => current_time('mysql'),
            ),

            // Clienți & CRM
            array(
                'category_id' => $cat_crm,
                'slug' => 'appointments',
                'title' => 'Programări Online',
                'description' => 'Sistem de programări online cu calendar',
                'logo' => null,
                'price' => 79.00,
                'availability_mode' => 'all_tenants',
                'is_active' => 1,
                'created_at' => current_time('mysql'),
            ),
            array(
                'category_id' => $cat_crm,
                'slug' => 'crm-basic',
                'title' => 'CRM Basic',
                'description' => 'Sistem CRM pentru gestionare clienți',
                'logo' => null,
                'price' => 99.00,
                'availability_mode' => 'all_tenants',
                'is_active' => 1,
                'created_at' => current_time('mysql'),
            ),

            // Comunicare
            array(
                'category_id' => $cat_communication,
                'slug' => 'contact-forms',
                'title' => 'Formulare Contact',
                'description' => 'Formulare de contact personalizabile',
                'logo' => null,
                'price' => 0.00,
                'availability_mode' => 'all_tenants',
                'is_active' => 1,
                'created_at' => current_time('mysql'),
            ),
            array(
                'category_id' => $cat_communication,
                'slug' => 'live-chat',
                'title' => 'Live Chat',
                'description' => 'Chat live pentru suport clienți',
                'logo' => null,
                'price' => 49.00,
                'availability_mode' => 'all_tenants',
                'is_active' => 1,
                'created_at' => current_time('mysql'),
            ),
            array(
                'category_id' => $cat_communication,
                'slug' => 'sms-notifications',
                'title' => 'Notificări SMS',
                'description' => 'Sistem de notificări SMS pentru clienți',
                'logo' => null,
                'price' => 59.00,
                'availability_mode' => 'all_tenants',
                'is_active' => 1,
                'created_at' => current_time('mysql'),
            ),
        );

        foreach ($modules as $module) {
            $wpdb->insert(
                $wpdb->prefix . 'wpt_available_modules',
                $module,
                array('%d', '%s', '%s', '%s', '%s', '%f', '%s', '%d', '%s')
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
