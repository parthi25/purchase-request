<?php
include '../../config/db.php';
include '../../config/response.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $result = $conn->query("
        SELECT id, supplier AS supplier_name, supplier_id 
        FROM suppliers 
        ORDER BY supplier
    ");

    $suppliers = $result->fetch_all(MYSQLI_ASSOC);

    sendResponse(200, "success", "Suppliers retrieved successfully", $suppliers);
} catch (Throwable $th) {
    error_log("Error in suppliers.php: " . $th->getMessage());
    sendResponse(500, "error", "Internal server error");
} finally {
    $conn->close();
}
