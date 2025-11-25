-- ============================================
-- Complete Database Migration Script
-- ============================================
-- This script includes:
-- 1. Table renaming operations
-- 2. New table creation
-- 3. Foreign key relationships
-- 4. Indexes for performance
-- 5. Master data insertion
-- ============================================
-- Run this script on a fresh or existing database
-- It handles both new installations and upgrades
-- ============================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- ============================================
-- PART 1: RENAME EXISTING TABLES
-- ============================================
-- Rename tables to follow better naming conventions
-- Uses IF EXISTS to avoid errors if tables don't exist

-- Main tables
SET @rename_sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'po_tracking') > 0,
    'RENAME TABLE `po_tracking` TO `purchase_requests`',
    'SELECT 1'
);
PREPARE stmt FROM @rename_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @rename_sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cat') > 0,
    'RENAME TABLE `cat` TO `categories`',
    'SELECT 1'
);
PREPARE stmt FROM @rename_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @rename_sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'purchase_master') > 0,
    'RENAME TABLE `purchase_master` TO `purchase_types`',
    'SELECT 1'
);
PREPARE stmt FROM @rename_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @rename_sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'status') > 0,
    'RENAME TABLE `status` TO `pr_statuses`',
    'SELECT 1'
);
PREPARE stmt FROM @rename_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @rename_sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'new_supplier') > 0,
    'RENAME TABLE `new_supplier` TO `supplier_requests`',
    'SELECT 1'
);
PREPARE stmt FROM @rename_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @rename_sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'po_team_member') > 0,
    'RENAME TABLE `po_team_member` TO `pr_assignments`',
    'SELECT 1'
);
PREPARE stmt FROM @rename_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @rename_sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'po_') > 0,
    'RENAME TABLE `po_` TO `po_documents`',
    'SELECT 1'
);
PREPARE stmt FROM @rename_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @rename_sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'po_order') > 0,
    'RENAME TABLE `po_order` TO `pr_attachments`',
    'SELECT 1'
);
PREPARE stmt FROM @rename_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Permission tables
SET @rename_sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'status_permissions') > 0,
    'RENAME TABLE `status_permissions` TO `role_status_permissions`',
    'SELECT 1'
);
PREPARE stmt FROM @rename_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @rename_sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'status_flow') > 0,
    'RENAME TABLE `status_flow` TO `status_transitions`',
    'SELECT 1'
);
PREPARE stmt FROM @rename_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @rename_sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pr_permissions') > 0,
    'RENAME TABLE `pr_permissions` TO `role_pr_permissions`',
    'SELECT 1'
);
PREPARE stmt FROM @rename_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @rename_sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'catbasbh') > 0,
    'RENAME TABLE `catbasbh` TO `buyer_head_categories`',
    'SELECT 1'
);
PREPARE stmt FROM @rename_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================
-- PART 2: CREATE NEW TABLES
-- ============================================

