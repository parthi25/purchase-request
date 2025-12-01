@echo off
REM ============================================
REM Add db_column_name to status_modal_fields
REM ============================================
REM This script adds the db_column_name column
REM to status_modal_fields table and updates
REM existing remark fields with their column mappings
REM ============================================

setlocal enabledelayedexpansion

echo.
echo ============================================
echo   Add db_column_name Migration
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
    php -r "require 'config/env.php'; require 'config/db.php'; echo 'DB_HOST=' . ($_ENV['DB_HOST'] ?? '127.0.0.1') . PHP_EOL; echo 'DB_USER=' . ($_ENV['DB_USER'] ?? 'root') . PHP_EOL; echo 'DB_PASS=' . ($_ENV['DB_PASS'] ?? '') . PHP_EOL; echo 'DB_NAME=' . ($_ENV['DB_NAME'] ?? 'jcrc_ch') . PHP_EOL; echo 'DB_PORT=' . ($_ENV['DB_PORT'] ?? '3307') . PHP_EOL;" > %TEMP%\db_config.txt
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
    echo You can also manually run the SQL file:
    echo   mysql -h %DB_HOST% -P %DB_PORT% -u %DB_USER% -p %DB_NAME% ^< database\migrations\add_db_column_name_to_status_modal_fields.sql
    echo.
    pause
    exit /b 1
)

REM Check if SQL file exists
if not exist "database\migrations\add_db_column_name_to_status_modal_fields.sql" (
    echo [ERROR] database\migrations\add_db_column_name_to_status_modal_fields.sql not found!
    echo Please ensure the migration file exists in the database\migrations folder.
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
echo [WARNING] This will modify your database structure.
echo [INFO] Adding db_column_name column to status_modal_fields table...
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
%MYSQL_CMD% < "database\migrations\add_db_column_name_to_status_modal_fields.sql"

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ============================================
    echo   Migration Successful!
    echo ============================================
    echo.
    echo The db_column_name column has been added to status_modal_fields table.
    echo Existing remark fields have been updated with their column mappings.
    echo.
    echo Next steps:
    echo   1. Use the superadmin page to configure db_column_name for new status flows
    echo   2. The system will now use database-stored column names instead of hardcoded mappings
    echo.
) else (
    echo.
    echo ============================================
    echo   Migration Failed!
    echo ============================================
    echo.
    echo There was an error running the migration.
    echo Please check:
    echo   1. Database connection settings are correct
    echo   2. Database exists and is accessible
    echo   3. User has necessary permissions (ALTER, UPDATE, etc.)
    echo   4. Column might already exist (this is safe to ignore)
    echo   5. Review error messages above
    echo.
)

pause

