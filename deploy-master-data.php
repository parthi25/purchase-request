<?php
/**
 * Master Data Deployment Script (PHP Version)
 * 
 * This script deploys all master data to the database
 * including statuses, roles, permissions, and status flows
 * 
 * Usage: php deploy-master-data.php
 */

// Load database configuration
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/db.php';

// Get database credentials
$host = $_ENV['DB_HOST'] ?? '127.0.0.1';
$user = $_ENV['DB_USER'] ?? 'root';
$pass = $_ENV['DB_PASS'] ?? '';
$dbname = $_ENV['DB_NAME'] ?? 'jcrc';
$port = $_ENV['DB_PORT'] ?? 3307;

echo "\n";
echo "============================================\n";
echo "  Master Data Deployment Script\n";
echo "============================================\n";
echo "\n";

echo "[INFO] Database Configuration:\n";
echo "  Host: $host\n";
echo "  Port: $port\n";
echo "  User: $user\n";
echo "  Database: $dbname\n";
echo "\n";

// Check if SQL file exists
$sqlFile = __DIR__ . '/database/master_data.sql';
if (!file_exists($sqlFile)) {
    die("[ERROR] database/master_data.sql not found!\n");
}

echo "[INFO] Reading SQL file...\n";
$sql = file_get_contents($sqlFile);

if ($sql === false) {
    die("[ERROR] Failed to read SQL file!\n");
}

echo "[INFO] Connecting to database...\n";
$conn = new mysqli($host, $user, $pass, $dbname, $port);

if ($conn->connect_error) {
    die("[ERROR] Connection failed: " . $conn->connect_error . "\n");
}

echo "[INFO] Connected successfully!\n";
echo "[INFO] Deploying master data...\n";
echo "\n";

// Execute SQL file
// Split by semicolon and execute each statement
$statements = array_filter(
    array_map('trim', explode(';', $sql)),
    function($stmt) {
        return !empty($stmt) && 
               !preg_match('/^(SET|START|COMMIT|--)/i', $stmt) &&
               !preg_match('/^\/\*/', $stmt);
    }
);

$successCount = 0;
$errorCount = 0;
$errors = [];

foreach ($statements as $statement) {
    // Skip comments and empty statements
    $statement = trim($statement);
    if (empty($statement) || strpos($statement, '--') === 0) {
        continue;
    }
    
    // Remove multi-line comments
    $statement = preg_replace('/\/\*.*?\*\//s', '', $statement);
    $statement = trim($statement);
    
    if (empty($statement)) {
        continue;
    }
    
    if ($conn->multi_query($statement)) {
        do {
            // Store first result set
            if ($result = $conn->store_result()) {
                $result->free();
            }
        } while ($conn->next_result());
        
        $successCount++;
    } else {
        $errorCount++;
        $errors[] = $conn->error;
        echo "[WARNING] Error executing statement: " . substr($statement, 0, 50) . "...\n";
        echo "  Error: " . $conn->error . "\n";
    }
}

echo "\n";
echo "============================================\n";
if ($errorCount === 0) {
    echo "  Deployment Successful!\n";
    echo "============================================\n";
    echo "\n";
    echo "Master data has been deployed successfully.\n";
    echo "  - Executed statements: $successCount\n";
    echo "\n";
    echo "Next steps:\n";
    echo "  1. Create users with roles (admin, buyer, B_Head, PO_Team, PO_Team_Member)\n";
    echo "  2. Map categories to buyer heads using catbasbh table\n";
    echo "  3. Map buyers to buyer heads using buyers_info table\n";
    echo "  4. Start using the system!\n";
    echo "\n";
} else {
    echo "  Deployment Completed with Warnings\n";
    echo "============================================\n";
    echo "\n";
    echo "  - Successful statements: $successCount\n";
    echo "  - Errors: $errorCount\n";
    echo "\n";
    if (!empty($errors)) {
        echo "Errors encountered:\n";
        foreach (array_unique($errors) as $error) {
            echo "  - $error\n";
        }
        echo "\n";
    }
    echo "Note: Some errors may be expected if data already exists.\n";
    echo "      Check the database to verify the deployment.\n";
    echo "\n";
}

$conn->close();
?>

