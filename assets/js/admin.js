/**
 * WP Content Importer - Admin JS
 */
(function($) {
    // Initialize when DOM is ready
    $(document).ready(function() {
        initializeImporter();
    });
    
    /**
     * Initialize the importer
     */
    function initializeImporter() {
        const $form = $('#wp-content-importer-form');
        const $urlInput = $('#wp-content-importer-url');
        const $previewButton = $('#wp-content-importer-preview');
        const $iframeContainer = $('#wp-content-importer-iframe-container');
        const $iframe = $('#wp-content-importer-iframe');
        const $selectorsContainer = $('#wp-content-importer-selectors-container');
        const $importButton = $('#wp-content-importer-import');
        const $loadingOverlay = $('#wp-content-importer-loading');
        const $notification = $('#wp-content-importer-notification');
        
        // Store selectors
        let currentSelectors = {
            title: '',
            content: '',
            featured_image: '',
            category: ''
        };
        
        // Attach event listeners
        $previewButton.on('click', handlePreview);
        $importButton.on('click', handleImport);
        
        // Listen for messages from iframe
        window.addEventListener('message', handleIframeMessage);
        
        /**
         * Handle preview button click
         */
        function handlePreview(e) {
            e.preventDefault();
            
            const url = $urlInput.val();
            
            if (!url) {
                showNotification('Please enter a URL', 'error');
                return;
            }
            
            // Show loading overlay
            showLoading('Loading preview...');
            
            // Get page content via AJAX
            $.ajax({
                url: wp_content_importer_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'wp_content_importer_preview',
                    nonce: wp_content_importer_params.nonce,
                    url: url
                },
                success: function(response) {
                    if (response.success) {
                        // Show iframe container
                        $iframeContainer.show();
                        
                        // Write HTML to iframe
                        const iframeDoc = $iframe[0].contentDocument || $iframe[0].contentWindow.document;
                        iframeDoc.open();
                        iframeDoc.write(response.data.html);
                        iframeDoc.close();
                        
                        // Hide loading overlay
                        hideLoading();
                    } else {
                        hideLoading();
                        showNotification(response.data.message, 'error');
                    }
                },
                error: function() {
                    hideLoading();
                    showNotification('Error loading preview. Please try again.', 'error');
                }
            });
        }
        
        /**
         * Handle iframe messages
         */
        function handleIframeMessage(event) {
            // Check if message is from our iframe
            if (!event.source === $iframe[0].contentWindow) {
                return;
            }
            
            const message = event.data;
            
            if (message.action === 'wp_content_importer_save_selectors') {
                // Save selectors
                currentSelectors = message.selectors;
                
                // Hide iframe
                $iframeContainer.hide();
                
                // Show selectors
                showSelectors();
                
                // Show notification
                showNotification('Selectors saved successfully', 'success');
                
                // Save selectors to server
                saveSelectorsToServer();
            } else if (message.action === 'wp_content_importer_cancel') {
                // Hide iframe
                $iframeContainer.hide();
            }
        }
        
        /**
         * Save selectors to server
         */
        function saveSelectorsToServer() {
            const url = $urlInput.val();
            
            $.ajax({
                url: wp_content_importer_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'wp_content_importer_save_selectors',
                    nonce: wp_content_importer_params.nonce,
                    url: url,
                    selectors: currentSelectors
                },
                success: function(response) {
                    if (!response.success) {
                        showNotification(response.data.message, 'error');
                    }
                },
                error: function() {
                    showNotification('Error saving selectors. Please try again.', 'error');
                }
            });
        }
        
        /**
         * Show selectors in the UI
         */
        function showSelectors() {
            // Clear container
            $selectorsContainer.empty();
            
            // Create table
            const $table = $('<table class="widefat" />');
            const $thead = $('<thead />');
            const $tbody = $('<tbody />');
            
            // Add header
            $thead.append('<tr><th>Element</th><th>XPath</th></tr>');
            
            // Add rows
            for (const [key, value] of Object.entries(currentSelectors)) {
                if (value) {
                    const label = key.replace('_', ' ');
                    const labelCapitalized = label.charAt(0).toUpperCase() + label.slice(1);
                    
                    $tbody.append(`<tr><td>${labelCapitalized}</td><td><code>${value}</code></td></tr>`);
                }
            }
            
            // Append table parts
            $table.append($thead).append($tbody);
            
            // Append to container
            $selectorsContainer.append($table);
            
            // Show import button
            $importButton.show();
        }
        
        /**
         * Handle import button click
         */
        function handleImport(e) {
            e.preventDefault();
            
            const url = $urlInput.val();
            
            if (!url) {
                showNotification('Please enter a URL', 'error');
                return;
            }
            
            // Show loading overlay
            showLoading('Importing content...');
            
            // Import content via AJAX
            $.ajax({
                url: wp_content_importer_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'wp_content_importer_import_content',
                    nonce: wp_content_importer_params.nonce,
                    url: url
                },
                success: function(response) {
                    hideLoading();
                    
                    if (response.success) {
                        showNotification('Content imported successfully!', 'success');
                        
                        // Add link to edit post
                        $selectorsContainer.append(`
                            <div class="notice notice-success">
                                <p>Content imported successfully! <a href="${response.data.edit_url}">Edit Post</a></p>
                            </div>
                        `);
                    } else {
                        showNotification(response.data.message, 'error');
                    }
                },
                error: function() {
                    hideLoading();
                    showNotification('Error importing content. Please try again.', 'error');
                }
            });
        }
        
        /**
         * Show loading overlay
         */
        function showLoading(message) {
            $loadingOverlay.find('p').text(message);
            $loadingOverlay.show();
        }
        
        /**
         * Hide loading overlay
         */
        function hideLoading() {
            $loadingOverlay.hide();
        }
        
        /**
         * Show notification
         */
        function showNotification(message, type) {
            // Set message and type
            $notification.text(message);
            $notification.attr('class', `notice notice-${type} inline`);
            
            // Show notification
            $notification.show();
            
            // Hide after 3 seconds
            setTimeout(function() {
                $notification.hide();
            }, 3000);
        }
    }
})(jQuery); 