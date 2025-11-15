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
        self::install_addon_prices();
        self::install_feature_mappings();
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
            // FREE Tier
            array(
                'slug' => 'free',
                'name' => 'FREE',
                'price' => 0.00,
                'billing_period' => 'monthly',
                'features' => json_encode(array(
                    'brand_listing' => true,
                    'locations' => 1,
                    'location_type' => 'standard', // read-only on Hub
                    'offers' => 0,
                    'jobs' => 0,
                    'candidati_access' => false,
                    'furnizori_access' => false,
                    'tenant_site' => false, // Must buy addon
                    'support' => 'community',
                )),
                'is_active' => 1,
                'created_at' => current_time('mysql'),
            ),
            // PREMIUM Tier
            array(
                'slug' => 'premium',
                'name' => 'PREMIUM',
                'price' => 99.00,
                'billing_period' => 'monthly',
                'features' => json_encode(array(
                    'brand_listing' => true,
                    'locations' => 999, // unlimited standard locations
                    'location_type' => 'standard',
                    'offers' => 20,
                    'jobs' => 10,
                    'candidati_access' => false,
                    'furnizori_access' => false,
                    'tenant_site' => false, // Must buy addon
                    'support' => 'email',
                )),
                'is_active' => 1,
                'created_at' => current_time('mysql'),
            ),
            // BUSINESS Tier
            array(
                'slug' => 'business',
                'name' => 'BUSINESS',
                'price' => 199.00,
                'billing_period' => 'monthly',
                'features' => json_encode(array(
                    'brand_listing' => true,
                    'locations' => 999, // unlimited standard locations
                    'location_type' => 'standard',
                    'offers' => 50,
                    'jobs' => 30,
                    'candidati_access' => true,
                    'furnizori_access' => true,
                    'tenant_site' => false, // Must buy addon
                    'support' => 'priority',
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
     * Install default addon prices
     */
    private static function install_addon_prices() {
        global $wpdb;

        // Check if addon prices already exist
        $existing = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wpt_addon_prices");
        if ($existing > 0) {
            return; // Already installed
        }

        $addons = array(
            array(
                'addon_slug' => 'tenant-site',
                'addon_name' => 'Tenant Site',
                'monthly_price' => 500.00,
                'description' => 'Standalone WordPress site on custom subdomain with full theme and module support',
                'is_active' => 1,
                'created_at' => current_time('mysql'),
            ),
            array(
                'addon_slug' => 'extra-offers',
                'addon_name' => 'Extra Offers',
                'monthly_price' => 5.00,
                'description' => 'Additional offer quota (price per offer per month)',
                'is_active' => 1,
                'created_at' => current_time('mysql'),
            ),
            array(
                'addon_slug' => 'extra-jobs',
                'addon_name' => 'Extra Jobs',
                'monthly_price' => 3.00,
                'description' => 'Additional job posting quota (price per job per month)',
                'is_active' => 1,
                'created_at' => current_time('mysql'),
            ),
            array(
                'addon_slug' => 'premium-location',
                'addon_name' => 'Premium Location',
                'monthly_price' => 20.00,
                'description' => 'Upgrade standard location to premium with full editing and advanced features',
                'is_active' => 1,
                'created_at' => current_time('mysql'),
            ),
        );

        foreach ($addons as $addon) {
            $wpdb->insert(
                $wpdb->prefix . 'wpt_addon_prices',
                $addon,
                array('%s', '%s', '%f', '%s', '%d', '%s')
            );
        }
    }

    /**
     * Install default feature mappings
     */
    private static function install_feature_mappings() {
        global $wpdb;

        // Check if mappings already exist
        $existing = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wpt_feature_mappings");
        if ($existing > 0) {
            return; // Already installed
        }

        $mappings = array(
            // Quota Features (Post Types)
            array(
                'feature_key' => 'offers',
                'feature_name' => 'Oferte',
                'feature_type' => 'post_type',
                'target_identifier' => 'offer',
                'is_quota' => 1,
                'description' => 'Număr maxim de oferte pe care tenantul le poate publica',
                'created_at' => current_time('mysql'),
            ),
            array(
                'feature_key' => 'jobs',
                'feature_name' => 'Joburi',
                'feature_type' => 'post_type',
                'target_identifier' => 'job',
                'is_quota' => 1,
                'description' => 'Număr maxim de joburi pe care tenantul le poate publica',
                'created_at' => current_time('mysql'),
            ),
            array(
                'feature_key' => 'locations',
                'feature_name' => 'Locații',
                'feature_type' => 'post_type',
                'target_identifier' => 'location',
                'is_quota' => 1,
                'description' => 'Număr maxim de locații pe care tenantul le poate avea',
                'created_at' => current_time('mysql'),
            ),
            // Access Features (Capabilities)
            array(
                'feature_key' => 'candidati_access',
                'feature_name' => 'Acces Candidați',
                'feature_type' => 'capability',
                'target_identifier' => 'manage_candidates',
                'is_quota' => 0,
                'description' => 'Acces la modulul de gestionare candidați',
                'created_at' => current_time('mysql'),
            ),
            array(
                'feature_key' => 'furnizori_access',
                'feature_name' => 'Acces Furnizori',
                'feature_type' => 'capability',
                'target_identifier' => 'manage_suppliers',
                'is_quota' => 0,
                'description' => 'Acces la modulul de gestionare furnizori',
                'created_at' => current_time('mysql'),
            ),
            // Boolean Features
            array(
                'feature_key' => 'brand_listing',
                'feature_name' => 'Listare Brand pe Hub',
                'feature_type' => 'boolean',
                'target_identifier' => null,
                'is_quota' => 0,
                'description' => 'Brandul apare în directorul de pe opticamedicala.ro',
                'created_at' => current_time('mysql'),
            ),
            array(
                'feature_key' => 'tenant_site',
                'feature_name' => 'Site Propriu',
                'feature_type' => 'boolean',
                'target_identifier' => null,
                'is_quota' => 0,
                'description' => 'Tenantul are site WordPress propriu (addon)',
                'created_at' => current_time('mysql'),
            ),
            // Enum/String Features
            array(
                'feature_key' => 'location_type',
                'feature_name' => 'Tip Locație',
                'feature_type' => 'numeric',
                'target_identifier' => null,
                'is_quota' => 0,
                'description' => 'Tipul de locație (standard sau premium)',
                'created_at' => current_time('mysql'),
            ),
            array(
                'feature_key' => 'support',
                'feature_name' => 'Nivel Suport',
                'feature_type' => 'numeric',
                'target_identifier' => null,
                'is_quota' => 0,
                'description' => 'Nivelul de suport disponibil (community/email/priority/dedicated)',
                'created_at' => current_time('mysql'),
            ),
        );

        foreach ($mappings as $mapping) {
            $wpdb->insert(
                $wpdb->prefix . 'wpt_feature_mappings',
                $mapping,
                array('%s', '%s', '%s', '%s', '%d', '%s', '%s')
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
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}wpt_addon_prices");
    }
}
