<?php
session_start();
require '../config/db.php';
require '../config/response.php';

if (!isset($_SESSION["user_id"])) {
    sendResponse(401, "error", "Unauthorized");
}

$userid = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? '';

// Build query to get grouped summary data
$sql = "SELECT 
            COALESCE(b.username, 'Unassigned') AS buyer_head,
            COALESCE(bu.username, 'Unassigned') AS buyer,
            COALESCE(c.maincat, 'Uncategorized') AS category,
            GROUP_CONCAT(DISTINCT pt.username ORDER BY pt.username SEPARATOR ', ') AS po_heads,
            GROUP_CONCAT(DISTINCT upo.username ORDER BY upo.username SEPARATOR ', ') AS po_team_members,
            COUNT(CASE WHEN p.po_status = 1 THEN 1 END) AS status_1_count,
            COUNT(CASE WHEN p.po_status = 2 THEN 1 END) AS status_2_count,
            COUNT(CASE WHEN p.po_status = 3 THEN 1 END) AS status_3_count,
            COUNT(CASE WHEN p.po_status = 4 THEN 1 END) AS status_4_count,
            COUNT(CASE WHEN p.po_status = 5 THEN 1 END) AS status_5_count,
            COUNT(CASE WHEN p.po_status = 6 THEN 1 END) AS status_6_count,
            COUNT(CASE WHEN p.po_status = 7 THEN 1 END) AS status_7_count,
            COUNT(CASE WHEN p.po_status = 8 THEN 1 END) AS status_8_count,
            COUNT(CASE WHEN p.po_status = 9 THEN 1 END) AS status_9_count,
            COUNT(*) AS total_count
        FROM purchase_requests p
        LEFT JOIN users b ON p.b_head = b.id
        LEFT JOIN users bu ON p.buyer = bu.id
        LEFT JOIN users pt ON p.po_team = pt.id
        LEFT JOIN pr_assignments ptm ON p.id = ptm.ord_id
        LEFT JOIN users upo ON ptm.po_team_member = upo.id
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE 1=1";

$params = [];
$types = "";

// Role-based filtering
switch ($user_role) {
    case 'admin':
        break;
    case 'B_Head':
        $sql .= " AND (p.b_head = ? OR p.created_by = ?)";
        $params[] = $userid;
        $params[] = $userid;
        $types .= "ii";
        break;
    case 'buyer':
        $sql .= " AND (p.buyer = ? OR p.created_by = ?)";
        $params[] = $userid;
        $params[] = $userid;
        $types .= "ii";
        break;
    case 'PO_Team':
    case 'PO_Team_Member':
        $sql .= " AND (p.po_team = ? OR ptm.po_team_member = ?)";
        $params[] = $userid;
        $params[] = $userid;
        $types .= "ii";
        break;
}

$sql .= " GROUP BY p.b_head, p.buyer, p.category_id, b.username, bu.username, c.maincat
          ORDER BY COALESCE(b.username, 'ZZZ'), COALESCE(bu.username, 'ZZZ'), COALESCE(c.maincat, 'ZZZ')";

// Increase GROUP_CONCAT max length
$conn->query("SET SESSION group_concat_max_len = 10000");

$stmt = $conn->prepare($sql);
if (!$stmt) {
    sendResponse(500, "error", "Query preparation failed: " . $conn->error);
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = [
        'buyer_head' => $row['buyer_head'],
        'buyer' => $row['buyer'],
        'category' => $row['category'],
        'po_heads' => $row['po_heads'] ?: '-',
        'po_team_members' => $row['po_team_members'] ?: '-',
        'status_1_count' => (int)$row['status_1_count'],
        'status_2_count' => (int)$row['status_2_count'],
        'status_3_count' => (int)$row['status_3_count'],
        'status_4_count' => (int)$row['status_4_count'],
        'status_5_count' => (int)$row['status_5_count'],
        'status_6_count' => (int)$row['status_6_count'],
        'status_7_count' => (int)$row['status_7_count'],
        'status_8_count' => (int)$row['status_8_count'],
        'status_9_count' => (int)$row['status_9_count'],
        'total_count' => (int)$row['total_count']
    ];
}

// Get status names for headers
$statusQuery = "SELECT id, status FROM pr_statuses ORDER BY id";
$statusResult = $conn->query($statusQuery);
$statusNames = [];
while ($statusRow = $statusResult->fetch_assoc()) {
    $statusNames[$statusRow['id']] = $statusRow['status'];
}

$stmt->close();
$conn->close();

sendResponse(200, "success", "Status report retrieved successfully", [
    'data' => $data,
    'status_names' => $statusNames
]);

