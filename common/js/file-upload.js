
document.addEventListener('DOMContentLoaded', () => {
  const modal = document.getElementById('fileModal');
  const fileList = document.getElementById('fileList');
  const fileModalTitle = document.getElementById('fileModalTitle');
  const uploadFileBtn = document.getElementById('uploadFileBtn');
  const fileInput = document.getElementById('fileInput');

  let currentPrId = null;
  let currentType = null;
  let currentUrls = {};
  let statusId = null;
  let uploadAllowed = false;
  let deleteAllowed = false;

  // Use event delegation for dynamically created buttons
  document.addEventListener('click', async (e) => {
    // Check if the clicked element or its parent has one of our target classes
    const proformaBtn = e.target.closest('.proforma');
    const poBtn = e.target.closest('.po');
    const productBtn = e.target.closest('.product');

    const btn = proformaBtn || poBtn || productBtn;
    if (!btn) return;

    e.preventDefault();

    const id = btn.dataset.prId;
    statusId = btn.dataset.statusId;
    const role = btn.dataset.role;
    if (!id) {
      console.error('No PR ID found on button');
      return;
    }

    currentPrId = id;

    // Check permissions for upload/delete (viewing is always allowed)
    // let uploadAllowed = false;
    // let deleteAllowed = false;
    if (btn.classList.contains('proforma')) {
      uploadAllowed = [1, 5].includes(parseInt(statusId)) && ['bhead'].includes(role);
      console.log("st",statusId,role);
      
      deleteAllowed = uploadAllowed;
    } else if (btn.classList.contains('po')) {
      uploadAllowed = parseInt(statusId) === 7 && ['pohead', 'poteammember'].includes(role);
      deleteAllowed = uploadAllowed;
    } else if (btn.classList.contains('product')) {
      uploadAllowed = [1, 2, 3, 4, 5].includes(parseInt(statusId)) && ['bhead','buyer','admin'].includes(role);
      deleteAllowed = uploadAllowed;
    }

    if (btn.classList.contains('proforma')) {
      currentType = 'proforma';
      currentUrls = {
        fetch: `../fetch/fetch-files.php?id=${id}&type=proforma`,
        upload: '../api/update-files.php',
        delete: '../api/delete-files.php'
      };
      fileModalTitle.textContent = 'Proforma Files';
    } else if (btn.classList.contains('po')) {
      currentType = 'po';
      currentUrls = {
        fetch: `../fetch/fetch-files.php?id=${id}&type=po`,
        upload: '../api/update-files.php',
        delete: '../api/delete-files.php'
      };
      fileModalTitle.textContent = 'PO Files';
    } else if (btn.classList.contains('product')) {
      currentType = 'product';
      currentUrls = {
        fetch: `../fetch/fetch-files.php?id=${id}&type=product`,
        upload: '../api/update-files.php',
        delete: '../api/delete-files.php'
      };
      fileModalTitle.textContent = 'Product Files';
    }

    console.log('Opening file modal for:', currentType, 'PR ID:', currentPrId);

    // Reset file input
    fileInput.value = '';

    // Load files
    await loadFiles();

    // Show modal using DaisyUI method
    if (modal && typeof modal.showModal === 'function') {
      modal.showModal();
    } else {
      console.error('Modal not found or showModal not available');
    }
  });

  // Upload button handler
  if (uploadFileBtn) {
    uploadFileBtn.addEventListener('click', async () => {
      await uploadFile();
    });
  }

  // Handle Enter key on file input
  if (fileInput) {
    fileInput.addEventListener('keypress', (e) => {
      if (e.key === 'Enter') {
        uploadFile();
      }
    });
  }

  async function loadFiles() {
    if (!fileList) return;
    
    fileList.innerHTML = `<div class="flex justify-center"><span class="loading loading-spinner loading-md"></span></div>`;

    try {
      const res = await fetch(currentUrls.fetch);
      const data = await res.json();
      console.log('Files response:', data);

      if (data.status === 'success' && data.data && data.data.length) {
        renderFileList(data.data);
      } else {
        fileList.innerHTML = `<p class="text-sm text-gray-500 text-center">No files found.</p>`;
      }
    } catch (err) {
      console.error('Error loading files:', err);
      fileList.innerHTML = `<p class="text-error text-sm text-center">Failed to fetch files.</p>`;
    }
  }

  function renderFileList(files) {
    if (!files || files.length === 0) {
      fileList.innerHTML = `<p class="text-sm text-gray-500 text-center">No files found.</p>`;
      return;
    }

    fileList.innerHTML = files.map(file => `
      <div class="flex items-center justify-between bg-base-200 p-3 rounded-lg">
        <div class="flex items-center gap-3 flex-1">
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
        <button class="btn btn-xs btn-error delete-file ml-2 flex-shrink-0" 
                data-id="${file.id}" 
                data-url="${currentUrls.delete}">
          Delete
        </button>
      </div>
    `).join('');

    // Add delete event listeners
    fileList.querySelectorAll('.delete-file').forEach(delBtn => {
      delBtn.addEventListener('click', async (e) => {
        e.preventDefault();
        await deleteFile(delBtn);
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

  async function uploadFile() {
    if (!fileInput) return;

    // Check upload permissions
    if (!uploadAllowed) {
      showAlert('You do not have permission to upload files for this status.', 'warning');
      return;
    }

    const file = fileInput.files[0];
    if (!file) {
      showAlert('Please select a file to upload.', 'warning');
      return;
    }

    // Validate file size (5MB limit)
    if (file.size > 5 * 1024 * 1024) {
      showAlert('File size must be less than 5MB.', 'error');
      return;
    }

    const formData = new FormData();
    formData.append('file', file);
    formData.append('id', currentPrId);
    formData.append('type', currentType);
    
    // Add CSRF token
    try {
      const csrfResponse = await fetch('../auth/get-csrf-token.php');
      const csrfData = await csrfResponse.json();
      if (csrfData.status === 'success') {
        formData.append('csrf_token', csrfData.data.csrf_token);
      }
    } catch (error) {
      console.error('Failed to get CSRF token:', error);
    }

    try {
      uploadFileBtn.disabled = true;
      uploadFileBtn.textContent = 'Uploading...';
      uploadFileBtn.classList.add('loading');

      const uploadRes = await fetch(currentUrls.upload, {
        method: 'POST',
        body: formData
      });
      
      const uploadData = await uploadRes.json();
      console.log('Upload response:', uploadData);

      if (uploadData.status === 'success') {
        showAlert('File uploaded successfully!', 'success');
        fileInput.value = ''; // Clear input
        // Reload page after successful upload
        setTimeout(() => {
          window.location.reload();
        }, 1000);
      } else {
        showAlert(uploadData.message || 'Upload failed', 'error');
      }
    } catch (err) {
      console.error('Upload error:', err);
      showAlert('Upload failed. Please try again.', 'error');
    } finally {
      uploadFileBtn.disabled = false;
      uploadFileBtn.textContent = 'Upload New File';
      uploadFileBtn.classList.remove('loading');
    }
  }

  async function deleteFile(deleteBtn) {
    const fileId = deleteBtn.dataset.id;
    const deleteUrl = deleteBtn.dataset.url;

    // Check delete permissions
    if (!deleteAllowed) {
      showAlert('You do not have permission to delete files for this status.', 'warning');
      return;
    }

    if (typeof Swal === 'undefined') {
      if (!confirm('Are you sure you want to delete this file?')) return;
    } else {
      const confirmResult = await Swal.fire({
        title: 'Are you sure?',
        text: 'Are you sure you want to delete this file?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
      });
      
      if (!confirmResult.isConfirmed) return;
    }

    try {
      deleteBtn.disabled = true;
      deleteBtn.textContent = 'Deleting...';
      deleteBtn.classList.add('loading');

      const res = await fetch(`${deleteUrl}?id=${fileId}&type=${currentType}`);
      const result = await res.json();

      if (result.status === 'success') {
        showAlert('File deleted successfully!', 'success');
        await loadFiles(); // Refresh file list
      } else {
        showAlert(result.message || 'Failed to delete file', 'error');
      }
    } catch (err) {
      console.error('Delete error:', err);
      showAlert('Delete failed. Please try again.', 'error');
    } finally {
      deleteBtn.disabled = false;
      deleteBtn.textContent = 'Delete';
      deleteBtn.classList.remove('loading');
    }
  }

  function showAlert(message, type = 'info') {
    // Using SweetAlert2 since it's included in your header
    if (typeof Swal !== 'undefined') {
      Swal.fire({
        icon: type,
        title: message,
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
      });
    } else {
      // Fallback to Swal if available
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          icon: type,
          title: message,
          toast: true,
          position: 'top-end',
          showConfirmButton: false,
          timer: 3000,
          timerProgressBar: true,
        });
      }
    }
  }
});