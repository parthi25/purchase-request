// DaisyUI Notification System
// Replaces SweetAlert with DaisyUI components

// Prevent re-execution if already loaded
if (typeof window.showToast === 'undefined') {
// Toast notification container
let toastContainer = null;

function initToastContainer() {
  if (!toastContainer) {
    const existing = document.getElementById('daisy-toast-container');
    if (existing) {
      toastContainer = existing;
    } else {
      toastContainer = document.createElement('div');
      toastContainer.className = 'toast toast-top toast-end';
      toastContainer.id = 'daisy-toast-container';
      // Use extremely high z-index to appear above modals (DaisyUI modals typically use ~9999)
      toastContainer.style.cssText = 'z-index: 99999999 !important; position: fixed !important; top: 1rem !important; right: 1rem !important; pointer-events: none !important;';
      document.body.appendChild(toastContainer);
    }
  }
  // Ensure toast container is always at the end of body to maintain stacking order above modals
  // Only move if it's not already a direct child of body or if there are modals open
  const hasOpenModal = document.querySelector('dialog[open]');
  if (toastContainer.parentNode !== document.body || (hasOpenModal && toastContainer !== document.body.lastElementChild)) {
    document.body.appendChild(toastContainer);
  }
  return toastContainer;
}

// Show toast notification
function showToast(message, type = 'info', duration = 3000) {
  initToastContainer();
  
  // Always move container to end of body when showing toast to ensure it's above any open modals
  // This maintains proper stacking order
  document.body.appendChild(toastContainer);
  
  const alertTypes = {
    success: 'alert-success',
    error: 'alert-error',
    warning: 'alert-warning',
    info: 'alert-info'
  };
  
  const icons = {
    success: '✓',
    error: '✕',
    warning: '⚠',
    info: 'ℹ'
  };
  
  const toast = document.createElement('div');
  toast.className = `alert ${alertTypes[type] || 'alert-info'} shadow-lg mb-2 animate-in slide-in-from-top`;
  // Ensure toast appears above everything including modals
  toast.style.cssText = 'z-index: 99999999 !important; position: relative !important; pointer-events: auto !important; min-width: 300px !important; max-width: 500px !important;';
  toast.innerHTML = `
    <div class="flex items-center gap-2">
      <span class="text-lg">${icons[type] || 'ℹ'}</span>
      <span>${message}</span>
    </div>
  `;
  
  toastContainer.appendChild(toast);
  
  // Auto remove after duration
  setTimeout(() => {
    toast.classList.add('animate-out', 'slide-out-to-top');
    setTimeout(() => {
      toast.remove();
    }, 300);
  }, duration);
  
  return toast;
}

// Show confirm dialog using DaisyUI modal
function showConfirm(title, text, confirmText = 'Yes', cancelText = 'Cancel') {
  return new Promise((resolve) => {
    // Create modal
    const modalId = 'daisy-confirm-modal';
    let modal = document.getElementById(modalId);
    
    if (!modal) {
      modal = document.createElement('dialog');
      modal.id = modalId;
      modal.className = 'modal';
      modal.style.cssText = 'z-index: 999999 !important; position: fixed !important;';
      document.body.appendChild(modal);
    }
    
    modal.innerHTML = `
      <div class="modal-box" style="z-index: 999999 !important;">
        <h3 class="font-bold text-lg mb-4">${title}</h3>
        <p class="mb-6">${text}</p>
        <div class="modal-action">
          <form method="dialog">
            <button type="button" class="btn btn-ghost cancel-btn">${cancelText}</button>
            <button type="button" class="btn btn-primary confirm-btn ml-2">${confirmText}</button>
          </form>
        </div>
      </div>
      <form method="dialog" class="modal-backdrop" style="z-index: 999998 !important;">
        <button type="button" class="cancel-btn">close</button>
      </form>
    `;
    
    // Force z-index after setting innerHTML
    setTimeout(() => {
      modal.style.cssText = 'z-index: 999999 !important; position: fixed !important;';
      const modalBox = modal.querySelector('.modal-box');
      if (modalBox) {
        modalBox.style.cssText = 'z-index: 999999 !important;';
      }
    }, 10);
    
    // Add event listeners
    const confirmBtn = modal.querySelector('.confirm-btn');
    const cancelBtn = modal.querySelector('.cancel-btn');
    
    const handleConfirm = (e) => {
      e.preventDefault();
      modal.close();
      resolve({ isConfirmed: true });
    };
    
    const handleCancel = (e) => {
      e.preventDefault();
      modal.close();
      resolve({ isConfirmed: false });
    };
    
    // Remove old listeners and add new ones
    confirmBtn.replaceWith(confirmBtn.cloneNode(true));
    cancelBtn.replaceWith(cancelBtn.cloneNode(true));
    
    const newConfirmBtn = modal.querySelector('.confirm-btn');
    const newCancelBtn = modal.querySelector('.cancel-btn');
    
    newConfirmBtn.addEventListener('click', handleConfirm);
    newCancelBtn.addEventListener('click', handleCancel);
    
    // Show modal
    modal.showModal();
  });
}

// Show alert dialog (modal)
function showAlertModal(title, text, type = 'info') {
  return new Promise((resolve) => {
    const modalId = 'daisy-alert-modal';
    let modal = document.getElementById(modalId);
    
    const alertTypes = {
      success: 'alert-success',
      error: 'alert-error',
      warning: 'alert-warning',
      info: 'alert-info'
    };
    
    if (!modal) {
      modal = document.createElement('dialog');
      modal.id = modalId;
      modal.className = 'modal';
      modal.style.cssText = 'z-index: 999999 !important; position: fixed !important;';
      document.body.appendChild(modal);
    }
    
    modal.innerHTML = `
      <div class="modal-box" style="z-index: 999999 !important;">
        <div class="alert ${alertTypes[type] || 'alert-info'} mb-4">
          <h3 class="font-bold text-lg">${title}</h3>
          <p>${text}</p>
        </div>
        <div class="modal-action">
          <form method="dialog">
            <button type="button" class="btn btn-primary ok-btn">OK</button>
          </form>
        </div>
      </div>
      <form method="dialog" class="modal-backdrop" style="z-index: 999998 !important;">
        <button type="button" class="ok-btn">close</button>
      </form>
    `;
    
    // Force z-index after setting innerHTML
    setTimeout(() => {
      modal.style.cssText = 'z-index: 999999 !important; position: fixed !important;';
      const modalBox = modal.querySelector('.modal-box');
      if (modalBox) {
        modalBox.style.cssText = 'z-index: 999999 !important;';
      }
    }, 10);
    
    const okBtn = modal.querySelector('.ok-btn');
    const handleOk = (e) => {
      e.preventDefault();
      modal.close();
      resolve();
    };
    
    okBtn.replaceWith(okBtn.cloneNode(true));
    const newOkBtn = modal.querySelector('.ok-btn');
    newOkBtn.addEventListener('click', handleOk);
    
    modal.showModal();
  });
}

// Export functions
window.showToast = showToast;
window.showConfirm = showConfirm;
window.showAlertModal = showAlertModal;

// Alias for backward compatibility - showAlert is for toasts
window.showAlert = showToast;

} // End of if check for preventing re-execution

