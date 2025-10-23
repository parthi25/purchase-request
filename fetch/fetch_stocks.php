<?php
require "../config/db.php";
require "../config/response.php"; // centralized sendResponse

try {
    // Fetch all products that have stock
    $stmt = $conn->prepare("
        SELECT DISTINCT p.id, p.name, p.lpp
        FROM pr_product p
        INNER JOIN stocks s ON p.id = s.product_id
        WHERE s.qty > 0
        ORDER BY p.name ASC
    ");

    if (!$stmt) {
        sendResponse(500, "error", "Database query preparation failed");
    }

    $stmt->execute();
    $res = $stmt->get_result();

    $data = [];
    while ($r = $res->fetch_assoc()) {
        $data[] = [
            "id" => $r['id'],
            "name" => $r['name'],
            "lpp" => $r['lpp']
        ];
    }

    sendResponse(200, "success", "Products with stock retrieved successfully", $data);

    $stmt->close();
} catch (Throwable $th) {
    error_log("Error in fetch_products_with_stock.php: " . $th->getMessage());
    sendResponse(500, "error", "Internal server error");
} finally {
    $conn->close();
}
