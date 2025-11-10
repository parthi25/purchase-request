<?php include '../common/header.php'; ?>

<body>

    <?php include '../common/nav.php'; ?>
    <?php
    // session_start();
    if (!isset($_SESSION["user_id"]) && $_SESSION["role"] !== "PO_Head") {
        header("Location: ../index.php");
        exit;
    }
    ?>
    <!-- Filter bar for view toggle buttons -->
    <div class="p-4 bg-base-200 rounded-xl shadow">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">

            <!-- PO Member Dropdown + Search + Dates -->
            <div class="flex flex-wrap items-center gap-3">
                <select id="poMemberSelect" class="select select-bordered w-48">
                    <option value="">Select PO Member</option>
                </select>
                <input type="text" id="searchInput" placeholder="Search..." class="input input-bordered w-48 md:w-64" />
                <input type="date" id="fromDate" class="input input-bordered w-36" />
                <input type="date" id="toDate" class="input input-bordered w-36" />
            </div>

            <!-- Buttons -->
            <div class="flex flex-wrap items-center gap-3">
                <button id="applyFilters" class="btn btn-primary">Apply</button>
                <button id="clearFilters" class="btn btn-outline btn-secondary">Clear</button>
                <button id="openCreatePRBtn" class="btn btn-accent">Create PR</button>
            </div>

            <div class="flex gap-2 w-full md:w-auto">
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

        <div id="statusCounts" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-4 m-4"></div>
        <div id="activeStatus" class="text-center text-sm text-gray-500 mb-2"></div>

        <div id="view-container" class="p-4"></div>
        <?php include '../common/read-more-modal.php'; ?>
        <?php include '../common/file-upload.php'; ?>
        <?php include '../common/create-pr-modal.php'; ?>
        <?php include '../common/status-modal.php'; ?>

        <!-- Include card-renderer.js first -->
        <script src="../common/js/count-box-component.js"></script>
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
                    role: 'pohead',
                    selectedPoMemberId: null
                };

                // Load PO members dropdown
                fetch('../fetch/fetch-po-team.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            const poMemberSelect = document.getElementById('poMemberSelect');
                            data.data.forEach(member => {
                                const option = document.createElement('option');
                                option.value = member.id;
                                option.textContent = member.username;
                                poMemberSelect.appendChild(option);
                            });
                        }
                    })
                    .catch(error => console.error('Error loading PO members:', error));

                // Handle PO member selection
                document.getElementById('poMemberSelect').addEventListener('change', function () {
                    window.state.selectedPoMemberId = this.value;
                    window.state.offset = 0;
                    window.state.noMoreData = false;

                    console.log('PO Member changed to:', this.value);

                    // Update count box with selected PO member
                    initCountBoxComponent({
                        role: 'pohead',
                        buyer_id: this.value || <?php echo $_SESSION['user_id'] ?? 0; ?>,
                        apiEndpoint: '../fetch/fetch-status-count-poteam.php',
                        onStatusClick: function (statusId, statusKey) {
                            console.log('Count box status clicked:', statusId);
                            window.state.statusFilter = statusId;
                            localStorage.setItem("filter", statusId);
                            window.ViewMode.refreshView();
                        }
                    });

                    // Refresh the view with new filter
                    window.ViewMode.refreshView();
                });

                // Initial load - show all team counts (pass 0 or null to indicate no specific member)
                initCountBoxComponent({
                    role: 'pohead',
                    buyer_id: 0, // Pass 0 to indicate show all team counts
                    apiEndpoint: '../fetch/fetch-status-count-poteam.php',
                    onStatusClick: function (statusId, statusKey) {
                        console.log('Initial count box status clicked:', statusId);
                        window.state.statusFilter = statusId;
                        localStorage.setItem("filter", statusId);
                        // Refresh the view with the new filter
                        window.ViewMode.refreshView();
                    }
                });

                initViewMode({
                    containerId: 'view-container',
                    toggleContainerId: 'filterbar',
                    role: 'pohead'
                });
            });
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
    </div>
    <?php include '../common/footer.php' ?>
</body>