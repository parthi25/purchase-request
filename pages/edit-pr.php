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
$currentPage = 'edit-pr.php';
?>
<?php include '../common/layout.php'; ?>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Edit Purchase Request</h1>
    </div>
    
    <!-- Search Card -->
    <div class="card bg-base-100 shadow-xl mb-6">
        <div class="card-body">
            <h2 class="card-title mb-4 capitalize">
                <i class="fas fa-search"></i>
                Search PR by Reference ID
            </h2>
            <div class="form-control">
                <div class="join w-full">
                    <input type="number" id="searchRefId" class="input input-bordered join-item flex-1" placeholder="Enter PR Reference ID (e.g., 123)" min="1">
                    <button type="button" id="searchBtn" class="btn btn-primary join-item">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
            </div>
            <div id="searchError" class="alert alert-error mt-4 hidden"></div>
        </div>
    </div>

    <!-- PR List Table Card -->
    <div class="card bg-base-100 shadow-xl mb-6">
        <div class="card-body">
            <h2 class="card-title mb-4 capitalize">
                <i class="fas fa-list"></i>
                Purchase Requests List
            </h2>
            
            <!-- Search and Filter -->
            <div class="form-control mb-4">
                <div class="join w-full">
                    <input type="text" id="tableSearchInput" class="input input-bordered join-item flex-1" placeholder="Search by ID, Supplier, Category, or Status...">
                    <button type="button" id="tableSearchBtn" class="btn btn-primary join-item">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <button type="button" id="tableResetBtn" class="btn btn-outline join-item">
                        <i class="fas fa-redo"></i> Reset
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Supplier</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Buyer Head</th>
                            <th>Buyer</th>
                            <th>Qty</th>
                            <th>UOM</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="prTableBody">
                        <tr>
                            <td colspan="10" class="text-center">
                                <span class="loading loading-spinner loading-lg"></span>
                                <p class="mt-2">Loading PRs...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div id="paginationContainer" class="flex justify-center items-center gap-2 mt-4">
                <!-- Pagination will be rendered here -->
            </div>

            <!-- Empty State -->
            <div id="emptyState" class="text-center py-8 hidden">
                <i class="fas fa-inbox text-6xl text-base-content opacity-20 mb-4"></i>
                <h5 class="text-xl font-semibold">No PRs found</h5>
                <p>Try adjusting your search criteria</p>
            </div>
        </div>
    </div>

    <!-- Edit Form Card (Hidden initially) -->
    <div class="card bg-base-100 shadow-xl mb-6 hidden" id="editFormCard">
        <div class="card-body">
            <h2 class="card-title mb-4 capitalize">
                <i class="fas fa-edit"></i>
                <span id="formTitle">Edit PR #<span id="prRefId"></span></span>
            </h2>
            <form id="editPRForm" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <input type="hidden" name="id" id="prId">
                <input type="hidden" name="csrf_token" id="csrf_token" value="">

                <!-- Supplier Name -->
                <div class="form-control relative">
                    <label class="label"><span class="label-text">Supplier Name <span class="text-error">*</span></span></label>
                    <div class="relative">
                        <input type="text" class="input input-bordered w-full pr-10" id="supplierInput" name="supplierInput"
                            required autocomplete="off" placeholder="Type to search suppliers..." oninput="searchSupplierAPI()"
                            onfocus="showSupplierDropdown()" onkeydown="handleSupplierKeydown(event)">
                        <div id="supplierDropdown" class="absolute top-full left-0 right-0 z-10 mt-1 hidden">
                            <ul class="menu bg-base-200 rounded-box shadow-lg max-h-60 overflow-y-auto" id="supplierList"></ul>
                        </div>
                        <input type="hidden" id="supplierId" name="supplierId">
                    </div>
                </div>

                <!-- Agent Name -->
                <div class="form-control">
                    <label class="label"><span class="label-text">Agent Name</span></label>
                    <input type="text" class="input input-bordered w-full" id="agentInput" name="agentInput">
                </div>

                <!-- Agent City -->
                <div class="form-control">
                    <label class="label"><span class="label-text">Agent City</span></label>
                    <input type="text" class="input input-bordered w-full" id="cityInput" name="cityInput">
                </div>

                <!-- Purchases Type -->
                <div class="form-control">
                    <label class="label"><span class="label-text">Purchases Type</span></label>
                    <select class="select select-bordered w-full" id="purchInput" name="purchInput">
                    </select>
                </div>

                <!-- Category -->
                <div class="form-control relative">
                    <label class="label"><span class="label-text">Category <span class="text-error">*</span></span></label>
                    <input type="text" class="input input-bordered w-full" id="categoryInput" name="categoryInput" required
                        autocomplete="off" placeholder="Type or select category..." oninput="searchCategoryAPI()"
                        onfocus="showCategoryDropdown()" onkeydown="handleCategoryKeydown(event)">
                    <div id="categoryDropdown" class="absolute top-full left-0 right-0 z-10 mt-1 hidden">
                        <ul class="menu bg-base-200 rounded-box shadow-lg max-h-60 overflow-y-auto" id="categoryList"></ul>
                    </div>
                    <input type="hidden" id="categoryId" name="categoryId">
                </div>

                <!-- Buyer Head -->
                <div class="form-control">
                    <label class="label"><span class="label-text">Buyer Head <span class="text-error">*</span></span></label>
                    <input type="text" class="input input-bordered w-full" id="buyerHeadInput" name="buyerInput" readonly>
                    <input type="hidden" id="buyerId" name="buyerId">
                </div>

                <!-- Quantity -->
                <div class="form-control">
                    <label class="label"><span class="label-text">Quantity <span class="text-error">*</span></span></label>
                    <input type="number" class="input input-bordered w-full" id="qtyInput" name="qtyInput" required min="1" value="1">
                </div>

                <!-- Unit of Measure -->
                <div class="form-control">
                    <label class="label"><span class="label-text">Unit of Measure</span></label>
                    <select class="select select-bordered w-full" id="uomInput" name="uomInput">
                        <option value="Box">Box</option>
                        <option value="Bundle">Bundle</option>
                        <option value="Bunch">Bunch</option>
                        <option value="Kilogram">Kilogram</option>
                        <option value="Meter">Meter</option>
                        <option value="Pairs">Pairs</option>
                        <option value="Pcs" selected>Pcs</option>
                        <option value="Pocket">Pocket</option>
                    </select>
                </div>

                <!-- Remark -->
                <div class="form-control md:col-span-3">
                    <label class="label"><span class="label-text">PR Remark</span></label>
                    <textarea class="textarea textarea-bordered w-full" id="remarkInput" name="remarkInput" placeholder="Add remarks..."></textarea>
                </div>

                <!-- Divider: Workflow Fields -->
                <div class="form-control md:col-span-3">
                    <div class="divider">
                        <span class="text-lg font-bold">Workflow Fields</span>
                    </div>
                </div>

                <!-- Status -->
                <div class="form-control">
                    <label class="label"><span class="label-text">Status <span class="text-error">*</span></span></label>
                    <select class="select select-bordered w-full" id="statusSelect" name="po_status" required>
                        <option value="">Select Status</option>
                    </select>
                </div>

                <!-- Buyer -->
                <div class="form-control">
                    <label class="label"><span class="label-text">Buyer</span></label>
                    <select class="select select-bordered w-full" id="buyerSelect" name="buyer">
                        <option value="">Select Buyer</option>
                    </select>
                </div>

                <!-- PO Team -->
                <div class="form-control">
                    <label class="label"><span class="label-text">PO Team</span></label>
                    <select class="select select-bordered w-full" id="poTeamSelect" name="po_team">
                        <option value="">Select PO Team</option>
                    </select>
                </div>

                <!-- PO Team Member -->
                <div class="form-control">
                    <label class="label"><span class="label-text">PO Team Member</span></label>
                    <select class="select select-bordered w-full" id="poTeamMemberSelect" name="po_team_member">
                        <option value="">Select PO Team Member</option>
                    </select>
                </div>

                <!-- PO Number -->
                <div class="form-control">
                    <label class="label"><span class="label-text">PO Number</span></label>
                    <input type="text" class="input input-bordered w-full" id="poNumberInput" name="po_number" placeholder="PO Number">
                </div>

                <!-- Buyer Name (for pr_assignments) -->
                <div class="form-control">
                    <label class="label"><span class="label-text">Buyer Name (Assignment)</span></label>
                    <input type="text" class="input input-bordered w-full" id="buyerNameInput" name="buyer_name" placeholder="Buyer name for assignment">
                </div>

                <!-- Divider: Remarks -->
                <div class="form-control md:col-span-3">
                    <div class="divider">
                        <span class="text-lg font-bold">Remarks</span>
                    </div>
                </div>

                <!-- Buyer Remark -->
                <div class="form-control md:col-span-3">
                    <label class="label"><span class="label-text">Buyer Remark</span></label>
                    <textarea class="textarea textarea-bordered w-full" id="buyerRemarkInput" name="b_remark" placeholder="Buyer remarks..."></textarea>
                </div>

                <!-- PO Team Remark -->
                <div class="form-control md:col-span-3">
                    <label class="label"><span class="label-text">PO Team Remark</span></label>
                    <textarea class="textarea textarea-bordered w-full" id="poTeamRemarkInput" name="po_team_rm" placeholder="PO team remarks..."></textarea>
                </div>

                <!-- PO Team Member Remark -->
                <div class="form-control md:col-span-3">
                    <label class="label"><span class="label-text">PO Team Member Remark</span></label>
                    <textarea class="textarea textarea-bordered w-full" id="poTeamMemberRemarkInput" name="rrm" placeholder="PO team member remarks..."></textarea>
                </div>

                <!-- Buyer Head Remark -->
                <div class="form-control md:col-span-3">
                    <label class="label"><span class="label-text">Buyer Head Remark</span></label>
                    <textarea class="textarea textarea-bordered w-full" id="buyerHeadRemarkInput" name="to_bh_rm" placeholder="Buyer head remarks..."></textarea>
                </div>

                <!-- Action Buttons -->
                <div class="form-control col-span-1 md:col-span-3 mt-4">
                    <div class="flex justify-between items-center">
                        <button type="button" id="deleteBtn" class="btn btn-error">
                            <i class="fas fa-trash"></i> Delete PR
                        </button>
                        <div class="flex gap-2">
                            <button type="button" id="resetBtn" class="btn btn-outline">
                                <i class="fas fa-undo"></i> Reset
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update PR
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="../common/js/create-pr.js"></script>
<script>
let currentPRData = null;

