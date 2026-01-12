<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION["user_id"])) {
    header("Location: ../index.php");
    exit;
}

// Check if user has permission (admin or PO_Team)
$allowed_roles = ['PO_Head', 'PO_Team_Member', 'admin', 'super_admin', 'master'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    header("Location: ../index.php");
    exit;
}

$role = $_SESSION['role'] ?? '';
$username = $_SESSION['username'] ?? 'User';
$userid = $_SESSION['user_id'] ?? 0;
$currentPage = 'supplier-requests.php';
?>
<?php include '../common/layout.php'; ?>
    <div class="flex justify-between items-center mb-4 sm:mb-6">
        <h1 class="text-2xl sm:text-3xl font-bold">
            <i class="fas fa-code me-2"></i>Supplier Requests Management
        </h1>
    </div>
    
    <!-- Summary Counts -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="card bg-base-100 shadow-xl border-l-4 border-warning">
            <div class="card-body text-center">
                <div class="text-warning font-semibold text-lg">Pending</div>
                <div class="text-4xl font-bold" id="pendingCount">0</div>
            </div>
        </div>
        
        <div class="card bg-base-100 shadow-xl border-l-4 border-success">
            <div class="card-body text-center">
                <div class="text-success font-semibold text-lg">Created</div>
                <div class="text-4xl font-bold" id="createdCount">0</div>
            </div>
        </div>
        
        <div class="card bg-base-100 shadow-xl border-l-4 border-primary">
            <div class="card-body text-center">
                <div class="text-primary font-semibold text-lg">Total</div>
                <div class="text-4xl font-bold" id="totalCount">0</div>
            </div>
        </div>
    </div>

    <!-- Filters and Table Card -->
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <!-- Filters -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-semibold">Filter by Status</span>
                    </label>
                    <select id="statusFilter" class="select select-bordered w-full">
                        <option value="">All</option>
                        <option value="pending">Pending</option>
                        <option value="created">Created</option>
                    </select>
                </div>
                
                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-semibold">Search</span>
                    </label>
                    <input type="text" id="searchInput" class="input input-bordered w-full" placeholder="Search supplier name, GST, PAN...">
                </div>
                
                <div class="form-control">
                    <label class="label">
                        <span class="label-text opacity-0">Clear</span>
                    </label>
                    <button class="btn btn-secondary w-full" onclick="clearFilters()">
                        <i class="fas fa-times me-1"></i> Clear Filters
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Supplier Name</th>
                            <th>GST Number</th>
                            <th>PAN Number</th>
                            <th>Mobile</th>
                            <th>Email</th>
                            <th>Agent</th>
                            <th>City</th>
                            <th>Status</th>
                            <th>Supplier Code</th>
                            <th>Created By</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="supplierTableBody">
                        <tr>
                            <td colspan="13" class="text-center">
                                <span class="loading loading-spinner loading-lg"></span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div id="loader" class="text-center py-4" style="display: none;">
                <span class="loading loading-spinner loading-lg"></span>
            </div>
            
            <div id="no-data-message" class="text-center py-4 text-base-content opacity-60" style="display: none;">
                <p>No more suppliers to load</p>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <dialog id="editSupplierCodeModal" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg mb-4">Update Supplier Code</h3>
            <form id="editSupplierCodeForm">
                <input type="hidden" id="editSupplierId" name="supplier_id">
                <div class="form-control mb-4">
                    <label class="label">
                        <span class="label-text font-semibold">Supplier Name</span>
                    </label>
                    <input type="text" class="input input-bordered" id="editSupplierName" readonly>
                </div>
                <div class="form-control mb-4">
                    <label class="label">
                        <span class="label-text font-semibold">Supplier Code <span class="text-error">*</span></span>
                    </label>
                    <input type="text" class="input input-bordered" id="editSupplierCode" name="supplier_code" required 
                           placeholder="Enter supplier code" maxlength="50">
                    <label class="label">
                        <span class="label-text-alt">Enter a unique supplier code for this supplier</span>
                    </label>
                </div>
                <div class="modal-action">
                    <button type="button" class="btn" onclick="document.getElementById('editSupplierCodeModal').close()">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveSupplierCode()">
                        <i class="fas fa-save me-1"></i> Save
                    </button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

