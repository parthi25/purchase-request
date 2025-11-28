/**
 * Status Badges Utility
 * Fetches statuses from database and generates badge HTML
 */

let statusCache = null;
let statusBadgeCache = {};

// Color mapping based on status text patterns
function getStatusColorClass(statusText) {
    if (!statusText) return 'text-base-content/50';
    
    const text = statusText.toLowerCase();
    
    // Success colors (green)
    if (text.includes('open') || text.includes('generated') || text.includes('completed') || text.includes('forwarded to po members')) {
        return 'text-success';
    }
    
    // Error colors (red)
    if (text.includes('rejected') || text.includes('cancel')) {
        return 'text-error';
    }
    
    // Warning colors (yellow)
    if (text.includes('awaiting') || text.includes('contacted')) {
        return 'text-warning';
    }
    
    // Info colors (blue)
    if (text.includes('forwarded to buyer')) {
        return 'text-info';
    }
    
    // Primary colors (indigo/blue)
    if (text.includes('proforma') || text.includes('received')) {
        return 'text-primary';
    }
    
    // Default gray
    if (text.includes('to po team') || text.includes('to buyer head')) {
        return 'text-base-content/70';
    }
    
    return 'text-base-content/50';
}

// Generate badge HTML for simple badges (view-mode.js style)
function generateSimpleBadge(statusText) {
    const colorClass = getStatusColorClass(statusText);
    return `<span class="${colorClass}">${statusText || 'Unknown'}</span>`;
}

// Generate badge HTML for card badges (card-renderer.js style)
function generateCardBadge(statusText) {
    const colorClass = getStatusColorClass(statusText);
    // Map to card-renderer color classes
    let cardColorClass = 'text-green-600';
    if (colorClass.includes('error')) cardColorClass = 'text-red-600';
    else if (colorClass.includes('warning')) cardColorClass = 'text-yellow-600';
    else if (colorClass.includes('info')) cardColorClass = 'text-blue-600';
    else if (colorClass.includes('primary')) cardColorClass = 'text-indigo-600';
    else if (colorClass.includes('base-content')) cardColorClass = 'text-gray-600';
    
    return `<span class="text-sm font-semibold ${cardColorClass} capitalize">${statusText || 'Unknown'}</span>`;
}

// Fetch statuses from database
async function fetchStatuses() {
    if (statusCache) {
        return statusCache;
    }
    
    try {
        const response = await fetch('../api/get-status-badges.php');
        const result = await response.json();
        
        if (result.status === 'success' && result.data) {
            statusCache = result.data;
            return statusCache;
        }
    } catch (error) {
        console.error('Error fetching statuses:', error);
    }
    
    return null;
}

// Initialize status badges - call this on page load
async function initStatusBadges() {
    const statuses = await fetchStatuses();
    
    if (statuses) {
        // Build cache for quick lookup
        statuses.forEach(status => {
            statusBadgeCache[String(status.id)] = {
                text: status.status,
                simple: generateSimpleBadge(status.status),
                card: generateCardBadge(status.status)
            };
        });
    }
    
    return statusBadgeCache;
}

// Get status badge HTML by status ID
function getStatusBadge(statusId, type = 'simple') {
    const status = statusBadgeCache[String(statusId)];
    if (!status) {
        return type === 'card' 
            ? '<span class="text-sm font-semibold text-base-content/50 capitalize">Unknown</span>'
            : '<span class="text-base-content/50">Unknown</span>';
    }
    
    return type === 'card' ? status.card : status.simple;
}

// Get status text by status ID
function getStatusText(statusId) {
    const status = statusBadgeCache[String(statusId)];
    return status ? status.text : 'Unknown';
}

// Export for use in other files
if (typeof window !== 'undefined') {
    window.StatusBadges = {
        init: initStatusBadges,
        getBadge: getStatusBadge,
        getText: getStatusText,
        fetchStatuses: fetchStatuses
    };
    
    // Auto-initialize on DOMContentLoaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            initStatusBadges();
        });
    } else {
        // DOM already loaded
        initStatusBadges();
    }
}

