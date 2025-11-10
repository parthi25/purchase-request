// ==========================
// Count Box Component
// ==========================

// Default configuration
let countBoxConfig = {
    apiEndpoint: '../fetch/fetch-status-count.php',
    containerId: 'statusCounts',
    role: 'buyer',
    buyer_id: null,
    onStatusClick: null,
    activeStatus: null
};

// Real API service
const apiService = {
    get: async (endpoint, params) => {
        console.log('API call to:', endpoint, 'with params:', params);
        // Build query string
        const queryParams = new URLSearchParams();
        Object.keys(params).forEach(key => queryParams.append(key, params[key]));
        const queryString = queryParams.toString();
        const fullUrl = endpoint + (queryString ? '?' + queryString : '');

        const response = await fetch(fullUrl);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();
        return data;
    }
};

// Initialize component
function initCountBoxComponent(config = {}) {
    countBoxConfig = { ...countBoxConfig, ...config };
    loadCountData();
}

// Load data from API
async function loadCountData() {
    try {
        const params = { role: countBoxConfig.role, buyer_id: countBoxConfig.buyer_id };
        const queryParams = new URLSearchParams();
        Object.keys(params).forEach(key => queryParams.append(key, params[key]));
        const queryString = queryParams.toString();
        const fullUrl = countBoxConfig.apiEndpoint + (queryString ? '?' + queryString : '');

        const response = await fetch(fullUrl);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();
        // Handle new response structure with counts and query
        let countData = [];
        if (data.data) {
            if (Array.isArray(data.data)) {
                countData = data.data;
            } else if (data.data.counts && Array.isArray(data.data.counts)) {
                countData = data.data.counts;
                // Log the executed query if available
                if (data.data.query) {
                    console.log('Executed Query:', data.data.query);
                }
            }
        } else if (Array.isArray(data)) {
            countData = data;
        }
        renderCountBox(countData);
    } catch (error) {
        console.error('Error loading count data:', error);
        renderCountBox([]);
    }
}

// Render the count boxes
function renderCountBox(counts) {
    const container = document.getElementById(countBoxConfig.containerId);
    if (!container) return console.error('Container not found:', countBoxConfig.containerId);

    const defaultCounts = [
        { status_id: 1, status_key: 'Open', count: 0, label: 'Open' },
        { status_id: 2, status_key: 'Forwarded to Buyer', count: 0, label: 'Forwarded to Buyer' },
        { status_id: 3, status_key: 'awaiting_po', count: 0, label: 'Awaiting PO' },
        { status_id: 4, status_key: 'proforma', count: 0, label: 'Proforma' },
        { status_id: 5, status_key: 'to_buyer_head', count: 0, label: 'To Buyer Head' },
        { status_id: 6, status_key: 'to_po_hed', count: 0, label: 'To PO Head' }, // Fixed syntax error
        { status_id: 9, status_key: 'Forwarded to PO Team', count: 0, label: 'To PO Team' },
        { status_id: 7, status_key: 'po_generated', count: 0, label: 'PO Generated' },
        { status_id: 8, status_key: 'rejected', count: 0, label: 'Rejected' }
    ];

    let countData = (Array.isArray(counts) && counts.length > 0) ? counts : defaultCounts;

    let html = '';
    countData.forEach(item => {
        let badgeClass = 'badge-outline';
        if (item.status_id === 1) badgeClass = 'badge-primary';
        else if (item.status_id === 2) badgeClass = 'badge-secondary';
        else if (item.status_id === 3) badgeClass = 'badge-accent';
        else if (item.status_id === 7) badgeClass = 'badge-success';
        else if (item.status_id === 8) badgeClass = 'badge-error';
        else badgeClass = 'badge-warning';

        html += `
            <div class="card bg-base-100 shadow-xl cursor-pointer count-box" 
                 data-status="${item.status_id}" 
                 data-key="${item.status_key}" 
                 title="Click to filter by ${item.label}">
                <div class="card-body text-center">
                    <div class="flex justify-center items-center mb-2">
                        <span class="text-4xl font-bold">${item.count || 0}</span>
                    </div>
                    <h3 class="card-title justify-center text-lg">${item.label}</h3>
                    <div class="card-actions justify-center mt-2">
                        <div class="hidden badge ${badgeClass}">${item.status_id}</div>
                    </div>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
    addCountBoxEventListeners();
}

// Add click events
function addCountBoxEventListeners() {
    const countBoxes = document.querySelectorAll('.count-box');
    countBoxes.forEach(box => {
        box.removeEventListener('click', handleCountBoxClick);
        box.addEventListener('click', handleCountBoxClick);
    });
}

// Handle click
function handleCountBoxClick(event) {
    const box = event.currentTarget;
    const statusId = box.getAttribute('data-status');
    const statusKey = box.getAttribute('data-key');
    const label = box.querySelector('h3').textContent;

    console.log('Count box clicked:', { statusId, statusKey, label });

    document.querySelectorAll('.count-box').forEach(b => {
        b.classList.remove('bg-primary', 'text-primary-content');
        b.classList.add('bg-base-100');
    });
    box.classList.remove('bg-base-100');
    box.classList.add('bg-primary', 'text-primary-content');

    const activeStatusElement = document.getElementById('activeStatus');
    if (activeStatusElement) {
        activeStatusElement.textContent = `${label} (ID: ${statusId})`;
    }
    countBoxConfig.activeStatus = statusId;

    if (countBoxConfig.onStatusClick && typeof countBoxConfig.onStatusClick === 'function') {
        try { 
            console.log('Calling onStatusClick callback with:', statusId, statusKey);
            countBoxConfig.onStatusClick(statusId, statusKey); 
        } 
        catch (error) { console.error(error); alert('Status filter failed: ' + error.message); }
    }
}

// External functions
function setActiveStatus(statusId) {
    const box = document.querySelector(`.count-box[data-status="${statusId}"]`);
    if (box) {
        document.querySelectorAll('.count-box').forEach(b => {
            b.classList.remove('bg-primary', 'text-primary-content');
            b.classList.add('bg-base-100');
        });
        box.classList.remove('bg-base-100');
        box.classList.add('bg-primary', 'text-primary-content');
        countBoxConfig.activeStatus = statusId;
        const activeStatusElement = document.getElementById('activeStatus');
        if (activeStatusElement) {
            activeStatusElement.textContent = `${box.querySelector('h3').textContent} (ID: ${statusId})`;
        }
    }
}

function refreshCountBox() { loadCountData(); }
function updateCountBoxConfig(newConfig) { countBoxConfig = { ...countBoxConfig, ...newConfig }; return countBoxConfig; }

// Expose globally
window.initCountBoxComponent = initCountBoxComponent;
window.refreshCountBox = refreshCountBox;
window.updateCountBoxConfig = updateCountBoxConfig;
window.setActiveStatus = setActiveStatus;
