<?php
ini_set('display_errors', 0);
error_reporting(0);

include '../config/db.php';
include '../config/response.php';

// Only allow GET method
if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    sendResponse(405, "error", "Only GET method allowed.");
}

// Get current user
session_start();
$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    sendResponse(401, "error", "Access denied.");
}

// Fetch user data
$stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    sendResponse(200, "success", "Profile data retrieved.", $user);
} else {
    sendResponse(404, "error", "User not found.");
}

$stmt->close();
$conn->close();
?>