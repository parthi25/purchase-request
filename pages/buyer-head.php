<?php include '../common/layout.php'; ?>
    <!-- Create PR Button - Sticky Top Right -->
    <div class="hidden lg:block fixed top-20 right-6 z-40 pt-2">
        <button id="openCreatePRBtn" class="btn btn-accent shadow-lg">Create PR</button>
    </div>
    <div class="bg-base-200 border-base-300 collapse border mt-1 lg:mt-15">
  <input type="checkbox" class="peer" />
  <div
    class="collapse-title bg-base-200 text-base-content font-semibold flex items-center justify-between"
  >
   <span>FILTERS</span>
   <i class="fas fa-filter ml-auto"></i>
  </div>
  <div
    class="collapse-content bg-base-200"
  >
  <div class="lg:sticky lg:top-16 z-40 bg-base-100">
        <!-- Filter bar -->
        <div class="bg-base-200 rounded-xl shadow border border-base-300">
            <div class="p-4 flex flex-wrap items-center justify-between gap-3">
                    <!-- Buyer Dropdown + Search + Date Range + Filter Buttons -->
                    <div class="flex flex-wrap items-center gap-3">
                        <select id="buyerSelect" class="select select-bordered w-48">
                            <option value="">Select Buyer</option>
                        </select>
                        <input type="text" id="searchInput" placeholder="Search..." class="input input-bordered w-48 md:w-64" />
                        <input type="text" id="dateRange" placeholder="Select Date Range" class="input input-bordered w-64" />
                        <button id="applyFilters" class="btn btn-outline btn-primary">Apply</button>
                        <button id="clearFilters" class="btn btn-outline btn-secondary">Clear</button>
                    </div>

                    <!-- Buttons -->
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="btn-group">
                            <button class="btn btn-outline view-toggle-btn active" data-view="table">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                                Table
                            </button>
                            <button class="btn btn-outline view-toggle-btn" data-view="cards">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                </svg>
                                Cards
                            </button>
                        </div>
                    </div>
                </div>
            <!-- Status Counts -->
            <div id="statusCounts" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-2 sm:gap-4 p-2 sm:p-4 border-t border-base-300"></div>
            <div id="activeStatus" class="text-center text-sm text-gray-500 mb-2 px-4 pb-2"></div>
        </div>
    </div>
  </div>
</div>

    <!-- Mobile-only sticky Create PR button -->
    <div class="lg:hidden fixed bottom-6 right-6 z-50">
        <button id="openCreatePRBtnMobile" class="btn btn-accent btn-circle btn-lg shadow-lg">
            <i class="fas fa-plus text-xl"></i>
        </button>
    </div>
        <div id="view-container" class="p-2 sm:p-4"></div>
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