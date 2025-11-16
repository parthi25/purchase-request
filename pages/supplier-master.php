<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION["user_id"])) {
    header("Location: ../index.php");
    exit;
}

// Only superadmin and master can access
if (!in_array($_SESSION['role'], ['super_admin', 'master'])) {
    header("Location: ../index.php");
    exit;
}

$role = $_SESSION['role'] ?? '';
$username = $_SESSION['username'] ?? 'User';
$userid = $_SESSION['user_id'] ?? 0;
$currentPage = 'supplier-master.php';
?>
<?php include '../common/layout.php'; ?>

<div class="container mx-auto p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Supplier Master</h1>
    </div>
    
    <!-- Form Card -->
    <div class="card bg-base-100 shadow-xl mb-6">
        <div class="card-body">
            <h2 class="card-title mb-4">
                <i class="fas fa-building"></i>
                <span id="formTitle">Add New Supplier</span>
            </h2>
            <form id="supplierForm" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <input type="hidden" name="id" id="supplierId">
                <input type="hidden" name="supplier_id" id="supplier_id">
                
                <div class="form-control md:col-span-2 lg:col-span-3">
                    <label class="label">
                        <span class="label-text font-semibold">Supplier Name *</span>
                    </label>
                    <input type="text" name="supplier_name" id="supplier_name" class="input input-bordered w-full" required placeholder="Enter supplier name">
                </div>
                
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">Agent</span>
                    </label>
                    <input type="text" name="agent" id="agent" class="input input-bordered w-full" placeholder="Agent name">
                </div>
                
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">City</span>
                    </label>
                    <input type="text" name="city" id="city" class="input input-bordered w-full" placeholder="City">
                </div>
                
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">Postal Code</span>
                    </label>
                    <input type="text" name="postal_code" id="postal_code" class="input input-bordered w-full" placeholder="Postal code">
                </div>
                
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">Region</span>
                    </label>
                    <input type="text" name="region" id="region" class="input input-bordered w-full" placeholder="Region">
                </div>
                
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">Street</span>
                    </label>
                    <input type="text" name="street" id="street" class="input input-bordered w-full" placeholder="Street address">
                </div>
                
                <div class="form-control md:col-span-2">
                    <label class="label">
                        <span class="label-text">Address</span>
                    </label>
                    <textarea name="address" id="address" class="textarea textarea-bordered w-full" placeholder="Full address"></textarea>
                </div>
                
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">Title</span>
                    </label>
                    <input type="text" name="title" id="title" class="input input-bordered w-full" placeholder="Title">
                </div>
                
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">Account Group</span>
                    </label>
                    <input type="text" name="account_group" id="account_group" class="input input-bordered w-full" placeholder="Account group">
                </div>
                
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">Tax Number 3</span>
                    </label>
                    <input type="text" name="tax_number_3" id="tax_number_3" class="input input-bordered w-full" placeholder="Tax number">
                </div>
                
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">PAN</span>
                    </label>
                    <input type="text" name="permanent_account_number" id="permanent_account_number" class="input input-bordered w-full" placeholder="Permanent account number">
                </div>
                
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">Search Term</span>
                    </label>
                    <input type="text" name="search_term" id="search_term" class="input input-bordered w-full" placeholder="Search term">
                </div>
                
                <div class="form-control md:col-span-2 lg:col-span-3 flex flex-row justify-between items-center mt-4">
                    <button type="button" id="resetBtn" class="btn btn-outline">
                        <i class="fas fa-undo"></i> Reset
                    </button>
                    <div class="flex gap-2">
                        <button type="button" id="deleteBtn" class="btn btn-error">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="fas fa-save"></i> Save Supplier
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Suppliers Table Card -->
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <div class="flex justify-between items-center mb-4">
                <h2 class="card-title">
                    <i class="fas fa-list"></i> Supplier List
                </h2>
                <div class="flex gap-2">
                    <div class="form-control">
                        <div class="join">
                            <input type="text" id="searchInput" class="input input-bordered join-item" placeholder="Search suppliers...">
                            <button class="btn btn-square join-item">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="dropdown dropdown-end">
                        <label tabindex="0" class="btn btn-success">
                            <i class="fas fa-file-export"></i> Export
                        </label>
                        <ul tabindex="0" class="dropdown-content menu p-2 shadow bg-base-100 rounded-box w-52">
                            <li><a id="exportExcel"><i class="fas fa-file-excel text-success"></i> Export as Excel</a></li>
                            <li><a id="exportCSV"><i class="fas fa-file-csv text-primary"></i> Export as CSV</a></li>
                        </ul>
                    </div>
                    <button id="refreshBtn" class="btn btn-outline">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full" id="supplierTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Supplier ID</th>
                            <th>Supplier Name</th>
                            <th>Agent</th>
                            <th>City</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
            <div id="emptyState" class="text-center py-8 hidden">
                <i class="fas fa-building text-6xl text-base-content opacity-20 mb-4"></i>
                <h5 class="text-xl font-semibold">No suppliers found</h5>
                <p>Add a new supplier to get started</p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
