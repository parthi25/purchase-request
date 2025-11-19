<?php
include '../../config/db.php';
include '../../config/response.php';

$search = isset($_POST['search']) ? trim($_POST['search']) . '%' : null;
if (!$search) {
    sendResponse(400, "error", "Search parameter is required");
}

try {
    $stmt = $conn->prepare("SELECT id, name FROM pr_product WHERE name LIKE ? LIMIT 10");
    if (!$stmt) {
        sendResponse(500, "error", "Database query preparation failed");
    }

    $stmt->bind_param("s", $search);
    $stmt->execute();
    $result = $stmt->get_result();

    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }

    sendResponse(200, "success", count($products) ? "Products found" : "No products found", $products);
    $stmt->close();

} catch (Exception $e) {
    error_log("Error in search-product.php: " . $e->getMessage());
    sendResponse(500, "error", "Internal server error");
} finally {
    $conn->close();
}
?>

