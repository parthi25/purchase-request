// ==========================
// Create PR Modal JS (Safe & API Driven)
// ==========================

// Dropdown states
let supplierFocus = -1;
let supplierResults = [];

let categoryFocus = -1;
let categoryResults = [];

let currentPRId = null; // null = create mode, number = edit mode

// ==========================
// Fetch Purchase Types when modal opens
// ==========================
async function fetchPurchaseTypes() {
    const select = document.getElementById("purchInput");
    if (!select) return;

    try {
        const res = await fetch("../fetch/api/fetch-purchtype.php");
        if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
        const json = await res.json();
        const data = json.data || [];

        select.innerHTML = "";
        data.forEach(type => {
            const opt = document.createElement("option");
            opt.value = type.id;
            opt.textContent = type.text;
            select.appendChild(opt);
        });

        if (!data.length) {
            const opt = document.createElement("option");
            opt.textContent = "No purchase types available";
            opt.disabled = true;
            select.appendChild(opt);
        }
    } catch (err) {
        console.error("Purchase Type API error:", err);
    }
}

// ==========================
// Open Modal (Create/Edit)
// ==========================
async function openPRModal(prId = null) {
    currentPRId = prId;
    
    // Get CSRF token
    try {
        const response = await fetch('../auth/get-csrf-token.php');
        const data = await response.json();
        if (data.status === 'success') {
            document.getElementById('csrf_token').value = data.data.csrf_token;
        }
    } catch (error) {
        console.error('Failed to get CSRF token:', error);
    }
    resetForm();
    await fetchPurchaseTypes();

    // Update modal title and submit button based on mode
    const modalTitle = document.querySelector('#create_modal .modal-box h3');
    const submitBtn = document.querySelector('#CreatePRForm button[type="submit"]');
    if (prId) {
        // Edit mode
        if (modalTitle) modalTitle.textContent = 'Update PR';
        if (submitBtn) submitBtn.textContent = 'Update';
    } else {
        // Create mode
        if (modalTitle) modalTitle.textContent = 'Create PR';
        if (submitBtn) submitBtn.textContent = 'Create PR';
    }

    if (prId) {
        // Edit mode → fetch data
        try {
            const res = await fetch(`../fetch/api/get-pr.php?id=${prId}`);
            const json = await res.json();

            if (json.status !== "success") {
                return alert("Error fetching PR: " + json.message);
            }

            const data = json.data;

            // Fill form fields
            document.getElementById("supplierInput").value = data.supplier || "";
            document.getElementById("supplierId").value = data.supplier_id || "";
            document.getElementById("agentInput").value = data.agent || "";
            document.getElementById("cityInput").value = data.city || "";

            document.getElementById("categoryInput").value = data.category || "";
            document.getElementById("categoryId").value = data.category_id || "";
            document.getElementById("buyerHeadInput").value = data.bhead_name || "";
            document.getElementById("buyerId").value = data.bhead_id || "";

            document.getElementById("qtyInput").value = data.qty || 1;
            document.getElementById("uomInput").value = data.uom || "Pcs";
            document.getElementById("remarkInput").value = data.remark || "";
            document.getElementById("purchInput").value = data.purch_id || "";

            // Show NEW SUPPLIER container if needed
            const newSupplierContainer = document.getElementById("newSupplierContainer");
            if (data.supplier === "NEW SUPPLIER" && newSupplierContainer) {
                newSupplierContainer.classList.remove("hidden");
                document.getElementById("agentInput").readOnly = false;
                document.getElementById("cityInput").readOnly = false;
            }

        } catch (err) {
            console.error(err);
            alert("Failed to fetch PR data");
        }
    }

    // Show modal
    document.getElementById("create_modal")?.showModal();
}

