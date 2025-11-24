# Batch File Migration Guide

## Overview
The `check-and-migrate-role.bat` file is a smart migration script that:
1. **Checks** the current database state
2. **Only migrates** if needed (idempotent)
3. **Inserts master data** automatically

## Features

### ✅ Smart State Checking
- Checks if `role_id` column exists
- Checks if `role` enum column still exists
- Checks if foreign key constraint exists
- Only runs migration if changes are needed

### ✅ Master Data Management
- Ensures all default roles exist
- Inserts/updates menu items for super_admin
- Inserts/updates initial page settings
- Uses `ON DUPLICATE KEY UPDATE` for safety

### ✅ Safe to Run Multiple Times
- Idempotent operations
- Won't duplicate data
- Won't break if already migrated

## Usage

### Run the Batch File
```batch
cd migration
check-and-migrate-role.bat
```

### What It Does

1. **Reads Database Config**
   - Tries to read from `.env` file via PHP
   - Falls back to defaults if PHP not available
   - Prompts for database name and password

2. **Checks Current State**
   - Queries INFORMATION_SCHEMA to check table structure
   - Determines if migration is needed

3. **Runs Migration (if needed)**
   - Executes `alter_users_role_to_id.sql`
   - Shows progress and results

4. **Inserts Master Data**
   - Runs `insert_master_data.sql`
   - Ensures all default data is present

## Output Example

```
============================================
Users Role Migration Checker
============================================

[INFO] Current State:
  role_id column exists: 0
  role enum column exists: 1
  Foreign key exists: 0

[ACTION NEEDED] role_id column does not exist
[ACTION NEEDED] role enum column still exists
[ACTION NEEDED] Foreign key constraint does not exist

============================================
Migration Required
============================================
Do you want to proceed with the migration? (Y/N): Y

[INFO] Running migration...
Migration Completed Successfully!

============================================
Checking and Inserting Master Data
============================================
[SUCCESS] Master data checked and inserted successfully.
```

## Files Used

1. **`check-and-migrate-role.bat`** - Main batch file
2. **`alter_users_role_to_id.sql`** - Migration SQL script
3. **`insert_master_data.sql`** - Master data insertion script

## Requirements

- MySQL command line client in PATH
- Database credentials (from .env or manual input)
- Appropriate database permissions (ALTER, CREATE, INSERT, etc.)

## Troubleshooting

### MySQL Not Found
```
[ERROR] MySQL command line client not found in PATH.
```
**Solution:** Add MySQL bin directory to your system PATH

### Connection Failed
```
[ERROR] Failed to connect to database
```
**Solution:** Check database credentials and ensure MySQL server is running

### Migration Already Completed
```
Migration Already Completed!
The users table has already been migrated.
```
**Solution:** This is normal! The script detected the migration was already done.

## Manual Execution

If the batch file doesn't work, you can run manually:

```sql
-- Check state
SELECT 
    CASE WHEN EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'your_db' AND TABLE_NAME = 'users' AND COLUMN_NAME = 'role_id') THEN 1 ELSE 0 END AS role_id_exists,
    CASE WHEN EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'your_db' AND TABLE_NAME = 'users' AND COLUMN_NAME = 'role' AND DATA_TYPE = 'enum') THEN 1 ELSE 0 END AS role_enum_exists;

-- Run migration
SOURCE migration/alter_users_role_to_id.sql;

-- Insert master data
SOURCE migration/insert_master_data.sql;
```

