<?php
/**
 * Content processor class
 */
class WP_Content_Importer_Content_Processor {
    /**
     * Process content using selectors
     */
    public function process($html, $selectors, $base_url) {
        if (empty($html) || empty($selectors)) {
            error_log('WP Content Importer: Empty input data');
            return new WP_Error('invalid_input', __('Invalid input data.', 'wp-content-importer'));
        }
        
        // Debug info
        error_log('WP Content Importer: Processing content with selectors: ' . print_r($selectors, true));
        
        // Load HTML into DOMDocument
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        
        // Convert HTML to UTF-8 and handle special characters
        $html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
        $html = '<?xml encoding="UTF-8">' . $html;
        
        // Load HTML with proper flags
        $success = $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING);
        if (!$success) {
            error_log('WP Content Importer: Failed to load HTML');
            return new WP_Error('html_load_failed', __('Failed to load HTML content.', 'wp-content-importer'));
        }
        
        $xpath = new DOMXPath($dom);
        
        // Extract content
        $content = array(
            'title' => '',
            'content' => '',
            'featured_image' => '',
            'category' => '',
            'images' => array(),
        );
        
        // Process title
        if (!empty($selectors['title'])) {
            error_log('WP Content Importer: Processing title with selector: ' . $selectors['title']);
            try {
                $title_nodes = $xpath->query($selectors['title']);
                if ($title_nodes && $title_nodes->length > 0) {
                    $title_text = trim($title_nodes->item(0)->textContent);
                    if (!empty($title_text)) {
                        $content['title'] = $title_text;
                        error_log('WP Content Importer: Found title: ' . $content['title']);
                    } else {
                        error_log('WP Content Importer: Title node found but content is empty');
                    }
                } else {
                    error_log('WP Content Importer: No nodes found for title selector');
                }
            } catch (Exception $e) {
                error_log('WP Content Importer: Error processing title: ' . $e->getMessage());
            }
        }
        
        // Process content
        if (!empty($selectors['content'])) {
            error_log('WP Content Importer: Processing content with selector: ' . $selectors['content']);
            try {
                $content_nodes = $xpath->query($selectors['content']);
                if ($content_nodes && $content_nodes->length > 0) {
                    $content_node = $content_nodes->item(0);
                    $content_html = $dom->saveHTML($content_node);
                    if (!empty($content_html)) {
                        $content['content'] = $this->process_content_html($content_html, $base_url);
                        error_log('WP Content Importer: Found content length: ' . strlen($content['content']));
                    } else {
                        error_log('WP Content Importer: Content node found but HTML is empty');
                    }
                } else {
                    error_log('WP Content Importer: No nodes found for content selector');
                }
            } catch (Exception $e) {
                error_log('WP Content Importer: Error processing content: ' . $e->getMessage());
            }
        }
        
        // Process featured image
        if (!empty($selectors['featured_image'])) {
            error_log('WP Content Importer: Processing featured image with selector: ' . $selectors['featured_image']);
            try {
                $img_nodes = $xpath->query($selectors['featured_image']);
                if ($img_nodes && $img_nodes->length > 0) {
                    $img_node = $img_nodes->item(0);
                    if ($img_node->nodeName === 'img') {
                        $src = $img_node->getAttribute('src');
                        $content['featured_image'] = $this->resolve_url($src, $base_url);
                        error_log('WP Content Importer: Found featured image: ' . $content['featured_image']);
                    } else {
                        // Try to find img inside the node
                        $img_subnodes = $xpath->query('.//img', $img_node);
                        if ($img_subnodes && $img_subnodes->length > 0) {
                            $src = $img_subnodes->item(0)->getAttribute('src');
                            $content['featured_image'] = $this->resolve_url($src, $base_url);
                            error_log('WP Content Importer: Found featured image (nested): ' . $content['featured_image']);
                        }
                    }
                } else {
                    error_log('WP Content Importer: No nodes found for featured image selector');
                }
            } catch (Exception $e) {
                error_log('WP Content Importer: Error processing featured image: ' . $e->getMessage());
            }
        }
        
        // Validate extracted content
        if (empty($content['title']) || empty($content['content'])) {
            error_log('WP Content Importer: Failed to extract required content');
            error_log('WP Content Importer: Title length: ' . strlen($content['title']));
            error_log('WP Content Importer: Content length: ' . strlen($content['content']));
            return new WP_Error('extraction_failed', __('Failed to extract content. Please verify your selectors.', 'wp-content-importer'));
        }
        
        return $content;
    }
    
    /**
     * Process content HTML
     */
    private function process_content_html($html, $base_url) {
        // Load HTML into DOMDocument
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();
        
        $xpath = new DOMXPath($dom);
        
        // Process images
        $img_nodes = $xpath->query('//img');
        $images = array();
        
        foreach ($img_nodes as $img_node) {
            $src = $img_node->getAttribute('src');
            
            if (empty($src)) {
                continue;
            }
            
            // Resolve relative URLs
            $full_url = $this->resolve_url($src, $base_url);
            
            // Add to images array
            $images[] = $full_url;
            
            // Add placeholder attribute for later replacement
            $img_node->setAttribute('data-wp-content-importer-src', $full_url);
        }
        
        // Save processed HTML
        $html = $dom->saveHTML($dom->documentElement);
        
        // Remove doctype, html and body tags
        $html = preg_replace('~<(?:!DOCTYPE|/?(?:html|body))[^>]*>\s*~i', '', $html);
        
        return $html;
    }
    
    /**
     * Resolve relative URL to absolute URL
     */
    private function resolve_url($url, $base_url) {
        // If already absolute URL
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }
        
        // If URL starts with //
        if (substr($url, 0, 2) === '//') {
            $base_parts = parse_url($base_url);
            return $base_parts['scheme'] . ':' . $url;
        }
        
        // If URL is relative
        return rtrim($base_url, '/') . '/' . ltrim($url, '/');
    }
    
    /**
     * Replace image URLs in content
     */
    public function replace_image_urls($content, $image_map) {
        // Load HTML into DOMDocument
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();
        
        $xpath = new DOMXPath($dom);
        
        // Process images
        $img_nodes = $xpath->query('//img[@data-wp-content-importer-src]');
        
        foreach ($img_nodes as $img_node) {
            $original_src = $img_node->getAttribute('data-wp-content-importer-src');
            
            if (isset($image_map[$original_src])) {
                $img_node->setAttribute('src', $image_map[$original_src]);
            }
            
            // Remove placeholder attribute
            $img_node->removeAttribute('data-wp-content-importer-src');
        }
        
        // Save processed HTML
        $html = $dom->saveHTML($dom->documentElement);
        
        // Remove doctype, html and body tags
        $html = preg_replace('~<(?:!DOCTYPE|/?(?:html|body))[^>]*>\s*~i', '', $html);
        
        return $html;
    }
} 