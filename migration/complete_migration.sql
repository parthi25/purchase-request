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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create categories table if it doesn't exist
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `maincat` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_maincat` (`maincat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create purchase_types table if it doesn't exist
CREATE TABLE IF NOT EXISTS `purchase_types` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create role_status_permissions table
CREATE TABLE IF NOT EXISTS `role_status_permissions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `role` VARCHAR(50) NOT NULL COMMENT 'User role (admin, buyer, B_Head, PO_Team, PO_Team_Member)',
  `status_id` INT(11) NOT NULL COMMENT 'Status ID that this role can change to',
  `is_active` TINYINT(1) DEFAULT 1 COMMENT 'Whether this permission is active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_role_status` (`role`, `status_id`),
  KEY `idx_role` (`role`),
  KEY `idx_status` (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
('PO_Team', 9, 1),
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
(6, 9, 'PO_Team', 0, 1, 1),
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
--
-- Next steps:
-- 1. Create users with roles (admin, buyer, B_Head, PO_Team, PO_Team_Member)
-- 2. Map categories to buyer heads using catbasbh table
-- 3. Map buyers to buyer heads using buyers_info table
-- 4. Start using the system!
-- ============================================

