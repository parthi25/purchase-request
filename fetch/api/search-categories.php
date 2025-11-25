<?php
session_start();
include '../../config/db.php';
include '../../config/response.php';

if (!isset($_POST['search'])) {
    sendResponse(400, "error", "Search parameter is required");
}

$search = $_POST['search'] ?? '';
$searchTerm = "$search%"; // Match beginning of string

try {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'B_Head') {
        // B_Head role: filter by user_id
        $stmt = $conn->prepare("SELECT c.maincat AS cat, u.fullname AS buyer_name, bhc.user_id 
                                FROM buyer_head_categories bhc
                                JOIN categories c ON c.id = bhc.cat_id
                                JOIN users u ON u.id = bhc.user_id
                                WHERE c.maincat LIKE ? AND bhc.user_id = ? 
                                LIMIT 10");
        $user_id = intval($_SESSION['user_id']);
        $stmt->bind_param("si", $searchTerm, $user_id);

    } elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'buyer') {
        // Buyer role: First try direct buyer-category mapping
        $user_id = intval($_SESSION['user_id']);
        $categories = [];
        
        // Get buyer head ID for buyer_name
        $bheadStmt = $conn->prepare("SELECT b_head FROM buyers_info WHERE buyer = ? LIMIT 1");
        $bheadStmt->bind_param("i", $user_id);
        $bheadStmt->execute();
        $bheadResult = $bheadStmt->get_result();
        $bheadRow = $bheadResult->fetch_assoc();
        $bheadStmt->close();
        $bheadId = $bheadRow ? intval($bheadRow['b_head']) : 0;
        $bheadName = '';
        
        if ($bheadId > 0) {
            $bheadNameStmt = $conn->prepare("SELECT fullname FROM users WHERE id = ?");
            $bheadNameStmt->bind_param("i", $bheadId);
            $bheadNameStmt->execute();
            $bheadNameResult = $bheadNameStmt->get_result();
            $bheadNameRow = $bheadNameResult->fetch_assoc();
            $bheadName = $bheadNameRow ? $bheadNameRow['fullname'] : '';
            $bheadNameStmt->close();
        }
        
        // Try buyer_category_mapping first
        $stmt = $conn->prepare("
            SELECT c.maincat AS cat, c.id AS category_id
            FROM buyer_category_mapping bcm
            JOIN categories c ON bcm.category_id = c.id
            WHERE c.maincat LIKE ? AND bcm.buyer_id = ? AND bcm.is_active = 1
            LIMIT 10
        ");
        $stmt->bind_param("si", $searchTerm, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $categories[] = [
                'cat' => $row['cat'],
                'buyer_name' => $bheadName,
                'user_id' => $bheadId  // Return buyer head ID for compatibility
            ];
        }
        $stmt->close();
        
        // If no direct mapping found, fallback to buyer head mapping (legacy)
        if (empty($categories) && $bheadId > 0) {
            $stmt = $conn->prepare("SELECT c.maincat AS cat, u.fullname AS buyer_name, bhc.user_id 
                                    FROM buyer_head_categories bhc
                                    JOIN categories c ON c.id = bhc.cat_id
                                    JOIN users u ON u.id = bhc.user_id
                                    WHERE c.maincat LIKE ? AND bhc.user_id = ? 
                                    LIMIT 10");
            $stmt->bind_param("si", $searchTerm, $bheadId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $categories[] = [
                    'cat' => $row['cat'],
                    'buyer_name' => $row['buyer_name'],
                    'user_id' => $row['user_id']
                ];
            }
            $stmt->close();
        }
        
        if (!empty($categories)) {
            sendResponse(200, "success", "Categories found successfully", $categories);
        } else {
            sendResponse(200, "success", "No categories found", []);
        }
        return;

    } else {
        // Other roles: no user_id filter
        $stmt = $conn->prepare("SELECT c.maincat AS cat, u.fullname AS buyer_name, bhc.user_id 
                                FROM buyer_head_categories bhc
                                JOIN categories c ON c.id = bhc.cat_id
                                JOIN users u ON u.id = bhc.user_id
                                WHERE c.maincat LIKE ? 
                                LIMIT 10");
        $stmt->bind_param("s", $searchTerm);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $categories = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $categories[] = [
                'cat' => $row['cat'],
                'buyer_name' => $row['buyer_name'],
                'user_id' => $row['user_id']
            ];
        }
        sendResponse(200, "success", "Categories found successfully", $categories);
    } else {
        sendResponse(200, "success", "No categories found", []);
    }

    $stmt->close();
    if (isset($sub_stmt)) {
        $sub_stmt->close();
    }

} catch (Exception $e) {
    error_log("Error in fetch_categories.php: " . $e->getMessage());
    sendResponse(500, "error", "Internal server error");
} finally {
    $conn->close();
}
?>