// Fetch Purchase Types
async function fetchPurchaseTypes() {
    const select = document.getElementById("purchInput");
    if (!select) return;

    try {
        const res = await fetch("../fetch/api/fetch-purchtype.php");
        if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
        const json = await res.json();
        const data = json.data || [];

        select.innerHTML = "";
        data.forEach(type => {
            const opt = document.createElement("option");
            opt.value = type.id;
            opt.textContent = type.text;
            select.appendChild(opt);
        });

        if (!data.length) {
            const opt = document.createElement("option");
            opt.textContent = "No purchase types available";
            opt.disabled = true;
            select.appendChild(opt);
        }
    } catch (err) {
        console.error("Purchase Type API error:", err);
    }
}

// Fetch Buyers
async function fetchBuyers() {
    const select = document.getElementById("buyerSelect");
    if (!select) return;

    try {
        const res = await fetch("../fetch/fetch-buyer.php");
        if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
        const json = await res.json();
        const data = json.data || [];

        select.innerHTML = '<option value="">Select Buyer</option>';
        data.forEach(buyer => {
            const opt = document.createElement("option");
            opt.value = buyer.id;
            opt.textContent = buyer.username;
            select.appendChild(opt);
        });
    } catch (err) {
        console.error("Buyer API error:", err);
    }
}

