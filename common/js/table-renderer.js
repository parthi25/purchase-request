// tableRenderer.js

// Helper functions for file modal
function openFileModal(type, id, statusId, role) {
  const modal = document.getElementById('fileModal');
  const fileList = document.getElementById('fileList');
  const fileModalTitle = document.getElementById('fileModalTitle');
  const uploadFileBtn = document.getElementById('uploadFileBtn');
  const fileInput = document.getElementById('fileInput');

  let currentPrId = id;
  let currentType = type;
  let currentUrls = {
    fetch: `../fetch/fetch-files.php?id=${id}&type=${type}`,
    upload: '../api/update-files.php',
    delete: '../api/delete-files.php'
  };

  // Check permissions for upload/delete (viewing is always allowed)
  // Permissions are now checked dynamically via API in file-upload.js
  // This function is kept for backward compatibility but permissions are handled in file-upload.js
  let uploadAllowed = false;
  let deleteAllowed = false;
  
  // Fallback permissions (file-upload.js will override with database permissions)
  if (type === 'proforma') {
    uploadAllowed = [1, 5].includes(parseInt(statusId));
    deleteAllowed = uploadAllowed;
  } else if (type === 'po') {
    uploadAllowed = parseInt(statusId) === 7 && ['pohead', 'poteammember'].includes(role);
    deleteAllowed = uploadAllowed;
  } else if (type === 'product') {
    uploadAllowed = [1, 2, 3, 4, 5].includes(parseInt(statusId));
    deleteAllowed = uploadAllowed;
  }

  fileModalTitle.textContent = type === 'proforma' ? 'Proforma Files' : type === 'po' ? 'PO Files' : 'Product Files';
  fileInput.value = '';

  // Load files
  fileList.innerHTML = `<div class="flex justify-center"><span class="loading loading-spinner loading-md"></span></div>`;

  fetch(currentUrls.fetch)
    .then(res => res.json())
    .then(data => {
      if (data.status === 'success' && data.data && data.data.length) {
        renderFileList(data.data, currentUrls, currentType, uploadAllowed, deleteAllowed);
      } else {
        fileList.innerHTML = `<p class="text-sm text-gray-500 text-center">No files found.</p>`;
      }
    })
    .catch(err => {
      console.error('Error loading files:', err);
      fileList.innerHTML = `<p class="text-error text-sm text-center">Failed to fetch files.</p>`;
    });

  // Show modal
  if (modal && typeof modal.showModal === 'function') {
    modal.showModal();
  }
}

function renderFileList(files, currentUrls, currentType, uploadAllowed, deleteAllowed) {
  if (!files || files.length === 0) {
    fileList.innerHTML = `<p class="text-sm text-gray-500 text-center">No files found.</p>`;
    return;
  }

  fileList.innerHTML = files.map(file => `
    <div class="flex items-center justify-between bg-base-200 p-3 rounded-lg min-w-[300px] min-h-[60px]">
      <div class="flex items-center gap-3 flex-1 min-w-0">
        <div class="flex-shrink-0">
          ${getFileIcon(file.url)}
        </div>
        <div class="flex-1 min-w-0">
          <a href="../${file.url}" target="_blank" class="link link-hover text-sm truncate block" title="${getFileName(file.url)}">
            ${getFileName(file.url)}
          </a>
          <div class="text-xs text-gray-500">
            ${file.uploaded_at ? new Date(file.uploaded_at).toLocaleDateString() : ''}
          </div>
        </div>
      </div>
      <div class="flex items-center gap-2 flex-shrink-0 ml-2">
        <a href="../${file.url}" download="${getFileName(file.url)}" class="btn btn-xs btn-primary">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
          </svg>
          Download
        </a>
        ${deleteAllowed ? `<button class="btn btn-xs btn-error delete-file"
                data-id="${file.id}"
                data-url="${currentUrls.delete}">
          Delete
        </button>` : ''}
      </div>
    </div>
  `).join('');

  // Add delete event listeners
  fileList.querySelectorAll('.delete-file').forEach(delBtn => {
    delBtn.addEventListener('click', async (e) => {
      e.preventDefault();
      const fileId = delBtn.dataset.id;
      
      // Use DaisyUI confirm dialog
      const confirmResult = await showConfirm(
        'Are you sure?',
        'Are you sure you want to delete this file?',
        'Yes, delete it!',
        'Cancel'
      );
      
      if (!confirmResult.isConfirmed) return;

      try {
        delBtn.disabled = true;
        delBtn.textContent = 'Deleting...';
        const res = await fetch(`${currentUrls.delete}?id=${fileId}&type=${currentType}`);
        const result = await res.json();

        if (result.status === 'success') {
          showAlert('File deleted successfully!', 'success');
          // Reload files
          openFileModal(currentType, currentPrId);
        } else {
          showAlert(result.message || 'Failed to delete file', 'error');
        }
      } catch (err) {
        showAlert('Delete failed. Please try again.', 'error');
      } finally {
        delBtn.disabled = false;
        delBtn.textContent = 'Delete';
      }
    });
  });
}

