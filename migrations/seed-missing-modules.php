<?php
/**
 * Seed Missing Modules
 *
 * Adds the missing modules: offers, career, locations
 *
 * @package WPT_Optica_Core
 */

// Load WordPress if not already loaded
if (!defined('ABSPATH')) {
    require_once(__DIR__ . '/../../../../wp-load.php');
}

// Only run on HUB
if (!defined('WPT_IS_HUB') || !WPT_IS_HUB) {
    wp_die('This migration can only be run on the HUB.');
}

// Verify we have admin capabilities
if (!current_user_can('manage_options')) {
    wp_die('You do not have permission to run migrations.');
}

global $wpdb;

echo '<h1>Seed Missing Modules</h1>';
echo '<p>Adding modules: offers, career, locations...</p>';

// Get Essential category ID
$cat_essential = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}wpt_module_categories WHERE slug = 'essential'");

if (!$cat_essential) {
    echo '<p style="color: red;">✗ Error: Essential category not found. Please run WPT_Default_Data::install_module_categories() first.</p>';
    exit;
}

echo "<p>✓ Found Essential category (ID: {$cat_essential})</p>";

// Define missing modules
$missing_modules = array(
    array(
        'category_id' => $cat_essential,
        'slug' => 'offers',
        'title' => 'Oferte',
        'description' => 'Sistem de gestionare oferte și promoții',
        'icon' => 'tags',
        'price' => 0.00,
        'is_active' => 1,
        'created_at' => current_time('mysql'),
    ),
    array(
        'category_id' => $cat_essential,
        'slug' => 'career',
        'title' => 'Cariere',
        'description' => 'Sistem de gestionare joburi și candidați',
        'icon' => 'briefcase',
        'price' => 0.00,
        'is_active' => 1,
        'created_at' => current_time('mysql'),
    ),
    array(
        'category_id' => $cat_essential,
        'slug' => 'locations',
        'title' => 'Locații',
        'description' => 'Gestionare locații și puncte de lucru',
        'icon' => 'map-marker-alt',
        'price' => 0.00,
        'is_active' => 1,
        'created_at' => current_time('mysql'),
    ),
);

echo '<h2>Adding modules...</h2>';

foreach ($missing_modules as $module) {
    // Check if module already exists
    $existing = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}wpt_available_modules WHERE slug = %s",
        $module['slug']
    ));

    if ($existing) {
        echo "<p style='color: orange;'>⚠ Module '{$module['slug']}' already exists (ID: {$existing->id}). Skipping.</p>";
        continue;
    }

    // Insert module
    $result = $wpdb->insert(
        $wpdb->prefix . 'wpt_available_modules',
        $module,
        array('%d', '%s', '%s', '%s', '%s', '%f', '%d', '%s')
    );

    if ($result === false) {
        echo "<p style='color: red;'>✗ Error adding '{$module['slug']}': " . $wpdb->last_error . "</p>";
    } else {
        $module_id = $wpdb->insert_id;
        echo "<p style='color: green;'>✓ Added module '{$module['title']}' (slug: {$module['slug']}, ID: {$module_id})</p>";
    }
}

echo '<h2>Verification...</h2>';

// Verify all modules exist
$modules = $wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}wpt_available_modules
    WHERE slug IN ('offers', 'career', 'locations')
    ORDER BY slug"
);

echo '<table border="1" cellpadding="5" style="border-collapse: collapse; margin-top: 10px;">';
echo '<thead><tr><th>ID</th><th>Slug</th><th>Title</th><th>Category</th><th>Active</th></tr></thead>';
echo '<tbody>';

foreach ($modules as $module) {
    $category = $wpdb->get_var($wpdb->prepare(
        "SELECT name FROM {$wpdb->prefix}wpt_module_categories WHERE id = %d",
        $module->category_id
    ));

    $active_badge = $module->is_active ? '<span style="color: green;">✓ Active</span>' : '<span style="color: red;">✗ Inactive</span>';

    echo "<tr>";
    echo "<td>{$module->id}</td>";
    echo "<td>{$module->slug}</td>";
    echo "<td>{$module->title}</td>";
    echo "<td>{$category}</td>";
    echo "<td>{$active_badge}</td>";
    echo "</tr>";
}

echo '</tbody></table>';

echo '<h2 style="color: green;">✓ Module seeding completed!</h2>';
echo '<p><a href="' . admin_url() . '">← Back to Dashboard</a></p>';
