<?php
/**
 * CSRF Token Endpoint
 * Provides CSRF tokens for AJAX requests
 */

session_start();
include '../config/response.php';
include '../config/security.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    sendResponse(401, "error", "User not logged in");
}

// Generate and return CSRF token
$csrfToken = Security::generateCSRFToken();
sendResponse(200, "success", "CSRF token generated", [
    "csrf_token" => $csrfToken
]);
?>
