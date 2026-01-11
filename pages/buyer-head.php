<?php include '../common/layout.php'; ?>
    <!-- Create PR Button - Sticky Top Right -->
<style>
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-scroll-in {
        animation: fadeInUp 0.5s ease-out forwards;
    }
</style>
    <!-- Modern Filter Bar (Not Sticky) -->
    <div class="mb-6 -mx-4 px-4 py-3 bg-base-100 border-b border-base-200 shadow-sm transition-all duration-300">
        <div class="flex flex-col md:flex-row items-center justify-between gap-4 max-w-7xl mx-auto">
            
            <!-- Left: Dropdown, Search & Date -->
            <div class="flex flex-wrap items-center gap-2 w-full md:w-auto flex-1">
                <select id="buyerSelect" class="select select-sm select-bordered w-full md:w-48">
                    <option value="">Select Buyer</option>
                </select>

                <div class="join shadow-sm w-full md:w-auto">
                    <div class="join-item flex items-center bg-base-100 px-3 border border-base-300 rounded-l-lg">
                        <i class="fas fa-search text-base-content/50"></i>
                    </div>
                    <input type="text" id="searchInput" placeholder="Search PO, Vendor..." class="join-item input input-sm input-bordered border-l-0 focus:outline-none w-full md:w-48" />
                </div>
                
                <input type="text" id="dateRange" placeholder="Date Range" class="input input-sm input-bordered shadow-sm w-full md:w-40" />
                
                <div class="flex items-center gap-2">
                    <button id="applyFilters" class="btn btn-sm btn-primary shadow-sm px-4 font-medium">Apply</button>
                    <button id="clearFilters" class="btn btn-sm btn-outline btn-error shadow-sm px-4 font-medium">Reset</button>
                </div>
            </div>

            <!-- Right: Actions & View Toggle -->
            <div class="flex items-center gap-3 w-full md:w-auto justify-end">
                <!-- Create PR Button (Desktop) -->
                <button id="openCreatePRBtn" class="hidden lg:flex btn btn-sm btn-accent shadow-sm gap-2">
                    <i class="fas fa-plus"></i> Create PR
                </button>

                <div class="divider divider-horizontal mx-0 hidden lg:flex"></div>

                <div class="join shadow-sm">
                    <button class="join-item btn btn-sm btn-outline view-toggle-btn active px-4 font-medium" data-view="table" title="Table View">
                        Table
                    </button>
                    <button class="join-item btn btn-sm btn-outline view-toggle-btn px-4 font-medium" data-view="cards" title="Card View">
                        Card
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Quick Status Pills (Horizontal Scroll) -->
        <div id="statusCounts" class="flex items-center gap-2 overflow-x-auto py-2 mt-2 no-scrollbar">
             <!-- Populated by JS -->
        </div>
    </div>

    <!-- Mobile-only sticky Create PR button -->
    <div class="lg:hidden fixed bottom-6 right-6 z-50">
        <button id="openCreatePRBtnMobile" class="btn btn-accent btn-circle btn-lg shadow-lg">
            <i class="fas fa-plus text-xl"></i>
        </button>
    </div>

    <div id="view-container" class="p-2 sm:p-4 animate-scroll-in"></div>
        <?php include '../common/read-more-modal.php'; ?>
        <?php include '../common/file-upload.php'; ?>
        <?php include '../common/create-pr-modal.php'; ?>
        <?php include '../common/status-modal.php'; ?>

        <!-- Include notifications.js first -->
        <script src="../common/js/notifications.js"></script>
        <script src="../common/js/count-box-component.js"></script>
        <!-- Include card-renderer.js first -->
        <script src="../common/js/table-renderer.js"></script>
        <script src="../common/js/card-renderer.js"></script>
        <script src="../common/js/view-mode.js"></script>
        <script src="../common/js/create-pr.js"></script>
        <script src="../common/js/read-more.js"></script>
        <script src="../common/js/file-upload.js"></script>
        <script src="../common/js/status-modal.js"></script>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                console.log(localStorage.getItem("viewMode"));
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
                    role: 'bhead',
                    selectedBuyerId: null
                };

                // Load buyers dropdown
                const buyerSelect = document.getElementById('buyerSelect');
                if (buyerSelect) {
                    fetch('../fetch/fetch-buyer.php')
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                data.data.forEach(buyer => {
                                    const option = document.createElement('option');
                                    option.value = buyer.id;
                                    option.textContent = buyer.username;
                                    buyerSelect.appendChild(option);
                                });
                            }
                        })
                        .catch(error => console.error('Error loading buyers:', error));

                    // Handle buyer selection
                    buyerSelect.addEventListener('change', function () {
                    window.state.selectedBuyerId = this.value;
                    window.state.offset = 0;
                    window.state.noMoreData = false;

                    console.log('Buyer changed to:', this.value);

                    initCountBoxComponent({
                        role: 'bhead',
                        buyer_id: this.value || <?php echo $_SESSION['user_id'] ?? 0; ?>,
                        onStatusClick: function (statusId, statusKey) {
                            console.log('Count box status clicked:', statusId);
                            window.state.statusFilter = statusId;
                            localStorage.setItem("filter", statusId);
                            window.ViewMode.refreshView();
                        }
                    });
                    window.ViewMode.refreshView();
                    });
                }

                initCountBoxComponent({
                    role: 'bhead',
                    buyer_id: <?php echo $_SESSION['user_id'] ?? 0; ?>,
                    onStatusClick: function (statusId, statusKey) {
                        console.log('Initial count box status clicked:', statusId);
                        window.state.statusFilter = statusId;
                        localStorage.setItem("filter", statusId);
                        // Refresh the view with the new filter
                        window.ViewMode.refreshView();
                    }
                });

                // Initialize Flatpickr date range picker
                const dateRangeInput = document.getElementById('dateRange');
                if (dateRangeInput) {
                    flatpickr(dateRangeInput, {
                        mode: "range",
                        dateFormat: "Y-m-d",
                        onChange: function(selectedDates, dateStr, instance) {
                            if (selectedDates.length === 2) {
                                window.state.from = selectedDates[0].toISOString().split('T')[0];
                                window.state.to = selectedDates[1].toISOString().split('T')[0];
                            } else if (selectedDates.length === 0) {
                                window.state.from = '';
                                window.state.to = '';
                            }
                        }
                    });
                }

                initViewMode({
                    containerId: 'view-container',
                    toggleContainerId: 'filterbar',
                    role: 'bhead'
                });
            });
            const openBtn = document.getElementById('openCreatePRBtn');
            const openBtnMobile = document.getElementById('openCreatePRBtnMobile');
            const modal = document.getElementById('create_modal');
            const editPRBtns = document.querySelectorAll('.openEditPRBtn');

            if (openBtn && modal) {
                openBtn.addEventListener('click', () => openPRModal());
            }
            if (openBtnMobile && modal) {
                openBtnMobile.addEventListener('click', () => openPRModal());
            }
            editPRBtns.forEach((btn) => {
                btn.addEventListener('click', () => {
                    const prId = btn.dataset.prId;
                    openPRModal(prId);
                });
            });

            document.addEventListener('click', function (e) {
                // Check if the clicked element or its parent has the class 'update-status'
                const btn = e.target.closest('.update-status');
                if (!btn) return; // Not a status button

                const prId = btn.dataset.id;
                const currentStatus = btn.dataset.status;

                // Open the modal and pass the PR ID & current status
                openStatusModal(prId, currentStatus);
            });

        </script>
<?php include '../common/layout-footer.php'; ?>