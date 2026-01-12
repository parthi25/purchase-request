<?php include '../common/layout.php'; ?>
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
    <div class="mb-6 -mx-4 px-4 py-3 bg-base-200 border-b border-base-200 shadow-sm transition-all duration-300">
        <div class="flex flex-col md:flex-row items-center justify-between gap-4 max-w-7xl mx-auto">
            
            <!-- Left: Search & Date -->
            <div class="flex flex-wrap items-center gap-2 w-full md:w-auto flex-1">
                <div class="join shadow-sm w-full md:w-auto">
                    <div class="join-item flex items-center bg-base-100 px-3 border border-base-300 rounded-l-lg">
                        <i class="fas fa-search text-base-content/50"></i>
                    </div>
                    <input type="text" id="searchInput" placeholder="Search PO, Vendor..." class="join-item input input-sm input-bordered border-l-0 focus:outline-none w-full md:w-48" />
                </div>
                
                <input type="text" id="dateRange" placeholder="Date Range" class="input input-sm input-bordered shadow-sm w-full md:w-40" />
            </div>

            <!-- Right: Actions & View Toggle -->
            <div class="flex flex-wrap items-center gap-2 w-full md:w-auto justify-end">
                <!-- Filter Buttons & View Toggle in Single Row on Mobile -->
                <div class="flex items-center gap-2 flex-1 sm:flex-none">
                    <button id="applyFilters" class="btn btn-sm btn-primary shadow-sm px-3 sm:px-4 font-medium flex-1 sm:flex-none">Apply</button>
                    <button id="clearFilters" class="btn btn-sm btn-outline btn-error shadow-sm px-3 sm:px-4 font-medium flex-1 sm:flex-none">Reset</button>
                </div>

                <!-- Create PR Button (Desktop) -->
                <?php if (!in_array($_SESSION['role'] ?? '', ['PO_Head', 'PO_Team_Member'])): ?>
                <button id="openCreatePRBtn" class="hidden lg:flex btn btn-sm btn-accent shadow-sm gap-2">
                    <i class="fas fa-plus"></i> Create PR
                </button>
                <div class="divider divider-horizontal mx-0 hidden lg:flex"></div>
                <?php endif; ?>

                <div class="join shadow-sm flex-1 sm:flex-none">
                    <button class="join-item btn btn-sm btn-outline view-toggle-btn active px-3 sm:px-4 font-medium flex-1 sm:flex-none" data-view="table" title="Table View">
                        Table
                    </button>
                    <button class="join-item btn btn-sm btn-outline view-toggle-btn px-3 sm:px-4 font-medium flex-1 sm:flex-none" data-view="cards" title="Card View">
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

    <?php if (!in_array($_SESSION['role'] ?? '', ['PO_Head', 'PO_Team_Member'])): ?>
    <!-- Mobile-only sticky Create PR button -->
    <div class="lg:hidden fixed bottom-6 right-6 z-50">
        <button id="openCreatePRBtnMobile" class="btn btn-accent btn-circle btn-lg shadow-lg">
            <i class="fas fa-plus text-xl"></i>
        </button>
    </div>
    <?php endif; ?>

    <div id="view-container" class="p-2 sm:p-4 animate-scroll-in"></div>
        <?php include '../common/read-more-modal.php'; ?>
        <?php include '../common/file-upload.php'; ?>
        <?php include '../common/create-pr-modal.php'; ?>
        <?php include '../common/status-modal.php'; ?>
        <?php include '../common/po-insert.php'; ?>

        <!-- Include card-renderer.js first -->
        <!-- Include notifications.js first -->
        <script src="../common/js/notifications.js"></script>
        <script src="../common/js/count-box-component.js"></script>
        <script src="../common/js/table-renderer.js"></script>
        <script src="../common/js/card-renderer.js"></script>
        <script src="../common/js/view-mode.js"></script>
        <script src="../common/js/create-pr.js"></script>
        <script src="../common/js/read-more.js"></script>
        <script src="../common/js/file-upload.js"></script>
        <script src="../common/js/status-modal.js"></script>
        <script src="../common/js/po-insert.js"></script>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                console.log(localStorage.getItem("viewMode"));
                // Initialize window.state for view mode
                window.state = {
                    offset: 0,
                    limit: 9,
                    loading: false,
                    noMoreData: false,
                    statusFilter: localStorage.getItem("filter") || 9,
                    search: '',
                    from: '',
                    to: '',
                    user_id: <?php echo $_SESSION['user_id'] ?? 0; ?>,
                    role: 'poteam'
                };

                initCountBoxComponent({
                    role: 'poteam',
                    buyer_id: this.value || <?php echo $_SESSION['user_id'] ?? 0; ?>,
                    apiEndpoint: '../fetch/fetch-status-count-poteam.php',
                    onStatusClick: function (statusId, statusKey) {
                        console.log('Count box status clicked:', statusId);
                        window.state.statusFilter = statusId;
                        localStorage.setItem("filter", statusId);
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
                    role: 'poteammember'
                });
            });
            const openBtn = document.getElementById('openCreatePRBtn');
            const openBtnMobile = document.getElementById('openCreatePRBtnMobile');
            
            // Hide buttons if role is PO_Head or PO_Team_Member
            const userRole = '<?php echo $_SESSION['role'] ?? ''; ?>';
            if (['PO_Head', 'PO_Team_Member'].includes(userRole)) {
                if (openBtn) openBtn.style.display = 'none';
                if (openBtnMobile) openBtnMobile.style.display = 'none';
            }
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

            document.addEventListener('click', function (e) {
                // Check if the clicked element or its parent has the class 'insert-po'
                const btn = e.target.closest('.insert-po');
                if (!btn) return; // Not an insert PO button

                const recordId = btn.dataset.id;

                // Open the PO insert modal
                openPoInsertModal(recordId);
            });

        </script>
<?php include '../common/layout-footer.php'; ?>