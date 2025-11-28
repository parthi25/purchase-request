/**
 * Page Loader Utility
 * Provides functions to show/hide the page loader for async operations
 */

(function() {
    'use strict';
    
    const pageLoader = document.getElementById('pageLoader');
    const pageContent = document.getElementById('pageContent');
    
    /**
     * Show the page loader (skeleton)
     */
    function showLoader() {
        if (pageLoader) {
            pageLoader.classList.remove('hidden');
        }
        if (pageContent) {
            pageContent.classList.add('hidden');
        }
    }
    
    /**
     * Hide the page loader and show content
     */
    function hideLoader() {
        if (pageLoader) {
            pageLoader.classList.add('hidden');
        }
        if (pageContent) {
            pageContent.classList.remove('hidden');
        }
    }
    
    /**
     * Check if loader is currently visible
     */
    function isLoaderVisible() {
        return pageLoader && !pageLoader.classList.contains('hidden');
    }
    
    // Expose functions globally
    window.PageLoader = {
        show: showLoader,
        hide: hideLoader,
        isVisible: isLoaderVisible
    };
    
    // Also expose as hidePageLoader for backward compatibility
    window.hidePageLoader = hideLoader;
    window.showPageLoader = showLoader;
    
})();

