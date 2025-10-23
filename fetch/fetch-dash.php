<?php
header('Content-Type: application/json');
include '../config/db.php';

// Error handling
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

try {
    session_start();
    $userid = $_SESSION['user_id'] ?? 0;
    $role = $_SESSION['role'] ?? '';

    // Input validation
    $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
    $perPage = filter_input(INPUT_GET, 'per_page', FILTER_VALIDATE_INT, ['options' => ['default' => 25, 'min_range' => 5, 'max_range' => 100]]);
    $includeAllData = filter_input(INPUT_GET, 'include_all_data', FILTER_VALIDATE_BOOLEAN);
    $offset = ($page - 1) * $perPage;

    // Get filter parameters
    $filters = [
        'status' => isset($_GET['status_filter']) ? 
            (is_array($_GET['status_filter']) ? 
                array_map('htmlspecialchars', $_GET['status_filter']) : 
                [htmlspecialchars($_GET['status_filter'])]) : [],
        'buyer_head' => isset($_GET['buyer_head']) ? 
            (is_array($_GET['buyer_head']) ? 
                array_filter($_GET['buyer_head'], 'is_numeric') : 
                [filter_var($_GET['buyer_head'], FILTER_VALIDATE_INT)]) : [],
        'buyer' => isset($_GET['buyer']) ? 
            (is_array($_GET['buyer']) ? 
                array_filter($_GET['buyer'], 'is_numeric') : 
                [filter_var($_GET['buyer'], FILTER_VALIDATE_INT)]) : [],
        'supplier' => isset($_GET['supplier']) ? 
            (is_array($_GET['supplier']) ? 
                array_filter($_GET['supplier'], 'is_numeric') : 
                [filter_var($_GET['supplier'], FILTER_VALIDATE_INT)]) : [],
        'category' => isset($_GET['category']) ? 
            (is_array($_GET['category']) ? 
                array_map('htmlspecialchars', $_GET['category']) : 
                [htmlspecialchars($_GET['category'])]) : [],
        'purchFilter' => isset($_GET['purchFilter']) ? 
            (is_array($_GET['purchFilter']) ? 
                array_filter($_GET['purchFilter'], 'is_numeric') : 
                [filter_var($_GET['purchFilter'], FILTER_VALIDATE_INT)]) : [],
        'po_team_member' => isset($_GET['po_team_member']) ? 
            (is_array($_GET['po_team_member']) ? 
                array_filter($_GET['po_team_member'], 'is_numeric') : 
                [filter_var($_GET['po_team_member'], FILTER_VALIDATE_INT)]) : [],
        'start_date' => isset($_GET['start_date']) ? $_GET['start_date'] : null,
        'end_date' => isset($_GET['end_date']) ? $_GET['end_date'] : null
    ];

    // Remove empty values from array filters
    foreach ($filters as $key => $value) {
        if (is_array($value)) {
            $filters[$key] = array_filter($value, function($v) {
                return $v !== '' && $v !== null;
            });
        }
    }

    // Build WHERE clause
    $where = "WHERE 1=1";
    $params = [];
    $types = "";

    // Status filter
    if (!empty($filters['status'])) {
        $placeholders = implode(',', array_fill(0, count($filters['status']), '?'));
        $where .= " AND st.status IN ($placeholders)";
        $types .= str_repeat('s', count($filters['status']));
        $params = array_merge($params, $filters['status']);
    }

    // Buyer head filter
    if (!empty($filters['buyer_head'])) {
        $placeholders = implode(',', array_fill(0, count($filters['buyer_head']), '?'));
        $where .= " AND pt.b_head IN ($placeholders)";
        $types .= str_repeat('i', count($filters['buyer_head']));
        $params = array_merge($params, $filters['buyer_head']);
    }

    // Buyer filter
    if (!empty($filters['buyer'])) {
        $placeholders = implode(',', array_fill(0, count($filters['buyer']), '?'));
        $where .= " AND pt.buyer IN ($placeholders)";
        $types .= str_repeat('i', count($filters['buyer']));
        $params = array_merge($params, $filters['buyer']);
    }

    // Supplier filter
    if (!empty($filters['supplier'])) {
        $placeholders = implode(',', array_fill(0, count($filters['supplier']), '?'));
        $where .= " AND pt.supplier_id IN ($placeholders)";
        $types .= str_repeat('i', count($filters['supplier']));
        $params = array_merge($params, $filters['supplier']);
    }

    // Purch filter
    if (!empty($filters['purchFilter'])) {
        $placeholders = implode(',', array_fill(0, count($filters['purchFilter']), '?'));
        $where .= " AND pt.purch_id IN ($placeholders)";
        $types .= str_repeat('i', count($filters['purchFilter']));
        $params = array_merge($params, $filters['purchFilter']);
    }

    // Category filter
    if (!empty($filters['category'])) {
        $placeholders = implode(',', array_fill(0, count($filters['category']), '?'));
        $where .= " AND EXISTS (
            SELECT 1 FROM catbasbh cb
            JOIN cat c ON c.maincat = cb.cat
            WHERE cb.user_id = pt.b_head AND c.maincat IN ($placeholders)
        )";
        $types .= str_repeat('s', count($filters['category']));
        $params = array_merge($params, $filters['category']);
    }

    // PO Team Member filter
    if (!empty($filters['po_team_member'])) {
        $placeholders = implode(',', array_fill(0, count($filters['po_team_member']), '?'));
        $where .= " AND ptm.po_team_member IN ($placeholders)";
        $types .= str_repeat('i', count($filters['po_team_member']));
        $params = array_merge($params, $filters['po_team_member']);
    }

    // Date range handling
    if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
        if (strtotime($filters['start_date']) > strtotime($filters['end_date'])) {
            throw new Exception("End date must be after start date");
        }
        $where .= " AND DATE(pt.created_at) BETWEEN ? AND ?";
        $types .= "ss";
        $params[] = $filters['start_date'];
        $params[] = $filters['end_date'];
    } elseif (!empty($filters['start_date'])) {
        $where .= " AND DATE(pt.created_at) >= ?";
        $types .= "s";
        $params[] = $filters['start_date'];
    } elseif (!empty($filters['end_date'])) {
        $where .= " AND DATE(pt.created_at) <= ?";
        $types .= "s";
        $params[] = $filters['end_date'];
    }

    // Role-based filtering
    if ($role == 'buyer') {
        $bheadQuery = "SELECT b_head FROM buyers_info WHERE buyer = ? LIMIT 1";
        $bheadStmt = $conn->prepare($bheadQuery);
        $bheadStmt->bind_param("i", $userid);
        $bheadStmt->execute();
        $bheadResult = $bheadStmt->get_result();
        $bheadRow = $bheadResult->fetch_assoc();
        $bheadId = $bheadRow['b_head'] ?? 0;
        $bheadStmt->close();

        $where .= " AND pt.buyer = ?";
        $types .= "i";
        $params[] = $userid;
    } elseif ($role == 'B_Head') {
        $where .= " AND pt.b_head = ?";
        $types .= "i";
        $params[] = $userid;
    } elseif ($role == 'PO_Team_Member') {
        $where .= " AND ptm.po_team_member = ?";
        $types .= "i";
        $params[] = $userid;
    }

    $search = isset($_GET['search']) ? trim($_GET['search']) : '';

    if (!empty($search)) {
    $searchConditions = [];
    $like = '%' . strtoupper($search) . '%';

    // 1. PO ID match if numeric
     if (ctype_digit($search)) {
        $searchConditions[] = "(pt.id = ? OR ptm.po_number = ?)";
        $types .= "ii";
        $params[] = (int)$search;
        $params[] = (int)$search;
    }

    // 2. Supplier/Agent name match
    $supplierIds = [];
    $stmt = $conn->prepare("SELECT id FROM suppliers WHERE UPPER(supplier) LIKE ? OR UPPER(agent) LIKE ?");
    $stmt->bind_param("ss", $like, $like);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_row()) {
        $supplierIds[] = $row[0];
    }
    $stmt->close();

    if (!empty($supplierIds)) {
        $placeholders = implode(',', array_fill(0, count($supplierIds), '?'));
        $searchConditions[] = "pt.supplier_id IN ($placeholders)";
        $types .= str_repeat("i", count($supplierIds));
        $params = array_merge($params, $supplierIds);
    }

    // 3. Category match via cat table
    $catIds = [];
    $stmt = $conn->prepare("SELECT id FROM cat WHERE UPPER(maincat) LIKE ?");
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_row()) {
        $catIds[] = $row[0];
    }
    $stmt->close();

    if (!empty($catIds)) {
        $placeholders = implode(',', array_fill(0, count($catIds), '?'));
        $searchConditions[] = "EXISTS (
            SELECT 1 FROM catbasbh cb
            JOIN cat c ON c.maincat = cb.cat
            WHERE cb.user_id = pt.b_head AND c.id IN ($placeholders)
        )";
        $types .= str_repeat("i", count($catIds));
        $params = array_merge($params, $catIds);
    }

    // 4. Username (buyer / b_head / po_team / po_head)
    $userIds = [];
    $stmt = $conn->prepare("SELECT id FROM users WHERE UPPER(username) LIKE ?");
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_row()) {
        $userIds[] = $row[0];
    }
    $stmt->close();

    if (!empty($userIds)) {
        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $searchConditions[] = "(pt.buyer IN ($placeholders) OR pt.b_head IN ($placeholders) OR pt.po_team IN ($placeholders))";
        $types .= str_repeat("i", count($userIds) * 3);
        $params = array_merge($params, $userIds, $userIds, $userIds);
    }

    if (!empty($searchConditions)) {
        $where .= " AND (" . implode(' OR ', $searchConditions) . ")";
    }
}


    // Count total records
    $countSql = "SELECT COUNT(DISTINCT pt.id) as total FROM po_tracking pt
                LEFT JOIN suppliers s ON pt.supplier_id = s.id
                LEFT JOIN status st ON pt.po_status = st.id
                LEFT JOIN po_team_member ptm ON ptm.ord_id = pt.id
                $where";

    $countStmt = $conn->prepare($countSql);
    if (!empty($params)) {
        $countStmt->bind_param($types, ...$params);
    }
    $countStmt->execute();
    $total = $countStmt->get_result()->fetch_assoc()['total'];
    $countStmt->close();

    // Main query with pagination
    $sql = "SELECT DISTINCT
                pt.id,
                pt.created_at,
                pt.status_1, pt.status_2, pt.status_3, pt.status_4, pt.status_5, pt.status_6,
                pt.po_status,
                bh.username AS b_head,
                b.username AS buyer,
                s.supplier,
                s.id as supplier_id,
                st.status AS status,
                ptm.buyername,
                pt.po_date as status_7,
                pt.po_date,
                po.username AS po_team_member,
                poh.username AS pohead,
                pm.name as purch_type,
                ptm.po_number,
                (SELECT GROUP_CONCAT(DISTINCT c.maincat SEPARATOR ', ') 
                 FROM catbasbh cb
                 JOIN cat c ON c.maincat = cb.cat
                 WHERE cb.user_id = pt.b_head) AS categories
            FROM po_tracking pt
            LEFT JOIN users bh ON pt.b_head = bh.id
            LEFT JOIN users b ON pt.buyer = b.id
            LEFT JOIN suppliers s ON pt.supplier_id = s.id
            LEFT JOIN status st ON pt.po_status = st.id
            LEFT JOIN po_team_member ptm ON ptm.ord_id = pt.id
            LEFT JOIN users po ON po.id = ptm.po_team_member
            LEFT JOIN users poh ON poh.id = pt.po_team
            LEFT JOIN purchase_master pm ON pm.id = pt.purch_id
            $where
            ORDER BY pt.created_at DESC
            LIMIT ?, ?";

    $dataStmt = $conn->prepare($sql);

    // Append pagination parameters
    $paramsWithPagination = array_merge($params, [$offset, $perPage]);
    $typesWithPagination = $types . "ii";

    $dataStmt->bind_param($typesWithPagination, ...$paramsWithPagination);
    $dataStmt->execute();
    $result = $dataStmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    $dataStmt->close();

    // Get ALL filtered data (without pagination) for charts
    $allDataSql = "SELECT DISTINCT
                    pt.id,
                    pt.created_at,
                    pt.status_1, pt.status_2, pt.status_3, pt.status_4, pt.status_5, pt.status_6,
                    pt.po_status,
                    bh.username AS b_head,
                    b.username AS buyer,
                    CASE 
    WHEN pt.supplier_id = 99999 THEN ns.supplier 
    ELSE s.supplier 
