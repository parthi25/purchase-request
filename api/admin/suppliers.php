<?php
session_start();
require '../../config/db.php';
include '../../config/response.php';

// Check if user is admin/super_admin/master
$allowedRoles = ['admin', 'super_admin', 'master'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], $allowedRoles)) {
    sendResponse(403, "error", "Unauthorized access");
}


$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Create operation
if ($action === 'create') {
    try {
        $supplier = $_POST['supplier_name'] ?? null;
        $agent = $_POST['agent'] ?? null;
        $street = $_POST['street'] ?? null;
        $city = $_POST['city'] ?? null;
        $postal_code = $_POST['postal_code'] ?? null;
        $region = $_POST['region'] ?? null;
        $search_term = $_POST['search_term'] ?? null;
        $address = $_POST['address'] ?? null;
        $title = $_POST['title'] ?? null;
        $account_group = $_POST['account_group'] ?? null;
        $tax_number_3 = $_POST['tax_number_3'] ?? null;
        $permanent_account_number = $_POST['permanent_account_number'] ?? null;
        $supplier_id = $_POST['supplier_id'] ?? rand(100000, 999999);

        if (empty($supplier)) {
            sendResponse(400, "error", "Supplier Name is required");
        }

        // Check if supplier name already exists
        $checkSql = "SELECT id FROM suppliers WHERE supplier = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("s", $supplier);
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows > 0) {
            sendResponse(400, "error", "Supplier name already exists");
        }
        $checkStmt->close();

        $sql = "INSERT INTO suppliers (supplier_id, supplier, city, postal_code, region, search_term, street, address, title, account_group, tax_number_3, permanent_account_number, agent) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            sendResponse(500, "error", "Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("sssssssssssss", $supplier_id, $supplier, $city, $postal_code, $region, $search_term, $street, $address, $title, $account_group, $tax_number_3, $permanent_account_number, $agent);

        if ($stmt->execute()) {
            sendResponse(200, "success", "New supplier added successfully");
        } else {
            $errorMessage = 'Failed to add supplier: ' . $stmt->error;
            if ($stmt->errno == 1062) {
                $errorMessage = 'A supplier with similar details already exists. Please check for duplicates.';
            }
            sendResponse(500, "error", $errorMessage);
        }
        $stmt->close();
    } catch (Exception $e) {
        sendResponse(500, "error", $e->getMessage());
    }
    exit;
}

// Read operation
if ($action === 'read_all') {
    try {
        // Get pagination and search parameters
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 10;
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $offset = ($page - 1) * $limit;
        
        // Build WHERE clause for search
        $whereClause = '';
        $params = [];
        $types = '';
        
        if (!empty($search)) {
            $whereClause = "WHERE supplier LIKE ? OR supplier_id LIKE ? OR agent LIKE ? OR city LIKE ?";
            $searchParam = "%{$search}%";
            $params = [$searchParam, $searchParam, $searchParam, $searchParam];
            $types = "ssss";
        }
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM suppliers {$whereClause}";
        $countStmt = $conn->prepare($countSql);
        if (!empty($search)) {
            $countStmt->bind_param($types, ...$params);
        }
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $totalRecords = $countResult->fetch_assoc()['total'];
        $countStmt->close();
        
        // Get paginated data
        $sql = "SELECT * FROM suppliers {$whereClause} ORDER BY supplier ASC LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($sql);
        
        if (!empty($search)) {
            $params[] = $limit;
            $params[] = $offset;
            $types .= "ii";
            $stmt->bind_param($types, ...$params);
        } else {
            $stmt->bind_param("ii", $limit, $offset);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $suppliers = [];
        
        while ($row = $result->fetch_assoc()) {
            $suppliers[] = $row;
        }
        $stmt->close();
        
        $totalPages = ceil($totalRecords / $limit);
        
        sendResponse(200, "success", "Suppliers retrieved successfully", [
            'data' => $suppliers,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_records' => $totalRecords,
                'per_page' => $limit
            ]
        ]);
    } catch (Exception $e) {
        sendResponse(500, "error", $e->getMessage());
    }
    exit;
}

// Update operation
if ($action === 'update' && isset($_POST['id'])) {
    try {
        $id = intval($_POST['id']);
        $supplier = $_POST['supplier_name'] ?? null;
        $agent = $_POST['agent'] ?? null;
        $street = $_POST['street'] ?? null;
        $city = $_POST['city'] ?? null;
        $postal_code = $_POST['postal_code'] ?? null;
        $region = $_POST['region'] ?? null;
        $search_term = $_POST['search_term'] ?? null;
        $address = $_POST['address'] ?? null;
        $title = $_POST['title'] ?? null;
        $account_group = $_POST['account_group'] ?? null;
        $tax_number_3 = $_POST['tax_number_3'] ?? null;
        $permanent_account_number = $_POST['permanent_account_number'] ?? null;
        $supplier_id = $_POST['supplier_id'] ?? null;

        if (empty($supplier)) {
            sendResponse(400, "error", "Supplier Name is required");
        }

        $sql = "UPDATE suppliers SET 
                    supplier_id=?, supplier=?, agent=?,
                    street=?, city=?, postal_code=?, region=?, search_term=?, 
                    address=?, title=?, account_group=?, tax_number_3=?, permanent_account_number=?
                WHERE id=?";

        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            sendResponse(500, "error", "Prepare failed: " . $conn->error);
        }

        $stmt->bind_param(
            "issssssssssssi",
            $supplier_id,
            $supplier,
            $agent,
            $street,
            $city,
            $postal_code,
            $region,
            $search_term,
            $address,
            $title,
            $account_group,
            $tax_number_3,
            $permanent_account_number,
            $id
        );

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                sendResponse(200, "success", "Supplier updated successfully");
            } else {
                sendResponse(400, "error", "No changes detected or supplier not found");
            }
        } else {
            sendResponse(500, "error", "Error: " . $stmt->error);
        }
        $stmt->close();
    } catch (Exception $e) {
        sendResponse(500, "error", $e->getMessage());
    }
    exit;
}

// Delete operation
if ($action === 'delete' && isset($_POST['id'])) {
    try {
        $id = intval($_POST['id']);
        
        // Check if supplier is being used
        $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM purchase_requests WHERE supplier_id = ?");
        $checkStmt->bind_param("i", $id);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['count'] > 0) {
            sendResponse(400, "error", "Cannot delete supplier. It is being used in purchase requests.");
        }
        
        $sql = "DELETE FROM suppliers WHERE id=?";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            sendResponse(500, "error", "Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                sendResponse(200, "success", "Supplier deleted successfully");
            } else {
                sendResponse(400, "error", "Supplier not found or already deleted");
            }
        } else {
            sendResponse(500, "error", "Error: " . $stmt->error);
        }
        $stmt->close();
    } catch (Exception $e) {
        sendResponse(500, "error", $e->getMessage());
    }
    exit;
}

sendResponse(400, "error", "Invalid action");
?>

