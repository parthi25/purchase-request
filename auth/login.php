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
$username = strtolower(trim($data['username'] ?? ''));
$password = $data['password'] ?? '';

// Input validation
$validator = new Validator();
if (!$validator->validateLogin(['username' => $username, 'password' => $password])) {
    sendResponse(400, "error", $validator->getFirstError());
}

// Sanitize input
$username = Security::sanitizeInput($username);

try {
    $sql = "SELECT id, username, fullname, password, role, is_active FROM users WHERE LOWER(TRIM(username)) = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        sendResponse(500, "error", "Database query preparation failed");
    }
    
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user) {
        sendResponse(404, "error", "No account found with this username");
    }

    if ($user['is_active'] != 1) {
        sendResponse(403, "error", "Your account is disabled");
    }

    if (!password_verify($password, $user['password'])) {
        sendResponse(401, "error", "Invalid password");
    }

    // Start secure session
    session_start();
    
    // Regenerate session ID for security
    session_regenerate_id(true);
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['fullname'] = $user['fullname'];
    $_SESSION['last_activity'] = time();
    
    // Generate CSRF token for the session
    Security::generateCSRFToken();

    sendResponse(200, "success", "Login successful", [
        "role" => $user['role'],
        "fullname" => $user['fullname'],
        "csrf_token" => $_SESSION['csrf_token']
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
