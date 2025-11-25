<?php
session_start();
require '../../config/db.php';
include '../../config/response.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    sendResponse(403, "error", "Unauthorized access");
}

// Get buyer ID from query parameter or session
$buyer_id = isset($_GET['buyer_id']) ? intval($_GET['buyer_id']) : (isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0);

if ($buyer_id <= 0) {
    sendResponse(400, "error", "Invalid buyer ID");
}

try {
    // First, try to get categories from buyer_category_mapping (direct mapping)
    $stmt = $conn->prepare("
        SELECT DISTINCT c.id, c.maincat 
        FROM buyer_category_mapping bcm
        JOIN categories c ON bcm.category_id = c.id
        WHERE bcm.buyer_id = ? AND bcm.is_active = 1
        ORDER BY c.maincat ASC
    ");
    $stmt->bind_param("i", $buyer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $categories = [];
    
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    $stmt->close();
    
    // If no direct mapping found, fallback to buyer head mapping (legacy)
    if (empty($categories)) {
        // Get buyer head from buyers_info
        $bheadStmt = $conn->prepare("SELECT b_head FROM buyers_info WHERE buyer = ? LIMIT 1");
        $bheadStmt->bind_param("i", $buyer_id);
        $bheadStmt->execute();
        $bheadResult = $bheadStmt->get_result();
        $bheadRow = $bheadResult->fetch_assoc();
        $bheadStmt->close();
        
        if ($bheadRow && isset($bheadRow['b_head'])) {
            $bheadId = intval($bheadRow['b_head']);
            
            // Get categories from catbasbh (buyer head mapping)
            $catStmt = $conn->prepare("
                SELECT DISTINCT c.id, c.maincat 
                FROM catbasbh cb
                JOIN categories c ON c.maincat = cb.cat 
                WHERE cb.user_id = ?
                ORDER BY c.maincat ASC
            ");
            $catStmt->bind_param("i", $bheadId);
            $catStmt->execute();
            $catResult = $catStmt->get_result();
            
            while ($row = $catResult->fetch_assoc()) {
                $categories[] = $row;
            }
            $catStmt->close();
        }
    }
    
    sendResponse(200, "success", "Categories retrieved successfully", $categories);
} catch (Exception $e) {
    sendResponse(500, "error", $e->getMessage());
}
?>

