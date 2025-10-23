<?php
session_start();
include '../config/db.php';
include '../config/response.php'; // unified sendResponse

// Ensure user is authenticated
if (!isset($_SESSION['user_id'])) {
    sendResponse(401, "error", "User not authenticated");
}

$user_id = (int) $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? '';

if ($user_role !== 'B_Head') {
    sendResponse(403, "error", "Access denied");
}

try {
    $stmt = $conn->prepare("SELECT id, name FROM users WHERE role = 'buyer' AND b_head = ? ORDER BY name");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $buyers = [];
    while ($row = $result->fetch_assoc()) {
        $buyers[] = $row;
    }

    sendResponse(200, "success", "Buyers retrieved successfully", $buyers);

    $stmt->close();
} catch (Exception $e) {
    error_log("Error in fetch_buyers.php: " . $e->getMessage());
    sendResponse(500, "error", "Internal server error");
} finally {
    $conn->close();
}
?>
