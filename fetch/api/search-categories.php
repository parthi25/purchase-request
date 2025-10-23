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
        $stmt = $conn->prepare("SELECT cat, Name AS buyer_name, user_id 
                                FROM catbasbh 
                                WHERE cat LIKE ? AND user_id = ? 
                                LIMIT 10");
        $user_id = intval($_SESSION['user_id']);
        $stmt->bind_param("si", $searchTerm, $user_id);

    } elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'buyer') {
        // Buyer role: get buyer's B_Head ID first
        $user_id = intval($_SESSION['user_id']);
        $sub_stmt = $conn->prepare("SELECT b_head FROM buyers_info WHERE buyer = ?");
        $sub_stmt->bind_param("i", $user_id);
        $sub_stmt->execute();
        $sub_result = $sub_stmt->get_result();

        if ($sub_result && $sub_result->num_rows > 0) {
            $b_head_row = $sub_result->fetch_assoc();
            $b_head_id = intval($b_head_row['b_head']);

            $stmt = $conn->prepare("SELECT cat, Name AS buyer_name, user_id 
                                    FROM catbasbh 
                                    WHERE cat LIKE ? AND user_id = ? 
                                    LIMIT 10");
            $stmt->bind_param("si", $searchTerm, $b_head_id);
        } else {
            sendResponse(404, "error", "No associated B_Head found");
        }

    } else {
        // Other roles: no user_id filter
        $stmt = $conn->prepare("SELECT cat, Name AS buyer_name, user_id 
                                FROM catbasbh 
                                WHERE cat LIKE ? 
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