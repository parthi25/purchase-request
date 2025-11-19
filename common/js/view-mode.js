/**
 * Common View Mode Toggle Functionality
 * Handles switching between card and table views with localStorage persistence
 * Works with both card-renderer.js and table-renderer.js
 */

const ViewMode = {
  currentView: localStorage.getItem("viewMode") || "cards",
  dom: {},
  tableRenderer: null,
  cardContainerId: "cardContainer",
  tableContainerId: "tableContainer",

  init(options = {}) {
    this.options = {
      containerId: "view-container",
      toggleContainerId: "filterbar",
      role: "buyer",
      onViewChange: null,
      ...options,
    };

    this.cacheDOM();
    this.bindEvents();
    this.setupView();
  },

  cacheDOM() {
    this.dom.container = document.getElementById(this.options.containerId);
    this.dom.toggleContainer = document.getElementById(
      this.options.toggleContainerId
    );

    if (!this.dom.container) {
      console.error(
        `Container with id "${this.options.containerId}" not found`
      );
      return;
    }

    // Create toggle buttons if they don't exist
    if (!document.querySelector(".view-toggle-btn")) {
      const toggleHtml = `
                <div class="flex gap-2 w-full md:w-auto">
                     <div class="btn-group">
                        <button class="btn btn-outline view-toggle-btn" data-view="table">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                            </svg>
                            Table
                        </button>
                        <button class="btn btn-outline view-toggle-btn active btn-primary" data-view="cards">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                            </svg>
                            Cards
                        </button>
                    </div>
     
                </div>
            `;

      if (this.dom.toggleContainer) {
        this.dom.toggleContainer.insertAdjacentHTML("beforeend", toggleHtml);
      } else {
        // Fallback: insert after the filter controls
        const filterControls = document.querySelector(".filter-controls");
        if (filterControls) {
          filterControls.insertAdjacentHTML("afterend", toggleHtml);
        }
      }
    }

    this.dom.viewToggle = document.querySelectorAll(".view-toggle-btn");
    
    // Create containers for both views
    this.setupViewContainers();
  },

  setupViewContainers() {
    // Create card container if it doesn't exist
    if (!document.getElementById(this.cardContainerId)) {
      const cardContainer = document.createElement('div');
      cardContainer.id = this.cardContainerId;
      cardContainer.className = 'flex flex-wrap gap-4 justify-center items-start p-4';
      this.dom.container.appendChild(cardContainer);
    }

    // Create table container if it doesn't exist
    if (!document.getElementById(this.tableContainerId)) {
      const tableContainer = document.createElement('div');
      tableContainer.id = this.tableContainerId;
      tableContainer.classList.add('pt-1')
      this.dom.container.appendChild(tableContainer);
    }

    this.dom.cardContainer = document.getElementById(this.cardContainerId);
    this.dom.tableContainer = document.getElementById(this.tableContainerId);

    // Ensure both containers are hidden initially
    this.dom.cardContainer.style.display = 'none';
    this.dom.tableContainer.style.display = 'none';
  },

  bindEvents() {
    this.dom.viewToggle.forEach((btn) => {
      btn.addEventListener("click", (e) => {
        const view = e.currentTarget.dataset.view;
        this.switchView(view);
      });
    });

    // Bind filter events
    this.bindFilterEvents();

    // Bind infinite scroll
    window.addEventListener('scroll', () => {
      if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 100 &&
          window.state && !window.state.loading && !window.state.noMoreData) {
        this.loadMoreData();
      }
    });
  },

  setupView() {
    // Prioritize localStorage over HTML active button
    this.currentView = localStorage.getItem("viewMode") || "cards";

    // Set active button
    this.dom.viewToggle.forEach((btn) => {
      if (btn.dataset.view === this.currentView) {
        btn.classList.add("active", "btn-primary");
        btn.classList.remove("btn-outline");
      } else {
        btn.classList.remove("active", "btn-primary");
        btn.classList.add("btn-outline");
      }
    });

    // Load initial view
    this.showCurrentView();

    // Load initial data
    this.loadCurrentViewData();

    // Call onViewChange callback if provided
    if (this.options.onViewChange) {
      this.options.onViewChange(this.currentView);
    }
  },

  switchView(view) {
    if (view === this.currentView) return;

    this.currentView = view;
    localStorage.setItem("viewMode", view);

    // Update active button
    this.dom.viewToggle.forEach((btn) => {
      if (btn.dataset.view === view) {
        btn.classList.add("active", "btn-primary");
        btn.classList.remove("btn-outline");
      } else {
        btn.classList.remove("active", "btn-primary");
        btn.classList.add("btn-outline");
      }
    });

    // Show the appropriate view
    this.showCurrentView();

    // Reset pagination state if available
    if (window.state) {
      window.state.offset = 0;
      window.state.noMoreData = false;
    }

    // Load data for the current view
    this.loadCurrentViewData();

    // Call onViewChange callback if provided
    if (this.options.onViewChange) {
      this.options.onViewChange(view);
    }
  },

  showCurrentView() {
    // Hide both containers first
    if (this.dom.cardContainer) {
      this.dom.cardContainer.style.display = 'none';
    }
    if (this.dom.tableContainer) this.dom.tableContainer.style.display = 'none';

    // Show the active one
    if (this.currentView === "cards" && this.dom.cardContainer) {
      this.dom.cardContainer.style.display = 'flex';
    } else if (this.currentView === "table" && this.dom.tableContainer) {
      this.dom.tableContainer.style.display = 'block';
    }
  },

  loadCurrentViewData() {
    if (!window.state) return;

    window.state.loading = true;
    this.showLoading();

    // Build query parameters based on role
    const params = new URLSearchParams();
    params.append("offset", window.state.offset || 0);
    params.append("limit", window.state.limit || 9);
    params.append("view", this.currentView);

    // Handle different status filter naming conventions
    const statusFilter =
      window.state.statusFilter ||
      window.state.status_filter ||
      localStorage.getItem("filter") ||
      1;
    params.append("status", statusFilter);
    
    // Debug logging
    console.log('Loading data with status filter:', statusFilter, 'for role:', this.options.role);

    // Add search and date filters
    if (window.state.search) params.append("search", window.state.search);
    if (window.state.from) params.append("from_date", window.state.from);
    if (window.state.to) params.append("to_date", window.state.to);

    // Add role-specific parameters
    this.addRoleSpecificParams(params);
    
    // Add role parameter for PHP backend
    params.append("role", this.options.role);

    // Fetch data using unified API service
    const queryString = params.toString();
    console.log('Fetching data from:', `../fetch/fetch-data.php?${queryString}`);
    fetch(`../fetch/fetch-data.php?${queryString}`)
  .then((res) => res.json())
  .then((response) => {
    // Handle both response formats
    let data = [];

    if (response && response.success && response.data) {
        data = response.data;
    } else if (Array.isArray(response)) {
        data = response;
    } else if (response && Array.isArray(response.data)) {
        data = response.data;
    }

    if (!Array.isArray(data) || data.length === 0) {
        window.state.noMoreData = true;
        this.hideLoading();
        if (window.state.offset === 0) this.showNoData();
        window.state.loading = false;
        return;
    }

    if (this.currentView === "cards") {
        this.renderCards(data);
    } else {
        this.renderTable(data);
    }

    window.state.offset += window.state.limit;
    this.hideLoading();
    window.state.loading = false;
  })
  .catch((error) => {
    console.error('Fetch error:', error);
    this.hideLoading();
    window.state.loading = false;
  })
  },

  renderCards(data) {
    if (typeof renderCards === 'function') {
      // Use the card renderer function
      renderCards(data, this.options.role, this.cardContainerId);
    } else {
      console.warn('Card renderer function not found');
      // Fallback: simple card rendering
      this.renderCardsFallback(data);
    }
  },

  renderCardsFallback(data) {
    const container = this.dom.cardContainer;
    if (!container) return;
console.log("fall backg");

    // Clear container if it's the first load
    if (window.state.offset === 0) {
      container.innerHTML = '';
    }

    data.forEach((item, index) => {
      const cardHtml = `
        <div class="card w-84 bg-base-100 shadow-md border border-gray-200 mb-4 opacity-0 translate-y-4 scale-95 transition-all duration-500 ease-in-out hover:shadow-lg hover:scale-105 rounded-2xl">
          <div class="card-body p-4">
            <h2 class="text-base font-bold mb-1">Buyer Head: ${item.buyerHead || 'N/A'}</h2>
            <div class="space-y-1.5 text-sm">
              <div class="flex"><span class="font-semibold w-24">Ref ID:</span><span>${item.refId || 'N/A'}</span></div>
              <div class="flex"><span class="font-semibold w-24">Supplier:</span><span>${item.supplier || 'N/A'}</span></div>
              <div class="flex"><span class="font-semibold w-24">Category:</span><span>${item.category || 'N/A'}</span></div>
            </div>
            <div class="divider my-3"></div>
            <div class="flex justify-between items-center">
              <button class="text-sm font-semibold text-blue-600 hover:text-blue-800">Read More</button>
              <div class="flex gap-2">
                <button class="btn btn-sm btn-outline">Proforma√</button>
                <button class="btn btn-sm btn-outline">PO</button>
              </div>
            </div>
          </div>
        </div>
      `;
      container.insertAdjacentHTML('beforeend', cardHtml);
      
      // Animate card in with scale effect
      setTimeout(() => {
        const card = container.lastElementChild;
        if (card) {
          card.classList.add('opacity-100', 'translate-y-0', 'scale-100');
          card.classList.remove('translate-y-4', 'scale-95');
        }
      }, index * 50);
    });
  },

  renderTable(data) {
    if (window.TableRenderer) {
      // Use TableRenderer class
      if (!this.tableRenderer || window.state.offset === 0) {
        // Initialize or reinitialize table renderer
        this.tableRenderer = new TableRenderer(this.tableContainerId, this.options.role);
      }
      
      if (window.state.offset === 0) {
        // First load - render all rows
        this.tableRenderer.renderRows(data);
      } else {
        // Append new rows
        this.tableRenderer.appendRows(data);
      }
    } else {
      console.warn('Table renderer not found');
      // Fallback: simple table rendering
      this.renderTableFallback(data);
    }
  },

  renderTableFallback(data) {
    const container = this.dom.tableContainer;
    if (!container) return;

    // Create table structure if it doesn't exist
    if (window.state.offset === 0 || !container.querySelector('table')) {
      const tableHtml = `
        <div class="overflow-x-auto">
          <table class="table table-zebra w-full" id="dataTable">
            <thead>
              <tr>
                <th>Ref ID</th>
                <th>Status</th>
                <th>Supplier</th>
                <th>B Head</th>
                <th>Buyer</th>
                <th>Category</th>
                <th>Quantity</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      `;
      container.innerHTML = tableHtml;
    }

    const tbody = container.querySelector('tbody');
    
    data.forEach(row => {
      const rowHtml = `
        <tr>
          <td>${row.id || 'N/A'}</td>
          <td>${this.getStatusBadge(row.po_status)}</td>
          <td>${row.supplier || 'N/A'}</td>
          <td>${row.b_head || 'N/A'}</td>
          <td>${row.buyer || 'N/A'}</td>
          <td>${row.category || 'N/A'}</td>
          <td>${row.qty || 'N/A'}</td>
          <td>
            <div class="flex gap-1">
              <button class="btn btn-xs btn-outline">View Proforma</button>
              <button class="btn btn-xs btn-outline">View PO</button>
            </div>
          </td>
        </tr>
      `;
      tbody.insertAdjacentHTML('beforeend', rowHtml);
    });
  },

  getStatusBadge(status) {
    const badges = {
      "1": '<span class="text-success">Open</span>',
      "2": '<span class="text-info">Forwarded</span>',
      "3": '<span class="text-warning">Awaiting PO</span>',
      "4": '<span class="text-primary">Proforma</span>',
      "5": '<span class="text-error">To Buyer Head</span>',
      "6": '<span class="text-base-content/70">To PO Team</span>',
      "7": '<span class="text-success">PO Generated</span>',
      "8": '<span class="text-error">Rejected</span>',
      "9": '<span class="text-success">Forwarded to PO Members</span>'
    };
    return badges[String(status)] || '<span class="text-base-content/50">Unknown</span>';
  },

  // Method to load more data (call this from your load more button)
  loadMoreData() {
    if (window.state && window.state.loading) return;
    
    this.loadCurrentViewData();
  },

  // Method to refresh current view with new data (call this after filters change)
  refreshView() {
    if (window.state) {
      window.state.offset = 0;
      window.state.noMoreData = false;
    }
    
    // Clear containers
    if (this.dom.cardContainer) this.dom.cardContainer.innerHTML = '';
    if (this.dom.tableContainer) this.dom.tableContainer.innerHTML = '';
    
    this.loadCurrentViewData();
  },

  showLoading() {
    const loader = document.getElementById("loader");
    const noData = document.getElementById("no-data-message");
    if (loader) loader.style.display = "block";
    if (noData) noData.style.display = "none";
  },

  hideLoading() {
    const loader = document.getElementById("loader");
    if (loader) loader.style.display = "none";
  },

  showNoData() {
    const noData = document.getElementById("no-data-message");
    if (noData) noData.style.display = "block";
  },

  bindFilterEvents() {
    const searchInput = document.getElementById('searchInput');
    const fromDate = document.getElementById('fromDate');
    const toDate = document.getElementById('toDate');
    const applyFilters = document.getElementById('applyFilters');
    const clearFilters = document.getElementById('clearFilters');

    if (applyFilters) {
      applyFilters.addEventListener('click', () => {
        this.applyFilters();
      });
    }

    if (clearFilters) {
      clearFilters.addEventListener('click', () => {
        this.clearFilters();
      });
    }

    // Optional: Apply filters on Enter key in search input
    if (searchInput) {
      searchInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
          this.applyFilters();
        }
      });
    }
  },

  applyFilters() {
    const searchInput = document.getElementById('searchInput');
    // Date range is already set by flatpickr onChange handler

    if (window.state) {
      window.state.search = searchInput ? searchInput.value : '';
      // window.state.from and window.state.to are set by flatpickr onChange
      window.state.offset = 0;
      window.state.noMoreData = false;
    }

    // Refresh the view with new filters
    this.refreshView();
  },

  clearFilters() {
    const searchInput = document.getElementById('searchInput');
    const dateRangeInput = document.getElementById('dateRange');

    if (searchInput) searchInput.value = '';
    if (dateRangeInput) {
      // Clear flatpickr instance
      const fpInstance = dateRangeInput._flatpickr;
      if (fpInstance) {
        fpInstance.clear();
      }
      dateRangeInput.value = '';
    }

    if (window.state) {
      window.state.search = '';
      window.state.from = '';
      window.state.to = '';
      window.state.offset = 0;
      window.state.noMoreData = false;
    }

    // Refresh the view with cleared filters
    this.refreshView();
  },

  addRoleSpecificParams(params) {
    // Add role-specific parameters based on the current role
    console.log('Adding role-specific params for role:', this.options.role);
    
    switch (this.options.role) {
      case "admin":
        if (window.state && window.state.user_id) {
          params.append("user_id", window.state.user_id);
        }
        break;

      case "buyer":
        const buyerUserId = document.querySelector(
          'input[name="userId"]'
        )?.value;
        if (buyerUserId) {
          params.append("user_id", buyerUserId);
        }
        break;

      case "bhead":
        const bheadUserId = document.querySelector(
          'input[name="userId"]'
        )?.value;
        if (bheadUserId) {
          params.append("user_id", bheadUserId);
        }
        // Add buyer filter from dropdown selection
        if (window.state && window.state.selectedBuyerId) {
          params.append("buyer_filter", window.state.selectedBuyerId);
          console.log('Added buyer_filter:', window.state.selectedBuyerId);
        }
        break;

      case "pohead":
        // Add PO head specific parameters
        if (window.state && window.state.user_id) {
          params.append("user_id", window.state.user_id);
        }
        // Add PO member filter from dropdown selection
        if (window.state && window.state.selectedPoMemberId) {
          params.append("po_filter", window.state.selectedPoMemberId);
          console.log('Added po_filter:', window.state.selectedPoMemberId);
        }
        break;

      case "poteam":
        params.append("role", "PO_Head");
        if (window.state && window.state.po_filter) {
          params.append("po_filter", window.state.po_filter);
        }
        break;

      case "poteammember":
        const poteamUserRole = document.querySelector(
          'input[name="userRole"]'
        )?.value;
        if (poteamUserRole) {
          params.append("role", poteamUserRole);
        }
        break;

      case "dashboard":
        if (window.state && window.state.role) {
          params.append("role", window.state.role);
        }
        if (window.state && window.state.user_id) {
          params.append("user_id", window.state.user_id);
        }
        break;

      default:
        const defaultUserId =
          document.querySelector('input[name="userId"]')?.value ||
          (window.state && window.state.user_id);
        if (defaultUserId) {
          params.append("user_id", defaultUserId);
        }
        break;
    }
    
    console.log('Final params:', params.toString());
  },
};

// Global initialization function
function initViewMode(options = {}) {
  if (typeof ViewMode.init === "function") {
    ViewMode.init(options);
  }
}

// Export for global use
window.ViewMode = ViewMode;
window.initViewMode = initViewMode;
