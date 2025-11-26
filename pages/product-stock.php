<?php include '../common/layout.php'; ?>
  <div class="max-w-6xl mx-auto">

    <!-- Header -->
    <div class="text-center mb-8">
      <h1 class="text-4xl font-bold text-primary mb-2">Supplier & Product Dashboard</h1>
      <p class="text-base-content">Select a supplier or product to view details.</p>
    </div>

    <!-- Selection Form -->
    <div class="card bg-base-100 shadow-xl mb-8">
      <div class="card-body space-y-4">
        <h2 class="card-title text-2xl capitalize">Select an Option</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Product -->
          <div class="form-control relative">
            <label class="label">
              <span class="label-text font-semibold">Select Product</span>
            </label>
            <div class="relative">
              <input type="text" class="input input-bordered w-full pr-10" id="productInput" 
                autocomplete="off" placeholder="Type to search products..." 
                oninput="searchProductAPI()" onfocus="showProductDropdown()" 
                onkeydown="handleProductKeydown(event)">
              <div id="productDropdown" class="absolute top-full left-0 right-0 z-10 mt-1 hidden">
                <ul class="menu bg-base-200 rounded-box shadow-lg max-h-60 overflow-y-auto" id="productList"></ul>
              </div>
              <input type="hidden" id="productId">
            </div>
          </div>

          <!-- Supplier -->
          <div class="form-control relative">
            <label class="label">
              <span class="label-text font-semibold">Select Supplier</span>
            </label>
            <div class="relative">
              <input type="text" class="input input-bordered w-full pr-10" id="supplierInput" 
                autocomplete="off" placeholder="Type to search suppliers..." 
                oninput="searchSupplierAPI()" onfocus="showSupplierDropdown()" 
                onkeydown="handleSupplierKeydown(event)">
              <div id="supplierDropdown" class="absolute top-full left-0 right-0 z-10 mt-1 hidden">
                <ul class="menu bg-base-200 rounded-box shadow-lg max-h-60 overflow-y-auto" id="supplierList"></ul>
              </div>
              <input type="hidden" id="supplierId">
            </div>
          </div>
        </div>

        <div class="card-actions justify-end">
          <button id="clearBtn" class="btn btn-outline btn-error">Clear Selection</button>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div id="loading" class="text-center hidden">
      <span class="loading loading-spinner loading-lg text-primary"></span>
      <p class="mt-2 text-base-content">Loading data...</p>
    </div>

    <!-- Results -->
    <div id="results" class="space-y-4"></div>

    <!-- No Results -->
    <div id="noResults" class="hidden text-center py-8">
      <div class="alert alert-info max-w-md mx-auto">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <span>No data found. Try a different selection.</span>
      </div>
    </div>

    <!-- Error -->
    <div id="errorMessage" class="hidden text-center py-8">
      <div class="alert alert-error max-w-md mx-auto">
        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none"
          viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span id="errorText">An error occurred.</span>
      </div>
    </div>

  </div>
    <script src="../common/js/product-stock.js"></script>
<?php include '../common/layout-footer.php'; ?>
