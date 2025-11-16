<?php
// api.php
include '../config/db.php';
include '../config/response.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    exit(0);
}

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'getSuppliers':
            getSuppliers($conn);
            break;
        case 'getProducts':
            getProducts($conn);
            break;
        case 'getSupplierProducts':
            getSupplierProducts($conn);
            break;
        default:
            sendResponse(400, "error", "Invalid action");
    }
} catch (Throwable $th) {
    error_log("Error in api.php: " . $th->getMessage());
    sendResponse(500, "error", "Internal server error");
} finally {
    $conn->close();
}

function getSuppliers($conn) {
    $result = $conn->query("
        SELECT  Distinct s.id, s.supplier as supplier_name, s.supplier_id 
        FROM pr_product p left join suppliers s on s.supplier_id = p.supplier_code
        ORDER BY s.supplier
    ");
    
    $suppliers = [];
    while ($row = $result->fetch_assoc()) {
        $suppliers[] = $row;
    }
    
    sendResponse(200, "success", "Suppliers retrieved successfully", $suppliers);
}

function getProducts($conn) {
    $result = $conn->query("
        SELECT DISTINCT name, supplier_code 
        FROM pr_product 
        ORDER BY name
    ");
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    
    sendResponse(200, "success", "Products retrieved successfully", $products);
}

function getSupplierProducts($conn) {
    $supplier_code = $_GET['supplier_code'] ?? null;
    $supplier_id = $_GET['supplier_id'] ?? null;

    if ($supplier_code) {
        $stmt = $conn->prepare("
            SELECT id, name, rsp, lpp, sub AS subcode, created_at, updated_at, supplier_code
            FROM pr_product p 
            LEFT JOIN suppliers s ON s.supplier_id = p.supplier_code
            WHERE p.supplier_code = ?
        ");
        $stmt->bind_param("s", $supplier_code);
        $stmt->execute();
        $res = $stmt->get_result();
    } elseif ($supplier_id) {
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
            FROM pr_product p 
            LEFT JOIN suppliers s ON s.supplier_id = p.supplier_code
            WHERE p.supplier_code = ?
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
            "plants" => $plants,
        ];
    }

    sendResponse(200, "success", "Products retrieved successfully", $data);
}
?>