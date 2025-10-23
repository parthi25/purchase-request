
document.addEventListener('click', async (e) => {
  if (e.target.classList.contains('read-more-toggle') || e.target.closest('.read-more-toggle')) {
    const btn = e.target.classList.contains('read-more-toggle') ? e.target : e.target.closest('.read-more-toggle');
    const id = btn.dataset.id;
    if (!id) return;

    try {
      const res = await fetch(`../fetch/fetch-remark.php?id=${id}`);
      const data = await res.json();

      if (data.status === "success") {
        const r = data.data;
        const content = `
          <div class="flex items-start gap-2">
            <i class="fas fa-comment text-primary mt-1"></i>
            <div><strong>PR Remark:</strong> ${r.remark || '-'}</div>
          </div>
          <div class="flex items-start gap-2">
            <i class="fas fa-comment text-primary mt-1"></i>
            <div><strong>Remark To Buyer:</strong> ${r.b_remark || '-'}</div>
          </div>
          <div class="flex items-start gap-2">
            <i class="fas fa-comment text-primary mt-1"></i>
            <div><strong>Remark To B Head:</strong> ${r.to_bh_rm || '-'}</div>
          </div>
          <div class="flex items-start gap-2">
            <i class="fas fa-comment text-primary mt-1"></i>
            <div><strong>Remark To PO Head:</strong> ${r.po_team_rm || '-'}</div>
          </div>
          <div class="flex items-start gap-2">
            <i class="fas fa-comment text-primary mt-1"></i>
            <div><strong>Remark To PO Team:</strong> ${r.rrm || '-'}</div>
          </div>
        `;

        document.getElementById('readMoreModalBody').innerHTML = content;
        document.getElementById('readMoreModal').showModal();
      } else {
        // Using SweetAlert2 for better alerts (since you have it included)
        Swal.fire({
          icon: 'info',
          title: 'No remarks found',
          text: 'No remarks found for this record',
          timer: 2000
        });
      }
    } catch (err) {
      console.error(err);
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'Failed to fetch remarks'
      });
    }
  }
});