<script>
    // State management
    const state = {
        offset: 0,
        limit: 20,
        loading: false,
        noMoreData: false,
        currentFilter: '',
        currentSearch: ''
    };

    // Utility functions
    function debounce(func, delay) {
        let timer;
        return function() {
            clearTimeout(timer);
            timer = setTimeout(func, delay);
        };
    }

    function loadSupplierCounts() {
        fetch('../fetch/fetch-supplier-requests-counts.php')
            .then(res => res.json())
            .then(data => {
                if (data.error) return;

                document.getElementById('pendingCount').innerText = data.pending || 0;
                document.getElementById('createdCount').innerText = data.created || 0;
                document.getElementById('totalCount').innerText = data.total || 0;
            })
            .catch(err => console.error('Count error:', err));
    }

    function isScrolledToBottom() {
        const scrollTop = window.scrollY || document.documentElement.scrollTop;
        const bodyHeight = document.body.scrollHeight || document.documentElement.scrollHeight;
        const viewportHeight = window.innerHeight || document.documentElement.clientHeight;
        return scrollTop + viewportHeight >= bodyHeight - 10;
    }

    // Load data on page load
    document.addEventListener('DOMContentLoaded', function() {
        loadSupplierData();
        
        // Infinite scroll
        window.addEventListener('scroll', debounce(function() {
            if (isScrolledToBottom() && !state.loading && !state.noMoreData) {
                loadSupplierData();
            }
        }, 200));
        
        // Filter event listeners
        document.getElementById('statusFilter').addEventListener('change', function() {
            state.currentFilter = this.value;
            resetAndLoadData();
        });
        
        document.getElementById('searchInput').addEventListener('input', debounce(function() {
            state.currentSearch = this.value;
            resetAndLoadData();
        }, 300));
    });

    function resetAndLoadData() {
        state.offset = 0;
        state.noMoreData = false;
        document.getElementById('supplierTableBody').innerHTML = '';
        document.getElementById('no-data-message').style.display = 'none';
        loadSupplierData();
    }

    function loadSupplierData() {
        loadSupplierCounts(); 
        if (state.loading || state.noMoreData) return;

        state.loading = true;
        document.getElementById('loader').style.display = 'block';
        document.getElementById('no-data-message').style.display = 'none';

        const params = new URLSearchParams();
        if (state.currentFilter) params.append('status', state.currentFilter);
        if (state.currentSearch) params.append('search', state.currentSearch);
        params.append('offset', state.offset);
        params.append('limit', state.limit);

        fetch(`../fetch/fetch-supplier-requests.php?${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                const tbody = document.getElementById('supplierTableBody');
                
                if (data.error) {
                    if (state.offset === 0) {
                        tbody.innerHTML = `<tr><td colspan="13" class="text-center text-error">${data.error}</td></tr>`;
                    }
                    return;
                }

                if (data.length === 0) {
                    state.noMoreData = true;
                    if (state.offset === 0) {
                        tbody.innerHTML = `<tr><td colspan="13" class="text-center">No suppliers found</td></tr>`;
                    } else {
                        document.getElementById('no-data-message').style.display = 'block';
                    }
                    return;
                }

                // Append new rows instead of replacing
                const newRows = data.map(supplier => `
                    <tr>
                        <td>${supplier.id}</td>
                        <td>${escapeHtml(supplier.supplier || 'N/A')}</td>
                        <td>${escapeHtml(supplier.gst_no || 'N/A')}</td>
                        <td>${escapeHtml(supplier.pan_no || 'N/A')}</td>
                        <td>${escapeHtml(supplier.mobile || 'N/A')}</td>
                        <td>${escapeHtml(supplier.email || 'N/A')}</td>
                        <td>${escapeHtml(supplier.agent || 'N/A')}</td>
                        <td>${escapeHtml(supplier.city || 'N/A')}</td>
                        <td>
                            <span class="badge ${supplier.supplier_code ? 'badge-success' : 'badge-warning'}">
                                ${supplier.supplier_code ? 'Created' : 'Pending'}
                            </span>
                        </td>
                        <td>${escapeHtml(supplier.supplier_code || '-')}</td>
                        <td>${escapeHtml(supplier.created_by_name || 'N/A')}</td>
                        <td>${formatDate(supplier.created_at)}</td>
                        <td>
                            <button class="btn btn-ghost btn-sm text-primary hover:bg-transparent" onclick="editSupplierCode(${supplier.id}, '${escapeHtml(supplier.supplier || '')}', '${escapeHtml(supplier.supplier_code || '')}')" title="${supplier.supplier_code ? 'Edit' : 'Add'}">
                                <i class="fas fa-edit"></i>
                            </button>
                        </td>
                    </tr>
                `).join('');

                if (state.offset === 0) {
                    tbody.innerHTML = newRows;
                } else {
                    tbody.insertAdjacentHTML('beforeend', newRows);
                }

                state.offset += state.limit;
                
                // If we got less than limit, no more data
                if (data.length < state.limit) {
                    state.noMoreData = true;
                    document.getElementById('no-data-message').style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                if (state.offset === 0) {
                    document.getElementById('supplierTableBody').innerHTML = 
                        `<tr><td colspan="13" class="text-center text-error">Error loading data</td></tr>`;
                }
            })
            .finally(() => {
                state.loading = false;
                document.getElementById('loader').style.display = 'none';
            });
    }

    function editSupplierCode(id, name, code) {
        document.getElementById('editSupplierId').value = id;
        document.getElementById('editSupplierName').value = name;
        document.getElementById('editSupplierCode').value = code;
        document.getElementById('editSupplierCodeModal').showModal();
    }

    function saveSupplierCode() {
        const form = document.getElementById('editSupplierCodeForm');
        const formData = new FormData(form);
        const supplierCode = formData.get('supplier_code').trim();

        if (!supplierCode) {
            showToast('Please enter a supplier code', 'error');
            return;
        }

        fetch('../update/update-supplier-code.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                document.getElementById('editSupplierCodeModal').close();
                resetAndLoadData();
                showToast('Supplier code updated successfully!', 'success');
            } else {
                showToast('Error: ' + (data.message || 'Failed to update supplier code'), 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error updating supplier code', 'error');
        });
    }

    function clearFilters() {
        document.getElementById('statusFilter').value = '';
        document.getElementById('searchInput').value = '';
        state.currentFilter = '';
        state.currentSearch = '';
        resetAndLoadData();
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
    }
</script>

<?php include '../common/layout-footer.php'; ?>

