<?php
// Simple environment variable loader
if (!function_exists('loadEnv')) {
    function loadEnv($path = '.env')
    {
        $envPath = dirname(__DIR__) . '/' . $path;

        if (!file_exists($envPath)) {
            // Return default values if .env doesn't exist
            return [
                'DB_HOST' => '127.0.0.1',
                'DB_USER' => 'root',
                'DB_PASS' => '',
                'DB_NAME' => 'jcrc_ch',
                'DB_PORT' => '3307',
                'PROXY_API' => '',
                'BACKUP_API' => '',
                'API_USER' => '',
                'API_PASS' => '',
                'SIGN_SECRET' => 'ok1p9a35u7v2z6rlhf8ytjd4nmeibcsgwvo',
                'UPLOAD_DIR' => 'uploads',
                'UPLOAD_MAX_SIZE' => '10485760',
                'UPLOAD_ALLOWED_TYPES' => 'image/jpeg,image/png,image/gif,application/pdf,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,text/csv,application/zip,application/octet-stream'
            ];
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $env = [];

        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                $env[$key] = $value;
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
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
return loadEnv();
?>