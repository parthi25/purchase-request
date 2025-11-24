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
        $name = $_POST['name'] ?? null;

        if (empty($name)) {
            sendResponse(400, "error", "Name is required");
        }

        // Check if name already exists
        $checkSql = "SELECT id FROM purchase_types WHERE name = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("s", $name);
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows > 0) {
            sendResponse(400, "error", "Purchase type already exists");
        }
        $checkStmt->close();

        $sql = "INSERT INTO purchase_types (name) VALUES (?)";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            sendResponse(500, "error", "Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("s", $name);

        if ($stmt->execute()) {
            sendResponse(200, "success", "New purchase type added successfully");
        } else {
            sendResponse(500, "error", "Failed to add purchase type: " . $stmt->error);
        }
        $stmt->close();
    } catch (Exception $e) {
        sendResponse(500, "error", $e->getMessage());
    }
    exit;
}

// Read operation with pagination and search
if ($action === 'read_all') {
    try {
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 10;
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $offset = ($page - 1) * $limit;
        
        // Build WHERE clause for search
        $whereClause = '';
        $searchParam = '';
        if (!empty($search)) {
            $searchParam = '%' . $conn->real_escape_string($search) . '%';
            $whereClause = "WHERE name LIKE ?";
        }
        
        // Get total count
        if (!empty($search)) {
            $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM purchase_types WHERE name LIKE ?");
            $countStmt->bind_param("s", $searchParam);
            $countStmt->execute();
        } else {
            $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM purchase_types");
            $countStmt->execute();
        }
        $countResult = $countStmt->get_result();
        $totalCount = $countResult->fetch_assoc()['total'];
        $countStmt->close();
        
        // Get paginated results
        if (!empty($search)) {
            $sql = "SELECT * FROM purchase_types WHERE name LIKE ? ORDER BY name ASC LIMIT ? OFFSET ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sii", $searchParam, $limit, $offset);
        } else {
            $sql = "SELECT * FROM purchase_types ORDER BY name ASC LIMIT ? OFFSET ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $limit, $offset);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $purchaseTypes = [];
        
        while ($row = $result->fetch_assoc()) {
            $purchaseTypes[] = $row;
        }
        
        $totalPages = ceil($totalCount / $limit);
        
        $responseData = [
            'data' => $purchaseTypes,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_items' => $totalCount,
                'items_per_page' => $limit
            ]
        ];
        
        sendResponse(200, "success", "Purchase types retrieved successfully", $responseData);
        $stmt->close();
    } catch (Exception $e) {
        sendResponse(500, "error", $e->getMessage());
    }
    exit;
}

// Update operation
if ($action === 'update' && isset($_POST['id'])) {
    try {
        $id = intval($_POST['id']);
        $name = $_POST['name'] ?? null;

        if (empty($name)) {
            sendResponse(400, "error", "Name is required");
        }

        $sql = "UPDATE purchase_types SET name = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            sendResponse(500, "error", "Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("si", $name, $id);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                sendResponse(200, "success", "Purchase type updated successfully");
            } else {
                sendResponse(400, "error", "No changes detected or purchase type not found");
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
        
        // Check if purchase type is being used
        $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM purchase_requests WHERE purch_id = ?");
        $checkStmt->bind_param("i", $id);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['count'] > 0) {
            sendResponse(400, "error", "Cannot delete purchase type. It is being used in purchase requests.");
        }
        
        $sql = "DELETE FROM purchase_types WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            sendResponse(500, "error", "Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                sendResponse(200, "success", "Purchase type deleted successfully");
            } else {
                sendResponse(400, "error", "Purchase type not found or already deleted");
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

