<?php
/**
 * Admin class
 */
class WP_Content_Importer_Admin {
    /**
     * Initialize the admin
     */
    public function init() {
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Register settings
        add_action('admin_init', array($this, 'register_settings'));
        
        // Add AJAX handlers
        add_action('wp_ajax_wp_content_importer_preview', array($this, 'ajax_preview_page'));
        add_action('wp_ajax_wp_content_importer_save_selectors', array($this, 'ajax_save_selectors'));
        add_action('wp_ajax_wp_content_importer_import_content', array($this, 'ajax_import_content'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Content Importer', 'wp-content-importer'),
            __('Content Importer', 'wp-content-importer'),
            'manage_options',
            'wp-content-importer',
            array($this, 'render_main_page'),
            'dashicons-download',
            30
        );
        
        add_submenu_page(
            'wp-content-importer',
            __('Import Content', 'wp-content-importer'),
            __('Import Content', 'wp-content-importer'),
            'manage_options',
            'wp-content-importer',
            array($this, 'render_main_page')
        );
        
        add_submenu_page(
            'wp-content-importer',
            __('Settings', 'wp-content-importer'),
            __('Settings', 'wp-content-importer'),
            'manage_options',
            'wp-content-importer-settings',
            array($this, 'render_settings_page')
        );
        
        add_submenu_page(
            'wp-content-importer',
            __('Import Queue', 'wp-content-importer'),
            __('Import Queue', 'wp-content-importer'),
            'manage_options',
            'wp-content-importer-queue',
            array($this, 'render_queue_page')
        );

        // Add Debug page
        add_submenu_page(
            'wp-content-importer',
            __('Debug', 'wp-content-importer'),
            __('Debug', 'wp-content-importer'),
            'manage_options',
            'wp-content-importer-debug',
            array($this, 'render_debug_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('wp_content_importer_settings', 'wp_content_importer_settings');
        
        add_settings_section(
            'wp_content_importer_general_section',
            __('General Settings', 'wp-content-importer'),
            array($this, 'render_general_section'),
            'wp_content_importer_settings'
        );
        
        add_settings_field(
            'default_post_status',
            __('Default Post Status', 'wp-content-importer'),
            array($this, 'render_default_post_status_field'),
            'wp_content_importer_settings',
            'wp_content_importer_general_section'
        );
        
        add_settings_field(
            'default_category',
            __('Default Category', 'wp-content-importer'),
            array($this, 'render_default_category_field'),
            'wp_content_importer_settings',
            'wp_content_importer_general_section'
        );
    }
    
    /**
     * Render main page
     */
    public function render_main_page() {
        // Enqueue styles and scripts
        wp_enqueue_style('wp-content-importer-admin');
        wp_enqueue_script('wp-content-importer-admin');
        
        wp_localize_script('wp-content-importer-admin', 'wp_content_importer_params', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp-content-importer-nonce'),
        ));
        
        require_once WP_CONTENT_IMPORTER_PLUGIN_DIR . 'includes/admin/views/main-page.php';
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        require_once WP_CONTENT_IMPORTER_PLUGIN_DIR . 'includes/admin/views/settings-page.php';
    }
    
    /**
     * Render queue page
     */
    public function render_queue_page() {
        require_once WP_CONTENT_IMPORTER_PLUGIN_DIR . 'includes/admin/views/queue-page.php';
    }
    
    /**
     * Render debug page
     */
    public function render_debug_page() {
        // Handle form submission
        if (isset($_POST['test_selectors']) && check_admin_referer('wp_content_importer_test_selectors')) {
            $url = isset($_POST['test_url']) ? esc_url_raw($_POST['test_url']) : '';
            $selectors = isset($_POST['selectors']) ? array_map('sanitize_text_field', $_POST['selectors']) : array();
            
            if (!empty($url) && !empty($selectors)) {
                // Get content from URL
                $response = wp_remote_get($url);
                
                if (!is_wp_error($response)) {
                    $html = wp_remote_retrieve_body($response);
                    
                    // Process content
                    $processor = new WP_Content_Importer_Content_Processor();
                    $result = $processor->process($html, $selectors, $url);
                    
                    if (!is_wp_error($result)) {
                        add_settings_error(
                            'wp_content_importer_messages',
                            'test_success',
                            __('Test successful! Check the error log for details.', 'wp-content-importer'),
                            'updated'
                        );
                    } else {
                        add_settings_error(
                            'wp_content_importer_messages',
                            'test_failed',
                            $result->get_error_message(),
                            'error'
                        );
                    }
                } else {
                    add_settings_error(
                        'wp_content_importer_messages',
                        'fetch_failed',
                        $response->get_error_message(),
                        'error'
                    );
                }
            }
        }
        
        require_once WP_CONTENT_IMPORTER_PLUGIN_DIR . 'includes/admin/views/debug-page.php';
    }
    
    /**
     * Render general section
     */
    public function render_general_section() {
        echo '<p>' . __('Configure general settings for the content importer.', 'wp-content-importer') . '</p>';
    }
    
    /**
     * Render default post status field
     */
    public function render_default_post_status_field() {
        $options = get_option('wp_content_importer_settings', array());
        $default_post_status = isset($options['default_post_status']) ? $options['default_post_status'] : 'draft';
        
        echo '<select name="wp_content_importer_settings[default_post_status]">
            <option value="draft" ' . selected($default_post_status, 'draft', false) . '>' . __('Draft', 'wp-content-importer') . '</option>
            <option value="publish" ' . selected($default_post_status, 'publish', false) . '>' . __('Published', 'wp-content-importer') . '</option>
            <option value="pending" ' . selected($default_post_status, 'pending', false) . '>' . __('Pending Review', 'wp-content-importer') . '</option>
        </select>';
    }
    
    /**
     * Render default category field
     */
    public function render_default_category_field() {
        $options = get_option('wp_content_importer_settings', array());
        $default_category = isset($options['default_category']) ? $options['default_category'] : '';
        
        $categories = get_categories(array(
            'hide_empty' => false,
        ));
        
        echo '<select name="wp_content_importer_settings[default_category]">';
        echo '<option value="">' . __('Select a category', 'wp-content-importer') . '</option>';
        
        foreach ($categories as $category) {
            echo '<option value="' . esc_attr($category->term_id) . '" ' . selected($default_category, $category->term_id, false) . '>' . esc_html($category->name) . '</option>';
        }
        
        echo '</select>';
    }
    
    /**
     * AJAX preview page
     */
    public function ajax_preview_page() {
        check_ajax_referer('wp-content-importer-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'wp-content-importer')));
        }
        
        $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
        
        if (empty($url)) {
            wp_send_json_error(array('message' => __('URL is required.', 'wp-content-importer')));
        }
        
        // Get content from URL
        $response = wp_remote_get($url);
        
        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => $response->get_error_message()));
        }
        
