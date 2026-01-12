<?php
session_start();
include '../config/db.php';
include '../config/response.php';
include '../config/security.php';
include '../config/env.php';

// Only allow GET requests
if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    sendResponse(405, "error", "Only GET requests are allowed");
}

// Get session parameter from query string
$session = $_GET['session'] ?? '';

if (empty($session)) {
    sendResponse(400, "error", "Session parameter is required");
}

try {
    // Get INTERNAL_URL from environment
    $internalUrl = $_ENV['INTERNAL_URL'] ?? getenv('INTERNAL_URL');
    
    if (empty($internalUrl)) {
        sendResponse(500, "error", "SSO service not configured");
    }

    // Call identity service
    $apiUrl = rtrim($internalUrl, '/') . '/api/user/by-session?session=' . urlencode($session);
    
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        error_log("SSO API curl error: " . $curlError);
        sendResponse(500, "error", "Failed to connect to identity service");
    }

    if ($httpCode !== 200) {
        error_log("SSO API returned HTTP code: " . $httpCode);
        sendResponse(404, "error", "User not found or session invalid");
    }

    $data = json_decode($response, true);

    if (!$data || !isset($data['success']) || !$data['success']) {
        sendResponse(404, "error", "User not found");
    }

    // Extract user data
    $user = $data['data']['user'] ?? $data['data'] ?? null;
    
    if (!$user) {
        sendResponse(404, "error", "User data not found");
    }

    // Find correct application (PR Tracking)
    $applications = $data['data']['applications'] ?? [];
    $prTrackerApp = null;
    foreach ($applications as $app) {
        if (($app['id'] ?? '') == 3 || strpos(strtolower($app['name'] ?? ''), 'pr tracking') !== false) {
            $prTrackerApp = $app;
            break;
        }
    }

    // Fallback if not specifically found
    if (!$prTrackerApp && !empty($applications)) {
        $prTrackerApp = $applications[0];
    }

    if (!$prTrackerApp) {
        sendResponse(403, "error", "Application access not authorized");
    }

    // Extract role and organization info
    $org = $prTrackerApp['organizations'][0] ?? null;
    $roleName = $org['roles'][0]['name'] ?? null;
    $orgId = $org['id'] ?? null;
    $plantId = $org['plants'][0]['id'] ?? null;
    $plantName = $org['plants'][0]['name'] ?? null;
    
    // Map identity service role to local role_code
    $roleMapping = [
        'admin' => 'admin',
        'buyer' => 'buyer',
        'B_Head' => 'B_Head',
        'PO_Head' => 'PO_Head',
        'PO_Team_Member' => 'PO_Team_Member',
        'super_admin' => 'super_admin',
        'super admin' => 'super_admin', // Handle space from identity service
        'master' => 'master'
    ];
    
    $roleCode = $roleMapping[$roleName] ?? $roleName ?? 'buyer'; 
    
    // Get user email (handle different possible structures)
    $email = $user['email'] ?? $user['supplier']['email'] ?? null;
    
    if (empty($email)) {
        sendResponse(400, "error", "User email not found");
    }

    // Find existing user by email
    $findUserStmt = $conn->prepare("SELECT u.id, u.username, u.fullname, u.role_id, u.is_active, r.role_code, r.role_name 
                                     FROM users u 
                                     LEFT JOIN roles r ON u.role_id = r.id 
                                     WHERE LOWER(TRIM(u.email)) = ? LIMIT 1");
    
    if (!$findUserStmt) {
        throw new Exception("Failed to prepare user lookup query: " . $conn->error);
    }
    
    $emailLower = strtolower(trim($email));
    $findUserStmt->bind_param("s", $emailLower);
    $findUserStmt->execute();
    $userResult = $findUserStmt->get_result();
    $existingUser = $userResult->fetch_assoc();
    $userResult->free();
    $findUserStmt->close();

    // Get role_id for the mapped role_code
    $roleStmt = $conn->prepare("SELECT id, role_code, role_name FROM roles WHERE role_code = ? LIMIT 1");
    if (!$roleStmt) {
        throw new Exception("Failed to prepare role query: " . $conn->error);
    }
    $roleStmt->bind_param("s", $roleCode);
    $roleStmt->execute();
    $roleResult = $roleStmt->get_result();
    $roleData = $roleResult->fetch_assoc();
    $roleResult->free();
    $roleStmt->close();

    if (!$roleData) {
        error_log("SSO Error: Role '$roleCode' not found in roles table");
        sendResponse(500, "error", "Invalid role configuration");
    }

    $roleId = $roleData['id'];
    $finalRoleCode = $roleData['role_code'];
    $finalRoleName = $roleData['role_name'];

    // Prepare user data
    $username = $user['username'] ?? $email;
    $fullname = $user['fullname'] ?? $user['username'] ?? $email;
    if (isset($user['supplier']['company_name'])) {
        $fullname = $user['supplier']['company_name'];
    }

    if ($existingUser) {
        // Update existing user
        $updateStmt = $conn->prepare("UPDATE users SET username = ?, fullname = ?, role_id = ?, email = ? WHERE id = ?");
        if (!$updateStmt) {
            throw new Exception("Failed to prepare update query: " . $conn->error);
        }
        $updateStmt->bind_param("ssisi", $username, $fullname, $roleId, $emailLower, $existingUser['id']);
        $updateStmt->execute();
        $updateStmt->close();
        
        $userId = $existingUser['id'];
        
        // Check if user is active
        if ($existingUser['is_active'] != 1) {
            sendResponse(403, "error", "Your account is disabled");
        }
    } else {
        // Create new user
        $hashedPassword = password_hash("mypassword", PASSWORD_DEFAULT);
        $insertStmt = $conn->prepare("INSERT INTO users (username, fullname, email, password, role_id, is_active) VALUES (?, ?, ?, ?, ?, 1)");
        if (!$insertStmt) {
            throw new Exception("Failed to prepare insert query: " . $conn->error);
        }
        $insertStmt->bind_param("ssssi", $username, $fullname, $emailLower, $hashedPassword, $roleId);
        $insertStmt->execute();
        $userId = $insertStmt->insert_id;
        $insertStmt->close();
    }

    // Start secure session
    session_regenerate_id(true);
    
    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $username;
    $_SESSION['role'] = $finalRoleCode;
    $_SESSION['role_name'] = $finalRoleName;
    $_SESSION['fullname'] = $fullname;
    $_SESSION['org_id'] = $orgId;
    $_SESSION['plant_id'] = $plantId;
    $_SESSION['plant_name'] = $plantName;
    $_SESSION['last_activity'] = time();
    
    // Generate CSRF token for the session
    Security::generateCSRFToken();

    // Get initial page URL from role_initial_settings
    $initialPageUrl = null;
    $settingsQuery = "SELECT initial_page_url FROM role_initial_settings WHERE role = ? AND is_active = 1 LIMIT 1";
    $settingsStmt = $conn->prepare($settingsQuery);
    if ($settingsStmt) {
        $settingsStmt->bind_param("s", $finalRoleCode);
        $settingsStmt->execute();
        $settingsResult = $settingsStmt->get_result();
        if ($settingsResult && $settingsRow = $settingsResult->fetch_assoc()) {
            $initialPageUrl = $settingsRow['initial_page_url'];
        }
        if ($settingsResult) {
            $settingsResult->free();
        }
        $settingsStmt->close();
    }

    // Determine redirect URL
    $redirectUrl = '/chn/pages/po-head.php'; // Default fallback
    
    if ($initialPageUrl) {
        $redirectUrl = '/chn/pages/' . $initialPageUrl;
    } else if ($finalRoleCode) {
        // Fallback to default based on role
        $defaultUrls = [
            'admin' => '/chn/pages/admin.php',
            'buyer' => '/chn/pages/buyer.php',
            'B_Head' => '/chn/pages/buyer-head.php',
            'PO_Head' => '/chn/pages/po-head.php',
            'PO_Team_Member' => '/chn/pages/po-member.php',
            'super_admin' => '/chn/pages/admin.php',
            'master' => '/chn/pages/admin.php'
            ];  
        $redirectUrl = $defaultUrls[$finalRoleCode] ?? '/chn/pages/po-head.php';
    }

    // Redirect to the appropriate page
    header("Location: " . $redirectUrl);
    exit;

} catch (Exception $e) {
    error_log("SSO error: " . $e->getMessage() . " | File: " . $e->getFile() . " | Line: " . $e->getLine());
    sendResponse(500, "error", "Internal server error. Please try again later.");
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>



