<?php
/**
 * Migration Runner
 * Executes SQL migration files
 */

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/db.php';

// Get database connection details
$host = $_ENV['DB_HOST'] ?? '127.0.0.1';
$user = $_ENV['DB_USER'] ?? 'root';
$pass = $_ENV['DB_PASS'] ?? '';
$dbname = $_ENV['DB_NAME'] ?? 'jcrc';
$port = $_ENV['DB_PORT'] ?? 3307;

// Get migration file from command line argument or use default
$migrationFile = $argv[1] ?? 'create_status_permissions_tables.sql';

$sqlFile = __DIR__ . '/migrations/' . $migrationFile;

if (!file_exists($sqlFile)) {
    die("Error: Migration file not found: $sqlFile\n");
}

echo "Connecting to database...\n";
$conn = new mysqli($host, $user, $pass, $dbname, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . "\n");
}

echo "Connected successfully!\n";
echo "Reading migration file: $migrationFile\n";

// Read SQL file
$sql = file_get_contents($sqlFile);

if ($sql === false) {
    die("Error: Could not read SQL file\n");
}

// Split SQL into individual statements
// Remove comments and split by semicolon
$sql = preg_replace('/--.*$/m', '', $sql); // Remove single-line comments
$sql = preg_replace('/\/\*.*?\*\//s', '', $sql); // Remove multi-line comments

// Split by semicolon, but keep in mind that semicolons might be inside strings
$statements = array_filter(
    array_map('trim', explode(';', $sql)),
    function($stmt) {
        return !empty($stmt) && strlen(trim($stmt)) > 0;
    }
);

echo "Found " . count($statements) . " SQL statements to execute\n\n";

$successCount = 0;
$errorCount = 0;

// Execute each statement
foreach ($statements as $index => $statement) {
    $statement = trim($statement);
    if (empty($statement)) {
        continue;
    }
    
    echo "Executing statement " . ($index + 1) . "...\n";
    
    // Show first 100 characters of statement for debugging
    $preview = substr($statement, 0, 100);
    if (strlen($statement) > 100) {
        $preview .= "...";
    }
    echo "  SQL: $preview\n";
    
    if ($conn->multi_query($statement)) {
        // Process all results
        do {
            if ($result = $conn->store_result()) {
                $result->free();
            }
        } while ($conn->next_result());
        
        echo "  ✓ Success\n\n";
        $successCount++;
    } else {
        echo "  ✗ Error: " . $conn->error . "\n\n";
        $errorCount++;
        
        // Continue with next statement even if one fails
        // (some statements might fail if tables/records already exist)
        if (strpos($conn->error, 'already exists') !== false || 
            strpos($conn->error, 'Duplicate entry') !== false) {
            echo "  (This is expected if table/record already exists)\n\n";
        }
    }
}

echo "\n========================================\n";
echo "Migration completed!\n";
echo "Successful: $successCount\n";
echo "Errors: $errorCount\n";
echo "========================================\n";

$conn->close();