<script>
function loadSuppliers() {
    $.get("../api/admin/suppliers.php?action=read_all", function(data) {
        if (data.status === 'success') {
            const suppliers = data.data || [];
            if (suppliers.length === 0) {
                $('#supplierTable').addClass('hidden');
                $('#emptyState').removeClass('hidden');
                return;
            }
            
            $('#supplierTable').removeClass('hidden');
            $('#emptyState').addClass('hidden');
            
            let rows = '';
            suppliers.forEach(supplier => {
                rows += `
                    <tr data-id="${supplier.id}">
                        <td>${supplier.id}</td>
                        <td>${supplier.supplier_id || '-'}</td>
                        <td>${supplier.supplier || '-'}</td>
                        <td>${supplier.agent || '-'}</td>
                        <td>${supplier.city || '-'}</td>
                        <td>
                            <div class="flex gap-2">
                                <button class="btn btn-sm btn-warning" onclick='editSupplier(${JSON.stringify(supplier)})'>
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="btn btn-sm btn-error" onclick="deleteSupplier(${supplier.id})">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </td>
                    </tr>`;
            });
            $('#supplierTable tbody').html(rows);
        }
    }, 'json').fail(function() {
        Swal.fire('Error', 'Failed to load suppliers', 'error');
    });
}

function editSupplier(supplier) {
    $('html, body').animate({
        scrollTop: $("#supplierForm").offset().top - 100
    }, 500);
    
    $('#supplierId').val(supplier.id);
    $('#supplier_id').val(supplier.supplier_id || '');
    $('#supplier_name').val(supplier.supplier || '');
    $('#agent').val(supplier.agent || '');
    $('#city').val(supplier.city || '');
    $('#postal_code').val(supplier.postal_code || '');
    $('#region').val(supplier.region || '');
    $('#street').val(supplier.street || '');
    $('#address').val(supplier.address || '');
    $('#title').val(supplier.title || '');
    $('#account_group').val(supplier.account_group || '');
    $('#tax_number_3').val(supplier.tax_number_3 || '');
    $('#permanent_account_number').val(supplier.permanent_account_number || '');
    $('#search_term').val(supplier.search_term || '');
    $('#submitBtn').html('<i class="fas fa-save"></i> Update Supplier');
    $('#formTitle').text('Edit Supplier');
    
    $('tr').removeClass('bg-primary bg-opacity-10');
    $(`tr[data-id="${supplier.id}"]`).addClass('bg-primary bg-opacity-10');
}

function resetForm() {
    $('#supplierForm')[0].reset();
    $('#supplierId').val('');
    $('#supplier_id').val('');
    $('#submitBtn').html('<i class="fas fa-save"></i> Save Supplier');
    $('#formTitle').text('Add New Supplier');
    $('tr').removeClass('bg-primary bg-opacity-10');
}

