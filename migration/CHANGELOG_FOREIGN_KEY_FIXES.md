# Changelog: Foreign Key Migration Fixes

## Summary

Fixed all foreign key constraint issues in database migrations to handle existing data properly. The fixes ensure that migrations can run successfully on databases with existing data without foreign key constraint failures.

## Changes Made

### 1. Created `migration/fix_foreign_key_data_issues.sql`
**Purpose**: Standalone script to clean up orphaned data before adding foreign key constraints.

**Key Features**:
- Ensures all referenced tables have required master data
- Fixes orphaned foreign key references in all tables
- Adds necessary indexes before foreign keys
- Safe to run multiple times (idempotent)

### 2. Updated `migration/complete_migration.sql`
**Changes**:
- Added **Part 2.5**: Data cleanup section before foreign keys
  - Ensures pr_statuses, categories, purchase_types have required data
  - Fixes orphaned references in purchase_requests
  - Fixes orphaned references in pr_assignments, po_documents, pr_attachments
  - Fixes orphaned references in supplier_requests
- Added **Part 2.6**: Index creation section before foreign keys
  - Adds indexes to foreign key columns in purchase_requests
- **Part 3**: Foreign key relationships (now safe after cleanup)

### 3. Updated `migration/alter_users_role_to_id.sql`
**Changes**:
- Added check for role column existence before migration
- Better handling of NULL role_id values
- Sets default admin role for unmatched roles
- More robust error handling

### 4. Updated `database/migrations/add_foreign_keys_safe.sql`
**Changes**:
- Added transaction handling (START TRANSACTION, COMMIT)
- All foreign key additions now check if constraints already exist
- All index additions now check if indexes already exist
- Better error handling with prepared statements
- Added note to run data cleanup script first

### 5. Created Documentation
- `migration/README_FOREIGN_KEY_FIXES.md`: Comprehensive guide on fixes
- `migration/CHANGELOG_FOREIGN_KEY_FIXES.md`: This file

## Tables Fixed

### purchase_requests
- `created_by` → users.id (set to first user if orphaned)
- `b_head` → users.id (set to first user if orphaned)
- `buyer` → users.id (set to NULL if orphaned)
- `po_team` → users.id (set to NULL if orphaned)
- `supplier_id` → suppliers.id (set to first supplier if orphaned)
- `new_supplier` → supplier_requests.id (set to NULL if orphaned)
- `category_id` → categories.id (set to first category if orphaned)
- `purch_id` → purchase_types.id (set to first type if orphaned)
- `po_status` → pr_statuses.id (set to status 1 if orphaned)

### pr_assignments
- `ord_id` → purchase_requests.id (delete if orphaned)
- `po_team_member` → users.id (set to NULL if orphaned)

### po_documents
- `ord_id` → purchase_requests.id (delete if orphaned)

### pr_attachments
- `ord_id` → purchase_requests.id (delete if orphaned)

### supplier_requests
- `created_by` → users.id (set to first user if orphaned)

## Master Data Ensured

1. **pr_statuses**: All 9 statuses (1-9) are ensured to exist
2. **categories**: At least one default category is created
3. **purchase_types**: At least one default purchase type is created
4. **suppliers**: Table is created if it doesn't exist
5. **supplier_requests**: Table is created if it doesn't exist

## Indexes Added

Before foreign keys are added, indexes are created on:
- `purchase_requests.created_by`
- `purchase_requests.b_head`
- `purchase_requests.supplier_id`
- `purchase_requests.category_id`
- `purchase_requests.purch_id`
- `purchase_requests.po_status`
- `purchase_requests.created_at` (for performance)

## Migration Order

1. **Table Renaming** (Part 1)
2. **Table Creation** (Part 2)
3. **Data Cleanup** (Part 2.5) ← NEW
4. **Index Creation** (Part 2.6) ← NEW
5. **Foreign Key Addition** (Part 3)
6. **Master Data Insertion** (Part 5)
7. **Role Migration** (Part 7)

## Testing Recommendations

1. **Test on empty database**: Verify all tables and constraints are created
2. **Test on database with data**: Verify orphaned data is cleaned up
3. **Test on partially migrated database**: Verify idempotency
4. **Verify foreign keys**: Check that all foreign keys are created successfully
5. **Verify data integrity**: Check that no orphaned records remain

## Breaking Changes

None. All changes are backward compatible and safe to run on existing databases.

## Migration Safety

- ✅ Idempotent: Can be run multiple times safely
- ✅ Non-destructive: Only fixes orphaned data, doesn't delete valid data
- ✅ Transactional: Uses transactions for data integrity
- ✅ Error handling: Checks for table/column existence before operations
- ✅ Data preservation: Sets default values instead of deleting data where possible

## Notes

- Some orphaned records in `pr_assignments`, `po_documents`, and `pr_attachments` are deleted if they reference non-existent purchase_requests
- Default values are used for required foreign keys (created_by, b_head, supplier_id, category_id, purch_id, po_status)
- NULL values are set for optional foreign keys (buyer, po_team, new_supplier, po_team_member)
- All operations are wrapped in transactions for safety





