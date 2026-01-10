<?php
session_start();
require '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'User not authenticated']);
    exit;
}

// Get filters and pagination
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;

// Check if supplier_code column exists
$checkColumn = $conn->query("SHOW COLUMNS FROM supplier_requests LIKE 'supplier_code'");
$hasSupplierCode = $checkColumn && $checkColumn->num_rows > 0;

$sql = "SELECT sr.*, u.username AS created_by_name
        FROM supplier_requests sr
        LEFT JOIN users u ON sr.created_by = u.id
        WHERE 1=1";

$conditions = [];
$param_types = '';
$values = [];

// Status filter - check if supplier_code exists, if not, all are considered pending
if ($hasSupplierCode) {
    if ($status_filter === 'pending') {
        $conditions[] = "sr.supplier_code IS NULL OR sr.supplier_code = ''";
    } elseif ($status_filter === 'created') {
        $conditions[] = "sr.supplier_code IS NOT NULL AND sr.supplier_code != ''";
    }
} else {
    // If supplier_code column doesn't exist, all are pending
    if ($status_filter === 'created') {
        // No created items if column doesn't exist
        $conditions[] = "1=0";
    }
    // If pending or empty, show all
}

// Search filter
if (!empty($search)) {
    $search_like = "%$search%";
    $searchFields = ['sr.supplier', 'sr.gst_no', 'sr.pan_no', 'sr.mobile', 'sr.email', 'sr.agent', 'sr.city'];
    if ($hasSupplierCode) {
        $searchFields[] = 'sr.supplier_code';
    }
    
    $searchConditions = [];
    foreach ($searchFields as $field) {
        $searchConditions[] = "$field LIKE ?";
        $param_types .= 's';
        $values[] = $search_like;
    }
    
    $conditions[] = "(" . implode(" OR ", $searchConditions) . ")";
}

if (!empty($conditions)) {
    $sql .= " AND " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY sr.created_at DESC LIMIT ?, ?";
$param_types .= "ii";
$values[] = $offset;
$values[] = $limit;

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['error' => 'Query preparation failed: ' . $conn->error]);
    exit;
}

if (!empty($values)) {
    $stmt->bind_param($param_types, ...$values);
}

$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    // Ensure supplier_code exists in response (default to null if column doesn't exist)
    if (!$hasSupplierCode) {
        $row['supplier_code'] = null;
    }
    $data[] = $row;
}

echo json_encode($data);
$stmt->close();
$conn->close();
?>