// Fetch PO Team (PO_Team role users)
async function fetchPOTeam() {
    const select = document.getElementById("poTeamSelect");
    if (!select) return;

    try {
        const res = await fetch("../fetch/fetch-po-team-heads.php");
        if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
        const json = await res.json();
        const data = json.data || [];

        select.innerHTML = '<option value="">Select PO Team</option>';
        data.forEach(team => {
            const opt = document.createElement("option");
            opt.value = team.id;
            opt.textContent = team.username;
            select.appendChild(opt);
        });
    } catch (err) {
        console.error("PO Team API error:", err);
    }
}

// Fetch PO Team Members
async function fetchPOTeamMembers() {
    const select = document.getElementById("poTeamMemberSelect");
    if (!select) return;

    try {
        const res = await fetch("../fetch/fetch-po-team.php");
        if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
        const json = await res.json();
        const data = json.data || [];

        select.innerHTML = '<option value="">Select PO Team Member</option>';
        data.forEach(member => {
            const opt = document.createElement("option");
            opt.value = member.id;
            opt.textContent = member.username || member.fullname;
            select.appendChild(opt);
        });
    } catch (err) {
        console.error("PO Team Member API error:", err);
    }
}

// Fetch Statuses
async function fetchStatuses() {
    const select = document.getElementById("statusSelect");
    if (!select) return;

    try {
        const res = await fetch("../api/admin/get-statuses.php");
        if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
        const json = await res.json();
        const data = json.data || [];

        select.innerHTML = '<option value="">Select Status</option>';
        data.forEach(status => {
            const opt = document.createElement("option");
            opt.value = status.id;
            opt.textContent = status.status;
            select.appendChild(opt);
        });
    } catch (err) {
        console.error("Status API error:", err);
    }
}

