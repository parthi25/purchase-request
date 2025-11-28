<?php
session_start();
require '../../config/db.php';
require '../../config/response.php';

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    sendResponse(401, "error", "User not authenticated");
}

// Get pagination parameters
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = isset($_GET['per_page']) ? max(1, min(100, (int)$_GET['per_page'])) : 10;
$offset = ($page - 1) * $perPage;

// Get search parameter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    // Build WHERE clause
    $where = "1=1";
    $params = [];
    $types = "";

    if (!empty($search)) {
        $where .= " AND (pt.id LIKE ? OR s.supplier LIKE ? OR c.maincat LIKE ? OR st.status LIKE ?)";
        $searchTerm = '%' . $search . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= 'ssss';
    }

    // Count total records
    $countSql = "SELECT COUNT(DISTINCT pt.id) as total 
                 FROM purchase_requests pt
                 LEFT JOIN suppliers s ON pt.supplier_id = s.id
                 LEFT JOIN categories c ON pt.category_id = c.id
                 LEFT JOIN pr_statuses st ON pt.po_status = st.id
                 WHERE $where";
    
    $countStmt = $conn->prepare($countSql);
    if (!empty($params)) {
        $countStmt->bind_param($types, ...$params);
    }
    $countStmt->execute();
    $totalResult = $countStmt->get_result();
    $total = $totalResult->fetch_assoc()['total'];
    $countStmt->close();

    // Get paginated data
    $sql = "SELECT pt.id,
                   pt.created_at,
                   pt.updated_at,
                   pt.qty,
                   pt.uom,
                   pt.remark,
                   pt.po_status,
                   COALESCE(s.supplier, 'N/A') as supplier,
                   COALESCE(c.maincat, 'N/A') as category,
                   COALESCE(st.status, 'N/A') as status,
                   COALESCE(bh.username, 'N/A') as buyer_head,
                   COALESCE(b.username, 'N/A') as buyer,
                   COALESCE(pm.name, 'N/A') as purchase_type
            FROM purchase_requests pt
            LEFT JOIN suppliers s ON pt.supplier_id = s.id
            LEFT JOIN categories c ON pt.category_id = c.id
            LEFT JOIN pr_statuses st ON pt.po_status = st.id
            LEFT JOIN users bh ON pt.b_head = bh.id
            LEFT JOIN users b ON pt.buyer = b.id
            LEFT JOIN purchase_types pm ON pt.purch_id = pm.id
            WHERE $where
            ORDER BY pt.created_at DESC
            LIMIT ? OFFSET ?";

    $dataStmt = $conn->prepare($sql);
    if (!empty($params)) {
        $params[] = $perPage;
        $params[] = $offset;
        $types .= 'ii';
        $dataStmt->bind_param($types, ...$params);
    } else {
        $dataStmt->bind_param('ii', $perPage, $offset);
    }
    
    $dataStmt->execute();
    $result = $dataStmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    $dataStmt->close();

    // Calculate pagination info
    $totalPages = ceil($total / $perPage);

    sendResponse(200, "success", "PRs retrieved successfully", [
        'data' => $data,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $perPage,
            'total_items' => $total,
            'total_pages' => $totalPages,
            'has_next' => $page < $totalPages,
            'has_prev' => $page > 1
        ]
    ]);

} catch (Exception $e) {
    error_log("Error in list-prs.php: " . $e->getMessage());
    sendResponse(500, "error", "Internal server error");
} finally {
    $conn->close();
}
?>

