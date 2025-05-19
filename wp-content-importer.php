<?php
/**
 * Plugin Name: WP Content Importer
 * Plugin URI: https://github.com/woloszynpawel/wp-content-importer
 * Description: Import content from other websites into WordPress with visual selector.
 * Version: 1.0.1
 * Author: Pawel Woloszyn
 * Author URI: https://github.com/woloszynpawel
 * Text Domain: wp-content-importer
 * Domain Path: /languages
 * License: GPL v2 or later
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Update URI: https://github.com/woloszynpawel/wp-content-importer
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Make sure we have access to WordPress functions
require_once(ABSPATH . 'wp-admin/includes/plugin.php');

// Define plugin constants
define('WP_CONTENT_IMPORTER_VERSION', '1.0.1');
define('WP_CONTENT_IMPORTER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_CONTENT_IMPORTER_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include necessary files
require_once WP_CONTENT_IMPORTER_PLUGIN_DIR . 'includes/class-wp-content-importer.php';
require_once WP_CONTENT_IMPORTER_PLUGIN_DIR . 'includes/class-updater.php';

// Initialize the updater
if (is_admin()) {
    error_log('WP Content Importer: Initializing in admin context');
    $updater = new WP_Content_Importer_Updater(__FILE__);
    
    // Force update check
    delete_site_transient('update_plugins');
    wp_update_plugins();
}

// Initialize the plugin
function wp_content_importer_init() {
    $plugin = WP_Content_Importer::get_instance();
    $plugin->init();
}
add_action('plugins_loaded', 'wp_content_importer_init');

// Activation hook
register_activation_hook(__FILE__, 'wp_content_importer_activate');
function wp_content_importer_activate() {
    // Activation tasks (create necessary database tables, etc.)
    error_log('WP Content Importer: Plugin activated');
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'wp_content_importer_deactivate');
function wp_content_importer_deactivate() {
    // Deactivation tasks
    error_log('WP Content Importer: Plugin deactivated');
} 