<?php
/**
 * Migration: Add ai_tokens_used column to wpt_tenants table
 *
 * Run this once:
 * cd "/Users/bogdan/Local Sites/opticamedicala/app/public"
 * wp eval-file wp-content/plugins/wpt-hub-plugin/migrations/add-ai-tokens-column.php
 *
 * @package WPT_Hub
 * @since 1.0.0
 */

if (!defined('ABSPATH') && !defined('WP_CLI')) {
    // Load WordPress
    require_once dirname(__FILE__) . '/../../../../wp-load.php';
}

global $wpdb;

$table_name = $wpdb->prefix . 'wpt_tenants';

echo "🔍 Checking if ai_tokens_used column exists in {$table_name}...\n";

// Check if column already exists
$column_exists = $wpdb->get_results(
    $wpdb->prepare(
        "SHOW COLUMNS FROM `{$table_name}` LIKE %s",
        'ai_tokens_used'
    )
);

if (empty($column_exists)) {
    echo "➕ Adding column ai_tokens_used...\n";

    // Add column
    $result = $wpdb->query("
        ALTER TABLE `{$table_name}`
        ADD COLUMN `ai_tokens_used` INT UNSIGNED NOT NULL DEFAULT 0
            COMMENT 'Total AI tokens consumed by this tenant (input + output)'
            AFTER `status`
    ");

    if ($result === false) {
        echo "❌ Error adding column: " . $wpdb->last_error . "\n";
        exit(1);
    }

    echo "✅ Column ai_tokens_used added successfully!\n";

    // Add index for faster queries
    echo "➕ Adding index idx_ai_tokens...\n";

    $index_result = $wpdb->query("
        ALTER TABLE `{$table_name}`
        ADD INDEX `idx_ai_tokens` (`ai_tokens_used`)
    ");

    if ($index_result === false) {
        echo "⚠️  Warning: Could not add index (may already exist): " . $wpdb->last_error . "\n";
    } else {
        echo "✅ Index idx_ai_tokens added successfully!\n";
    }

    // Verify column was added
    $verify = $wpdb->get_row("SHOW COLUMNS FROM `{$table_name}` LIKE 'ai_tokens_used'");

    if ($verify) {
        echo "\n📋 Column details:\n";
        echo "   Field: {$verify->Field}\n";
        echo "   Type: {$verify->Type}\n";
        echo "   Null: {$verify->Null}\n";
        echo "   Default: {$verify->Default}\n";
        echo "\n✅ Migration completed successfully!\n";
    }

} else {
    echo "ℹ️  Column ai_tokens_used already exists. Skipping migration.\n";

    // Show current column details
    $column = $column_exists[0];
    echo "\n📋 Current column details:\n";
    echo "   Field: {$column->Field}\n";
    echo "   Type: {$column->Type}\n";
    echo "   Null: {$column->Null}\n";
    echo "   Default: {$column->Default}\n";
}

echo "\n";
