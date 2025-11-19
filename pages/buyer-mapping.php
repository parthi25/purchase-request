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
$currentPage = 'buyer-mapping.php';
?>
<?php include '../common/layout.php'; ?>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Buyer Mapping</h1>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Form Card -->
        <div class="lg:col-span-1">
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title mb-4">
                        <i class="fas fa-users"></i>
                        <span id="formTitle">Map Buyer</span>
                    </h2>
                    <form id="buyerMappingForm">
                        <input type="hidden" name="id" id="mappingId">
                        
                        <div class="form-control mb-4">
                            <label class="label">
                                <span class="label-text">Buyer Head</span>
                            </label>
                            <div class="join w-full">
                                <span class="join-item btn btn-disabled bg-base-200"><i class="fas fa-user-tie"></i></span>
                                <select name="b_head" id="b_head" class="select select-bordered join-item flex-1" required>
                                    <option value="">Select Buyer Head</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-control mb-4">
                            <label class="label">
                                <span class="label-text">Buyer</span>
                            </label>
                            <div class="join w-full">
                                <span class="join-item btn btn-disabled bg-base-200"><i class="fas fa-user"></i></span>
                                <select name="buyer" id="buyer" class="select select-bordered join-item flex-1" required>
                                    <option value="">Select Buyer</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="flex justify-between gap-2 mt-4">
                            <button type="button" id="resetBtn" class="btn btn-outline">
                                <i class="fas fa-undo"></i> Reset
                            </button>
                            <div class="flex gap-2">
                                <button type="button" id="deleteBtn" class="btn btn-error">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Mappings List -->
        <div class="lg:col-span-2">
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="card-title">
                            <i class="fas fa-list-check"></i> Buyer Mappings
                        </h2>
                        <div class="flex gap-2">
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
                    
                    <div class="form-control mb-4">
                        <div class="join w-full">
                            <input type="text" id="searchInput" class="input input-bordered join-item flex-1" placeholder="Search mappings...">
                            <button class="btn btn-square join-item">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div id="mappingList" class="space-y-2"></div>
                    
                    <div id="emptyState" class="text-center py-8 hidden">
                        <i class="fas fa-users text-6xl text-base-content opacity-20 mb-4"></i>
                        <h5 class="text-xl font-semibold">No mappings found</h5>
                        <p>Create a new mapping to get started</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Hidden table for exports -->
    <table id="exportTable" class="hidden">
        <thead>
            <tr>
                <th>ID</th>
                <th>Buyer Head</th>
                <th>Buyer</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

<script src="../assets/js/xlsx.full.min.js"></script>
<script src="../assets/js/FileSaver.min.js"></script>
<script>
$("#buyerMappingForm").submit(function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    Swal.fire({
        title: 'Confirm Mapping',
        text: 'Create/Update this buyer mapping?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, save it!',
        cancelButtonText: 'Cancel',
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: "../api/admin/buyer-mapping.php",
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                        resetForm();
                        loadMappings();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message || 'An error occurred',
                        });
                    }
                },
                error: function(err) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred while processing your request.',
                    });
                }
            });
        }
    });
});

$("#deleteBtn").click(function() {
    deleteMapping();
});

function deleteMapping() {
    const id = $("#mappingId").val();
    if (!id) {
        return Swal.fire({
            icon: 'warning',
            title: 'No Selection',
            text: 'Please select a mapping to delete first.',
        });
    }

    Swal.fire({
        title: 'Delete Mapping?',
        text: 'This will permanently delete this buyer mapping.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#ef4444',
    }).then((result) => {
        if (result.isConfirmed) {
            $.post("../api/admin/buyer-mapping.php", { delete_id: id }, function(response) {
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    resetForm();
                    loadMappings();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'Failed to delete mapping',
                    });
                }
            }, 'json');
        }
    });
}

function resetForm() {
    $("#buyerMappingForm")[0].reset();
    $("#mappingId").val('');
    $("#formTitle").text('Map Buyer');
    $(".mapping-item").removeClass('bg-primary bg-opacity-10');
}

$("#resetBtn").click(function() {
    resetForm();
});

function loadBuyerHeads() {
    $.getJSON("../api/admin/get-users.php?role=B_Head", function(data) {
        if (data.status === 'success') {
            const select = $("#b_head");
            select.empty();
            select.append($("<option>", {value: "", text: "Select Buyer Head"}));
            
            data.data.forEach(user => {
                select.append($("<option>", {
                    value: user.id,
                    text: user.fullname
                }));
            });
        }
    });
}

function loadBuyers() {
    $.getJSON("../api/admin/get-users.php?role=buyer", function(data) {
        if (data.status === 'success') {
            const select = $("#buyer");
            select.empty();
            select.append($("<option>", {value: "", text: "Select Buyer"}));
            
            data.data.forEach(user => {
                select.append($("<option>", {
                    value: user.id,
                    text: user.fullname
                }));
            });
        }
    });
}