// Search PR by Reference ID
async function searchPR() {
    const refId = document.getElementById('searchRefId').value.trim();
    const errorDiv = document.getElementById('searchError');
    const editFormCard = document.getElementById('editFormCard');
    
    if (!refId) {
        errorDiv.textContent = 'Please enter a Reference ID';
        errorDiv.classList.remove('hidden');
        return;
    }

    errorDiv.classList.add('hidden');
    const searchBtn = document.getElementById('searchBtn');
    searchBtn.disabled = true;
    searchBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Searching...';

    try {
        const res = await fetch(`../fetch/api/get-pr.php?id=${refId}`);
        const json = await res.json();

        if (json.status !== "success") {
            errorDiv.textContent = json.message || 'PR not found';
            errorDiv.classList.remove('hidden');
            editFormCard.classList.add('hidden');
            return;
        }

        currentPRData = json.data;
        await loadPRData(currentPRData);
        editFormCard.classList.remove('hidden');
        document.getElementById('prRefId').textContent = refId;
        
        // Scroll to form
        editFormCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
        
    } catch (err) {
        console.error(err);
        errorDiv.textContent = 'Failed to fetch PR data';
        errorDiv.classList.remove('hidden');
        editFormCard.classList.add('hidden');
    } finally {
        searchBtn.disabled = false;
        searchBtn.innerHTML = '<i class="fas fa-search"></i> Search';
    }
}

