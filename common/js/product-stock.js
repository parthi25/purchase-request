class SupplierProductsAPI {
  async getSuppliers() {
    return await this.fetchData('../fetch/api/suppliers.php');
  }

  async getProducts() {
    return await this.fetchData('../fetch/api/products.php');
  }

  async getDetails(params) {
    const query = new URLSearchParams(params).toString();
    return await this.fetchData(`../fetch/api/details.php?${query}`);
  }

  async fetchData(url) {
    const response = await fetch(url);
    const data = await response.json();
    if (!response.ok) throw new Error(data.message || 'API request failed');
    return data;
  }
}

const supplierAPI = new SupplierProductsAPI();

document.addEventListener('DOMContentLoaded', async () => {
  const productSelect = document.getElementById('productSelect');
  const supplierSelect = document.getElementById('supplierSelect');
  const clearBtn = document.getElementById('clearBtn');
  const results = document.getElementById('results');
  const loading = document.getElementById('loading');
  const noResults = document.getElementById('noResults');
  const errorMessage = document.getElementById('errorMessage');
  const errorText = document.getElementById('errorText');

  // init
  await loadDropdowns();

  async function loadDropdowns() {
    try {
      const [suppliers, products] = await Promise.all([
        supplierAPI.getSuppliers(),
        supplierAPI.getProducts(),
      ]);

      productSelect.innerHTML = '<option value="" disabled selected>Select Product</option>';
      products.data.forEach(p => {
        const opt = document.createElement('option');
        opt.value = p.id;
        opt.textContent = p.name;
        productSelect.appendChild(opt);
      });

      supplierSelect.innerHTML = '<option value="" disabled selected>Select Supplier</option>';
      suppliers.data.forEach(s => {
        const opt = document.createElement('option');
        opt.value = s.id;
        opt.textContent = `${s.supplier_name} (${s.supplier_id})`;
        supplierSelect.appendChild(opt);
      });
    } catch (err) {
      showError('Failed to load dropdown data.');
    }
  }

  async function fetchDetails(params) {
    hideMessages();
    showLoading();

    try {
      const response = await supplierAPI.getDetails(params);
      hideLoading();

      const data = Array.isArray(response.data)
        ? response.data
        : [response.data];

      if (!data || data.length === 0) {
        showNoResults();
      } else {
        displayResults(data);
      }
    } catch (err) {
      hideLoading();
      showError('Failed to fetch details.');
    }
  }

  function displayResults(data) {
    results.innerHTML = data.map(item => `
      <div class="collapse collapse-arrow border border-base-300 bg-base-100">
        <input type="checkbox" class="peer" />
        <div class="collapse-title text-lg font-semibold bg-primary text-primary-content peer-checked:bg-secondary peer-checked:text-secondary-content">
          ${item.product_name || item.name}  <span class="font-semibold">UOM:${item.uom}</span>
        </div>
        <div class="collapse-content">
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
            <div><span class="font-semibold">Supplier Code:</span> ${item.supplier_code || 'N/A'}</div>
            <div><span class="font-semibold">Last Purchase Price:</span> ₹${item.lpp || item.last_purchase_price || 'N/A'}</div>
            <div><span class="font-semibold">RSP:</span> ₹${item.rsp || 'N/A'}</div>
            <div><span class="font-semibold">Total Quantity:</span> ${item.total_qty || item.plants?.reduce((a,b)=>a+parseFloat(b.quantity||0),0) || 0}</div>
          </div>

          ${(item.plants && item.plants.length > 0) ? `
            <div class="overflow-x-auto">
              <table class="table table-zebra table-sm">
                <thead><tr><th>Plant Name</th><th>Quantity</th></tr></thead>
                <tbody>
                  ${item.plants.map(p => `
                    <tr><td>${p.plant_name || 'N/A'}</td><td>${p.quantity}</td></tr>
                  `).join('')}
                </tbody>
              </table>
            </div>
          ` : '<p class="text-warning">No stock data available</p>'}
        </div>
      </div>
    `).join('');
  }

  function showLoading() { loading.classList.remove('hidden'); }
  function hideLoading() { loading.classList.add('hidden'); }
  function showNoResults() { noResults.classList.remove('hidden'); }
  function showError(msg) {
    errorText.textContent = msg;
    errorMessage.classList.remove('hidden');
  }
  function hideMessages() {
    noResults.classList.add('hidden');
    errorMessage.classList.add('hidden');
    results.innerHTML = '';
  }

  // Event Listeners
  productSelect.addEventListener('change', () => {
    supplierSelect.value = '';
    fetchDetails({ product_id: productSelect.value });
  });

  supplierSelect.addEventListener('change', () => {
    productSelect.value = '';
    fetchDetails({ supplier_id: supplierSelect.value });
  });

  clearBtn.addEventListener('click', () => {
    productSelect.value = '';
    supplierSelect.value = '';
    results.innerHTML = '';
    hideMessages();
  });
});
