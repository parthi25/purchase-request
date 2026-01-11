document.addEventListener('DOMContentLoaded', () => {
  const modal = document.getElementById('fileModal');
  const fileList = document.getElementById('fileList');
  const fileModalTitle = document.getElementById('fileModalTitle');
  const uploadFileBtn = document.getElementById('uploadFileBtn');
  const fileInput = document.getElementById('fileInput');
  const newItemFields = document.getElementById('newItemFields');
  const itemDetailsFileInput = document.getElementById('itemDetailsFileInput');
  const itemInfoInput = document.getElementById('itemInfoInput');

  let currentPrId = null;
  let currentType = null;
  let currentUrls = {};
  let statusId = null;
  let uploadAllowed = false;
  let deleteAllowed = false;
  let filePermissions = null;

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

    // Load permissions from API if not already loaded
    if (!filePermissions) {
      try {
        const permRes = await fetch('../fetch/fetch-file-permissions.php');
        const permData = await permRes.json();
        if (permData.status === 'success') {
          filePermissions = permData.data;
        }
      } catch (err) {
        console.error('Failed to load permissions, using fallback:', err);
        filePermissions = {};
      }
    }

    // Check permissions for upload/delete from database
    uploadAllowed = false;
    deleteAllowed = false;
    
    if (filePermissions && filePermissions[btn.classList.contains('proforma') ? 'proforma' : btn.classList.contains('po') ? 'po' : 'product']) {
      const fileType = btn.classList.contains('proforma') ? 'proforma' : btn.classList.contains('po') ? 'po' : 'product';
      const perms = filePermissions[fileType];
      const currentStatus = parseInt(statusId);
      
      if (perms.upload_statuses && perms.upload_statuses.includes(currentStatus)) {
        uploadAllowed = true;
      }
      if (perms.delete_statuses && perms.delete_statuses.includes(currentStatus)) {
        deleteAllowed = true;
      }
    } else {
      // Fallback to hardcoded permissions if API fails
      if (btn.classList.contains('proforma')) {
        uploadAllowed = [1, 5].includes(parseInt(statusId)) && ['bhead'].includes(role);
        deleteAllowed = uploadAllowed;
      } else if (btn.classList.contains('po')) {
        uploadAllowed = parseInt(statusId) === 7 && ['pohead', 'poteammember'].includes(role);
        deleteAllowed = uploadAllowed;
      } else if (btn.classList.contains('product')) {
        uploadAllowed = [1, 2, 3, 4, 5].includes(parseInt(statusId)) && ['bhead','buyer','admin'].includes(role);
        deleteAllowed = uploadAllowed;
      }
    }

    if (btn.classList.contains('proforma')) {
      currentType = 'proforma';
      currentUrls = {
        fetch: `../fetch/fetch-files.php?id=${id}&type=proforma`,
        upload: '../api/update-files.php',
        delete: '../api/delete-files.php'
      };
      fileModalTitle.textContent = 'Proforma Files';
      // Show new item fields for proforma
      // TEMPORARILY HIDDEN - Will be unhidden in future
      // if (newItemFields) {
      //   newItemFields.classList.remove('hidden');
      // }
    } else if (btn.classList.contains('po')) {
      currentType = 'po';
      currentUrls = {
        fetch: `../fetch/fetch-files.php?id=${id}&type=po`,
        upload: '../api/update-files.php',
        delete: '../api/delete-files.php'
      };
      fileModalTitle.textContent = 'PO Files';
      // Hide new item fields for non-proforma
      if (newItemFields) {
        newItemFields.classList.add('hidden');
      }
    } else if (btn.classList.contains('product')) {
      currentType = 'product';
      currentUrls = {
        fetch: `../fetch/fetch-files.php?id=${id}&type=product`,
        upload: '../api/update-files.php',
        delete: '../api/delete-files.php'
      };
      fileModalTitle.textContent = 'Product Files';
      // Set file input to accept images for product files
      if (fileInput) {
        fileInput.setAttribute('accept', 'image/*');
      }
      // Hide new item fields for non-proforma
      if (newItemFields) {
        newItemFields.classList.add('hidden');
      }
    } else {
      // Remove accept restriction for other file types
      if (fileInput) {
        fileInput.removeAttribute('accept');
      }
      // Hide new item fields
      if (newItemFields) {
        newItemFields.classList.add('hidden');
      }
    }

    console.log('Opening file modal for:', currentType, 'PR ID:', currentPrId);

    // Reset file inputs and textarea
    fileInput.value = '';
    if (itemDetailsFileInput) {
      itemDetailsFileInput.value = '';
    }
    if (itemInfoInput) {
      itemInfoInput.value = '';
    }

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

    // For product files, check if we have images to show in carousel
    if (currentType === 'product') {
      const imageFiles = files.filter(file => isImageFile(file.url));
      const nonImageFiles = files.filter(file => !isImageFile(file.url));

      if (imageFiles.length > 0) {
        // Render image carousel for product images
        let carouselHtml = `
          <div class="w-full flex justify-center mb-4">
            <div class="carousel carousel-vertical rounded-box h-96">
        `;
        
        imageFiles.forEach(file => {
          carouselHtml += `
            <div class="carousel-item h-full flex items-center justify-center">
              <img src="../${file.url}" alt="${getFileName(file.url)}" class="max-w-full max-h-full object-contain" loading="lazy" decoding="async" />
            </div>
          `;
        });
        
        carouselHtml += `
            </div>
          </div>
        `;

        // Add delete buttons for images
        if (deleteAllowed) {
          carouselHtml += `
            <div class="flex flex-wrap gap-2 mb-4 justify-center">
              ${imageFiles.map(file => `
                <button class="btn btn-xs btn-error delete-file" 
                        data-id="${file.id}" 
                        data-url="${currentUrls.delete}">
                  Delete ${getFileName(file.url)}
                </button>
              `).join('')}
            </div>
          `;
        }

        // Add non-image files list if any
        if (nonImageFiles.length > 0) {
          carouselHtml += `
            <div class="divider my-4">Other Files</div>
            <div class="space-y-2 max-h-60 overflow-y-auto">
              ${nonImageFiles.map(file => `
                <div class="flex items-center justify-between bg-base-200 p-3 rounded-lg min-w-[300px] min-h-[60px]">
                  <div class="flex items-center gap-3 flex-1 min-w-0">
                    <div class="flex-shrink-0">
                      ${getFileIcon(file.url)}
                    </div>
                    <div class="flex-1 min-w-0">
                      <a href="../${file.url}" target="_blank" class="link link-hover text-sm truncate block" title="${getFileName(file.url)}">
                        ${getFileName(file.url)}
                      </a>
                    </div>
                  </div>
                  <div class="flex items-center gap-2 flex-shrink-0 ml-2">
                    <a href="../${file.url}" download="${getFileName(file.url)}" class="btn btn-xs btn-primary">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                      </svg>
                      Download
                    </a>
                    ${deleteAllowed ? `
                      <button class="btn btn-xs btn-error delete-file" 
                              data-id="${file.id}" 
                              data-url="${currentUrls.delete}">
                        Delete
                      </button>
                    ` : ''}
                  </div>
                </div>
              `).join('')}
            </div>
          `;
        }

        fileList.innerHTML = carouselHtml;
      } else {
        // No images, show regular list
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
              ${deleteAllowed ? `
                <button class="btn btn-xs btn-error delete-file" 
                        data-id="${file.id}" 
                        data-url="${currentUrls.delete}">
                  Delete
                </button>
              ` : ''}
            </div>
          </div>
        `).join('');
      }
    } else {
      // For non-product files, use regular list view
      fileList.innerHTML = files.map(file => {
        let itemDetailsHtml = '';
        let itemInfoHtml = '';
        
        // Show item details and info for proforma files
        if (currentType === 'proforma') {
          if (file.item_details_url) {
            itemDetailsHtml = `
              <div class="mt-2 text-xs">
                <span class="font-semibold">Item Details:</span>
                <a href="../${file.item_details_url}" target="_blank" class="link link-primary ml-1">
                  ${getFileName(file.item_details_url)}
                </a>
              </div>
            `;
          }
          if (file.item_info) {
            itemInfoHtml = `
              <div class="mt-1 text-xs text-gray-600">
                <span class="font-semibold">Item Info:</span>
                <span class="ml-1">${file.item_info}</span>
              </div>
            `;
          }
        }
        
        return `
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
                ${itemDetailsHtml}
                ${itemInfoHtml}
              </div>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0 ml-2">
              <a href="../${file.url}" download="${getFileName(file.url)}" class="btn btn-xs btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Download
              </a>
              ${deleteAllowed ? `
                <button class="btn btn-xs btn-error delete-file" 
                        data-id="${file.id}" 
                        data-url="${currentUrls.delete}">
                  Delete
                </button>
              ` : ''}
            </div>
          </div>
        `;
      }).join('');
    }

    // Add delete event listeners
    fileList.querySelectorAll('.delete-file').forEach(delBtn => {
      delBtn.addEventListener('click', async (e) => {
        e.preventDefault();
        await deleteFile(delBtn);
      });
    });
  }

  function isImageFile(url) {
    if (!url) return false;
    const extension = url.split('.').pop()?.toLowerCase();
    return ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'].includes(extension);
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
      showToast('You do not have permission to upload files for this status.', 'warning', 4000);
      return;
    }

    const file = fileInput.files[0];
    if (!file) {
      showToast('Please select a file to upload.', 'warning');
      return;
    }

    // Validate file size (5MB limit)
    if (file.size > 5 * 1024 * 1024) {
      showToast('File size must be less than 5MB.', 'error');
      return;
    }

    // Now upload the main file
    const formData = new FormData();
    formData.append('file', file);
    formData.append('id', currentPrId);
    formData.append('type', currentType);
    
    // Add item details file and info for proforma
    if (currentType === 'proforma') {
      // Add item details file if provided
      if (itemDetailsFileInput && itemDetailsFileInput.files[0]) {
        const itemDetailsFile = itemDetailsFileInput.files[0];
        
        // Validate item details file size
        if (itemDetailsFile.size > 5 * 1024 * 1024) {
          showToast('Item details file size must be less than 5MB.', 'error');
          return;
        }
        
        formData.append('item_details_file', itemDetailsFile);
      }
      
      // Add item info text if provided
      if (itemInfoInput && itemInfoInput.value.trim()) {
        formData.append('item_info', itemInfoInput.value.trim());
      }
    }
    
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
        showToast('File uploaded successfully!', 'success');
        fileInput.value = ''; // Clear input
        if (itemDetailsFileInput) {
          itemDetailsFileInput.value = ''; // Clear item details input
        }
        if (itemInfoInput) {
          itemInfoInput.value = ''; // Clear item info input
        }
        // Reload page after successful upload
        setTimeout(() => {
          window.location.reload();
        }, 1000);
      } else {
        showToast(uploadData.message || 'Upload failed', 'error');
      }
    } catch (err) {
      console.error('Upload error:', err);
      showToast('Upload failed. Please try again.', 'error');
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
      showToast('You do not have permission to delete files for this status.', 'warning', 4000);
      return;
    }

    // Use DaisyUI confirm dialog
    const confirmResult = await showConfirm(
      'Are you sure?',
      'Are you sure you want to delete this file?',
      'Yes, delete it!',
      'Cancel'
    );
    
    if (!confirmResult.isConfirmed) {
      return;
    }

    try {
      deleteBtn.disabled = true;
      deleteBtn.textContent = 'Deleting...';
      deleteBtn.classList.add('loading');

      const res = await fetch(`${deleteUrl}?id=${fileId}&type=${currentType}`);
      const result = await res.json();

      if (result.status === 'success') {
        showToast('File deleted successfully!', 'success');
        await loadFiles(); // Refresh file list
      } else {
        showToast(result.message || 'Failed to delete file', 'error');
      }
    } catch (err) {
      console.error('Delete error:', err);
      showToast('Delete failed. Please try again.', 'error');
    } finally {
      deleteBtn.disabled = false;
      deleteBtn.textContent = 'Delete';
      deleteBtn.classList.remove('loading');
    }
  }

  // Remove showAlert function - using showToast directly
});