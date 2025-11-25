# Master Data Deployment Guide

This guide explains how to deploy the master data to your database using the one-click deployment script.

## What is Master Data?

Master data includes:
- **PR Statuses**: All 9 status values (Open, Forwarded to Buyer, Awaiting PO, etc.)
- **Role Status Permissions**: Which roles can change to which statuses
- **Status Transitions**: The workflow flow between statuses
- **Role PR Permissions**: Which roles can create/edit PRs
- **Status Modal Fields**: Which fields appear in status change modals
- **Purchase Types Table**: Structure for purchase types (empty, ready for data)
- **Categories Table**: Structure for categories (empty, ready for data)

## Prerequisites

1. **Database Schema**: Ensure all database tables are created (run migrations first)
2. **Database Access**: You need MySQL/MariaDB access with appropriate permissions
3. **Configuration**: Database settings should be configured in `config/env.php` or `.env` file

## Quick Start

### Option 1: One-Click Batch File (Windows)

Simply double-click `deploy-master-data.bat` in the project root directory.

The script will:
- Automatically detect if PHP is available (preferred method)
- Fall back to MySQL command line if PHP is not available
- Read database configuration from your config files
- Deploy all master data

### Option 2: PHP Script (Cross-Platform)

Run from command line:
```bash
php deploy-master-data.php
```

### Option 3: Manual MySQL

If you prefer to run manually:
```bash
mysql -h 127.0.0.1 -P 3307 -u root -p jcrc < database/master_data.sql
```

## After Deployment

Once master data is deployed, you can:

1. **Create Users**: Create users with appropriate roles:
   - `admin` - Full system access
   - `buyer` - Can create PRs and update statuses 3, 4, 5
   - `B_Head` - Buyer Head, can update statuses 2, 6, 8
   - `PO_Team` - PO Team Head, can update status 9
   - `PO_Team_Member` - PO Team Member, can update status 7

2. **Map Categories**: Use the `buyer_head_categories` table to map categories to buyer heads
   ```sql
   INSERT INTO buyer_head_categories (user_id, cat_id) VALUES (buyer_head_id, category_id);
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

## Status Flow Overview

The system uses the following status flow:

1. **Open** (Status 1) - Initial status when PR is created
   - Can be changed to: Status 2 (by B_Head) or Status 6 (by B_Head if proforma exists)

2. **Forwarded to Buyer** (Status 2) - Assigned to a buyer
   - Can be changed to: Status 3 (by buyer)

3. **Awaiting PO** (Status 3) - Buyer contacted supplier
   - Can be changed to: Status 4 (by buyer)

4. **Received Proforma PO** (Status 4) - Proforma received
   - Can be changed to: Status 5 (by buyer)

5. **Forwarded to Buyer Head** (Status 5) - Sent for approval
   - Can be changed to: Status 6 (by B_Head) or Status 8 (by B_Head - rejected)

6. **Forwarded to PO Team** (Status 6) - Sent to PO team
   - Can be changed to: Status 9 (by PO_Team)

7. **PO Generated** (Status 7) - Final status, PO created
   - Terminal status

8. **Rejected** (Status 8) - PR rejected
   - Terminal status

9. **Forwarded to PO Members** (Status 9) - Assigned to PO team member
   - Can be changed to: Status 7 (by PO_Team_Member)

## Troubleshooting

### Error: Table doesn't exist
- **Solution**: Run database migrations first to create all required tables

### Error: Access denied
- **Solution**: Check database credentials in `config/env.php` or `.env` file
- Ensure the database user has INSERT, UPDATE, CREATE permissions

### Error: Foreign key constraint fails
- **Solution**: Ensure `pr_statuses` table exists and has data before running
- The script should handle this, but if issues persist, check table creation order

### Duplicate key errors
- **Note**: This is normal if master data already exists. The script uses `ON DUPLICATE KEY UPDATE` to handle this gracefully.

## Verification

After deployment, verify the data:

```sql
-- Check statuses
SELECT * FROM pr_statuses ORDER BY id;

-- Check role permissions
SELECT * FROM role_status_permissions;

-- Check status transitions
SELECT * FROM status_transitions;

-- Check PR permissions
SELECT * FROM role_pr_permissions;
```

## Support

If you encounter issues:
1. Check the error messages in the deployment output
2. Verify database connection settings
3. Ensure all required tables exist
4. Check database user permissions

