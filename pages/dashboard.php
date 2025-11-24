<?php include '../common/layout.php'; ?>
                <!-- Page Header -->
                <div class="mb-6">
                    <h1 class="text-4xl font-bold mb-2">Dashboard</h1>
                    <p class="text-base-content/70">View and filter purchase order tracking data</p>
                </div>

                <!-- Filters Section -->
                <div class="mb-6 bg-base-200 p-4 rounded-lg">
                    <h2 class="text-xl font-semibold mb-4">Filters</h2>
                    
                    <!-- Search and Date Range -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text font-semibold">Search</span>
                            </label>
                            <input type="text" id="searchInput" placeholder="Search by PO ID, supplier, category..." class="input input-bordered w-full" />
                        </div>
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text font-semibold">Start Date</span>
                            </label>
                            <input type="date" id="startDate" class="input input-bordered w-full" />
                        </div>
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text font-semibold">End Date</span>
                            </label>
                            <input type="date" id="endDate" class="input input-bordered w-full" />
                        </div>
                    </div>

                    <!-- Filter Dropdowns -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                        <!-- Status Multiselect -->
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text font-semibold">Status</span>
                            </label>
                            <select id="statusFilter" class="w-full" multiple>
                            </select>
                        </div>

                        <!-- Buyer Head Multiselect -->
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text font-semibold">Buyer Head</span>
                            </label>
                            <select id="buyerHeadFilter" class="w-full" multiple>
                            </select>
                        </div>

                        <!-- Buyer Multiselect -->
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text font-semibold">Buyer</span>
                            </label>
                            <select id="buyerFilter" class="w-full" multiple>
                            </select>
                        </div>

                        <!-- Supplier Multiselect -->
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text font-semibold">Supplier</span>
                            </label>
                            <select id="supplierFilter" class="w-full" multiple>
                            </select>
                        </div>

                        <!-- Category Multiselect -->
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text font-semibold">Category</span>
                            </label>
                            <select id="categoryFilter" class="w-full" multiple>
                            </select>
                        </div>

                        <!-- Purchase Type Multiselect -->
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text font-semibold">Purchase Type</span>
                            </label>
                            <select id="purchFilter" class="w-full" multiple>
                            </select>
                        </div>

                        <!-- PO Team Member Multiselect -->
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text font-semibold">PO Team Member</span>
                            </label>
                            <select id="poTeamMemberFilter" class="w-full" multiple>
                            </select>
                        </div>

                        <!-- Per Page (Regular Select) -->
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text font-semibold">Per Page</span>
                            </label>
                            <select id="perPageSelect" class="select select-bordered w-full">
                                <option value="10">10</option>
                                <option value="25" selected>25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-wrap gap-2">
                        <button id="applyFiltersBtn" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                            </svg>
                            Apply Filters
                        </button>
                        <button id="resetFiltersBtn" class="btn btn-outline">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Reset
                        </button>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <!-- Status Distribution Chart -->
                    <div class="bg-base-200 p-6 rounded-lg">
                        <h3 class="text-xl font-semibold mb-4">Status Distribution</h3>
                        <canvas id="statusChart"></canvas>
                    </div>

                    <!-- Time Buckets Chart -->
                    <div class="bg-base-200 p-6 rounded-lg">
                        <h3 class="text-xl font-semibold mb-4">Processing Time Distribution</h3>
                        <canvas id="timeBucketChart"></canvas>
                    </div>

                    <!-- Buyer PR Count Chart -->
                    <div class="bg-base-200 p-6 rounded-lg lg:col-span-2 hidden">
                        <h3 class="text-xl font-semibold mb-4">PR Count by Buyer</h3>
                        <div class="relative" style="height: 400px;">
                            <canvas id="buyerChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Data Table Section -->
                <div class="bg-base-200 p-6 rounded-lg">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-4">
                        <h2 class="text-2xl font-semibold">Purchase Orders</h2>
                        <div class="flex flex-wrap gap-2 items-center">
                            <div class="badge badge-primary badge-lg">Total: <span id="totalRecords">0</span></div>
                            <div class="badge badge-success badge-lg">Completed: <span id="completedBadge">0</span></div>
                            <button id="columnToggleBtn" class="btn btn-outline btn-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                                Columns
                            </button>
                            <button id="downloadBtn" class="btn btn-primary btn-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                Download
                            </button>
                        </div>
                    </div>

                    <!-- Column Selection Modal -->
                    <dialog id="columnModal" class="modal">
                        <div class="modal-box max-w-2xl">
                            <h3 class="font-bold text-lg mb-4">Select Columns</h3>
                            <div class="space-y-2 max-h-96 overflow-y-auto" id="columnCheckboxes">
                                <!-- Column checkboxes will be inserted here -->
                            </div>
                            <div class="modal-action">
                                <button class="btn btn-primary" id="applyColumnsBtn">Apply</button>
                                <form method="dialog">
                                    <button class="btn">Close</button>
                                </form>
                            </div>
                        </div>
                        <form method="dialog" class="modal-backdrop">
                            <button>close</button>
                        </form>
                    </dialog>

                    <!-- Loading State -->
                    <div id="loadingState" class="flex justify-center items-center py-12">
                        <span class="loading loading-spinner loading-lg text-primary"></span>
                    </div>

                    <!-- Table -->
                    <div class="overflow-x-auto" id="tableContainer" style="display: none;">
                        <table class="table table-zebra" id="dataTable">
                            <thead id="tableHead">
                                <!-- Headers will be dynamically generated -->
                            </thead>
                            <tbody id="tableBody">
                                <!-- Data will be inserted here -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Empty State -->
                    <div id="emptyState" class="text-center py-12" style="display: none;">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-24 w-24 mx-auto text-base-content/20 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="text-lg font-semibold text-base-content/70">No data found</p>
                        <p class="text-base-content/50 mt-2">Try adjusting your filters</p>
                    </div>

                    <!-- Pagination -->
                    <div class="flex flex-col md:flex-row justify-between items-center gap-4 mt-6">
                        <div class="text-sm text-base-content/70">
                            Showing <span id="showingFrom">0</span> to <span id="showingTo">0</span> of <span id="showingTotal">0</span> entries
                        </div>
                        <div class="join" id="pagination">
                            <!-- Pagination buttons will be inserted here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Details Modal -->
    <dialog id="detailsModal" class="modal">
        <div class="modal-box w-11/12 max-w-5xl">
            <h3 class="font-bold text-lg mb-4">Order Details - <span id="modalOrderId"></span></h3>
            <div class="overflow-x-auto">
                <table class="table table-zebra">
                    <tbody id="detailsTableBody">
                        <!-- Details will be inserted here -->
                    </tbody>
                </table>
            </div>
            <div class="modal-action">
                <form method="dialog">
                    <button class="btn">Close</button>
                </form>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

    <!-- Chart.js -->
    <script src="../assets/js/chart.umd.min.js"></script>
    <!-- XLSX for Excel export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>



    <script>
        const Dashboard = {
            state: {
                currentPage: 1,
                perPage: 25,
                data: [],
                allFilteredData: [],
                options: {},
                stats: {},
                pagination: {},
                searchQuery: '',
                searchTimeout: null,
                statusChart: null,
                timeBucketChart: null,
                buyerChart: null,
                filtersInitialized: false,
                lastOptionsHash: null,
                abortController: null,
                // Column visibility management
                availableColumns: [
                    { key: 'id', label: 'ID', visible: true, order: 1 },
                    { key: 'status', label: 'Status', visible: true, order: 2 },
                    { key: 'supplier', label: 'Supplier', visible: true, order: 3 },
                    { key: 'supplier_code', label: 'Supplier Code', visible: false, order: 4 },
                    { key: 'b_head', label: 'Buyer Head', visible: true, order: 5 },
                    { key: 'buyer', label: 'Buyer', visible: true, order: 6 },
                    { key: 'buyername', label: 'Buyer Name', visible: false, order: 7 },
                    { key: 'po_team_member', label: 'PO Team Member', visible: true, order: 8 },
                    { key: 'pohead', label: 'PO Head', visible: false, order: 9 },
                    { key: 'purch_type', label: 'Purchase Type', visible: true, order: 10 },
                    { key: 'categories', label: 'Category', visible: true, order: 11 },
                    { key: 'category_name', label: 'Category Name', visible: false, order: 12 },
                    { key: 'qty', label: 'Quantity', visible: false, order: 13 },
                    { key: 'uom', label: 'UOM', visible: false, order: 14 },
                    { key: 'remark', label: 'Remark', visible: false, order: 15 },
                    { key: 'created_at', label: 'Created Date', visible: true, order: 16 },
                    { key: 'created_by_name', label: 'Created By', visible: false, order: 17 },
                    { key: 'updated_at', label: 'Updated Date', visible: false, order: 18 },
                    { key: 'po_number', label: 'PO Number', visible: true, order: 19 },
                    { key: 'po_date', label: 'PO Date', visible: true, order: 20 },
                    { key: 'status_1', label: 'Status 1 Date', visible: false, order: 21 },
                    { key: 'status_2', label: 'Status 2 Date', visible: false, order: 22 },
                    { key: 'status_3', label: 'Status 3 Date', visible: false, order: 23 },
                    { key: 'status_4', label: 'Status 4 Date', visible: false, order: 24 },
                    { key: 'status_5', label: 'Status 5 Date', visible: false, order: 25 },
                    { key: 'status_6', label: 'Status 6 Date', visible: false, order: 26 },
                    { key: 'status_7', label: 'Status 7 Date', visible: false, order: 27 }
                ],
                visibleColumns: []
            },

            init() {
                this.setupDefaultDates();
                this.bindEvents();
                this.initCharts();
                this.initColumnManagement();
                
                // Load filters and data in parallel, but prioritize data
                this.loadFilters();
                this.loadData();
            },

            initColumnManagement() {
                // Initialize visible columns from available columns
                this.state.visibleColumns = this.state.availableColumns
                    .filter(col => col.visible)
                    .sort((a, b) => a.order - b.order);
                
                // Load saved column preferences from localStorage
                const savedColumns = localStorage.getItem('dashboard_columns');
                if (savedColumns) {
                    try {
                        const saved = JSON.parse(savedColumns);
                        this.state.availableColumns.forEach(col => {
                            const savedCol = saved.find(s => s.key === col.key);
                            if (savedCol) {
                                col.visible = savedCol.visible;
                                col.order = savedCol.order;
                            }
                        });
                        this.state.visibleColumns = this.state.availableColumns
                            .filter(col => col.visible)
                            .sort((a, b) => a.order - b.order);
                    } catch (e) {
                        console.error('Error loading saved columns:', e);
                    }
                }
                
                // Setup column toggle button
                document.getElementById('columnToggleBtn').addEventListener('click', () => {
                    this.showColumnModal();
                });
                
                // Setup download button
                document.getElementById('downloadBtn').addEventListener('click', () => {
                    this.downloadData();
                });
                
                // Setup apply columns button
                document.getElementById('applyColumnsBtn').addEventListener('click', () => {
                    this.applyColumnSelection();
                });
            },

            showColumnModal() {
                const container = document.getElementById('columnCheckboxes');
                container.innerHTML = '';
                
                // Sort columns by order
                const sortedColumns = [...this.state.availableColumns].sort((a, b) => a.order - b.order);
                
                // Create a sortable list
                const list = document.createElement('div');
                list.id = 'sortableColumnList';
                list.className = 'space-y-2';
                
                sortedColumns.forEach((col, index) => {
                    const item = document.createElement('div');
                    item.className = 'flex items-center gap-2 p-2 hover:bg-base-200 rounded cursor-move border border-base-300';
                    item.draggable = true;
                    item.dataset.key = col.key;
                    item.dataset.order = col.order;
                    item.innerHTML = `
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-base-content/50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <input type="checkbox" class="checkbox checkbox-primary" 
                               data-key="${col.key}" ${col.visible ? 'checked' : ''}>
                        <span class="label-text flex-1">${col.label}</span>
                    `;
                    
                    // Drag and drop handlers
                    item.addEventListener('dragstart', (e) => {
                        e.dataTransfer.effectAllowed = 'move';
                        e.dataTransfer.setData('text/html', item.outerHTML);
                        e.dataTransfer.setData('text/key', col.key);
                        item.classList.add('opacity-50');
                    });
                    
                    item.addEventListener('dragend', (e) => {
                        item.classList.remove('opacity-50');
                    });
                    
                    item.addEventListener('dragover', (e) => {
                        e.preventDefault();
                        e.dataTransfer.dropEffect = 'move';
                        const afterElement = this.getDragAfterElement(list, e.clientY);
                        const dragging = document.querySelector('.opacity-50');
                        if (dragging && afterElement == null) {
                            list.appendChild(dragging);
                        } else if (dragging && afterElement) {
                            list.insertBefore(dragging, afterElement);
                        }
                    });
                    
                    item.addEventListener('drop', (e) => {
                        e.preventDefault();
                        const draggedKey = e.dataTransfer.getData('text/key');
                        const draggedItem = list.querySelector(`[data-key="${draggedKey}"]`);
                        if (draggedItem && draggedItem !== item) {
                            const allItems = Array.from(list.children);
                            const draggedIndex = allItems.indexOf(draggedItem);
                            const targetIndex = allItems.indexOf(item);
                            
                            if (draggedIndex < targetIndex) {
                                list.insertBefore(draggedItem, item.nextSibling);
                            } else {
                                list.insertBefore(draggedItem, item);
                            }
                            
                            // Update order values
                            Array.from(list.children).forEach((el, idx) => {
                                const key = el.dataset.key;
                                const col = this.state.availableColumns.find(c => c.key === key);
                                if (col) {
                                    col.order = idx + 1;
                                }
                            });
                        }
                    });
                    
                    list.appendChild(item);
                });
                
                container.appendChild(list);
                document.getElementById('columnModal').showModal();
            },

            getDragAfterElement(container, y) {
                const draggableElements = [...container.querySelectorAll('div[draggable]:not(.opacity-50)')];
                
                return draggableElements.reduce((closest, child) => {
                    const box = child.getBoundingClientRect();
                    const offset = y - box.top - box.height / 2;
                    
                    if (offset < 0 && offset > closest.offset) {
                        return { offset: offset, element: child };
                    } else {
                        return closest;
                    }
                }, { offset: Number.NEGATIVE_INFINITY }).element;
            },

            applyColumnSelection() {
                const list = document.getElementById('sortableColumnList');
                const checkboxes = list.querySelectorAll('input[type="checkbox"]');
                
                // Update visibility from checkboxes
                checkboxes.forEach(cb => {
                    const col = this.state.availableColumns.find(c => c.key === cb.dataset.key);
                    if (col) {
                        col.visible = cb.checked;
                    }
                });
                
                // Update order from DOM position
                Array.from(list.children).forEach((el, idx) => {
                    const key = el.dataset.key;
                    const col = this.state.availableColumns.find(c => c.key === key);
                    if (col) {
                        col.order = idx + 1;
                    }
                });
                
                // Update visible columns
                this.state.visibleColumns = this.state.availableColumns
                    .filter(col => col.visible)
                    .sort((a, b) => a.order - b.order);
                
                // Save to localStorage
                localStorage.setItem('dashboard_columns', JSON.stringify(this.state.availableColumns));
                
                // Re-render table
                this.renderTable();
                
                // Close modal
                document.getElementById('columnModal').close();
            },

            loadFilters() {
                // No need to load filter options upfront - each filter uses AJAX
                // Just initialize the filters directly
                const initFilters = () => {
                    // Use scheduler.postTask if available for better scheduling
                    if ('scheduler' in window && 'postTask' in window.scheduler) {
                        scheduler.postTask(() => {
                            this.populateFilters();
                        }, { priority: 'background' });
                    } else if ('requestIdleCallback' in window) {
                        requestIdleCallback(() => {
                            this.populateFilters();
                        }, { timeout: 2000 });
                    } else {
                        // Fallback: use setTimeout with chunking
                        setTimeout(() => {
                            this.populateFilters();
                        }, 100);
                    }
                };
                
                // Defer initialization
                if ('requestIdleCallback' in window) {
                    requestIdleCallback(initFilters, { timeout: 1000 });
                } else {
                    setTimeout(initFilters, 0);
                }
            },

            setupDefaultDates() {
                const endDate = new Date();
                const startDate = new Date();
                startDate.setDate(startDate.getDate() - 30);
                document.getElementById('startDate').value = startDate.toISOString().split('T')[0];
                document.getElementById('endDate').value = endDate.toISOString().split('T')[0];
            },

            bindEvents() {
                // Optimize click handlers to prevent forced reflow
                const applyBtn = document.getElementById('applyFiltersBtn');
                const resetBtn = document.getElementById('resetFiltersBtn');
                
                // Use setTimeout to defer work and prevent blocking
                applyBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Use setTimeout to yield to browser before heavy work
                    setTimeout(() => {
                        requestAnimationFrame(() => {
                            this.state.currentPage = 1;
                            this.loadData();
                        });
                    }, 0);
                }, { passive: false });

                resetBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Use setTimeout to yield to browser before heavy work
                    setTimeout(() => {
                        requestAnimationFrame(() => {
                            this.resetFilters();
                        });
                    }, 0);
                }, { passive: false });

                document.getElementById('perPageSelect').addEventListener('change', (e) => {
                    requestAnimationFrame(() => {
                        this.state.perPage = parseInt(e.target.value);
                        this.state.currentPage = 1;
                        this.loadData();
                    });
                });

                // Debounced search with longer delay for better performance
                document.getElementById('searchInput').addEventListener('input', (e) => {
                    this.state.searchQuery = e.target.value.trim();
                    clearTimeout(this.state.searchTimeout);
                    this.state.searchTimeout = setTimeout(() => {
                        requestAnimationFrame(() => {
                            this.state.currentPage = 1;
                            this.loadData();
                        });
                    }, 800); // Increased debounce time
                }, { passive: true });
            },

            resetFilters() {
                // Batch all DOM reads first to avoid forced reflow
                const filterIds = ['statusFilter', 'buyerHeadFilter', 'buyerFilter', 'supplierFilter', 'categoryFilter', 'purchFilter', 'poTeamMemberFilter'];
                const selectsToReset = [];
                
                // Read phase - batch all DOM reads
                filterIds.forEach(filterId => {
                    const $select = $(`#${filterId}`);
                    if ($select.length && $select.hasClass('select2-hidden-accessible')) {
                        selectsToReset.push($select);
                    }
                });
                
                // Batch all remaining DOM reads
                const searchInput = document.getElementById('searchInput');
                const startDate = document.getElementById('startDate');
                const endDate = document.getElementById('endDate');
                
                // Write phase - batch all DOM writes using requestAnimationFrame
                requestAnimationFrame(() => {
                    // Reset Select2 filters
                    selectsToReset.forEach($select => {
                        $select.val(null).trigger('change');
                    });
                    
                    // Reset other inputs
                    if (searchInput) searchInput.value = '';
                    this.state.searchQuery = '';
                    
                    // Reset dates
                    if (startDate && endDate) {
                        const endDateObj = new Date();
                        const startDateObj = new Date();
                        startDateObj.setDate(startDateObj.getDate() - 30);
                        startDate.value = startDateObj.toISOString().split('T')[0];
                        endDate.value = endDateObj.toISOString().split('T')[0];
                    }
                    
                    this.state.currentPage = 1;
                    
                    // Load data after all DOM updates
                    requestAnimationFrame(() => {
                        this.loadData();
                    });
                });
            },

            initCharts() {
                // Status Distribution Chart
                const statusCtx = document.getElementById('statusChart').getContext('2d');
                this.state.statusChart = new Chart(statusCtx, {
                    type: 'doughnut',
                    data: {
                        labels: [],
                        datasets: [{
                            label: 'Orders',
                            data: [],
                            backgroundColor: [
                                'rgba(239, 68, 68, 0.8)',
                                'rgba(245, 158, 11, 0.8)',
                                'rgba(34, 197, 94, 0.8)',
                                'rgba(59, 130, 246, 0.8)',
                                'rgba(139, 92, 246, 0.8)',
                                'rgba(236, 72, 153, 0.8)'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });

                // Time Buckets Chart - Line Chart
                const timeCtx = document.getElementById('timeBucketChart').getContext('2d');
                this.state.timeBucketChart = new Chart(timeCtx, {
                    type: 'line',
                    data: {
                        labels: [],
                        datasets: [{
                            label: 'Number of Orders',
                            data: [],
                            borderColor: 'rgba(59, 130, 246, 1)',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });

                // Buyer PR Count Chart
                const buyerCtx = document.getElementById('buyerChart').getContext('2d');
                this.state.buyerChart = new Chart(buyerCtx, {
                    type: 'bar',
                    data: {
                        labels: [],
                        datasets: [{
                            label: 'Number of PRs',
                            data: [],
                            backgroundColor: 'rgba(34, 197, 94, 0.8)',
                            borderColor: 'rgba(34, 197, 94, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        aspectRatio: 2,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top'
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return 'PRs: ' + context.parsed.y;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1,
                                    precision: 0
                                }
                            },
                            x: {
                                ticks: {
                                    maxRotation: 45,
                                    minRotation: 45,
                                    autoSkip: true,
                                    maxTicksLimit: 20
                                }
                            }
                        }
                    }
                });
            },

            updateCharts() {
                // Defer chart updates to avoid blocking
                requestAnimationFrame(() => {
                    const stats = this.state.stats;
                    
                    // Update Status Distribution Chart
                    if (stats.status_distribution && this.state.statusChart) {
                        const statusLabels = Object.keys(stats.status_distribution);
                        const statusData = Object.values(stats.status_distribution);
                        
                        this.state.statusChart.data.labels = statusLabels;
                        this.state.statusChart.data.datasets[0].data = statusData;
                        this.state.statusChart.update('none'); // 'none' mode for faster updates
                    }

                    // Update Time Buckets Chart
                    if (stats.time_buckets && this.state.timeBucketChart) {
                        const timeLabels = Object.keys(stats.time_buckets);
                        const timeData = Object.values(stats.time_buckets);
                        
                        this.state.timeBucketChart.data.labels = timeLabels;
                        this.state.timeBucketChart.data.datasets[0].data = timeData;
                        this.state.timeBucketChart.update('none'); // 'none' mode for faster updates
                    }

                    // Update Buyer Chart
                    if (stats.buyer_distribution && this.state.buyerChart) {
                        const buyerLabels = Object.keys(stats.buyer_distribution);
                        const buyerData = Object.values(stats.buyer_distribution);
                        
                        if (buyerLabels.length > 0) {
                            // Sort by count descending and take top 20
                            const buyerEntries = buyerLabels.map((label, idx) => ({
                                label: label || 'Unknown',
                                count: buyerData[idx] || 0
                            }))
                            .sort((a, b) => b.count - a.count)
                            .slice(0, 20);
                            
                            this.state.buyerChart.data.labels = buyerEntries.map(e => e.label);
                            this.state.buyerChart.data.datasets[0].data = buyerEntries.map(e => e.count);
                            this.state.buyerChart.update('none');
                        } else {
                            // Handle empty data
                            this.state.buyerChart.data.labels = ['No Data'];
                            this.state.buyerChart.data.datasets[0].data = [0];
                            this.state.buyerChart.update('none');
                        }
                    }
                });
            },

            showLoading() {
                document.getElementById('loadingState').style.display = 'flex';
                document.getElementById('tableContainer').style.display = 'none';
                document.getElementById('emptyState').style.display = 'none';
            },

            hideLoading() {
                document.getElementById('loadingState').style.display = 'none';
            },

            loadData() {
                this.showLoading();

                const params = new URLSearchParams({
                    page: this.state.currentPage,
                    per_page: this.state.perPage,
                    include_all_data: true
                });

                // Add search
                if (this.state.searchQuery) {
                    params.append('search', this.state.searchQuery);
                }

                // Add date filters
                const startDate = document.getElementById('startDate').value;
                const endDate = document.getElementById('endDate').value;
                if (startDate) params.append('start_date', startDate);
                if (endDate) params.append('end_date', endDate);

                // Add filter values (Select2)
                const addMultiselectFilter = (name, filterId) => {
                    const values = this.getMultiselectValues(filterId);
                    if (values && values.length > 0) {
                        values.forEach(value => {
                            params.append(name, value);
                        });
                    }
                };

                addMultiselectFilter('status_filter', 'statusFilter');
                addMultiselectFilter('buyer_head', 'buyerHeadFilter');
                addMultiselectFilter('buyer', 'buyerFilter');
                addMultiselectFilter('supplier', 'supplierFilter');
                addMultiselectFilter('category', 'categoryFilter');
                addMultiselectFilter('purchFilter', 'purchFilter');
                addMultiselectFilter('po_team_member', 'poTeamMemberFilter');

                // Use AbortController for request cancellation
                if (this.state.abortController) {
                    this.state.abortController.abort();
                }
                this.state.abortController = new AbortController();

                // Add parameter to skip filter options in response
                params.append('skip_filters', '1');
                // Only include all data if charts need updating (first load or filter change)
                if (this.state.currentPage === 1) {
                    params.append('include_all_data', '1');
                }

                fetch(`../fetch/fetch-dash.php?${params.toString()}`, {
                    signal: this.state.abortController.signal,
                    cache: 'no-cache',
                    headers: {
                        'Cache-Control': 'no-cache'
                    }
                })
                    .then(response => {
                        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            // Use requestAnimationFrame for DOM updates to improve INP
                            requestAnimationFrame(() => {
                                this.state.data = data.data || [];
                                this.state.allFilteredData = data.all_filtered_data || [];
                                this.state.stats = data.stats || {};
                                this.state.pagination = data.pagination || {};

                                // Batch DOM updates
                                requestAnimationFrame(() => {
                                    this.renderTable();
                                    this.updateStats();
                                    this.updateCharts();
                                    this.renderPagination();
                                });
                            });
                        } else {
                            this.showError(data.message || 'Failed to load data');
                        }
                    })
                    .catch(error => {
                        if (error.name === 'AbortError') {
                            console.log('Request aborted');
                            return;
                        }
                        console.error('Error:', error);
                        this.showError('Failed to load data. Please try again.');
                    })
                    .finally(() => {
                        requestAnimationFrame(() => {
                            this.hideLoading();
                        });
                    });
            },

            populateFilters() {
                // Initialize all filters with AJAX - no need to load options upfront
                const filterConfigs = [
                    { id: 'statusFilter', url: '../fetch/fetch-filter-status.php', valueKey: null, labelKey: null },
                    { id: 'buyerHeadFilter', url: '../fetch/fetch-filter-buyer-head.php', valueKey: 'id', labelKey: 'username' },
                    { id: 'buyerFilter', url: '../fetch/fetch-filter-buyer.php', valueKey: 'id', labelKey: 'username' },
                    { id: 'supplierFilter', url: '../fetch/fetch-dash-suppliers-search.php', valueKey: 'id', labelKey: 'supplier' },
                    { id: 'categoryFilter', url: '../fetch/fetch-filter-category.php', valueKey: 'id', labelKey: 'maincat' },
                    { id: 'purchFilter', url: '../fetch/fetch-filter-purch.php', valueKey: 'id', labelKey: 'name' },
                    { id: 'poTeamMemberFilter', url: '../fetch/fetch-filter-po-team-member.php', valueKey: 'id', labelKey: 'username' }
                ];

                // Initialize filters with proper time slicing to avoid blocking
                let index = 0;
                const initNext = () => {
                    if (index < filterConfigs.length) {
                        const config = filterConfigs[index];
                        // Use requestIdleCallback for each filter to avoid blocking
                        if ('requestIdleCallback' in window) {
                            requestIdleCallback(() => {
                                this.initSelect2Ajax(config.id, config.url);
                                index++;
                                if (index < filterConfigs.length) {
                                    setTimeout(initNext, 10); // Small delay between filters
                                }
                            }, { timeout: 100 });
                        } else {
                            this.initSelect2Ajax(config.id, config.url);
                            index++;
                            if (index < filterConfigs.length) {
                                setTimeout(initNext, 10);
                            }
                        }
                    }
                };
                // Start initialization after a small delay
                setTimeout(initNext, 50);
            },

            initSelect2Ajax(filterId, url) {
                const $select = $(`#${filterId}`);
                if (!$select.length) return;

                // Clear any existing options
                $select.empty();

                // Initialize Select2 with AJAX
                const initSelect2 = () => {
                    requestAnimationFrame(() => {
                        const config = {
                            placeholder: `Select ${filterId.replace('Filter', '').replace(/([A-Z])/g, ' $1')}`,
                            allowClear: true,
                            width: '100%',
                            closeOnSelect: false,
                            theme: 'default',
                            maximumSelectionLength: 0,
                            dropdownAutoWidth: false,
                            selectOnClose: false,
                            minimumInputLength: 0, // Allow empty search to show top 4
                            ajax: {
                                url: url,
                                dataType: 'json',
                                delay: 300, // Debounce AJAX requests
                                data: function (params) {
                                    return {
                                        q: params.term || '', // search term (empty shows top 4)
                                        page: params.page || 1,
                                        per_page: 4 // Load 4 items per page
                                    };
                                },
                                processResults: function (data) {
                                    return {
                                        results: data.results || [],
                                        pagination: {
                                            more: data.pagination && data.pagination.more
                                        }
                                    };
                                },
                                cache: true,
                                transport: function (params, success, failure) {
                                    // Use AbortController for request cancellation
                                    const controller = new AbortController();
                                    const timeoutId = setTimeout(() => controller.abort(), 5000);
                                    
                                    const urlParams = new URLSearchParams(params.data);
                                    const fullUrl = params.url + '?' + urlParams.toString();
                                    
                                    fetch(fullUrl, {
                                        signal: controller.signal
                                    })
                                    .then(response => {
                                        if (!response.ok) {
                                            throw new Error(`HTTP error! status: ${response.status}`);
                                        }
                                        return response.json();
                                    })
                                    .then(data => {
                                        clearTimeout(timeoutId);
                                        success(data);
                                    })
                                    .catch(error => {
                                        clearTimeout(timeoutId);
                                        if (error.name !== 'AbortError') {
                                            console.error(`Error fetching ${filterId}:`, error);
                                            console.error('URL:', fullUrl);
                                            failure(error);
                                        }
                                    });
                                }
                            }
                        };

                        $select.select2(config);
                        
                        // Optimize search field interaction
                        setTimeout(() => {
                            this.optimizeSelect2Search(filterId);
                        }, 0);
                    });
                };

                // Use scheduler.postTask if available for better scheduling
                if ('scheduler' in window && 'postTask' in window.scheduler) {
                    scheduler.postTask(initSelect2, { priority: 'background' });
                } else if ('requestIdleCallback' in window) {
                    requestIdleCallback(initSelect2, { timeout: 1000 });
                } else {
                    setTimeout(initSelect2, 50);
                }
            },

            initSelect2(filterId, options, valueKey, labelKey) {
                if (!options || !Array.isArray(options)) return;

                const $select = $(`#${filterId}`);
                if (!$select.length) return;
                
                // Use AJAX for large lists (suppliers) to improve performance
                const isLargeList = options.length > 200;
                const isSupplier = filterId === 'supplierFilter';
                
                // Get current selected values to preserve them
                const currentValues = $select.val() || [];
                
                // Map new options once
                const newOptions = options.map(option => {
                    const value = valueKey ? option[valueKey] : option;
                    const label = labelKey ? option[labelKey] : option;
                    return { value: String(value), label: String(label) };
                });
                
                // Check if options actually changed - optimize this check
                let optionsChanged = true;
                if ($select.hasClass('select2-hidden-accessible')) {
                    const currentOptions = Array.from($select.find('option')).map(opt => ({
                        value: opt.value,
                        label: opt.text
                    }));
                    
                    // Quick length check first
                    if (currentOptions.length === newOptions.length) {
                        optionsChanged = JSON.stringify(currentOptions) !== JSON.stringify(newOptions);
                    }
                }
                
                if (!optionsChanged && $select.hasClass('select2-hidden-accessible')) {
                    return; // No need to update
                }

                // For large lists, only add selected options initially
                if (isLargeList && !isSupplier) {
                    // Limit initial options to first 100 for performance
                    const limitedOptions = newOptions.slice(0, 100);
                    const fragment = document.createDocumentFragment();
                    const selectElement = $select[0];
                    selectElement.innerHTML = '';
                    
                    limitedOptions.forEach(option => {
                        const opt = new Option(option.label, option.value, false, false);
                        fragment.appendChild(opt);
                    });
                    selectElement.appendChild(fragment);
                } else {
                    // Use DocumentFragment for batch DOM operations
                    const fragment = document.createDocumentFragment();
                    const selectElement = $select[0];
                    selectElement.innerHTML = '';

                    newOptions.forEach(option => {
                        const opt = new Option(option.label, option.value, false, false);
                        fragment.appendChild(opt);
                    });
                    selectElement.appendChild(fragment);
                }

                // Initialize Select2 if not already initialized - defer heavy work
                if (!$select.hasClass('select2-hidden-accessible')) {
                    // Defer Select2 initialization to avoid blocking
                    const initSelect2 = () => {
                        // Use requestAnimationFrame to ensure DOM is ready
                        requestAnimationFrame(() => {
                        const config = {
                            placeholder: `Select ${filterId.replace('Filter', '').replace(/([A-Z])/g, ' $1')}`,
                            allowClear: true,
                            width: '100%',
                            closeOnSelect: false,
                            theme: 'default',
                            maximumSelectionLength: 0,
                            dropdownAutoWidth: false,
                            selectOnClose: false
                        };

                            // Use AJAX for suppliers to avoid loading all options
                        if (isSupplier) {
                            config.minimumInputLength = 2; // Require 2 characters before searching
                            config.ajax = {
                                url: '../fetch/fetch-dash-suppliers-search.php',
                                dataType: 'json',
                                delay: 400, // Increased debounce for AJAX requests
                                data: function (params) {
                                    return {
                                        q: params.term, // search term
                                        page: params.page || 1
                                    };
                                },
                                processResults: function (data) {
                                    return {
                                        results: data.results || [],
                                        pagination: {
                                            more: data.pagination && data.pagination.more
                                        }
                                    };
                                },
                                cache: true,
                                transport: function (params, success, failure) {
                                    // Use AbortController for request cancellation
                                    const controller = new AbortController();
                                    const timeoutId = setTimeout(() => controller.abort(), 5000);
                                    
                                    const url = params.url + '?' + new URLSearchParams(params.data);
                                    
                                    fetch(url, {
                                        signal: controller.signal
                                    })
                                    .then(response => {
                                        if (!response.ok) {
                                            throw new Error(`HTTP error! status: ${response.status}`);
                                        }
                                        return response.json();
                                    })
                                    .then(data => {
                                        clearTimeout(timeoutId);
                                        success(data);
                                    })
                                    .catch(error => {
                                        clearTimeout(timeoutId);
                                        if (error.name !== 'AbortError') {
                                            console.error('Error fetching suppliers:', error);
                                            console.error('URL:', url);
                                            failure(error);
                                        }
                                    });
                                }
                            };
                            // Pre-populate with selected values if any
                            if (currentValues.length > 0) {
                                const selectedOptions = newOptions.filter(opt => 
                                    currentValues.includes(opt.value)
                                );
                                selectedOptions.forEach(opt => {
                                    const option = new Option(opt.label, opt.value, true, true);
                                    $select.append(option);
                                });
                            }
                        } else if (isLargeList) {
                            config.minimumInputLength = 1; // Require 1 character for large lists
                            // Optimize matcher for large lists
                            config.matcher = function(params, data) {
                                if (!params.term || params.term.trim() === '') {
                                    return data;
                                }
                                const term = params.term.toLowerCase();
                                const text = data.text.toLowerCase();
                                // Simple indexOf is faster than regex
                                if (text.indexOf(term) > -1) {
                                    return data;
                                }
                                return null;
                            };
                        } else {
                            config.minimumInputLength = 0;
                        }
                        
                        // Add search delay for all selects
                        config.language = {
                            inputTooShort: function() {
                                return 'Type at least ' + (config.minimumInputLength || 0) + ' characters';
                            }
                        };

                            $select.select2(config);
                            
                            // Add debounced search for non-AJAX selects to improve INP
                            if (!config.ajax && (isLargeList || options.length > 50)) {
                                // Defer search optimization
                                setTimeout(() => {
                                    this.addSearchDebounce($select, filterId);
                                }, 0);
                            }
                            
                            // Optimize search field interaction
                            setTimeout(() => {
                                this.optimizeSelect2Search(filterId);
                            }, 0);
                        });
                    };

                    // Use scheduler.postTask if available for better scheduling
                    if ('scheduler' in window && 'postTask' in window.scheduler) {
                        scheduler.postTask(initSelect2, { priority: 'background' });
                    } else if ('requestIdleCallback' in window) {
                        requestIdleCallback(initSelect2, { timeout: 1000 });
                    } else {
                        setTimeout(initSelect2, 50);
                    }
                } else {
                    // Restore selected values
                    if (currentValues.length > 0) {
                        const validValues = currentValues.filter(val => 
                            newOptions.some(opt => opt.value === String(val))
                        );
                        if (validValues.length > 0) {
                            setTimeout(() => {
                                $select.val(validValues).trigger('change');
                            }, 0);
                        }
                    }
                }
            },

            optimizeSelect2Search(filterId) {
                // Optimize search field to reduce INP
                const $select = $(`#${filterId}`);
                
                // Override Select2's matcher function for better performance
                $select.on('select2:open', () => {
                    // Use requestIdleCallback to defer search optimization
                    if ('requestIdleCallback' in window) {
                        requestIdleCallback(() => {
                            this.setupSearchOptimization(filterId);
                        }, { timeout: 100 });
                    } else {
                        setTimeout(() => {
                            this.setupSearchOptimization(filterId);
                        }, 0);
                    }
                });
            },

            addSearchDebounce($select, filterId) {
                // Add debouncing to Select2 search for non-AJAX selects
                let searchTimeout;
                let isProcessing = false;
                
                $select.on('select2:open', () => {
                    setTimeout(() => {
                        const searchField = document.querySelector(`.select2-container--open .select2-search__field`);
                        if (searchField && !searchField.dataset.debounced) {
                            searchField.dataset.debounced = 'true';
                            
                            // Store original value
                            let lastValue = searchField.value;
                            
                            // Override input event with debouncing
                            const handleInput = (e) => {
                                const currentValue = e.target.value;
                                
                                // Clear previous timeout
                                clearTimeout(searchTimeout);
                                
                                // If value hasn't changed, skip
                                if (currentValue === lastValue) return;
                                
                                // Use requestAnimationFrame to yield to browser
                                requestAnimationFrame(() => {
                                    // Debounce the search
                                    searchTimeout = setTimeout(() => {
                                        if (!isProcessing) {
                                            isProcessing = true;
                                            lastValue = currentValue;
                                            
                                            // Trigger Select2's search manually
                                            const select2Data = $select.data('select2');
                                            if (select2Data) {
                                                // Use Select2's internal search
                                                const query = { term: currentValue };
                                                select2Data.dataAdapter.query(query, (data) => {
                                                    select2Data.trigger('results:all', {
                                                        data: data,
                                                        query: query
                                                    });
                                                    isProcessing = false;
                                                });
                                            } else {
                                                isProcessing = false;
                                            }
                                        }
                                    }, 250); // 250ms debounce
                                });
                            };
                            
                            // Remove existing listener and add debounced one
                            searchField.removeEventListener('input', handleInput);
                            searchField.addEventListener('input', handleInput, { passive: true });
                            searchField.setAttribute('autocomplete', 'off');
                            searchField.setAttribute('spellcheck', 'false');
                        }
                    }, 50);
                });
            },

            setupSearchOptimization(filterId) {
                // This function is called when Select2 opens
                // Select2 already has built-in debouncing via the delay option for AJAX
                // We just need to ensure the search field is optimized
                const searchField = document.querySelector(`.select2-container--open .select2-search__field`);
                if (searchField) {
                    // Add passive attribute if possible
                    searchField.setAttribute('autocomplete', 'off');
                    searchField.setAttribute('spellcheck', 'false');
                }
            },

            getMultiselectValues(filterId) {
                const $select = $(`#${filterId}`);
                return $select.val() || [];
            },

            escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            },

            renderTable() {
                // Batch all DOM reads first to prevent forced reflow
                const tbody = document.getElementById('tableBody');
                const thead = document.getElementById('tableHead');
                const tableContainer = document.getElementById('tableContainer');
                const emptyState = document.getElementById('emptyState');
                const hasData = this.state.data && this.state.data.length > 0;

                // Use requestAnimationFrame to batch all DOM writes
                requestAnimationFrame(() => {
                    if (!hasData) {
                        tableContainer.style.display = 'none';
                        emptyState.style.display = 'block';
                        return;
                    }

                    tableContainer.style.display = 'block';
                    emptyState.style.display = 'none';

                    // Render table headers based on visible columns
                    const headerRow = document.createElement('tr');
                    this.state.visibleColumns.forEach(col => {
                        const th = document.createElement('th');
                        th.textContent = col.label;
                        headerRow.appendChild(th);
                    });
                    // Always show Actions column
                    const actionsTh = document.createElement('th');
                    actionsTh.textContent = 'Actions';
                    headerRow.appendChild(actionsTh);
                    thead.innerHTML = '';
                    thead.appendChild(headerRow);

                    // Use DocumentFragment for better performance
                    const fragment = document.createDocumentFragment();
                    
                    // Helper function to convert to title case
                    const toTitleCase = (str) => {
                        if (!str || typeof str !== 'string') return str || '';
                        return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
                    };
                    
                    // Helper function to get cell value
                    const getCellValue = (row, key) => {
                        switch(key) {
                            case 'id': return row.id || '-';
                            case 'status': return row.status || '-';
                            case 'supplier': return toTitleCase(row.supplier_name || row.supplier) || '-';
                            case 'supplier_code': return row.supplier_code || '-';
                            case 'b_head': return toTitleCase(row.b_head) || '-';
                            case 'buyer': return toTitleCase(row.buyer || row.buyername) || '-';
                            case 'buyername': return toTitleCase(row.buyername) || '-';
                            case 'po_team_member': return toTitleCase(row.po_team_member) || '-';
                            case 'pohead': return toTitleCase(row.pohead) || '-';
                            case 'purch_type': return toTitleCase(row.purch_type) || '-';
                            case 'categories': return row.categories ? toTitleCase(row.categories.split(',')[0].trim()) : '-';
                            case 'category_name': return toTitleCase(row.category_name) || '-';
                            case 'qty': return row.qty || '-';
                            case 'uom': return row.uom || '-';
                            case 'remark': return row.remark || '-';
                            case 'created_at': return this.formatDate(row.created_at);
                            case 'created_by_name': return row.created_by_name || '-';
                            case 'updated_at': return row.updated_at ? this.formatDate(row.updated_at) : '-';
                            case 'po_number': return row.po_number || '-';
                            case 'po_date': return this.formatDate(row.po_date);
                            case 'status_1': return row.status_1 ? this.formatDate(row.status_1) : '-';
                            case 'status_2': return row.status_2 ? this.formatDate(row.status_2) : '-';
                            case 'status_3': return row.status_3 ? this.formatDate(row.status_3) : '-';
                            case 'status_4': return row.status_4 ? this.formatDate(row.status_4) : '-';
                            case 'status_5': return row.status_5 ? this.formatDate(row.status_5) : '-';
                            case 'status_6': return row.status_6 ? this.formatDate(row.status_6) : '-';
                            case 'status_7': return row.status_7 ? this.formatDate(row.status_7) : '-';
                            default: return row[key] || '-';
                        }
                    };
                    
                    // Pre-compute all HTML to avoid multiple reflows
                    const rows = this.state.data.map(row => {
                        const tr = document.createElement('tr');
                        const statusColor = this.getStatusTextColor(row.status);
                        
                        // Add cells for visible columns
                        this.state.visibleColumns.forEach(col => {
                            const td = document.createElement('td');
                            if (col.key === 'status') {
                                td.className = statusColor;
                            } else if (col.key === 'id') {
                                td.className = 'font-semibold';
                            } else if (col.key === 'categories') {
                                const value = getCellValue(row, col.key);
                                td.innerHTML = `<div class="max-w-xs truncate" title="${value}">${value}</div>`;
                                tr.appendChild(td);
                                return;
                            }
                            td.textContent = getCellValue(row, col.key);
                            tr.appendChild(td);
                        });
                        
                        // Always add Actions column
                        const actionsTd = document.createElement('td');
                        actionsTd.innerHTML = `
                            <button class="btn btn-sm btn-primary" onclick="Dashboard.viewDetails(${row.id})">
                                View
                            </button>
                        `;
                        tr.appendChild(actionsTd);
                        
                        return tr;
                    });
                    
                    // Append all rows at once
                    rows.forEach(tr => fragment.appendChild(tr));
                    
                    // Single DOM write operation
                    tbody.innerHTML = '';
                    tbody.appendChild(fragment);
                });
            },

            getStatusTextColor(status) {
                if (!status) return '';
                const s = status.toLowerCase();
                if (s.includes('po generated') || s.includes('completed') || s.includes('done')) return 'text-success';
                if (s.includes('rejected') || s.includes('cancel')) return 'text-error';
                if (s.includes('awaiting') || s.includes('open') || s.includes('forwarded')) return 'text-warning';
                return 'text-info';
            },

            formatDate(dateString) {
                if (!dateString) return '-';
                const date = new Date(dateString);
                if (isNaN(date)) return '-';
                return date.toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            },

            updateStats() {
                const stats = this.state.stats;
                const pagination = this.state.pagination;

                // Update badge elements if they exist
                const completedBadge = document.getElementById('completedBadge');
                if (completedBadge) {
                    completedBadge.textContent = (stats.completed_count || 0).toLocaleString();
                }

                const totalRecords = document.getElementById('totalRecords');
                if (totalRecords) {
                    totalRecords.textContent = (pagination.total || 0).toLocaleString();
                }

                // Update pagination info
                const from = ((pagination.page - 1) * this.state.perPage) + 1;
                const to = Math.min(pagination.page * this.state.perPage, pagination.total || 0);
                
                const showingFrom = document.getElementById('showingFrom');
                if (showingFrom) showingFrom.textContent = from;
                
                const showingTo = document.getElementById('showingTo');
                if (showingTo) showingTo.textContent = to;
                
                const showingTotal = document.getElementById('showingTotal');
                if (showingTotal) showingTotal.textContent = (pagination.total || 0).toLocaleString();
            },

            renderPagination() {
                const pagination = this.state.pagination;
                const container = document.getElementById('pagination');
                container.innerHTML = '';

                if (!pagination || pagination.total_pages <= 1) return;

                // Previous button
                const prevBtn = document.createElement('button');
                prevBtn.className = `join-item btn ${pagination.page === 1 ? 'btn-disabled' : ''}`;
                prevBtn.innerHTML = '«';
                prevBtn.onclick = () => {
                    if (pagination.page > 1) {
                        this.state.currentPage = pagination.page - 1;
                        this.loadData();
                    }
                };
                container.appendChild(prevBtn);

                // Page numbers
                const maxVisible = 5;
                let start = Math.max(1, pagination.page - Math.floor(maxVisible / 2));
                let end = Math.min(pagination.total_pages, start + maxVisible - 1);
                if (end - start + 1 < maxVisible) {
                    start = Math.max(1, end - maxVisible + 1);
                }

                if (start > 1) {
                    const firstBtn = document.createElement('button');
                    firstBtn.className = 'join-item btn';
                    firstBtn.textContent = '1';
                    firstBtn.onclick = () => {
                        this.state.currentPage = 1;
                        this.loadData();
                    };
                    container.appendChild(firstBtn);
                    if (start > 2) {
                        const ellipsis = document.createElement('button');
                        ellipsis.className = 'join-item btn btn-disabled';
                        ellipsis.textContent = '...';
                        container.appendChild(ellipsis);
                    }
                }

                for (let i = start; i <= end; i++) {
                    const pageBtn = document.createElement('button');
                    pageBtn.className = `join-item btn ${i === pagination.page ? 'btn-active' : ''}`;
                    pageBtn.textContent = i;
                    pageBtn.onclick = () => {
                        this.state.currentPage = i;
                        this.loadData();
                    };
                    container.appendChild(pageBtn);
                }

                if (end < pagination.total_pages) {
                    if (end < pagination.total_pages - 1) {
                        const ellipsis = document.createElement('button');
                        ellipsis.className = 'join-item btn btn-disabled';
                        ellipsis.textContent = '...';
                        container.appendChild(ellipsis);
                    }
                    const lastBtn = document.createElement('button');
                    lastBtn.className = 'join-item btn';
                    lastBtn.textContent = pagination.total_pages;
                    lastBtn.onclick = () => {
                        this.state.currentPage = pagination.total_pages;
                        this.loadData();
                    };
                    container.appendChild(lastBtn);
                }

                // Next button
                const nextBtn = document.createElement('button');
                nextBtn.className = `join-item btn ${pagination.page === pagination.total_pages ? 'btn-disabled' : ''}`;
                nextBtn.innerHTML = '»';
                nextBtn.onclick = () => {
                    if (pagination.page < pagination.total_pages) {
                        this.state.currentPage = pagination.page + 1;
                        this.loadData();
                    }
                };
                container.appendChild(nextBtn);
            },

            async downloadData() {
                // Check if XLSX is available
                if (typeof XLSX === 'undefined') {
                    showToast('Excel export library not loaded. Please refresh the page.', 'error');
                    return;
                }
                
                showToast('Fetching all data for export...', 'info');
                
                try {
                    // Fetch all data without pagination
                    const params = new URLSearchParams({
                        page: 1,
                        per_page: 100000, // Large number to get all data
                        include_all_data: true
                    });

                    // Add current filters
                    if (this.state.searchQuery) {
                        params.append('search', this.state.searchQuery);
                    }

                    const filterIds = ['statusFilter', 'buyerHeadFilter', 'buyerFilter', 'supplierFilter', 'categoryFilter', 'purchFilter', 'poTeamMemberFilter'];
                    filterIds.forEach(filterId => {
                        const values = this.getMultiselectValues(filterId);
                        if (values && values.length > 0) {
                            params.append(filterId.replace('Filter', ''), values.join(','));
                        }
                    });

                    const startDate = document.getElementById('startDate').value;
                    const endDate = document.getElementById('endDate').value;
                    if (startDate) params.append('start_date', startDate);
                    if (endDate) params.append('end_date', endDate);

                    const response = await fetch(`../fetch/fetch-dash.php?${params.toString()}`);
                    const result = await response.json();

                    if (result.status !== 'success' || !result.data || result.data.length === 0) {
                        showToast('No data found to export', 'warning');
                        return;
                    }

                    const allData = result.data;
                    
                    // Prepare headers based on visible columns
                    const headers = this.state.visibleColumns.map(col => col.label);
                    
                    // Helper function to get cell value
                    const getCellValue = (row, key) => {
                        switch(key) {
                            case 'id': return row.id || '-';
                            case 'status': return row.status || '-';
                            case 'supplier': return row.supplier_name || row.supplier || '-';
                            case 'b_head': return row.b_head || '-';
                            case 'buyer': return row.buyer || row.buyername || '-';
                            case 'po_team_member': return row.po_team_member || '-';
                            case 'purch_type': return row.purch_type || '-';
                            case 'created_at': return row.created_at ? this.formatDate(row.created_at) : '-';
                            case 'po_number': return row.po_number || '-';
                            case 'po_date': return row.po_date ? this.formatDate(row.po_date) : '-';
                            case 'categories': return row.categories ? row.categories.split(',')[0].trim() : '-';
                            case 'pohead': return row.pohead || '-';
                            case 'supplier_id': return row.supplier_id || '-';
                            case 'buyername': return row.buyername || '-';
                            default: return row[key] || '-';
                        }
                    };
                    
                    // Prepare rows
                    const rows = allData.map(row => {
                        return this.state.visibleColumns.map(col => getCellValue(row, col.key));
                    });
                    
                    // Create worksheet
                    const ws = XLSX.utils.aoa_to_sheet([headers, ...rows]);
                    const wb = XLSX.utils.book_new();
                    XLSX.utils.book_append_sheet(wb, ws, "Dashboard Data");
                    
                    // Generate filename
                    const date = new Date();
                    const dateStr = date.toISOString().split('T')[0];
                    const filename = `Dashboard_Export_${dateStr}.xlsx`;
                    
                    // Download file
                    XLSX.writeFile(wb, filename);
                    
                    showToast(`Exported ${allData.length} records successfully`, 'success');
                } catch (error) {
                    console.error('Export error:', error);
                    showToast('An error occurred while exporting: ' + error.message, 'error');
                }
            },

            viewDetails(orderId) {
                const order = this.state.data.find(o => o.id == orderId) || 
                             this.state.allFilteredData.find(o => o.id == orderId);
                
                if (!order) return;

                document.getElementById('modalOrderId').textContent = order.id;
                const tbody = document.getElementById('detailsTableBody');
                
                // Show only first category
                const category = order.categories ? order.categories.split(',')[0].trim() : '-';
                
                tbody.innerHTML = `
                    <tr><th>PO Number</th><td>${order.po_number || '-'}</td></tr>
                    <tr><th>Status</th><td class="${this.getStatusTextColor(order.status)}">${order.status || '-'}</td></tr>
                    <tr><th>Supplier</th><td>${order.supplier_name || order.supplier || '-'}</td></tr>
                    <tr><th>Buyer Head</th><td>${order.b_head || '-'}</td></tr>
                    <tr><th>Buyer</th><td>${order.buyer || order.buyername || '-'}</td></tr>
                    <tr><th>PO Team Member</th><td>${order.po_team_member || '-'}</td></tr>
                    <tr><th>PO Head</th><td>${order.pohead || '-'}</td></tr>
                    <tr><th>Purchase Type</th><td>${order.purch_type || '-'}</td></tr>
                    <tr><th>Category</th><td>${category}</td></tr>
                    <tr><th>Created Date</th><td>${this.formatDate(order.created_at)}</td></tr>
                    <tr><th>PO Date</th><td>${this.formatDate(order.po_date)}</td></tr>
                `;

                document.getElementById('detailsModal').showModal();
            },

            showError(message) {
                showToast(message, 'error');
            }
        };

        // Initialize on page load
        // Ensure jQuery and Select2 are loaded before initializing
        if (typeof jQuery === 'undefined') {
            console.error('jQuery is not loaded. Please check the jQuery script path.');
        } else if (typeof jQuery.fn.select2 === 'undefined') {
            console.error('Select2 is not loaded. Please check the Select2 script path.');
        } else {
            // Wait for DOM to be fully ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => {
                    Dashboard.init();
                });
            } else {
                // DOM is already ready
                Dashboard.init();
            }
        }

        // Make Dashboard available globally
        window.Dashboard = Dashboard;
    </script>
<?php include '../common/layout-footer.php'; ?>