// ==========================
// Form Submission (Create or Update)
// ==========================
const form = document.getElementById("CreatePRForm");
if (form) {
    form.addEventListener("submit", async function (e) {
        e.preventDefault();
        const formData = new FormData(this);

        if (!formData.get('supplierId') || !formData.get('categoryId')) {
            return alert('Please fill all required fields');
        }

        let url = '../api/create-pr.php';
        if (currentPRId) {
            url = '../api/update-pr.php';
            formData.append('id', currentPRId);
        }

        try {
            const res = await fetch(url, { method: 'POST', body: formData });
            const json = await res.json();
            console.log('PR Response:', json);

            if (json.status === 'success') {
                alert((currentPRId ? 'PR updated' : 'PR created') + ' successfully (ID: ' + json.data.po_id + ')');
                document.getElementById('create_modal')?.close();
                currentPRId = null;
                // Reload page after successful create/update
                setTimeout(() => {
                  window.location.reload();
                }, 1000);
            } else {
                alert('Error: ' + json.message);
            }
        } catch (err) {
            console.error(err);
            alert('Network or server error');
        }
    });
}

// ==========================
// Reset function
// ==========================
function resetForm() {
    supplierFocus = categoryFocus = -1;
    document.getElementById("supplierDropdown")?.classList.add("hidden");
    document.getElementById("categoryDropdown")?.classList.add("hidden");
    document.getElementById("newSupplierContainer")?.classList.add("hidden");
    form.reset();
}

// ==========================
// Supplier Search + Handling
// ==========================
async function searchSupplierAPI() {
  const input = document.getElementById("supplierInput");
  const query = input?.value.trim();
  if (!query) {
    document.getElementById("supplierDropdown")?.classList.add("hidden");
    return;
  }

  try {
   await  fetchPurchaseTypes();

    const res = await fetch("../fetch/api/search-supplier.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `search=${encodeURIComponent(query)}`,
    });

    if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);

    const json = await res.json();
    supplierResults = json.data || [];

    // Add "NEW SUPPLIER" as a selectable option at the bottom
    // supplierResults.push({ id: "new", supplier: "NEW SUPPLIER", agent: "", city: "" });

    supplierFocus = -1;
    renderSupplierList();
  } catch (err) {
    console.error("Supplier API error:", err);
    supplierResults = [];
    document.getElementById("supplierDropdown")?.classList.add("hidden");
  }
}

function renderSupplierList() {
  const list = document.getElementById("supplierList");
  if (!list) return;

  list.innerHTML = "";
  supplierResults.forEach((s, i) => {
    const li = document.createElement("li");
    const a = document.createElement("a");
    a.textContent = s.supplier;
    a.className = i === supplierFocus ? "bg-primary text-primary-content" : "";
    a.onclick = () => selectSupplier(s.id, s.supplier, s.agent, s.city);
    li.appendChild(a);
    list.appendChild(li);
  });

  const dropdown = document.getElementById("supplierDropdown");
  dropdown?.classList.toggle("hidden", supplierResults.length === 0);
}

function selectSupplier(id, name, agent, city) {
  const input = document.getElementById("supplierInput");
  const supplierId = document.getElementById("supplierId");
  const agentInput = document.getElementById("agentInput");
  const cityInput = document.getElementById("cityInput");

  if (input) input.value = name;
  if (supplierId) supplierId.value = id;
  if (agentInput) agentInput.value = agent || "";
  if (cityInput) cityInput.value = city || "";

  document.getElementById("supplierDropdown")?.classList.add("hidden");
  supplierFocus = -1;

  // Handle NEW SUPPLIER logic
  const newSupplierField = document.getElementById("newSupplierContainer");
  if (name === "NEW SUPPLIER" && newSupplierField) {
    newSupplierField.classList.add("form-control");
    agentInput.readOnly = false;
    cityInput.readOnly = false;
  } else if (newSupplierField) {
    newSupplierField.classList.remove("form-control");
    agentInput.readOnly = true;
    cityInput.readOnly = true;
  }
}

function handleSupplierKeydown(e) {
  const items = document.querySelectorAll("#supplierList a");
  if (!items.length) return;

  if (e.key === "ArrowDown") {
    e.preventDefault();
    supplierFocus = (supplierFocus + 1) % items.length;
    renderSupplierList();
    items[supplierFocus].scrollIntoView({ block: "nearest" });
  } else if (e.key === "ArrowUp") {
    e.preventDefault();
    supplierFocus = supplierFocus <= 0 ? items.length - 1 : supplierFocus - 1;
    renderSupplierList();
    items[supplierFocus].scrollIntoView({ block: "nearest" });
  } else if (e.key === "Enter") {
    e.preventDefault();
    if (supplierFocus > -1) {
      const s = supplierResults[supplierFocus];
      selectSupplier(s.id, s.supplier, s.agent, s.city);
    }
  } else if (e.key === "Escape") {
    document.getElementById("supplierDropdown")?.classList.add("hidden");
    supplierFocus = -1;
  }
}

