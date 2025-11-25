@echo off
REM ============================================
REM One-Click Master Data Deployment Script
REM ============================================
REM This script deploys all master data to the database
REM including statuses, roles, permissions, and status flows
REM ============================================

setlocal enabledelayedexpansion

echo.
echo ============================================
echo   Master Data Deployment Script
echo ============================================
echo.

REM Get script directory
set "SCRIPT_DIR=%~dp0"
cd /d "%SCRIPT_DIR%"

REM Check if PHP is available (preferred method)
where php >nul 2>&1
if %ERRORLEVEL% EQU 0 (
    if exist "deploy-master-data.php" (
        echo [INFO] Using PHP deployment script (recommended)...
        echo.
        php deploy-master-data.php
        pause
        exit /b %ERRORLEVEL%
    )
)

REM Fallback to MySQL direct method
echo [INFO] Using MySQL direct method...
echo.

REM Check if .env file exists
if not exist "config\env.php" (
    echo [WARNING] config\env.php not found. Using default database settings.
    set DB_HOST=127.0.0.1
    set DB_USER=root
    set DB_PASS=
    set DB_NAME=jcrc
    set DB_PORT=3307
) else (
    REM Try to extract database settings from env.php (basic parsing)
    REM Note: This is a simple parser - for production, use PHP to read .env properly
    echo [INFO] Reading database configuration from config\env.php...
    
    REM Default values
    set DB_HOST=127.0.0.1
    set DB_USER=root
    set DB_PASS=
    set DB_NAME=jcrc
    set DB_PORT=3307
    
    REM Try to read from .env file if it exists
    if exist ".env" (
        for /f "tokens=1,2 delims==" %%a in ('.env') do (
            if "%%a"=="DB_HOST" set DB_HOST=%%b
            if "%%a"=="DB_USER" set DB_USER=%%b
            if "%%a"=="DB_PASS" set DB_PASS=%%b
            if "%%a"=="DB_NAME" set DB_NAME=%%b
            if "%%a"=="DB_PORT" set DB_PORT=%%b
        )
    )
)

REM Check if MySQL is available
where mysql >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo [ERROR] MySQL command line client not found in PATH.
    echo Please ensure MySQL is installed and mysql.exe is in your system PATH.
    echo.
    echo You can also manually run the SQL file:
    echo   mysql -h %DB_HOST% -P %DB_PORT% -u %DB_USER% -p %DB_NAME% ^< database\master_data.sql
    echo.
    pause
    exit /b 1
)

REM Check if SQL file exists
if not exist "database\master_data.sql" (
    echo [ERROR] database\master_data.sql not found!
    echo Please ensure the master_data.sql file exists in the database folder.
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
echo [INFO] Deploying master data...
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
%MYSQL_CMD% < "database\master_data.sql"

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ============================================
    echo   Deployment Successful!
    echo ============================================
    echo.
    echo Master data has been deployed successfully.
    echo.
    echo Next steps:
    echo   1. Create users with roles (admin, buyer, B_Head, PO_Team, PO_Team_Member)
    echo   2. Map categories to buyer heads using buyer_head_categories table
    echo   3. Map buyers to buyer heads using buyers_info table
    echo   4. Start using the system!
    echo.
) else (
    echo.
    echo ============================================
    echo   Deployment Failed!
    echo ============================================
    echo.
    echo There was an error deploying the master data.
    echo Please check:
    echo   1. Database connection settings are correct
    echo   2. Database exists and is accessible
    echo   3. User has necessary permissions
    echo   4. All required tables exist (run migrations first)
    echo.
    echo You can also manually run:
    echo   mysql -h %DB_HOST% -P %DB_PORT% -u %DB_USER% -p %DB_NAME% ^< database\master_data.sql
    echo.
)

pause

