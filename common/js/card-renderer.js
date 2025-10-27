// === Full Card Configurations ===
// if (typeof CardConfigs === 'undefined') {
const CardConfigs = {
    buyer: {
        role: 'buyer',
        showFields: { refId:true, poNumber:true, poTeam:true, supplier:true, category:true, purchType:false, qty:true, createdBy:true, createdOn:true, remarks:true },
        showButtons: { edit:true, proforma:true, po:true },
        statusBadges: {
            "1": '<span class="text-sm font-semibold text-green-600">Open</span>',
            "2": '<span class="text-sm font-semibold text-blue-600">Forwarded to Buyer</span>',
            "3": '<span class="text-sm font-semibold text-yellow-600">Agent/Supplier contacted and Awaiting PO details</span>',
            "4": '<span class="text-sm font-semibold text-indigo-600">Received Proforma PO</span>',
            "5": '<span class="text-sm font-semibold text-red-600">Forwarded to Buyer Head</span>',
            "6": '<span class="text-sm font-semibold text-gray-600">Forwarded to PO Team</span>',
            "7": '<span class="text-sm font-semibold text-green-600">PO generated</span>',
            "8": '<span class="text-sm font-semibold text-red-600">Rejected</span>',
            "9": '<span class="text-sm font-semibold text-green-600">Forwarded to PO Members</span>'
        }
    },
    admin: {
        role: 'admin',
        showFields: { refId:true, poNumber:true, poTeam:true, supplier:true, category:true, purchType:true, qty:true, createdBy:true, createdOn:true, remarks:true },
        showButtons: { edit:true, proforma:true, po:true },
        statusBadges: {
            "1": '<span class="text-sm font-semibold text-green-600">Open</span>',
            "2": '<span class="text-sm font-semibold text-blue-600">Forwarded to Buyer</span>',
            "3": '<span class="text-sm font-semibold text-yellow-600">Awaiting PO</span>',
            "4": '<span class="text-sm font-semibold text-indigo-600">Received Proforma PO</span>',
            "5": '<span class="text-sm font-semibold text-red-600">Forwarded to Buyer Head</span>',
            "6": '<span class="text-sm font-semibold text-gray-600">Forwarded to PO Team</span>',
            "7": '<span class="text-sm font-semibold text-green-600">PO generated</span>',
            "8": '<span class="text-sm font-semibold text-red-600">Rejected</span>',
            "9": '<span class="text-sm font-semibold text-green-600">Forwarded to PO Members</span>'
        }
    },
    bhead: {
        role: 'bhead',
        showFields: { refId:true, poNumber:true, poTeam:true, supplier:true, category:true, purchType:false, qty:true, createdBy:true, createdOn:true, remarks:true },
        showButtons: { edit:true, proforma:true, po:true },
        statusBadges: {
            "1": '<span class="text-sm font-semibold text-green-600">Open</span>',
            "2": '<span class="text-sm font-semibold text-blue-600">Forwarded to Buyer</span>',
            "3": '<span class="text-sm font-semibold text-yellow-600">Agent/Supplier contacted and Awaiting PO details</span>',
            "4": '<span class="text-sm font-semibold text-indigo-600">Received Proforma PO</span>',
            "5": '<span class="text-sm font-semibold text-red-600">Forwarded to Buyer Head</span>',
            "6": '<span class="text-sm font-semibold text-gray-600">Forwarded to PO Team</span>',
            "7": '<span class="text-sm font-semibold text-green-600">PO generated</span>',
            "8": '<span class="text-sm font-semibold text-red-600">Rejected</span>',
            "9": '<span class="text-sm font-semibold text-green-600">Forwarded to PO Members</span>'
        }
    },
    pohead: {
        role: 'pohead',
        showFields: { refId:true, poNumber:true, poTeam:true, supplier:true, category:true, purchType:true, qty:true, createdBy:true, createdOn:true, remarks:true },
        showButtons: { edit:false, proforma:true, po:true },
        statusBadges: {
            "1": '<span class="text-sm font-semibold text-green-600">Open</span>',
            "2": '<span class="text-sm font-semibold text-blue-600">Forwarded to Buyer</span>',
            "3": '<span class="text-sm font-semibold text-yellow-600">Awaiting PO</span>',
            "4": '<span class="text-sm font-semibold text-indigo-600">Received Proforma PO</span>',
            "5": '<span class="text-sm font-semibold text-red-600">Forwarded to Buyer Head</span>',
            "6": '<span class="text-sm font-semibold text-gray-600">Forwarded to PO Team</span>',
            "7": '<span class="text-sm font-semibold text-green-600">PO generated</span>',
            "8": '<span class="text-sm font-semibold text-red-600">Rejected</span>',
            "9": '<span class="text-sm font-semibold text-green-600">Forwarded to PO Members</span>'
        }
    },
    poteammember: {
        role: 'poteammember',
        showFields: { refId:true, poNumber:true, poTeam:false, supplier:true, category:true, purchType:true, qty:true, createdBy:true, createdOn:true, remarks:true },
        showButtons: { edit:false, proforma:true, po:true },
        statusBadges: {
            "1": '<span class="text-sm font-semibold text-green-600">Open</span>',
            "2": '<span class="text-sm font-semibold text-blue-600">Forwarded to Buyer</span>',
            "3": '<span class="text-sm font-semibold text-yellow-600">Awaiting PO</span>',
            "4": '<span class="text-sm font-semibold text-indigo-600">Received Proforma PO</span>',
            "5": '<span class="text-sm font-semibold text-red-600">Forwarded to Buyer Head</span>',
            "6": '<span class="text-sm font-semibold text-gray-600">Forwarded to PO Team</span>',
            "7": '<span class="text-sm font-semibold text-green-600">PO generated</span>',
            "8": '<span class="text-sm font-semibold text-red-600">Rejected</span>',
            "9": '<span class="text-sm font-semibold text-green-600">Forwarded to PO Members</span>'
        }
    },
    dashboard: {
        role: 'dashboard',
        showFields: { refId:true, poNumber:true, poTeam:true, supplier:true, category:true, purchType:true, qty:true, createdBy:true, createdOn:true, remarks:false },
        showButtons: { edit:false, proforma:true, po:true },
        statusBadges: {
            "1": '<span class="text-sm font-semibold text-green-600">Open</span>',
            "2": '<span class="text-sm font-semibold text-blue-600">Forwarded to Buyer</span>',
            "3": '<span class="text-sm font-semibold text-yellow-600">Awaiting PO</span>',
            "4": '<span class="text-sm font-semibold text-indigo-600">Received Proforma PO</span>',
            "5": '<span class="text-sm font-semibold text-red-600">Forwarded to Buyer Head</span>',
            "6": '<span class="text-sm font-semibold text-gray-600">Forwarded to PO Team</span>',
            "7": '<span class="text-sm font-semibold text-green-600">PO generated</span>',
            "8": '<span class="text-sm font-semibold text-red-600">Rejected</span>',
            "9": '<span class="text-sm font-semibold text-green-600">Forwarded to PO Members</span>'
        }
    }
};
// }
// Helper
function getCardConfig(role) {
    return CardConfigs[role] || CardConfigs.buyer;
}

