<dialog id="poInsertModal" class="modal">
    <div class="modal-box max-w-lg">
      <h3 class="font-bold text-lg mb-4">Insert PO</h3>

      <form method="dialog" id="poInsertForm" class="space-y-3">
        <input type="hidden" id="po-selectedRecordId">

        <div id="po-ponum">
          <label class="label">
            <span class="label-text">Enter PO Number <span class="text-error">*</span></span>
          </label>
          <input type="text" id="po-PO" name="PO" maxlength="10" class="input input-bordered w-full" required autocomplete="off">
        </div>

        <div>
          <label class="label">
            <span class="label-text">PO Qty</span>
          </label>
          <input type="text" id="po-qty" name="qty" class="input input-bordered w-full" readonly>
        </div>

        <div>
          <label class="label">
            <span class="label-text">PO Lines/items</span>
          </label>
          <input type="text" id="po-Lines" name="Lines" class="input input-bordered w-full" readonly>
        </div>

        <div>
          <label class="label">
            <span class="label-text">Inv Amount</span>
          </label>
          <input type="text" id="po-inv_am" name="inv_am" class="input input-bordered w-full" readonly>
        </div>

        <div>
          <label class="label">
            <span class="label-text">Supplier</span>
          </label>
          <input type="text" id="po-supplier" name="supplier" class="input input-bordered w-full" readonly>
          <input type="hidden" id="po-supplier_code" name="supplier_code">
          <input type="hidden" id="po-sapsupplier_code" name="sapsupplier_code">
        </div>

        <div>
          <label class="label">
            <span class="label-text">Buyer</span>
          </label>
          <input type="text" id="po-Buyer" name="Buyer" class="input input-bordered w-full" readonly>
        </div>

        <div>
          <label class="label">
            <span class="label-text">PO Date</span>
          </label>
          <input type="text" id="po-PODate" name="PODate" class="input input-bordered w-full" readonly>
        </div>

        <div class="modal-action">
          <button type="button" class="btn" onclick="closePoInsertModal()">Close</button>
          <button id="insertPO" type="button" class="btn btn-primary">Insert</button>
        </div>
      </form>
    </div>
  </dialog>
