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

// Load WordPress constants first
require_once(dirname(__FILE__) . '/includes/wp-constants.php');

// If this file is called directly, abort
if (!defined('ABSPATH')) {
    die('Direct access is not allowed.');
}

// Load WordPress compatibility layer
require_once(dirname(__FILE__) . '/includes/wp-functions.php');

// Make sure we have access to WordPress functions
require_once(ABSPATH . 'wp-admin/includes/plugin.php');
require_once(ABSPATH . 'wp-includes/pluggable.php');
require_once(ABSPATH . 'wp-admin/includes/file.php');

// Define plugin constants
define('WP_CONTENT_IMPORTER_VERSION', '1.0.1');
define('WP_CONTENT_IMPORTER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_CONTENT_IMPORTER_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include necessary files
require_once WP_CONTENT_IMPORTER_PLUGIN_DIR . 'includes/class-wp-content-importer.php';
require_once WP_CONTENT_IMPORTER_PLUGIN_DIR . 'includes/class-content-processor.php';
require_once WP_CONTENT_IMPORTER_PLUGIN_DIR . 'includes/class-updater.php';

// Initialize error logging
if (!function_exists('wp_content_importer_error_log')) {
    function wp_content_importer_error_log($message) {
        if (defined('WP_DEBUG') && WP_DEBUG === true) {
            if (is_array($message) || is_object($message)) {
                error_log('WP Content Importer: ' . print_r($message, true));
            } else {
                error_log('WP Content Importer: ' . $message);
            }
        }
    }
}

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
    // Check if all required WordPress functions are available
    if (!function_exists('wp_content_importer_check_wordpress_functions') || 
        !wp_content_importer_check_wordpress_functions()) {
        wp_content_importer_error_log('Required WordPress functions not available');
        return;
    }

    if (!class_exists('WP_Content_Importer')) {
        wp_content_importer_error_log('Required class WP_Content_Importer not found');
        return;
    }
    
    $plugin = WP_Content_Importer::get_instance();
    $plugin->init();
}

// Hook into WordPress
add_action('plugins_loaded', 'wp_content_importer_init');

// Activation hook
register_activation_hook(__FILE__, 'wp_content_importer_activate');
function wp_content_importer_activate() {
    // Check if all required WordPress functions are available
    if (!function_exists('wp_content_importer_check_wordpress_functions') || 
        !wp_content_importer_check_wordpress_functions()) {
        wp_die('Required WordPress functions not available. Plugin cannot be activated.');
    }

    // Activation tasks
    wp_content_importer_error_log('Plugin activated');
    
    // Create necessary database tables
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    global $wpdb;
    
    // Enable error logging
    $wpdb->show_errors();
    
    wp_content_importer_error_log('Plugin activation completed');
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'wp_content_importer_deactivate');
function wp_content_importer_deactivate() {
    wp_content_importer_error_log('Plugin deactivated');
} 