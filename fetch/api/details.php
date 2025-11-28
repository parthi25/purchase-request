<?php
include '../../config/db.php';
include '../../config/response.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Check database connection
if (!isset($conn) || !$conn || $conn->connect_error) {
    error_log("Database connection failed in details.php");
    sendResponse(200, "success", "No data available", []);
    exit;
}

try {
    $supplier_id = $_GET['supplier_id'] ?? null;
    $product_id  = $_GET['product_id'] ?? null;

    if ($supplier_id) {
        // Get supplier_id → supplier_code
        $stmt = $conn->prepare("SELECT supplier_id, supplier FROM suppliers WHERE id = ?");
        
        if (!$stmt) {
            error_log("Error preparing supplier query: " . $conn->error);
            sendResponse(200, "success", "No data available", []);
        }
        
        $stmt->bind_param("i", $supplier_id);
        
        if (!$stmt->execute()) {
            error_log("Error executing supplier query: " . $stmt->error);
            sendResponse(200, "success", "No data available", []);
        }
        
        $result = $stmt->get_result();
        $supplier = $result->fetch_assoc();
        $stmt->close();

        if (!$supplier) {
            // Return empty data instead of 404 error
            sendResponse(200, "success", "No data available", []);
        }

        $stmt = $conn->prepare("
            SELECT id, name, rsp, lpp, supplier_code, uom
            FROM pr_product 
            WHERE supplier_code = ?
        ");
        
        if (!$stmt) {
            error_log("Error preparing product query for supplier: " . $conn->error);
            sendResponse(200, "success", "No data available", []);
        }
        
        $stmt->bind_param("s", $supplier['supplier_id']);
        
        if (!$stmt->execute()) {
            error_log("Error executing product query for supplier: " . $stmt->error);
            $stmt->close();
            sendResponse(200, "success", "No data available", []);
        }
        
        $res = $stmt->get_result();

        $data = [];
        while ($row = $res->fetch_assoc()) {
            $row['supplier_name'] = $supplier['supplier'];
            // Get product stocks with error handling
            try {
                $row['plants'] = getProductStocks($conn, $row['id']);
            } catch (Exception $e) {
                error_log("Error getting product stocks for product {$row['id']}: " . $e->getMessage());
                $row['plants'] = []; // Set empty array if stocks fetch fails
            }
            $data[] = $row;
        }
        
        $stmt->close();

        sendResponse(200, "success", "Supplier products retrieved successfully", $data);
    }

    elseif ($product_id) {
        $stmt = $conn->prepare("
            SELECT p.id, p.name, p.rsp, p.lpp, p.supplier_code, p.uom, s.supplier AS supplier_name
            FROM pr_product p
            LEFT JOIN suppliers s ON s.supplier_id = p.supplier_code
            WHERE p.id = ?
        ");
        
        if (!$stmt) {
            error_log("Error preparing product query: " . $conn->error);
            sendResponse(200, "success", "No data available", []);
        }
        
        $stmt->bind_param("i", $product_id);
        
        if (!$stmt->execute()) {
            error_log("Error executing product query: " . $stmt->error);
            $stmt->close();
            sendResponse(200, "success", "No data available", []);
        }
        
        $res = $stmt->get_result();
        $product = $res->fetch_assoc();
        $stmt->close();

        if (!$product || empty($product)) {
            // Return empty data instead of 404 error
            sendResponse(200, "success", "No data available", []);
        }

        // Ensure all fields have default values if null
        $product['id'] = $product['id'] ?? null;
        $product['name'] = $product['name'] ?? '';
        $product['rsp'] = $product['rsp'] ?? 0.00;
        $product['lpp'] = $product['lpp'] ?? 0.00;
        $product['supplier_code'] = $product['supplier_code'] ?? '';
        $product['uom'] = $product['uom'] ?? '';
        $product['supplier_name'] = $product['supplier_name'] ?? '';

        // Get product stocks with error handling
        try {
            $product['plants'] = getProductStocks($conn, $product_id);
        } catch (Exception $e) {
            error_log("Error getting product stocks: " . $e->getMessage());
            $product['plants'] = []; // Set empty array if stocks fetch fails
        }

        sendResponse(200, "success", "Product details retrieved successfully", $product);
    }

    else {
        sendResponse(400, "error", "supplier_id or product_id is required");
    }

} catch (Throwable $th) {
    error_log("Error in details.php: " . $th->getMessage());
    error_log("Stack trace: " . $th->getTraceAsString());
    // Return empty data instead of error to show "no data available"
    sendResponse(200, "success", "No data available", []);
} finally {
    if (isset($conn) && $conn) {
        $conn->close();
    }
}


function getProductStocks($conn, $product_id) {
    try {
        $stockStmt = $conn->prepare("
            SELECT p.name AS plant_name, s.qty AS quantity 
            FROM stocks s 
            LEFT JOIN rc_vendors_db.plants p ON p.plant_code = s.plant_code  
            WHERE s.product_id = ? AND s.qty > 0
        ");
        
        if (!$stockStmt) {
            error_log("Error preparing stock query: " . $conn->error);
            return []; // Return empty array instead of throwing error
        }
        
        $stockStmt->bind_param("i", $product_id);
        
        if (!$stockStmt->execute()) {
            error_log("Error executing stock query: " . $stockStmt->error);
            $stockStmt->close();
            return []; // Return empty array instead of throwing error
        }
        
        $res = $stockStmt->get_result();
        $plants = [];
        
        while ($row = $res->fetch_assoc()) {
            // Only include plants with quantity > 0
            if (floatval($row['quantity']) > 0) {
                $plants[] = $row;
            }
        }
        
        $stockStmt->close();
        return $plants;
    } catch (Exception $e) {
        error_log("Exception in getProductStocks: " . $e->getMessage());
        return []; // Return empty array on any exception
    }
}
