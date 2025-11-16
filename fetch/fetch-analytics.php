<?php
header('Content-Type: application/json');
include '../config/db.php';
include '../config/response.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    sendResponse(401, "error", "User not logged in");
}

try {
    // Get filters
    $startDate = $_GET['start_date'] ?? '';
    $endDate = $_GET['end_date'] ?? '';
    $statusIds = isset($_GET['status']) ? (is_array($_GET['status']) ? $_GET['status'] : [$_GET['status']]) : [];
    $buyerIds = isset($_GET['buyer']) ? (is_array($_GET['buyer']) ? $_GET['buyer'] : [$_GET['buyer']]) : [];
    $categories = isset($_GET['category']) ? (is_array($_GET['category']) ? $_GET['category'] : [$_GET['category']]) : [];
    $purchIds = isset($_GET['purch']) ? (is_array($_GET['purch']) ? $_GET['purch'] : [$_GET['purch']]) : [];

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

    // Main query
    $sql = "SELECT DISTINCT
                pt.id,
                pt.created_at,
                pt.po_status,
                b.username AS buyer,
                s.supplier,
                pm.name as purch_type,
                st.status AS status_name,
                (SELECT GROUP_CONCAT(DISTINCT c.maincat SEPARATOR ', ') 
                 FROM catbasbh cb
                 JOIN categories c ON c.maincat = cb.cat
                 WHERE cb.user_id = pt.b_head
                 LIMIT 50) AS categories
            FROM purchase_requests pt
            LEFT JOIN users b ON pt.buyer = b.id
            LEFT JOIN suppliers s ON pt.supplier_id = s.id
            LEFT JOIN pr_statuses st ON pt.po_status = st.id
            LEFT JOIN purchase_types pm ON pm.id = pt.purch_id
            WHERE $where";

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
            $rowCategories = explode(', ', $row['categories'] ?? '');
            return !empty(array_intersect($categories, $rowCategories));
        });
    }

    // Calculate distributions
    $analytics = [
        'purchase_type_distribution' => [],
        'category_distribution' => [],
        'status_distribution' => [],
        'buyer_distribution' => [],
        'supplier_distribution' => [],
        'monthly_trend' => [],
        'status_over_time' => []
    ];

    foreach ($allData as $row) {
        // Purchase Type
        $purchType = $row['purch_type'] ?? 'Unknown';
        $analytics['purchase_type_distribution'][$purchType] = 
            ($analytics['purchase_type_distribution'][$purchType] ?? 0) + 1;

        // Category
        $cats = explode(', ', $row['categories'] ?? '');
        foreach ($cats as $cat) {
            $cat = trim($cat);
            if ($cat) {
                $analytics['category_distribution'][$cat] = 
                    ($analytics['category_distribution'][$cat] ?? 0) + 1;
            }
        }

        // Status
        $status = $row['status_name'] ?? 'Unknown';
        $analytics['status_distribution'][$status] = 
            ($analytics['status_distribution'][$status] ?? 0) + 1;

        // Buyer
        $buyer = $row['buyer'] ?? 'Unknown';
        $analytics['buyer_distribution'][$buyer] = 
            ($analytics['buyer_distribution'][$buyer] ?? 0) + 1;

        // Supplier
        $supplier = $row['supplier'] ?? 'Unknown';
        $analytics['supplier_distribution'][$supplier] = 
            ($analytics['supplier_distribution'][$supplier] ?? 0) + 1;

        // Monthly Trend
        if ($row['created_at']) {
            $month = date('Y-m', strtotime($row['created_at']));
            $analytics['monthly_trend'][$month] = 
                ($analytics['monthly_trend'][$month] ?? 0) + 1;
        }

        // Status Over Time
        if ($row['created_at']) {
            $month = date('Y-m', strtotime($row['created_at']));
            if (!isset($analytics['status_over_time'][$month])) {
                $analytics['status_over_time'][$month] = [];
            }
            $analytics['status_over_time'][$month][$status] = 
                ($analytics['status_over_time'][$month][$status] ?? 0) + 1;
        }
    }

    sendResponse(200, "success", "Analytics data retrieved successfully", $analytics);

} catch (Exception $e) {
    sendResponse(500, "error", "Error: " . $e->getMessage());
}
?>

