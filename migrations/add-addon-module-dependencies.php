<?php
/**
 * Migration: Add Addon-Module Dependencies
 *
 * Adds required_modules column to wpt_addon_prices table
 * and updates existing addons with their module dependencies
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

echo '<h1>Migration: Add Addon-Module Dependencies</h1>';
echo '<p>Starting migration...</p>';

// Step 1: Check if column already exists
echo '<h2>Step 1: Checking database schema...</h2>';

$table_name = $wpdb->prefix . 'wpt_addon_prices';
$column_check = $wpdb->get_results("SHOW COLUMNS FROM {$table_name} LIKE 'required_modules'");

if (!empty($column_check)) {
    echo '<p style="color: orange;">✓ Column "required_modules" already exists. Skipping schema update.</p>';
} else {
    echo '<p>Adding "required_modules" column to wpt_addon_prices table...</p>';

    $sql = "ALTER TABLE {$table_name}
            ADD COLUMN required_modules JSON DEFAULT NULL
            AFTER description";

    $result = $wpdb->query($sql);

    if ($result === false) {
        echo '<p style="color: red;">✗ Error: Could not add column. ' . $wpdb->last_error . '</p>';
        exit;
    }

    echo '<p style="color: green;">✓ Column added successfully!</p>';
}

// Step 2: Update existing addons with module dependencies
echo '<h2>Step 2: Updating addon module dependencies...</h2>';

$addon_dependencies = array(
    'extra-offers' => array('offers'),
    'extra-jobs' => array('career'),
    'premium-location' => array('locations'),
);

foreach ($addon_dependencies as $addon_slug => $required_modules) {
    $addon = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$table_name} WHERE addon_slug = %s",
        $addon_slug
    ));

    if (!$addon) {
        echo "<p style='color: orange;'>⚠ Addon '{$addon_slug}' not found. Skipping.</p>";
        continue;
    }

    // Check if already has required_modules
    if (!empty($addon->required_modules)) {
        $existing_modules = json_decode($addon->required_modules, true);
        if (is_array($existing_modules) && !empty($existing_modules)) {
            echo "<p>✓ Addon '{$addon_slug}' already has required_modules: " . implode(', ', $existing_modules) . "</p>";
            continue;
        }
    }

    // Update addon with required_modules
    $result = $wpdb->update(
        $table_name,
        array('required_modules' => json_encode($required_modules)),
        array('addon_slug' => $addon_slug),
        array('%s'),
        array('%s')
    );

    if ($result === false) {
        echo "<p style='color: red;'>✗ Error updating '{$addon_slug}': " . $wpdb->last_error . "</p>";
    } else {
        echo "<p style='color: green;'>✓ Updated '{$addon_slug}' with required modules: " . implode(', ', $required_modules) . "</p>";
    }
}

// Step 3: Add missing modules if needed
echo '<h2>Step 3: Checking required modules exist...</h2>';

$required_module_slugs = array('offers', 'career', 'locations');
$modules_table = $wpdb->prefix . 'wpt_available_modules';

foreach ($required_module_slugs as $module_slug) {
    $module = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$modules_table} WHERE slug = %s",
        $module_slug
    ));

    if ($module) {
        echo "<p>✓ Module '{$module_slug}' exists.</p>";
    } else {
        echo "<p style='color: orange;'>⚠ Module '{$module_slug}' not found in wpt_available_modules.</p>";
        echo "<p>  → Please ensure the module is seeded via WPT_Default_Data::install_modules()</p>";
    }
}

// Step 4: Verify migration
echo '<h2>Step 4: Verification...</h2>';

$addons = $wpdb->get_results("SELECT addon_slug, addon_name, required_modules FROM {$table_name}");

echo '<table border="1" cellpadding="5" style="border-collapse: collapse; margin-top: 10px;">';
echo '<thead><tr><th>Addon Slug</th><th>Addon Name</th><th>Required Modules</th></tr></thead>';
echo '<tbody>';

foreach ($addons as $addon) {
    $modules_display = 'None';
    if (!empty($addon->required_modules)) {
        $modules = json_decode($addon->required_modules, true);
        if (is_array($modules) && !empty($modules)) {
            $modules_display = implode(', ', $modules);
        }
    }

    echo "<tr>";
    echo "<td>{$addon->addon_slug}</td>";
    echo "<td>{$addon->addon_name}</td>";
    echo "<td>{$modules_display}</td>";
    echo "</tr>";
}

echo '</tbody></table>';

echo '<h2 style="color: green;">Migration completed successfully!</h2>';
echo '<p><a href="' . admin_url() . '">← Back to Dashboard</a></p>';