function getFileName(url) {
  if (!url) return 'Unknown file';
  return url.split('/').pop() || 'Unknown file';
}

function getFileIcon(fileUrl) {
  const extension = fileUrl.split('.').pop()?.toLowerCase();
  const iconClass = "w-5 h-5";
  
  if (['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'].includes(extension)) {
    return `<svg class="${iconClass} text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
    </svg>`;
  } else if (['pdf'].includes(extension)) {
    return `<svg class="${iconClass} text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
    </svg>`;
  } else {
    return `<svg class="${iconClass} text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
    </svg>`;
  }
}

function showAlert(message, type = 'info') {
  // Use DaisyUI toast notification
  showToast(message, type, 3000);
}

// Table configurations
const TableConfigs = {
  // Buyer role table configuration
  buyer: {
    role: "buyer",
    columns: [
      { key: "id", label: "Ref ID", sortable: true },
      { key: "po_status", label: "Status", sortable: true },
      { key: "supplier", label: "Supplier", sortable: true },
      { key: "b_head", label: "B Head", sortable: true },
      { key: "buyer", label: "Buyer", sortable: true },
      { key: "po_head", label: "PO Head", sortable: true },
      { key: "po_team", label: "PO Team", sortable: true },
      { key: "category", label: "Category", sortable: true },
      { key: "qty", label: "Quantity", sortable: true },

      { key: "created_by", label: "Created By", sortable: true },
      { key: "created_at", label: "Created On", sortable: true },
      { key: "actions", label: "Actions", sortable: false },
    ],
    showButtons: { edit: true, proforma: true, po: true },
  },

  // Admin role table configuration
  admin: {
    role: "admin",
    columns: [
      { key: "id", label: "Ref ID", sortable: true },
      { key: "po_status", label: "Status", sortable: true },
      { key: "supplier", label: "Supplier", sortable: true },
      { key: "b_head", label: "B Head", sortable: true },
      { key: "buyer", label: "Buyer", sortable: true },
      { key: "po_head", label: "PO Head", sortable: true },
      { key: "po_team", label: "PO Team", sortable: true },
      { key: "category", label: "Category", sortable: true },
      { key: "qty", label: "Quantity", sortable: true },

      { key: "created_by", label: "Created By", sortable: true },
      { key: "created_at", label: "Created On", sortable: true },
      { key: "actions", label: "Actions", sortable: false },
    ],
    showButtons: { edit: true, proforma: true, po: true },
  },

  // Buyer Head role table configuration
  bhead: {
    role: "bhead",
    tableClasses: "table table-hover table-striped",
    columns: [
      { key: "id", label: "Ref ID", sortable: true },
      { key: "po_status", label: "Status", sortable: true },
      { key: "supplier", label: "Supplier", sortable: true },
      { key: "b_head", label: "B Head", sortable: true },
      { key: "buyer", label: "Buyer", sortable: true },
      { key: "po_head", label: "PO Head", sortable: true },
      { key: "po_team", label: "PO Team", sortable: true },
      { key: "category", label: "Category", sortable: true },
      { key: "qty", label: "Quantity", sortable: true },

      { key: "created_by", label: "Created By", sortable: true },
      { key: "created_at", label: "Created On", sortable: true },
      { key: "actions", label: "Actions", sortable: false },
    ],
    showButtons: { edit: true, proforma: true, po: true },
  },

  // PO Team role table configuration
  pohead: {
    role: "pohead",
    columns: [
      { key: "id", label: "Ref ID", sortable: true },
      { key: "po_status", label: "Status", sortable: true },
      { key: "supplier", label: "Supplier", sortable: true },
      { key: "b_head", label: "B Head", sortable: true },
      { key: "buyer", label: "Buyer", sortable: true },
      { key: "po_head", label: "PO Head", sortable: true },
      { key: "po_team", label: "PO Team", sortable: true },
      { key: "category", label: "Category", sortable: true },
      { key: "qty", label: "Quantity", sortable: true },
      
      { key: "created_by", label: "Created By", sortable: true },
      { key: "created_at", label: "Created On", sortable: true },
      { key: "actions", label: "Actions", sortable: false },
    ],
    showButtons: { edit: false, proforma: true, po: true },
  },

  // PO Team Member role table configuration
  poteammember: {
    role: "poteammember",
    columns: [
      { key: "id", label: "Ref ID", sortable: true },
      { key: "po_status", label: "Status", sortable: true },
      { key: "supplier", label: "Supplier", sortable: true },
      { key: "b_head", label: "B Head", sortable: true },
      { key: "buyer", label: "Buyer", sortable: true },
      { key: "po_head", label: "PO Head", sortable: true },
      { key: "po_team", label: "PO Team", sortable: true },
      { key: "category", label: "Category", sortable: true },
      { key: "qty", label: "Quantity", sortable: true },

      { key: "created_by", label: "Created By", sortable: true },
      { key: "created_at", label: "Created On", sortable: true },
      { key: "actions", label: "Actions", sortable: false },
    ],
    showButtons: { edit: false, proforma: true, po: true },
  },

  // Dashboard view table configuration
  dashboard: {
    role: "dashboard",
    columns: [
      { key: "id", label: "Ref ID", sortable: true },
      { key: "po_status", label: "Status", sortable: true },
      { key: "supplier", label: "Supplier", sortable: true },
      { key: "b_head", label: "B Head", sortable: true },
      { key: "buyer", label: "Buyer", sortable: true },
      { key: "po_head", label: "PO Head", sortable: true },
      { key: "po_team", label: "PO Team", sortable: true },
      { key: "category", label: "Category", sortable: true },
      { key: "qty", label: "Quantity", sortable: true },

      { key: "created_by", label: "Created By", sortable: true },
      { key: "created_at", label: "Created On", sortable: true },
      { key: "actions", label: "Actions", sortable: false },
    ],
    showButtons: { edit: false, proforma: true, po: true },
  },
};

