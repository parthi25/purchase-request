@echo off
REM ============================================
REM Database Migration Script
REM ============================================
REM This script runs the complete migration including:
REM - Table renaming
REM - New table creation
REM - Foreign key relationships
REM - Indexes
REM - Master data
REM ============================================

setlocal enabledelayedexpansion

echo.
echo ============================================
echo   Database Migration Script
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
set /p DB_NAME="Enter database name to migrate (press Enter for '%DEFAULT_DB_NAME%'): "
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

REM Check if SQL files exist
if not exist "migration\complete_migration.sql" (
    echo [ERROR] migration\complete_migration.sql not found!
    pause
    exit /b 1
)

if not exist "database\migrations\alter_buyer_head_categories_structure.sql" (
    echo [WARNING] database\migrations\alter_buyer_head_categories_structure.sql not found!
    echo This migration will be skipped.
    set SKIP_ALTER_MIGRATION=1
) else (
    set SKIP_ALTER_MIGRATION=0
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
echo [WARNING] This will modify your database structure and data.
echo [INFO] Starting migration...
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
echo [INFO] Running complete migration...
%MYSQL_CMD% < "migration\complete_migration.sql"
set MIGRATION_SUCCESS=%ERRORLEVEL%

if %MIGRATION_SUCCESS% EQU 0 (
    REM Run additional migration for buyer_head_categories structure
    if %SKIP_ALTER_MIGRATION% EQU 0 (
        echo.
        echo [INFO] Running buyer_head_categories structure migration...
        %MYSQL_CMD% < "database\migrations\alter_buyer_head_categories_structure.sql"
        
        if %ERRORLEVEL% EQU 0 (
            echo [INFO] buyer_head_categories structure migration completed successfully.
        ) else (
            echo [WARNING] buyer_head_categories structure migration had errors, but continuing...
            set MIGRATION_SUCCESS=1
        )
    )
)

if %MIGRATION_SUCCESS% EQU 0 (
    echo.
    echo ============================================
    echo   Migration Successful!
    echo ============================================
    echo.
    echo The database has been migrated successfully.
    echo.
    echo What was done:
    echo   1. Renamed tables to follow better naming conventions
    echo   2. Created new permission and workflow tables
    echo   3. Added foreign key relationships
    echo   4. Added performance indexes
    echo   5. Inserted master data (statuses, permissions, workflows)
    if %SKIP_ALTER_MIGRATION% EQU 0 (
        echo   6. Altered buyer_head_categories table structure (removed Name and cat columns, added cat_id)
    )
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
    echo   Migration Failed!
    echo ============================================
    echo.
    echo There was an error during migration.
    echo Please check:
    echo   1. Database connection settings are correct
    echo   2. Database exists and is accessible
    echo   3. User has necessary permissions (ALTER, CREATE, INSERT, etc.)
    echo   4. Review error messages above
    echo.
    echo You can also manually run:
    echo   mysql -h %DB_HOST% -P %DB_PORT% -u %DB_USER% -p %DB_NAME% ^< migration\complete_migration.sql
    echo.
)

pause