END AS supplier_name,
                    s.id as supplier_id,
                    st.status AS status,
                    ptm.buyername,
                    pt.po_date as status_7,
                    pt.po_date,
                    po.username AS po_team_member,
                    poh.username AS pohead,
                    ptm.po_number,
                    pm.name as purch_type
                FROM po_tracking pt
                LEFT JOIN users bh ON pt.b_head = bh.id
                LEFT JOIN users b ON pt.buyer = b.id
                LEFT JOIN suppliers s ON pt.supplier_id = s.id
                LEFT JOIN status st ON pt.po_status = st.id
                LEFT JOIN new_supplier ns ON pt.new_supplier = ns.id 
                LEFT JOIN po_team_member ptm ON ptm.ord_id = pt.id
                LEFT JOIN users po ON po.id = ptm.po_team_member
                LEFT JOIN users poh ON poh.id = pt.po_team
                LEFT JOIN purchase_master pm ON pm.id = pt.purch_id
                $where
                ORDER BY pt.created_at DESC";

    $allDataStmt = $conn->prepare($allDataSql);
    if (!empty($params)) {
        $allDataStmt->bind_param($types, ...$params);
    }
    $allDataStmt->execute();
    $allDataResult = $allDataStmt->get_result();

    $allFilteredData = [];
    while ($row = $allDataResult->fetch_assoc()) {
        $allFilteredData[] = $row;
    }
    $allDataStmt->close();

    // Get status options from database
    $statusStmt = $conn->query("SELECT DISTINCT status FROM status ORDER BY status ASC");
    $statusOptions = [];
    while ($row = $statusStmt->fetch_assoc()) {
        $statusOptions[] = $row['status'];
    }

    // Get filter options based on user role
    $options = [];

    // Suppliers (available to all roles)
    $supplierResult = $conn->query("SELECT id, supplier FROM suppliers ORDER BY supplier ASC");
    $options['supplier_options'] = [];
    while ($row = $supplierResult->fetch_assoc()) {
        $options['supplier_options'][] = $row;
    }

    $purchFilterresult = $conn->query("SELECT id, name FROM purchase_master ORDER BY name ASC");
    $options['purch_options'] = [];
    if ($purchFilterresult && $purchFilterresult->num_rows > 0) {
        while ($row = $purchFilterresult->fetch_assoc()) {
            $options['purch_options'][] = $row;
        }
    }

    if ($role == 'buyer') {
        if (!isset($bheadId)) {
            $bheadQuery = "SELECT b_head FROM buyers_info WHERE buyer = $userid LIMIT 1";
            $bheadResult = $conn->query($bheadQuery);
            $bheadRow = $bheadResult->fetch_assoc();
            $bheadId = $bheadRow['b_head'] ?? 0;
        }

        // Categories mapped to this buyer through catbasbh
        $catQuery = "SELECT c.id, c.maincat FROM catbasbh cb
                     JOIN cat c ON c.maincat = cb.cat 
                     WHERE cb.user_id = $bheadId
                     ORDER BY c.maincat ASC";
        $catResult = $conn->query($catQuery);
        $options['category_options'] = [];
        while ($row = $catResult->fetch_assoc()) {
            $options['category_options'][] = $row;
        }

        // Get buyer head details using the ID we found
        $options['buyer_head_options'] = [];
        if ($bheadId > 0) {
            $bheadDetailsQuery = "SELECT id, username FROM users WHERE id = $bheadId";
            $bheadDetailsResult = $conn->query($bheadDetailsQuery);
            while ($row = $bheadDetailsResult->fetch_assoc()) {
                $options['buyer_head_options'][] = $row;
            }
        }

        // PO Team Members (all)
        $poTeamResult = $conn->query("SELECT id, username FROM users WHERE role = 'PO_Team_Member' ORDER BY username ASC");
        $options['po_team_member_options'] = [];
        while ($row = $poTeamResult->fetch_assoc()) {
            $options['po_team_member_options'][] = $row;
        }
    } elseif ($role == 'B_Head') {
        // All categories assigned to this buyer head
        $catQuery = "SELECT c.id, c.maincat FROM catbasbh cb
                     JOIN cat c ON c.maincat = cb.cat 
                     WHERE cb.user_id = $userid
                     ORDER BY c.maincat ASC";
        $catResult = $conn->query($catQuery);
        $options['category_options'] = [];
        while ($row = $catResult->fetch_assoc()) {
            $options['category_options'][] = $row;
        }

        // Buyers under this buyer head (from buyers_info table)
        $buyerQuery = "SELECT u.username, u.id FROM `buyers_info` bi 
                       LEFT JOIN users u on u.id = bi.buyer 
                       WHERE bi.b_head= $userid
                       ORDER BY u.username ASC";
        $buyerResult = $conn->query($buyerQuery);
        $options['buyer_options'] = [];
        while ($row = $buyerResult->fetch_assoc()) {
            $options['buyer_options'][] = $row;
        }

        // Show only self in buyer heads
        $selfQuery = "SELECT id, username FROM users WHERE id = $userid";
        $selfResult = $conn->query($selfQuery);
        $options['buyer_head_options'] = [];
        while ($row = $selfResult->fetch_assoc()) {
            $options['buyer_head_options'][] = $row;
        }

        // PO Team Members (all)
        $poTeamResult = $conn->query("SELECT id, username FROM users WHERE role = 'PO_Team_Member' ORDER BY username ASC");
        $options['po_team_member_options'] = [];
        while ($row = $poTeamResult->fetch_assoc()) {
            $options['po_team_member_options'][] = $row;
        }
    } elseif ($role == 'PO_Team_Member') {
        // All categories
        $catResult = $conn->query("SELECT id, maincat FROM cat ORDER BY maincat ASC");
        $options['category_options'] = [];
        while ($row = $catResult->fetch_assoc()) {
            $options['category_options'][] = $row;
        }

        // All buyers
        $buyerResult = $conn->query("SELECT id, username FROM users WHERE role = 'buyer' ORDER BY username ASC");
        $options['buyer_options'] = [];
        while ($row = $buyerResult->fetch_assoc()) {
            $options['buyer_options'][] = $row;
        }

        // All buyer heads
        $bheadResult = $conn->query("SELECT id, username FROM users WHERE role = 'B_Head' ORDER BY username ASC");
        $options['buyer_head_options'] = [];
        while ($row = $bheadResult->fetch_assoc()) {
            $options['buyer_head_options'][] = $row;
        }

        // PO Team Members (only self)
        $selfQuery = "SELECT id, username FROM users WHERE id = $userid";
        $selfResult = $conn->query($selfQuery);
        $options['po_team_member_options'] = [];
        while ($row = $selfResult->fetch_assoc()) {
            $options['po_team_member_options'][] = $row;
        }
    } elseif ($role == 'admin' || $role == 'PO_Team') {
        // For admin, show everything
        $catResult = $conn->query("SELECT id, maincat FROM cat ORDER BY maincat ASC");
        $options['category_options'] = [];
        while ($row = $catResult->fetch_assoc()) {
            $options['category_options'][] = $row;
        }

        $buyerResult = $conn->query("SELECT id, username FROM users WHERE role = 'buyer' ORDER BY username ASC");
        $options['buyer_options'] = [];
        while ($row = $buyerResult->fetch_assoc()) {
            $options['buyer_options'][] = $row;
        }

        $bheadResult = $conn->query("SELECT id, username FROM users WHERE role = 'B_Head' ORDER BY username ASC");
        $options['buyer_head_options'] = [];
        while ($row = $bheadResult->fetch_assoc()) {
            $options['buyer_head_options'][] = $row;
        }

        $poTeamResult = $conn->query("SELECT id, username FROM users WHERE role = 'PO_Team_Member' ORDER BY username ASC");
        $options['po_team_member_options'] = [];
        while ($row = $poTeamResult->fetch_assoc()) {
            $options['po_team_member_options'][] = $row;
        }
    }

    $options['status_options'] = $statusOptions;

    // Calculate statistics with time buckets
    $stats = [
        'total_orders' => $total,
        'avg_processing_time' => 0,
        'completed_count' => 0,
        'status_distribution' => [],
        'time_buckets' => [
            '0-30 mins' => 0,
            '30-60 mins' => 0,
            '1-3 hours' => 0,
            '3-6 hours' => 0,
            '6-12 hours' => 0,
            '12-24 hours' => 0,
            '1-2 days' => 0,
            '2-3 days' => 0,
            '3-7 days' => 0,
            '1-2 weeks' => 0,
            '2+ weeks' => 0
        ],
        'status_time_buckets' => []
    ];

    if (!empty($allFilteredData)) {
        $totalMinutes = 0;
        $completedCount = 0;
        $statusCounts = [];

        foreach ($allFilteredData as $row) {
            $status = $row['status'] ?? 'Unknown';
            $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;

            if (!empty($row['status_7']) && !empty($row['created_at'])) {
                try {
                    $createdAt = new DateTime($row['created_at']);
                    $completedAt = new DateTime($row['status_7']);
                    
                    if ($completedAt > $createdAt) {
                        $interval = $createdAt->diff($completedAt);
                        $minutes = $interval->days * 1440 + $interval->h * 60 + $interval->i;
                        $totalMinutes += $minutes;
                        $completedCount++;

                        // Determine time bucket
                        if ($minutes <= 30) {
                            $bucket = '0-30 mins';
                        } elseif ($minutes <= 60) {
                            $bucket = '30-60 mins';
                        } elseif ($minutes <= 180) {
                            $bucket = '1-3 hours';
                        } elseif ($minutes <= 360) {
                            $bucket = '3-6 hours';
                        } elseif ($minutes <= 720) {
                            $bucket = '6-12 hours';
                        } elseif ($minutes <= 1440) {
                            $bucket = '12-24 hours';
                        } elseif ($minutes <= 2880) {
                            $bucket = '1-2 days';
                        } elseif ($minutes <= 4320) {
                            $bucket = '2-3 days';
                        } elseif ($minutes <= 10080) {
                            $bucket = '3-7 days';
                        } elseif ($minutes <= 20160) {
                            $bucket = '1-2 weeks';
                        } else {
                            $bucket = '2+ weeks';
                        }
                        
                        $stats['time_buckets'][$bucket]++;
                        
                        // Group by status
                        if (!isset($stats['status_time_buckets'][$status])) {
                            $stats['status_time_buckets'][$status] = array_fill_keys(array_keys($stats['time_buckets']), 0);
                        }
                        $stats['status_time_buckets'][$status][$bucket]++;
                    }
                } catch (Exception $e) {
                    error_log("Date parsing error: " . $e->getMessage());
                    continue;
                }
            }
        }

        // Calculate average processing time
        if ($completedCount > 0) {
    $avgMinutes = $totalMinutes / $completedCount;
    // Convert to hours if more than 72 hours (3 days)
    if ($avgMinutes > (72 * 60)) {
        $stats['avg_processing_time'] = round($avgMinutes / 60, 2); // Return in hours
        $stats['avg_time_unit'] = 'hours';
    } else {
        $stats['avg_processing_time'] = round($avgMinutes / 60 / 24, 2); // Return in days
        $stats['avg_time_unit'] = 'days';
    }
} 
    
        
        $stats['completed_count'] = $completedCount;
        $stats['status_distribution'] = $statusCounts;
    }

    // Response
    echo json_encode([
        'success' => true,
        'data' => $data,
        'all_filtered_data' => $allFilteredData,
        'options' => $options,
        'stats' => $stats,
        'pagination' => [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => ceil($total / $perPage)
        ],
        'filters' => $filters
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'trace' => $e->getTrace()
    ]);
}
?>