<?php
// Load environment variables
require_once __DIR__ . '/env.php';

// Ensure loadEnv is called to set $_ENV variables
if (function_exists('loadEnv')) {
    loadEnv();
}

// Check if .env file exists
$envFileExists = file_exists(dirname(__DIR__) . '/.env');
$envSource = $envFileExists ? '.env file' : 'fallback defaults';

$host = $_ENV['DB_HOST'] ?? '127.0.0.1';
$user = $_ENV['DB_USER'] ?? 'root';
$pass = $_ENV['DB_PASS'] ?? '';
$dbname = $_ENV['DB_NAME'] ?? 'jcrc_ch';
$port = $_ENV['DB_PORT'] ?? 3307;

// Helper function to check if logging is enabled
if (!function_exists('isLoggingEnabled')) {
    function isLoggingEnabled() {
        $logSetting = getenv('LOG');
        if ($logSetting === false) {
            $logSetting = $_ENV['LOG'] ?? 'true';
        }
        // Check if LOG is explicitly set to 'false' (case-insensitive)
        return strtolower(trim($logSetting)) !== 'false';
    }
}

// Function to log database connection
function logDbConnection($host, $user, $dbname, $port, $status, $error = null, $envSource = 'unknown', $errorCode = null) {
    // Check if logging is enabled before proceeding
    if (!isLoggingEnabled()) {
        return;
    }
    
    $logDir = dirname(__DIR__) . '/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . '/db_connection.log';
    $errorLogFile = $logDir . '/db_error.log';
    $timestamp = date('Y-m-d H:i:s');
    $statusText = $status === 'success' ? 'SUCCESS' : 'FAILED';
    
    // Connection log (all attempts)
    $logMessage = "[$timestamp] DB Connection $statusText - Host: $host, User: $user, Database: $dbname, Port: $port, Source: $envSource";
    
    if ($error) {
        $logMessage .= ", Error: $error";
    }
    
    $logMessage .= PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    
    // Error log (only failures with detailed info)
    if ($status === 'failed' && $error) {
        $lastError = error_get_last();
        $phpError = $lastError ? $lastError['message'] : 'N/A';
        
        $errorMessage = "[$timestamp] DB CONNECTION FAILED" . PHP_EOL;
        $errorMessage .= "  Host: $host" . PHP_EOL;
        $errorMessage .= "  User: $user" . PHP_EOL;
        $errorMessage .= "  Database: $dbname" . PHP_EOL;
        $errorMessage .= "  Port: $port" . PHP_EOL;
        $errorMessage .= "  Source: $envSource" . PHP_EOL;
        $errorMessage .= "  Error Code: " . ($errorCode ?? 'N/A') . PHP_EOL;
        $errorMessage .= "  Error Message: $error" . PHP_EOL;
        $errorMessage .= "  PHP Error: $phpError" . PHP_EOL;
        $errorMessage .= "  File: " . __FILE__ . PHP_EOL;
        $errorMessage .= "  Line: " . __LINE__ . PHP_EOL;
        $errorMessage .= str_repeat('-', 80) . PHP_EOL;
        
        file_put_contents($errorLogFile, $errorMessage, FILE_APPEND | LOCK_EX);
    }
}

$conn = new mysqli($host, $user, $pass, $dbname, $port);
if ($conn->connect_error) {
    logDbConnection($host, $user, $dbname, $port, 'failed', $conn->connect_error, $envSource, $conn->connect_errno);
    die("Connection failed: " . $conn->connect_error);
} else {
    logDbConnection($host, $user, $dbname, $port, 'success', null, $envSource);
}
?>
