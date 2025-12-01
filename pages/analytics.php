<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION["user_id"])) {
    header("Location: ../index.php");
    exit;
}

$role = $_SESSION['role'] ?? '';
$username = $_SESSION['username'] ?? 'User';
$userid = $_SESSION['user_id'] ?? 0;
$currentPage = 'analytics.php';
?>
<?php include '../common/layout.php'; ?>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Analytics Dashboard</h1>
        <div class="flex gap-2">
            <button id="exportCSV" class="btn btn-success">
                <i class="fas fa-file-csv"></i> Export CSV
            </button>
            <button id="exportExcel" class="btn btn-success">
                <i class="fas fa-file-excel"></i> Export Excel
            </button>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="bg-base-200 p-4 rounded-lg mb-6">
        <h2 class="text-xl font-semibold mb-4">Filters</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div class="form-control">
                <label class="label">
                    <span class="label-text font-semibold">Date Range</span>
                </label>
                <input type="text" id="dateRange" placeholder="Select Date Range" class="input input-bordered w-full" />
            </div>
            <div class="form-control">
                <label class="label">
                    <span class="label-text font-semibold">Status</span>
                </label>
                <select id="statusFilter" class="select select-bordered w-full" multiple>
                </select>
            </div>
            <div class="form-control">
                <label class="label">
                    <span class="label-text font-semibold">Buyer</span>
                </label>
                <select id="buyerFilter" class="select select-bordered w-full" multiple>
                </select>
            </div>
            <div class="form-control">
                <label class="label">
                    <span class="label-text font-semibold">Category</span>
                </label>
                <select id="categoryFilter" class="select select-bordered w-full" multiple>
                </select>
            </div>
            <div class="form-control">
                <label class="label">
                    <span class="label-text font-semibold">Purchase Type</span>
                </label>
                <select id="purchFilter" class="select select-bordered w-full" multiple>
                </select>
            </div>
        </div>
        <div class="flex gap-2">
            <button id="applyFiltersBtn" class="btn btn-primary">
                <i class="fas fa-filter"></i> Apply Filters
            </button>
            <button id="resetFiltersBtn" class="btn btn-outline">
                <i class="fas fa-undo"></i> Reset
            </button>
        </div>
    </div>

    <!-- Charts Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Purchase Type Distribution -->
        <div class="bg-base-200 p-6 rounded-lg">
            <h3 class="text-xl font-semibold mb-4">Purchase Type Distribution</h3>
            <canvas id="purchaseTypeChart"></canvas>
        </div>

        <!-- Category Distribution -->
        <div class="bg-base-200 p-6 rounded-lg">
            <h3 class="text-xl font-semibold mb-4">Category Distribution</h3>
            <canvas id="categoryChart"></canvas>
        </div>

        <!-- Status Distribution -->
        <div class="bg-base-200 p-6 rounded-lg">
            <h3 class="text-xl font-semibold mb-4">Status Distribution</h3>
            <canvas id="statusChart"></canvas>
        </div>

        <!-- Buyer Distribution -->
        <div class="bg-base-200 p-6 rounded-lg">
            <h3 class="text-xl font-semibold mb-4">Buyer Distribution</h3>
            <canvas id="buyerChart"></canvas>
        </div>

        <!-- Supplier Distribution -->
        <div class="bg-base-200 p-6 rounded-lg">
            <h3 class="text-xl font-semibold mb-4">Supplier Distribution</h3>
            <canvas id="supplierChart"></canvas>
        </div>

        <!-- Monthly Trend -->
        <div class="bg-base-200 p-6 rounded-lg">
            <h3 class="text-xl font-semibold mb-4">Monthly PR Trend</h3>
            <canvas id="monthlyTrendChart"></canvas>
        </div>

        <!-- Status Over Time -->
        <div class="bg-base-200 p-6 rounded-lg lg:col-span-2">
            <h3 class="text-xl font-semibold mb-4">Status Over Time</h3>
            <canvas id="statusOverTimeChart"></canvas>
        </div>
    </div>