        $body = wp_remote_retrieve_body($response);
        
        if (empty($body)) {
            wp_send_json_error(array('message' => __('Empty response from URL.', 'wp-content-importer')));
        }
        
        // Prepare the HTML for display in iframe
        $html = $this->prepare_html_for_iframe($body, $url);
        
        wp_send_json_success(array(
            'html' => $html,
        ));
    }
    
    /**
     * Prepare HTML for iframe
     */
    private function prepare_html_for_iframe($html, $base_url) {
        // Add base tag to resolve relative URLs
        $html = preg_replace('/<head>/i', '<head><base href="' . esc_url($base_url) . '">', $html, 1);
        
        // Add selector.js to the page
        $selector_script = '<script type="text/javascript" src="' . WP_CONTENT_IMPORTER_PLUGIN_URL . 'assets/js/selector.js"></script>';
        $html = preg_replace('/<\/body>/i', $selector_script . '</body>', $html, 1);
        
        return $html;
    }
    
    /**
     * AJAX save selectors
     */
    public function ajax_save_selectors() {
        check_ajax_referer('wp-content-importer-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'wp-content-importer')));
        }
        
        $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
        $selectors = isset($_POST['selectors']) ? $_POST['selectors'] : array();
        
        if (empty($url) || empty($selectors)) {
            wp_send_json_error(array('message' => __('URL and selectors are required.', 'wp-content-importer')));
        }
        
        // Sanitize selectors
        $sanitized_selectors = array();
        foreach ($selectors as $key => $selector) {
            $sanitized_selectors[$key] = sanitize_text_field($selector);
        }
        
        // Save selectors for this URL
        update_option('wp_content_importer_selectors_' . md5($url), $sanitized_selectors);
        
        wp_send_json_success(array('message' => __('Selectors saved successfully.', 'wp-content-importer')));
    }
    
    /**
     * AJAX import content
     */
    public function ajax_import_content() {
        check_ajax_referer('wp-content-importer-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'wp-content-importer')));
        }
        
        $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
        
        if (empty($url)) {
            wp_send_json_error(array('message' => __('URL is required.', 'wp-content-importer')));
        }
        
        // Get selectors for this URL
        $selectors = get_option('wp_content_importer_selectors_' . md5($url), array());
        
        if (empty($selectors)) {
            wp_send_json_error(array('message' => __('No selectors found for this URL.', 'wp-content-importer')));
        }
        
        // Validate selectors
        $required_selectors = array('title', 'content');
        foreach ($required_selectors as $required) {
            if (empty($selectors[$required])) {
                wp_send_json_error(array(
                    'message' => sprintf(__('Missing required selector: %s', 'wp-content-importer'), $required)
                ));
            }
        }
        
        // Get content from URL with proper timeout and user agent
        $args = array(
            'timeout' => 30,
            'user-agent' => 'Mozilla/5.0 (compatible; WP Content Importer/1.0)',
            'sslverify' => false
        );
        
        $response = wp_remote_get($url, $args);
        
        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => $response->get_error_message()));
        }
        
        $body = wp_remote_retrieve_body($response);
        
        if (empty($body)) {
            wp_send_json_error(array('message' => __('Empty response from URL.', 'wp-content-importer')));
        }
        
        // Process content using selectors
        require_once WP_CONTENT_IMPORTER_PLUGIN_DIR . 'includes/class-content-processor.php';
        $processor = new WP_Content_Importer_Content_Processor();
        $content = $processor->process($body, $selectors, $url);
        
        if (is_wp_error($content)) {
            wp_send_json_error(array('message' => $content->get_error_message()));
        }
        
        // Validate processed content
        if (empty($content['title']) || empty($content['content'])) {
            wp_send_json_error(array(
                'message' => __('Failed to extract content. Please verify your selectors.', 'wp-content-importer'),
                'debug' => array(
                    'title_length' => strlen($content['title']),
                    'content_length' => strlen($content['content']),
                    'selectors' => $selectors
                )
            ));
        }
        
        // Create post with processed content
        $post_id = $this->create_post($content);
        
        if (is_wp_error($post_id)) {
            wp_send_json_error(array('message' => $post_id->get_error_message()));
        }
        
        wp_send_json_success(array(
            'message' => __('Content imported successfully.', 'wp-content-importer'),
            'post_id' => $post_id,
            'edit_url' => get_edit_post_link($post_id, 'raw'),
        ));
    }
    
    /**
     * Create post with imported content
     */
    private function create_post($content) {
        // Get settings
        $options = get_option('wp_content_importer_settings', array());
        $default_post_status = isset($options['default_post_status']) ? $options['default_post_status'] : 'draft';
        $default_category = isset($options['default_category']) ? $options['default_category'] : '';
        
        // Prepare post data
        $post_data = array(
            'post_title'    => $content['title'],
            'post_content'  => $content['content'],
            'post_status'   => $default_post_status,
            'post_type'     => 'post',
        );
        
        // Add category if set
        if (!empty($default_category) && !empty($content['category'])) {
            $post_data['post_category'] = array($default_category);
        } elseif (!empty($content['category'])) {
            // Try to find or create the category
            $category = term_exists($content['category'], 'category');
            if (!$category) {
                $category = wp_insert_term($content['category'], 'category');
            }
            
            if (!is_wp_error($category)) {
                $post_data['post_category'] = array($category['term_id']);
            }
        } elseif (!empty($default_category)) {
            $post_data['post_category'] = array($default_category);
        }
        
        // Insert post
        $post_id = wp_insert_post($post_data, true);
        
        if (is_wp_error($post_id)) {
            return $post_id;
        }
        
        // Set featured image if available
        if (!empty($content['featured_image'])) {
            $this->set_featured_image($post_id, $content['featured_image']);
        }
        
        return $post_id;
    }
    
    /**
     * Set featured image for post
     */
    private function set_featured_image($post_id, $image_url) {
        // Download image
        $upload = $this->download_image($image_url);
        
        if (is_wp_error($upload)) {
            return $upload;
        }
        
        // Prepare attachment data
        $file_path = $upload['file'];
        $file_name = basename($file_path);
        $file_type = wp_check_filetype($file_name, null);
        $attachment_title = sanitize_file_name(pathinfo($file_name, PATHINFO_FILENAME));
        
        $attachment = array(
            'guid'           => $upload['url'],
            'post_mime_type' => $file_type['type'],
            'post_title'     => $attachment_title,
            'post_content'   => '',
            'post_status'    => 'inherit',
        );
        
        // Insert attachment
        $attachment_id = wp_insert_attachment($attachment, $file_path, $post_id);
        
        if (is_wp_error($attachment_id)) {
            return $attachment_id;
        }
        
        // Generate attachment metadata
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $attachment_data = wp_generate_attachment_metadata($attachment_id, $file_path);
        wp_update_attachment_metadata($attachment_id, $attachment_data);
        
        // Set as featured image
        set_post_thumbnail($post_id, $attachment_id);
        
        return $attachment_id;
    }
    
    /**
     * Download image from URL
     */
    private function download_image($url) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        
        // Download file to temp dir
        $temp_file = download_url($url);
        
        if (is_wp_error($temp_file)) {
            return $temp_file;
        }
        
        $file_array = array(
            'name'     => basename($url),
            'tmp_name' => $temp_file,
        );
        
        // Move the temporary file into the uploads directory
        $results = wp_handle_sideload($file_array, array('test_form' => false));
        
        if (isset($results['error'])) {
            @unlink($file_array['tmp_name']);
            return new WP_Error('upload_error', $results['error']);
        }
        
        return $results;
    }
} 