<?php
session_start();
include '../config/db.php';
include '../config/response.php'; // unified sendResponse

// Ensure user is authenticated
if (!isset($_SESSION['user_id'])) {
    sendResponse(401, "error", "User not authenticated");
}

$user_id = (int) $_SESSION['user_id'];
$user_role =  $_SESSION['role'] ?? '';

// Get buyer_id from GET parameter for B_Head and PO_Head roles, otherwise use user_id
if ($user_role == 'B_Head' || $user_role == 'PO_Head') {
    $buyer_id = isset($_GET['buyer_id']) && $_GET['buyer_id'] !== '' && $_GET['buyer_id'] !== '0' 
        ? (int) $_GET['buyer_id'] 
        : $user_id;
} else {
    $buyer_id = $user_id;
}

try {
    // Base query
    $statusQuery = "
        SELECT DISTINCT s.id as status_id, s.status, COALESCE(COUNT(p.po_status), 0) AS count
        FROM pr_statuses s
        LEFT JOIN purchase_requests p ON s.id = p.po_status
    ";

    $bindTypes = "";
    $bindValues = [];

    // Apply filters
    if ($user_role === 'admin') {
        // Admin sees all records, no WHERE clause
        $statusQuery .= " GROUP BY s.id ORDER BY FIELD(s.id, 1,2,3,4,5,6,9,7,8)";
    } elseif ($user_role === 'poteam') {
        $statusQuery .= " WHERE p.po_status IN (9, 7) GROUP BY s.id ORDER BY FIELD(s.id, 9,7)";
    } elseif ($user_role === 'B_Head') {
        // Buyer Head: if buyer_id is provided and different from user_id, show that buyer's counts
        // Otherwise show counts for buyer head's own records
        if ($buyer_id && $buyer_id != $user_id) {
            // Selected buyer: show counts for that buyer
            $statusQuery .= " WHERE (p.buyer = ? OR p.created_by = ?)";
            $bindTypes .= "ii";
            $bindValues[] = $buyer_id;
            $bindValues[] = $buyer_id;
        } else {
            // No buyer selected: show counts for buyer head's own records
            $statusQuery .= " WHERE (p.b_head = ? OR p.created_by = ?)";
            $bindTypes .= "ii";
            $bindValues[] = $user_id;
            $bindValues[] = $user_id;
        }
        $statusQuery .= " GROUP BY s.id ORDER BY s.id";
    } elseif ($user_role === 'PO_Head') {
        // PO Head: if buyer_id is provided and different from user_id, show that user's counts
        // Otherwise show counts for PO head's own records
        if ($buyer_id && $buyer_id != $user_id) {
            // Selected user: show counts for that user (as buyer or creator)
            $statusQuery .= " WHERE (p.buyer = ? OR p.created_by = ?)";
            $bindTypes .= "ii";
            $bindValues[] = $buyer_id;
            $bindValues[] = $buyer_id;
        } else {
            // No user selected: show counts for PO head's own records
            $statusQuery .= " WHERE (p.po_team = ? OR p.created_by = ?)";
            $bindTypes .= "ii";
            $bindValues[] = $user_id;
            $bindValues[] = $user_id;
        }
        $statusQuery .= " GROUP BY s.id ORDER BY s.id";
    } elseif ($buyer_id) {
        // Regular users: show their own counts
        $statusQuery .= " WHERE (p.buyer = ? OR p.created_by = ?)";
        $bindTypes .= "ii";
        $bindValues[] = $buyer_id;
        $bindValues[] = $buyer_id;
        $statusQuery .= " GROUP BY s.id ORDER BY s.id";
    } else {
        $statusQuery .= " GROUP BY s.id ORDER BY s.id";
    }

    $stmt = $conn->prepare($statusQuery);
    if (!$stmt) {
        sendResponse(500, "error", "Database query preparation failed");
    }

    if (!empty($bindValues)) {
        $stmt->bind_param($bindTypes, ...$bindValues);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    // Status mapping
    $statusMapping = [
        "Open" => ['status_id' => 1, 'status_key' => 'Open', 'label' => 'Open'],
        "Forwarded to Buyer" => ['status_id' => 2, 'status_key' => 'Forwarded to Buyer', 'label' => 'To Buyer'],
        "Agent/Supplier contacted and Awaiting PO details" => ['status_id' => 3, 'status_key' => 'awaiting_po', 'label' => 'Awaiting PO'],
        "Received Proforma PO" => ['status_id' => 4, 'status_key' => 'proforma', 'label' => 'Proforma'],
        "Forwarded to Buyer Head" => ['status_id' => 5, 'status_key' => 'to_buyer_head', 'label' => 'To Category Head'],
        "Forwarded to PO Team" => ['status_id' => 6, 'status_key' => 'to_po_hed', 'label' => 'To PO Head'],
        "PO generated" => ['status_id' => 7, 'status_key' => 'po_generated', 'label' => 'PO Generated'],
        "Rejected" => ['status_id' => 8, 'status_key' => 'rejected', 'label' => 'Rejected'],
        "Forwarded to PO Members" => ['status_id' => 9, 'status_key' => 'to_po_team', 'label' => 'To PO Team'],
    ];

    // Initialize counts
    $statusCounts = array_fill_keys(array_keys($statusMapping), 0);

    while ($row = $result->fetch_assoc()) {
        $statusName = $row['status'];
        if (isset($statusCounts[$statusName])) {
            $statusCounts[$statusName] = (int) $row['count'];
        }
    }

    // Prepare final array for JS
    $countsArray = [];
    foreach ($statusMapping as $status => $info) {
        $countsArray[] = [
            'status_id' => $info['status_id'],
            'status_key' => $info['status_key'],
            'count' => $statusCounts[$status],
            'label' => $info['label']
        ];
    }

    $customOrder = [1,2,3,4,5,6,9,7,8];
usort($countsArray, function($a, $b) use ($customOrder) {
    $posA = array_search($a['status_id'], $customOrder);
    $posB = array_search($b['status_id'], $customOrder);

    $posA = $posA === false ? PHP_INT_MAX : $posA;
    $posB = $posB === false ? PHP_INT_MAX : $posB;

    return $posA <=> $posB;
});
    sendResponse(200, "success", "Status counts retrieved successfully", $countsArray);

    $stmt->close();

} catch (Exception $e) {
    error_log("Error in fetch_status_count.php: " . $e->getMessage());
    sendResponse(500, "error", "Internal server error");
} finally {
    $conn->close();
}
?>
