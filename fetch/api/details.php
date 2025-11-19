<?php
include '../../config/db.php';
include '../../config/response.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $supplier_id = $_GET['supplier_id'] ?? null;
    $product_id  = $_GET['product_id'] ?? null;

    if ($supplier_id) {
        // Get supplier_id → supplier_code
        $stmt = $conn->prepare("SELECT supplier_id, supplier FROM suppliers WHERE id = ?");
        $stmt->bind_param("i", $supplier_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $supplier = $result->fetch_assoc();

        if (!$supplier) sendResponse(404, "error", "Supplier not found");

        $stmt = $conn->prepare("
            SELECT id, name, rsp, lpp, supplier_code, uom
            FROM pr_product 
            WHERE supplier_code = ?
        ");
        $stmt->bind_param("s", $supplier['supplier_id']);
        $stmt->execute();
        $res = $stmt->get_result();

        $data = [];
        while ($row = $res->fetch_assoc()) {
            $row['supplier_name'] = $supplier['supplier'];
            $row['plants'] = getProductStocks($conn, $row['id']);
            $data[] = $row;
        }

        sendResponse(200, "success", "Supplier products retrieved successfully", $data);
    }

    elseif ($product_id) {
        $stmt = $conn->prepare("
            SELECT p.id, p.name, p.rsp, p.lpp, p.supplier_code, p.uom, s.supplier AS supplier_name
            FROM pr_product p
            LEFT JOIN suppliers s ON s.supplier_id = p.supplier_code
            WHERE p.id = ?
        ");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $product = $res->fetch_assoc();

        if (!$product) sendResponse(404, "error", "Product not found");

        $product['plants'] = getProductStocks($conn, $product_id);

        sendResponse(200, "success", "Product details retrieved successfully", $product);
    }

    else {
        sendResponse(400, "error", "supplier_id or product_id is required");
    }

} catch (Throwable $th) {
    error_log("Error in details.php: " . $th->getMessage());
    sendResponse(500, "error", "Internal server error");
} finally {
    $conn->close();
}


function getProductStocks($conn, $product_id) {
    $stockStmt = $conn->prepare("
        SELECT p.name AS plant_name, s.qty AS quantity 
        FROM stocks s 
        LEFT JOIN rc_vendors_db.plants p ON p.plant_code = s.plant_code  
        WHERE s.product_id = ? AND s.qty > 0
    ");
    $stockStmt->bind_param("i", $product_id);
    $stockStmt->execute();
    $res = $stockStmt->get_result();

    $plants = [];
    while ($row = $res->fetch_assoc()) {
        // Only include plants with quantity > 0
        if (floatval($row['quantity']) > 0) {
            $plants[] = $row;
        }
    }

    return $plants;
}
