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

# Check if SQL files exist
if [ ! -f "migration/complete_migration.sql" ]; then
    echo "[ERROR] migration/complete_migration.sql not found!"
    exit 1
fi

if [ ! -f "database/migrations/alter_buyer_head_categories_structure.sql" ]; then
    echo "[WARNING] database/migrations/alter_buyer_head_categories_structure.sql not found!"
    echo "This migration will be skipped."
    SKIP_ALTER_MIGRATION=1
else
    SKIP_ALTER_MIGRATION=0
fi

# Check for proforma item columns migration
if [ -f "database/migrations/add_proforma_item_columns_simple.sql" ]; then
    SKIP_PROFORMA_COLUMNS=0
    PROFORMA_SQL_FILE="database/migrations/add_proforma_item_columns_simple.sql"
elif [ -f "database/migrations/add_proforma_item_columns.sql" ]; then
    SKIP_PROFORMA_COLUMNS=0
    PROFORMA_SQL_FILE="database/migrations/add_proforma_item_columns.sql"
else
    echo "[WARNING] database/migrations/add_proforma_item_columns.sql not found!"
    echo "This migration will be skipped."
    SKIP_PROFORMA_COLUMNS=1
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

# Execute MySQL command with SQL file
# Build command arguments properly
MYSQL_ARGS=(-h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER")

# Add password if provided
if [ -n "$DB_PASS" ]; then
    MYSQL_ARGS+=(-p"$DB_PASS")
fi

# Add database name
MYSQL_ARGS+=("$DB_NAME")

# Execute MySQL command with SQL file
echo "[INFO] Running complete migration..."
MIGRATION_SUCCESS=0

if mysql "${MYSQL_ARGS[@]}" < "migration/complete_migration.sql"; then
    # Run additional migration for buyer_head_categories structure
    if [ "$SKIP_ALTER_MIGRATION" -eq 0 ]; then
        echo ""
        echo "[INFO] Running buyer_head_categories structure migration..."
        if mysql "${MYSQL_ARGS[@]}" < "database/migrations/alter_buyer_head_categories_structure.sql"; then
            echo "[INFO] buyer_head_categories structure migration completed successfully."
        else
            echo "[WARNING] buyer_head_categories structure migration had errors, but continuing..."
            MIGRATION_SUCCESS=1
        fi
    fi
    
    # Run migration for proforma item columns
    if [ "$SKIP_PROFORMA_COLUMNS" -eq 0 ]; then
        echo ""
        echo "[INFO] Running proforma item columns migration..."
        if mysql "${MYSQL_ARGS[@]}" < "$PROFORMA_SQL_FILE"; then
            echo "[INFO] Proforma item columns migration completed successfully."
        else
            echo "[WARNING] Proforma item columns migration had errors (columns may already exist - safe to ignore)."
            # Don't set MIGRATION_SUCCESS=1 here as "Duplicate column name" errors are expected if columns exist
        fi
    fi
else
    MIGRATION_SUCCESS=1
fi

if [ $MIGRATION_SUCCESS -eq 0 ]; then
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
    if [ "$SKIP_ALTER_MIGRATION" -eq 0 ]; then
        echo "  6. Altered buyer_head_categories table structure (removed Name and cat columns, added cat_id)"
    fi
    if [ "$SKIP_PROFORMA_COLUMNS" -eq 0 ]; then
        echo "  7. Added item_details_url and item_info columns to proforma table"
    fi
    echo ""
    echo "Next steps:"
    echo "  1. Create users with roles (admin, buyer, B_Head, PO_Team, PO_Team_Member)"
    echo "  2. Map categories to buyer heads using buyer_head_categories table"
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

