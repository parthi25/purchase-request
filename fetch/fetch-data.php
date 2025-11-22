<?php
/**
 * Fetch Data - Main Data Fetcher
 * Handles data fetching with filters and pagination using unified response format
 */

session_start();

// Load required configurations
require_once '../config/db.php';
require_once '../config/response.php';

// Content type set in response.php

// Utility to fix datepicker bug
function fix_datepicker_bug($date)
{
    return $date ? date('Y-m-d', strtotime($date . ' +1 day')) : '';
}

// ---------------- INPUT ----------------
$offset = max(0, (int) ($_GET['offset'] ?? 0));
$limit = max(1, min(100, (int) ($_GET['limit'] ?? 20)));

$status_filter = isset($_GET['status_filter']) ? (int) $_GET['status_filter'] : (isset($_GET['status']) ? (int) $_GET['status'] : null);

// Validate session
$userid = $_SESSION['user_id'] ?? 0;
$user_role = $_SESSION['role'] ?? '';

// Override role if passed from frontend (for testing/debugging)
$frontend_role = $_GET['role'] ?? '';
if ($frontend_role) {
    // Map frontend role names to backend role names
    $role_mapping = [
        'bhead' => 'B_Head',
        'pohead' => 'PO_Team',
        'buyer' => 'buyer',
        'admin' => 'admin',
        'poteammember' => 'PO_Team_Member'
    ];
    if (isset($role_mapping[$frontend_role])) {
        $user_role = $role_mapping[$frontend_role];
    }
}

if ($userid <= 0) {
    sendResponse(401, "error", "Invalid session");
}

$buyer_filter = (int) ($_GET['buyer_filter'] ?? 0);
$bhead_filter = (int) ($_GET['bhead_filter'] ?? 0);
$poteam_filter = (int) ($_GET['poteam_filter'] ?? 0);
$supplier_filter = (int) ($_GET['supplier_filter'] ?? 0);
$category_filter = (int) ($_GET['category_filter'] ?? 0);
$purch_filter = (int) ($_GET['purch_filter'] ?? 0);
$po_filter = (int) ($_GET['po_filter'] ?? 0);

$search = trim($_GET['search'] ?? '');
$from_date = fix_datepicker_bug($_GET['from_date'] ?? $_GET['from'] ?? '');
$to_date = fix_datepicker_bug($_GET['to_date'] ?? $_GET['to'] ?? '');

// ---------------- BASE QUERY (OPTIMIZED) ----------------
$sql = "SELECT p.*, 
            u.username AS created_by_name,
            b.username AS b_head_name,
            bu.username AS buyer_name,
            pt.username AS po_team_name,
            po.po_num,
            po.url AS po_url,
            pm.name AS purch_type,
            CASE WHEN p.supplier_id=99999 THEN ns.supplier ELSE s.supplier END AS supplier_name,
            CASE WHEN p.supplier_id=99999 THEN ns.city ELSE s.city END AS city,
            CASE WHEN p.supplier_id=99999 THEN ns.agent ELSE s.agent END AS agent,
            c.maincat AS category,
            ptm.po_number AS po_number,
            ptm.buyername AS buyername,
            upo.username AS po_team_member,
            s.id AS supplier_code
        FROM purchase_requests p
        LEFT JOIN pr_assignments ptm ON p.id = ptm.ord_id
        LEFT JOIN users upo ON ptm.po_team_member = upo.id
        LEFT JOIN users u ON p.created_by = u.id
        LEFT JOIN users b ON p.b_head = b.id
        LEFT JOIN users bu ON p.buyer = bu.id
        LEFT JOIN users pt ON p.po_team = pt.id
        LEFT JOIN po_documents po ON p.id = po.ord_id AND po.filename IS NOT NULL
        LEFT JOIN suppliers s ON p.supplier_id = s.id
        LEFT JOIN supplier_requests ns ON p.new_supplier = ns.id
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN purchase_types pm ON pm.id = p.purch_id";

