@echo off
REM ============================================
REM Add item details columns to proforma table
REM ============================================

setlocal enabledelayedexpansion

echo.
echo ============================================
echo   Add Proforma Item Columns Migration
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
    set DEFAULT_DB_NAME=jcrc
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

REM Check if SQL file exists (try simple version first, then complex)
if exist "database\migrations\add_proforma_item_columns_simple.sql" (
    set "SQL_FILE=database\migrations\add_proforma_item_columns_simple.sql"
) else if exist "database\migrations\add_proforma_item_columns.sql" (
    set "SQL_FILE=database\migrations\add_proforma_item_columns.sql"
) else (
    echo [ERROR] Migration SQL file not found!
    echo Please ensure add_proforma_item_columns.sql exists in database\migrations\
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
echo [INFO] Adding item_details_url and item_info columns to proforma table...
echo.

REM Build MySQL command
set "MYSQL_CMD=mysql -h %DB_HOST% -P %DB_PORT% -u %DB_USER%"

REM Add password if provided
if not "%DB_PASS%"=="" (
    set "MYSQL_CMD=%MYSQL_CMD% -p%DB_PASS%"
)

REM Add database name
set "MYSQL_CMD=%MYSQL_CMD% %DB_NAME%"

REM Execute MySQL command with SQL file
echo Executing migration...
%MYSQL_CMD% ^< "%SQL_FILE%"
set MIGRATION_ERROR=%ERRORLEVEL%

if %MIGRATION_ERROR% EQU 0 (
    echo.
    echo ============================================
    echo   Migration Completed Successfully!
    echo ============================================
    echo.
    echo The following columns have been added to the proforma table:
    echo   - item_details_url (VARCHAR(500)) - URL for item details upload
    echo   - item_info (TEXT) - Item code, name, price information
    echo.
    echo Both columns are optional (NULL allowed).
    echo.
) else (
    echo.
    echo ============================================
    echo   Migration Status
    echo ============================================
    echo.
    echo The migration has been executed.
    echo.
    echo NOTE: If you see "Duplicate column name" errors above,
    echo       this means the columns already exist and is SAFE TO IGNORE.
    echo.
    echo If you see other errors, please check:
    echo   1. Database connection settings are correct
    echo   2. Database exists and is accessible
    echo   3. User has necessary permissions (ALTER, etc.)
    echo   4. Review error messages above
    echo.
)

pause

