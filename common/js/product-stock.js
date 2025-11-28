class SupplierProductsAPI {
  async getDetails(params) {
    const query = new URLSearchParams(params).toString();
    const response = await fetch(`../fetch/api/details.php?${query}`);
    
    if (!response.ok) {
      // If it's a server error, return empty data structure
      if (response.status >= 500) {
        return {
          status: "success",
          message: "No data available",
          data: []
        };
      }
      const data = await response.json();
      throw new Error(data.message || 'API request failed');
    }
    
    const data = await response.json();
    return data;
  }
}

const supplierAPI = new SupplierProductsAPI();

// Dropdown states
let productFocus = -1;
let productResults = [];
let supplierFocus = -1;
let supplierResults = [];

// ==========================
// Product Search Functions
// ==========================
async function searchProductAPI() {
  const input = document.getElementById("productInput");
  const query = input?.value.trim();
  if (!query) {
    document.getElementById("productDropdown")?.classList.add("hidden");
    return;
  }

  try {
    const res = await fetch("../fetch/api/search-product.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `search=${encodeURIComponent(query)}`,
    });

    if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);

    const json = await res.json();
    productResults = json.data || [];
    productFocus = -1;
    renderProductList();
  } catch (err) {
    console.error("Product API error:", err);
    productResults = [];
    document.getElementById("productDropdown")?.classList.add("hidden");
  }
}

function renderProductList() {
  const list = document.getElementById("productList");
  if (!list) return;

  list.innerHTML = "";
  productResults.forEach((p, i) => {
    const li = document.createElement("li");
    const a = document.createElement("a");
    a.textContent = p.name;
    a.className = i === productFocus ? "bg-primary text-primary-content" : "";
    a.onclick = () => selectProduct(p.id, p.name);
    li.appendChild(a);
    list.appendChild(li);
  });

  const dropdown = document.getElementById("productDropdown");
  dropdown?.classList.toggle("hidden", productResults.length === 0);
}

function selectProduct(id, name) {
  const input = document.getElementById("productInput");
  const productId = document.getElementById("productId");

  if (input) input.value = name;
  if (productId) productId.value = id;

  document.getElementById("productDropdown")?.classList.add("hidden");
  productFocus = -1;

  // Clear supplier and fetch details
  const supplierInput = document.getElementById("supplierInput");
  const supplierId = document.getElementById("supplierId");
  if (supplierInput) supplierInput.value = '';
  if (supplierId) supplierId.value = '';
  
  if (id) {
    fetchDetails({ product_id: id });
  }
}

function handleProductKeydown(e) {
  const items = document.querySelectorAll("#productList a");
  if (!items.length) return;

  if (e.key === "ArrowDown") {
    e.preventDefault();
    productFocus = (productFocus + 1) % items.length;
    renderProductList();
    items[productFocus].scrollIntoView({ block: "nearest" });
  } else if (e.key === "ArrowUp") {
    e.preventDefault();
    productFocus = productFocus <= 0 ? items.length - 1 : productFocus - 1;
    renderProductList();
    items[productFocus].scrollIntoView({ block: "nearest" });
  } else if (e.key === "Enter") {
    e.preventDefault();
    if (productFocus > -1) {
      const p = productResults[productFocus];
      selectProduct(p.id, p.name);
    }
  } else if (e.key === "Escape") {
    document.getElementById("productDropdown")?.classList.add("hidden");
    productFocus = -1;
  }
}

function showProductDropdown() {
  if (productResults.length)
    document.getElementById("productDropdown")?.classList.remove("hidden");
}

// ==========================
// Supplier Search Functions
// ==========================
async function searchSupplierAPI() {
  const input = document.getElementById("supplierInput");
  const query = input?.value.trim();
  if (!query) {
    document.getElementById("supplierDropdown")?.classList.add("hidden");
    return;
  }

  try {
    const res = await fetch("../fetch/api/search-supplier.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `search=${encodeURIComponent(query)}`,
    });

    if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);

    const json = await res.json();
    supplierResults = json.data || [];
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
    a.onclick = () => selectSupplier(s.id, s.supplier);
    li.appendChild(a);
    list.appendChild(li);
  });

  const dropdown = document.getElementById("supplierDropdown");
  dropdown?.classList.toggle("hidden", supplierResults.length === 0);
}

