<?php
session_start();
require '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Check if supplier_code column exists
$checkColumn = $conn->query("SHOW COLUMNS FROM supplier_requests LIKE 'supplier_code'");
$hasSupplierCode = $checkColumn && $checkColumn->num_rows > 0;

if ($hasSupplierCode) {
    $sql = "
        SELECT
            COUNT(*) AS total,
            SUM(CASE 
                WHEN supplier_code IS NULL OR supplier_code = '' THEN 1 
                ELSE 0 
            END) AS pending,
            SUM(CASE 
                WHEN supplier_code IS NOT NULL AND supplier_code <> '' THEN 1 
                ELSE 0 
            END) AS created
        FROM supplier_requests
    ";
} else {
    // If supplier_code doesn't exist, all are pending
    $sql = "
        SELECT
            COUNT(*) AS total,
            COUNT(*) AS pending,
            0 AS created
        FROM supplier_requests
    ";
}

$result = $conn->query($sql);

if (!$result) {
    echo json_encode(['error' => $conn->error]);
    exit;
}

$data = $result->fetch_assoc();
echo json_encode($data);
$conn->close();
?>

