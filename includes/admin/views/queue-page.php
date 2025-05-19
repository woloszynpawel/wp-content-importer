<div class="wrap">
    <h1><?php echo esc_html__('Import Queue', 'wp-content-importer'); ?></h1>
    
    <div class="wp-content-importer-form">
        <form id="wp-content-importer-queue-form" method="post">
            <div class="form-group">
                <label for="wp-content-importer-urls"><?php echo esc_html__('URLs to Import (one per line)', 'wp-content-importer'); ?></label>
                <textarea id="wp-content-importer-urls" name="urls" rows="5" class="large-text"></textarea>
            </div>
            
            <div class="form-group">
                <button id="wp-content-importer-add-to-queue" class="button button-primary"><?php echo esc_html__('Add to Queue', 'wp-content-importer'); ?></button>
            </div>
        </form>
    </div>
    
    <h2><?php echo esc_html__('Current Queue', 'wp-content-importer'); ?></h2>
    
    <div class="wp-content-importer-queue-table">
        <table class="widefat">
            <thead>
                <tr>
                    <th><?php echo esc_html__('URL', 'wp-content-importer'); ?></th>
                    <th><?php echo esc_html__('Status', 'wp-content-importer'); ?></th>
                    <th><?php echo esc_html__('Added on', 'wp-content-importer'); ?></th>
                    <th><?php echo esc_html__('Processed on', 'wp-content-importer'); ?></th>
                    <th><?php echo esc_html__('Actions', 'wp-content-importer'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $queue_items = get_option('wp_content_importer_queue', array());
                
                if (empty($queue_items)) {
                    echo '<tr><td colspan="5">' . esc_html__('No items in queue', 'wp-content-importer') . '</td></tr>';
                } else {
                    foreach ($queue_items as $id => $item) {
                        $status_class = 'status-' . $item['status'];
                        $status_text = ucfirst($item['status']);
                        
                        echo '<tr>';
                        echo '<td>' . esc_url($item['url']) . '</td>';
                        echo '<td><span class="' . esc_attr($status_class) . '">' . esc_html($status_text) . '</span></td>';
                        echo '<td>' . esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $item['added_on'])) . '</td>';
                        echo '<td>' . (empty($item['processed_on']) ? '-' : esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $item['processed_on']))) . '</td>';
                        echo '<td>';
                        
                        if ($item['status'] === 'pending') {
                            echo '<a href="' . esc_url(add_query_arg(array('action' => 'process', 'id' => $id))) . '" class="button button-small">' . esc_html__('Process Now', 'wp-content-importer') . '</a> ';
                        }
                        
                        if ($item['status'] === 'completed' && !empty($item['post_id'])) {
                            echo '<a href="' . esc_url(get_edit_post_link($item['post_id'])) . '" class="button button-small">' . esc_html__('Edit Post', 'wp-content-importer') . '</a> ';
                        }
                        
                        echo '<a href="' . esc_url(add_query_arg(array('action' => 'delete', 'id' => $id))) . '" class="button button-small">' . esc_html__('Remove', 'wp-content-importer') . '</a>';
                        
                        echo '</td>';
                        echo '</tr>';
                    }
                }
                ?>
            </tbody>
        </table>
    </div>
    
    <div class="wp-content-importer-queue-actions">
        <form method="post">
            <input type="hidden" name="action" value="process_all">
            <button type="submit" class="button"><?php echo esc_html__('Process All Pending Items', 'wp-content-importer'); ?></button>
        </form>
    </div>
</div>

<script type="text/javascript">
    jQuery(document).ready(function($) {
        // Handle form submission
        $('#wp-content-importer-queue-form').on('submit', function(e) {
            e.preventDefault();
            
            var urls = $('#wp-content-importer-urls').val();
            
            if (!urls) {
                alert('<?php echo esc_js(__('Please enter at least one URL', 'wp-content-importer')); ?>');
                return;
            }
            
            // Submit form via AJAX
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'wp_content_importer_add_to_queue',
                    nonce: '<?php echo wp_create_nonce('wp-content-importer-queue-nonce'); ?>',
                    urls: urls
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function() {
                    alert('<?php echo esc_js(__('Error adding to queue', 'wp-content-importer')); ?>');
                }
            });
        });
    });
</script> 