function showSupplierDropdown() {
  if (supplierResults.length)
    document.getElementById("supplierDropdown")?.classList.remove("hidden");
}

// ==========================
// Category Functions
// ==========================
async function searchCategoryAPI() {
  const input = document.getElementById("categoryInput");
  const query = input?.value.trim();
  if (!query) {
    document.getElementById("categoryDropdown")?.classList.add("hidden");
    return;
  }

  try {
    const res = await fetch(`../fetch/api/search-categories.php`, {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `search=${encodeURIComponent(query)}`,
    });

    if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);

    const json = await res.json();
    categoryResults = json.data || [];
    categoryFocus = -1;
    renderCategoryList();
  } catch (err) {
    console.error("Category API error:", err);
    categoryResults = [];
    document.getElementById("categoryDropdown")?.classList.add("hidden");
  }
}

function renderCategoryList() {
  const list = document.getElementById("categoryList");
  if (!list) return;

  list.innerHTML = "";
  categoryResults.forEach((c, i) => {
    const li = document.createElement("li");
    const a = document.createElement("a");
    a.textContent = c.cat;
    a.className = i === categoryFocus ? "bg-primary text-primary-content" : "";
    a.onclick = () => selectCategory(c.user_id, c.cat, c.buyer_name);
    li.appendChild(a);
    list.appendChild(li);
  });

  const dropdown = document.getElementById("categoryDropdown");
  dropdown?.classList.toggle("hidden", categoryResults.length === 0);
}

function selectCategory(id, cat, buyerName) {
  const input = document.getElementById("categoryInput");
  const categoryId = document.getElementById("categoryId");
  const buyerInput = document.getElementById("buyerHeadInput");
  const buyerId = document.getElementById("buyerId");

  if (input) input.value = cat;
  if (categoryId) categoryId.value = id;
  if (buyerInput) buyerInput.value = buyerName;
  if (buyerId) buyerId.value = id; // store user_id if needed

  document.getElementById("categoryDropdown")?.classList.add("hidden");
  categoryFocus = -1;
}

function handleCategoryKeydown(e) {
  const items = document.querySelectorAll("#categoryList a");
  if (!items.length) return;

  if (e.key === "ArrowDown") {
    e.preventDefault();
    categoryFocus = (categoryFocus + 1) % items.length;
    renderCategoryList();
    items[categoryFocus].scrollIntoView({ block: "nearest" });
  } else if (e.key === "ArrowUp") {
    e.preventDefault();
    categoryFocus = categoryFocus <= 0 ? items.length - 1 : categoryFocus - 1;
    renderCategoryList();
    items[categoryFocus].scrollIntoView({ block: "nearest" });
  } else if (e.key === "Enter") {
    e.preventDefault();
    if (categoryFocus > -1) {
      const c = categoryResults[categoryFocus];
      selectCategory(c.user_id, c.cat, c.buyer_name);
    }
  } else if (e.key === "Escape") {
    document.getElementById("categoryDropdown")?.classList.add("hidden");
    categoryFocus = -1;
  }
}

function showCategoryDropdown() {
  if (categoryResults.length)
    document.getElementById("categoryDropdown")?.classList.remove("hidden");
}

// ==========================
// Outside click close
// ==========================
document.addEventListener("click", function (e) {
  const supplierInput = document.getElementById("supplierInput");
  const supplierDropdown = document.getElementById("supplierDropdown");
  if (
    supplierInput &&
    supplierDropdown &&
    !supplierInput.contains(e.target) &&
    !supplierDropdown.contains(e.target)
  ) {
    supplierDropdown.classList.add("hidden");
  }

  const categoryInput = document.getElementById("categoryInput");
  const categoryDropdown = document.getElementById("categoryDropdown");
  if (
    categoryInput &&
    categoryDropdown &&
    !categoryInput.contains(e.target) &&
    !categoryDropdown.contains(e.target)
  ) {
    categoryDropdown.classList.add("hidden");
  }
});
