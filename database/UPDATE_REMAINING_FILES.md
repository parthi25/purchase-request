# Remaining Files to Update

After running the migration, you need to update the following files manually or using find/replace:

## Search and Replace Patterns

Use these patterns in your IDE or text editor to update remaining files:

1. `po_tracking` → `purchase_requests`
2. `FROM cat` → `FROM categories` (be careful with context)
3. `JOIN cat` → `JOIN categories`
4. `purchase_master` → `purchase_types`
5. `FROM status` → `FROM pr_statuses` (be careful - not all "status" should be changed)
6. `JOIN status` → `JOIN pr_statuses`
7. `new_supplier` → `supplier_requests`
8. `po_team_member` → `pr_assignments`
9. `po_` → `po_documents` (be very careful - this is just `po_` with underscore)
10. `po_order` → `pr_attachments`
11. `status_permissions` → `role_status_permissions`
12. `status_flow` → `status_transitions`
13. `pr_permissions` → `role_pr_permissions`

## Files That May Need Updates

- All files in `fetch/` directory
- All files in `api/` directory  
- All files in `update/` directory
- Any other PHP files that query the database

## Important Notes

- Be careful with `status` → `pr_statuses` - only change table references, not column names
- The `po_` table name is tricky - it's just `po_` with an underscore, so search for `po_ ` (with space) or `po_` in SQL context
- Test thoroughly after making changes
- Backup your database before running the migration

## Migration Steps

1. **Backup your database**
2. **Run the migration**: `php database/run-migration.php rename_tables_and_add_relationships.sql`
3. **Update code files** using the patterns above
4. **Test the application** thoroughly
5. **Fix any remaining issues**