-- Create pr_statuses table if it doesn't exist (in case status table wasn't renamed)
CREATE TABLE IF NOT EXISTS `pr_statuses` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `status` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create categories table if it doesn't exist
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `maincat` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_maincat` (`maincat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create purchase_types table if it doesn't exist
CREATE TABLE IF NOT EXISTS `purchase_types` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create role_status_permissions table
CREATE TABLE IF NOT EXISTS `role_status_permissions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `role` VARCHAR(50) NOT NULL COMMENT 'User role (admin, buyer, B_Head, PO_Head, PO_Team_Member)',
  `status_id` INT(11) NOT NULL COMMENT 'Status ID that this role can change to',
  `is_active` TINYINT(1) DEFAULT 1 COMMENT 'Whether this permission is active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_role_status` (`role`, `status_id`),
  KEY `idx_role` (`role`),
  KEY `idx_status` (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create status_transitions table
CREATE TABLE IF NOT EXISTS `status_transitions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `from_status_id` INT(11) NOT NULL COMMENT 'Current status ID',
  `to_status_id` INT(11) NOT NULL COMMENT 'Next status ID',
  `role` VARCHAR(50) NOT NULL COMMENT 'Role that can make this transition',
  `requires_proforma` TINYINT(1) DEFAULT 0 COMMENT 'Whether proforma is required for this transition',
  `is_active` TINYINT(1) DEFAULT 1 COMMENT 'Whether this flow is active',
  `priority` INT(11) DEFAULT 0 COMMENT 'Priority order for multiple flows',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_flow` (`from_status_id`, `to_status_id`, `role`),
  KEY `idx_from_status` (`from_status_id`),
  KEY `idx_to_status` (`to_status_id`),
  KEY `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create role_pr_permissions table
CREATE TABLE IF NOT EXISTS `role_pr_permissions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `role` VARCHAR(50) NOT NULL COMMENT 'User role (admin, buyer, B_Head, etc.)',
  `can_create` TINYINT(1) DEFAULT 0 COMMENT 'Whether this role can create PRs',
  `can_edit` TINYINT(1) DEFAULT 0 COMMENT 'Whether this role can edit PRs',
  `can_edit_status` INT(11) DEFAULT NULL COMMENT 'PR can only be edited when status equals this value (NULL = any status)',
  `is_active` TINYINT(1) DEFAULT 1 COMMENT 'Whether this permission is active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_role` (`role`),
  KEY `idx_role` (`role`),
  KEY `idx_can_create` (`can_create`),
  KEY `idx_can_edit` (`can_edit`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create status_modal_fields table
CREATE TABLE IF NOT EXISTS `status_modal_fields` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `status_id` INT(11) NOT NULL,
    `field_name` VARCHAR(50) NOT NULL COMMENT 'Field identifier: buyer, po_head, po_team, qty, file_upload, remark',
    `is_required` TINYINT(1) DEFAULT 0 COMMENT 'Whether the field is required',
    `field_order` INT(11) DEFAULT 0 COMMENT 'Order in which fields should appear',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_status_field` (`status_id`, `field_name`),
    KEY `idx_status_id` (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- PART 3: ADD FOREIGN KEY RELATIONSHIPS
-- ============================================
-- Add foreign keys with error handling (skip if already exists)

-- purchase_requests foreign keys
SET @fk_sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'purchase_requests' 
     AND CONSTRAINT_NAME = 'fk_pr_created_by') = 0
    AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
         WHERE TABLE_SCHEMA = DATABASE() 
         AND TABLE_NAME = 'purchase_requests' 
         AND COLUMN_NAME = 'created_by') > 0,
    'ALTER TABLE `purchase_requests` ADD CONSTRAINT `fk_pr_created_by` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE',
    'SELECT 1'
);
PREPARE stmt FROM @fk_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'purchase_requests' 
     AND CONSTRAINT_NAME = 'fk_pr_b_head') = 0
    AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
         WHERE TABLE_SCHEMA = DATABASE() 
         AND TABLE_NAME = 'purchase_requests' 
         AND COLUMN_NAME = 'b_head') > 0,
    'ALTER TABLE `purchase_requests` ADD CONSTRAINT `fk_pr_b_head` FOREIGN KEY (`b_head`) REFERENCES `users`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE',
    'SELECT 1'
);
PREPARE stmt FROM @fk_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'purchase_requests' 
     AND CONSTRAINT_NAME = 'fk_pr_supplier') = 0
    AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
         WHERE TABLE_SCHEMA = DATABASE() 
         AND TABLE_NAME = 'purchase_requests' 
         AND COLUMN_NAME = 'supplier_id') > 0,
    'ALTER TABLE `purchase_requests` ADD CONSTRAINT `fk_pr_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE',
    'SELECT 1'
);
PREPARE stmt FROM @fk_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'purchase_requests' 
     AND CONSTRAINT_NAME = 'fk_pr_new_supplier') = 0
    AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
         WHERE TABLE_SCHEMA = DATABASE() 
         AND TABLE_NAME = 'purchase_requests' 
         AND COLUMN_NAME = 'new_supplier') > 0,
    'ALTER TABLE `purchase_requests` ADD CONSTRAINT `fk_pr_new_supplier` FOREIGN KEY (`new_supplier`) REFERENCES `supplier_requests`(`id`) ON DELETE SET NULL ON UPDATE CASCADE',
    'SELECT 1'
);
PREPARE stmt FROM @fk_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'purchase_requests' 
     AND CONSTRAINT_NAME = 'fk_pr_category') = 0
    AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
         WHERE TABLE_SCHEMA = DATABASE() 
         AND TABLE_NAME = 'purchase_requests' 
         AND COLUMN_NAME = 'category_id') > 0,
    'ALTER TABLE `purchase_requests` ADD CONSTRAINT `fk_pr_category` FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE',
    'SELECT 1'
);
PREPARE stmt FROM @fk_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'purchase_requests' 
     AND CONSTRAINT_NAME = 'fk_pr_purchase_type') = 0
    AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
         WHERE TABLE_SCHEMA = DATABASE() 
         AND TABLE_NAME = 'purchase_requests' 
         AND COLUMN_NAME = 'purch_id') > 0,
    'ALTER TABLE `purchase_requests` ADD CONSTRAINT `fk_pr_purchase_type` FOREIGN KEY (`purch_id`) REFERENCES `purchase_types`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE',
    'SELECT 1'
);
PREPARE stmt FROM @fk_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'purchase_requests' 
     AND CONSTRAINT_NAME = 'fk_pr_status') = 0
    AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
         WHERE TABLE_SCHEMA = DATABASE() 
         AND TABLE_NAME = 'purchase_requests' 
         AND COLUMN_NAME = 'po_status') > 0,
    'ALTER TABLE `purchase_requests` ADD CONSTRAINT `fk_pr_status` FOREIGN KEY (`po_status`) REFERENCES `pr_statuses`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE',
    'SELECT 1'
);
PREPARE stmt FROM @fk_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- pr_assignments foreign keys
SET @fk_sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'pr_assignments' 
     AND CONSTRAINT_NAME = 'fk_assignment_pr') = 0
    AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
         WHERE TABLE_SCHEMA = DATABASE() 
         AND TABLE_NAME = 'pr_assignments' 
         AND COLUMN_NAME = 'ord_id') > 0,
    'ALTER TABLE `pr_assignments` ADD CONSTRAINT `fk_assignment_pr` FOREIGN KEY (`ord_id`) REFERENCES `purchase_requests`(`id`) ON DELETE CASCADE ON UPDATE CASCADE',
    'SELECT 1'
);
PREPARE stmt FROM @fk_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- po_documents foreign keys
SET @fk_sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'po_documents' 
     AND CONSTRAINT_NAME = 'fk_po_doc_pr') = 0
    AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
         WHERE TABLE_SCHEMA = DATABASE() 
         AND TABLE_NAME = 'po_documents' 
         AND COLUMN_NAME = 'ord_id') > 0,
    'ALTER TABLE `po_documents` ADD CONSTRAINT `fk_po_doc_pr` FOREIGN KEY (`ord_id`) REFERENCES `purchase_requests`(`id`) ON DELETE CASCADE ON UPDATE CASCADE',
    'SELECT 1'
);
PREPARE stmt FROM @fk_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- pr_attachments foreign keys
SET @fk_sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'pr_attachments' 
     AND CONSTRAINT_NAME = 'fk_attachment_pr') = 0
    AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
         WHERE TABLE_SCHEMA = DATABASE() 
         AND TABLE_NAME = 'pr_attachments' 
         AND COLUMN_NAME = 'ord_id') > 0,
    'ALTER TABLE `pr_attachments` ADD CONSTRAINT `fk_attachment_pr` FOREIGN KEY (`ord_id`) REFERENCES `purchase_requests`(`id`) ON DELETE CASCADE ON UPDATE CASCADE',
    'SELECT 1'
);
PREPARE stmt FROM @fk_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- role_status_permissions foreign keys
SET @fk_sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'role_status_permissions' 
     AND CONSTRAINT_NAME = 'fk_role_status_perm_status') = 0,
    'ALTER TABLE `role_status_permissions` ADD CONSTRAINT `fk_role_status_perm_status` FOREIGN KEY (`status_id`) REFERENCES `pr_statuses`(`id`) ON DELETE CASCADE ON UPDATE CASCADE',
    'SELECT 1'
);
PREPARE stmt FROM @fk_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- status_transitions foreign keys
SET @fk_sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'status_transitions' 
     AND CONSTRAINT_NAME = 'fk_transition_from_status') = 0,
    'ALTER TABLE `status_transitions` ADD CONSTRAINT `fk_transition_from_status` FOREIGN KEY (`from_status_id`) REFERENCES `pr_statuses`(`id`) ON DELETE CASCADE ON UPDATE CASCADE, ADD CONSTRAINT `fk_transition_to_status` FOREIGN KEY (`to_status_id`) REFERENCES `pr_statuses`(`id`) ON DELETE CASCADE ON UPDATE CASCADE',
    'SELECT 1'
);
PREPARE stmt FROM @fk_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- role_pr_permissions foreign keys
SET @fk_sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'role_pr_permissions' 
     AND CONSTRAINT_NAME = 'fk_pr_perm_status') = 0,
    'ALTER TABLE `role_pr_permissions` ADD CONSTRAINT `fk_pr_perm_status` FOREIGN KEY (`can_edit_status`) REFERENCES `pr_statuses`(`id`) ON DELETE SET NULL ON UPDATE CASCADE',
    'SELECT 1'
);
PREPARE stmt FROM @fk_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- status_modal_fields foreign keys
SET @fk_sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'status_modal_fields' 
     AND CONSTRAINT_NAME = 'fk_modal_field_status') = 0,
    'ALTER TABLE `status_modal_fields` ADD CONSTRAINT `fk_modal_field_status` FOREIGN KEY (`status_id`) REFERENCES `pr_statuses`(`id`) ON DELETE CASCADE ON UPDATE CASCADE',
    'SELECT 1'
);
PREPARE stmt FROM @fk_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================
-- PART 4: ADD INDEXES FOR PERFORMANCE
-- ============================================