// Load PR data into form
async function loadPRData(data) {
    await Promise.all([
        fetchPurchaseTypes(),
        fetchBuyers(),
        fetchPOTeam(),
        fetchPOTeamMembers(),
        fetchStatuses()
    ]);
    
    // Get CSRF token
    try {
        const response = await fetch('../auth/get-csrf-token.php');
        const csrfData = await response.json();
        if (csrfData.status === 'success') {
            document.getElementById('csrf_token').value = csrfData.data.csrf_token;
        }
    } catch (error) {
        console.error('Failed to get CSRF token:', error);
    }

    // Fill basic form fields
    document.getElementById("prId").value = data.id || "";
    document.getElementById("supplierInput").value = data.supplier || "";
    document.getElementById("supplierId").value = data.supplier_id || "";
    document.getElementById("agentInput").value = data.agent || "";
    document.getElementById("cityInput").value = data.city || "";
    document.getElementById("categoryInput").value = data.category || "";
    document.getElementById("categoryId").value = data.category_id || "";
    document.getElementById("buyerHeadInput").value = data.bhead_name || "";
    document.getElementById("buyerId").value = data.b_head || "";
    document.getElementById("qtyInput").value = data.qty || 1;
    document.getElementById("uomInput").value = data.uom || "Pcs";
    document.getElementById("remarkInput").value = data.remark || "";
    document.getElementById("purchInput").value = data.purch_id || "";

    // Handle NEW SUPPLIER - check if supplier_id is 99999 or if new_supplier exists
    const supplierId = data.supplier_id;
    const isNewSupplier = supplierId === "99999" || supplierId === 99999 || data.supplier === "NEW SUPPLIER" || data.new_supplier;
    const newSupplierContainer = document.getElementById("newSupplierContainer");
    const newSupplierInput = document.getElementById("newSupplierInput");
    
    if (isNewSupplier && newSupplierContainer) {
        newSupplierContainer.classList.remove("hidden");
        newSupplierContainer.classList.add("form-control");
        if (newSupplierInput) {
            newSupplierInput.value = data.supplier || "";
        }
        const agentInput = document.getElementById("agentInput");
        const cityInput = document.getElementById("cityInput");
        if (agentInput) agentInput.readOnly = false;
        if (cityInput) cityInput.readOnly = false;
    } else if (newSupplierContainer) {
        newSupplierContainer.classList.add("hidden");
        newSupplierContainer.classList.remove("form-control");
        const agentInput = document.getElementById("agentInput");
        const cityInput = document.getElementById("cityInput");
        if (agentInput) agentInput.readOnly = true;
        if (cityInput) cityInput.readOnly = true;
    }

    // Fill workflow fields (only if they have values)
    if (data.po_status) {
        document.getElementById("statusSelect").value = data.po_status;
    }
    if (data.buyer) {
        document.getElementById("buyerSelect").value = data.buyer;
    }
    if (data.po_team) {
        document.getElementById("poTeamSelect").value = data.po_team;
    }
    if (data.po_team_member) {
        document.getElementById("poTeamMemberSelect").value = data.po_team_member;
    }
    if (data.po_number) {
        document.getElementById("poNumberInput").value = data.po_number;
    }
    if (data.po_team_member_buyername) {
        document.getElementById("buyerNameInput").value = data.po_team_member_buyername;
    }

    // Fill remarks (only if they have values)
    if (data.b_remark) {
        document.getElementById("buyerRemarkInput").value = data.b_remark;
    }
    if (data.po_team_rm) {
        document.getElementById("poTeamRemarkInput").value = data.po_team_rm;
    }
    if (data.rrm) {
        document.getElementById("poTeamMemberRemarkInput").value = data.rrm;
    }
    if (data.to_bh_rm) {
        document.getElementById("buyerHeadRemarkInput").value = data.to_bh_rm;
    }
}

