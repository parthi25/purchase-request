# Database Table Rename Mapping

This document lists all table renames and their mappings for code updates.

## Table Rename Mappings

| Old Table Name | New Table Name | Description |
|---------------|----------------|-------------|
| `po_tracking` | `purchase_requests` | Main purchase request tracking table |
| `cat` | `categories` | Product categories |
| `purchase_master` | `purchase_types` | Purchase type master data |
| `status` | `pr_statuses` | PR status master |
| `new_supplier` | `supplier_requests` | New/pending supplier requests |
| `po_team_member` | `pr_assignments` | PR assignments to team members |
| `po_` | `po_documents` | PO document files |
| `po_order` | `pr_attachments` | PR attachment files (product images) |
| `status_permissions` | `role_status_permissions` | Role-based status permissions |
| `status_flow` | `status_transitions` | Status transition rules |
| `pr_permissions` | `role_pr_permissions` | Role-based PR permissions |
| `catbasbh` | `buyer_head_categories` | Category to buyer head mapping |

## Column Name Changes

No column names were changed - only table names.

## Foreign Key Relationships Added

All tables now have proper foreign key relationships:
- `purchase_requests` → `users`, `suppliers`, `supplier_requests`, `categories`, `purchase_types`, `pr_statuses`
- `pr_assignments` → `purchase_requests`, `users`
- `po_documents` → `purchase_requests`
- `pr_attachments` → `purchase_requests`
- `supplier_requests` → `users`
- `role_status_permissions` → `pr_statuses`
- `status_transitions` → `pr_statuses` (from/to)
- `role_pr_permissions` → `pr_statuses`

## Search and Replace Patterns

Use these patterns to update code:

1. `po_tracking` → `purchase_requests`
2. `FROM cat` → `FROM categories`
3. `JOIN cat` → `JOIN categories`
4. `purchase_master` → `purchase_types`
5. `FROM status` → `FROM pr_statuses`
6. `JOIN status` → `JOIN pr_statuses`
7. `new_supplier` → `supplier_requests`
8. `po_team_member` → `pr_assignments`
9. `po_` → `po_documents` (be careful with this one - it's just `po_`)
10. `po_order` → `pr_attachments`
11. `status_permissions` → `role_status_permissions`
12. `status_flow` → `status_transitions`
13. `pr_permissions` → `role_pr_permissions`
14. `catbasbh` → `buyer_head_categories`

