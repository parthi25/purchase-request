<body class="min-h-screen bg-base-200">

<?php
include '../common/header.php';
include '../common/adminheader.php';
?>

<!-- Main Content -->
<div class="container mx-auto px-4 py-6">
    <!-- Page Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-base-content">Purchase Requisition Dashboard</h1>
        <p class="text-base-content/70 mt-2">Manage and track all purchase requests in your organization</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="stat bg-base-100 rounded-lg shadow-sm border border-base-300">
            <div class="stat-figure text-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
            </div>
            <div class="stat-title">Total PRs</div>
            <div class="stat-value text-primary">89</div>
            <div class="stat-desc">All purchase requests</div>
        </div>
        
        <div class="stat bg-base-100 rounded-lg shadow-sm border border-base-300">
            <div class="stat-figure text-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="stat-title">Pending Approval</div>
            <div class="stat-value text-secondary">24</div>
            <div class="stat-desc">Awaiting action</div>
        </div>
        
        <div class="stat bg-base-100 rounded-lg shadow-sm border border-base-300">
            <div class="stat-figure text-accent">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="stat-title">Completed This Month</div>
            <div class="stat-value text-accent">42</div>
            <div class="stat-desc">↑ 12% from last month</div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex flex-wrap gap-4 mb-6">
        <button class="btn btn-primary" id="openCreatePRBtn">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Create New PR
        </button>
        
        <button class="btn btn-outline openEditPRBtn" data-pr-id="23">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            Edit PR
        </button>
        
        <div class="dropdown dropdown-end">
            <div tabindex="0" role="button" class="btn btn-outline">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
                Filters
            </div>
            <div tabindex="0" class="dropdown-content z-[1] menu p-4 shadow bg-base-100 rounded-box w-80 mt-2">
                <div class="space-y-4">
                    <div>
                        <label class="label">
                            <span class="label-text">Status</span>
                        </label>
                        <select class="select select-bordered w-full">
                            <option disabled selected>Select status</option>
                            <option>All</option>
                            <option>Open</option>
                            <option>In Progress</option>
                            <option>Completed</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="label">
                            <span class="label-text">Date Range</span>
                        </label>
                        <div class="flex gap-2">
                            <input type="date" class="input input-bordered w-full" id="fromDate">
                            <input type="date" class="input input-bordered w-full" id="toDate">
                        </div>
                    </div>
                    
                    <div class="flex justify-end gap-2 mt-4">
                        <button class="btn btn-ghost btn-sm" id="clearFilters">Clear</button>
                        <button class="btn btn-primary btn-sm" id="applyFilters">Apply</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search and View Controls -->
    <div class="card bg-base-100 shadow-sm mb-6">
        <div class="card-body p-4">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div class="flex flex-col md:flex-row gap-4 w-full md:w-auto">
                    <div class="form-control w-full md:w-auto">
                        <div class="input-group">
                            <input type="text" id="searchInput" placeholder="Search PRs..." class="input input-bordered w-full md:w-64" />
                            <button class="btn btn-square">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <div class="flex gap-2">
                        <div class="form-control">
                            <label class="label cursor-pointer gap-2">
                                <input type="radio" name="status-radio" class="radio radio-primary" checked />
                                <span class="label-text">All</span>
                            </label>
                        </div>
                        <div class="form-control">
                            <label class="label cursor-pointer gap-2">
                                <input type="radio" name="status-radio" class="radio radio-warning" />
                                <span class="label-text">Pending</span>
                            </label>
                        </div>
                        <div class="form-control">
                            <label class="label cursor-pointer gap-2">
                                <input type="radio" name="status-radio" class="radio radio-success" />
                                <span class="label-text">Completed</span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="flex gap-2 w-full md:w-auto">
                    <div class="btn-group">
                        <button class="btn btn-outline view-toggle-btn active" data-view="table">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            Table
                        </button>
                        <button class="btn btn-outline view-toggle-btn" data-view="cards">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                            </svg>
                            Cards
                        </button>
                    </div>
                    
                    <div class="dropdown dropdown-end">
                        <div tabindex="0" role="button" class="btn btn-ghost btn-square">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                            </svg>
                        </div>
                        <ul tabindex="0" class="dropdown-content z-[1] menu p-2 shadow bg-base-100 rounded-box w-52">
                            <li><a>Export to CSV</a></li>
                            <li><a>Print Report</a></li>
                            <li><a>Refresh Data</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Container -->
    <div id="view-container" class="min-h-[400px]">
        <!-- Data will be loaded here by JavaScript -->
        <div class="flex justify-center items-center h-64">
            <div class="text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-base-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p class="mt-4 text-base-content/70">Loading purchase requisitions...</p>
            </div>
        </div>
    </div>
</div>

<!-- Modals -->
<?php include '../common/create-pr-modal.php'; ?>

<!-- JavaScript -->
<script src="../common/js/table-renderer.js"></script>
<script src="../common/js/card-renderer.js"></script>
<script src="../common/js/view-mode.js"></script>
<script src="../common/js/create-pr.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize window.state for view mode
    window.state = {
        offset: 0,
        limit: 9,
        loading: false,
        noMoreData: false,
        statusFilter: localStorage.getItem("filter") || 1,
        search: '',
        from: '',
        to: '',
        user_id: <?php echo $_SESSION['user_id'] ?? 0; ?>,
        role: 'admin'
    };

    // Initialize view mode
    initViewMode({
        containerId: 'view-container',
        toggleContainerId: 'filterbar',
        role: 'admin'
    });

    // Modal functionality
    const openBtn = document.getElementById('openCreatePRBtn');
    const modal = document.getElementById('create_modal');
    const editPRBtns = document.querySelectorAll('.openEditPRBtn');

    if (openBtn && modal) {
        openBtn.addEventListener('click', () => openPRModal());
    }
    
    editPRBtns.forEach((btn) => {
        btn.addEventListener('click', () => {
            const prId = btn.dataset.prId; 
            openPRModal(prId);
        });
    });

    // View toggle buttons
    const viewToggleBtns = document.querySelectorAll('.view-toggle-btn');
    viewToggleBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            viewToggleBtns.forEach(b => b.classList.remove('active', 'btn-primary'));
            this.classList.add('active', 'btn-primary');
        });
    });

    // Search functionality
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            // Add debounce for search
            clearTimeout(window.searchTimeout);
            window.searchTimeout = setTimeout(() => {
                window.state.search = this.value;
                window.state.offset = 0;
                ViewMode.refreshView();
            }, 500);
        });
    }
});
</script>

</body>