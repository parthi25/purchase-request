<?php
header('Content-Type: application/json');
include '../config/db.php';
include '../config/response.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    sendResponse(401, "error", "User not logged in");
}

try {
    // Get filters from chart click
    $startDate = $_GET['start_date'] ?? '';
    $endDate = $_GET['end_date'] ?? '';
    $statusIds = isset($_GET['status']) ? (is_array($_GET['status']) ? $_GET['status'] : [$_GET['status']]) : [];
    $buyerIds = isset($_GET['buyer']) ? (is_array($_GET['buyer']) ? $_GET['buyer'] : [$_GET['buyer']]) : [];
    $categories = isset($_GET['category']) ? (is_array($_GET['category']) ? $_GET['category'] : [$_GET['category']]) : [];
    $purchIds = isset($_GET['purch']) ? (is_array($_GET['purch']) ? $_GET['purch'] : [$_GET['purch']]) : [];
    $supplierName = $_GET['supplier'] ?? '';
    $purchTypeName = $_GET['purch_type'] ?? '';
    $buyerName = $_GET['buyer_name'] ?? '';
    $statusName = $_GET['status_name'] ?? '';
    $categoryName = $_GET['category_name'] ?? '';
    $month = $_GET['month'] ?? '';

    // Build WHERE clause
    $where = "1=1";
    $params = [];
    $types = "";

    if ($startDate) {
        $where .= " AND DATE(pt.created_at) >= ?";
        $params[] = $startDate;
        $types .= "s";
    }

    if ($endDate) {
        $where .= " AND DATE(pt.created_at) <= ?";
        $params[] = $endDate;
        $types .= "s";
    }

    if (!empty($statusIds)) {
        $placeholders = implode(',', array_fill(0, count($statusIds), '?'));
        $where .= " AND pt.po_status IN ($placeholders)";
        $params = array_merge($params, $statusIds);
        $types .= str_repeat("i", count($statusIds));
    }

    if (!empty($buyerIds)) {
        $placeholders = implode(',', array_fill(0, count($buyerIds), '?'));
        $where .= " AND pt.buyer IN ($placeholders)";
        $params = array_merge($params, $buyerIds);
        $types .= str_repeat("i", count($buyerIds));
    }

    if (!empty($purchIds)) {
        $placeholders = implode(',', array_fill(0, count($purchIds), '?'));
        $where .= " AND pt.purch_id IN ($placeholders)";
        $params = array_merge($params, $purchIds);
        $types .= str_repeat("i", count($purchIds));
    }

    // Filter by name values (from chart clicks)
    if ($supplierName) {
        $where .= " AND s.supplier = ?";
        $params[] = $supplierName;
        $types .= "s";
    }

    if ($purchTypeName) {
        // Handle "Unknown" purchase type (NULL or invalid purch_id)
        if ($purchTypeName === 'Unknown') {
            $where .= " AND (pt.purch_id IS NULL OR pm.id IS NULL)";
        } else {
            $where .= " AND pm.name = ?";
            $params[] = $purchTypeName;
            $types .= "s";
        }
    }

    if ($buyerName) {
        // Handle "Unknown" buyer (NULL or invalid buyer_id)
        if ($buyerName === 'Unknown') {
            $where .= " AND (pt.buyer IS NULL OR b.id IS NULL) AND (ptm.buyername IS NULL) AND (pt.b_head IS NULL OR bh.id IS NULL)";
        } else {
            $where .= " AND (b.username = ? OR ptm.buyername = ? OR bh.username = ?)";
            $params[] = $buyerName;
            $params[] = $buyerName;
            $params[] = $buyerName;
            $types .= "sss";
        }
    }

    if ($statusName) {
        $where .= " AND st.status = ?";
        $params[] = $statusName;
        $types .= "s";
    }

    if ($month) {
        $where .= " AND DATE_FORMAT(pt.created_at, '%Y-%m') = ?";
        $params[] = $month;
        $types .= "s";
    }

    // Main query
    $sql = "SELECT DISTINCT
                pt.id,
                pt.id AS ref_id,
                pt.created_at,
                pt.po_status,
                COALESCE(b.username, ptm.buyername, bh.username, 'Unknown') AS buyer,
                s.supplier,
                COALESCE(pm.name, 'Unknown') as purch_type,
                st.status AS status_name,
                pt.qty,
                pt.uom,
                pt.remark,
                c.maincat AS categories
            FROM purchase_requests pt
            LEFT JOIN users b ON pt.buyer = b.id
            LEFT JOIN users bh ON pt.b_head = bh.id
            LEFT JOIN pr_assignments ptm ON ptm.ord_id = pt.id
            LEFT JOIN suppliers s ON pt.supplier_id = s.id
            LEFT JOIN pr_statuses st ON pt.po_status = st.id
            LEFT JOIN purchase_types pm ON pm.id = pt.purch_id
            LEFT JOIN categories c ON c.id = pt.category_id
            WHERE $where
            ORDER BY pt.created_at DESC
            LIMIT 1000";

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $allData = [];
    while ($row = $result->fetch_assoc()) {
        $allData[] = $row;
    }
    $stmt->close();

    // Filter by category if specified
    if (!empty($categories)) {
        $allData = array_filter($allData, function($row) use ($categories) {
            $rowCategory = $row['categories'] ?? '';
            return in_array($rowCategory, $categories);
        });
    }

    // Also filter by category name if specified
    if ($categoryName) {
        $allData = array_filter($allData, function($row) use ($categoryName) {
            $rowCategory = $row['categories'] ?? '';
            return $rowCategory === $categoryName;
        });
    }

    // Re-index array after filtering
    $allData = array_values($allData);

    sendResponse(200, "success", "Records retrieved successfully", $allData);

} catch (Exception $e) {
    sendResponse(500, "error", "Error: " . $e->getMessage());
}
?>

