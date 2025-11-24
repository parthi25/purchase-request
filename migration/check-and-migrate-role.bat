@echo off
REM ============================================
REM Check and Migrate Users Role from ENUM to role_id
REM ============================================
REM This script checks the current database state and only
REM runs the migration if needed. It also ensures master data is inserted.
REM ============================================

setlocal enabledelayedexpansion

echo.
echo ============================================
echo Users Role Migration Checker
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
    php -r "require 'config/env.php'; require 'config/db.php'; echo 'DB_HOST=' . ($_ENV['DB_HOST'] ?? '127.0.0.1') . PHP_EOL; echo 'DB_USER=' . ($_ENV['DB_USER'] ?? 'root') . PHP_EOL; echo 'DB_PASS=' . ($_ENV['DB_PASS'] ?? '') . PHP_EOL; echo 'DB_NAME=' . ($_ENV['DB_NAME'] ?? 'jcrc_ch') . PHP_EOL; echo 'DB_PORT=' . ($_ENV['DB_PORT'] ?? '3307') . PHP_EOL;" > %TEMP%\db_config.txt 2>nul
    for /f "tokens=1,2 delims==" %%a in (%TEMP%\db_config.txt) do (
        if "%%a"=="DB_HOST" set DB_HOST=%%b
        if "%%a"=="DB_USER" set DB_USER=%%b
        if "%%a"=="DB_PASS" set DB_PASS=%%b
        if "%%a"=="DB_NAME" set DEFAULT_DB_NAME=%%b
        if "%%a"=="DB_PORT" set DB_PORT=%%b
    )
    del %TEMP%\db_config.txt 2>nul
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
echo Available database from .env file: %DEFAULT_DB_NAME%
echo.
set /p DB_NAME="Enter database name to check/migrate (press Enter for '%DEFAULT_DB_NAME%'): "
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

REM Prompt for password if not set
if "%DB_PASS%"=="" (
    set /p DB_PASS="Enter MySQL password (press Enter if no password): "
)

echo.
echo [INFO] Database Configuration:
echo   Host: %DB_HOST%
echo   Port: %DB_PORT%
echo   User: %DB_USER%
echo   Database: %DB_NAME%
echo.

REM Build MySQL command
set "MYSQL_CMD=mysql -h %DB_HOST% -P %DB_PORT% -u %DB_USER%"
if not "%DB_PASS%"=="" (
    set "MYSQL_CMD=%MYSQL_CMD% -p%DB_PASS%"
)
set "MYSQL_CMD=%MYSQL_CMD% %DB_NAME%"

REM Check if check SQL file exists, if not create it
if not exist "migration\check_role_state.sql" (
    echo [WARNING] check_role_state.sql not found. Creating it...
    (
        echo -- Check current state of users table for role migration
        echo SELECT 
        echo     ^(SELECT COUNT^(*^) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE^(^) AND TABLE_NAME = 'users' AND COLUMN_NAME = 'role_id'^) AS role_id_exists,
        echo     ^(SELECT COUNT^(*^) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE^(^) AND TABLE_NAME = 'users' AND COLUMN_NAME = 'role' AND DATA_TYPE = 'enum'^) AS role_enum_exists,
        echo     ^(SELECT COUNT^(*^) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE^(^) AND TABLE_NAME = 'users' AND CONSTRAINT_NAME = 'fk_users_role'^) AS fk_exists;
    ) > "migration\check_role_state.sql"
)

REM Run the check query
echo [INFO] Checking current database state...
%MYSQL_CMD% < "migration\check_role_state.sql" > "%TEMP%\check_result.txt" 2>&1

if %errorlevel% neq 0 (
    echo [ERROR] Failed to connect to database
    echo Please check your database credentials
    type "%TEMP%\check_result.txt"
    del "%TEMP%\check_result.txt" 2>nul
    pause
    exit /b 1
)