<!-- Data Table Modal -->
<dialog id="chartDataModal" class="modal">
    <div class="modal-box w-11/12 max-w-7xl">
        <h3 class="font-bold text-lg mb-4" id="modalTitle">Chart Data</h3>
        <div class="overflow-x-auto">
            <table class="table table-zebra w-full">
                <thead>
                    <tr>
                        <th>Ref ID</th>
                        <th>Created At</th>
                        <th>Buyer</th>
                        <th>Supplier</th>
                        <th>Purchase Type</th>
                        <th>Status</th>
                        <th>Category</th>
                        <th>Qty</th>
                        <th>UOM</th>
                        <th>Remark</th>
                    </tr>
                </thead>
                <tbody id="chartDataTableBody">
                    <tr>
                        <td colspan="10" class="text-center">Loading...</td>
                    </tr>
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

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
<script src="../assets/js/chart.umd.min.js"></script>
<script src="../assets/js/select2.min.js"></script>

<style>
    /* Responsive Flatpickr Calendar Styles */
    .flatpickr-calendar {
        max-width: 100% !important;
        width: auto !important;
    }
    
    .fp-side-panel {
        max-height: 400px;
        overflow-y: auto;
    }
    
    /* Mobile: Stack vertically */
    @media (max-width: 768px) {
        .flatpickr-calendar {
            flex-direction: column !important;
            max-width: 100vw !important;
            left: 50% !important;
            transform: translateX(-50%) !important;
            right: auto !important;
        }
        
        .flatpickr-calendar .fp-side-panel {
            border-right: none !important;
            border-top: 1px solid rgba(0, 0, 0, 0.1) !important;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1) !important;
            margin-right: 0 !important;
            margin-top: 0 !important;
            padding-top: 10px !important;
            width: 100% !important;
            flex-direction: row !important;
            flex-wrap: wrap !important;
            gap: 4px !important;
            max-height: 120px !important;
        }
        
        .flatpickr-calendar .fp-side-panel button {
            flex: 1 1 auto !important;
            min-width: auto !important;
            font-size: 11px !important;
            padding: 6px 8px !important;
        }
        
        .flatpickr-innerContainer {
            width: 100% !important;
        }
    }
    
    /* Tablet: Adjust layout */
    @media (min-width: 769px) and (max-width: 1024px) {
        .flatpickr-calendar {
            max-width: 90vw !important;
        }
        
        .flatpickr-calendar .fp-side-panel {
            width: 90px !important;
            font-size: 11px !important;
        }
        
        .flatpickr-calendar .fp-side-panel button {
            font-size: 11px !important;
            padding: 6px 8px !important;
        }
    }
    
    /* Small screens: Ensure calendar doesn't overflow */
    @media (max-width: 480px) {
        .flatpickr-calendar {
            width: calc(100vw - 20px) !important;
            left: 10px !important;
            right: 10px !important;
            transform: none !important;
        }
    }
