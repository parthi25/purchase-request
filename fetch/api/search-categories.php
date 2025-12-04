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
        if (!$stmt) {
            throw new Exception("Failed to prepare query: " . $conn->error);
        }
        $user_id = intval($_SESSION['user_id']);
        $stmt->bind_param("si", $searchTerm, $user_id);
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
        }
        if ($stmt) {
            $stmt->close();
            $stmt = null;
        }
        sendResponse(200, "success", count($categories) ? "Categories found successfully" : "No categories found", $categories);
        return;

    } elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'buyer') {
        // Buyer role: ALWAYS prioritize buyer categories first, then buyer head categories as fallback
        // Fetch ALL categories first, then apply search filter in PHP
        $user_id = intval($_SESSION['user_id']);
        $allCategories = [];
        
        // Get buyer head ID for buyer_name (needed for both buyer and buyer head categories)
        $bheadStmt = $conn->prepare("SELECT b_head FROM buyers_info WHERE buyer = ? LIMIT 1");
        if (!$bheadStmt) {
            throw new Exception("Failed to prepare buyer head query: " . $conn->error);
        }
        $bheadStmt->bind_param("i", $user_id);
        $bheadStmt->execute();
        $bheadResult = $bheadStmt->get_result();
        $bheadRow = $bheadResult->fetch_assoc();
        $bheadStmt->close();
        $bheadStmt = null;
        $bheadId = $bheadRow ? intval($bheadRow['b_head']) : 0;
        $bheadName = '';
        
        if ($bheadId > 0) {
            $bheadNameStmt = $conn->prepare("SELECT fullname FROM users WHERE id = ?");
            if (!$bheadNameStmt) {
                throw new Exception("Failed to prepare buyer name query: " . $conn->error);
            }
            $bheadNameStmt->bind_param("i", $bheadId);
            $bheadNameStmt->execute();
            $bheadNameResult = $bheadNameStmt->get_result();
            $bheadNameRow = $bheadNameResult->fetch_assoc();
            $bheadName = $bheadNameRow ? $bheadNameRow['fullname'] : '';
            $bheadNameStmt->close();
            $bheadNameStmt = null;
        }
        
        // ============================================
        // PRIORITY 1: ALWAYS try buyer categories first (direct buyer to category mapping)
        // ============================================
        // Check if buyer_category_mapping table exists
        $tableCheck = $conn->query("SHOW TABLES LIKE 'buyer_category_mapping'");
        $tableExists = $tableCheck && $tableCheck->num_rows > 0;
        
        if ($tableExists) {
            // Fetch ALL categories without search filter
            $stmt = $conn->prepare("
                SELECT c.maincat AS cat, c.id AS category_id
                FROM buyer_category_mapping bcm
                JOIN categories c ON bcm.category_id = c.id
                WHERE bcm.buyer_id = ? AND bcm.is_active = 1
            ");
            if ($stmt) {
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                while ($row = $result->fetch_assoc()) {
                    $allCategories[] = [
                        'cat' => $row['cat'],
                        'buyer_name' => $bheadName,
                        'user_id' => $bheadId  // Return buyer head ID for compatibility
                    ];
                }
                $stmt->close();
                $stmt = null;
            }
        }
        
        // ============================================
        // PRIORITY 2: ONLY if buyer categories are EMPTY, then show buyer head categories
        // ============================================
        // This ensures buyer categories always take precedence
        if (empty($allCategories) && $bheadId > 0) {
            // Fetch ALL categories without search filter
            $stmt = $conn->prepare("SELECT c.maincat AS cat, u.fullname AS buyer_name, bhc.user_id 
                                    FROM buyer_head_categories bhc
                                    JOIN categories c ON c.id = bhc.cat_id
                                    JOIN users u ON u.id = bhc.user_id
                                    WHERE bhc.user_id = ?");
            if (!$stmt) {
                throw new Exception("Failed to prepare fallback query: " . $conn->error);
            }
            $stmt->bind_param("i", $bheadId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $allCategories[] = [
                    'cat' => $row['cat'],
                    'buyer_name' => $row['buyer_name'],
                    'user_id' => $row['user_id']
                ];
            }
            if ($stmt) {
                $stmt->close();
                $stmt = null;
            }
        }
        
        // ============================================
        // Apply search filter in PHP after fetching all categories
        // ============================================
        $categories = [];
        if (!empty($search)) {
            $searchLower = strtolower($search);
            foreach ($allCategories as $category) {
                // Check if category name starts with search term (case-insensitive)
                if (stripos($category['cat'], $search) === 0) {
                    $categories[] = $category;
                }
            }
            // Limit to 10 results after filtering
            $categories = array_slice($categories, 0, 10);
        } else {
            // If no search term, return all categories (limited to 10)
            $categories = array_slice($allCategories, 0, 10);
        }
        
        // Return results (either buyer categories or buyer head categories, never both)
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
        if (!$stmt) {
            throw new Exception("Failed to prepare query: " . $conn->error);
        }
        $stmt->bind_param("s", $searchTerm);
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
        }
        if ($stmt) {
            $stmt->close();
            $stmt = null;
        }
        sendResponse(200, "success", count($categories) ? "Categories found successfully" : "No categories found", $categories);
        return;
    }

} catch (Exception $e) {
    // Clean up any open statements (safely close only if not already closed)
    if (isset($stmt) && $stmt) {
        try {
            $stmt->close();
        } catch (Exception $closeError) {
            // Statement already closed, ignore
        }
        $stmt = null;
    }
    if (isset($bheadStmt) && $bheadStmt) {
        try {
            $bheadStmt->close();
        } catch (Exception $closeError) {
            // Statement already closed, ignore
        }
        $bheadStmt = null;
    }
    if (isset($bheadNameStmt) && $bheadNameStmt) {
        try {
            $bheadNameStmt->close();
        } catch (Exception $closeError) {
            // Statement already closed, ignore
        }
        $bheadNameStmt = null;
    }
    
    // Log detailed error for debugging
    error_log("Error in search-categories.php: " . $e->getMessage() . " | File: " . $e->getFile() . " | Line: " . $e->getLine() . " | Trace: " . $e->getTraceAsString());
    
    // Send generic error message to client (don't expose internal details)
    sendResponse(500, "error", "Internal server error. Please try again later.");
} finally {
    if (isset($conn) && $conn) {
        $conn->close();
    }
}
?>