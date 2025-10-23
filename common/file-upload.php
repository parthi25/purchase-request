
<!-- File Viewer / Upload Modal -->
<dialog id="fileModal" class="modal">
  <div class="modal-box max-w-2xl">
    <!-- Close Button -->
    <form method="dialog">
      <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
    </form>
    
    <h3 id="fileModalTitle" class="font-bold text-lg mb-4"></h3>

    <!-- File List -->
    <div id="fileList" class="space-y-2 mb-4 max-h-60 overflow-y-auto">
      <p class="text-sm text-gray-500">No files found.</p>
    </div>

    <!-- Upload Form -->
    <div class="space-y-2">
      <input type="file" id="fileInput" class="file-input file-input-bordered w-full" />
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