<?php
/**
 * Main plugin class
 */
class WP_Content_Importer {
    /**
     * Plugin instance
     */
    private static $instance = null;

    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize the plugin
     */
    public function init() {
        // Load plugin text domain
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        
        // Admin hooks
        if (is_admin()) {
            require_once WP_CONTENT_IMPORTER_PLUGIN_DIR . 'includes/admin/class-admin.php';
            $admin = new WP_Content_Importer_Admin();
            $admin->init();
        }

        // Register scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'register_assets'));
    }

    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'wp-content-importer',
            false,
            dirname(plugin_basename(WP_CONTENT_IMPORTER_PLUGIN_DIR)) . '/languages/'
        );
    }

    /**
     * Register scripts and styles
     */
    public function register_assets() {
        // Register styles
        wp_register_style(
            'wp-content-importer-admin',
            WP_CONTENT_IMPORTER_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            WP_CONTENT_IMPORTER_VERSION
        );

        // Register scripts
        wp_register_script(
            'wp-content-importer-admin',
            WP_CONTENT_IMPORTER_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            WP_CONTENT_IMPORTER_VERSION,
            true
        );

        wp_register_script(
            'wp-content-importer-selector',
            WP_CONTENT_IMPORTER_PLUGIN_URL . 'assets/js/selector.js',
            array('jquery'),
            WP_CONTENT_IMPORTER_VERSION,
            true
        );
    }
} 