// Get table config by role
function getTableConfig(role) {
  return TableConfigs[role] || TableConfigs.buyer;
}

// Helper function to convert text to title case (first letter capitalized)
function toTitleCase(str) {
    if (!str || typeof str !== 'string') return str || '';
    return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
}

// TableRenderer class
class TableRenderer {
  constructor(containerId, role = "buyer") {
    this.containerId = containerId;
    const config = getTableConfig(role);
    this.config = {
      columns: config.columns,
      tableClasses: "table table-zebra w-full",
      showButtons: config.showButtons,
      role: role,
      statusBadges: {
        1: '<span class="text-success">Open</span>',
        2: '<span class="text-info">Forwarded</span>',
        3: '<span class="text-warning">Awaiting PO</span>',
        4: '<span class="text-primary">Proforma</span>',
        5: '<span class="text-error">To Category Head</span>',
        6: '<span class="text-base-content/70">To PO Team</span>',
        7: '<span class="text-success">PO Generated</span>',
        8: '<span class="text-error">Rejected</span>',
        9: '<span class="text-success">Forwarded to PO Members</span>',
      },
    };
    this.data = [];
    this.init();
  }

  init() {
    this.createTable();
    this.bindSorting();
    this.observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('opacity-100');
        }
      });
    }, { threshold: 0.1 });
  }

  createTable() {
    const container = document.getElementById(this.containerId);
    if (!container) return;

    const headers = this.config.columns.map(
      (col, index) =>
        `<th class="${
          col.sortable ? "sortable-header" : ""
        }" data-column="${col.key}"${index === 0 ? ' ' : ''}>${col.label}</th>`
    ).join("");

    const html = `
            <div class="overflow-x-auto">
                <div class="table-wrapper" style="max-height: calc(100vh - 300px); overflow-y: auto; position: relative;">
                    <table class="${this.config.tableClasses}" id="dataTable">
                        <thead style="position: sticky; top: 0; z-index: 10; background-color: hsl(var(--b1));">
                            <tr>
                                ${headers}
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        `;
    container.innerHTML = html;
    
    // Store reference to scrollable wrapper for infinite scroll
    this.scrollableWrapper = container.querySelector('.table-wrapper');
  }

  renderRows(data) {
    this.data = data || this.data;
    const tbody = document.querySelector("#dataTable tbody");
    if (!tbody) return;

    // Clear existing content
    tbody.innerHTML = '';
    
    // Render in chunks to avoid blocking the main thread
    const chunkSize = 50; // Process 50 rows at a time
    let index = 0;
    
    const renderChunk = () => {
      const end = Math.min(index + chunkSize, this.data.length);
      const fragment = document.createDocumentFragment();
      
      for (let i = index; i < end; i++) {
        const row = this.data[i];
        const tr = document.createElement('tr');
        tr.innerHTML = this.config.columns.map((col, colIndex) =>
          `<td${colIndex === 0 ? ' ' : ''}>${this.formatCell(row, col)}</td>`
        ).join("");
        tr.setAttribute('data-id', row.id);
        tr.className = 'opacity-0 transition-opacity duration-500';
        fragment.appendChild(tr);
      }
      
      tbody.appendChild(fragment);
      
      // Observe and attach listeners for this chunk
      const newRows = tbody.querySelectorAll('tr[data-id]');
      for (let i = newRows.length - (end - index); i < newRows.length; i++) {
        this.observer.observe(newRows[i]);
      }
      
      // Attach edit button listeners for this chunk
      const newButtons = Array.from(tbody.querySelectorAll('.openEditPRBtn'))
        .slice(-(end - index));
      newButtons.forEach(btn => {
        btn.addEventListener('click', () => {
          const id = btn.dataset.prId;
          if (typeof openPRModal === 'function') {
            openPRModal(id);
          }
        });
      });
      
      index = end;
      
      // Continue rendering if there's more data
      if (index < this.data.length) {
        requestAnimationFrame(renderChunk);
      }
    };
    
    // Start rendering
    requestAnimationFrame(renderChunk);
  }

  appendRows(newData) {
    this.data = [...this.data, ...newData];
    const tbody = document.querySelector("#dataTable tbody");
    if (!tbody) return;

    // Render in chunks to avoid blocking the main thread
    const chunkSize = 50;
    let index = 0;
    const startIndex = this.data.length - newData.length;
    
    const renderChunk = () => {
      const end = Math.min(index + chunkSize, newData.length);
      const fragment = document.createDocumentFragment();
      
      for (let i = index; i < end; i++) {
        const row = newData[i];
        const tr = document.createElement('tr');
        tr.innerHTML = this.config.columns.map((col, colIndex) =>
          `<td${colIndex === 0 ? ' ' : ''}>${this.formatCell(row, col)}</td>`
        ).join("");
        tr.setAttribute('data-id', row.id);
        tr.className = 'opacity-0 transition-opacity duration-500';
        fragment.appendChild(tr);
      }
      
      tbody.appendChild(fragment);
      
      // Observe and attach listeners for this chunk
      const newRows = tbody.querySelectorAll('tr[data-id]');
      for (let i = newRows.length - (end - index); i < newRows.length; i++) {
        this.observer.observe(newRows[i]);
      }
      
      // Attach edit button listeners for this chunk
      const newButtons = Array.from(tbody.querySelectorAll('.openEditPRBtn'))
        .slice(-(end - index));
      newButtons.forEach(btn => {
        btn.addEventListener('click', () => {
          const id = btn.dataset.prId;
          if (typeof openPRModal === 'function') {
            openPRModal(id);
          }
        });
      });
      
      index = end;
      
      // Continue rendering if there's more data
      if (index < newData.length) {
        requestAnimationFrame(renderChunk);
      }
    };
    
    // Start rendering
    requestAnimationFrame(renderChunk);
  }

  createRow(row) {
    const cells = this.config.columns.map((col, index) =>
      `<td${index === 0 ? ' ' : ''}>${this.formatCell(row, col)}</td>`
    ).join("");

    return `<tr data-id="${row.id}" class="opacity-0 transition-opacity duration-500">
            ${cells}
        </tr>`;
  }