// === Render Cards in DaisyUI Style ===
function renderCards(dataArray, role = 'buyer', containerId = 'cardContainer') {
    const config = getCardConfig(role);
    const container = document.getElementById(containerId);
    if (!container) return console.error(`Container with ID '${containerId}' not found.`);

    // Clear container only if it's the first load (offset === 0)
    if (!window.state || window.state.offset === 0) {
        container.innerHTML = '';
    }

    dataArray.forEach(item => {
        const statusBadge = config.statusBadges?.[String(item.po_status)] || '';

        // Map API fields to card renderer expected fields
        const mappedItem = {
            refId: item.id,
            poNumber: item.po_number || '-',
            poTeam: item.po_team,
            supplier: item.supplier,
            category: item.category_name,
            purchType: item.purch_type,
            qty: item.qty,
            createdBy: item.created_by,
            createdOn: item.created_at ? new Date(item.created_at).toLocaleDateString() : '-',
            remarks: item.remark || '-',
            buyerHead: item.b_head,
            proforma : item.proforma_ids[0],
            po_url : item.po_url,
            product : item.images[0],
        };

        let fieldsHtml = '';
        Object.keys(config.showFields).forEach(key => {
            if (config.showFields[key] && mappedItem[key] !== undefined) {
                const label = key
                    .replace(/([A-Z])/g, ' $1')
                    .replace(/^./, str => str.toUpperCase());
                fieldsHtml += `
                    <div class="flex">
                        <span class="font-semibold w-24">${label}:</span>
                        <span>${mappedItem[key]}</span>
                    </div>
                `;
            }
        });

        let buttonsHtml = '';
        buttonsHtml += `<button class="btn btn-sm btn-outline read-more-toggle" data-id='${mappedItem.refId}'>remarks</button>`;
        if (config.showButtons.edit && item.po_status === 1) buttonsHtml += `<button class="btn btn-sm btn-outline openEditPRBtn" data-pr-id='${mappedItem.refId}'>Edit</button>`;
        
        if (config.showButtons.proforma) {
    const hasProforma = item.proforma_ids && item.proforma_ids[0] ? true : false;
    buttonsHtml += `
        <button class="btn btn-sm btn-primary proforma" data-pr-id='${mappedItem.refId}' data-status-id='${item.po_status}' data-role='${config.role}'>
            Proforma
            ${hasProforma ? `<span class="text-success">&#10003;</span>` : ''}
        </button>
    `;
}

// PO button
if (config.showButtons.po && item.po_status === 7) {
    const hasPO = item.po_url ? true : false;
    buttonsHtml += `
        <button class="btn btn-sm btn-secondary po" data-pr-id='${mappedItem.refId}' data-status-id='${item.po_status}' data-role='${config.role}'>
            PO
            ${hasPO ? `<span class="text-success">&#10003;</span>` : ''}
        </button>
    `;
}
        if(config.role === 'admin' ? [1].includes(item.po_status) :
           config.role === 'bhead' ? [1, 5].includes(item.po_status) :
           config.role === 'buyer' ? [2, 3, 4].includes(item.po_status) :
           config.role === 'pohead' ? [6].includes(item.po_status) :
           false) buttonsHtml += `<button class="btn btn-sm btn-outline update-status" data-id='${mappedItem.refId}' data-status='${item.po_status}'>--></button>`;
        if (config.role === 'poteammember' && item.po_status === 9) buttonsHtml += `<button class="btn btn-sm btn-info insert-po" data-id='${mappedItem.refId}'>Insert PO</button>`;

        const cardHtml = `
            <div class="card max-w-xs min-w-90 bg-base-100 shadow-md border border-gray-200 mb-4 opacity-0 transition-opacity duration-500">
                <div class="card-body">
                    <div class="avatar absolute translate-x-[220px] translate-y-[30px] product" data-pr-id='${mappedItem.refId}' data-status-id='${item.po_status}' data-role='${config.role}'>
                        <div class="w-24 rounded flex justify-center items-center">
                            <img src="../${mappedItem.product}" alt="product" onerror="this.onerror=null; this.src='../assets/brand/no-image.png';"/>
                        </div>
                    </div>
                    <!-- Header -->
                    <div class="mb-4">
                        <h2 class="text-lg font-bold mb-2 truncate">Buyer Head: ${mappedItem.buyerHead}</h2>
                        ${statusBadge}
                    </div>

                    <!-- Content -->
                    <div class="space-y-2 text-sm">${fieldsHtml}</div>

                    <!-- Divider -->
                    <div class="divider my-4"></div>

                    <!-- Footer Actions -->
                    <div class="flex justify-between items-center gap-1">
                    ${buttonsHtml}
                    </div>
                </div>
            </div>
        `;

        container.insertAdjacentHTML('beforeend', cardHtml);

        // Attach event listener to the edit button
        const editBtn = container.lastElementChild.querySelector('.openEditPRBtn');
        if (editBtn) {
            editBtn.addEventListener('click', () => {
                const prId = editBtn.dataset.prId;
                if (typeof openPRModal === 'function') {
                    openPRModal(prId);
                }
            });
        }

        // Set up Intersection Observer for scroll animation
        if (!window.cardObserver) {
            window.cardObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('opacity-100');
                    }
                });
            }, { threshold: 0 });
        }
        window.cardObserver.observe(container.lastElementChild);
    });
}
