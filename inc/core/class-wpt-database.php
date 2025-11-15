<?php
/**
 * Database Management
 * Creates and manages custom database tables
 *
 * @package WPT_Optica_Core
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPT_Database {

    /**
     * Create all custom tables
     */
    public static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Only create HUB tables if on HUB
        if (WPT_IS_HUB) {
            self::create_hub_tables($charset_collate);
        }

        // Create common tables (both HUB and Site)
        self::create_common_tables($charset_collate);

        // Update database version
        update_option('wpt_db_version', WPT_CORE_VERSION);
    }

    /**
     * Create HUB-only tables
     */
    private static function create_hub_tables($charset_collate) {
        global $wpdb;

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Table: wpt_tenants
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpt_tenants (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            hub_user_id BIGINT UNSIGNED NOT NULL,
            brand_id BIGINT UNSIGNED NOT NULL,
            site_url VARCHAR(255) DEFAULT NULL,
            tenant_key VARCHAR(64) NOT NULL UNIQUE,
            api_key VARCHAR(64) NOT NULL,
            plan_id BIGINT UNSIGNED DEFAULT NULL,
            plan ENUM('starter', 'business', 'enterprise') DEFAULT 'starter',
            status ENUM('pending_site', 'active', 'suspended') DEFAULT 'pending_site',
            next_billing_date DATE DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME DEFAULT NULL,
            INDEX idx_user (hub_user_id),
            INDEX idx_brand (brand_id),
            INDEX idx_plan (plan_id),
            INDEX idx_status (status),
            INDEX idx_tenant_key (tenant_key)
        ) $charset_collate;";
        dbDelta($sql);

        // Table: wpt_plans
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpt_plans (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            slug VARCHAR(50) NOT NULL UNIQUE,
            name VARCHAR(100) NOT NULL,
            price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            billing_period ENUM('monthly', 'yearly') DEFAULT 'monthly',
            features JSON,
            is_active BOOLEAN DEFAULT 1,
            created_at DATETIME NOT NULL,
            INDEX idx_slug (slug)
        ) $charset_collate;";
        dbDelta($sql);

        // Table: wpt_addon_prices (master list of available addons)
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpt_addon_prices (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            addon_slug VARCHAR(100) NOT NULL UNIQUE,
            addon_name VARCHAR(255) NOT NULL,
            monthly_price DECIMAL(10,2) NOT NULL,
            description TEXT,
            is_active BOOLEAN DEFAULT 1,
            created_at DATETIME NOT NULL,
            updated_at DATETIME DEFAULT NULL,
            INDEX idx_slug (addon_slug)
        ) $charset_collate;";
        dbDelta($sql);

        // Table: wpt_feature_mappings (defines what each feature means)
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpt_feature_mappings (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            feature_key VARCHAR(100) NOT NULL UNIQUE,
            feature_name VARCHAR(255) NOT NULL,
            feature_type ENUM('post_type', 'taxonomy', 'capability', 'boolean', 'numeric') NOT NULL,
            target_identifier VARCHAR(255),
            is_quota BOOLEAN DEFAULT 0,
            description TEXT,
            created_at DATETIME NOT NULL,
            updated_at DATETIME DEFAULT NULL,
            INDEX idx_key (feature_key),
            INDEX idx_type (feature_type)
        ) $charset_collate;";
        dbDelta($sql);

        // Table: wpt_tenant_addons
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpt_tenant_addons (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tenant_id BIGINT UNSIGNED NOT NULL,
            addon_slug VARCHAR(50) NOT NULL,
            addon_price DECIMAL(10,2) NOT NULL,
            status ENUM('active', 'suspended') DEFAULT 'active',
            activated_at DATETIME NOT NULL,
            FOREIGN KEY (tenant_id) REFERENCES {$wpdb->prefix}wpt_tenants(id) ON DELETE CASCADE,
            INDEX idx_tenant (tenant_id)
        ) $charset_collate;";
        dbDelta($sql);

        // Table: wpt_available_modules
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpt_available_modules (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            category_id BIGINT UNSIGNED NOT NULL,
            slug VARCHAR(50) NOT NULL UNIQUE,
            title VARCHAR(100) NOT NULL,
            description TEXT,
            logo VARCHAR(255),
            price DECIMAL(10,2) DEFAULT 0.00,
            availability_mode ENUM('all_tenants', 'specific_tenants') DEFAULT 'all_tenants',
            is_active BOOLEAN DEFAULT 1,
            created_at DATETIME NOT NULL,
            updated_at DATETIME DEFAULT NULL,
            INDEX idx_slug (slug),
            INDEX idx_category (category_id)
        ) $charset_collate;";
        dbDelta($sql);

        // Table: wpt_module_categories
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpt_module_categories (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            slug VARCHAR(50) NOT NULL UNIQUE,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            icon VARCHAR(50),
            sort_order INT DEFAULT 0,
            INDEX idx_slug (slug)
        ) $charset_collate;";
        dbDelta($sql);

        // Table: wpt_module_availability (for specific_tenants mode)
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpt_module_availability (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            module_id BIGINT UNSIGNED NOT NULL,
            tenant_id BIGINT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL,
            FOREIGN KEY (module_id) REFERENCES {$wpdb->prefix}wpt_available_modules(id) ON DELETE CASCADE,
            FOREIGN KEY (tenant_id) REFERENCES {$wpdb->prefix}wpt_tenants(id) ON DELETE CASCADE,
            UNIQUE KEY unique_module_tenant (module_id, tenant_id),
            INDEX idx_module (module_id),
            INDEX idx_tenant (tenant_id)
        ) $charset_collate;";
        dbDelta($sql);

        // Table: wpt_tenant_modules
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpt_tenant_modules (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tenant_id BIGINT UNSIGNED NOT NULL,
            module_id BIGINT UNSIGNED NOT NULL,
            status ENUM('active', 'inactive') DEFAULT 'active',
            activated_by ENUM('admin', 'tenant') DEFAULT 'tenant',
            deactivated_by ENUM('admin', 'tenant') DEFAULT NULL,
            activated_at DATETIME NOT NULL,
            deactivated_at DATETIME DEFAULT NULL,
            FOREIGN KEY (tenant_id) REFERENCES {$wpdb->prefix}wpt_tenants(id) ON DELETE CASCADE,
            FOREIGN KEY (module_id) REFERENCES {$wpdb->prefix}wpt_available_modules(id) ON DELETE CASCADE,
            UNIQUE KEY unique_tenant_module (tenant_id, module_id),
            INDEX idx_tenant (tenant_id),
            INDEX idx_module (module_id)
        ) $charset_collate;";
        dbDelta($sql);

        // Table: wpt_releases
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpt_releases (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            version VARCHAR(20) NOT NULL UNIQUE,
            package_type ENUM('plugin', 'theme') NOT NULL,
            package_name VARCHAR(100) NOT NULL,
            file_path VARCHAR(255) NOT NULL,
            file_url VARCHAR(255) NOT NULL,
            file_size BIGINT UNSIGNED NOT NULL,
            checksum VARCHAR(64) NOT NULL,
            changelog TEXT DEFAULT NULL,
            requires_php VARCHAR(10) DEFAULT '7.4',
            requires_wp VARCHAR(10) DEFAULT '6.0',
            status ENUM('draft', 'testing', 'available', 'deprecated') DEFAULT 'draft',
            published_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME DEFAULT NULL,
            INDEX idx_version (version),
            INDEX idx_status (status)
        ) $charset_collate;";
        dbDelta($sql);

        // Table: wpt_site_versions
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpt_site_versions (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tenant_id BIGINT UNSIGNED NOT NULL,
            package_type ENUM('plugin', 'theme') NOT NULL,
            package_name VARCHAR(100) NOT NULL,
            installed_version VARCHAR(20) NOT NULL,
            updated_at DATETIME NOT NULL,
            FOREIGN KEY (tenant_id) REFERENCES {$wpdb->prefix}wpt_tenants(id) ON DELETE CASCADE,
            INDEX idx_tenant (tenant_id),
            UNIQUE KEY unique_tenant_package (tenant_id, package_type, package_name)
        ) $charset_collate;";
        dbDelta($sql);

        // Table: wpt_recommendations (reviews)
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpt_recommendations (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            brand_id BIGINT UNSIGNED NOT NULL,
            location_id BIGINT UNSIGNED DEFAULT NULL,
            reviewer_name VARCHAR(100) NOT NULL,
            reviewer_email VARCHAR(100) NOT NULL,
            rating TINYINT UNSIGNED NOT NULL,
            review_text TEXT NOT NULL,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            ip_address VARCHAR(45),
            user_agent VARCHAR(255),
            created_at DATETIME NOT NULL,
            approved_at DATETIME DEFAULT NULL,
            INDEX idx_brand (brand_id),
            INDEX idx_location (location_id),
            INDEX idx_status (status)
        ) $charset_collate;";
        dbDelta($sql);

        // Table: wpt_analytics
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpt_analytics (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tenant_id BIGINT UNSIGNED DEFAULT NULL,
            period DATE NOT NULL,
            pageviews INT UNSIGNED DEFAULT 0,
            visitors INT UNSIGNED DEFAULT 0,
            appointments INT UNSIGNED DEFAULT 0,
            offers INT UNSIGNED DEFAULT 0,
            jobs INT UNSIGNED DEFAULT 0,
            created_at DATETIME NOT NULL,
            updated_at DATETIME DEFAULT NULL,
            UNIQUE KEY unique_tenant_period (tenant_id, period),
            INDEX idx_tenant (tenant_id),
            INDEX idx_period (period)
        ) $charset_collate;";
        dbDelta($sql);
    }

    /**
     * Create common tables (both HUB and Site)
     */
    private static function create_common_tables($charset_collate) {
        global $wpdb;

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Table: wpt_sync_queue
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpt_sync_queue (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            post_id BIGINT UNSIGNED NOT NULL,
            post_type VARCHAR(50) NOT NULL,
            action ENUM('create', 'update', 'delete') NOT NULL,
            status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
            attempts INT DEFAULT 0,
            last_error TEXT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME DEFAULT NULL,
            INDEX idx_status (status),
            INDEX idx_post (post_id, post_type)
        ) $charset_collate;";
        dbDelta($sql);

        // Table: wpt_error_logs
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpt_error_logs (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tenant_id BIGINT UNSIGNED DEFAULT NULL,
            error_type VARCHAR(50) NOT NULL,
            message TEXT NOT NULL,
            context TEXT,
            user_id BIGINT UNSIGNED DEFAULT NULL,
            url VARCHAR(255),
            created_at DATETIME NOT NULL,
            INDEX idx_type (error_type),
            INDEX idx_tenant (tenant_id),
            INDEX idx_created (created_at)
        ) $charset_collate;";
        dbDelta($sql);
    }

    /**
     * Drop all custom tables (for uninstall)
     */
    public static function drop_tables() {
        global $wpdb;

        $tables = array(
            'wpt_tenant_modules',
            'wpt_module_availability',
            'wpt_tenant_addons',
            'wpt_addon_prices',
            'wpt_feature_mappings',
            'wpt_site_versions',
            'wpt_tenants',
            'wpt_plans',
            'wpt_available_modules',
            'wpt_module_categories',
            'wpt_releases',
            'wpt_recommendations',
            'wpt_analytics',
            'wpt_sync_queue',
            'wpt_error_logs',
        );

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}{$table}");
        }

        delete_option('wpt_db_version');
    }
}
