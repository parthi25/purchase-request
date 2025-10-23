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
$email = trim($input['email'] ?? '');
$oldPassword = $input['old_password'] ?? '';
$newPassword = $input['new_password'] ?? '';
$confirmPassword = $input['confirm_password'] ?? '';

// Validate email
if (!$email) {
    sendResponse(400, "warning", "Email is required.");
}

// Fetch current password
$stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($currentHashedPassword);
$stmt->fetch();
$stmt->close();

// Password update logic
$updatePassword = false;
if ($oldPassword || $newPassword || $confirmPassword) {
    if (!$oldPassword || !$newPassword || !$confirmPassword) {
        sendResponse(400, "warning", "To change password, fill all password fields.");
    }

    if (!password_verify($oldPassword, $currentHashedPassword)) {
        sendResponse(403, "error", "Incorrect old password.");
    }

    if ($newPassword !== $confirmPassword) {
        sendResponse(400, "warning", "New passwords do not match.");
    }

    $updatePassword = true;
    $hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);
}

// Update user
if ($updatePassword) {
    $stmt = $conn->prepare("UPDATE users SET email = ?, password = ? WHERE id = ?");
    $stmt->bind_param("ssi", $email, $hashedNewPassword, $userId);
} else {
    $stmt = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
    $stmt->bind_param("si", $email, $userId);
}

if ($stmt->execute()) {
    sendResponse(200, "success", "Profile updated successfully.");
} else {
    sendResponse(500, "error", "Update failed: " . htmlspecialchars($stmt->error));
}

$stmt->close();
$conn->close();
?>