<?php
include '../../config/db.php';
include '../../config/response.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $result = $conn->query("
        SELECT id, name 
        FROM pr_product 
        ORDER BY name
    ");

    $products = $result->fetch_all(MYSQLI_ASSOC);

    sendResponse(200, "success", "Products retrieved successfully", $products);
} catch (Throwable $th) {
    error_log("Error in products.php: " . $th->getMessage());
    sendResponse(500, "error", "Internal server error");
} finally {
    $conn->close();
}
