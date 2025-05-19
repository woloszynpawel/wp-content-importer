<?php
/**
 * WordPress constants compatibility layer
 */

// Define WordPress constants if they're not already defined
if (!defined('ABSPATH')) {
    // Try to get WordPress root directory
    $wordpress_path = dirname(dirname(dirname(dirname(__FILE__))));
    if (file_exists($wordpress_path . '/wp-load.php')) {
        define('ABSPATH', $wordpress_path . '/');
    } else {
        die('WordPress installation not found. Please make sure the plugin is installed in the correct directory.');
    }
}

if (!defined('WP_CONTENT_DIR')) {
    define('WP_CONTENT_DIR', ABSPATH . 'wp-content');
}

if (!defined('WP_PLUGIN_DIR')) {
    define('WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins');
}

if (!defined('WP_DEBUG')) {
    define('WP_DEBUG', false);
}

// Load WordPress core if not already loaded
if (!function_exists('add_action')) {
    require_once(ABSPATH . 'wp-load.php');
}

// Make sure we have basic WordPress functionality
require_once(ABSPATH . 'wp-admin/includes/plugin.php');
require_once(ABSPATH . 'wp-includes/pluggable.php');
require_once(ABSPATH . 'wp-admin/includes/file.php'); 