$conditions = [];
$values = [];
$param_types = "";

// ---------------- ROLE FILTER ----------------
switch ($user_role) {
    case 'admin':
        break;
    case 'B_Head':
        $conditions[] = "(p.b_head=? OR p.created_by=?)";
        $param_types .= "ii";
        $values[] = $userid;
        $values[] = $userid;
        break;
    case 'buyer':
        $conditions[] = "(p.buyer=? OR p.created_by=?)";
        $param_types .= "ii";
        $values[] = $userid;
        $values[] = $userid;
        break;
    case 'PO_Team':
        $conditions[] = "p.po_team =?";
        $param_types .= "i";
        $values[] = $userid;
        break;
    case 'PO_Team_Member':
        $conditions[] = "ptm.po_team_member=?";
        $param_types .= "i";
        $values[] = $userid;
        break;
}

// ---------------- SEARCH FILTER (OPTIMIZED) ----------------
if ($search !== '') {
    $search_conditions = [];

    // Check if search is numeric first (faster than LIKE)
    if (ctype_digit($search)) {
        $search_int = (int) $search;
        $search_conditions[] = "p.id=? OR ptm.po_number=?";
        $param_types .= "ii";
        $values[] = $search_int;
        $values[] = $search_int;
    } else {
        // Use LIKE only for text searches, avoid UPPER() if possible
        $like = "%" . $search . "%";
        // Optimize: Use COALESCE to handle NULLs and reduce conditions
        $search_conditions[] = "(s.supplier LIKE ? OR s.agent LIKE ? OR ns.supplier LIKE ? OR ns.agent LIKE ? OR c.maincat LIKE ? OR u.username LIKE ? OR b.username LIKE ? OR bu.username LIKE ? OR ptm.po_number LIKE ?)";
        $param_types .= str_repeat("s", 9);
        for ($i = 0; $i < 9; $i++)
            $values[] = $like;
    }

    if (!empty($search_conditions)) {
        $conditions[] = "(" . implode(" OR ", $search_conditions) . ")";
    }
}

// ---------------- DATE FILTER ----------------
// Use direct date comparison instead of DATE() function for better performance
if ($from_date) {
    $conditions[] = "p.created_at >= ?";
    $param_types .= "s";
    $values[] = $from_date . ' 00:00:00';
}
if ($to_date) {
    $conditions[] = "p.created_at <= ?";
    $param_types .= "s";
    $values[] = $to_date . ' 23:59:59';
}
// ---------------- OTHER FILTERS ----------------
$filters = [
    'p.purch_id' => $purch_filter,
    'p.buyer' => $buyer_filter,
    'p.b_head' => $bhead_filter,
    'p.po_team' => $poteam_filter,
    'p.supplier_id' => $supplier_filter,
    'p.category_id' => $category_filter,
    'ptm.po_team_member' => $po_filter
];

foreach ($filters as $col => $val) {
    if (
        $user_role === 'buyer' &&
        $col === 'p.buyer' && !empty($buyer_filter)
    ) {
        // include buyer and also their created records
        $conditions[] = "(p.buyer = ? OR p.created_by = ?)";
        $param_types .= "ii";
        $values[] = $buyer_filter;
        $values[] = $buyer_filter;
        continue;
    }

    if ($val > 0) {
        $conditions[] = "$col = ?";
        $param_types .= "i";
        $values[] = $val;
    }
}



// ---------------- STATUS FILTER ----------------
if ($status_filter !== null && $status_filter > 0) {
    if ($user_role === 'admin') {
        if ($status_filter === 11)
            $conditions[] = "p.po_status NOT IN (1,7,8)";
        elseif ($status_filter === 7)
            $conditions[] = "p.po_status IN (7,8)";
        else {
            $conditions[] = "p.po_status=?";
            $param_types .= "i";
            $values[] = $status_filter;
        }
    } else {
        $digits = str_split((string) $status_filter);
        $placeholders = implode(',', array_fill(0, count($digits), '?'));
        $conditions[] = "p.po_status IN ($placeholders)";
        $param_types .= str_repeat('i', count($digits));
        $values = array_merge($values, $digits);
    }
}

