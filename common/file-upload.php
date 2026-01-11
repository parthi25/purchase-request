
<!-- File Viewer / Upload Modal -->
<dialog id="fileModal" class="modal">
  <div class="modal-box max-w-2xl">
    <!-- Close Button -->
    <form method="dialog">
      <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
    </form>
    
    <h3 id="fileModalTitle" class="font-bold text-lg mb-4"></h3>

    <!-- File List / Image Carousel -->
    <div id="fileList" class="mb-4 flex flex-col items-center">
      <p class="text-sm text-gray-500">No files found.</p>
    </div>

    <!-- Upload Form -->
    <div class="space-y-2">
      <input type="file" id="fileInput" class="file-input file-input-bordered w-full" />
      
      <!-- New Item Upload and Info (only for proforma) -->
      <!-- TEMPORARILY HIDDEN - Will be unhidden in future -->
      <!--
      <div id="newItemFields" class="hidden space-y-2">
        <div class="divider my-2">New Item Details</div>
        <div>
          <label class="label">
            <span class="label-text">New Item Upload</span>
          </label>
          <input type="file" id="itemDetailsFileInput" class="file-input file-input-bordered w-full" />
          <label class="label">
            <span class="label-text-alt text-gray-500">Upload item details file (optional)</span>
          </label>
        </div>
        <div>
          <label class="label">
            <span class="label-text">New Item Info</span>
          </label>
          <textarea id="itemInfoInput" class="textarea textarea-bordered w-full" rows="3" placeholder="Item Code: ABC123, Name: Product Name, Price: $100, Type: New Item"></textarea>
          <label class="label">
            <span class="label-text-alt text-gray-500">Enter item information (optional)</span>
          </label>
        </div>
      </div>
      -->
      
      <button id="uploadFileBtn" class="btn btn-primary w-full">Upload New File</button>
    </div>

    <div class="modal-action">
      <form method="dialog">
        <button class="btn">Close</button>
      </form>
    </div>
  </div>
  
  <!-- Modal Backdrop -->
  <form method="dialog" class="modal-backdrop">
    <button>close</button>
  </form>
</dialog>