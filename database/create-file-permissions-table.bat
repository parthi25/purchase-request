@echo off
REM ============================================
REM Create file_upload_permissions table
REM ============================================

setlocal enabledelayedexpansion

echo.
echo ============================================
echo   Create file_upload_permissions Table
echo ============================================
echo.

REM Get script directory
set "SCRIPT_DIR=%~dp0"
cd /d "%SCRIPT_DIR%"
cd ..

REM Check if PHP is available (preferred method)
where php >nul 2>&1
if %ERRORLEVEL% EQU 0 (
    echo [INFO] Using PHP to read database configuration...
    echo.
    php -r "require 'config/env.php'; require 'config/db.php'; echo 'DB_HOST=' . ($_ENV['DB_HOST'] ?? '127.0.0.1') . PHP_EOL; echo 'DB_USER=' . ($_ENV['DB_USER'] ?? 'root') . PHP_EOL; echo 'DB_PASS=' . ($_ENV['DB_PASS'] ?? '') . PHP_EOL; echo 'DB_NAME=' . ($_ENV['DB_NAME'] ?? 'jcrc') . PHP_EOL; echo 'DB_PORT=' . ($_ENV['DB_PORT'] ?? '3307') . PHP_EOL;" > %TEMP%\db_config.txt
    for /f "tokens=1,2 delims==" %%a in (%TEMP%\db_config.txt) do (
        if "%%a"=="DB_HOST" set DB_HOST=%%b
        if "%%a"=="DB_USER" set DB_USER=%%b
        if "%%a"=="DB_PASS" set DB_PASS=%%b
        if "%%a"=="DB_NAME" set DEFAULT_DB_NAME=%%b
        if "%%a"=="DB_PORT" set DB_PORT=%%b
    )
    del %TEMP%\db_config.txt
) else (
    echo [WARNING] PHP not found. Using default database settings.
    set DB_HOST=127.0.0.1
    set DB_USER=root
    set DB_PASS=
    set DEFAULT_DB_NAME=jcrc_ch
    set DB_PORT=3307
)

REM Prompt user to choose database
echo.
echo Available databases from .env file: %DEFAULT_DB_NAME%
echo.
set /p DB_NAME="Enter database name (press Enter for '%DEFAULT_DB_NAME%'): "
if "%DB_NAME%"=="" set DB_NAME=%DEFAULT_DB_NAME%
echo.
echo [INFO] Selected database: %DB_NAME%
echo.

REM Check if MySQL is available
where mysql >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo [ERROR] MySQL command line client not found in PATH.
    echo Please ensure MySQL is installed and mysql.exe is in your system PATH.
    echo.
    pause
    exit /b 1
)

REM Check if SQL file exists
if not exist "database\migrations\create_file_upload_permissions_standalone.sql" (
    echo [ERROR] database\migrations\create_file_upload_permissions_standalone.sql not found!
    pause
    exit /b 1
)

echo [INFO] Database Configuration:
echo   Host: %DB_HOST%
echo   Port: %DB_PORT%
echo   User: %DB_USER%
echo   Database: %DB_NAME%
echo.

REM Prompt for password if not set
if "%DB_PASS%"=="" (
    set /p DB_PASS="Enter MySQL password (press Enter if no password): "
)

echo.
echo [INFO] Creating file_upload_permissions table...
echo.

REM Build MySQL command
set "MYSQL_CMD=mysql -h %DB_HOST% -P %DB_PORT% -u %DB_USER%"

REM Add password if provided
if not "%DB_PASS%"=="" (
    set "MYSQL_CMD=%MYSQL_CMD% -p%DB_PASS%"
)

REM Add database name and SQL file
set "MYSQL_CMD=%MYSQL_CMD% %DB_NAME%"

REM Execute MySQL command with SQL file
%MYSQL_CMD% < "database\migrations\create_file_upload_permissions_standalone.sql"

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ============================================
    echo   Table Created Successfully!
    echo ============================================
    echo.
    echo The file_upload_permissions table has been created.
    echo.
) else (
    echo.
    echo ============================================
    echo   Creation Failed!
    echo ============================================
    echo.
    echo There was an error creating the table.
    echo Please check:
    echo   1. Database connection settings are correct
    echo   2. Database exists and is accessible
    echo   3. User has necessary permissions (CREATE, INSERT, etc.)
    echo   4. Review error messages above
    echo.
)

pause

