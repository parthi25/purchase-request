document.addEventListener('DOMContentLoaded', function() {
  const poInsertModal = document.getElementById('poInsertModal');
  const poInsertForm = document.getElementById('poInsertForm');
  const poInput = document.getElementById('po-PO');
  const insertPOBtn = document.getElementById('insertPO');

  // Function to open the modal
  window.openPoInsertModal = function(recordId) {
    // Reset form
    poInsertForm.reset();
    document.getElementById('po-selectedRecordId').value = recordId;
    poInsertModal.showModal();
  };

  // Function to close the modal
  window.closePoInsertModal = function() {
    poInsertModal.close();
  };

  // Fetch PO data when PO number is entered
  poInput.addEventListener('blur', async function() {
    const poNumber = poInput.value.trim();
    if (!poNumber) return;

    try {
      const response = await fetch('../fetch/fetch-sap-po.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
          PoNumber: poNumber
        })
      });

      const result = await response.json();

      if (result.status === 'success') {
        // Updated to match the new response structure
        const data = result.data.data.raw_data.d.results[0];
        
        // Calculate total quantity and lines
        const totalQty = data.navHeaderToItem.results.reduce((sum, item) => {
          return sum + parseFloat(item.quan || 0);
        }, 0);
        
        const totalLines = data.navHeaderToItem.results.length;
        
        // Calculate total invoice amount
        const totalAmount = data.navHeaderToItem.results.reduce((sum, item) => {
          return sum + parseFloat(item.netAmount || 0);
        }, 0);

        // Populate fields with fetched data
        document.getElementById('po-qty').value = totalQty || '';
        document.getElementById('po-Lines').value = totalLines || '';
        document.getElementById('po-inv_am').value = totalAmount || '';
        document.getElementById('po-supplier').value = data.supplierName || '';
        document.getElementById('po-supplier_code').value = data.supplierCode || '';
        document.getElementById('po-sapsupplier_code').value = data.supplierCode || '';
        document.getElementById('po-Buyer').value = data.buyerName || '';
        
        // Format PO date from creationDate (YYYYMMDD format)
        let poDate = '';
        if (data.creationDate) {
          const year = data.creationDate.substring(0, 4);
          const month = data.creationDate.substring(4, 6);
          const day = data.creationDate.substring(6, 8);
          poDate = `${year}-${month}-${day}`;
        }
        document.getElementById('po-PODate').value = poDate;
        
        // Optional: Show success message
        console.log('PO data loaded successfully:', {
          poNumber: data.Po,
          supplier: data.supplierName,
          totalAmount: totalAmount,
          totalLines: totalLines,
          totalQty: totalQty
        });
        
      } else {
        showAlert(result.message || 'Failed to fetch PO data', 'error');
      }
    } catch (error) {
      console.error('Error fetching PO data:', error);
      showAlert('Failed to fetch PO data', 'error');
    }
  });

  // Submit the form
  insertPOBtn.addEventListener('click', async function() {
    const formData = new FormData(poInsertForm);
    const recordId = document.getElementById('po-selectedRecordId').value;

    formData.append('ids', recordId);
    formData.append('statusSelect', '9'); // Assuming status for PO insertion
    formData.append('poTeamInput', ''); // Add logic if needed
    formData.append('buyerInput', document.getElementById('po-Buyer').value);

    try {
      insertPOBtn.disabled = true;
      insertPOBtn.textContent = 'Inserting...';

      const response = await fetch('../api/po-insert.php', {
        method: 'POST',
        body: formData
      });

      const result = await response.json();

      if (result.status === 'success') {
        showAlert('PO inserted successfully!', 'success');
        poInsertModal.close();
        // Refresh the view if needed
        if (window.ViewMode && typeof ViewMode.refreshView === 'function') {
          ViewMode.refreshView();
        }
      } else {
        showAlert(result.message || 'Failed to insert PO', 'error');
      }
    } catch (error) {
      console.error('Error inserting PO:', error);
      showAlert('Failed to insert PO', 'error');
    } finally {
      insertPOBtn.disabled = false;
      insertPOBtn.textContent = 'Insert';
    }
  });

  function showAlert(message, type = 'info') {
    // Use DaisyUI toast notification
    showToast(message, type, 3000);
  }
});
