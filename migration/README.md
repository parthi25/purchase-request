# Database Migration Guide

This folder contains the complete database migration script that handles table renaming, relationship creation, new tables, and master data insertion.

## Files

- **`complete_migration.sql`** - Complete migration script with all operations
- **`run-migration.bat`** - Windows batch file to run the migration
- **`README.md`** - This file

## What This Migration Does

The migration script performs the following operations:

### 1. Table Renaming
Renames old table names to new naming conventions:
- `po_tracking` → `purchase_requests`
- `cat` → `categories`
- `purchase_master` → `purchase_types`
- `status` → `pr_statuses`
- `new_supplier` → `supplier_requests`
- `po_team_member` → `pr_assignments`
- `po_` → `po_documents`
- `po_order` → `pr_attachments`
- `status_permissions` → `role_status_permissions`
- `status_flow` → `status_transitions`
- `pr_permissions` → `role_pr_permissions`

### 2. New Table Creation
Creates new tables if they don't exist:
- `pr_statuses` - PR status master data
- `categories` - Product categories
- `purchase_types` - Purchase type master data
- `role_status_permissions` - Role-based status permissions
- `status_transitions` - Status workflow transitions
- `role_pr_permissions` - Role-based PR permissions
- `status_modal_fields` - Status modal field configurations

### 3. Foreign Key Relationships
Adds foreign key constraints to maintain referential integrity:
- `purchase_requests` → `users`, `suppliers`, `categories`, `purchase_types`, `pr_statuses`
- `pr_assignments` → `purchase_requests`, `users`
- `po_documents` → `purchase_requests`
- `pr_attachments` → `purchase_requests`
- `role_status_permissions` → `pr_statuses`
- `status_transitions` → `pr_statuses`
- `role_pr_permissions` → `pr_statuses`
- `status_modal_fields` → `pr_statuses`

### 4. Performance Indexes
Adds indexes on frequently queried columns for better performance.

### 5. Master Data
Inserts all required master data:
- 9 PR statuses (Open, Forwarded to Buyer, Awaiting PO, etc.)
- Role status permissions for all roles
- Status transition workflows
- Role PR permissions
- Status modal field configurations

## How to Run

### Option 1: Using Batch File (Windows)

1. Double-click `run-migration.bat`
2. Enter MySQL password when prompted (if required)
3. Wait for migration to complete

### Option 2: Using MySQL Command Line

```bash
mysql -h 127.0.0.1 -P 3307 -u root -p jcrc < migration/complete_migration.sql
```

### Option 3: Using PHP Script

You can also use the PHP deployment script from the root directory:
```bash
php deploy-master-data.php
```

## Prerequisites

1. **Database Access**: You need MySQL/MariaDB access with appropriate permissions:
   - `ALTER` - for table renaming and structure changes
   - `CREATE` - for creating new tables
   - `INSERT` - for inserting master data
   - `SELECT` - for checking existing structures

2. **Database Configuration**: Database settings should be configured in `config/env.php` or `.env` file

3. **Existing Tables**: Some tables should already exist (like `users`, `suppliers`, `purchase_requests`, etc.)

## Safety Features

The migration script includes safety features:

- **IF EXISTS checks**: Renames only if tables exist
- **IF NOT EXISTS checks**: Creates tables only if they don't exist
- **Duplicate key handling**: Uses `ON DUPLICATE KEY UPDATE` for master data
- **Foreign key checks**: Temporarily disabled during migration
- **Transaction support**: Wrapped in a transaction for rollback capability

## After Migration

Once migration is complete, you can:

1. **Create Users**: Create users with appropriate roles:
   - `admin` - Full system access
   - `buyer` - Can create PRs and update statuses 3, 4, 5
   - `B_Head` - Buyer Head, can update statuses 2, 6, 8
   - `PO_Team` - PO Team Head, can update status 9
   - `PO_Team_Member` - PO Team Member, can update status 7

2. **Map Categories**: Use the `catbasbh` table to map categories to buyer heads
   ```sql
   INSERT INTO catbasbh (user_id, cat) VALUES (buyer_head_id, 'category_name');
   ```

3. **Map Buyers**: Use the `buyers_info` table to map buyers to buyer heads
   ```sql
   INSERT INTO buyers_info (b_head, buyer) VALUES (buyer_head_id, buyer_id);
   ```

4. **Add Purchase Types**: Add purchase types as needed
   ```sql
   INSERT INTO purchase_types (name) VALUES ('Purchase Type Name');
   ```

5. **Add Categories**: Add categories as needed
   ```sql
   INSERT INTO categories (maincat) VALUES ('Category Name');
   ```

## Troubleshooting

### Error: Table doesn't exist
- **Solution**: Some tables need to exist before migration. Ensure core tables like `users`, `suppliers`, `purchase_requests` exist.

### Error: Access denied
- **Solution**: Check database credentials in `config/env.php` or `.env` file
- Ensure the database user has ALTER, CREATE, INSERT permissions

### Error: Foreign key constraint fails
- **Solution**: The script handles this by checking if constraints exist before adding them
- If issues persist, check that referenced tables and columns exist

### Error: Duplicate key
- **Note**: This is normal if master data already exists. The script uses `ON DUPLICATE KEY UPDATE` to handle this gracefully.

### Migration partially completed
- **Solution**: The script is idempotent - you can run it multiple times safely
- It will skip operations that are already completed

## Verification

After migration, verify the changes:

```sql
-- Check renamed tables exist
SHOW TABLES LIKE 'purchase_requests';
SHOW TABLES LIKE 'pr_statuses';
SHOW TABLES LIKE 'role_status_permissions';

-- Check statuses
SELECT * FROM pr_statuses ORDER BY id;

-- Check role permissions
SELECT * FROM role_status_permissions;

-- Check status transitions
SELECT * FROM status_transitions;

-- Check foreign keys
SELECT 
    TABLE_NAME,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
AND REFERENCED_TABLE_NAME IS NOT NULL;
```

## Support

If you encounter issues:
1. Check the error messages in the migration output
2. Verify database connection settings
3. Ensure all required tables exist
4. Check database user permissions
5. Review the SQL file for specific operations that failed