function loadMappings() {
    $.get("../api/admin/buyer-mapping.php", function(response) {
        let data;
        try {
            data = typeof response === 'string' ? JSON.parse(response) : response;
        } catch (e) {
            console.error("Invalid JSON:", e, response);
            return Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Server returned invalid data.',
            });
        }

        const container = $("#mappingList");
        const exportTable = $("#exportTable tbody");
        
        container.empty();
        exportTable.empty();
        
        if (data.status === 'success' && data.data && data.data.length === 0) {
            $("#emptyState").removeClass('hidden');
            return;
        }
        
        if (data.status !== 'success' || !data.data) {
            $("#emptyState").removeClass('hidden');
            return;
        }
        
        $("#emptyState").addClass('hidden');

        data.data.forEach(row => {
            const item = $(`
                <div class="card bg-base-200 mapping-item cursor-pointer hover:bg-base-300 transition" data-id="${row.id}">
                    <div class="card-body py-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="avatar placeholder">
                                    <div class="bg-primary text-primary-content rounded w-10">
                                        <span><i class="fas fa-users"></i></span>
                                    </div>
                                </div>
                                <div>
                                    <div class="text-xs opacity-60">ID: ${row.id}</div>
                                    <div class="font-semibold">${row.b_head_name}</div>
                                    <div class="text-sm opacity-70"><i class="fas fa-user"></i> ${row.buyer_name}</div>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <button class="btn btn-sm btn-warning btn-edit" data-id="${row.id}">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-error btn-delete" data-id="${row.id}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `);
            
            item.find('.btn-edit').click(function(e) {
                e.stopPropagation();
                selectMapping(row);
            });
            
            item.find('.btn-delete').click(function(e) {
                e.stopPropagation();
                $("#mappingId").val(row.id);
                deleteMapping();
            });
            
            item.click(function(e) {
                if (!$(e.target).closest('button').length) {
                    selectMapping(row);
                }
            });
            
            container.append(item);
            
            exportTable.append(`
                <tr>
                    <td>${row.id}</td>
                    <td>${row.b_head_name}</td>
                    <td>${row.buyer_name}</td>
                </tr>
            `);
        });
    }, 'json').fail(function() {
        Swal.fire('Error', 'Failed to load mappings', 'error');
    });
}

function selectMapping(row) {
    $("#mappingId").val(row.id);
    $("#formTitle").text('Edit Mapping');
    
    ensureOptionExists("#b_head", row.b_head, row.b_head_name);
    $("#b_head").val(row.b_head);
    
    ensureOptionExists("#buyer", row.buyer, row.buyer_name);
    $("#buyer").val(row.buyer);
    
    $(".mapping-item").removeClass('bg-primary bg-opacity-10');
    $(`.mapping-item[data-id="${row.id}"]`).addClass('bg-primary bg-opacity-10');
    
    $('html, body').animate({
        scrollTop: $("#buyerMappingForm").offset().top - 100
    }, 500);
}

function ensureOptionExists(selectId, value, text) {
    const select = $(selectId);
    if (select.find(`option[value="${value}"]`).length === 0) {
        select.append($("<option>", {
            value: value,
            text: text
        }));
    }
}

$("#searchInput").on("keyup", function() {
    const value = $(this).val().toLowerCase();
    $("#mappingList .mapping-item").each(function() {
        const itemText = $(this).text().toLowerCase();
        if (itemText.indexOf(value) > -1) {
            $(this).removeClass('hidden');
        } else {
            $(this).addClass('hidden');
        }
    });
    
    const visibleItems = $("#mappingList .mapping-item").not('.hidden').length;
    if (visibleItems === 0) {
        $("#emptyState").removeClass('hidden');
        $("#emptyState h5").text('No matching mappings found');
    } else {
        $("#emptyState").addClass('hidden');
    }
});

$("#refreshBtn").click(function() {
    $(this).find('i').addClass('fa-spin');
    loadMappings();
    setTimeout(() => {
        $(this).find('i').removeClass('fa-spin');
    }, 700);
});

function exportToExcel() {
    const date = new Date();
    const dateStr = date.toISOString().split('T')[0];
    const ws = XLSX.utils.table_to_sheet(document.getElementById('exportTable'));
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, "Mappings");
    XLSX.writeFile(wb, `Buyer_Mappings_${dateStr}.xlsx`);
    Swal.fire({
        icon: 'success',
        title: 'Export Successful',
        text: 'Mappings have been exported to Excel',
        timer: 1500,
        showConfirmButton: false
    });
}

function exportToCSV() {
    const table = document.getElementById('exportTable');
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
    saveAs(blob, `Buyer_Mappings_${dateStr}.csv`);
    Swal.fire({
        icon: 'success',
        title: 'Export Successful',
        text: 'Mappings have been exported to CSV',
        timer: 1500,
        showConfirmButton: false
    });
}

$('#exportExcel').click(function(e) {
    e.preventDefault();
    exportToExcel();
});

$('#exportCSV').click(function(e) {
    e.preventDefault();
    exportToCSV();
});

$(document).ready(function() {
    loadBuyerHeads();
    loadBuyers();
    loadMappings();
});
</script>

<?php include '../common/layout-footer.php'; ?>