-- purchase_requests indexes
SET @idx_sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'purchase_requests' 
     AND INDEX_NAME = 'idx_po_status') = 0,
    'ALTER TABLE `purchase_requests` ADD INDEX `idx_po_status` (`po_status`)',
    'SELECT 1'
);
PREPARE stmt FROM @idx_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'purchase_requests' 
     AND INDEX_NAME = 'idx_created_by') = 0,
    'ALTER TABLE `purchase_requests` ADD INDEX `idx_created_by` (`created_by`)',
    'SELECT 1'
);
PREPARE stmt FROM @idx_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'purchase_requests' 
     AND INDEX_NAME = 'idx_b_head') = 0,
    'ALTER TABLE `purchase_requests` ADD INDEX `idx_b_head` (`b_head`)',
    'SELECT 1'
);
PREPARE stmt FROM @idx_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'purchase_requests' 
     AND INDEX_NAME = 'idx_created_at') = 0,
    'ALTER TABLE `purchase_requests` ADD INDEX `idx_created_at` (`created_at`)',
    'SELECT 1'
);
PREPARE stmt FROM @idx_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- pr_assignments indexes
SET @idx_sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'pr_assignments' 
     AND INDEX_NAME = 'idx_ord_id') = 0,
    'ALTER TABLE `pr_assignments` ADD INDEX `idx_ord_id` (`ord_id`)',
    'SELECT 1'
);
PREPARE stmt FROM @idx_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'pr_assignments' 
     AND INDEX_NAME = 'idx_po_team_member') = 0,
    'ALTER TABLE `pr_assignments` ADD INDEX `idx_po_team_member` (`po_team_member`)',
    'SELECT 1'
);
PREPARE stmt FROM @idx_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- po_documents indexes
SET @idx_sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'po_documents' 
     AND INDEX_NAME = 'idx_ord_id') = 0,
    'ALTER TABLE `po_documents` ADD INDEX `idx_ord_id` (`ord_id`)',
    'SELECT 1'
);
PREPARE stmt FROM @idx_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- pr_attachments indexes
SET @idx_sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'pr_attachments' 
     AND INDEX_NAME = 'idx_ord_id') = 0,
    'ALTER TABLE `pr_attachments` ADD INDEX `idx_ord_id` (`ord_id`)',
    'SELECT 1'
);
PREPARE stmt FROM @idx_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================
-- PART 5: INSERT MASTER DATA
-- ============================================

