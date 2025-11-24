<?php
include '../config/db.php';
include '../config/response.php';
include '../config/security.php';
include '../config/validator.php';

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    sendResponse(200, "success", "Preflight OK");
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    sendResponse(405, "error", "Only POST requests are allowed");
}

// Rate limiting
$clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
// if (!Security::checkRateLimit($clientIP, 'login', 5, 300)) { // 5 attempts per 5 minutes
//     sendResponse(429, "error", "Too many login attempts. Please try again later.");
// }

$data = json_decode(file_get_contents("php://input"), true);
$usernameOrEmail = strtolower(trim($data['username'] ?? ''));
$password = $data['password'] ?? '';

// Input validation
$validator = new Validator();
if (!$validator->validateLogin(['username' => $usernameOrEmail, 'password' => $password])) {
    sendResponse(400, "error", $validator->getFirstError());
}

// Sanitize input
$usernameOrEmail = Security::sanitizeInput($usernameOrEmail);

try {
    // Query to check both username and email in a single query, JOIN with roles to get role_code
    $sql = "SELECT u.id, u.username, u.fullname, u.password, u.role_id, u.is_active, r.role_code, r.role_name 
            FROM users u 
            LEFT JOIN roles r ON u.role_id = r.id 
            WHERE LOWER(TRIM(u.username)) = ? OR LOWER(TRIM(u.email)) = ?";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        sendResponse(500, "error", "Database query preparation failed");
    }
    
    $stmt->bind_param("ss", $usernameOrEmail, $usernameOrEmail);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $result->free();
    $stmt->close();

    if (!$user) {
        sendResponse(404, "error", "No account found with this username or email");
    }

    if ($user['is_active'] != 1) {
        sendResponse(403, "error", "Your account is disabled");
    }

    if (!password_verify($password, $user['password'])) {
        sendResponse(401, "error", "Invalid password");
    }

    // Validate that user has a valid role
    if (empty($user['role_id']) || empty($user['role_code'])) {
        error_log("Login error: User ID {$user['id']} has no valid role assigned");
        sendResponse(500, "error", "Your account is missing a valid role. Please contact administrator.");
    }

    // Start secure session
    session_start();
    
    // Regenerate session ID for security
    session_regenerate_id(true);
    
    $roleCode = $user['role_code'];
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $roleCode; // Store role_code in session
    $_SESSION['fullname'] = $user['fullname'];
    $_SESSION['last_activity'] = time();
    
    // Generate CSRF token for the session
    Security::generateCSRFToken();

    // Get initial page URL from role_initial_settings using role_code
    $initialPageUrl = null;
    $initialStatusFilter = null;
    
    $settingsQuery = "SELECT initial_page_url, initial_status_filter FROM role_initial_settings WHERE role = ? AND is_active = 1 LIMIT 1";
    $settingsStmt = $conn->prepare($settingsQuery);
    if ($settingsStmt) {
        $settingsStmt->bind_param("s", $roleCode);
        $settingsStmt->execute();
        $settingsResult = $settingsStmt->get_result();
        if ($settingsResult && $settingsRow = $settingsResult->fetch_assoc()) {
            $initialPageUrl = $settingsRow['initial_page_url'];
            $initialStatusFilter = $settingsRow['initial_status_filter'];
        }
        if ($settingsResult) {
            $settingsResult->free();
        }
        $settingsStmt->close();
    }

    sendResponse(200, "success", "Login successful", [
        "role" => $roleCode,
        "fullname" => $user['fullname'],
        "csrf_token" => $_SESSION['csrf_token'],
        "initial_page_url" => $initialPageUrl,
        "initial_status_filter" => $initialStatusFilter
    ]);

} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    sendResponse(500, "error", "Internal server error");
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
