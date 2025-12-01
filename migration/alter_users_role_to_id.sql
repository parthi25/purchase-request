-- ============================================
-- Migration: Convert users.role from ENUM to role_id (Foreign Key)
-- ============================================
-- This script:
-- 1. Adds role_id column to users table
-- 2. Migrates existing role enum values to role_id
-- 3. Removes the old role enum column
-- 4. Adds foreign key constraint
-- ============================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- ============================================
-- Step 1: Ensure roles table exists and has data
-- ============================================
-- Create roles table if it doesn't exist
CREATE TABLE IF NOT EXISTS `roles` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `role_code` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Role code (e.g., admin, buyer, B_Head)',
  `role_name` VARCHAR(100) NOT NULL COMMENT 'Display name for the role (e.g., Admin, Buyer, Buyer Head)',
  `description` TEXT DEFAULT NULL COMMENT 'Description of the role',
  `is_active` TINYINT(1) DEFAULT 1 COMMENT 'Whether this role is active',
  `display_order` INT(11) DEFAULT 0 COMMENT 'Order for displaying roles in dropdowns',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_role_code` (`role_code`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_display_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default roles if they don't exist
INSERT INTO `roles` (`role_code`, `role_name`, `description`, `is_active`, `display_order`) VALUES
('admin', 'Admin', 'Administrator role with full access', 1, 1),
('buyer', 'Buyer', 'Buyer role for creating and managing purchase requests', 1, 2),
('B_Head', 'Buyer Head', 'Buyer Head role for approving and managing buyer requests', 1, 3),
('PO_Head', 'PO Head', 'Purchase Order Head role for managing PO operations', 1, 4),
('PO_Team_Member', 'PO Team Member', 'Purchase Order Team Member role', 1, 5),
('PO_Team', 'PO Team', 'Purchase Order Team role', 1, 6),
('super_admin', 'Super Admin', 'Super Administrator with system-wide access', 1, 7),
('master', 'Master', 'Master role with complete system control', 1, 8)
ON DUPLICATE KEY UPDATE `role_name` = VALUES(`role_name`), `description` = VALUES(`description`), `is_active` = VALUES(`is_active`), `display_order` = VALUES(`display_order`), `updated_at` = NOW();

-- ============================================
-- Insert Master Data: Role Menu Settings
-- ============================================
-- Add Role Management and Role Initial Settings menu items for super_admin
INSERT INTO `role_menu_settings` (`role`, `menu_item_label`, `menu_item_url`, `menu_item_icon`, `menu_order`, `is_visible`, `menu_group`) VALUES
('super_admin', 'Role Management', 'role-management.php', '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>', 17, 1, 'master_management'),
('super_admin', 'Role Initial Settings', 'role-initial-settings.php', '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>', 18, 1, 'master_management')
ON DUPLICATE KEY UPDATE `menu_item_label` = VALUES(`menu_item_label`), `menu_item_icon` = VALUES(`menu_item_icon`), `menu_order` = VALUES(`menu_order`), `is_visible` = VALUES(`is_visible`), `menu_group` = VALUES(`menu_group`), `updated_at` = NOW();

-- ============================================
-- Insert Master Data: Role Initial Settings
-- ============================================
-- Ensure role_initial_settings has default settings
INSERT INTO `role_initial_settings` (`role`, `initial_page_url`, `initial_status_filter`) VALUES
('admin', 'admin.php', 1),
('buyer', 'buyer.php', 2),
('B_Head', 'buyer-head.php', 1),
('PO_Head', 'po-head.php', 1),
('PO_Team_Member', 'po-member.php', 9),
('super_admin', 'admin.php', 1),
('master', 'admin.php', 1)
ON DUPLICATE KEY UPDATE `initial_page_url` = VALUES(`initial_page_url`), `initial_status_filter` = VALUES(`initial_status_filter`), `updated_at` = NOW();

-- ============================================
-- Step 2: Add role_id column to users table
-- ============================================
-- Check if role_id column already exists
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'users' 
    AND COLUMN_NAME = 'role_id'
);

SET @add_col_sql = IF(
    @col_exists = 0,
    'ALTER TABLE `users` ADD COLUMN `role_id` INT(11) NULL AFTER `role`',
    'SELECT 1'
);
PREPARE stmt FROM @add_col_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================
-- Step 3: Migrate existing role enum values to role_id
-- ============================================
-- First, check if role column exists
SET @role_col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'users' 
    AND COLUMN_NAME = 'role'
);

-- Update role_id based on role enum value matching role_code
-- Use BINARY comparison to avoid collation mismatch
-- Only run if role column exists
SET @migrate_role_sql = IF(
    @role_col_exists > 0,
    'UPDATE `users` u
     INNER JOIN `roles` r ON BINARY u.role = BINARY r.role_code
     SET u.role_id = r.id
     WHERE u.role_id IS NULL',
    'SELECT 1'
);
PREPARE stmt FROM @migrate_role_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Handle any roles that don't match (set to default admin role)
-- Also handle case where role column doesn't exist but role_id is NULL
SET @set_default_role_sql = 'UPDATE `users` u
     SET u.role_id = (SELECT id FROM roles WHERE BINARY role_code = BINARY ''admin'' LIMIT 1)
     WHERE u.role_id IS NULL';
PREPARE stmt FROM @set_default_role_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================
-- Step 4: Make role_id NOT NULL after migration
-- ============================================
SET @make_not_null_sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'users' 
     AND COLUMN_NAME = 'role_id' 
     AND IS_NULLABLE = 'YES') > 0,
    'ALTER TABLE `users` MODIFY COLUMN `role_id` INT(11) NOT NULL',
    'SELECT 1'
);
PREPARE stmt FROM @make_not_null_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================
-- Step 5: Add foreign key constraint
-- ============================================
SET @fk_sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'users' 
     AND CONSTRAINT_NAME = 'fk_users_role') = 0,
    'ALTER TABLE `users` ADD CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE',
    'SELECT 1'
);
PREPARE stmt FROM @fk_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================
-- Step 6: Add index on role_id for performance
-- ============================================
SET @idx_sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'users' 
     AND INDEX_NAME = 'idx_role_id') = 0,
    'ALTER TABLE `users` ADD INDEX `idx_role_id` (`role_id`)',
    'SELECT 1'
);
PREPARE stmt FROM @idx_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================
-- Step 7: Drop old role enum column
-- ============================================
-- Check if role column exists and is enum
SET @role_col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'users' 
    AND COLUMN_NAME = 'role'
    AND DATA_TYPE = 'enum'
);

SET @drop_role_sql = IF(
    @role_col_exists > 0,
    'ALTER TABLE `users` DROP COLUMN `role`',
    'SELECT 1'
);
PREPARE stmt FROM @drop_role_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================
-- Step 8: Insert Master Data
-- ============================================
-- Ensure role_menu_settings has menu items for super_admin
INSERT INTO `role_menu_settings` (`role`, `menu_item_label`, `menu_item_url`, `menu_item_icon`, `menu_order`, `is_visible`, `menu_group`) VALUES
('super_admin', 'Role Management', 'role-management.php', '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>', 17, 1, 'master_management'),
('super_admin', 'Role Initial Settings', 'role-initial-settings.php', '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>', 18, 1, 'master_management')
ON DUPLICATE KEY UPDATE `menu_item_label` = VALUES(`menu_item_label`), `menu_item_icon` = VALUES(`menu_item_icon`), `menu_order` = VALUES(`menu_order`), `is_visible` = VALUES(`is_visible`), `menu_group` = VALUES(`menu_group`), `updated_at` = NOW();

-- Ensure role_initial_settings has default settings
INSERT INTO `role_initial_settings` (`role`, `initial_page_url`, `initial_status_filter`) VALUES
('admin', 'admin.php', 1),
('buyer', 'buyer.php', 2),
('B_Head', 'buyer-head.php', 1),
('PO_Head', 'po-head.php', 1),
('PO_Team_Member', 'po-member.php', 9),
('super_admin', 'admin.php', 1),
('master', 'admin.php', 1)
ON DUPLICATE KEY UPDATE `initial_page_url` = VALUES(`initial_page_url`), `initial_status_filter` = VALUES(`initial_status_filter`), `updated_at` = NOW();

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;

-- ============================================
-- MIGRATION COMPLETE!
-- ============================================
-- The users table has been migrated from role enum to role_id foreign key.
-- 
-- What was done:
-- 1. Ensured roles table exists with default roles
-- 2. Added role_id column to users table
-- 3. Migrated existing role enum values to role_id
-- 4. Made role_id NOT NULL
-- 5. Added foreign key constraint to roles table
-- 6. Added index on role_id for performance
-- 7. Dropped old role enum column
-- 8. Inserted master data (menu items and initial settings)
--
-- Next steps:
-- 1. Update application code to use role_id instead of role
-- 2. Update queries to JOIN with roles table when role_code is needed
-- 3. Test all user-related functionality
-- ============================================