// Form submission
document.getElementById('editPRForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    if (!formData.get('supplierId') || !formData.get('categoryId')) {
        showToast('Please fill all required fields', 'warning');
        return;
    }

    const confirmResult = await showConfirm(
        'Update PR?',
        'Are you sure you want to update this Purchase Request?',
        'Yes, update it!',
        'Cancel'
    );
    
    if (confirmResult.isConfirmed) {
        try {
            const res = await fetch('../api/update-pr.php', { method: 'POST', body: formData });
            const json = await res.json();

            if (json.status === 'success') {
                showToast('PR updated successfully (ID: ' + json.data.po_id + ')', 'success', 2000);
                // Reload the PR data to show updated values
                setTimeout(() => {
                    searchPR();
                }, 1000);
            } else {
                showToast(json.message || 'Failed to update PR', 'error');
            }
        } catch (err) {
            console.error(err);
            showToast('Failed to update PR', 'error');
        }
    }
});

// Reset form
document.getElementById('resetBtn').addEventListener('click', function() {
    if (currentPRData) {
        loadPRData(currentPRData);
    }
});

// Search button click
document.getElementById('searchBtn').addEventListener('click', searchPR);

// Enter key in search input
document.getElementById('searchRefId').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        searchPR();
    }
});

// Delete PR
document.getElementById('deleteBtn').addEventListener('click', async function() {
    const prId = document.getElementById('prId').value;
    if (!prId) {
        showToast('Please search for a PR first', 'warning');
        return;
    }

    const confirmResult = await showConfirm(
        'Are you sure?',
        'You are about to delete PR #' + prId + '. This action cannot be undone!',
        'Yes, delete it!',
        'Cancel'
    );
    
    if (confirmResult.isConfirmed) {
        try {
            const formData = new FormData();
            formData.append('id', prId);
            
            const res = await fetch('../api/delete-pr.php', { 
                method: 'POST', 
                body: formData 
            });
            const json = await res.json();

            if (json.status === 'success') {
                showToast('PR #' + prId + ' has been deleted successfully', 'success', 2000);
                
                // Clear form and hide it
                document.getElementById('editFormCard').classList.add('hidden');
                document.getElementById('searchRefId').value = '';
                document.getElementById('editPRForm').reset();
                currentPRData = null;
            } else {
                showToast(json.message || 'Failed to delete PR', 'error');
            }
        } catch (err) {
            console.error(err);
            showToast('Failed to delete PR', 'error');
        }
    }
});

// Table pagination variables
let currentTablePage = 1;
let tableSearchQuery = '';
const itemsPerPage = 10;

// Load PRs table
async function loadPRsTable(page = 1, search = '') {
    currentTablePage = page;
    tableSearchQuery = search || document.getElementById('tableSearchInput').value.trim();
    
    const tbody = document.getElementById('prTableBody');
    const emptyState = document.getElementById('emptyState');
    const paginationContainer = document.getElementById('paginationContainer');
    
    // Show loading
    tbody.innerHTML = `
        <tr>
            <td colspan="10" class="text-center">
                <span class="loading loading-spinner loading-lg"></span>
                <p class="mt-2">Loading PRs...</p>
            </td>
        </tr>
    `;
    
    try {
        const url = `../fetch/api/list-prs.php?page=${page}&per_page=${itemsPerPage}${tableSearchQuery ? '&search=' + encodeURIComponent(tableSearchQuery) : ''}`;
        const res = await fetch(url);
        const json = await res.json();
        
        if (json.status !== 'success') {
            throw new Error(json.message || 'Failed to load PRs');
        }
        
        const data = json.data.data || [];
        const pagination = json.data.pagination || {};
        
        if (data.length === 0) {
            tbody.innerHTML = '';
            emptyState.classList.remove('hidden');
            paginationContainer.innerHTML = '';
            return;
        }
        
        emptyState.classList.add('hidden');
        
        // Render table rows
        tbody.innerHTML = data.map(pr => `
            <tr class="hover cursor-pointer" onclick="selectPRFromTable(${pr.id})">
                <td><strong>${pr.id}</strong></td>
                <td>${pr.supplier}</td>
                <td>${pr.category}</td>
                <td>
                    <span class="badge badge-primary">${pr.status}</span>
                </td>
                <td>${pr.buyer_head}</td>
                <td>${pr.buyer}</td>
                <td>${pr.qty}</td>
                <td>${pr.uom}</td>
                <td>${new Date(pr.created_at).toLocaleDateString()}</td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="event.stopPropagation(); selectPRFromTable(${pr.id})">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                </td>
            </tr>
        `).join('');
        
        // Render pagination
        renderTablePagination(pagination);
        
    } catch (err) {
        console.error('Error loading PRs table:', err);
        tbody.innerHTML = `
            <tr>
                <td colspan="10" class="text-center text-error">
                    <i class="fas fa-exclamation-circle"></i> Failed to load PRs
                </td>
            </tr>
        `;
        paginationContainer.innerHTML = '';
    }
}

