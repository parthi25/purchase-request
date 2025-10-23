<form id="CreatePRForm" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <input type="hidden" name="csrf_token" id="csrf_token" value="">

    <!-- Supplier Name -->
    <div class="form-control relative">
        <label class="label"><span class="label-text">Supplier Name <span class="text-error">*</span></span></label>
        <div class="relative">
            <input type="text" class="input input-bordered w-full pr-10" id="supplierInput" name="supplierInput"
                required autocomplete="off" placeholder="Type to search suppliers..." oninput="searchSupplierAPI()"
                onfocus="showSupplierDropdown()" onkeydown="handleSupplierKeydown(event)">
            <div id="supplierDropdown" class="absolute top-full left-0 right-0 z-10 mt-1 hidden">
                <ul class="menu bg-base-200 rounded-box shadow-lg max-h-60 overflow-y-auto" id="supplierList"></ul>
            </div>
            <input type="hidden" id="supplierId" name="supplierId">
        </div>
    </div>

    <!-- New Supplier (hidden by default) -->
    <div class="hidden" id="newSupplierContainer">
        <label class="label"><span class="label-text">New Supplier Name</span></label>
        <input type="text" class="input input-bordered w-full" id="newSupplierInput" name="newSupplierInput"
            placeholder="Enter new supplier name">
    </div>

    <!-- Agent Name -->
    <div class="form-control">
        <label class="label"><span class="label-text">Agent Name</span></label>
        <input type="text" class="input input-bordered w-full" id="agentInput" readonly name="agentInput">
    </div>

    <!-- Agent City -->
    <div class="form-control">
        <label class="label"><span class="label-text">Agent City</span></label>
        <input type="text" class="input input-bordered w-full" id="cityInput" readonly name="cityInput">
    </div>

    <!-- Purchases Type -->
    <div class="form-control">
        <label class="label"><span class="label-text">Purchases Type</span></label>
        <select class="select select-bordered w-full" id="purchInput" name="purchInput">

        </select>
    </div>

    <!-- Category -->
    <div class="form-control relative">
        <label class="label"><span class="label-text">Category <span class="text-error">*</span></span></label>
        <input type="text" class="input input-bordered w-full" id="categoryInput" name="categoryInput" required
            autocomplete="off" placeholder="Type or select category..." oninput="searchCategoryAPI()"
            onfocus="showCategoryDropdown()" onkeydown="handleCategoryKeydown(event)">
        <div id="categoryDropdown" class="absolute top-full left-0 right-0 z-10 mt-1 hidden">
            <ul class="menu bg-base-200 rounded-box shadow-lg max-h-60 overflow-y-auto" id="categoryList"></ul>
        </div>
        <input type="hidden" id="categoryId" name="categoryId">
    </div>

    <!-- Buyer -->
    <div class="form-control">
        <label class="label"><span class="label-text">Buyer Head <span class="text-error">*</span></span></label>
        <input type="text" class="input input-bordered w-full" id="buyerHeadInput" readonly name="buyerInput">
        <input type="hidden" id="buyerId" name="buyerId">
    </div>

    <!-- Quantity -->
    <div class="form-control">
        <label class="label"><span class="label-text">Quantity <span class="text-error">*</span></span></label>
        <input type="number" class="input input-bordered w-full" id="qtyInput" name="qtyInput" required min="1"
            value="1">
    </div>

    <!-- Unit of Measure -->
    <div class="form-control">
        <label class="label"><span class="label-text">Unit of Measure</span></label>
        <select class="select select-bordered w-full" id="uomInput" name="uomInput">
            <option value="Box">Box</option>
            <option value="Bundle">Bundle</option>
            <option value="Bunch">Bunch</option>
            <option value="Kilogram">Kilogram</option>
            <option value="Meter">Meter</option>
            <option value="Pairs">Pairs</option>
            <option value="Pcs" selected>Pcs</option>
            <option value="Pocket">Pocket</option>
        </select>
    </div>

    <!-- Remark -->
    <div class="form-control">
        <label class="label"><span class="label-text">Remark</span></label>
        <input type="text" class="input input-bordered w-full" id="remarkInput" name="remarkInput"
            placeholder="Add remarks...">
    </div>

    <!-- File Upload -->
    <div class="form-control">
        <label class="label"><span class="label-text">Upload Product Images</span></label>
        <input type="file" id="fileInput" name="files[]" accept="image/*" multiple
            class="file-input file-input-bordered w-full" />
    </div>

    <!-- Action Buttons -->
    <div class="form-control col-span-1 md:col-span-3 mt-4">
        <div class="flex justify-end gap-2">
            <button type="submit" class="btn btn-primary">Create PR</button>
            <button type="reset" class="btn btn-error" onclick="resetForm()">Clear Form</button>
        </div>
    </div>

</form>