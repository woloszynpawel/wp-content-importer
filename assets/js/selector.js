/**
 * WP Content Importer - Visual Selector
 */
(function() {
    // Check if we're in the iframe context
    if (window.self === window.top) {
        return;
    }
    
    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        initializeSelector();
    });
    
    /**
     * Initialize the selector
     */
    function initializeSelector() {
        // Create overlay container
        const overlay = document.createElement('div');
        overlay.id = 'wp-content-importer-overlay';
        overlay.style.position = 'fixed';
        overlay.style.top = '0';
        overlay.style.left = '0';
        overlay.style.width = '100%';
        overlay.style.height = '50px';
        overlay.style.backgroundColor = 'rgba(0, 0, 0, 0.8)';
        overlay.style.color = 'white';
        overlay.style.zIndex = '999999';
        overlay.style.display = 'flex';
        overlay.style.alignItems = 'center';
        overlay.style.justifyContent = 'space-between';
        overlay.style.padding = '0 20px';
        overlay.style.boxSizing = 'border-box';
        overlay.style.fontFamily = 'Arial, sans-serif';
        document.body.appendChild(overlay);
        
        // Create overlay content
        overlay.innerHTML = `
            <div>
                <strong>Select elements:</strong>
                <select id="wp-content-importer-selector-type">
                    <option value="title">Title</option>
                    <option value="content">Content</option>
                    <option value="featured_image">Featured Image</option>
                    <option value="category">Category</option>
                </select>
                <button id="wp-content-importer-save">Save Selectors</button>
            </div>
            <div>
                <button id="wp-content-importer-cancel">Cancel</button>
            </div>
        `;
        
        // Create hover element
        const hoverElement = document.createElement('div');
        hoverElement.id = 'wp-content-importer-hover';
        hoverElement.style.position = 'absolute';
        hoverElement.style.border = '2px dashed red';
        hoverElement.style.backgroundColor = 'rgba(255, 0, 0, 0.1)';
        hoverElement.style.pointerEvents = 'none';
        hoverElement.style.zIndex = '999998';
        hoverElement.style.display = 'none';
        document.body.appendChild(hoverElement);
        
        // Create label element
        const labelElement = document.createElement('div');
        labelElement.id = 'wp-content-importer-label';
        labelElement.style.position = 'absolute';
        labelElement.style.backgroundColor = 'black';
        labelElement.style.color = 'white';
        labelElement.style.padding = '5px 10px';
        labelElement.style.borderRadius = '3px';
        labelElement.style.fontSize = '12px';
        labelElement.style.pointerEvents = 'none';
        labelElement.style.zIndex = '999999';
        labelElement.style.display = 'none';
        document.body.appendChild(labelElement);
        
        // Get elements
        const selectorType = document.getElementById('wp-content-importer-selector-type');
        const saveButton = document.getElementById('wp-content-importer-save');
        const cancelButton = document.getElementById('wp-content-importer-cancel');
        
        // Store selected elements
        const selectedElements = {
            title: null,
            content: null,
            featured_image: null,
            category: null
        };
        
        // Store XPath selectors
        const selectors = {
            title: '',
            content: '',
            featured_image: '',
            category: ''
        };
        
        // Attach event listeners
        document.addEventListener('mouseover', handleMouseOver);
        document.addEventListener('mouseout', handleMouseOut);
        document.addEventListener('click', handleClick);
        
        saveButton.addEventListener('click', handleSave);
        cancelButton.addEventListener('click', handleCancel);
        
        /**
         * Handle mouseover event
         */
        function handleMouseOver(event) {
            const target = event.target;
            
            // Skip overlay elements
            if (isOverlayElement(target)) {
                return;
            }
            
            // Update hover element
            const rect = target.getBoundingClientRect();
            hoverElement.style.top = rect.top + window.scrollY + 'px';
            hoverElement.style.left = rect.left + window.scrollX + 'px';
            hoverElement.style.width = rect.width + 'px';
            hoverElement.style.height = rect.height + 'px';
            hoverElement.style.display = 'block';
            
            // Update label element
            labelElement.textContent = getElementDescription(target);
            labelElement.style.top = (rect.top + window.scrollY - 25) + 'px';
            labelElement.style.left = rect.left + window.scrollX + 'px';
            labelElement.style.display = 'block';
        }
        
        /**
         * Handle mouseout event
         */
        function handleMouseOut(event) {
            hoverElement.style.display = 'none';
            labelElement.style.display = 'none';
        }
        
        /**
         * Handle click event
         */
        function handleClick(event) {
            const target = event.target;
            
            // Skip overlay elements
            if (isOverlayElement(target)) {
                return;
            }
            
            // Prevent default behavior
            event.preventDefault();
            
            // Get current selector type
            const type = selectorType.value;
            
            // Store selected element
            selectedElements[type] = target;
            
            // Generate XPath for the element
            selectors[type] = getXPath(target);
            
            // Update overlay to show selected element
            showSelectedElement(type, target);
        }
        
        /**
         * Handle save button click
         */
        function handleSave() {
            // Send message to parent window
            window.parent.postMessage({
                action: 'wp_content_importer_save_selectors',
                selectors: selectors
            }, '*');
        }
        
        /**
         * Handle cancel button click
         */
        function handleCancel() {
            // Send message to parent window
            window.parent.postMessage({
                action: 'wp_content_importer_cancel'
            }, '*');
        }
        
        /**
         * Check if element is part of the overlay
         */
        function isOverlayElement(element) {
            return element.closest('#wp-content-importer-overlay') !== null ||
                element.closest('#wp-content-importer-hover') !== null ||
                element.closest('#wp-content-importer-label') !== null;
        }
        
        /**
         * Get element description
         */
        function getElementDescription(element) {
            let description = element.tagName.toLowerCase();
            
            if (element.id) {
                description += '#' + element.id;
            }
            
            if (element.className) {
                const classes = element.className.split(/\s+/).filter(c => c.trim() !== '');
                if (classes.length) {
                    description += '.' + classes.join('.');
                }
            }
            
            return description;
        }
        
        /**
         * Show selected element in the overlay
         */
        function showSelectedElement(type, element) {
            const typeLabel = type.replace('_', ' ');
            const notification = document.createElement('div');
            notification.style.position = 'fixed';
            notification.style.top = '60px';
            notification.style.right = '20px';
            notification.style.backgroundColor = 'rgba(0, 128, 0, 0.8)';
            notification.style.color = 'white';
            notification.style.padding = '10px 20px';
            notification.style.borderRadius = '3px';
            notification.style.zIndex = '999999';
            notification.textContent = typeLabel.charAt(0).toUpperCase() + typeLabel.slice(1) + ' element selected';
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 2000);
        }
        
        /**
         * Generate XPath for element
         */
        function getXPath(element) {
            // Try to use ID if available
            if (element.id) {
                return `//*[@id='${element.id}']`;
            }
            
            // Try to use unique class if available
            if (element.className) {
                const classes = element.className.split(/\s+/);
                for (const className of classes) {
                    const elements = document.getElementsByClassName(className);
                    if (elements.length === 1) {
                        return `//*[contains(@class, '${className}')]`;
                    }
                }
            }
            
            // Generate path based on element hierarchy with position
            const paths = [];
            while (element !== document.body && element.parentNode) {
                let nodeName = element.nodeName.toLowerCase();
                let position = 1;
                let sibling = element;
                
                // Count previous siblings of same type
                while (sibling = sibling.previousElementSibling) {
                    if (sibling.nodeName.toLowerCase() === nodeName) {
                        position++;
                    }
                }
                
                // Add node to path
                if (position === 1) {
                    paths.unshift(nodeName);
                } else {
                    paths.unshift(`${nodeName}[${position}]`);
                }
                
                element = element.parentNode;
            }
            
            return '//' + paths.join('/');
        }
    }
})(); 