// ---------------- FINAL QUERY (OPTIMIZED) ----------------
if ($conditions)
    $sql .= " WHERE " . implode(" AND ", $conditions);
$sql .= " ORDER BY p.created_at DESC LIMIT ?,?";
$param_types .= "ii";
$values[] = $offset;
$values[] = $limit;

$stmt = $conn->prepare($sql);
if (!$stmt) {
    sendResponse(500, "error", "Database query preparation failed");
}
if ($param_types)
    $stmt->bind_param($param_types, ...$values);
$stmt->execute();
$result = $stmt->get_result();

// ---------------- FETCH MAIN DATA ----------------
$data = [];
$ids = [];
while ($row = $result->fetch_assoc()) {
    $row['created_by'] = $row['created_by_name'] ?? 'Unknown';
    $row['b_head'] = $row['b_head_name'] ?? 'Unknown';
    $row['buyer'] = $row['buyer_name'] ?? 'Unknown';
    $row['po_team'] = $row['po_team_name'] ?? '-';
    $row['po_team_member'] = $row['po_team_member'] ?? '-';
    $row['supplier'] = $row['supplier_name'] ?? 'Unknown';
    $row['city'] = $row['city'] ?? 'Unknown';
    $row['agent'] = $row['agent'] ?? 'Unknown';
    $row['category_name'] = $row['category'] ?? 'Unknown';
    unset($row['created_by_name'], $row['b_head_name'], $row['buyer_name'], $row['po_team_name'], $row['supplier_name']);
    $data[$row['id']] = $row;
    $ids[] = $row['id'];
}

// ---------------- FETCH ALL IMAGES IN ONE QUERY (OPTIMIZED) ----------------
if ($ids) {
    // Sanitize IDs and use prepared statement for security
    $id_list = implode(',', array_map('intval', $ids));
    $img_sql = "
        SELECT ord_id, id, url, 'pr_attachments' AS type FROM pr_attachments WHERE ord_id IN ($id_list) AND filename IS NOT NULL
        UNION ALL
        SELECT ord_id, id, url, 'proforma' AS type FROM proforma WHERE ord_id IN ($id_list) AND filename IS NOT NULL
    ";
    $img_result = $conn->query($img_sql);
    
    if ($img_result) {
        $images_map = [];
        while ($img = $img_result->fetch_assoc()) {
            $oid = (int) $img['ord_id'];
            $type = $img['type'];
            if (!isset($images_map[$oid]))
                $images_map[$oid] = ['pr_attachments' => [], 'proforma' => []];
            $images_map[$oid][$type][] = ['id' => (int) $img['id'], 'url' => $img['url']];
        }
        
        // Initialize arrays to avoid isset checks
        foreach ($data as &$d) {
            $oid = $d['id'];
            $d['po_order_ids'] = array_column($images_map[$oid]['pr_attachments'] ?? [], 'id');
            $d['images'] = array_column($images_map[$oid]['pr_attachments'] ?? [], 'url');
            $d['proforma_ids'] = array_column($images_map[$oid]['proforma'] ?? [], 'id');
            $d['proforma_images'] = array_column($images_map[$oid]['proforma'] ?? [], 'url');
        }
        unset($d); // Break reference
    }
}

// ---------------- DEBUG LOGGING (DISABLED FOR PERFORMANCE) ----------------
// Uncomment only when debugging
// error_log("DEBUG: Role: $user_role, Status Filter: $status_filter, User ID: $userid");
// error_log("DEBUG: Buyer Filter: $buyer_filter, PO Filter: $po_filter");
// error_log("DEBUG: Fetched " . count($data) . " records");

// ---------------- OUTPUT ----------------
sendResponse(200, "success", "Data fetched successfully", array_values($data));
// ApiResponse::success($sql, "Data fetched successfully");
$stmt->close();
$conn->close();
?>