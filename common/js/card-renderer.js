// Helper function to convert text to title case (first letter capitalized)
function toTitleCase(str) {
    if (!str || typeof str !== 'string') return str || '';
    return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
}

// === Full Card Configurations ===
// if (typeof CardConfigs === 'undefined') {
const CardConfigs = {
    buyer: {
        role: 'buyer',
        showFields: { refId:true, poNumber:true, poHead:true, poTeam:true, supplier:true, category:true, purchType:false, qty:true, createdBy:true, createdOn:true, remarks:false },
        showButtons: { edit:true, proforma:true, po:true },
        statusBadges: {
            "1": '<span class="text-sm font-semibold text-green-600 capitalize">Open</span>',
            "2": '<span class="text-sm font-semibold text-blue-600 capitalize">Forwarded to Buyer</span>',
            "3": '<span class="text-sm font-semibold text-yellow-600 truncate capitalize">Agent/Supplier contacted and Awaiting PO details</span>',
            "4": '<span class="text-sm font-semibold text-indigo-600 capitalize">Received Proforma PO</span>',
            "5": '<span class="text-sm font-semibold text-red-600 capitalize">Forwarded to Buyer Head</span>',
            "6": '<span class="text-sm font-semibold text-gray-600 capitalize">Forwarded to PO Team</span>',
            "7": '<span class="text-sm font-semibold text-green-600 capitalize">PO generated</span>',
            "8": '<span class="text-sm font-semibold text-red-600 capitalize">Rejected</span>',
            "9": '<span class="text-sm font-semibold text-green-600 capitalize">Forwarded to PO Members</span>'
        }
    },
    admin: {
        role: 'admin',
        showFields: { refId:true, poNumber:true, poHead:true, poTeam:true, supplier:true, category:true, purchType:true, qty:true, createdBy:true, createdOn:true, remarks:false },
        showButtons: { edit:true, proforma:true, po:true },
        statusBadges: {
            "1": '<span class="text-sm font-semibold text-green-600 capitalize">Open</span>',
            "2": '<span class="text-sm font-semibold text-blue-600 capitalize">Forwarded to Buyer</span>',
            "3": '<span class="text-sm font-semibold text-yellow-600 capitalize">Awaiting PO</span>',
            "4": '<span class="text-sm font-semibold text-indigo-600 capitalize">Received Proforma PO</span>',
            "5": '<span class="text-sm font-semibold text-red-600 capitalize">Forwarded to Buyer Head</span>',
            "6": '<span class="text-sm font-semibold text-gray-600 capitalize">Forwarded to PO Team</span>',
            "7": '<span class="text-sm font-semibold text-green-600 capitalize">PO generated</span>',
            "8": '<span class="text-sm font-semibold text-red-600 capitalize">Rejected</span>',
            "9": '<span class="text-sm font-semibold text-green-600 capitalize">Forwarded to PO Members</span>'
        }
    },
    bhead: {
        role: 'bhead',
        showFields: { refId:true, poNumber:true, poHead:true, poTeam:true, supplier:true, category:true, purchType:false, qty:true, createdBy:true, createdOn:true, remarks:false },
        showButtons: { edit:true, proforma:true, po:true },
        statusBadges: {
            "1": '<span class="text-sm font-semibold text-green-600 capitalize">Open</span>',
            "2": '<span class="text-sm font-semibold text-blue-600 capitalize">Forwarded to Buyer</span>',
            "3": '<span class="text-sm font-semibold text-yellow-600 truncate capitalize">Agent/Supplier contacted and Awaiting PO details</span>',
            "4": '<span class="text-sm font-semibold text-indigo-600 capitalize">Received Proforma PO</span>',
            "5": '<span class="text-sm font-semibold text-red-600 capitalize">Forwarded to Buyer Head</span>',
            "6": '<span class="text-sm font-semibold text-gray-600 capitalize">Forwarded to PO Team</span>',
            "7": '<span class="text-sm font-semibold text-green-600 capitalize">PO generated</span>',
            "8": '<span class="text-sm font-semibold text-red-600 capitalize">Rejected</span>',
            "9": '<span class="text-sm font-semibold text-green-600 capitalize">Forwarded to PO Members</span>'
        }
    },
    pohead: {
        role: 'pohead',
        showFields: { refId:true, poNumber:true, poHead:true, poTeam:true, supplier:true, category:true, purchType:true, qty:true, createdBy:true, createdOn:true, remarks:false },
        showButtons: { edit:false, proforma:true, po:true },
        statusBadges: {
            "1": '<span class="text-sm font-semibold text-green-600 capitalize">Open</span>',
            "2": '<span class="text-sm font-semibold text-blue-600 capitalize">Forwarded to Buyer</span>',
            "3": '<span class="text-sm font-semibold text-yellow-600 capitalize">Awaiting PO</span>',
            "4": '<span class="text-sm font-semibold text-indigo-600 capitalize">Received Proforma PO</span>',
            "5": '<span class="text-sm font-semibold text-red-600 capitalize">Forwarded to Buyer Head</span>',
            "6": '<span class="text-sm font-semibold text-gray-600 capitalize">Forwarded to PO Team</span>',
            "7": '<span class="text-sm font-semibold text-green-600 capitalize">PO generated</span>',
            "8": '<span class="text-sm font-semibold text-red-600 capitalize">Rejected</span>',
            "9": '<span class="text-sm font-semibold text-green-600 capitalize">Forwarded to PO Members</span>'
        }
    },
    poteammember: {
        role: 'poteammember',
        showFields: { refId:true, poNumber:true, poHead:true, poTeam:true, supplier:true, category:true, purchType:true, qty:true, createdBy:true, createdOn:true, remarks:false },
        showButtons: { edit:false, proforma:true, po:true },
        statusBadges: {
            "1": '<span class="text-sm font-semibold text-green-600 capitalize">Open</span>',
            "2": '<span class="text-sm font-semibold text-blue-600 capitalize">Forwarded to Buyer</span>',
            "3": '<span class="text-sm font-semibold text-yellow-600 capitalize">Awaiting PO</span>',
            "4": '<span class="text-sm font-semibold text-indigo-600 capitalize">Received Proforma PO</span>',
            "5": '<span class="text-sm font-semibold text-red-600 capitalize">Forwarded to Buyer Head</span>',
            "6": '<span class="text-sm font-semibold text-gray-600 capitalize">Forwarded to PO Team</span>',
            "7": '<span class="text-sm font-semibold text-green-600 capitalize">PO generated</span>',
            "8": '<span class="text-sm font-semibold text-red-600 capitalize">Rejected</span>',
            "9": '<span class="text-sm font-semibold text-green-600 capitalize">Forwarded to PO Members</span>'
        }
    },
    dashboard: {
        role: 'dashboard',
        showFields: { refId:true, poNumber:true, poHead:true, poTeam:true, supplier:true, category:true, purchType:true, qty:true, createdBy:true, createdOn:true, remarks:false },
        showButtons: { edit:false, proforma:true, po:true },
        statusBadges: {
            "1": '<span class="text-sm font-semibold text-green-600 capitalize">Open</span>',
            "2": '<span class="text-sm font-semibold text-blue-600 capitalize">Forwarded to Buyer</span>',
            "3": '<span class="text-sm font-semibold text-yellow-600 capitalize">Awaiting PO</span>',
            "4": '<span class="text-sm font-semibold text-indigo-600 capitalize">Received Proforma PO</span>',
            "5": '<span class="text-sm font-semibold text-red-600 capitalize">Forwarded to Buyer Head</span>',
            "6": '<span class="text-sm font-semibold text-gray-600 capitalize">Forwarded to PO Team</span>',
            "7": '<span class="text-sm font-semibold text-green-600 capitalize">PO generated</span>',
            "8": '<span class="text-sm font-semibold text-red-600 capitalize">Rejected</span>',
            "9": '<span class="text-sm font-semibold text-green-600 capitalize">Forwarded to PO Members</span>'
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
        // Use database statuses if available, otherwise fallback to config
        let statusBadge = '';
        if (window.StatusBadges) {
            statusBadge = window.StatusBadges.getBadge(item.po_status, 'card');
        } else {
            statusBadge = config.statusBadges?.[String(item.po_status)] || '';
        }

        // Map API fields to card renderer expected fields
        const mappedItem = {
            refId: item.id,
            poNumber: item.po_number || '-',
            poHead: item.po_team || '-',
            poTeam: item.po_team_member || '-',
            supplier: item.supplier,
            category: item.category_name,
            purchType: item.purch_type,
            qty: item.qty ? `${item.qty}${item.uom ? ' ' + item.uom : ''}` : '-',
            createdBy: item.created_by,
            createdOn: item.created_at ? new Date(item.created_at).toLocaleDateString() : '-',
            remarks: item.remark || '-',
            buyerHead: item.b_head,
            proforma : item.proforma_ids[0],
            po_url : item.po_url,
            product : item.images[0],
        };

        // Icon mapping for fields
        const fieldIcons = {
            refId: '<i class="fas fa-hashtag text-blue-500"></i>',
            poNumber: '<i class="fas fa-file-invoice text-blue-500"></i>',
            poHead: '<i class="fas fa-user-tie text-blue-500"></i>',
            poTeam: '<i class="fas fa-users text-blue-500"></i>',
            supplier: '<i class="fas fa-truck text-blue-500"></i>',
            category: '<i class="fas fa-tags text-blue-500"></i>',
            purchType: '<i class="fas fa-shopping-cart text-blue-500"></i>',
            qty: '<i class="fas fa-boxes text-blue-500"></i>',
            createdBy: '<i class="fas fa-user text-blue-500"></i>',
            createdOn: '<i class="fas fa-calendar text-blue-500"></i>',
            remarks: '<i class="fas fa-comment text-blue-500"></i>'
        };

        let fieldsHtml = '';
        Object.keys(config.showFields).forEach(key => {
            if (config.showFields[key] && mappedItem[key] !== undefined) {
                const label = key
                    .replace(/([A-Z])/g, ' $1')
                    .replace(/^./, str => str.toUpperCase());
                const icon = fieldIcons[key] || '<i class="fas fa-circle text-blue-500"></i>';
                fieldsHtml += `
                    <div class="flex items-center gap-2">
                        <span class="font-semibold w-24 flex items-center gap-1 capitalize">
                            ${icon}
                            ${label}:
                        </span>
                        <span class="truncate capitalize">${typeof mappedItem[key] === 'string' && key !== 'refId' && key !== 'poNumber' && key !== 'createdOn' && key !== 'qty' ? toTitleCase(mappedItem[key]) : mappedItem[key]}</span>
                    </div>
                `;
            }
        });

        let buttonsHtml = '';
        buttonsHtml += `<button class="btn btn-sm btn-outline h-7 min-h-7 px-2 text-xs read-more-toggle" data-id='${mappedItem.refId}'><i class="fas fa-comment text-blue-500 text-xs"></i> <span class="hidden sm:inline">remarks</span></button>`;
        if (config.showButtons.edit && item.po_status === 1) buttonsHtml += `<button class="btn btn-sm btn-outline h-7 min-h-7 px-2 text-xs openEditPRBtn" data-pr-id='${mappedItem.refId}'><i class="fas fa-edit text-blue-500 text-xs"></i> <span class="hidden sm:inline">Edit</span></button>`;
        
        if (config.showButtons.proforma) {
    const hasProforma = item.proforma_ids && item.proforma_ids[0] ? true : false;
    buttonsHtml += `
        <button class="btn btn-sm btn-outline h-7 min-h-7 px-2 text-xs proforma" data-pr-id='${mappedItem.refId}' data-status-id='${item.po_status}' data-role='${config.role}'>
            <i class="fas fa-file-invoice-dollar text-blue-500 text-xs"></i>
            <span class="hidden sm:inline">Proforma</span>
            ${hasProforma ? `<i class="fas fa-check text-success text-xs"></i>` : ''}
        </button>
    `;
}

// PO button
if (config.showButtons.po && item.po_status === 7) {
    const hasPO = item.po_url ? true : false;
    buttonsHtml += `
        <button class="btn btn-sm btn-outline h-7 min-h-7 px-2 text-xs po" data-pr-id='${mappedItem.refId}' data-status-id='${item.po_status}' data-role='${config.role}'>
            <i class="fas fa-file-alt text-blue-500 text-xs"></i>
            <span class="hidden sm:inline">PO</span>
            ${hasPO ? `<i class="fas fa-check text-success text-xs"></i>` : ''}
        </button>
    `;
}
        if(config.role === 'admin' ? [1].includes(item.po_status) :
           config.role === 'bhead' ? [1, 5].includes(item.po_status) :
           config.role === 'buyer' ? [2, 3, 4].includes(item.po_status) :
           config.role === 'pohead' ? [6].includes(item.po_status) :
           false) buttonsHtml += `<button class="btn btn-sm btn-outline h-7 min-h-7 px-2 text-xs update-status" data-id='${mappedItem.refId}' data-status='${item.po_status}'><i class="fas fa-arrow-right text-blue-500 text-xs"></i></button>`;
        if (config.role === 'poteammember' && item.po_status === 9) buttonsHtml += `<button class="btn btn-sm btn-info h-7 min-h-7 px-2 text-xs insert-po" data-id='${mappedItem.refId}'><i class="fas fa-plus text-white text-xs"></i> <span class="hidden sm:inline">Insert PO</span></button>`;

        const cardHtml = `
            <div class="card w-full min-w-[280px] max-w-[320px] min-h-[400px] bg-base-100 shadow-md border border-gray-200 m-2 opacity-0 translate-y-4 scale-95 transition-all duration-500 ease-in-out hover:shadow-lg hover:scale-105 rounded-2xl flex flex-col">
                <div class="card-body p-4 flex flex-col flex-grow relative">
                    <div class="absolute top-2 right-2 product cursor-pointer" data-pr-id='${mappedItem.refId}' data-status-id='${item.po_status}' data-role='${config.role}'>
                        <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-lg overflow-hidden flex justify-center items-center bg-base-200">
                            <img src="../${mappedItem.product}" alt="product" class="w-full h-full object-cover cursor-pointer" loading="lazy" decoding="async" onerror="this.onerror=null; this.src='../assets/brand/no-image.png';"/>
                        </div>
                    </div>
                    <!-- Header -->
                    <div class="mb-3 pr-20">
                        <h2 class="text-base font-bold mb-1 truncate capitalize">Buyer Head: ${mappedItem.buyerHead}</h2>
                        ${statusBadge}
                    </div>

                    <!-- Content -->
                    <div class="space-y-1.5 text-sm flex-grow">${fieldsHtml}</div>

                    <!-- Divider -->
                    <div class="divider my-3"></div>

                    <!-- Footer Actions -->
                    <div class="flex flex-nowrap items-center gap-1 overflow-x-auto">
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

        // Set up Intersection Observer for scroll animation with scale effect
        if (!window.cardObserver) {
            window.cardObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('opacity-100', 'translate-y-0', 'scale-100');
                        entry.target.classList.remove('translate-y-4', 'scale-95');
                    } else {
                        // Scale down when leaving viewport
                        entry.target.classList.add('scale-95');
                        entry.target.classList.remove('scale-100');
                    }
                });
            }, { threshold: 0.1 });
        }
        window.cardObserver.observe(container.lastElementChild);
    });
}
