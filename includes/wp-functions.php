<?php
/**
 * WordPress functions compatibility layer
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Make sure we're in WordPress context
if (!function_exists('add_action')) {
    require_once(ABSPATH . 'wp-includes/plugin.php');
}

if (!function_exists('wp_content_importer_check_wordpress_functions')) {
    function wp_content_importer_check_wordpress_functions() {
        $required_functions = array(
            'add_action',
            'add_filter',
            'add_menu_page',
            'add_submenu_page',
            'admin_url',
            'check_admin_referer',
            'current_user_can',
            'esc_attr',
            'esc_html',
            'esc_url_raw',
            'get_admin_page_title',
            'get_option',
            'plugin_dir_path',
            'plugin_dir_url',
            'register_activation_hook',
            'register_deactivation_hook',
            'sanitize_text_field',
            'wp_create_nonce',
            'wp_enqueue_script',
            'wp_enqueue_style',
            'wp_localize_script',
            'wp_remote_get',
            'wp_send_json_error',
            'wp_send_json_success',
            '__'
        );

        $missing_functions = array();
        foreach ($required_functions as $function) {
            if (!function_exists($function)) {
                $missing_functions[] = $function;
            }
        }

        if (!empty($missing_functions)) {
            error_log('WP Content Importer: Missing required WordPress functions: ' . implode(', ', $missing_functions));
            return false;
        }

        return true;
    }
}

// Check WordPress functions availability
if (!wp_content_importer_check_wordpress_functions()) {
    error_log('WP Content Importer: Plugin initialization aborted due to missing WordPress functions');
    return;
}

// Add compatibility layer for missing functions if needed
if (!function_exists('wp_content_importer_get_debug_log')) {
    function wp_content_importer_get_debug_log() {
        $log_file = WP_CONTENT_DIR . '/debug.log';
        if (file_exists($log_file) && is_readable($log_file)) {
            return file_get_contents($log_file);
        }
        return false;
    }
} 