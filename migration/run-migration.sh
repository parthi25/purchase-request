#!/bin/bash
# ============================================
# Database Migration Script (Linux/Mac)
# ============================================
# This script runs the complete migration including:
# - Table renaming
# - New table creation
# - Foreign key relationships
# - Indexes
# - Master data
# ============================================

set -e  # Exit on error

echo ""
echo "============================================"
echo "  Database Migration Script"
echo "============================================"
echo ""

# Get script directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"
cd ..

# Check if PHP is available
if command -v php &> /dev/null; then
    echo "[INFO] Using PHP to read database configuration..."
    echo ""
    
    # Read database config from PHP
    DB_CONFIG=$(php -r "
        require 'config/env.php';
        require 'config/db.php';
        echo 'DB_HOST=' . (\$_ENV['DB_HOST'] ?? '127.0.0.1') . PHP_EOL;
        echo 'DB_USER=' . (\$_ENV['DB_USER'] ?? 'root') . PHP_EOL;
        echo 'DB_PASS=' . (\$_ENV['DB_PASS'] ?? '') . PHP_EOL;
        echo 'DB_NAME=' . (\$_ENV['DB_NAME'] ?? 'jcrc') . PHP_EOL;
        echo 'DB_PORT=' . (\$_ENV['DB_PORT'] ?? '3307') . PHP_EOL;
    ")
    
    # Parse config
    while IFS='=' read -r key value; do
        case "$key" in
            DB_HOST) DB_HOST="$value" ;;
            DB_USER) DB_USER="$value" ;;
            DB_PASS) DB_PASS="$value" ;;
            DB_NAME) DEFAULT_DB_NAME="$value" ;;
            DB_PORT) DB_PORT="$value" ;;
        esac
    done <<< "$DB_CONFIG"
else
    echo "[WARNING] PHP not found. Using default database settings."
    DB_HOST="127.0.0.1"
    DB_USER="root"
    DB_PASS=""
    DEFAULT_DB_NAME="jcrc_ch"
    DB_PORT="3307"
fi

# Prompt user to choose database
echo ""
echo "Available databases from .env file: $DEFAULT_DB_NAME"
echo ""
read -p "Enter database name to migrate (press Enter for '$DEFAULT_DB_NAME'): " DB_NAME
if [ -z "$DB_NAME" ]; then
    DB_NAME="$DEFAULT_DB_NAME"
fi
echo ""
echo "[INFO] Selected database: $DB_NAME"
echo ""

# Check if MySQL is available
if ! command -v mysql &> /dev/null; then
    echo "[ERROR] MySQL command line client not found in PATH."
    echo "Please ensure MySQL is installed and mysql is in your system PATH."
    echo ""
    exit 1
fi

# Check if SQL file exists
if [ ! -f "migration/complete_migration.sql" ]; then
    echo "[ERROR] migration/complete_migration.sql not found!"
    exit 1
fi

echo "[INFO] Database Configuration:"
echo "  Host: $DB_HOST"
echo "  Port: $DB_PORT"
echo "  User: $DB_USER"
echo "  Database: $DB_NAME"
echo ""

# Prompt for password if not set
if [ -z "$DB_PASS" ]; then
    read -sp "Enter MySQL password (press Enter if no password): " DB_PASS
    echo ""
fi

echo ""
echo "[WARNING] This will modify your database structure and data."
echo "[INFO] Starting migration..."
echo ""

# Build MySQL command
MYSQL_CMD="mysql -h $DB_HOST -P $DB_PORT -u $DB_USER"

# Add password if provided
if [ -n "$DB_PASS" ]; then
    MYSQL_CMD="$MYSQL_CMD -p$DB_PASS"
fi

# Add database name and SQL file
MYSQL_CMD="$MYSQL_CMD $DB_NAME"

# Execute MySQL command with SQL file
if $MYSQL_CMD < "migration/complete_migration.sql"; then
    echo ""
    echo "============================================"
    echo "  Migration Successful!"
    echo "============================================"
    echo ""
    echo "The database has been migrated successfully."
    echo ""
    echo "What was done:"
    echo "  1. Renamed tables to follow better naming conventions"
    echo "  2. Created new permission and workflow tables"
    echo "  3. Added foreign key relationships"
    echo "  4. Added performance indexes"
    echo "  5. Inserted master data (statuses, permissions, workflows)"
    echo ""
    echo "Next steps:"
    echo "  1. Create users with roles (admin, buyer, B_Head, PO_Team, PO_Team_Member)"
    echo "  2. Map categories to buyer heads using catbasbh table"
    echo "  3. Map buyers to buyer heads using buyers_info table"
    echo "  4. Start using the system!"
    echo ""
else
    echo ""
    echo "============================================"
    echo "  Migration Failed!"
    echo "============================================"
    echo ""
    echo "There was an error during migration."
    echo "Please check:"
    echo "  1. Database connection settings are correct"
    echo "  2. Database exists and is accessible"
    echo "  3. User has necessary permissions (ALTER, CREATE, INSERT, etc.)"
    echo "  4. Review error messages above"
    echo ""
    echo "You can also manually run:"
    echo "  mysql -h $DB_HOST -P $DB_PORT -u $DB_USER -p $DB_NAME < migration/complete_migration.sql"
    echo ""
    exit 1
fi

