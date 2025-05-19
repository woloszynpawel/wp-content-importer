<div class="wrap">
    <h1><?php echo esc_html__('Content Importer', 'wp-content-importer'); ?></h1>
    
    <div id="wp-content-importer-notification" class="notice inline" style="display: none;"></div>
    
    <div class="wp-content-importer-form">
        <form id="wp-content-importer-form" method="post">
            <div class="form-group">
                <label for="wp-content-importer-url"><?php echo esc_html__('URL to Import From', 'wp-content-importer'); ?></label>
                <input type="text" id="wp-content-importer-url" name="url" placeholder="https://example.com/article-to-import" class="regular-text">
            </div>
            
            <div class="form-group">
                <button id="wp-content-importer-preview" class="button button-primary"><?php echo esc_html__('Preview & Select Elements', 'wp-content-importer'); ?></button>
                <button id="wp-content-importer-import" class="button" style="display: none;"><?php echo esc_html__('Import Content', 'wp-content-importer'); ?></button>
            </div>
        </form>
    </div>
    
    <div id="wp-content-importer-iframe-container" class="wp-content-importer-iframe-container" style="display: none;">
        <iframe id="wp-content-importer-iframe" class="wp-content-importer-iframe" frameborder="0"></iframe>
    </div>
    
    <div id="wp-content-importer-selectors-container" class="wp-content-importer-selectors-container">
        <!-- Selected elements will be displayed here -->
    </div>
    
    <div id="wp-content-importer-loading" class="wp-content-importer-loading" style="display: none;">
        <div class="wp-content-importer-loading-content">
            <div class="wp-content-importer-loading-spinner"></div>
            <p><?php echo esc_html__('Loading...', 'wp-content-importer'); ?></p>
        </div>
    </div>
</div> 