-- Ensure pr_statuses table has created_at and updated_at columns
-- (in case table was renamed from old 'status' table without these columns)
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'pr_statuses' 
    AND COLUMN_NAME = 'created_at'
);

SET @add_cols_sql = IF(
    @col_exists = 0,
    'ALTER TABLE `pr_statuses` 
     ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER `status`,
     ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`',
    'SELECT 1'
);
PREPARE stmt FROM @add_cols_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

 -- Insert PR Statuses
-- Check if created_at column exists to determine which INSERT to use
SET @has_created_at = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'pr_statuses' 
    AND COLUMN_NAME = 'created_at'
);

SET @insert_sql = IF(
    @has_created_at > 0,
    'INSERT INTO `pr_statuses` (`id`, `status`, `created_at`, `updated_at`) VALUES
    (1, ''Open'', NOW(), NOW()),
    (2, ''Forwarded to Buyer'', NOW(), NOW()),
    (3, ''Agent/Supplier contacted and Awaiting PO details'', NOW(), NOW()),
    (4, ''Received Proforma PO'', NOW(), NOW()),
    (5, ''Forwarded to Buyer Head'', NOW(), NOW()),
    (6, ''Forwarded to PO Team'', NOW(), NOW()),
    (7, ''PO generated'', NOW(), NOW()),
    (8, ''Rejected'', NOW(), NOW()),
    (9, ''Forwarded to PO Members'', NOW(), NOW())
    ON DUPLICATE KEY UPDATE `status` = VALUES(`status`), `updated_at` = NOW()',
    'INSERT INTO `pr_statuses` (`id`, `status`) VALUES
    (1, ''Open''),
    (2, ''Forwarded to Buyer''),
    (3, ''Agent/Supplier contacted and Awaiting PO details''),
    (4, ''Received Proforma PO''),
    (5, ''Forwarded to Buyer Head''),
    (6, ''Forwarded to PO Team''),
    (7, ''PO generated''),
    (8, ''Rejected''),
    (9, ''Forwarded to PO Members'')
    ON DUPLICATE KEY UPDATE `status` = VALUES(`status`)'
);
PREPARE stmt FROM @insert_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Insert Role Status Permissions
INSERT INTO `role_status_permissions` (`role`, `status_id`, `is_active`) VALUES
('admin', 1, 1),
('buyer', 3, 1),
('buyer', 4, 1),
('buyer', 5, 1),
('B_Head', 2, 1),
('B_Head', 6, 1),
('B_Head', 8, 1),
('PO_Head', 9, 1),
('PO_Head', 7, 1),
('PO_Team_Member', 7, 1)
ON DUPLICATE KEY UPDATE `is_active` = 1, `updated_at` = NOW();

-- Insert Status Transitions
INSERT INTO `status_transitions` (`from_status_id`, `to_status_id`, `role`, `requires_proforma`, `is_active`, `priority`) VALUES
(1, 2, 'B_Head', 0, 1, 1),
(1, 6, 'B_Head', 1, 1, 2),
(2, 3, 'buyer', 0, 1, 1),
(3, 4, 'buyer', 0, 1, 1),
(4, 5, 'buyer', 0, 1, 1),
(5, 6, 'B_Head', 0, 1, 1),
(6, 9, 'PO_Head', 0, 1, 1),
(9, 7, 'PO_Head', 0, 1, 1),
(9, 7, 'PO_Team_Member', 0, 1, 1),
(1, 8, 'B_Head', 0, 1, 0),
(2, 8, 'B_Head', 0, 1, 0),
(3, 8, 'B_Head', 0, 1, 0),
(4, 8, 'B_Head', 0, 1, 0),
(5, 8, 'B_Head', 0, 1, 0)
ON DUPLICATE KEY UPDATE `is_active` = 1, `updated_at` = NOW();

-- Insert Role PR Permissions
INSERT INTO `role_pr_permissions` (`role`, `can_create`, `can_edit`, `can_edit_status`, `is_active`) VALUES
('admin', 1, 1, 1, 1),
('buyer', 1, 1, 1, 1),
('B_Head', 1, 1, 1, 1)
ON DUPLICATE KEY UPDATE `can_create` = VALUES(`can_create`), `can_edit` = VALUES(`can_edit`), `can_edit_status` = VALUES(`can_edit_status`), `is_active` = 1, `updated_at` = NOW();

-- Insert Status Modal Fields
INSERT INTO `status_modal_fields` (`status_id`, `field_name`, `is_required`, `field_order`) VALUES
(2, 'buyer', 1, 1),
(2, 'remark', 0, 2),
(4, 'qty', 0, 1),
(4, 'file_upload', 0, 2),
(5, 'remark', 0, 1),
(6, 'po_head', 0, 1),
(6, 'buyer', 0, 2),
(6, 'remark', 0, 3),
(8, 'remark', 0, 1),
(9, 'po_team', 1, 1),
(9, 'remark', 0, 2)
ON DUPLICATE KEY UPDATE `is_required` = VALUES(`is_required`), `field_order` = VALUES(`field_order`), `updated_at` = NOW();

-- ============================================
-- PART 6: FILE UPLOAD PERMISSIONS
-- ============================================
-- Create file_upload_permissions table
-- This table defines which roles can upload/delete files for specific file types and statuses
CREATE TABLE IF NOT EXISTS `file_upload_permissions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `role` VARCHAR(50) NOT NULL COMMENT 'User role (admin, buyer, B_Head, PO_Head, PO_Team_Member)',
  `file_type` VARCHAR(20) NOT NULL COMMENT 'File type: proforma, po, product',
  `status_id` INT(11) DEFAULT NULL COMMENT 'Status ID that allows upload (NULL = any status)',
  `can_upload` TINYINT(1) DEFAULT 0 COMMENT 'Whether this role can upload files',
  `can_delete` TINYINT(1) DEFAULT 0 COMMENT 'Whether this role can delete files',
  `is_active` TINYINT(1) DEFAULT 1 COMMENT 'Whether this permission is active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_role_file_status` (`role`, `file_type`, `status_id`),
  KEY `idx_role` (`role`),
  KEY `idx_file_type` (`file_type`),
  KEY `idx_status` (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert file upload permissions
-- Proforma file permissions
-- B_Head can upload/delete proforma files when status is 1 or 5
-- buyer can upload/delete proforma files when status is 1, 2, or 3
INSERT INTO `file_upload_permissions` (`role`, `file_type`, `status_id`, `can_upload`, `can_delete`, `is_active`) VALUES
('B_Head', 'proforma', 1, 1, 1, 1),
('B_Head', 'proforma', 5, 1, 1, 1),
('buyer', 'proforma', 1, 1, 1, 1),
('buyer', 'proforma', 2, 1, 1, 1),
('buyer', 'proforma', 3, 1, 1, 1)
ON DUPLICATE KEY UPDATE `can_upload` = 1, `can_delete` = 1, `is_active` = 1, `updated_at` = NOW();

-- PO file permissions
-- PO_Head and PO_Team_Member can upload/delete PO files when status is 7
INSERT INTO `file_upload_permissions` (`role`, `file_type`, `status_id`, `can_upload`, `can_delete`, `is_active`) VALUES
('PO_Head', 'po', 7, 1, 1, 1),
('PO_Team_Member', 'po', 7, 1, 1, 1)
ON DUPLICATE KEY UPDATE `can_upload` = 1, `can_delete` = 1, `is_active` = 1, `updated_at` = NOW();

-- Product file permissions
-- B_Head, buyer, and admin can upload/delete product files when status is 1, 2, 3, 4, or 5
INSERT INTO `file_upload_permissions` (`role`, `file_type`, `status_id`, `can_upload`, `can_delete`, `is_active`) VALUES
('B_Head', 'product', 1, 1, 1, 1),
('B_Head', 'product', 2, 1, 1, 1),
('B_Head', 'product', 3, 1, 1, 1),
('B_Head', 'product', 4, 1, 1, 1),
('B_Head', 'product', 5, 1, 1, 1),
('buyer', 'product', 1, 1, 1, 1),
('buyer', 'product', 2, 1, 1, 1),
('buyer', 'product', 3, 1, 1, 1),
('buyer', 'product', 4, 1, 1, 1),
('buyer', 'product', 5, 1, 1, 1),
('admin', 'product', 1, 1, 1, 1),
('admin', 'product', 2, 1, 1, 1),
('admin', 'product', 3, 1, 1, 1),
('admin', 'product', 4, 1, 1, 1),
('admin', 'product', 5, 1, 1, 1)
ON DUPLICATE KEY UPDATE `can_upload` = 1, `can_delete` = 1, `is_active` = 1, `updated_at` = NOW();

-- ============================================
-- Create roles table
-- ============================================
-- This table stores all available user roles in the system
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

-- Insert default roles
INSERT INTO `roles` (`role_code`, `role_name`, `description`, `is_active`, `display_order`) VALUES
('admin', 'Admin', 'Administrator role with full access', 1, 1),
('buyer', 'Buyer', 'Buyer role for creating and managing purchase requests', 1, 2),
('B_Head', 'Buyer Head', 'Buyer Head role for approving and managing buyer requests', 1, 3),
('PO_Head', 'PO Head', 'Purchase Order Head role for managing PO operations', 1, 4),
('PO_Team_Member', 'PO Team Member', 'Purchase Order Team Member role', 1, 5),
('super_admin', 'Super Admin', 'Super Administrator with system-wide access', 1, 7),
('master', 'Master', 'Master role with complete system control', 1, 8)
ON DUPLICATE KEY UPDATE `role_name` = VALUES(`role_name`), `description` = VALUES(`description`), `is_active` = VALUES(`is_active`), `display_order` = VALUES(`display_order`), `updated_at` = NOW();

-- ============================================
-- Create role_menu_settings table
-- ============================================
-- This table stores role-based menu items for the sidebar navigation
CREATE TABLE IF NOT EXISTS `role_menu_settings` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `role` VARCHAR(50) NOT NULL COMMENT 'User role (admin, buyer, B_Head, PO_Head, PO_Team_Member, super_admin, master)',
  `menu_item_label` VARCHAR(100) NOT NULL COMMENT 'Menu item label (e.g., Dashboard, Home, Product Stock)',
  `menu_item_url` VARCHAR(255) NOT NULL COMMENT 'Menu item URL (e.g., dashboard.php, admin.php)',
  `menu_item_icon` TEXT DEFAULT NULL COMMENT 'SVG icon code for the menu item',
  `menu_order` INT(11) DEFAULT 0 COMMENT 'Display order of menu item',
  `is_visible` TINYINT(1) DEFAULT 1 COMMENT 'Whether this menu item is visible for this role',
  `menu_group` VARCHAR(50) DEFAULT NULL COMMENT 'Menu group (e.g., main, master_management)',
  `is_active` TINYINT(1) DEFAULT 1 COMMENT 'Whether this setting is active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_role_menu` (`role`, `menu_item_url`),
  KEY `idx_role` (`role`),
  KEY `idx_menu_order` (`menu_order`),
  KEY `idx_is_visible` (`is_visible`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Create role_initial_settings table
-- ============================================
-- This table stores initial page URL and status filter for each role
CREATE TABLE IF NOT EXISTS `role_initial_settings` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `role` VARCHAR(50) NOT NULL COMMENT 'User role',
  `initial_page_url` VARCHAR(255) NOT NULL COMMENT 'Initial page URL to show when user logs in',
  `initial_status_filter` INT(11) DEFAULT NULL COMMENT 'Initial status filter ID to apply (NULL = no filter)',
  `is_active` TINYINT(1) DEFAULT 1 COMMENT 'Whether this setting is active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_role_initial` (`role`),
  KEY `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Insert default menu items for each role
-- ============================================
-- Admin role
INSERT INTO `role_menu_settings` (`role`, `menu_item_label`, `menu_item_url`, `menu_item_icon`, `menu_order`, `is_visible`, `menu_group`) VALUES
('admin', 'Dashboard', 'dashboard.php', '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>', 1, 1, 'main'),
('admin', 'Home', 'admin.php', '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>', 2, 1, 'main'),
('admin', 'Product Stock', 'product-stock.php', '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>', 3, 1, 'main'),
('admin', 'Analytics', 'analytics.php', '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>', 4, 1, 'main'),
('admin', 'Profile', 'profile.php', '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>', 99, 1, 'main')
ON DUPLICATE KEY UPDATE `menu_item_label` = VALUES(`menu_item_label`), `menu_item_icon` = VALUES(`menu_item_icon`), `menu_order` = VALUES(`menu_order`), `is_visible` = VALUES(`is_visible`), `menu_group` = VALUES(`menu_group`), `updated_at` = NOW();

-- Buyer role
INSERT INTO `role_menu_settings` (`role`, `menu_item_label`, `menu_item_url`, `menu_item_icon`, `menu_order`, `is_visible`, `menu_group`) VALUES
('buyer', 'Dashboard', 'dashboard.php', '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>', 1, 1, 'main'),
('buyer', 'Home', 'buyer.php', '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>', 2, 1, 'main'),
('buyer', 'Analytics', 'analytics.php', '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>', 3, 1, 'main'),
('buyer', 'Profile', 'profile.php', '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>', 99, 1, 'main')
ON DUPLICATE KEY UPDATE `menu_item_label` = VALUES(`menu_item_label`), `menu_item_icon` = VALUES(`menu_item_icon`), `menu_order` = VALUES(`menu_order`), `is_visible` = VALUES(`is_visible`), `menu_group` = VALUES(`menu_group`), `updated_at` = NOW();

-- B_Head role
INSERT INTO `role_menu_settings` (`role`, `menu_item_label`, `menu_item_url`, `menu_item_icon`, `menu_order`, `is_visible`, `menu_group`) VALUES
('B_Head', 'Dashboard', 'dashboard.php', '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>', 1, 1, 'main'),
('B_Head', 'Home', 'buyer-head.php', '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>', 2, 1, 'main'),
('B_Head', 'Analytics', 'analytics.php', '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>', 3, 1, 'main'),
('B_Head', 'Profile', 'profile.php', '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>', 99, 1, 'main')
ON DUPLICATE KEY UPDATE `menu_item_label` = VALUES(`menu_item_label`), `menu_item_icon` = VALUES(`menu_item_icon`), `menu_order` = VALUES(`menu_order`), `is_visible` = VALUES(`is_visible`), `menu_group` = VALUES(`menu_group`), `updated_at` = NOW();

-- PO_Head role
INSERT INTO `role_menu_settings` (`role`, `menu_item_label`, `menu_item_url`, `menu_item_icon`, `menu_order`, `is_visible`, `menu_group`) VALUES
('PO_Head', 'Dashboard', 'dashboard.php', '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>', 1, 1, 'main'),
('PO_Head', 'Home', 'po-head.php', '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>', 2, 1, 'main'),
('PO_Head', 'Product Stock', 'product-stock.php', '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>', 3, 1, 'main'),
('PO_Head', 'Analytics', 'analytics.php', '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>', 4, 1, 'main'),
('PO_Head', 'Profile', 'profile.php', '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>', 99, 1, 'main')
ON DUPLICATE KEY UPDATE `menu_item_label` = VALUES(`menu_item_label`), `menu_item_icon` = VALUES(`menu_item_icon`), `menu_order` = VALUES(`menu_order`), `is_visible` = VALUES(`is_visible`), `menu_group` = VALUES(`menu_group`), `updated_at` = NOW();

-- PO_Team_Member role
INSERT INTO `role_menu_settings` (`role`, `menu_item_label`, `menu_item_url`, `menu_item_icon`, `menu_order`, `is_visible`, `menu_group`) VALUES
('PO_Team_Member', 'Dashboard', 'dashboard.php', '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>', 1, 1, 'main'),
('PO_Team_Member', 'Home', 'po-member.php', '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>', 2, 1, 'main'),
('PO_Team_Member', 'Analytics', 'analytics.php', '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>', 3, 1, 'main'),
('PO_Team_Member', 'Profile', 'profile.php', '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>', 99, 1, 'main')
ON DUPLICATE KEY UPDATE `menu_item_label` = VALUES(`menu_item_label`), `menu_item_icon` = VALUES(`menu_item_icon`), `menu_order` = VALUES(`menu_order`), `is_visible` = VALUES(`is_visible`), `menu_group` = VALUES(`menu_group`), `updated_at` = NOW();

-- Super Admin / Master role menu items
INSERT INTO `role_menu_settings` (`role`, `menu_item_label`, `menu_item_url`, `menu_item_icon`, `menu_order`, `is_visible`, `menu_group`) VALUES
('super_admin', 'Dashboard', 'dashboard.php', '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>', 1, 1, 'main'),
('super_admin', 'Home', 'admin.php', '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>', 2, 1, 'main'),
('super_admin', 'Product Stock', 'product-stock.php', '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>', 3, 1, 'main'),
('super_admin', 'Analytics', 'analytics.php', '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>', 4, 1, 'main'),
('super_admin', 'Status Flow Management', 'superadmin.php', '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>', 5, 1, 'main'),
('super_admin', 'User Management', 'user-management.php', '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>', 10, 1, 'master_management'),
('super_admin', 'Category Master', 'category-master.php', '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>', 11, 1, 'master_management'),
('super_admin', 'Category Assignment', 'category-assignment.php', '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg>', 12, 1, 'master_management'),
('super_admin', 'Buyer Mapping', 'buyer-mapping.php', '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>', 13, 1, 'master_management'),
('super_admin', 'Supplier Master', 'supplier-master.php', '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>', 14, 1, 'master_management'),
('super_admin', 'Purchase Type Master', 'purchase-type-master.php', '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>', 15, 1, 'master_management'),
('super_admin', 'Role Menu Settings', 'role-menu-settings.php', '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>', 16, 1, 'master_management'),
('super_admin', 'Profile', 'profile.php', '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>', 99, 1, 'main')
ON DUPLICATE KEY UPDATE `menu_item_label` = VALUES(`menu_item_label`), `menu_item_icon` = VALUES(`menu_item_icon`), `menu_order` = VALUES(`menu_order`), `is_visible` = VALUES(`is_visible`), `menu_group` = VALUES(`menu_group`), `updated_at` = NOW();

-- ============================================
-- Insert default initial page and status settings
-- ============================================
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
-- PART 7: MIGRATE USERS TABLE ROLE FROM ENUM TO FOREIGN KEY
-- ============================================
-- Convert users.role from ENUM to role_id (foreign key to roles table)

-- Step 1: Add role_id column to users table
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'users' 
    AND COLUMN_NAME = 'role_id'
);

-- Check if role column exists to determine where to add role_id
SET @role_col_exists_for_position = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'users' 
    AND COLUMN_NAME = 'role'
);

SET @add_col_sql = IF(
    @col_exists = 0 AND @role_col_exists_for_position > 0,
    'ALTER TABLE `users` ADD COLUMN `role_id` INT(11) NULL AFTER `role`',
    IF(
        @col_exists = 0,
        'ALTER TABLE `users` ADD COLUMN `role_id` INT(11) NULL',
        'SELECT 1'
    )
);
PREPARE stmt FROM @add_col_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Step 2: Migrate existing role enum values to role_id
-- Check if role column exists before trying to migrate
SET @role_col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'users' 
    AND COLUMN_NAME = 'role'
);

-- Only migrate if role column exists and role_id is NULL
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
-- This should run regardless of whether role column exists (handles NULL role_id from any source)
SET @set_default_role_sql = 'UPDATE `users` u
     SET u.role_id = (SELECT id FROM roles WHERE BINARY role_code = BINARY ''admin'' LIMIT 1)
     WHERE u.role_id IS NULL';
PREPARE stmt FROM @set_default_role_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Step 3: Make role_id NOT NULL after migration
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

-- Step 4: Add foreign key constraint
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

-- Step 5: Add index on role_id for performance
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

-- Step 6: Drop old role enum column (only if it exists and is enum type)
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
-- PART 8: INSERT ADDITIONAL MASTER DATA
-- ============================================
-- Ensure role_menu_settings has menu items for super_admin (Role Management and Role Initial Settings)
INSERT INTO `role_menu_settings` (`role`, `menu_item_label`, `menu_item_url`, `menu_item_icon`, `menu_order`, `is_visible`, `menu_group`) VALUES
('super_admin', 'Role Management', 'role-management.php', '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>', 17, 1, 'master_management'),
('super_admin', 'Role Initial Settings', 'role-initial-settings.php', '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>', 18, 1, 'master_management')
ON DUPLICATE KEY UPDATE `menu_item_label` = VALUES(`menu_item_label`), `menu_item_icon` = VALUES(`menu_item_icon`), `menu_order` = VALUES(`menu_order`), `is_visible` = VALUES(`is_visible`), `menu_group` = VALUES(`menu_group`), `updated_at` = NOW();

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;

-- ============================================
-- MIGRATION COMPLETE!
-- ============================================
-- The database has been migrated successfully.
-- 
-- What was done:
-- 1. Renamed tables to follow better naming conventions
-- 2. Created new permission and workflow tables
-- 3. Added foreign key relationships
-- 4. Added performance indexes
-- 5. Inserted master data (statuses, permissions, workflows)
-- 6. Migrated users.role from ENUM to role_id foreign key
-- 7. Inserted additional master data (menu items, initial settings)
--
-- Next steps:
-- 1. Update application code to use role_id instead of role
-- 2. Update queries to JOIN with roles table when role_code is needed
-- 3. Test all user-related functionality
-- 4. Map categories to buyer heads using buyer_head_categories table
-- 5. Map buyers to buyer heads using buyers_info table
-- 6. Start using the system!
-- ============================================

