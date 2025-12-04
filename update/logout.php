<?php
session_start();
include '../config/env.php';

// Try SSO logout if INTERNAL_URL is configured
$internalUrl = $_ENV['INTERNAL_URL'] ?? getenv('INTERNAL_URL');
$ssoLogoutSuccess = false;

if (!empty($internalUrl) && isset($_SESSION['user_id'])) {
    try {
        // Call SSO logout endpoint
        $logoutUrl = rtrim($internalUrl, '/') . '/api/logout';
        
        $ch = curl_init($logoutUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        // Include session cookie if available
        if (isset($_COOKIE[session_name()])) {
            curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . $_COOKIE[session_name()]);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Consider successful if we got a response (even if it's not 200)
        // The SSO service might handle logout differently
        if ($httpCode >= 200 && $httpCode < 500) {
            $ssoLogoutSuccess = true;
        }
    } catch (Exception $e) {
        // Silently fail and continue with local logout
        error_log("SSO logout error: " . $e->getMessage());
    }
}

// Always perform local logout regardless of SSO logout result
session_destroy();
?>
<!DOCTYPE html>
<html>

<head>
    <script>
    // clear client-side filters
    localStorage.removeItem('filter');
    localStorage.removeItem('viewMode');
    localStorage.removeItem('prDashboardFilters');
    // now redirect
    window.location.replace("../index.php");
    </script>
    <noscript>
        <meta http-equiv="refresh" content="0;url=../index.php">
    </noscript>
</head>

<body></body>

</html>