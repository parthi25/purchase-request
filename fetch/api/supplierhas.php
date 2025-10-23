<?php
require "../config/db.php";
require "../../config/response.php"; // This contains sendResponse()

header("Content-Type: application/json");

$supplier_code = $_GET['supplier_code'] ?? $_POST['supplier_code'] ?? null;
$supplier_id = $_GET['supplier_id'] ?? $_POST['supplier_id'] ?? null;

try {
    if ($supplier_code) {
        // Fetch products by supplier_code
        $stmt = $conn->prepare("
            SELECT id, name, rsp, lpp, created_at, sub AS subcode, updated_at, supplier_code 
            FROM pr_product 
            WHERE supplier_code = ?
        ");
        $stmt->bind_param("s", $supplier_code);
        $stmt->execute();
        $res = $stmt->get_result();

        $data = [];
        while ($row = $res->fetch_assoc())
            $data[] = $row;

        if (empty($data)) {
            sendResponse(404, "error", "No products found for supplier code: $supplier_code");
        } else {
            sendResponse(200, "success", "Products retrieved successfully for supplier code", $data);
        }

    } elseif ($supplier_id) {
        // Get supplier_code from supplier_id
        $stmt1 = $conn->prepare("SELECT supplier_id FROM suppliers WHERE id = ?");
        $stmt1->bind_param("i", $supplier_id);
        $stmt1->execute();
        $res1 = $stmt1->get_result();

        if ($res1->num_rows === 0) {
            sendResponse(404, "error", "Supplier not found with ID: $supplier_id");
        }

        $found_supplier_code = $res1->fetch_assoc()['supplier_id'];

        // Fetch products using supplier_code
        $stmt2 = $conn->prepare("
            SELECT id, name, rsp, lpp, created_at, sub AS subcode, updated_at, supplier_code 
            FROM pr_product 
            WHERE supplier_code = ?
        ");
        $stmt2->bind_param("s", $found_supplier_code);
        $stmt2->execute();
        $res2 = $stmt2->get_result();

        $data = [];
        while ($row = $res2->fetch_assoc())
            $data[] = $row;

        if (empty($data)) {
            sendResponse(404, "error", "No products found for supplier ID: $supplier_id");
        } else {
            sendResponse(200, "success", "Products retrieved successfully for supplier ID", $data);
        }

    } else {
        sendResponse(400, "error", "Either supplier_code or supplier_id parameter is required");
    }

} catch (Exception $e) {
    error_log("Error in supplierhas.php: " . $e->getMessage());
    sendResponse(500, "error", "Internal server error");
} finally {
    $conn->close();
}
