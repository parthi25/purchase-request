<dialog id="statusModal" class="modal">
  <div class="modal-box w-96 max-h-[90vh] overflow-y-auto">
    <!-- Close Button -->
    <form method="dialog">
      <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
    </form>
    
    <h3 id="statusModalTitle" class="font-bold text-lg mb-4">Update Status</h3>
    
    <form id="statusForm" enctype="multipart/form-data">
      <input type="hidden" id="statusPrId" name="prId">
      <input type="hidden" id="statusCurrentStatus" name="currentStatus">
      <input type="hidden" name="csrf_token" id="status_csrf_token" value="">
    
    <!-- Status Selection -->
    <div class="form-control mb-4">
      <label class="label">
        <span class="label-text">Status</span>
      </label>
      <select id="statusSelect" class="select select-bordered w-full">
        <option value="">Select status</option>
      </select>
    </div>

<!-- Buyer Field (Shown for status 2) -->
<div id="statusBuyerField" class="hidden">
  <label class="label">
    <span class="label-text">Buyer</span>
  </label>
  <select id="statusBuyerInput" class="select select-bordered w-full">
  </select>
</div>

<!-- PO Head Field (Shown for status 6) -->
<div id="statusPoHeadField" class="hidden">
  <label class="label">
    <span class="label-text">PO Head</span>
  </label>
  <select id="statusPoHeadInput" class="select select-bordered w-full">
  </select>
</div>

<!-- PO Team Member Field (Shown for status 9) -->
<div id="statusPoTeamField" class="hidden">
  <label class="label">
    <span class="label-text">PO Team Member</span>
  </label>
  <select id="statusPoTeamInput" class="select select-bordered w-full">
  </select>
</div>


    <!-- Quantity Field (Shown for status 3) -->
    <div id="statusQtyField" class="hidden">
      <label class="label">
        <span class="label-text">Quantity</span>
      </label>
      <input type="number" id="statusQtyInput" placeholder="Enter quantity" class="input input-bordered w-full" min="1" />
    </div>

    <!-- File Upload (Shown for status 3) -->
    <div id="statusFileUploadField" class="hidden">
      <label class="label">
        <span class="label-text">Upload Product Images</span>
      </label>
      <input type="file" id="statusFileInput" name="files[]" accept="image/*" multiple class="file-input file-input-bordered w-full" />
    </div>

    <!-- Remark Field (Shown for status 3, 4, 5, 6, 9) -->
    <div id="statusRemarkField" class="hidden">
      <label class="label">
        <span class="label-text">Remark</span>
      </label>
      <textarea id="statusRemarkInput" placeholder="Add remarks..." class="textarea textarea-bordered w-full h-24"></textarea>
    </div>

    <div class="modal-action">
      <button type="button" class="btn btn-ghost" id="statusCancelBtn">Cancel</button>
      <button type="button" class="btn btn-primary" id="statusSaveBtn">Update</button>
    </div>
    </form>
  </div>
  
  <!-- Modal Backdrop -->
  <form method="dialog" class="modal-backdrop">
    <button>close</button>
  </form>
</dialog>