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

  // Status change handler
  function handleStatusChange(status) {
    console.log(status);

    // First hide all fields
    hideAllFields();

    switch (status) {
      case "2": // Forwarded to Buyer
        showFields([buyerField, remarkField]);
        loadBuyers();
        break;

      case "4": // Received Proforma PO
        showFields([qtyField, fileUploadField]);
        break;

      case "5": // Forword To B Head
        showFields([remarkField]);
        break;

      case "7": // PO Generated
        // showFields([remarkField]);
        break;

      case "6": // Forwarded to PO Head
        showFields([poHeadField, remarkField, buyerField]);
        loadBuyers();
        break;

      case "3": // Awaiting PO 
        // Only status select, nothing to show
        break;

      case "8": // Rejected
        showFields([remarkField]);
        break;

      case "9": // Forwarded to PO Team
        showFields([ poTeamField, remarkField]);
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

    const formData = new FormData();
    formData.append("ids", prId);
    formData.append("status", status);
    formData.append("status_date", new Date().toISOString().split("T")[0]); // Current date
    
    // Add CSRF token
    const csrfToken = document.getElementById('status_csrf_token').value;
    if (csrfToken) {
      formData.append("csrf_token", csrfToken);
    }

    // Add conditional fields based on status
   switch (status) {
  case "2": // Forwarded to Buyer
    if (buyerInput.value) formData.append("buyerInput", buyerInput.value);
    if (remarkInput.value) formData.append("remarkInput", remarkInput.value);
    break;

  case "4": // Awaiting PO
    if (qtyInput.value) formData.append("qtyInput", qtyInput.value);
    if (fileInput.files.length > 0) {
      for (let file of fileInput.files) {
        formData.append("files[]", file); // use [] for multiple files
      }
    }
    break;

  case "5": 
    if (remarkInput.value) formData.append("remarkInput", remarkInput.value);
    break;
  case "7": // Forwarded to Buyer Head
  case "8": // Rejected
    if (remarkInput.value) formData.append("remarkInput", remarkInput.value);
    break;

  case "6": // Forwarded to PO Team
    if (poHeadInput.value) formData.append("poHeadInput", poHeadInput.value);
    if (buyerInput.value) formData.append("buyerInput", buyerInput.value);
    if (remarkInput.value) formData.append("remarkInput", remarkInput.value);
    break;

  case "9": // Forwarded to PO Members
    if (poTeamInput.value) formData.append("poTeamInput", poTeamInput.value);
    if (remarkInput.value) formData.append("remarkInput", remarkInput.value);
    break;
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
  }

  function showAlert(message, type = "info") {
    if (typeof Swal !== "undefined") {
      Swal.fire({
        icon: type,
        title: message,
        toast: true,
        position: "top-end",
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
      });
    } else {
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          icon: 'info',
          title: message,
          toast: true,
          position: "top-end",
          showConfirmButton: false,
          timer: 3000,
          timerProgressBar: true,
        });
      }
    }
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

    try {
      // Show loading message while fetching
      poTeamSelect.innerHTML =
        '<option value="">Loading team members...</option>';

      // Fetch data from the PHP API
      const response = await fetch("../fetch/fetch-po-team.php");
      const result = await response.json();

      if (result.status === "success") {
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
      poTeamSelect.innerHTML =
        '<option value="">Error loading team members</option>';
    }
  }

  async function loadBuyers() {
    const buyerSelect = document.getElementById("statusBuyerInput");

    try {
      buyerSelect.innerHTML = '<option value="">Loading buyers...</option>';
      const response = await fetch("../fetch/fetch-buyer.php");
      const result = await response.json();

      if (result.status === "success") {
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
      buyerSelect.innerHTML = '<option value="">Error loading buyers</option>';
    }
  }
});
