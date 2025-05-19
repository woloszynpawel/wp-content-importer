<?php
/**
 * Debug page view
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="notice notice-info">
        <p><?php _e('Here you can test your selectors and see detailed error logs.', 'wp-content-importer'); ?></p>
    </div>

    <div class="card">
        <h2><?php _e('Test Selectors', 'wp-content-importer'); ?></h2>
        <form method="post" action="">
            <?php wp_nonce_field('wp_content_importer_test_selectors'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="test_url"><?php _e('URL to Test', 'wp-content-importer'); ?></label></th>
                    <td>
                        <input type="url" id="test_url" name="test_url" class="regular-text" required>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="title_selector"><?php _e('Title Selector', 'wp-content-importer'); ?></label></th>
                    <td>
                        <input type="text" id="title_selector" name="selectors[title]" class="regular-text" required>
                        <p class="description"><?php _e('XPath selector for the title element', 'wp-content-importer'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="content_selector"><?php _e('Content Selector', 'wp-content-importer'); ?></label></th>
                    <td>
                        <input type="text" id="content_selector" name="selectors[content]" class="regular-text" required>
                        <p class="description"><?php _e('XPath selector for the content element', 'wp-content-importer'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="featured_image_selector"><?php _e('Featured Image Selector', 'wp-content-importer'); ?></label></th>
                    <td>
                        <input type="text" id="featured_image_selector" name="selectors[featured_image]" class="regular-text">
                        <p class="description"><?php _e('XPath selector for the featured image element', 'wp-content-importer'); ?></p>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="test_selectors" class="button button-primary" value="<?php _e('Test Selectors', 'wp-content-importer'); ?>">
            </p>
        </form>
    </div>

    <div class="card">
        <h2><?php _e('Error Log', 'wp-content-importer'); ?></h2>
        <div class="error-log-viewer" style="background: #fff; padding: 10px; max-height: 400px; overflow-y: auto;">
            <?php
            $log_file = WP_CONTENT_DIR . '/debug.log';
            if (file_exists($log_file)) {
                $log_content = file_get_contents($log_file);
                $log_entries = array_filter(explode("\n", $log_content), function($line) {
                    return strpos($line, 'WP Content Importer:') !== false;
                });
                echo '<pre>' . esc_html(implode("\n", array_slice($log_entries, -50))) . '</pre>';
            } else {
                _e('No log entries found.', 'wp-content-importer');
            }
            ?>
        </div>
    </div>
</div> 