<?php include '../common/layout.php'; ?>

            <!-- Page Content -->
            <div class="p-4 lg:p-6">
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
                    <div class="bg-base-200 p-6 rounded-lg lg:col-span-2">
                        <h3 class="text-xl font-semibold mb-4">PR Count by Buyer</h3>
                        <canvas id="buyerChart"></canvas>
                    </div>
                </div>

                <!-- Data Table Section -->
                <div class="bg-base-200 p-6 rounded-lg">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-4">
                        <h2 class="text-2xl font-semibold">Purchase Orders</h2>
                        <div class="flex flex-wrap gap-2">
                            <div class="badge badge-primary badge-lg">Total: <span id="totalRecords">0</span></div>
                            <div class="badge badge-success badge-lg">Completed: <span id="completedBadge">0</span></div>
                        </div>
                    </div>

                    <!-- Loading State -->
                    <div id="loadingState" class="flex justify-center items-center py-12">
                        <span class="loading loading-spinner loading-lg text-primary"></span>
                    </div>

                    <!-- Table -->
                    <div class="overflow-x-auto" id="tableContainer" style="display: none;">
                        <table class="table table-zebra">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Status</th>
                                    <th>Supplier</th>
                                    <th>Buyer Head</th>
                                    <th>Buyer</th>
                                    <th>PO Team Member</th>
                                    <th>Purchase Type</th>
                                    <th>Created Date</th>
                                    <th>PO Number</th>
                                    <th>PO Date</th>
                                    <th>Categories</th>
                                    <th>Actions</th>
                                </tr>
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
                abortController: null
            },

            init() {
                this.setupDefaultDates();
                this.bindEvents();
                this.initCharts();
                
                // Load filters and data in parallel, but prioritize data
                this.loadFilters();
                this.loadData();
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
                            },
                            x: {
                                ticks: {
                                    maxRotation: 45,
                                    minRotation: 45
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
                        
                        // Sort by count descending and take top 20
                        const buyerEntries = buyerLabels.map((label, idx) => ({label, count: buyerData[idx]}))
                            .sort((a, b) => b.count - a.count)
                            .slice(0, 20);
                        
                        this.state.buyerChart.data.labels = buyerEntries.map(e => e.label);
                        this.state.buyerChart.data.datasets[0].data = buyerEntries.map(e => e.count);
                        this.state.buyerChart.update('none');
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

                    // Use DocumentFragment for better performance
                    const fragment = document.createDocumentFragment();
                    
                    // Pre-compute all HTML to avoid multiple reflows
                    const rows = this.state.data.map(row => {
                        const tr = document.createElement('tr');
                        const statusColor = this.getStatusTextColor(row.status);
                        tr.innerHTML = `
                            <td class="font-semibold">${row.id || '-'}</td>
                            <td class="${statusColor}">${row.status || '-'}</td>
                            <td>${row.supplier_name || row.supplier || '-'}</td>
                            <td>${row.b_head || '-'}</td>
                            <td>${row.buyername || row.buyer || '-'}</td>
                            <td>${row.po_team_member || '-'}</td>
                            <td>${row.purch_type || '-'}</td>
                            <td>${this.formatDate(row.created_at)}</td>
                            <td>${row.po_number || '-'}</td>
                            <td>${this.formatDate(row.po_date)}</td>
                            <td><div class="max-w-xs truncate" title="${row.categories || '-'}">${row.categories || '-'}</div></td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="Dashboard.viewDetails(${row.id})">
                                    View
                                </button>
                            </td>
                        `;
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

            viewDetails(orderId) {
                const order = this.state.data.find(o => o.id == orderId) || 
                             this.state.allFilteredData.find(o => o.id == orderId);
                
                if (!order) return;

                document.getElementById('modalOrderId').textContent = order.id;
                const tbody = document.getElementById('detailsTableBody');
                
                tbody.innerHTML = `
                    <tr><th>PO Number</th><td>${order.po_number || '-'}</td></tr>
                    <tr><th>Status</th><td class="${this.getStatusTextColor(order.status)}">${order.status || '-'}</td></tr>
                    <tr><th>Supplier</th><td>${order.supplier_name || order.supplier || '-'}</td></tr>
                    <tr><th>Buyer Head</th><td>${order.b_head || '-'}</td></tr>
                    <tr><th>Buyer</th><td>${order.buyername || order.buyer || '-'}</td></tr>
                    <tr><th>PO Team Member</th><td>${order.po_team_member || '-'}</td></tr>
                    <tr><th>PO Head</th><td>${order.pohead || '-'}</td></tr>
                    <tr><th>Purchase Type</th><td>${order.purch_type || '-'}</td></tr>
                    <tr><th>Categories</th><td>${order.categories || '-'}</td></tr>
                    <tr><th>Created Date</th><td>${this.formatDate(order.created_at)}</td></tr>
                    <tr><th>PO Date</th><td>${this.formatDate(order.po_date)}</td></tr>
                `;

                document.getElementById('detailsModal').showModal();
            },

            showError(message) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: message
                    });
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: message,
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000
                        });
                    }
                }
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
