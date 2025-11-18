#!/usr/bin/env php
<?php
/**
 * Run migration from command line
 */

// Load WordPress
define('WP_USE_THEMES', false);
require_once(__DIR__ . '/../../../../wp-load.php');

// Set context
if (!defined('WPT_IS_HUB')) {
    define('WPT_IS_HUB', true);
}

// Check if running from CLI
if (php_sapi_name() !== 'cli') {
    die('This script must be run from the command line.');
}

// Include migration file
require_once(__DIR__ . '/add-addon-module-dependencies.php');
