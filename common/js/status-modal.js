document.addEventListener("DOMContentLoaded", function () {
  const statusModal = document.getElementById("statusModal");
  const statusSelect = document.getElementById("statusSelect");
  const statusSaveBtn = document.getElementById("statusSaveBtn");
  const statusCancelBtn = document.getElementById("statusCancelBtn");

  // Field containers
  const buyerField = document.getElementById("statusBuyerField");
  const poHeadField = document.getElementById("statusPoHeadField");
  const poTeamField = document.getElementById("statusPoTeamField");
  const qtyField = document.getElementById("statusQtyField");
  const fileUploadField = document.getElementById("statusFileUploadField");
  const remarkField = document.getElementById("statusRemarkField");

  // Input elements
  const buyerInput = document.getElementById("statusBuyerInput");
  const poHeadInput = document.getElementById("statusPoHeadInput");
  const poTeamInput = document.getElementById("statusPoTeamInput");
  const qtyInput = document.getElementById("statusQtyInput");
  const fileInput = document.getElementById("statusFileInput");
  const remarkInput = document.getElementById("statusRemarkInput");

  // Field mapping: database field_name -> DOM element
  const fieldMapping = {
    'buyer': { field: buyerField, input: buyerInput, loader: loadBuyers },
    'po_head': { field: poHeadField, input: poHeadInput, loader: loadPoHeads },
    'po_team': { field: poTeamField, input: poTeamInput, loader: loadPoTeamMembers },
    'qty': { field: qtyField, input: qtyInput, loader: null },
    'file_upload': { field: fileUploadField, input: fileInput, loader: null },
    'remark': { field: remarkField, input: remarkInput, loader: null }
  };

  // Track required fields for current status
  let requiredFields = new Map();

  // Status change handler - Now fetches from database
  async function handleStatusChange(status) {
    console.log("Status changed to:", status);

    // First hide all fields and clear required fields tracking
    hideAllFields();
    requiredFields.clear();

    if (!status) {
      return;
    }

    try {
      // Fetch field configuration from database
      const response = await fetch(`../api/get-status-fields.php?status_id=${status}`);
      const result = await response.json();

      if (result.status === "success" && result.data && result.data.length > 0) {
        // Sort by field_order, but ensure po_head always comes first
        const fields = result.data.sort((a, b) => {
          // PO head always first
          if (a.field_name === 'po_head') return -1;
          if (b.field_name === 'po_head') return 1;
          return a.field_order - b.field_order;
        });
        
        // Show fields based on database configuration
        const fieldsToShow = [];
        const poHeadFieldConfig = fields.find(f => f.field_name === 'po_head');
        
        // If po_head exists, process it first
        if (poHeadFieldConfig && fieldMapping['po_head']) {
          const mappedField = fieldMapping['po_head'];
          fieldsToShow.push(mappedField.field);
          
          if (poHeadFieldConfig.is_required) {
            mappedField.input.setAttribute('required', 'required');
            requiredFields.set('po_head', mappedField);
          } else {
            mappedField.input.removeAttribute('required');
          }
          
          // Load PO heads first
          if (mappedField.loader && typeof mappedField.loader === 'function') {
            mappedField.loader();
          }
        }
        
        // Process other fields
        fields.forEach(fieldConfig => {
          const fieldName = fieldConfig.field_name;
          // Skip po_head as it's already processed
          if (fieldName === 'po_head') return;
          
          if (fieldMapping[fieldName]) {
            const mappedField = fieldMapping[fieldName];
            fieldsToShow.push(mappedField.field);
            
            // Mark as required if needed and track it
            if (fieldConfig.is_required) {
              mappedField.input.setAttribute('required', 'required');
              requiredFields.set(fieldName, mappedField);
            } else {
              mappedField.input.removeAttribute('required');
            }
            
            // Load data if loader function exists
            if (mappedField.loader && typeof mappedField.loader === 'function') {
              mappedField.loader();
            }
          }
        });
        
        showFields(fieldsToShow);
      } else {
        // No fields configured in database - fallback to old behavior
        console.log("No field configuration found, using fallback");
        handleStatusChangeFallback(status);
      }
    } catch (error) {
      console.error("Error fetching status fields:", error);
      // Fallback to old behavior on error
      handleStatusChangeFallback(status);
    }
  }

  // Fallback function for old hardcoded behavior (if database doesn't have config)
  function handleStatusChangeFallback(status) {
    // Clear required fields first
    requiredFields.clear();
    
    switch (status) {
      case "2": // Forwarded to Buyer
        showFields([buyerField, remarkField]);
        buyerInput.setAttribute('required', 'required');
        requiredFields.set('buyer', fieldMapping['buyer']);
        loadBuyers();
        break;

      case "4": // Received Proforma PO
        showFields([qtyField, fileUploadField]);
        break;

      case "5": // Forwarded To B Head
        showFields([remarkField]);
        break;

      case "7": // PO Generated
        // showFields([remarkField]);
        break;

      case "6": // Forwarded to PO Head
        showFields([poHeadField, remarkField, buyerField]);
        loadPoHeads(); // Load PO heads first
        loadBuyers();
        break;

      case "3": // Awaiting PO 
        // Only status select, nothing to show
        break;

      case "8": // Rejected
        showFields([remarkField]);
        break;

      case "9": // Forwarded to PO Team
        showFields([poTeamField, remarkField]);
        poTeamInput.setAttribute('required', 'required');
        requiredFields.set('po_team', fieldMapping['po_team']);
        loadPoTeamMembers();
        break;

      default:
        // Optional: reset or show nothing
        break;
    }
  }

  // Open modal function - Modified to fetch next status first
  window.openStatusModal = async function (prId, currentStatus = "") {
    console.log("Opening modal:", prId, "Current status:", currentStatus);

    // 1️⃣ Always start clean
    resetStatusForm();

    // Get CSRF token
    try {
      const response = await fetch('../auth/get-csrf-token.php');
      const data = await response.json();
      if (data.status === 'success') {
        document.getElementById('status_csrf_token').value = data.data.csrf_token;
      }
    } catch (error) {
      console.error('Failed to get CSRF token:', error);
    }

    // 2️⃣ Disable controls while loading
    statusSaveBtn.disabled = true;
    statusSelect.disabled = true;
    statusSelect.innerHTML = '<option value="">Loading statuses...</option>';

    // Store PR ID
    statusModal.dataset.prId = prId;

    try {
      // 3️⃣ Fetch allowed next statuses
      const response = await fetch(
        `../api/get-status.php?current_status=${currentStatus}&pr_id=${prId}`
      );
      const result = await response.json();

      if (result.status === "success") {
        if (result.data && result.data.length > 0) {
          populateStatusSelect(result.data);
        } else {
          // No statuses available - silently handle
          statusSelect.innerHTML = '<option value="">No status options available</option>';
        }
      } else {
        // Silently handle errors - don't show alerts
        statusSelect.innerHTML = '<option value="">No status options available</option>';
      }
    } catch (error) {
      // Silently handle errors - don't show alerts
      console.error("Error fetching status:", error);
      statusSelect.innerHTML = '<option value="">No status options available</option>';
    } finally {
      // 4️⃣ Re-enable controls
      statusSaveBtn.disabled = false;
      statusSelect.disabled = false;

      // 5️⃣ Ensure all fields are hidden initially - only show when status is selected
      hideAllFields();

      // 6️⃣ Make sure dropdown changes trigger updates
      statusSelect.onchange = function () {
        if (this.value) {
          handleStatusChange(this.value);
        } else {
          hideAllFields();
        }
      };

      // 7️⃣ Show modal
      statusModal.showModal();
    }
  };

  // Populate status select dropdown
  function populateStatusSelect(statuses) {
    statusSelect.innerHTML = '<option value="">Select status</option>';

    const statusOptions = {
      2: "Forwarded to Buyer",
      3: "Awaiting PO",
      4: "Received Proforma PO",
      5: "Forwarded to Buyer Head",
      6: "Forwarded to PO Team",
      7: "PO Generated",
      8: "Rejected",
      9: "Forwarded to PO Members",
    };

    statuses.forEach((status) => {
      const optionText =
        statusOptions[status.id] || status.status || `Status ${status.id}`;
      statusSelect.innerHTML += `<option value="${status.id}">${optionText}</option>`;
    });
  }

  // Get user role from session (you might need to adjust this based on your setup)
  function getUserRole() {
    // This should be set in your HTML or fetched from server
    // Example: <meta name="user-role" content="B_Head">
    const metaTag = document.querySelector('meta[name="user-role"]');
    return metaTag ? metaTag.content : "buyer"; // default fallback
  }

  // Save button handler - Modified to match your PHP API
  statusSaveBtn.addEventListener("click", async function () {
    const prId = statusModal.dataset.prId;
    const status = statusSelect.value;

    if (!status) {
      showAlert("Please select a status", "warning");
      return;
    }

    // Validate required fields before submitting
    const validationErrors = [];
    const fieldLabels = {
      'buyer': 'Buyer',
      'po_head': 'PO Head',
      'po_team': 'PO Team Member',
      'qty': 'Quantity',
      'file_upload': 'File Upload',
      'remark': 'Remark'
    };

    // Debug: Log required fields
    console.log('Required fields to validate:', Array.from(requiredFields.keys()));
    console.log('Current status:', status);

    // Check all required fields
    requiredFields.forEach((mappedField, fieldName) => {
      const input = mappedField.input;
      const fieldContainer = mappedField.field;
      let isEmpty = false;

      // Check if field is visible (not hidden)
      // A field is visible if it doesn't have the "hidden" class
      const isHidden = fieldContainer.classList.contains("hidden");
      
      if (isHidden) {
        return; // Skip hidden fields
      }

      // Additional check: verify the field is actually in the DOM and visible
      if (!input || !fieldContainer || !document.body.contains(input)) {
        return; // Skip if element doesn't exist
      }

      // Validate based on field type
      if (fieldName === 'file_upload') {
        // For file upload, check if files are selected
        isEmpty = !input.files || input.files.length === 0;
      } else if (input.tagName === 'SELECT') {
        // For select elements, check if value is empty (default option has value="")
        // Also check if it's the default "Select option" or empty string
        const selectValue = input.value;
        isEmpty = !selectValue || selectValue === "" || selectValue === "Select option" || selectValue === "Select status";
      } else {
        // For text inputs, textareas, number inputs
        isEmpty = !input.value || (typeof input.value === 'string' && input.value.trim() === "");
      }

      if (isEmpty) {
        const label = fieldLabels[fieldName] || fieldName;
        validationErrors.push(`${label} is required`);
        // Add visual feedback - highlight the field
        input.classList.add('input-error', 'border-error');
        fieldContainer.classList.add('has-error');
        console.log(`Validation failed: ${label} is required but empty`);
      } else {
        // Remove error styling if field is valid
        input.classList.remove('input-error', 'border-error');
        fieldContainer.classList.remove('has-error');
      }
    });
    
    console.log('Validation errors:', validationErrors);

    // If there are validation errors, show them and stop submission
    if (validationErrors.length > 0) {
      showAlert(validationErrors.join(", "), "warning");
      return;
    }

    const formData = new FormData();
    formData.append("ids", prId);
    formData.append("status", status);
    formData.append("status_date", new Date().toISOString().split("T")[0]); // Current date
    
    // Add CSRF token
    const csrfToken = document.getElementById('status_csrf_token').value;
    if (csrfToken) {
      formData.append("csrf_token", csrfToken);
    }

    // Add conditional fields based on visible fields (dynamic from database)
    // Check which fields are visible and add their values
    if (!buyerField.classList.contains("hidden") && buyerInput.value) {
      formData.append("buyerInput", buyerInput.value);
    }
    if (!poHeadField.classList.contains("hidden") && poHeadInput.value) {
      formData.append("poHeadInput", poHeadInput.value);
    }
    if (!poTeamField.classList.contains("hidden") && poTeamInput.value) {
      formData.append("poTeamInput", poTeamInput.value);
    }
    if (!qtyField.classList.contains("hidden") && qtyInput.value) {
      formData.append("qtyInput", qtyInput.value);
    }
    if (!fileUploadField.classList.contains("hidden") && fileInput.files.length > 0) {
      for (let file of fileInput.files) {
        formData.append("files[]", file); // use [] for multiple files
      }
    }
    if (!remarkField.classList.contains("hidden") && remarkInput.value) {
      formData.append("remarkInput", remarkInput.value);
    }

    try {
      statusSaveBtn.disabled = true;
      statusSaveBtn.classList.add("loading");
      statusSaveBtn.textContent = "Updating...";

      const response = await fetch( status == 6 ? "../api/bypass-6.php": status == 9 ? "../api/po-to-po.php" :"../api/update-status.php", {
        method: "POST",
        body: formData,
      });

      const result = await response.json();

      if (result.status === "success") {
        showAlert("Status updated successfully!", "success");
        statusModal.close();
        // Reload page after successful update
        setTimeout(() => {
          window.location.reload();
        }, 1000);
      } else {
        showAlert(result.message || "Failed to update status", "error");
      }
    } catch (error) {
      console.error("Error updating status:", error);
      showAlert("Failed to update status", "error");
    } finally {
      statusSaveBtn.disabled = false;
      statusSaveBtn.classList.remove("loading");
      statusSaveBtn.textContent = "Update";
    }
  });

  // Cancel button handler
  statusCancelBtn.addEventListener("click", function () {
    statusModal.close();
  });

  // Helper functions
 function hideAllFields() {
  const fields = [
    buyerField,
    poHeadField,
    poTeamField,
    qtyField,
    fileUploadField,
    remarkField,
  ];
  fields.forEach((field) => {
    field.classList.remove("form-control", "mb-4");
    field.classList.add("hidden");
  });
}

function showFields(fieldsToShow) {
  fieldsToShow.forEach((field) => {
    field.classList.add("form-control", "mb-4");
    field.classList.remove("hidden");
    // Remove any error styling when showing fields
    const input = field.querySelector('select, input, textarea');
    if (input) {
      input.classList.remove('input-error', 'border-error');
    }
    field.classList.remove('has-error');
  });
}


  function resetStatusForm() {
    statusSelect.value = "";
    buyerInput.value = "";
    poHeadInput.value = "";
    poTeamInput.value = "";
    qtyInput.value = "";
    fileInput.value = "";
    remarkInput.value = "";
    hideAllFields();
    requiredFields.clear();
  }

  function showAlert(message, type = "info") {
    // Use DaisyUI toast notification
    showToast(message, type, 3000);
  }

  function populateSelect(selectElement, options) {
    console.log(options);

    selectElement.innerHTML = '<option value="">Select option</option>';
    options.forEach((opt) => {
      const option = document.createElement("option");
      option.value = opt.value;
      option.textContent = opt.label;
      selectElement.appendChild(option);
    });
  }

  async function loadPoTeamMembers() {
    const poTeamSelect = document.getElementById("statusPoTeamInput");
    
    if (!poTeamSelect) {
      console.error("PO team select element not found");
      return;
    }

    try {
      // Show loading message while fetching
      poTeamSelect.innerHTML =
        '<option value="">Loading team members...</option>';

      // Fetch data from the PHP API
      const response = await fetch("../fetch/fetch-po-team.php");
      const result = await response.json();

      if (result.status === "success" && result.data && Array.isArray(result.data)) {
        // Format the response for populateSelect()
        const options = result.data.map((user) => ({
          value: user.id,
          label: user.fullname || user.username,
        }));

        // Populate the select dropdown
        populateSelect(poTeamSelect, options);
      } else {
        // API returned success=false or empty data
        poTeamSelect.innerHTML =
          '<option value="">No team members found</option>';
      }
    } catch (error) {
      console.error("Error fetching PO team members:", error);
      if (poTeamSelect) {
        poTeamSelect.innerHTML =
          '<option value="">Error loading team members</option>';
      }
    }
  }

  async function loadBuyers() {
    const buyerSelect = document.getElementById("statusBuyerInput");
    
    if (!buyerSelect) {
      console.error("Buyer select element not found");
      return;
    }

    try {
      buyerSelect.innerHTML = '<option value="">Loading buyers...</option>';
      const response = await fetch("../fetch/fetch-buyer.php");
      const result = await response.json();

      if (result.status === "success" && result.data && Array.isArray(result.data)) {
        const options = result.data.map((user) => ({
          value: user.id,
          label: user.username,
        }));
        populateSelect(buyerSelect, options);
      } else {
        buyerSelect.innerHTML = '<option value="">No buyers found</option>';
      }
    } catch (error) {
      console.error("Error fetching buyers:", error);
      if (buyerSelect) {
        buyerSelect.innerHTML = '<option value="">Error loading buyers</option>';
      }
    }
  }

  async function loadPoHeads() {
    const poHeadSelect = document.getElementById("statusPoHeadInput");
    
    if (!poHeadSelect) {
      console.error("PO head select element not found");
      return;
    }

    try {
      // Show loading message while fetching
      poHeadSelect.innerHTML = '<option value="">Loading PO heads...</option>';

      // Fetch data from the PHP API
      const response = await fetch("../fetch/fetch-po-team-heads.php");
      const result = await response.json();

      if (result.status === "success" && result.data && Array.isArray(result.data)) {
        // Format the response for populateSelect()
        const options = result.data.map((user) => ({
          value: user.id,
          label: user.username,
        }));

        // Populate the select dropdown
        populateSelect(poHeadSelect, options);
      } else {
        // API returned success=false or empty data
        poHeadSelect.innerHTML = '<option value="">No PO heads found</option>';
      }
    } catch (error) {
      console.error("Error fetching PO heads:", error);
      if (poHeadSelect) {
        poHeadSelect.innerHTML = '<option value="">Error loading PO heads</option>';
      }
    }
  }
});
