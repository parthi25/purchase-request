<?php
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

// Simple environment variable loader
if (!function_exists('loadEnv')) {
    function loadEnv($path = '.env')
    {
        $envPath = dirname(__DIR__) . '/' . $path;

        // Default values
        $defaults = [
            'DB_HOST' => '127.0.0.1',
            'DB_USER' => 'root',
            'DB_PASS' => '',
            'DB_NAME' => 'jcrc',
            'DB_PORT' => '3307',
            'PROXY_API' => '',
            'BACKUP_API' => '',
            'API_USER' => '',
            'API_PASS' => '',
            'SIGN_SECRET' => 'ok1p9a35u7v2z6rlhf8ytjd4nmeibcsgwvo',
            'UPLOAD_DIR' => 'uploads',
            'UPLOAD_MAX_SIZE' => '10485760',
            'UPLOAD_ALLOWED_TYPES' => 'image/jpeg,image/png,image/gif,application/pdf,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,text/csv,application/zip,application/octet-stream',
            'LOG' => 'true'
        ];

        // Start with defaults
        $env = $defaults;
        
        // First, quickly check for LOG in .env file to determine if we should log
        $logEnabled = true; // default
        if (file_exists($envPath)) {
            $content = file_get_contents($envPath);
            // Remove BOM if present
            if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
                $content = substr($content, 3);
            }
            // Convert UTF-16 to UTF-8 if needed
            if (substr($content, 0, 2) === "\xFF\xFE" || substr($content, 0, 2) === "\xFE\xFF") {
                $content = mb_convert_encoding($content, 'UTF-8', 'UTF-16');
            }
            // Quick scan for LOG setting
            if (preg_match('/^LOG\s*=\s*(.+)$/mi', $content, $matches)) {
                $logValue = trim($matches[1], ' "\'');
                $logEnabled = strtolower($logValue) !== 'false';
            }
        } else {
            // Check environment variable if .env doesn't exist
            $logEnabled = isLoggingEnabled();
        }

        // Load from .env file if it exists (this will override defaults)
        $logDir = dirname(__DIR__) . '/logs';
        $envLogFile = $logDir . '/env_loading.log';
        
        // Only create log directory if logging is enabled
        if ($logEnabled) {
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }
        }
        
        if (file_exists($envPath)) {
            // Read file content and handle encoding issues
            $content = file_get_contents($envPath);
            
            // Remove BOM if present
            if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
                $content = substr($content, 3);
            }
            
            // Convert UTF-16 to UTF-8 if needed
            if (substr($content, 0, 2) === "\xFF\xFE" || substr($content, 0, 2) === "\xFE\xFF") {
                $content = mb_convert_encoding($content, 'UTF-8', 'UTF-16');
            }
            
            // Split into lines
            $lines = preg_split('/\r\n|\r|\n/', $content);
            $lines = array_filter($lines, function($line) {
                return trim($line) !== '';
            });
            
            if ($logEnabled) {
                $logMessage = "[" . date('Y-m-d H:i:s') . "] Loading .env file from: $envPath\n";
                $logMessage .= "  Total lines read: " . count($lines) . "\n";
                file_put_contents($envLogFile, $logMessage, FILE_APPEND | LOCK_EX);
            }

            foreach ($lines as $lineNum => $line) {
                // Trim the line first
                $line = trim($line);
                
                // Skip empty lines and comments
                if (empty($line) || strpos($line, '#') === 0) {
                    continue;
                }

                // Check if line contains = sign
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value);
                    
                    // Skip if key is empty
                    if (empty($key)) {
                        continue;
                    }
                    
                    // Remove quotes if present
                    $value = trim($value, '"\'');
                    
                    // Override default with .env value
                    $env[$key] = $value;
                    // Force update $_ENV and putenv (override any existing values)
                    $_ENV[$key] = $value;
                    putenv("$key=$value");
                    
                    // Log what was loaded (only for DB_* variables to reduce log size)
                    if ($logEnabled && strpos($key, 'DB_') === 0) {
                        $logMessage = "  Line " . ($lineNum + 1) . ": $key = '$value' (set in \$_ENV)\n";
                        file_put_contents($envLogFile, $logMessage, FILE_APPEND | LOCK_EX);
                    }
                    
                    // Special check for DB_NAME
                    if ($logEnabled && $key === 'DB_NAME') {
                        $logMessage = "  *** DB_NAME FOUND: '$value' (from line " . ($lineNum + 1) . ") ***\n";
                        file_put_contents($envLogFile, $logMessage, FILE_APPEND | LOCK_EX);
                    }
                }
            }
        } else {
            if ($logEnabled) {
                $logMessage = "[" . date('Y-m-d H:i:s') . "] .env file NOT found at: $envPath - Using defaults\n";
                file_put_contents($envLogFile, $logMessage, FILE_APPEND | LOCK_EX);
            }
            
            // If .env doesn't exist, set defaults in $_ENV and putenv
            foreach ($defaults as $key => $value) {
                if (!isset($_ENV[$key])) {
                    $_ENV[$key] = $value;
                    putenv("$key=$value");
                }
            }
        }
        
        // Log final values - check both $env array and $_ENV
        if ($logEnabled) {
            $logMessage = "[" . date('Y-m-d H:i:s') . "] Final DB values:\n";
            $logMessage .= "  \$env['DB_HOST']: " . ($env['DB_HOST'] ?? 'N/A') . "\n";
            $logMessage .= "  \$env['DB_NAME']: " . ($env['DB_NAME'] ?? 'N/A') . "\n";
            $logMessage .= "  \$_ENV['DB_HOST']: " . ($_ENV['DB_HOST'] ?? 'N/A') . "\n";
            $logMessage .= "  \$_ENV['DB_NAME']: " . ($_ENV['DB_NAME'] ?? 'N/A') . "\n";
            $logMessage .= "  getenv('DB_NAME'): " . (getenv('DB_NAME') ?: 'N/A') . "\n";
            $logMessage .= str_repeat('-', 80) . "\n";
            file_put_contents($envLogFile, $logMessage, FILE_APPEND | LOCK_EX);
        }

        return $env;
    }
}

// Helper function to get upload configuration
if (!function_exists('getUploadConfig')) {
    function getUploadConfig() {
        $env = loadEnv();
        return [
            'dir' => $env['UPLOAD_DIR'] ?? 'uploads',
            'max_size' => intval($env['UPLOAD_MAX_SIZE'] ?? 10485760),
            'allowed_types' => explode(',', $env['UPLOAD_ALLOWED_TYPES'] ?? 'image/jpeg,image/png,image/gif,application/pdf,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,text/csv,application/zip,application/octet-stream')
        ];
    }
}

// Load environment variables and return the array
// This ensures $_ENV is populated when the file is required
$env = loadEnv();

// Also ensure all values are in $_ENV (double-check)
foreach ($env as $key => $value) {
    $_ENV[$key] = $value;
    putenv("$key=$value");
}

return $env;
?>