/**
 * Drawer/Sidebar interactions for Purchase Tracker
 * Handles the mini-sidebar toggle and responsive behavior
 */

// Wait for DOM to be fully loaded
function initSidebar() {
    const sidebar = document.getElementById('sidebarContent');
    if (!sidebar) {
        // Retry if sidebar not found yet
        setTimeout(initSidebar, 100);
        return;
    }
    
    const toggleBtns = document.querySelectorAll('label[for="my-drawer-4"]');
    const drawerCheckbox = document.getElementById('my-drawer-4');
    
    // Initialize tooltip behavior for collapsed state
    initializeTooltips();

    // Check local storage for preference (only on desktop)
    if (window.innerWidth >= 1024 && sidebar) {
        const isExpanded = localStorage.getItem('sidebarExpanded') === 'true';
        if (isExpanded) {
            sidebar.classList.remove('w-20');
            sidebar.classList.add('w-64');
            sidebar.classList.add('sidebar-expanded');
            document.body.classList.add('sidebar-expanded');
        } else {
            sidebar.classList.remove('w-64', 'sidebar-expanded');
            sidebar.classList.add('w-20');
            document.body.classList.remove('sidebar-expanded');
        }
    } else if (sidebar) {
        // Mobile: ensure collapsed state
        sidebar.classList.remove('w-64', 'sidebar-expanded');
        sidebar.classList.add('w-20');
    }

    // Handle desktop toggle button (button element, not label)
    const desktopToggle = document.getElementById('desktop-sidebar-toggle');
    if (desktopToggle) {
        // Remove any existing listeners by cloning (this also removes onclick attributes)
        const newToggle = desktopToggle.cloneNode(true);
        desktopToggle.parentNode.replaceChild(newToggle, desktopToggle);
        
        // Add single event listener
        newToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation(); // Prevent any other handlers
            if (window.innerWidth >= 1024) {
                toggleSidebar();
            }
            return false;
        }, true); // Use capture phase to ensure it fires first
    }
    
    // Add click handlers to toggle buttons (labels for mobile)
    toggleBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            // On mobile, let the default drawer behavior work (checkbox toggles drawer)
            // Only handle mobile labels here
            if (window.innerWidth < 1024) {
                // Let default behavior work for mobile
                return true;
            }
        });
    });

    function toggleSidebar() {
        // Prevent multiple rapid calls
        if (window.sidebarToggling) {
            return;
        }
        window.sidebarToggling = true;
        
        const sidebar = document.getElementById('sidebarContent');
        if (!sidebar) {
            console.error('Sidebar not found');
            window.sidebarToggling = false;
            return;
        }
        
        const isCurrentlyExpanded = sidebar.classList.contains('w-64') || sidebar.classList.contains('sidebar-expanded');
        
        if (isCurrentlyExpanded) {
            // Collapse
            sidebar.classList.remove('w-64', 'sidebar-expanded');
            sidebar.classList.add('w-20');
            document.body.classList.remove('sidebar-expanded');
            localStorage.setItem('sidebarExpanded', 'false');
        } else {
            // Expand
            sidebar.classList.remove('w-20');
            sidebar.classList.add('w-64', 'sidebar-expanded');
            document.body.classList.add('sidebar-expanded');
            localStorage.setItem('sidebarExpanded', 'true');
        }
        
        // Reset flag after a short delay
        setTimeout(() => {
            window.sidebarToggling = false;
        }, 300);
    }
    
    // Make toggleSidebar globally accessible
    window.toggleSidebar = toggleSidebar;

    function initializeTooltips() {
        // Any specific tooltip initialization if not using CSS-only tooltips
    }
    
    // Handle window resize to maintain sidebar state
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            const sidebar = document.getElementById('sidebarContent');
            if (!sidebar) return;
            
            if (window.innerWidth >= 1024) {
                // On desktop, restore saved state
                const isExpanded = localStorage.getItem('sidebarExpanded') === 'true';
                if (isExpanded) {
                    sidebar.classList.remove('w-20');
                    sidebar.classList.add('w-64', 'sidebar-expanded');
                } else {
                    sidebar.classList.remove('w-64', 'sidebar-expanded');
                    sidebar.classList.add('w-20');
                }
            } else {
                // On mobile, reset to default collapsed state
                sidebar.classList.remove('w-64', 'sidebar-expanded');
                sidebar.classList.add('w-20');
            }
        }, 250);
    });
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSidebar);
} else {
    // DOM already loaded
    initSidebar();
}

