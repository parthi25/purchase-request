# Foreign Key Migration Fixes

This document explains the fixes applied to handle foreign key constraint issues when migrating databases with existing data.

## Problem

When adding foreign key constraints to a database with existing data, you may encounter errors due to:

1. **Orphaned Records**: Child table records referencing non-existent parent records
2. **Missing Master Data**: Referenced tables (users, categories, purchase_types, pr_statuses, suppliers) may not have required data
3. **NULL Values**: Foreign keys with RESTRICT constraint require valid values
4. **Missing Indexes**: Foreign keys require indexes on referenced columns

## Solution

We've created comprehensive fixes that:

1. **Clean up orphaned data** before adding foreign key constraints
2. **Ensure all referenced tables have necessary data**
3. **Handle NULL values appropriately** (set to NULL or default values)
4. **Add indexes** before foreign key constraints

## Files Updated

### 1. `migration/fix_foreign_key_data_issues.sql`
A standalone script that cleans up all orphaned data before adding foreign keys. Run this BEFORE adding foreign key constraints.

**What it does:**
- Ensures `pr_statuses` has all 9 required statuses
- Ensures `categories` has at least one category
- Ensures `purchase_types` has at least one type
- Fixes orphaned `created_by`, `b_head`, `buyer`, `po_team` references in `purchase_requests`
- Fixes orphaned `supplier_id`, `new_supplier`, `category_id`, `purch_id`, `po_status` references
- Fixes orphaned data in `pr_assignments`, `po_documents`, `pr_attachments`
- Adds necessary indexes before foreign keys

### 2. `migration/complete_migration.sql`
Updated to include data cleanup steps (Part 2.5) before adding foreign keys (Part 3).

**New sections:**
- **Part 2.5**: Fix orphaned data before foreign keys
- **Part 2.6**: Ensure indexes exist before foreign keys
- **Part 3**: Add foreign key relationships (now safe after cleanup)

### 3. `migration/alter_users_role_to_id.sql`
Updated to better handle existing data when migrating from role enum to role_id foreign key.

**Improvements:**
- Checks if role column exists before trying to migrate
- Handles cases where role column doesn't exist but role_id is NULL
- Sets default admin role for any unmatched roles

### 4. `database/migrations/add_foreign_keys_safe.sql`
Enhanced with better error handling and validation.

**Improvements:**
- All foreign key additions now check if constraints already exist
- All index additions check if indexes already exist
- Better transaction handling
- Added note to run data cleanup script first

## Usage

### Option 1: Using complete_migration.sql (Recommended)

The `complete_migration.sql` now includes all fixes. Simply run:

```bash
mysql -h 127.0.0.1 -P 3307 -u root -p your_database < migration/complete_migration.sql
```

This will:
1. Rename tables
2. Create new tables
3. **Clean up orphaned data** (NEW)
4. **Add indexes** (NEW)
5. Add foreign key constraints
6. Insert master data
7. Migrate users.role to role_id

### Option 2: Using Standalone Scripts

If you prefer to run scripts separately:

**Step 1: Clean up data**
```bash
mysql -h 127.0.0.1 -P 3307 -u root -p your_database < migration/fix_foreign_key_data_issues.sql
```

**Step 2: Add foreign keys**
```bash
mysql -h 127.0.0.1 -P 3307 -u root -p your_database < database/migrations/add_foreign_keys_safe.sql
```

**Step 3: Migrate role enum to role_id**
```bash
mysql -h 127.0.0.1 -P 3307 -u root -p your_database < migration/alter_users_role_to_id.sql
```

## What Gets Fixed

### purchase_requests Table
- `created_by`: Set to first available user if orphaned
- `b_head`: Set to first available user if orphaned
- `buyer`: Set to NULL if orphaned (can be NULL)
- `po_team`: Set to NULL if orphaned (can be NULL)
- `supplier_id`: Set to first available supplier if orphaned
- `new_supplier`: Set to NULL if orphaned (can be NULL)
- `category_id`: Set to first available category if orphaned
- `purch_id`: Set to first available purchase type if orphaned
- `po_status`: Set to status 1 (Open) if orphaned

### pr_assignments Table
- `ord_id`: Delete records with orphaned references
- `po_team_member`: Set to NULL if orphaned (can be NULL)

### po_documents Table
- `ord_id`: Delete records with orphaned references

### pr_attachments Table
- `ord_id`: Delete records with orphaned references

### supplier_requests Table
- `created_by`: Set to first available user if orphaned

## Important Notes

1. **Backup First**: Always backup your database before running migrations
2. **Data Loss**: Some orphaned records may be deleted (pr_assignments, po_documents, pr_attachments with invalid ord_id)
3. **Default Values**: Orphaned references are set to default values (first available record) or NULL
4. **Master Data**: Default categories and purchase types are created if none exist
5. **Statuses**: All 9 PR statuses are ensured to exist

## Verification

After running the migration, verify foreign keys were added:

```sql
-- Check foreign keys on purchase_requests
SELECT 
    CONSTRAINT_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'purchase_requests'
AND CONSTRAINT_NAME LIKE 'fk_%'
ORDER BY CONSTRAINT_NAME;
```

Check for orphaned data (should return 0 rows):

```sql
-- Check for orphaned created_by
SELECT COUNT(*) as orphaned_created_by
FROM purchase_requests pr
LEFT JOIN users u ON pr.created_by = u.id
WHERE pr.created_by IS NOT NULL AND u.id IS NULL;

-- Check for orphaned po_status
SELECT COUNT(*) as orphaned_status
FROM purchase_requests pr
LEFT JOIN pr_statuses ps ON pr.po_status = ps.id
WHERE pr.po_status IS NOT NULL AND ps.id IS NULL;
```

## Troubleshooting

### Error: "Cannot add foreign key constraint"
- **Solution**: Run `fix_foreign_key_data_issues.sql` first to clean up orphaned data

### Error: "Duplicate key name"
- **Solution**: The constraint or index already exists. This is safe to ignore.

### Error: "Table doesn't exist"
- **Solution**: Run table creation migrations first (complete_migration.sql Part 1 and 2)

### Foreign key constraint fails after migration
- **Solution**: Check for orphaned data using the verification queries above
- Manually fix any remaining orphaned records
- Re-run the foreign key migration script

## Support

If you encounter issues:
1. Check the error message carefully
2. Verify all referenced tables exist
3. Check for orphaned data using verification queries
4. Ensure indexes exist on foreign key columns
5. Review the migration logs





