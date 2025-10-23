<?php
require "../config/db.php";
require "../config/sendResponse.php"; // your unified response helper

$supplier_code = $_GET['supplier_code'] ?? $_POST['supplier_code'] ?? null;
$supplier_id = $_GET['supplier_id'] ?? $_POST['supplier_id'] ?? null;

try {
    if ($supplier_code) {
        // Direct supplier_code case
        $stmt = $conn->prepare("
            SELECT id, name, rsp, lpp, sub AS subcode, created_at, updated_at, supplier_code
            FROM pr_product 
            WHERE supplier_code = ?
        ");
        $stmt->bind_param("s", $supplier_code);
        $stmt->execute();
        $res = $stmt->get_result();
    } elseif ($supplier_id) {
        // supplier_id case → find supplier_code first
        $stmt = $conn->prepare("SELECT supplier_id FROM suppliers WHERE id = ?");
        $stmt->bind_param("i", $supplier_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $supplier = $result->fetch_assoc();

        if (!$supplier) {
            sendResponse(404, "error", "Supplier not found");
        }

        $stmt = $conn->prepare("
            SELECT id, name, rsp, lpp, sub AS subcode, created_at, updated_at, supplier_code
            FROM pr_product 
            WHERE supplier_code = ?
        ");
        $stmt->bind_param("s", $supplier['supplier_id']);
        $stmt->execute();
        $res = $stmt->get_result();
    } else {
        sendResponse(400, "error", "supplier_code or supplier_id is required");
    }

    $data = [];

    while ($r = $res->fetch_assoc()) {
        $product_id = $r['id'];

        // Fetch plant-wise stock for this product
        $stockStmt = $conn->prepare("
            SELECT p.name AS plant_name, s.qty AS quantity 
            FROM stocks s 
            LEFT JOIN rc_vendors_db.plants p ON p.plant_code = s.plant_code  
            WHERE s.product_id = ?
        ");
        $stockStmt->bind_param("i", $product_id);
        $stockStmt->execute();
        $stockRes = $stockStmt->get_result();

        $plants = [];
        $total_qty = 0;

        while ($s = $stockRes->fetch_assoc()) {
            $plants[] = $s;
            $total_qty += (float) $s['quantity'];
        }

        $data[] = [
            "product_name" => $r['name'],
            "supplier_code" => $r['supplier_code'],
            "last_purchase_price" => $r['lpp'],
            "rsp" => $r['rsp'],
            "total_qty" => $total_qty,
            "plants" => $plants
        ];
    }

    sendResponse(200, "success", "Products retrieved successfully", $data);

} catch (Throwable $th) {
    error_log("Error in fetch_products.php: " . $th->getMessage());
    sendResponse(500, "error", "Internal server error");
} finally {
    $conn->close();
}
