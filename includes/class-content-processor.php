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
            return new WP_Error('invalid_input', __('Invalid input data.', 'wp-content-importer'));
        }
        
        // Debug info
        error_log('Processing content with selectors: ' . print_r($selectors, true));
        
        // Load HTML into DOMDocument
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        
        // Detect character encoding from meta tags or headers
        $encoding = 'UTF-8';
        if (preg_match('/<meta[^>]+charset=[\'"]*([^"\'>]+)/i', $html, $matches)) {
            $encoding = $matches[1];
        }
        
        // Convert HTML to UTF-8 if needed
        if (strtoupper($encoding) !== 'UTF-8') {
            $html = mb_convert_encoding($html, 'HTML-ENTITIES', $encoding);
        }
        
        // Load HTML with proper flags
        $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        
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
            error_log('Processing title with selector: ' . $selectors['title']);
            $title_nodes = $xpath->query($selectors['title']);
            if ($title_nodes && $title_nodes->length > 0) {
                $title_text = trim($title_nodes->item(0)->textContent);
                if (!empty($title_text)) {
                    $content['title'] = $title_text;
                    error_log('Found title: ' . $content['title']);
                } else {
                    error_log('Title node found but content is empty');
                }
            } else {
                error_log('No nodes found for title selector: ' . $selectors['title']);
            }
        }
        
        // Process content
        if (!empty($selectors['content'])) {
            error_log('Processing content with selector: ' . $selectors['content']);
            $content_nodes = $xpath->query($selectors['content']);
            if ($content_nodes && $content_nodes->length > 0) {
                $content_node = $content_nodes->item(0);
                $content_html = $dom->saveHTML($content_node);
                if (!empty($content_html)) {
                    $content['content'] = $this->process_content_html($content_html, $base_url);
                    error_log('Found content length: ' . strlen($content['content']));
                    
                    // Validate content is not just whitespace
                    if (trim(strip_tags($content['content'])) === '') {
                        error_log('Content appears to be empty after stripping tags');
                        $content['content'] = '';
                    }
                } else {
                    error_log('Content node found but HTML is empty');
                }
            } else {
                error_log('No nodes found for content selector: ' . $selectors['content']);
            }
        }
        
        // Process featured image
        if (!empty($selectors['featured_image'])) {
            $featured_image_nodes = $xpath->query($selectors['featured_image']);
            if ($featured_image_nodes && $featured_image_nodes->length > 0) {
                $img_node = $featured_image_nodes->item(0);
                if ($img_node->nodeName === 'img') {
                    $src = $img_node->getAttribute('src');
                    $content['featured_image'] = $this->resolve_url($src, $base_url);
                } else {
                    // Try to find img inside the node
                    $img_subnodes = $xpath->query('.//img', $img_node);
                    if ($img_subnodes && $img_subnodes->length > 0) {
                        $src = $img_subnodes->item(0)->getAttribute('src');
                        $content['featured_image'] = $this->resolve_url($src, $base_url);
                    }
                }
            }
        }
        
        // Process category
        if (!empty($selectors['category'])) {
            $category_nodes = $xpath->query($selectors['category']);
            if ($category_nodes && $category_nodes->length > 0) {
                $content['category'] = $category_nodes->item(0)->textContent;
            }
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