function deleteSupplier(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: 'You will not be able to recover this supplier!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#ef4444',
    }).then(result => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);
            
            $.ajax({
                url: '../api/admin/suppliers.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: response.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                        setTimeout(() => {
                            loadSuppliers();
                        }, 500);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: response.message || 'Failed to delete supplier.',
                        });
                    }
                }
            });
        }
    });
}

$('#supplierForm').submit(function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const action = $('#supplierId').val() ? 'update' : 'create';
    formData.append('action', action);
    
    // Generate supplier_id if not exists
    if (!formData.get('supplier_id')) {
        formData.set('supplier_id', Math.floor(100000 + Math.random() * 900000));
    }

    Swal.fire({
        title: action === 'add' ? 'Add Supplier?' : 'Update Supplier?',
        text: action === 'add' ? 'New supplier will be added to the system.' : 'Supplier information will be updated.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, proceed',
        cancelButtonText: 'Cancel',
    }).then(result => {
        if (result.isConfirmed) {
            $.ajax({
                url: '../api/admin/suppliers.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                        resetForm();
                        loadSuppliers();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: response.message || 'Failed to process your request.',
                        });
                    }
                }
            });
        }
    });
});

$('#resetBtn').click(function() {
    resetForm();
});

$('#refreshBtn').click(function() {
    $(this).find('i').addClass('fa-spin');
    loadSuppliers();
    setTimeout(() => {
        $(this).find('i').removeClass('fa-spin');
    }, 700);
});

$('#searchInput').on('keyup', function() {
    const value = $(this).val().toLowerCase();
    $("#supplierTable tbody tr").each(function() {
        const rowText = $(this).text().toLowerCase();
        if (rowText.indexOf(value) > -1) {
            $(this).removeClass('hidden');
        } else {
            $(this).addClass('hidden');
        }
    });
    
    const visibleRows = $('#supplierTable tbody tr').not('.hidden').length;
    if (visibleRows === 0) {
        $('#emptyState').removeClass('hidden');
        $('#emptyState h5').text('No matching suppliers found');
    } else {
        $('#emptyState').addClass('hidden');
    }
});

function exportToExcel() {
    const date = new Date();
    const dateStr = date.toISOString().split('T')[0];
    const ws = XLSX.utils.table_to_sheet(document.getElementById('supplierTable'));
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, "Suppliers");
    XLSX.writeFile(wb, `Supplier_List_${dateStr}.xlsx`);
    Swal.fire({
        icon: 'success',
        title: 'Export Successful',
        text: 'Supplier list has been exported to Excel',
        timer: 1500,
        showConfirmButton: false
    });
}

function exportToCSV() {
    const table = document.getElementById('supplierTable');
    const rows = Array.from(table.querySelectorAll('tr'));
    const headers = Array.from(rows.shift().querySelectorAll('th'))
        .map(header => header.textContent.trim());
    const csvData = rows.map(row => {
        return Array.from(row.querySelectorAll('td'))
            .map(cell => {
                let text = cell.textContent.trim();
                if (text.includes(',')) {
                    text = `"${text.replace(/"/g, '""')}"`;
                }
                return text;
            })
            .join(',');
    });
    csvData.unshift(headers.join(','));
    const csvContent = csvData.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const date = new Date();
    const dateStr = date.toISOString().split('T')[0];
    saveAs(blob, `Supplier_List_${dateStr}.csv`);
    Swal.fire({
        icon: 'success',
        title: 'Export Successful',
        text: 'Supplier list has been exported to CSV',
        timer: 1500,
        showConfirmButton: false
    });
}

$(document).ready(function() {
    loadSuppliers();
    
    $('#exportExcel').click(function(e) {
        e.preventDefault();
        exportToExcel();
    });
    
    $('#exportCSV').click(function(e) {
        e.preventDefault();
        exportToCSV();
    });
});
</script>

<?php include '../common/layout-footer.php'; ?>

