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
$currentPage = 'status-report.php';
?>
<?php include '../common/layout.php'; ?>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Status Report</h1>
        <div class="flex gap-2">
            <button id="exportCSV" class="btn btn-success">
                <i class="fas fa-file-csv"></i> Export CSV
            </button>
            <button id="exportExcel" class="btn btn-success">
                <i class="fas fa-file-excel"></i> Export Excel
            </button>
        </div>
    </div>

    <!-- Table Section -->
    <div class="bg-base-200 p-6 rounded-lg">
        <div class="overflow-x-auto" style="max-height: calc(100vh - 300px);">
            <table class="table table-zebra w-full" id="statusReportTable">
                <thead id="statusReportTableHead">
                    <tr>
                        <th class="sticky-header">Buyer Head</th>
                        <th class="sticky-header">Buyer</th>
                        <th class="sticky-header">Category</th>
                        <th class="sticky-header">PO Head</th>
                        <th class="sticky-header">PO Team Member</th>
                        <!-- Status columns will be dynamically added here -->
                    </tr>
                </thead>
                <tbody id="statusReportTableBody">
                    <tr>
                        <td colspan="5" class="text-center">Loading...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>

<style>
    /* Sticky table header - fully opaque */
    #statusReportTable .sticky-header {
        position: sticky !important;
        top: 0 !important;
        background-color: #f9fafb !important;
        background: #f9fafb !important;
        background-image: none !important;
        z-index: 20 !important;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1) !important;
        opacity: 1 !important;
    }
    
    /* Make sure thead has proper background - fully opaque */
    #statusReportTable thead {
        background-color: #f9fafb !important;
        background: #f9fafb !important;
        position: relative;
        opacity: 1 !important;
    }
    
    #statusReportTable thead tr {
        background-color: #f9fafb !important;
        background: #f9fafb !important;
        opacity: 1 !important;
    }
    
    #statusReportTable thead th {
        background-color: #f9fafb !important;
        background: #f9fafb !important;
        opacity: 1 !important;
    }
    
    /* Override any DaisyUI table styles that might cause transparency */
    #statusReportTable.table thead th {
        background-color: #f9fafb !important;
        background: #f9fafb !important;
    }
    
    /* Make table container scrollable */
    .overflow-x-auto {
        max-height: calc(100vh - 400px);
        overflow-y: auto;
    }
</style>

<script>
$(document).ready(function() {
    const StatusReport = {
        state: {
            data: [],
            statusNames: {}
        },

        init() {
            this.bindEvents();
            this.loadData();
        },

        bindEvents() {
            $('#exportCSV').click(() => this.exportToCSV());
            $('#exportExcel').click(() => this.exportToExcel());
        },

        loadData() {
            $.get('../fetch/fetch-status-report.php', (response) => {
                if (response.status === 'success' && response.data) {
                    this.state.data = response.data.data || [];
                    this.state.statusNames = response.data.status_names || {};
                    this.renderTable();
                } else {
                    console.error('Error loading data:', response);
                    $('#statusReportTableBody').html('<tr><td colspan="100" class="text-center text-error">Error loading data</td></tr>');
                }
            }, 'json').fail(function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                $('#statusReportTableBody').html('<tr><td colspan="100" class="text-center text-error">Error: ' + error + '</td></tr>');
            });
        },

        renderTable() {
            const thead = $('#statusReportTableHead tr');
            const tbody = $('#statusReportTableBody');
            
            // Clear existing header (except first 5 columns)
            thead.find('th:gt(4)').remove();
            
            // Add status columns to header
            const statusIds = Object.keys(this.state.statusNames).sort((a, b) => parseInt(a) - parseInt(b));
            statusIds.forEach(statusId => {
                const statusName = this.state.statusNames[statusId];
                thead.append(`<th class="sticky-header">${statusName}</th>`);
            });
            
            // Clear and render body
            tbody.empty();

            if (this.state.data.length === 0) {
                const colspan = 5 + statusIds.length;
                tbody.html(`<tr><td colspan="${colspan}" class="text-center">No records found</td></tr>`);
                return;
            }

            this.state.data.forEach(row => {
                const tr = $('<tr>');
                tr.append(`<td>${row.buyer_head || '-'}</td>`);
                tr.append(`<td>${row.buyer || '-'}</td>`);
                tr.append(`<td>${row.category || '-'}</td>`);
                tr.append(`<td>${row.po_heads || '-'}</td>`);
                tr.append(`<td>${row.po_team_members || '-'}</td>`);
                
                // Add status counts
                statusIds.forEach(statusId => {
                    const count = row[`status_${statusId}_count`] || 0;
                    tr.append(`<td class="text-center">${count}</td>`);
                });
                
                tbody.append(tr);
            });
        },

        exportToCSV() {
            const statusIds = Object.keys(this.state.statusNames).sort((a, b) => parseInt(a) - parseInt(b));
            let csv = 'Buyer Head,Buyer,Category,PO Head,PO Team Member';
            
            statusIds.forEach(statusId => {
                csv += `,"${this.state.statusNames[statusId]}"`;
            });
            csv += '\n';
            
            this.state.data.forEach(row => {
                csv += `"${row.buyer_head || ''}","${row.buyer || ''}","${row.category || ''}","${row.po_heads || ''}","${row.po_team_members || ''}"`;
                statusIds.forEach(statusId => {
                    const count = row[`status_${statusId}_count`] || 0;
                    csv += `,${count}`;
                });
                csv += '\n';
            });

            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            saveAs(blob, `status_report_${new Date().toISOString().split('T')[0]}.csv`);
        },

        exportToExcel() {
            const statusIds = Object.keys(this.state.statusNames).sort((a, b) => parseInt(a) - parseInt(b));
            const headers = ['Buyer Head', 'Buyer', 'Category', 'PO Head', 'PO Team Member'];
            statusIds.forEach(statusId => {
                headers.push(this.state.statusNames[statusId]);
            });
            
            const rows = this.state.data.map(row => {
                const rowData = [
                    row.buyer_head || '',
                    row.buyer || '',
                    row.category || '',
                    row.po_heads || '',
                    row.po_team_members || ''
                ];
                statusIds.forEach(statusId => {
                    rowData.push(row[`status_${statusId}_count`] || 0);
                });
                return rowData;
            });
            
            const wb = XLSX.utils.book_new();
            const ws = XLSX.utils.aoa_to_sheet([headers, ...rows]);
            XLSX.utils.book_append_sheet(wb, ws, 'Status Report');
            XLSX.writeFile(wb, `status_report_${new Date().toISOString().split('T')[0]}.xlsx`);
        }
    };

    StatusReport.init();
});
</script>

<?php include '../common/layout-footer.php'; ?>

