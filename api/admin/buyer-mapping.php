<?php
session_start();
require '../../config/db.php';
include '../../config/response.php';

// Check if user is admin/super_admin/master
$allowedRoles = ['admin', 'super_admin', 'master'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], $allowedRoles)) {
    sendResponse(403, "error", "Unauthorized access");
}


// INSERT or UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['b_head']) && isset($_POST['buyer'])) {
    try {
        $id = $_POST['id'] ?? '';
        $b_head = intval($_POST['b_head']);
        $buyer = intval($_POST['buyer']);

        if ($id) {
            // Check if mapping already exists (excluding current)
            $checkStmt = $conn->prepare("SELECT id FROM buyers_info WHERE b_head = ? AND buyer = ? AND id != ?");
            $checkStmt->bind_param("iii", $b_head, $buyer, $id);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows > 0) {
                sendResponse(400, "error", "This mapping already exists");
            }
            
            $stmt = $conn->prepare("UPDATE buyers_info SET b_head = ?, buyer = ? WHERE id = ?");
            $stmt->bind_param("iii", $b_head, $buyer, $id);
            $stmt->execute();
            
            if ($stmt->affected_rows === 0) {
                sendResponse(400, "error", "No changes made or mapping not found");
            }
            
            sendResponse(200, "success", "Updated successfully");
        } else {
            // Check if mapping already exists
            $checkStmt = $conn->prepare("SELECT id FROM buyers_info WHERE b_head = ? AND buyer = ?");
            $checkStmt->bind_param("ii", $b_head, $buyer);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows > 0) {
                sendResponse(400, "error", "This mapping already exists");
            }
            
            $stmt = $conn->prepare("INSERT INTO buyers_info (b_head, buyer) VALUES (?, ?)");
            $stmt->bind_param("ii", $b_head, $buyer);
            $stmt->execute();
            
            sendResponse(200, "success", "Inserted successfully");
        }
    } catch (Exception $e) {
        sendResponse(500, "error", $e->getMessage());
    }
    exit;
}

// DELETE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    try {
        $delete_id = intval($_POST['delete_id']);
        $stmt = $conn->prepare("DELETE FROM buyers_info WHERE id = ?");
        $stmt->bind_param("i", $delete_id);
        $stmt->execute();
        
        if ($stmt->affected_rows === 0) {
            sendResponse(400, "error", "Mapping not found or already deleted");
        }
        
        sendResponse(200, "success", "Deleted successfully");
    } catch (Exception $e) {
        sendResponse(500, "error", $e->getMessage());
    }
    exit;
}

// FETCH ALL
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $result = $conn->query("SELECT b.*, u.fullname AS b_head_name, u2.fullname AS buyer_name 
                                FROM buyers_info b 
                                LEFT JOIN users u ON u.id = b.b_head 
                                LEFT JOIN users u2 ON u2.id = b.buyer 
                                ORDER BY b.id DESC");
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        
        sendResponse(200, "success", "Mappings retrieved successfully", $rows);
    } catch (Exception $e) {
        sendResponse(500, "error", $e->getMessage());
    }
    exit;
}

sendResponse(400, "error", "Invalid request");
?>