function selectSupplier(id, name) {
  const input = document.getElementById("supplierInput");
  const supplierId = document.getElementById("supplierId");

  if (input) input.value = name;
  if (supplierId) supplierId.value = id;

  document.getElementById("supplierDropdown")?.classList.add("hidden");
  supplierFocus = -1;

  // Clear product and fetch details
  const productInput = document.getElementById("productInput");
  const productId = document.getElementById("productId");
  if (productInput) productInput.value = '';
  if (productId) productId.value = '';
  
  if (id) {
    fetchDetails({ supplier_id: id });
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
      selectSupplier(s.id, s.supplier);
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
// Details Fetching
// ==========================
async function fetchDetails(params) {
  const results = document.getElementById('results');
  const loading = document.getElementById('loading');
  const noResults = document.getElementById('noResults');
  const errorMessage = document.getElementById('errorMessage');
  const errorText = document.getElementById('errorText');

  // Hide messages and show loading
  if (noResults) noResults.classList.add('hidden');
  if (errorMessage) errorMessage.classList.add('hidden');
  if (results) results.innerHTML = '';
  if (loading) loading.classList.remove('hidden');

  try {
    const response = await supplierAPI.getDetails(params);
    if (loading) loading.classList.add('hidden');

    // Handle empty or null data
    if (!response.data || (Array.isArray(response.data) && response.data.length === 0)) {
      if (noResults) {
        noResults.classList.remove('hidden');
        // Update the message if available
        const noResultsText = noResults.querySelector('p, .text, [class*="text"]');
        if (noResultsText) {
          noResultsText.textContent = 'No data available';
        }
      }
      if (results) results.innerHTML = '';
      return;
    }

    const data = Array.isArray(response.data)
      ? response.data
      : [response.data];

    // Check if data is actually empty after processing
    if (!data || data.length === 0 || (data.length === 1 && !data[0])) {
      if (noResults) {
        noResults.classList.remove('hidden');
        const noResultsText = noResults.querySelector('p, .text, [class*="text"]');
        if (noResultsText) {
          noResultsText.textContent = 'No data available';
        }
      }
      if (results) results.innerHTML = '';
      return;
    }

    displayResults(data);
  } catch (err) {
    console.error('Error fetching details:', err);
    if (loading) loading.classList.add('hidden');
    
    // Show "no data available" instead of error message
    if (noResults) {
      noResults.classList.remove('hidden');
      const noResultsText = noResults.querySelector('p, .text, [class*="text"]');
      if (noResultsText) {
        noResultsText.textContent = 'No data available';
      }
    }
    
    // Hide error message and show no results instead
    if (errorMessage) errorMessage.classList.add('hidden');
    if (results) results.innerHTML = '';
  }
}

function displayResults(data) {
  const results = document.getElementById('results');
  if (!results) return;
  
  results.innerHTML = data.map(item => {
    // Filter out plants with quantity 0
    const validPlants = (item.plants || []).filter(p => parseFloat(p.quantity || 0) > 0);
    const totalQty = validPlants.reduce((a, b) => a + parseFloat(b.quantity || 0), 0);
    
    return `
    <div class="collapse collapse-arrow border border-base-300 bg-base-100">
      <input type="checkbox" class="peer" />
      <div class="collapse-title text-lg font-semibold bg-primary text-primary-content peer-checked:bg-secondary peer-checked:text-secondary-content">
        ${item.product_name || item.name}  <span class="font-semibold">UOM:${item.uom}</span>
      </div>
      <div class="collapse-content">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
          <div><span class="font-semibold">Supplier Name:</span> ${item.supplier_name || 'N/A'}</div>
          <div><span class="font-semibold">Last Purchase Price:</span> ₹${item.lpp || item.last_purchase_price || 'N/A'}</div>
          <div><span class="font-semibold">RSP:</span> ₹${item.rsp || 'N/A'}</div>
          <div><span class="font-semibold">Total Quantity:</span> ${totalQty || 0}</div>
        </div>

        ${(validPlants && validPlants.length > 0) ? `
          <div class="overflow-x-auto">
            <table class="table table-zebra table-sm">
              <thead><tr><th>Plant Name</th><th>Quantity</th></tr></thead>
              <tbody>
                ${validPlants.map(p => `
                  <tr><td>${p.plant_name || 'N/A'}</td><td>${p.quantity}</td></tr>
                `).join('')}
              </tbody>
            </table>
          </div>
        ` : '<p class="text-warning">No stock data available</p>'}
      </div>
    </div>
    `;
  }).join('');
}

// Clear button handler
document.addEventListener('DOMContentLoaded', function() {
  const clearBtn = document.getElementById('clearBtn');
  if (clearBtn) {
    clearBtn.addEventListener('click', () => {
      const productInput = document.getElementById('productInput');
      const productId = document.getElementById('productId');
      const supplierInput = document.getElementById('supplierInput');
      const supplierId = document.getElementById('supplierId');
      const results = document.getElementById('results');
      const noResults = document.getElementById('noResults');
      const errorMessage = document.getElementById('errorMessage');

      if (productInput) productInput.value = '';
      if (productId) productId.value = '';
      if (supplierInput) supplierInput.value = '';
      if (supplierId) supplierId.value = '';
      if (results) results.innerHTML = '';
      if (noResults) noResults.classList.add('hidden');
      if (errorMessage) errorMessage.classList.add('hidden');
      
      document.getElementById("productDropdown")?.classList.add("hidden");
      document.getElementById("supplierDropdown")?.classList.add("hidden");
      productResults = [];
      supplierResults = [];
      productFocus = -1;
      supplierFocus = -1;
    });
  }
});
