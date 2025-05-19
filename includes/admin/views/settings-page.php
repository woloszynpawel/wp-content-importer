<div class="wrap">
    <h1><?php echo esc_html__('Content Importer Settings', 'wp-content-importer'); ?></h1>
    
    <form method="post" action="options.php" class="wp-content-importer-settings-form">
        <?php settings_fields('wp_content_importer_settings'); ?>
        <?php do_settings_sections('wp_content_importer_settings'); ?>
        
        <p class="submit">
            <?php submit_button(); ?>
        </p>
    </form>
</div> 