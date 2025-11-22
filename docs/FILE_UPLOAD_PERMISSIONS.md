# File Upload Permissions Guide

This document outlines which user roles can upload/delete files for each file type and PR status.

## PR Status Reference

| Status ID | Status Name |
|-----------|-------------|
| 1 | Open |
| 2 | Forwarded to Buyer |
| 3 | Agent/Supplier contacted and Awaiting PO details |
| 4 | Received Proforma PO |
| 5 | Forwarded to Buyer Head |
| 6 | Forwarded to PO Team |
| 7 | PO generated |
| 8 | Rejected |
| 9 | Forwarded to PO Members |

## File Upload Permissions by File Type

### 1. PROFORMA Files

**Allowed Roles:** 
- `B_Head` (Buyer Head)
- `buyer` (Buyer)

**Allowed Statuses:**
- **B_Head:** Status 1 (Open), Status 5 (Forwarded to Buyer Head)
- **buyer:** Status 1 (Open), Status 2 (Forwarded to Buyer), Status 3 (Agent/Supplier contacted and Awaiting PO details)

**Summary:** Buyer Heads can upload/delete proforma files when the PR is in Open status or has been forwarded to Buyer Head. Buyers can upload/delete proforma files during the early stages (statuses 1, 2, 3).

---

### 2. PO Files (Purchase Order Documents)

**Allowed Roles:** 
- `PO_Team` (PO Team Head)
- `PO_Team_Member` (PO Team Member)

**Allowed Statuses:**
- Status 7 (PO generated)

**Summary:** Only PO Team and PO Team Members can upload/delete PO files when the PR status is "PO generated".

---

### 3. PRODUCT Files (Product Images/Attachments)

**Allowed Roles:**
- `B_Head` (Buyer Head)
- `buyer` (Buyer)
- `admin` (Administrator)

**Allowed Statuses:**
- Status 1 (Open)
- Status 2 (Forwarded to Buyer)
- Status 3 (Agent/Supplier contacted and Awaiting PO details)
- Status 4 (Received Proforma PO)
- Status 5 (Forwarded to Buyer Head)

**Summary:** Buyer Heads, Buyers, and Admins can upload/delete product files during the early stages of the PR process (statuses 1-5).

---

## Permission Matrix

| File Type | Role | Status 1 | Status 2 | Status 3 | Status 4 | Status 5 | Status 6 | Status 7 | Status 8 | Status 9 |
|-----------|------|----------|----------|----------|----------|----------|----------|----------|----------|----------|
| **Proforma** | B_Head | ✅ | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ |
| **Proforma** | buyer | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Proforma** | admin | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Proforma** | PO_Team | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Proforma** | PO_Team_Member | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **PO** | B_Head | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **PO** | buyer | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **PO** | admin | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **PO** | PO_Team | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ |
| **PO** | PO_Team_Member | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ |
| **Product** | B_Head | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| **Product** | buyer | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| **Product** | admin | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| **Product** | PO_Team | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Product** | PO_Team_Member | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |

**Legend:**
- ✅ = Can upload and delete
- ❌ = Cannot upload or delete

## Notes

1. **Viewing is always allowed** - All users can view files regardless of their role or the PR status.

2. **Upload and Delete permissions are linked** - If a role can upload, they can also delete files of that type.

3. **Permissions are stored in database** - The `file_upload_permissions` table stores these permissions and can be modified by administrators.

4. **Fallback mechanism** - If the database permissions cannot be loaded, the system falls back to the hardcoded permissions shown above.

## Database Table Structure

The permissions are stored in the `file_upload_permissions` table with the following structure:

- `role` - User role (admin, buyer, B_Head, PO_Team, PO_Team_Member)
- `file_type` - Type of file (proforma, po, product)
- `status_id` - PR status ID that allows upload
- `can_upload` - Boolean flag for upload permission
- `can_delete` - Boolean flag for delete permission
- `is_active` - Whether the permission is currently active

## Modifying Permissions

To modify permissions, update the `file_upload_permissions` table directly or use the migration script:

```sql
-- Example: Allow admin to upload PO files in status 7
INSERT INTO `file_upload_permissions` 
(`role`, `file_type`, `status_id`, `can_upload`, `can_delete`, `is_active`) 
VALUES 
('admin', 'po', 7, 1, 1, 1)
ON DUPLICATE KEY UPDATE 
`can_upload` = 1, 
`can_delete` = 1, 
`is_active` = 1, 
`updated_at` = NOW();
```