REM Read the check results (skip header line)
set ROLE_ID_EXISTS=0
set ROLE_ENUM_EXISTS=0
set FK_EXISTS=0

for /f "tokens=1,2,3 skip=1" %%a in ('type "%TEMP%\check_result.txt"') do (
    set ROLE_ID_EXISTS=%%a
    set ROLE_ENUM_EXISTS=%%b
    set FK_EXISTS=%%c
    goto :found_result
)

:found_result
del "%TEMP%\check_result.txt" 2>nul

echo.
echo [INFO] Current State:
echo   role_id column exists: %ROLE_ID_EXISTS%
echo   role enum column exists: %ROLE_ENUM_EXISTS%
echo   Foreign key exists: %FK_EXISTS%
echo.

REM Determine if migration is needed
set MIGRATION_NEEDED=0

if "%ROLE_ID_EXISTS%"=="0" (
    echo [ACTION NEEDED] role_id column does not exist
    set MIGRATION_NEEDED=1
)

if "%ROLE_ENUM_EXISTS%"=="1" (
    echo [ACTION NEEDED] role enum column still exists
    set MIGRATION_NEEDED=1
)

if "%FK_EXISTS%"=="0" (
    echo [ACTION NEEDED] Foreign key constraint does not exist
    set MIGRATION_NEEDED=1
)

if "%MIGRATION_NEEDED%"=="0" (
    echo.
    echo ============================================
    echo Migration Already Completed!
    echo ============================================
    echo The users table has already been migrated.
    echo role_id column exists, role enum removed, and foreign key is in place.
    echo.
    goto :check_master_data
) else (
    echo.
    echo ============================================
    echo Migration Required
    echo ============================================
    echo The database needs to be migrated.
    echo.
    set /p CONFIRM="Do you want to proceed with the migration? (Y/N): "
    if /i not "!CONFIRM!"=="Y" (
        echo Migration cancelled.
        pause
        exit /b 0
    )
    
    echo.
    echo [INFO] Running migration...
    
    REM Check if migration file exists
    if not exist "migration\alter_users_role_to_id.sql" (
        echo [ERROR] migration\alter_users_role_to_id.sql not found!
        pause
        exit /b 1
    )
    
    %MYSQL_CMD% < "migration\alter_users_role_to_id.sql"
    
    if %errorlevel% neq 0 (
        echo.
        echo [ERROR] Migration failed!
        echo Please check the error messages above.
        pause
        exit /b 1
    )
    
    echo.
    echo ============================================
    echo Migration Completed Successfully!
    echo ============================================
    echo.
)

:check_master_data
echo.
echo ============================================
echo Checking and Inserting Master Data
echo ============================================
echo.

REM Check if master data SQL file exists
if exist "migration\insert_master_data.sql" (
    echo [INFO] Inserting/updating master data from insert_master_data.sql...
    %MYSQL_CMD% < "migration\insert_master_data.sql" > "%TEMP%\master_data_result.txt" 2>&1
    
    if %errorlevel% neq 0 (
        echo [WARNING] Master data insertion had some issues.
        echo This is usually okay if data already exists or tables don't exist yet.
        type "%TEMP%\master_data_result.txt" | findstr /i "error warning" 2>nul
    ) else (
        echo [SUCCESS] Master data checked and inserted successfully.
    )
    del "%TEMP%\master_data_result.txt" 2>nul
) else (
    echo [WARNING] insert_master_data.sql not found. Skipping master data insertion.
    echo You can run it manually if needed.
)

echo.
echo ============================================
echo All Checks and Migrations Completed!
echo ============================================
echo.
echo Summary:
echo   - Database structure checked
echo   - Migration applied if needed
echo   - Master data verified and inserted
echo.
echo Next steps:
echo   1. Update application code to use role_id
echo   2. Test all user-related functionality
echo   3. Verify role-based permissions work correctly
echo.

pause
endlocal
