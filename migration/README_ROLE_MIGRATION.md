# Users Table Role Migration Guide

## Overview
This migration converts the `users` table `role` column from an ENUM type to a foreign key reference (`role_id`) to the `roles` table.

## Why This Change?
- **Normalization**: Follows database normalization best practices
- **Flexibility**: Allows dynamic role management without schema changes
- **Data Integrity**: Foreign key constraints ensure referential integrity
- **Scalability**: Easy to add new roles without altering table structure

## Migration Files

### 1. `alter_users_role_to_id.sql`
Standalone migration script that can be run independently.

**What it does:**
1. Ensures `roles` table exists with default roles
2. Adds `role_id` column to `users` table
3. Migrates existing role enum values to `role_id` by matching `role_code`
4. Makes `role_id` NOT NULL
5. Adds foreign key constraint to `roles` table
6. Adds index on `role_id` for performance
7. Drops the old `role` enum column

### 2. `complete_migration.sql`
Updated to include the role migration as PART 7.

## Running the Migration

### Option 1: Run Standalone Migration
```sql
SOURCE migration/alter_users_role_to_id.sql;
```

### Option 2: Run Complete Migration (includes role migration)
```sql
SOURCE migration/complete_migration.sql;
```

## Important Notes

### Before Migration
1. **Backup your database** - Always backup before running migrations
2. Ensure all existing users have valid role values that match role codes in the `roles` table
3. The migration will set any unmapped roles to the default 'admin' role

### After Migration
1. **Update Application Code** - All code referencing `users.role` must be updated to:
   - Use `users.role_id` for foreign key operations
   - JOIN with `roles` table when `role_code` is needed
   - Example: `SELECT u.*, r.role_code FROM users u JOIN roles r ON u.role_id = r.id`

2. **Update Session Variables** - Session code should store `role_code` from JOIN query, not directly from users table

3. **Update API Endpoints** - All API endpoints that query users by role need to be updated

## Code Changes Required

### Database Queries
**Before:**
```sql
SELECT * FROM users WHERE role = 'admin'
```

**After:**
```sql
SELECT u.*, r.role_code, r.role_name 
FROM users u 
JOIN roles r ON u.role_id = r.id 
WHERE r.role_code = 'admin'
```

### PHP Code
**Before:**
```php
$user['role'] // Direct access to enum value
```

**After:**
```php
// When fetching user, JOIN with roles table
$query = "SELECT u.*, r.role_code, r.role_name 
          FROM users u 
          JOIN roles r ON u.role_id = r.id 
          WHERE u.id = ?";
// Then use: $user['role_code'] or $user['role_name']
```

## Rollback (if needed)
If you need to rollback, you would need to:
1. Add back the `role` enum column
2. Migrate data from `role_id` back to `role` enum
3. Drop `role_id` column and foreign key

**Note:** Rollback script not provided. Always test migrations in development first.

## Testing Checklist
- [ ] All existing users have valid role_id after migration
- [ ] Login functionality works with new structure
- [ ] User management pages display roles correctly
- [ ] Role-based permissions still work
- [ ] All API endpoints updated to use role_id
- [ ] Session variables updated correctly

