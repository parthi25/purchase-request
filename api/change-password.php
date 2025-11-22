<?php
ini_set('display_errors', 0);
error_reporting(0);

include '../config/db.php';
include '../config/response.php';

// Only allow POST for updates
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    sendResponse(405, "error", "Only POST method allowed.");
}

// Get current user
session_start();
$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    sendResponse(401, "error", "Access denied.");
}

// Read JSON body
$input = json_decode(file_get_contents('php://input'), true);
$oldPassword = $input['old_password'] ?? '';
$newPassword = $input['new_password'] ?? '';
$confirmPassword = $input['confirm_password'] ?? '';

// Validate password fields
if (!$oldPassword || !$newPassword || !$confirmPassword) {
    sendResponse(400, "warning", "All password fields are required.");
}

// Fetch current password
$stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($currentHashedPassword);
$stmt->fetch();
$stmt->close();

// Verify old password
if (!password_verify($oldPassword, $currentHashedPassword)) {
    sendResponse(403, "error", "Incorrect old password.");
}

// Verify passwords match
if ($newPassword !== $confirmPassword) {
    sendResponse(400, "warning", "New passwords do not match.");
}

// Hash new password
$hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);

// Update password only (email cannot be changed)
$stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
$stmt->bind_param("si", $hashedNewPassword, $userId);

if ($stmt->execute()) {
    sendResponse(200, "success", "Profile updated successfully.");
} else {
    sendResponse(500, "error", "Update failed: " . htmlspecialchars($stmt->error));
}

$stmt->close();
$conn->close();
?>