// Render pagination controls
function renderTablePagination(pagination) {
    const container = document.getElementById('paginationContainer');
    
    if (!pagination || pagination.total_pages <= 1) {
        container.innerHTML = '';
        return;
    }
    
    const current = pagination.current_page || 1;
    const total = pagination.total_pages || 1;
    
    let html = '<div class="join">';
    
    // Previous button
    if (pagination.has_prev) {
        html += `<button class="join-item btn btn-sm" onclick="loadPRsTable(${current - 1})">«</button>`;
    } else {
        html += `<button class="join-item btn btn-sm btn-disabled">«</button>`;
    }
    
    // Page numbers
    const maxPages = 5;
    let startPage = Math.max(1, current - Math.floor(maxPages / 2));
    let endPage = Math.min(total, startPage + maxPages - 1);
    
    if (endPage - startPage < maxPages - 1) {
        startPage = Math.max(1, endPage - maxPages + 1);
    }
    
    if (startPage > 1) {
        html += `<button class="join-item btn btn-sm" onclick="loadPRsTable(1)">1</button>`;
        if (startPage > 2) {
            html += `<button class="join-item btn btn-sm btn-disabled">...</button>`;
        }
    }
    
    for (let i = startPage; i <= endPage; i++) {
        if (i === current) {
            html += `<button class="join-item btn btn-sm btn-active">${i}</button>`;
        } else {
            html += `<button class="join-item btn btn-sm" onclick="loadPRsTable(${i})">${i}</button>`;
        }
    }
    
    if (endPage < total) {
        if (endPage < total - 1) {
            html += `<button class="join-item btn btn-sm btn-disabled">...</button>`;
        }
        html += `<button class="join-item btn btn-sm" onclick="loadPRsTable(${total})">${total}</button>`;
    }
    
    // Next button
    if (pagination.has_next) {
        html += `<button class="join-item btn btn-sm" onclick="loadPRsTable(${current + 1})">»</button>`;
    } else {
        html += `<button class="join-item btn btn-sm btn-disabled">»</button>`;
    }
    
    html += '</div>';
    html += `<div class="ml-4 text-sm text-base-content opacity-70">Page ${current} of ${total} (${pagination.total_items} total)</div>`;
    
    container.innerHTML = html;
}

// Select PR from table
async function selectPRFromTable(prId) {
    // Set search input and trigger search
    document.getElementById('searchRefId').value = prId;
    await searchPR();
    
    // Scroll to edit form
    const editFormCard = document.getElementById('editFormCard');
    if (editFormCard) {
        editFormCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

// Table search button
document.getElementById('tableSearchBtn').addEventListener('click', function() {
    loadPRsTable(1);
});

// Table reset button
document.getElementById('tableResetBtn').addEventListener('click', function() {
    document.getElementById('tableSearchInput').value = '';
    loadPRsTable(1, '');
});

// Table search input enter key
document.getElementById('tableSearchInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        loadPRsTable(1);
    }
});

// Initialize
$(document).ready(function() {
    fetchPurchaseTypes();
    fetchBuyers();
    fetchPOTeam();
    fetchPOTeamMembers();
    fetchStatuses();
    loadPRsTable(); // Load PRs table on page load
});
</script>

<?php include '../common/layout-footer.php'; ?>