formatCell(row, column) {
    switch (column.key) {
        case "id":
            return row.id || "Unknown";
        case "po_status":
            // Use database statuses if available, otherwise fallback to config
            if (window.StatusBadges) {
                return window.StatusBadges.getBadge(row.po_status, 'simple');
            }
            return (
                this.config.statusBadges[String(row.po_status)] ||
                '<span class="text-base-content/50">Unknown</span>'
            );
        case "supplier":
            return toTitleCase(row.supplier) || "Unknown";
        case "b_head":
            return toTitleCase(row.b_head) || "Unknown";
        case "buyer":
            return toTitleCase(row.buyer) || "Unknown";
        case "po_head":
            return toTitleCase(row.po_team) || "-";
        case "po_team":
            return toTitleCase(row.po_team_member) || "-";
        case "category":
            return toTitleCase(row.category_name) || "Unknown";
        case "qty":
            const qtyValue = row.qty || 0;
            const uomValue = row.uom || '';
            return uomValue ? `${qtyValue} ${uomValue}` : qtyValue || "Unknown";
        // case "images":
        //     return row.images && row.images.length > 0 
        //         ? `<span class="text-green-500">✓ (${row.images.length})</span>`
        //         : '<span class="text-gray-400">✗</span>';
        case "created_by":
            return row.created_by || "Unknown";
        case "created_at":
            return row.created_at ? new Date(row.created_at).toLocaleDateString() : "Unknown";
        case "actions":
            let buttons = '<div class="flex gap-1">';
            buttons += `<button class="btn btn-sm btn-outline read-more-toggle" data-id='${row.id}'>remarks</button>`;
            if (this.config.showButtons.edit && row.po_status === 1) buttons += `<button class="btn btn-sm btn-outline openEditPRBtn" data-pr-id='${row.id}'>Edit</button>`;
           
            if (this.config.showButtons.proforma && ((this.config.role === 'bhead' && [1, 5].includes(row.po_status)) || this.config.role === 'admin')) {
    const hasProforma = row.proforma_ids && row.proforma_ids[0] ? true : false;
    buttons += `
        <button class="btn btn-sm btn-outline proforma" data-pr-id='${row.id}' data-status-id='${row.po_status}' data-role='${this.config.role}'>
            Proforma
            ${hasProforma ? `<span class="text-success">&#10003;</span>` : ''}
        </button>
    `;
}

// PO button
if (this.config.showButtons.po && row.po_status === 7) {
    const hasPO = row.po_url ? true : false;
    buttons += `
        <button class="btn btn-sm btn-outline po" data-pr-id='${row.id}' data-status-id='${row.po_status}' data-role='${this.config.role}'>
            PO
            ${hasPO ? `<span class="text-success">&#10003;</span>` : ''}
        </button>
    `;
}
            if (this.config.role === 'admin' ? [1].includes(row.po_status) :
               this.config.role === 'bhead' ? [1, 5].includes(row.po_status) :
               this.config.role === 'buyer' ? [2, 3, 4].includes(row.po_status) :
               this.config.role === 'pohead' || this.config.role === 'poteam' ? [6].includes(row.po_status) :
               false) buttons += `<button class="btn btn-sm btn-outline update-status" data-id='${row.id}' data-status='${row.po_status}'>--></button>`;
            if (this.config.role === 'poteammember' && row.po_status === 9) buttons += `<button class="btn btn-sm btn-info insert-po" data-id='${row.id}'>Insert PO</button>`;

            buttons += "</div>";
            return buttons;

        default:
            return row[column.key] || "-";
    }
}

  bindSorting() {
    const headers = document.querySelectorAll(".sortable-header");
    headers.forEach((header) => {
      header.addEventListener("click", () => {
        const col = header.dataset.column;
        const direction = header.dataset.sort === "asc" ? "desc" : "asc";
        headers.forEach((h) => {
          h.dataset.sort = "";
          h.classList.remove("sort-asc", "sort-desc");
        });
        header.dataset.sort = direction;
        header.classList.add(`sort-${direction}`);
        this.sortData(col, direction);
      });
    });
  }

  sortData(column, direction) {
    this.data.sort((a, b) => {
      const aValue = a[column] || "";
      const bValue = b[column] || "";
      if (direction === "asc")
        return aValue < bValue ? -1 : aValue > bValue ? 1 : 0;
      return aValue > bValue ? -1 : aValue < bValue ? 1 : 0;
    });
    this.renderRows(this.data);
  }

  changeRole(role) {
    const config = getTableConfig(role);
    this.config.columns = config.columns;
    this.config.showButtons = config.showButtons;
    this.config.role = role;
    this.createTable();
    if (this.data.length) this.renderRows(this.data);
  }

  filterRows(searchTerm) {
    const term = (searchTerm || "").toLowerCase();
    document.querySelectorAll("#dataTable tbody tr").forEach((row) => {
      row.style.display = row.textContent.toLowerCase().includes(term)
        ? ""
        : "none";
    });
  }
}

// Make globally available
window.TableRenderer = TableRenderer;
