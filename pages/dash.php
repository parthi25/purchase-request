<?php
session_start();
if (!isset($_SESSION["user_id"])) {
        header("Location: ../index.php");
    exit;
}
 ?>
<?php include '../common/header.php'; ?>
<body>
    <?php include '../common/nav.php'; ?>
    <div class="container mx-auto p-4">
        <!-- Header Card -->
        <div class="card bg-base-100 shadow-xl mb-4">
            <div class="card-body">
                <div class="flex justify-between items-center">
                    <h2 class="card-title">PR Dashboard</h2>
                    <button id="exportExcelBtn" class="btn btn-success">
                        Export to Excel
                    </button>
                </div>
                
                <!-- Filters -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-4">
                    <div class="form-control">
                        <label class="label"><span class="label-text">Buyer Head</span></label>
                        <select id="buyerHeadFilter" class="select select-bordered"><option value="">All Buyer Heads</option></select>
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text">Buyer</span></label>
                        <select id="buyerFilter" class="select select-bordered"><option value="">All Buyers</option></select>
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text">Supplier</span></label>
                        <select id="supplierFilter" class="select select-bordered"><option value="">All Suppliers</option></select>
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text">Category</span></label>
                        <select id="categoryFilter" class="select select-bordered"><option value="">All Categories</option></select>
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text">Purch Type</span></label>
                        <select id="purchFilter" class="select select-bordered"><option value="">All Purch Type</option></select>
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text">PO Team Members</span></label>
                        <select id="poTeamMemberFilter" class="select select-bordered"><option value="">All Members</option></select>
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text">Status</span></label>
                        <select id="statusFilter" class="select select-bordered"><option value="">All Statuses</option></select>
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text">Date Range</span></label>
                        <div class="flex gap-2">
                            <input type="date" id="startDate" class="input input-bordered">
                            <input type="date" id="endDate" class="input input-bordered">
                        </div>
                    </div>
                </div>
                
                <div class="flex gap-2 mt-4">
                    <button id="applyFilterBtn" class="btn btn-primary">Apply Filters</button>
                    <button id="resetFilterBtn" class="btn btn-outline">Reset</button>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="stats shadow">
                <div class="stat">
                    <div class="stat-figure text-primary">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div class="stat-title">Total PRs</div>
                    <div class="stat-value text-primary" id="totalPrCount">0</div>
                    <div class="stat-desc">Across all statuses</div>
                </div>
            </div>
            <div class="stats shadow">
                <div class="stat">
                    <div class="stat-figure text-secondary">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="stat-title">Avg Processing Time</div>
                    <div class="stat-value text-secondary" id="avgProcessingTime">0</div>
                    <div class="stat-desc">For completed PRs</div>
                </div>
            </div>
            <div class="stats shadow">
                <div class="stat">
                    <div class="stat-figure text-accent">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <div class="stat-title">Current Page</div>
                    <div class="stat-value text-accent" id="currentPageInfo">0</div>
                    <div class="stat-desc" id="filteredCountText">0 records filtered</div>
                </div>
            </div>
        </div>

        <!-- Data Table -->
        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="card-title">PR Entries</h3>
                    <div class="flex items-center gap-4">
                        <input type="text" placeholder="Search..." class="input input-bordered input-sm" id="searchInput">
                        <div class="badge badge-primary">Total: <span id="totalRecords">0</span></div>
                        <div class="badge badge-success">Completed: <span id="completedCount">0</span></div>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="table table-zebra">
                        <thead>
                            <tr>
                                <th>Order Ref</th>
                                <th>Status</th>
                                <th>Supplier</th>
                                <th>Buyer Head</th>
                                <th>Buyer</th>
                                <th>PO Team Member</th>
                                <th>Purch Type</th>
                                <th>Created Date</th>
                                <th>Total Age</th>
                                <th>Open To Buyer</th>
                                <th>Buyer To Supplier</th>
                                <th>Buyer To Buyer Head</th>
                                <th>Buyer Head To PO Team</th>
                                <th>PO Team To PO</th>
                                <th>PO NO</th>
                                <th>PO Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="dataTableBody"></tbody>
                    </table>
                </div>
                <div class="flex justify-between items-center mt-4">
                    <div class="flex items-center gap-2">
                        <span>Rows per page:</span>
                        <select id="perPageSelect" class="select select-bordered select-sm">
                            <option value="10" selected>10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                    <div class="join" id="pagination"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="fixed inset-0 bg-base-100 bg-opacity-90 flex items-center justify-center z-50 hidden">
        <div class="text-center">
            <span class="loading loading-spinner loading-lg text-primary"></span>
            <p class="mt-4 text-lg">Loading data...</p>
        </div>
    </div>

    <!-- View Details Modal -->
    <dialog id="detailsModal" class="modal">
        <div class="modal-box w-11/12 max-w-5xl">
            <h3 class="font-bold text-lg">PR Details - <span id="modalPrId"></span></h3>
            <div class="py-4">
                <div class="overflow-x-auto">
                    <table class="table table-zebra">
                        <tbody id="basicInfoTable"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-action">
                <form method="dialog">
                    <button class="btn">Close</button>
                </form>
            </div>
        </div>
    </dialog>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const Dashboard = {
        state: {
            currentPage: 1,
            perPage: 10,
            originalData: [],
            allFilteredData: [],
            filterOptions: {},
            stats: {},
            searchQuery: '',
            searchTimeout: null
        },

        init() {
            this.cacheDom();
            this.bindEvents();
            this.loadSavedFilters();
            this.loadData();
        },

        cacheDom() {
            this.dom = {
                buyerHead: document.getElementById('buyerHeadFilter'),
                buyer: document.getElementById('buyerFilter'),
                supplier: document.getElementById('supplierFilter'),
                status: document.getElementById('statusFilter'),
                purchFilter: document.getElementById('purchFilter'),
                categoryFilter: document.getElementById('categoryFilter'),
                poTeamMemberFilter: document.getElementById('poTeamMemberFilter'),
                startDate: document.getElementById('startDate'),
                endDate: document.getElementById('endDate'),
                applyBtn: document.getElementById('applyFilterBtn'),
                resetBtn: document.getElementById('resetFilterBtn'),
                perPageSelect: document.getElementById('perPageSelect'),
                tableBody: document.getElementById('dataTableBody'),
                totalRecords: document.getElementById('totalRecords'),
                completedCount: document.getElementById('completedCount'),
                pagination: document.getElementById('pagination'),
                loadingOverlay: document.getElementById('loadingOverlay'),
                totalPrCount: document.getElementById('totalPrCount'),
                avgProcessingTime: document.getElementById('avgProcessingTime'),
                currentPageInfo: document.getElementById('currentPageInfo'),
                filteredCountText: document.getElementById('filteredCountText'),
                exportExcelBtn: document.getElementById('exportExcelBtn'),
                searchInput: document.getElementById('searchInput'),
                detailsModal: document.getElementById('detailsModal'),
                modalPrId: document.getElementById('modalPrId'),
                basicInfoTable: document.getElementById('basicInfoTable')
            };

            // Set default date range (last 30 days)
            const endDate = new Date();
            const startDate = new Date();
            startDate.setDate(startDate.getDate() - 30);
            this.dom.startDate.value = startDate.toISOString().split('T')[0];
            this.dom.endDate.value = endDate.toISOString().split('T')[0];
        },

        bindEvents() {
            this.dom.applyBtn.addEventListener('click', () => {
                this.saveFilters();
                this.loadData(1);
            });

            this.dom.resetBtn.addEventListener('click', () => {
                this.resetFilters();
                this.loadData(1);
            });

            this.dom.perPageSelect.addEventListener('change', () => {
                this.state.perPage = parseInt(this.dom.perPageSelect.value);
                this.saveFilters();
                this.state.currentPage = 1;
                this.loadData(1);
            });

            this.dom.searchInput.addEventListener('input', (e) => {
                this.state.searchQuery = e.target.value.trim();
                clearTimeout(this.searchTimeout);
                this.searchTimeout = setTimeout(() => this.loadData(1), 500);
            });

            this.dom.exportExcelBtn.addEventListener('click', () => this.exportToExcel());
        },

        saveFilters() {
            const filters = {
                buyerHead: this.dom.buyerHead.value,
                buyer: this.dom.buyer.value,
                supplier: this.dom.supplier.value,
                status: this.dom.status.value,
                category: this.dom.categoryFilter.value,
                po_team_member: this.dom.poTeamMemberFilter.value,
                startDate: this.dom.startDate.value,
                endDate: this.dom.endDate.value,
                purchFilter: this.dom.purchFilter.value,
                perPage: this.state.perPage
            };
            localStorage.setItem('prDashboardFilters', JSON.stringify(filters));
        },

        loadSavedFilters() {
            const savedFilters = localStorage.getItem('prDashboardFilters');
            if (savedFilters) {
                const filters = JSON.parse(savedFilters);
                
                this.dom.buyerHead.value = filters.buyerHead || '';
                this.dom.buyer.value = filters.buyer || '';
                this.dom.supplier.value = filters.supplier || '';
                this.dom.status.value = filters.status || '';
                this.dom.categoryFilter.value = filters.category || '';
                this.dom.poTeamMemberFilter.value = filters.po_team_member || '';
                this.dom.purchFilter.value = filters.purchFilter || '';
                
                if (filters.startDate) this.dom.startDate.value = filters.startDate;
                if (filters.endDate) this.dom.endDate.value = filters.endDate;
                
                if (filters.perPage) {
                    this.state.perPage = parseInt(filters.perPage);
                    this.dom.perPageSelect.value = this.state.perPage;
                }
            }
        },

        showLoading() {
            this.dom.loadingOverlay.classList.remove('hidden');
        },

        hideLoading() {
            this.dom.loadingOverlay.classList.add('hidden');
        },

        showAlert(message, type = 'error') {
            // Create a simple toast notification
            const toast = document.createElement('div');
            toast.className = `toast toast-top toast-end`;
            toast.innerHTML = `
                <div class="alert alert-${type}">
                    <span>${message}</span>
                </div>
            `;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.remove();
            }, 5000);
        },

        loadData(page = 1) {
            this.showLoading();
            this.state.currentPage = page;

            const params = new URLSearchParams({
                page: page,
                per_page: this.state.perPage,
                start_date: this.dom.startDate.value,
                end_date: this.dom.endDate.value,
                include_all_data: true,
                search: this.state.searchQuery
            });

            // Add filter values if they exist
            const addParamIfNotEmpty = (paramName, value) => {
                if (value) {
                    params.append(paramName, value);
                }
            };

            addParamIfNotEmpty('status_filter', this.dom.status.value);
            addParamIfNotEmpty('buyer_head', this.dom.buyerHead.value);
            addParamIfNotEmpty('buyer', this.dom.buyer.value);
            addParamIfNotEmpty('supplier', this.dom.supplier.value);
            addParamIfNotEmpty('category', this.dom.categoryFilter.value);
            addParamIfNotEmpty('po_team_member', this.dom.poTeamMemberFilter.value);
            addParamIfNotEmpty('purchFilter', this.dom.purchFilter.value);

            fetch(`../fetch/fetch-dash.php?${params.toString()}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data && data.success) {
                        this.state.originalData = data.data || [];
                        this.state.allFilteredData = data.all_filtered_data || [];
                        this.state.filterOptions = data.options || {};
                        this.state.stats = data.stats || {};

                        this.renderTable(this.state.originalData);
                        this.populateFilters(data.options);
                        this.updateSummaryCards(data);
                        this.renderPagination(data.pagination || {});
                    } else {
                        this.showAlert(data ? data.message : 'Failed to load data', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error loading data:', error);
                    this.showAlert('Failed to load data. Please check console for details.', 'error');
                })
                .finally(() => {
                    this.hideLoading();
                });
        },

        populateFilters(options) {
            // Clear existing options
            const clearSelect = (select) => {
                select.innerHTML = '';
                select.appendChild(new Option('All ' + select.id.replace('Filter', '').replace(/([A-Z])/g, ' $1'), ''));
            };

            clearSelect(this.dom.status);
            clearSelect(this.dom.buyerHead);
            clearSelect(this.dom.buyer);
            clearSelect(this.dom.supplier);
            clearSelect(this.dom.categoryFilter);
            clearSelect(this.dom.poTeamMemberFilter);
            clearSelect(this.dom.purchFilter);

            // Populate status options
            if (options.status_options && Array.isArray(options.status_options)) {
                options.status_options.forEach(status => {
                    this.dom.status.appendChild(new Option(status, status));
                });
            }

            // Populate buyer heads
            if (options.buyer_head_options && Array.isArray(options.buyer_head_options)) {
                options.buyer_head_options.forEach(head => {
                    this.dom.buyerHead.appendChild(new Option(head.username, head.id));
                });
            }

            // Populate buyers
            if (options.buyer_options && Array.isArray(options.buyer_options)) {
                options.buyer_options.forEach(buyer => {
                    this.dom.buyer.appendChild(new Option(buyer.username, buyer.id));
                });
            }

            // Populate suppliers
            if (options.supplier_options && Array.isArray(options.supplier_options)) {
                options.supplier_options.forEach(supplier => {
                    this.dom.supplier.appendChild(new Option(supplier.supplier, supplier.id));
                });
            }

            // Populate categories
            if (options.category_options && Array.isArray(options.category_options)) {
                options.category_options.forEach(category => {
                    this.dom.categoryFilter.appendChild(new Option(category.maincat, category.id));
                });
            }

            // Populate PO team members
            if (options.po_team_member_options && Array.isArray(options.po_team_member_options)) {
                options.po_team_member_options.forEach(member => {
                    this.dom.poTeamMemberFilter.appendChild(new Option(member.username, member.id));
                });
            }

            // Populate purch options
            if (options.purch_options && Array.isArray(options.purch_options)) {
                options.purch_options.forEach(purch => {
                    this.dom.purchFilter.appendChild(new Option(purch.name, purch.id));
                });
            }

            // Reload saved filters to set selected values
            this.loadSavedFilters();
        },

        formatDateTime(rawDate) {
            if (!rawDate) return "-";
            const date = new Date(rawDate);
            if (isNaN(date)) return "-";

            return date.toLocaleString("en-US", {
                month: "short",
                day: "2-digit",
                year: "numeric",
                hour: "numeric",
                minute: "2-digit",
                hour12: true
            });
        },

        calculateTimeDifference(start, end) {
            if (!start || !end) return "-";

            try {
                const startDate = new Date(start);
                let endDate = new Date(end);

                if (isNaN(startDate) || isNaN(endDate)) return "Invalid";

                if (endDate < startDate) {
                    endDate = new Date(startDate.getTime() + 30 * 60000); // Add 30 minutes
                }

                const diffMs = endDate - startDate;
                const totalMinutes = Math.floor(diffMs / (1000 * 60));

                if (totalMinutes < 60) {
                    return `${Math.round(totalMinutes)} min${totalMinutes !== 1 ? 's' : ''}`;
                }
                if (totalMinutes < 1440) {
                    const hours = Math.floor(totalMinutes / 60);
                    return `${hours} hour${hours !== 1 ? 's' : ''}`;
                }
                const days = Math.floor(totalMinutes / 1440);
                return `${days} day${days !== 1 ? 's' : ''}`;

            } catch (e) {
                console.error('Date calculation failed:', e);
                return "Error";
            }
        },

        getStatusColor(status) {
            if (!status) return '';
            status = status.toLowerCase();

            if (status.includes('po generated') || status.includes('completed') || status.includes('done')) 
                return 'text-success';
            if (status.includes('rejected') || status.includes('cancel')) 
                return 'text-error';
            if (status.includes('awaiting') || status.includes('open') || status.includes('forwarded') || status.includes('contacted') || status.includes('received')) 
                return 'text-warning';
            return 'text-info';
        },

        getStatusTag(timeString, isFinal = false) {
            if (!timeString || timeString === "-" || timeString.includes("Invalid") || timeString.includes("Error")) {
                return `<span class="badge badge-ghost">${isFinal ? 'In Process' : (timeString || '-')}</span>`;
            }

            const value = parseInt(timeString) || 0;
            const unit = timeString.includes('day') ? 'day' : timeString.includes('hour') ? 'hour' : 'min';
            
            let badgeClass = 'badge-';
            if (unit === 'day') {
                if (value <= 2) badgeClass += 'success';
                else if (value <= 5) badgeClass += isFinal ? 'warning' : 'info';
                else badgeClass += 'error';
            } else if (unit === 'hour') {
                if (value <= 24) badgeClass += 'success';
                else badgeClass += isFinal ? 'success' : 'info';
            } else if (unit === 'min') {
                badgeClass += 'success';
            } else {
                badgeClass += isFinal ? 'success' : 'info';
            }

            return `<span class="badge ${badgeClass}">${timeString}</span>`;
        },

        renderTable(rows) {
            this.dom.tableBody.innerHTML = '';

            if (!rows || !Array.isArray(rows)) {
                this.dom.tableBody.innerHTML = `
                    <tr>
                        <td colspan="17" class="text-center py-4 text-gray-500">
                            No data available
                        </td>
                    </tr>
                `;
                return;
            }

            if (rows.length === 0) {
                this.dom.tableBody.innerHTML = `
                    <tr>
                        <td colspan="17" class="text-center py-4 text-gray-500">
                            No records found matching your criteria
                        </td>
                    </tr>
                `;
                return;
            }

            rows.forEach((row, index) => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${row.id || '-'}</td>
                    <td class="${this.getStatusColor(row.status)}">${row.status || '-'}</td>
                    <td>${row.supplier ? row.supplier.toUpperCase() : '-'}</td>
                    <td>${row.b_head || '-'}</td>
                    <td>${row.buyername || row.buyer || '-'}</td>
                    <td>${row.po_team_member || '-'}</td>
                    <td>${row.purch_type || '-'}</td>
                    <td>${this.formatDateTime(row.created_at)}</td>
                    <td>${this.getStatusTag(this.calculateTimeDifference(row.created_at, row.status_7), true)}</td>
                    <td>${this.getStatusTag(this.calculateTimeDifference(row.created_at, row.status_2))}</td>
                    <td>${this.getStatusTag(this.calculateTimeDifference(row.status_2, row.status_3))}</td>
                    <td>${this.getStatusTag(this.calculateTimeDifference(row.status_3, row.status_4))}</td>
                    <td>${this.getStatusTag(this.calculateTimeDifference(row.status_4, row.status_5))}</td>
                    <td>${this.getStatusTag(this.calculateTimeDifference(row.status_5, row.status_7))}</td>
                    <td>${row.po_number || "-"}</td>
                    <td>${this.formatDateTime(row.po_date) || "-"}</td>
                    <td>
                        <button class="btn btn-sm btn-outline btn-primary" onclick="Dashboard.viewDetails(${index})">
                            View
                        </button>
                    </td>
                `;
                this.dom.tableBody.appendChild(tr);
            });

            this.dom.totalRecords.textContent = this.state.stats.total_orders || rows.length;
        },

        updateSummaryCards(resp) {
            this.dom.totalPrCount.textContent = resp.pagination ? resp.pagination.total.toLocaleString() : '0';
            
            const avgTime = parseFloat(resp.stats.avg_processing_time) || 0;
            const unit = resp.stats.avg_time_unit || 'days';
            
            // Format large numbers better
            let displayTime = `${avgTime} ${unit}`;
            if (avgTime > 1000 && unit === 'days') {
                const years = (avgTime / 365).toFixed(1);
                displayTime = `${years} years`;
            } else if (avgTime > 168 && unit === 'hours') {
                const weeks = (avgTime / 168).toFixed(1);
                displayTime = `${weeks} weeks`;
            } else if (avgTime > 72 && unit === 'hours') {
                const days = (avgTime / 24).toFixed(1);
                displayTime = `${days} days`;
            }
            
            this.dom.avgProcessingTime.textContent = displayTime;
            this.dom.completedCount.textContent = resp.stats.completed_count || '0';

            const currentPage = resp.pagination ? resp.pagination.page : 1;
            const totalPages = resp.pagination ? resp.pagination.total_pages : 1;
            const totalItems = resp.pagination ? resp.pagination.total : 0;
            const startItem = ((currentPage - 1) * this.state.perPage) + 1;
            const endItem = Math.min(currentPage * this.state.perPage, totalItems);

            this.dom.currentPageInfo.textContent = `${startItem}-${endItem} of ${totalItems}`;
            this.dom.filteredCountText.textContent = `${totalItems} records match your filters`;
        },

        renderPagination(pagination) {
            this.dom.pagination.innerHTML = '';

            if (!pagination || pagination.total_pages <= 1) return;

            // Previous button
            const prevDisabled = pagination.page === 1 ? 'btn-disabled' : '';
            const prevBtn = document.createElement('button');
            prevBtn.className = `join-item btn btn-sm ${prevDisabled}`;
            prevBtn.innerHTML = '«';
            prevBtn.addEventListener('click', () => {
                if (pagination.page > 1) this.loadData(pagination.page - 1);
            });
            this.dom.pagination.appendChild(prevBtn);

            // Page numbers
            const maxVisiblePages = 5;
            let startPage = Math.max(1, pagination.page - Math.floor(maxVisiblePages / 2));
            let endPage = Math.min(pagination.total_pages, startPage + maxVisiblePages - 1);

            if (endPage - startPage + 1 < maxVisiblePages) {
                startPage = Math.max(1, endPage - maxVisiblePages + 1);
            }

            if (startPage > 1) {
                const firstBtn = document.createElement('button');
                firstBtn.className = 'join-item btn btn-sm';
                firstBtn.textContent = '1';
                firstBtn.addEventListener('click', () => this.loadData(1));
                this.dom.pagination.appendChild(firstBtn);

                if (startPage > 2) {
                    const ellipsis = document.createElement('button');
                    ellipsis.className = 'join-item btn btn-sm btn-disabled';
                    ellipsis.textContent = '...';
                    this.dom.pagination.appendChild(ellipsis);
                }
            }

            for (let i = startPage; i <= endPage; i++) {
                const active = i === pagination.page ? 'btn-active' : '';
                const pageBtn = document.createElement('button');
                pageBtn.className = `join-item btn btn-sm ${active}`;
                pageBtn.textContent = i;
                pageBtn.addEventListener('click', () => this.loadData(i));
                this.dom.pagination.appendChild(pageBtn);
            }

            if (endPage < pagination.total_pages) {
                if (endPage < pagination.total_pages - 1) {
                    const ellipsis = document.createElement('button');
                    ellipsis.className = 'join-item btn btn-sm btn-disabled';
                    ellipsis.textContent = '...';
                    this.dom.pagination.appendChild(ellipsis);
                }

                const lastBtn = document.createElement('button');
                lastBtn.className = 'join-item btn btn-sm';
                lastBtn.textContent = pagination.total_pages;
                lastBtn.addEventListener('click', () => this.loadData(pagination.total_pages));
                this.dom.pagination.appendChild(lastBtn);
            }

            // Next button
            const nextDisabled = pagination.page === pagination.total_pages ? 'btn-disabled' : '';
            const nextBtn = document.createElement('button');
            nextBtn.className = `join-item btn btn-sm ${nextDisabled}`;
            nextBtn.innerHTML = '»';
            nextBtn.addEventListener('click', () => {
                if (pagination.page < pagination.total_pages) this.loadData(pagination.page + 1);
            });
            this.dom.pagination.appendChild(nextBtn);
        },

        resetFilters() {
            this.dom.buyerHead.value = '';
            this.dom.buyer.value = '';
            this.dom.supplier.value = '';
            this.dom.status.value = '';
            this.dom.categoryFilter.value = '';
            this.dom.poTeamMemberFilter.value = '';
            this.dom.purchFilter.value = '';

            // Reset date range to default (last 30 days)
            const endDate = new Date();
            const startDate = new Date();
            startDate.setDate(startDate.getDate() - 30);
            this.dom.startDate.value = startDate.toISOString().split('T')[0];
            this.dom.endDate.value = endDate.toISOString().split('T')[0];

            // Reset per page
            this.state.perPage = 10;
            this.dom.perPageSelect.value = '10';

            // Reset search
            this.state.searchQuery = '';
            this.dom.searchInput.value = '';

            // Clear saved filters
            localStorage.removeItem('prDashboardFilters');
        },

        viewDetails(index) {
            const record = this.state.originalData[index];
            if (!record) return;

            this.dom.modalPrId.textContent = record.id;
            this.dom.basicInfoTable.innerHTML = `
                <tr><th>PO Number</th><td>${record.po_number || '-'}</td></tr>
                <tr><th>Supplier</th><td>${record.supplier || '-'}</td></tr>
                <tr><th>Buyer Head</th><td>${record.b_head || '-'}</td></tr>
                <tr><th>Buyer</th><td>${record.buyer || '-'}</td></tr>
                <tr><th>PO Team Member</th><td>${record.po_team_member || '-'}</td></tr>
                <tr><th>Purch Type</th><td>${record.purch_type || '-'}</td></tr>
                <tr><th>Status</th><td class="${this.getStatusColor(record.status)}">${record.status || '-'}</td></tr>
                <tr><th>Created Date</th><td>${this.formatDateTime(record.created_at) || '-'}</td></tr>
                <tr><th>Completed Date</th><td>${this.formatDateTime(record.status_7) || 'Not Completed'}</td></tr>
                <tr><th>Total Processing Time</th><td>${this.calculateTimeDifference(record.created_at, record.status_7)}</td></tr>
            `;

            this.dom.detailsModal.showModal();
        },

        exportToExcel() {
            this.showAlert('Export functionality would be implemented here', 'info');
            // Implementation would depend on your backend export endpoint
        }
    };

    // Initialize dashboard
    Dashboard.init();

    // Make Dashboard available globally for button clicks
    window.Dashboard = Dashboard;
});
</script>
</body>