</style>
<script>
$(document).ready(function() {
    const AnalyticsDashboard = {
        state: {
            charts: {},
            analyticsData: {},
            filters: {},
            dateRangePicker: null,
            startDate: '',
            endDate: '',
            resizeHandler: null
        },

        init() {
            this.initDateRangePicker();
            this.setupDefaultDates();
            this.initSelect2();
            this.initCharts();
            this.loadFilterOptions();
            this.bindEvents();
            // Load analytics after a short delay to ensure charts are initialized
            setTimeout(() => {
                this.loadAnalytics();
            }, 500);
        },

        initDateRangePicker() {
            const dateRangeInput = document.getElementById('dateRange');
            if (dateRangeInput) {
                this.state.dateRangePicker = flatpickr(dateRangeInput, {
                    mode: "range",
                    dateFormat: "Y-m-d",
                    onChange: (selectedDates, dateStr, instance) => {
                        if (selectedDates.length === 2) {
                            this.state.startDate = selectedDates[0].toISOString().split('T')[0];
                            this.state.endDate = selectedDates[1].toISOString().split('T')[0];
                        } else if (selectedDates.length === 0) {
                            this.state.startDate = '';
                            this.state.endDate = '';
                        }
                    },
                    onReady: (selectedDates, dateStr, instance) => {
                        this.addQuickSelectButtons(instance);
                        this.setupResponsiveHandlers(instance);
                    },
                    onOpen: (selectedDates, dateStr, instance) => {
                        this.addQuickSelectButtons(instance);
                        this.setupResponsiveHandlers(instance);
                    }
                });
            }
        },

        setupResponsiveHandlers(instance) {
            // Remove existing resize listener if any
            if (this.state.resizeHandler) {
                window.removeEventListener('resize', this.state.resizeHandler);
            }
            
            // Add resize listener to update layout on screen size change
            this.state.resizeHandler = () => {
                if (instance.calendarContainer && instance.isOpen) {
                    this.addQuickSelectButtons(instance);
                }
            };
            
            window.addEventListener('resize', this.state.resizeHandler);
        },

        addQuickSelectButtons(instance) {
            const calendarContainer = instance.calendarContainer;
            if (!calendarContainer) return;

            // Remove existing side panel if any
            const existingPanel = calendarContainer.querySelector('.fp-side-panel');
            if (existingPanel) {
                existingPanel.remove();
            }

            // Check screen size for responsive layout
            const isMobile = window.innerWidth <= 768;
            
            // Ensure calendar container uses flex layout
            if (calendarContainer.style.display !== 'flex') {
                calendarContainer.style.display = 'flex';
                // On mobile, stack vertically; on desktop, row-reverse (panel on left)
                calendarContainer.style.flexDirection = isMobile ? 'column' : 'row-reverse';
            }
            // Add min-width to fit content (but respect max-width from CSS)
            calendarContainer.style.minWidth = 'fit-content';
            calendarContainer.style.maxWidth = '100%';

            // Create a side panel
            const panel = document.createElement("div");
            panel.className = "fp-side-panel p-2 border-r mr-2 flex flex-col gap-1";
            // Responsive styling
            if (isMobile) {
                panel.style.cssText = 'margin-top: 0; padding-top: 10px; width: 100%; flex-direction: row; flex-wrap: wrap; gap: 4px; border-right: none; border-top: 1px solid rgba(0,0,0,0.1); max-height: 120px; overflow-y: auto;';
            } else {
                panel.style.cssText = 'margin-top: 20px; padding-top: 20px;';
            }

            // Get today's date
            const today = new Date();
            const yesterday = new Date(today);
            yesterday.setDate(yesterday.getDate() - 1);
            
            // Get first and last day of current month
            const firstDayThisMonth = new Date(today.getFullYear(), today.getMonth(), 1);
            const lastDayThisMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0);
            
            // Get first and last day of last month
            const firstDayLastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
            const lastDayLastMonth = new Date(today.getFullYear(), today.getMonth(), 0);

            // Get first and last day of current year
            const firstDayThisYear = new Date(today.getFullYear(), 0, 1);
            const lastDayThisYear = new Date(today.getFullYear(), 11, 31);
            
            // Get first and last day of last year
            const firstDayLastYear = new Date(today.getFullYear() - 1, 0, 1);
            const lastDayLastYear = new Date(today.getFullYear() - 1, 11, 31);

            // Get first and last day of current quarter
            const currentQuarter = Math.floor(today.getMonth() / 3);
            const firstDayThisQuarter = new Date(today.getFullYear(), currentQuarter * 3, 1);
            const lastDayThisQuarter = new Date(today.getFullYear(), (currentQuarter + 1) * 3, 0);
            
            // Get first and last day of last quarter
            const lastQuarter = currentQuarter === 0 ? 3 : currentQuarter - 1;
            const lastQuarterYear = currentQuarter === 0 ? today.getFullYear() - 1 : today.getFullYear();
            const firstDayLastQuarter = new Date(lastQuarterYear, lastQuarter * 3, 1);
            const lastDayLastQuarter = new Date(lastQuarterYear, (lastQuarter + 1) * 3, 0);

            const ranges = [
                { label: "Today", start: today, end: today, category: "day" },
                { label: "Yesterday", start: yesterday, end: yesterday, category: "day" },
                { label: "This Month", start: firstDayThisMonth, end: lastDayThisMonth, category: "month" },
                { label: "Last Month", start: firstDayLastMonth, end: lastDayLastMonth, category: "month" },
                { label: "This Quarter", start: firstDayThisQuarter, end: lastDayThisQuarter, category: "quarter" },
                { label: "Last Quarter", start: firstDayLastQuarter, end: lastDayLastQuarter, category: "quarter" },
                { label: "This Year", start: firstDayThisYear, end: lastDayThisYear, category: "year" },
                { label: "Last Year", start: firstDayLastYear, end: lastDayLastYear, category: "year" },
                { label: "Overall", start: null, end: null, category: "overall" }, // null means no date filter
            ];

            ranges.forEach(r => {
                const btn = document.createElement("button");
                btn.textContent = r.label;
                btn.className = "btn btn-xs btn-outline";
                // Responsive button styling
                if (isMobile) {
                    btn.style.cssText = 'white-space: nowrap; text-align: center; min-width: auto; flex: 1 1 auto; font-size: 11px; padding: 6px 8px;';
                } else {
                    btn.style.cssText = 'white-space: nowrap; text-align: left; min-width: fit-content; width: auto; padding-left: 12px; padding-right: 12px;';
                }
                btn.addEventListener("click", () => {
                    if (r.start === null && r.end === null) {
                        // Overall - clear dates
                        instance.clear();
                        this.state.startDate = '';
                        this.state.endDate = '';
                    } else {
                        instance.setDate([r.start, r.end], true);
                        this.state.startDate = r.start.toISOString().split('T')[0];
                        this.state.endDate = r.end.toISOString().split('T')[0];
                    }
                    setTimeout(() => instance.close(), 100);
                });
                panel.appendChild(btn);
            });

            calendarContainer.appendChild(panel);
        },

        setupDefaultDates() {
            const endDate = new Date();
            const startDate = new Date();
            startDate.setDate(startDate.getDate() - 30);
            
            if (this.state.dateRangePicker) {
                this.state.dateRangePicker.setDate([startDate, endDate], true);
                this.state.startDate = startDate.toISOString().split('T')[0];
                this.state.endDate = endDate.toISOString().split('T')[0];
            }
        },

        initSelect2() {
            $('#statusFilter, #buyerFilter, #categoryFilter, #purchFilter').select2({
                placeholder: 'Select options...',
                width: '100%'
            });
        },

        initCharts() {
            const self = this;
            
            // Purchase Type Chart
            this.state.charts.purchaseType = new Chart(document.getElementById('purchaseTypeChart'), {
                type: 'doughnut',
                data: { labels: [], datasets: [{ data: [], backgroundColor: [] }] },
                options: { 
                    responsive: true, 
                    maintainAspectRatio: true,
                    onClick: (event, elements) => {
                        if (elements.length > 0) {
                            const index = elements[0].index;
                            const label = self.state.charts.purchaseType.data.labels[index];
                            self.loadChartData('purchase_type', { purch_type: label });
                        }
                    }
                }
            });

            // Category Chart
            this.state.charts.category = new Chart(document.getElementById('categoryChart'), {
                type: 'pie',
                data: { labels: [], datasets: [{ data: [], backgroundColor: [] }] },
                options: { 
                    responsive: true, 
                    maintainAspectRatio: true,
                    onClick: (event, elements) => {
                        if (elements.length > 0) {
                            const index = elements[0].index;
                            const label = self.state.charts.category.data.labels[index];
                            self.loadChartData('category', { category_name: label });
                        }
                    }
                }
            });

            // Status Chart
            this.state.charts.status = new Chart(document.getElementById('statusChart'), {
                type: 'bar',
                data: { labels: [], datasets: [{ label: 'Count', data: [], backgroundColor: 'rgba(59, 130, 246, 0.8)' }] },
                options: { 
                    responsive: true, 
                    maintainAspectRatio: true, 
                    scales: { y: { beginAtZero: true } },
                    onClick: (event, elements) => {
                        if (elements.length > 0) {
                            const index = elements[0].index;
                            const label = self.state.charts.status.data.labels[index];
                            self.loadChartData('status', { status_name: label });
                        }
                    }
                }
            });

            // Buyer Chart
            this.state.charts.buyer = new Chart(document.getElementById('buyerChart'), {
                type: 'bar',
                data: { labels: [], datasets: [{ label: 'PR Count', data: [], backgroundColor: 'rgba(34, 197, 94, 0.8)' }] },
                options: { 
                    responsive: true, 
                    maintainAspectRatio: true, 
                    scales: { y: { beginAtZero: true }, x: { ticks: { maxRotation: 45, minRotation: 45 } } },
                    onClick: (event, elements) => {
                        if (elements.length > 0) {
                            const index = elements[0].index;
                            const label = self.state.charts.buyer.data.labels[index];
                            self.loadChartData('buyer', { buyer_name: label });
                        }
                    }
                }
            });

            // Supplier Chart
            this.state.charts.supplier = new Chart(document.getElementById('supplierChart'), {
                type: 'bar',
                data: { labels: [], datasets: [{ label: 'PR Count', data: [], backgroundColor: 'rgba(245, 158, 11, 0.8)' }] },
                options: { 
                    responsive: true, 
                    maintainAspectRatio: true, 
                    scales: { y: { beginAtZero: true }, x: { ticks: { maxRotation: 45, minRotation: 45 } } },
                    onClick: (event, elements) => {
                        if (elements.length > 0) {
                            const index = elements[0].index;
                            const label = self.state.charts.supplier.data.labels[index];
                            self.loadChartData('supplier', { supplier: label });
                        }
                    }
                }
            });

            // Monthly Trend Chart
            this.state.charts.monthlyTrend = new Chart(document.getElementById('monthlyTrendChart'), {
                type: 'line',
                data: { labels: [], datasets: [{ label: 'PR Count', data: [], borderColor: 'rgba(139, 92, 246, 1)', backgroundColor: 'rgba(139, 92, 246, 0.1)', fill: true, tension: 0.4 }] },
                options: { 
                    responsive: true, 
                    maintainAspectRatio: true, 
                    scales: { y: { beginAtZero: true } },
                    onClick: (event, elements) => {
                        if (elements.length > 0) {
                            const index = elements[0].index;
                            const label = self.state.charts.monthlyTrend.data.labels[index];
                            self.loadChartData('monthly_trend', { month: label });
                        }
                    }
                }
            });

            // Status Over Time Chart
            this.state.charts.statusOverTime = new Chart(document.getElementById('statusOverTimeChart'), {
                type: 'line',
                data: { labels: [], datasets: [] },
                options: { 
                    responsive: true, 
                    maintainAspectRatio: true, 
                    scales: { y: { beginAtZero: true } },
                    onClick: (event, elements) => {
                        if (elements.length > 0) {
                            const element = elements[0];
                            const datasetIndex = element.datasetIndex;
                            const index = element.index;
                            const dataset = self.state.charts.statusOverTime.data.datasets[datasetIndex];
                            const status = dataset.label;
                            const month = self.state.charts.statusOverTime.data.labels[index];
                            self.loadChartData('status_over_time', { status_name: status, month: month });
                        }
                    }
                }
            });
        },

        loadFilterOptions() {
            $.get('../fetch/fetch-dash-filters.php', (data) => {
                if (data.success || data.status === 'success') {
                    const opts = data.options || data.data?.options || data;
                    
                    // Status
                    if (opts.status_options) {
                        opts.status_options.forEach(s => {
                            $('#statusFilter').append(`<option value="${s.id}">${s.status}</option>`);
                        });
                    }

                    // Buyer
                    if (opts.buyer_options) {
                        opts.buyer_options.forEach(b => {
                            $('#buyerFilter').append(`<option value="${b.id}">${b.username}</option>`);
                        });
                    }

                    // Category
                    if (opts.category_options) {
                        opts.category_options.forEach(c => {
                            $('#categoryFilter').append(`<option value="${c.maincat}">${c.maincat}</option>`);
                        });
                    }

                    // Purchase Type
                    if (opts.purch_options) {
                        opts.purch_options.forEach(p => {
                            $('#purchFilter').append(`<option value="${p.id}">${p.name}</option>`);
                        });
                    }
                }
            }, 'json');
        },

        bindEvents() {
            $('#applyFiltersBtn').click(() => this.loadAnalytics());
            $('#resetFiltersBtn').click(() => {
                // Reset date range picker
                if (this.state.dateRangePicker) {
                    const endDate = new Date();
                    const startDate = new Date();
                    startDate.setDate(startDate.getDate() - 30);
                    this.state.dateRangePicker.setDate([startDate, endDate], true);
                    this.state.startDate = startDate.toISOString().split('T')[0];
                    this.state.endDate = endDate.toISOString().split('T')[0];
                }
                $('#statusFilter, #buyerFilter, #categoryFilter, #purchFilter').val(null).trigger('change');
                this.loadAnalytics();
            });

            $('#exportCSV').click(() => this.exportToCSV());
            $('#exportExcel').click(() => this.exportToExcel());
        },

        loadAnalytics() {
            const filters = {
                start_date: this.state.startDate,
                end_date: this.state.endDate,
                status: $('#statusFilter').val(),
                buyer: $('#buyerFilter').val(),
                category: $('#categoryFilter').val(),
                purch: $('#purchFilter').val()
            };

            $.get('../fetch/fetch-analytics.php', filters, (response) => {
                console.log('Analytics Response:', response);
                if (response.status === 'success' && response.data) {
                    this.state.analyticsData = response.data;
                    console.log('Analytics Data:', this.state.analyticsData);
                    this.updateCharts();
                } else {
                    console.error('Error loading analytics:', response);
                }
            }, 'json').fail(function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                console.error('Response:', xhr.responseText);
            });
        },

        updateCharts() {
            const data = this.state.analyticsData;
            console.log('Updating charts with data:', data);

            // Purchase Type
            if (data.purchase_type_distribution && Object.keys(data.purchase_type_distribution).length > 0) {
                const labels = Object.keys(data.purchase_type_distribution);
                const values = Object.values(data.purchase_type_distribution);
                this.state.charts.purchaseType.data.labels = labels;
                this.state.charts.purchaseType.data.datasets[0].data = values;
                this.state.charts.purchaseType.data.datasets[0].backgroundColor = this.generateColors(labels.length);
                this.state.charts.purchaseType.update();
                console.log('Purchase Type chart updated:', labels, values);
            }

            // Category
            if (data.category_distribution && Object.keys(data.category_distribution).length > 0) {
                const labels = Object.keys(data.category_distribution);
                const values = Object.values(data.category_distribution);
                this.state.charts.category.data.labels = labels;
                this.state.charts.category.data.datasets[0].data = values;
                this.state.charts.category.data.datasets[0].backgroundColor = this.generateColors(labels.length);
                this.state.charts.category.update();
                console.log('Category chart updated:', labels, values);
            }

            // Status
            if (data.status_distribution && Object.keys(data.status_distribution).length > 0) {
                const labels = Object.keys(data.status_distribution);
                const values = Object.values(data.status_distribution);
                this.state.charts.status.data.labels = labels;
                this.state.charts.status.data.datasets[0].data = values;
                this.state.charts.status.update();
                console.log('Status chart updated:', labels, values);
            }

            // Buyer
            if (data.buyer_distribution && Object.keys(data.buyer_distribution).length > 0) {
                const entries = Object.entries(data.buyer_distribution)
                    .sort((a, b) => b[1] - a[1])
                    .slice(0, 15);
                this.state.charts.buyer.data.labels = entries.map(e => e[0]);
                this.state.charts.buyer.data.datasets[0].data = entries.map(e => e[1]);
                this.state.charts.buyer.update();
                console.log('Buyer chart updated:', entries.length, 'entries');
            }

            // Supplier
            if (data.supplier_distribution && Object.keys(data.supplier_distribution).length > 0) {
                const entries = Object.entries(data.supplier_distribution)
                    .sort((a, b) => b[1] - a[1])
                    .slice(0, 15);
                this.state.charts.supplier.data.labels = entries.map(e => e[0]);
                this.state.charts.supplier.data.datasets[0].data = entries.map(e => e[1]);
                this.state.charts.supplier.update();
                console.log('Supplier chart updated:', entries.length, 'entries');
            }

            // Monthly Trend
            if (data.monthly_trend && Object.keys(data.monthly_trend).length > 0) {
                const labels = Object.keys(data.monthly_trend).sort();
                const values = labels.map(l => data.monthly_trend[l]);
                this.state.charts.monthlyTrend.data.labels = labels;
                this.state.charts.monthlyTrend.data.datasets[0].data = values;
                this.state.charts.monthlyTrend.update();
                console.log('Monthly Trend chart updated:', labels, values);
            }

            // Status Over Time
            if (data.status_over_time && Object.keys(data.status_over_time).length > 0) {
                const timeLabels = Object.keys(data.status_over_time).sort();
                const statuses = new Set();
                timeLabels.forEach(t => {
                    if (data.status_over_time[t]) {
                        Object.keys(data.status_over_time[t]).forEach(s => statuses.add(s));
                    }
                });

                if (statuses.size > 0) {
                    const datasets = Array.from(statuses).map((status, idx) => ({
                        label: status,
                        data: timeLabels.map(t => (data.status_over_time[t] && data.status_over_time[t][status]) || 0),
                        borderColor: this.generateColors(statuses.size)[idx],
                        backgroundColor: this.generateColors(statuses.size)[idx].replace('0.8)', '0.1)'),
                        fill: false,
                        tension: 0.4
                    }));

                    this.state.charts.statusOverTime.data.labels = timeLabels;
                    this.state.charts.statusOverTime.data.datasets = datasets;
                    this.state.charts.statusOverTime.update();
                    console.log('Status Over Time chart updated:', timeLabels.length, 'time points,', statuses.size, 'statuses');
                }
            }
        },

        generateColors(count) {
            const colors = [
                'rgba(239, 68, 68, 0.8)', 'rgba(245, 158, 11, 0.8)', 'rgba(34, 197, 94, 0.8)',
                'rgba(59, 130, 246, 0.8)', 'rgba(139, 92, 246, 0.8)', 'rgba(236, 72, 153, 0.8)',
                'rgba(20, 184, 166, 0.8)', 'rgba(251, 191, 36, 0.8)', 'rgba(168, 85, 247, 0.8)'
            ];
            const result = [];
            for (let i = 0; i < count; i++) {
                result.push(colors[i % colors.length]);
            }
            return result;
        },

        exportToCSV() {
            const data = this.state.analyticsData;
            let csv = 'Analytics Data\n\n';

            // Purchase Type
            csv += 'Purchase Type Distribution\n';
            csv += 'Type,Count\n';
            if (data.purchase_type_distribution) {
                Object.entries(data.purchase_type_distribution).forEach(([k, v]) => {
                    csv += `"${k}",${v}\n`;
                });
            }

            // Category
            csv += '\nCategory Distribution\n';
            csv += 'Category,Count\n';
            if (data.category_distribution) {
                Object.entries(data.category_distribution).forEach(([k, v]) => {
                    csv += `"${k}",${v}\n`;
                });
            }

            // Status
            csv += '\nStatus Distribution\n';
            csv += 'Status,Count\n';
            if (data.status_distribution) {
                Object.entries(data.status_distribution).forEach(([k, v]) => {
                    csv += `"${k}",${v}\n`;
                });
            }

            // Buyer
            csv += '\nBuyer Distribution\n';
            csv += 'Buyer,Count\n';
            if (data.buyer_distribution) {
                Object.entries(data.buyer_distribution).forEach(([k, v]) => {
                    csv += `"${k}",${v}\n`;
                });
            }

            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            saveAs(blob, `analytics_${new Date().toISOString().split('T')[0]}.csv`);
        },

        exportToExcel() {
            const data = this.state.analyticsData;
            const wb = XLSX.utils.book_new();

            // Purchase Type
            if (data.purchase_type_distribution) {
                const ws1 = XLSX.utils.aoa_to_sheet([
                    ['Purchase Type', 'Count'],
                    ...Object.entries(data.purchase_type_distribution)
                ]);
                XLSX.utils.book_append_sheet(wb, ws1, 'Purchase Type');
            }

            // Category
            if (data.category_distribution) {
                const ws2 = XLSX.utils.aoa_to_sheet([
                    ['Category', 'Count'],
                    ...Object.entries(data.category_distribution)
                ]);
                XLSX.utils.book_append_sheet(wb, ws2, 'Category');
            }

            // Status
            if (data.status_distribution) {
                const ws3 = XLSX.utils.aoa_to_sheet([
                    ['Status', 'Count'],
                    ...Object.entries(data.status_distribution)
                ]);
                XLSX.utils.book_append_sheet(wb, ws3, 'Status');
            }

            // Buyer
            if (data.buyer_distribution) {
                const ws4 = XLSX.utils.aoa_to_sheet([
                    ['Buyer', 'Count'],
                    ...Object.entries(data.buyer_distribution)
                ]);
                XLSX.utils.book_append_sheet(wb, ws4, 'Buyer');
            }

            XLSX.writeFile(wb, `analytics_${new Date().toISOString().split('T')[0]}.xlsx`);
        },

        loadChartData(chartType, additionalFilters = {}) {
            const baseFilters = {
                start_date: this.state.startDate,
                end_date: this.state.endDate,
                status: $('#statusFilter').val(),
                buyer: $('#buyerFilter').val(),
                category: $('#categoryFilter').val(),
                purch: $('#purchFilter').val()
            };

            const filters = { ...baseFilters, ...additionalFilters };
            
            // Set modal title
            let title = 'Chart Data';
            if (chartType === 'purchase_type' && additionalFilters.purch_type) {
                title = `Purchase Type: ${additionalFilters.purch_type}`;
            } else if (chartType === 'category' && additionalFilters.category_name) {
                title = `Category: ${additionalFilters.category_name}`;
            } else if (chartType === 'status' && additionalFilters.status_name) {
                title = `Status: ${additionalFilters.status_name}`;
            } else if (chartType === 'buyer' && additionalFilters.buyer_name) {
                title = `Buyer: ${additionalFilters.buyer_name}`;
            } else if (chartType === 'supplier' && additionalFilters.supplier) {
                title = `Supplier: ${additionalFilters.supplier}`;
            } else if (chartType === 'monthly_trend' && additionalFilters.month) {
                title = `Monthly Trend: ${additionalFilters.month}`;
            } else if (chartType === 'status_over_time' && additionalFilters.status_name && additionalFilters.month) {
                title = `Status: ${additionalFilters.status_name} - Month: ${additionalFilters.month}`;
            }
            
            $('#modalTitle').text(title);
            $('#chartDataTableBody').html('<tr><td colspan="10" class="text-center">Loading...</td></tr>');
            document.getElementById('chartDataModal').showModal();

            $.get('../fetch/fetch-analytics-records.php', filters, (response) => {
                if (response.status === 'success' && response.data) {
                    this.displayChartData(response.data);
                } else {
                    $('#chartDataTableBody').html(`<tr><td colspan="10" class="text-center text-error">Error loading data: ${response.message || 'Unknown error'}</td></tr>`);
                }
            }, 'json').fail(function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                $('#chartDataTableBody').html(`<tr><td colspan="10" class="text-center text-error">Error: ${error}</td></tr>`);
            });
        },

        displayChartData(data) {
            const tbody = $('#chartDataTableBody');
            tbody.empty();

            if (data.length === 0) {
                tbody.html('<tr><td colspan="10" class="text-center">No records found</td></tr>');
                return;
            }

            data.forEach(row => {
                const tr = $('<tr>');
                tr.append(`<td>${row.ref_id || row.id || '-'}</td>`);
                tr.append(`<td>${row.created_at ? new Date(row.created_at).toLocaleDateString() : '-'}</td>`);
                tr.append(`<td>${row.buyer || '-'}</td>`);
                tr.append(`<td>${row.supplier || '-'}</td>`);
                tr.append(`<td>${row.purch_type || '-'}</td>`);
                tr.append(`<td>${row.status_name || '-'}</td>`);
                tr.append(`<td>${row.categories || '-'}</td>`);
                tr.append(`<td>${row.qty || '-'}</td>`);
                tr.append(`<td>${row.uom || '-'}</td>`);
                tr.append(`<td>${row.remark || '-'}</td>`);
                tbody.append(tr);
            });
        }
    };

    AnalyticsDashboard.init();
});
</script>

<?php include '../common/layout-footer.php'; ?>

