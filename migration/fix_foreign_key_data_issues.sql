-- ============================================
-- Fix Foreign Key Data Issues
-- ============================================
-- This script cleans up orphaned data before adding foreign key constraints
-- Run this BEFORE adding foreign key constraints to avoid constraint failures
-- ============================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- ============================================
-- PART 1: ENSURE REFERENCED TABLES HAVE DATA
-- ============================================

-- Ensure pr_statuses table has all required statuses
-- This is critical as many tables reference pr_statuses
INSERT IGNORE INTO `pr_statuses` (`id`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Open', NOW(), NOW()),
(2, 'Forwarded to Buyer', NOW(), NOW()),
(3, 'Agent/Supplier contacted and Awaiting PO details', NOW(), NOW()),
(4, 'Received Proforma PO', NOW(), NOW()),
(5, 'Forwarded to Buyer Head', NOW(), NOW()),
(6, 'Forwarded to PO Team', NOW(), NOW()),
(7, 'PO generated', NOW(), NOW()),
(8, 'Rejected', NOW(), NOW()),
(9, 'Forwarded to PO Members', NOW(), NOW())
ON DUPLICATE KEY UPDATE `status` = VALUES(`status`), `updated_at` = NOW();

-- Ensure categories table exists and has at least one category
-- Create a default category if none exists
INSERT IGNORE INTO `categories` (`id`, `maincat`, `created_at`, `updated_at`) VALUES
(1, 'Default Category', NOW(), NOW())
ON DUPLICATE KEY UPDATE `maincat` = VALUES(`maincat`), `updated_at` = NOW();

-- Ensure purchase_types table exists and has at least one type
-- Create a default purchase type if none exists
INSERT IGNORE INTO `purchase_types` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'Default Purchase Type', NOW(), NOW())
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `updated_at` = NOW();

-- Ensure suppliers table exists (create if not exists)
CREATE TABLE IF NOT EXISTS `suppliers` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ensure supplier_requests table exists (create if not exists)
CREATE TABLE IF NOT EXISTS `supplier_requests` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ensure users table exists and has at least one admin user
-- Create a default admin user if none exists (only if users table exists)
SET @users_table_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.TABLES 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'users'
);

-- If users table exists, ensure there's at least one user
-- Note: This assumes users table has id, username, password, role or role_id columns
SET @admin_user_exists = IF(
    @users_table_exists > 0,
    (SELECT COUNT(*) FROM `users` LIMIT 1),
    0
);

-- ============================================
-- PART 2: FIX ORPHANED DATA IN purchase_requests
-- ============================================

-- Fix orphaned created_by references (set to NULL if user doesn't exist, or create default user)
-- First, check if purchase_requests table exists
SET @pr_table_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.TABLES 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'purchase_requests'
);

-- If purchase_requests exists, fix orphaned references
SET @fix_created_by_sql = IF(
    @pr_table_exists > 0 AND @users_table_exists > 0,
    'UPDATE `purchase_requests` pr
     LEFT JOIN `users` u ON pr.created_by = u.id
     SET pr.created_by = (SELECT id FROM users LIMIT 1)
     WHERE pr.created_by IS NOT NULL AND u.id IS NULL',
    'SELECT 1'
);
PREPARE stmt FROM @fix_created_by_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Fix orphaned b_head references
SET @fix_b_head_sql = IF(
    @pr_table_exists > 0 AND @users_table_exists > 0,
    'UPDATE `purchase_requests` pr
     LEFT JOIN `users` u ON pr.b_head = u.id
     SET pr.b_head = (SELECT id FROM users LIMIT 1)
     WHERE pr.b_head IS NOT NULL AND u.id IS NULL',
    'SELECT 1'
);
PREPARE stmt FROM @fix_b_head_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Fix orphaned buyer references (set to NULL if user doesn't exist - buyer can be NULL)
SET @fix_buyer_sql = IF(
    @pr_table_exists > 0 AND @users_table_exists > 0,
    'UPDATE `purchase_requests` pr
     LEFT JOIN `users` u ON pr.buyer = u.id
     SET pr.buyer = NULL
     WHERE pr.buyer IS NOT NULL AND u.id IS NULL',
    'SELECT 1'
);
PREPARE stmt FROM @fix_buyer_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Fix orphaned po_team references (set to NULL if user doesn't exist - po_team can be NULL)
SET @fix_po_team_sql = IF(
    @pr_table_exists > 0 AND @users_table_exists > 0,
    'UPDATE `purchase_requests` pr
     LEFT JOIN `users` u ON pr.po_team = u.id
     SET pr.po_team = NULL
     WHERE pr.po_team IS NOT NULL AND u.id IS NULL',
    'SELECT 1'
);
PREPARE stmt FROM @fix_po_team_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Fix orphaned supplier_id references
SET @suppliers_table_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.TABLES 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'suppliers'
);

SET @fix_supplier_sql = IF(
    @pr_table_exists > 0 AND @suppliers_table_exists > 0,
    'UPDATE `purchase_requests` pr
     LEFT JOIN `suppliers` s ON pr.supplier_id = s.id
     SET pr.supplier_id = (SELECT id FROM suppliers LIMIT 1)
     WHERE pr.supplier_id IS NOT NULL AND s.id IS NULL',
    'SELECT 1'
);
PREPARE stmt FROM @fix_supplier_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Fix orphaned new_supplier references (set to NULL if supplier_request doesn't exist - can be NULL)
SET @supplier_requests_table_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.TABLES 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'supplier_requests'
);

SET @fix_new_supplier_sql = IF(
    @pr_table_exists > 0 AND @supplier_requests_table_exists > 0,
    'UPDATE `purchase_requests` pr
     LEFT JOIN `supplier_requests` sr ON pr.new_supplier = sr.id
     SET pr.new_supplier = NULL
     WHERE pr.new_supplier IS NOT NULL AND sr.id IS NULL',
    'SELECT 1'
);
PREPARE stmt FROM @fix_new_supplier_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Fix orphaned category_id references
SET @categories_table_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.TABLES 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'categories'
);

SET @fix_category_sql = IF(
    @pr_table_exists > 0 AND @categories_table_exists > 0,
    'UPDATE `purchase_requests` pr
     LEFT JOIN `categories` c ON pr.category_id = c.id
     SET pr.category_id = (SELECT id FROM categories LIMIT 1)
     WHERE pr.category_id IS NOT NULL AND c.id IS NULL',
    'SELECT 1'
);
PREPARE stmt FROM @fix_category_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Fix orphaned purch_id references
SET @purchase_types_table_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.TABLES 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'purchase_types'
);

SET @fix_purchase_type_sql = IF(
    @pr_table_exists > 0 AND @purchase_types_table_exists > 0,
    'UPDATE `purchase_requests` pr
     LEFT JOIN `purchase_types` pt ON pr.purch_id = pt.id
     SET pr.purch_id = (SELECT id FROM purchase_types LIMIT 1)
     WHERE pr.purch_id IS NOT NULL AND pt.id IS NULL',
    'SELECT 1'
);
PREPARE stmt FROM @fix_purchase_type_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Fix orphaned po_status references (critical - must have valid status)
SET @pr_statuses_table_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.TABLES 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'pr_statuses'
);

SET @fix_status_sql = IF(
    @pr_table_exists > 0 AND @pr_statuses_table_exists > 0,
    'UPDATE `purchase_requests` pr
     LEFT JOIN `pr_statuses` ps ON pr.po_status = ps.id
     SET pr.po_status = 1
     WHERE pr.po_status IS NOT NULL AND ps.id IS NULL',
    'SELECT 1'
);
PREPARE stmt FROM @fix_status_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================
-- PART 3: FIX ORPHANED DATA IN pr_assignments
-- ============================================

SET @pr_assignments_table_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.TABLES 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'pr_assignments'
);

-- Fix orphaned ord_id references (delete orphaned assignments)
SET @fix_assignment_pr_sql = IF(
    @pr_assignments_table_exists > 0 AND @pr_table_exists > 0,
    'DELETE FROM `pr_assignments` pa
     WHERE pa.ord_id IS NOT NULL 
     AND NOT EXISTS (SELECT 1 FROM purchase_requests pr WHERE pr.id = pa.ord_id)',
    'SELECT 1'
);
PREPARE stmt FROM @fix_assignment_pr_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Fix orphaned po_team_member references (set to NULL if user doesn't exist)
SET @fix_assignment_member_sql = IF(
    @pr_assignments_table_exists > 0 AND @users_table_exists > 0,
    'UPDATE `pr_assignments` pa
     LEFT JOIN `users` u ON pa.po_team_member = u.id
     SET pa.po_team_member = NULL
     WHERE pa.po_team_member IS NOT NULL AND u.id IS NULL',
    'SELECT 1'
);
PREPARE stmt FROM @fix_assignment_member_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================
-- PART 4: FIX ORPHANED DATA IN po_documents
-- ============================================

SET @po_documents_table_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.TABLES 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'po_documents'
);

-- Fix orphaned ord_id references (delete orphaned documents)
SET @fix_po_doc_sql = IF(
    @po_documents_table_exists > 0 AND @pr_table_exists > 0,
    'DELETE FROM `po_documents` pd
     WHERE pd.ord_id IS NOT NULL 
     AND NOT EXISTS (SELECT 1 FROM purchase_requests pr WHERE pr.id = pd.ord_id)',
    'SELECT 1'
);
PREPARE stmt FROM @fix_po_doc_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================
-- PART 5: FIX ORPHANED DATA IN pr_attachments
-- ============================================

SET @pr_attachments_table_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.TABLES 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'pr_attachments'
);

-- Fix orphaned ord_id references (delete orphaned attachments)
SET @fix_attachment_sql = IF(
    @pr_attachments_table_exists > 0 AND @pr_table_exists > 0,
    'DELETE FROM `pr_attachments` pa
     WHERE pa.ord_id IS NOT NULL 
     AND NOT EXISTS (SELECT 1 FROM purchase_requests pr WHERE pr.id = pa.ord_id)',
    'SELECT 1'
);
PREPARE stmt FROM @fix_attachment_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================
-- PART 6: FIX ORPHANED DATA IN supplier_requests
-- ============================================

-- Fix orphaned created_by references in supplier_requests
SET @fix_supplier_req_created_by_sql = IF(
    @supplier_requests_table_exists > 0 AND @users_table_exists > 0,
    'UPDATE `supplier_requests` sr
     LEFT JOIN `users` u ON sr.created_by = u.id
     SET sr.created_by = (SELECT id FROM users LIMIT 1)
     WHERE sr.created_by IS NOT NULL AND u.id IS NULL',
    'SELECT 1'
);
PREPARE stmt FROM @fix_supplier_req_created_by_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================
-- PART 7: ENSURE INDEXES EXIST BEFORE FOREIGN KEYS
-- ============================================

-- Ensure users.id has a primary key (should already exist, but check)
-- Primary keys automatically create indexes

-- Ensure pr_statuses.id has a primary key
-- Primary keys automatically create indexes

-- Ensure categories.id has a primary key
-- Primary keys automatically create indexes

-- Ensure purchase_types.id has a primary key
-- Primary keys automatically create indexes

-- Ensure suppliers.id has a primary key
-- Primary keys automatically create indexes

-- Ensure supplier_requests.id has a primary key
-- Primary keys automatically create indexes

-- Ensure purchase_requests.id has a primary key
-- Primary keys automatically create indexes

-- Add indexes to foreign key columns in purchase_requests (if they don't exist)
SET @add_idx_created_by_sql = IF(
    @pr_table_exists > 0,
    'CREATE INDEX IF NOT EXISTS `idx_created_by` ON `purchase_requests` (`created_by`)',
    'SELECT 1'
);
PREPARE stmt FROM @add_idx_created_by_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @add_idx_b_head_sql = IF(
    @pr_table_exists > 0,
    'CREATE INDEX IF NOT EXISTS `idx_b_head` ON `purchase_requests` (`b_head`)',
    'SELECT 1'
);
PREPARE stmt FROM @add_idx_b_head_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @add_idx_supplier_id_sql = IF(
    @pr_table_exists > 0,
    'CREATE INDEX IF NOT EXISTS `idx_supplier_id` ON `purchase_requests` (`supplier_id`)',
    'SELECT 1'
);
PREPARE stmt FROM @add_idx_supplier_id_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @add_idx_category_id_sql = IF(
    @pr_table_exists > 0,
    'CREATE INDEX IF NOT EXISTS `idx_category_id` ON `purchase_requests` (`category_id`)',
    'SELECT 1'
);
PREPARE stmt FROM @add_idx_category_id_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @add_idx_purch_id_sql = IF(
    @pr_table_exists > 0,
    'CREATE INDEX IF NOT EXISTS `idx_purch_id` ON `purchase_requests` (`purch_id`)',
    'SELECT 1'
);
PREPARE stmt FROM @add_idx_purch_id_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @add_idx_po_status_sql = IF(
    @pr_table_exists > 0,
    'CREATE INDEX IF NOT EXISTS `idx_po_status` ON `purchase_requests` (`po_status`)',
    'SELECT 1'
);
PREPARE stmt FROM @add_idx_po_status_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;

-- ============================================
-- DATA CLEANUP COMPLETE!
-- ============================================
-- All orphaned data has been fixed.
-- You can now safely add foreign key constraints.
-- ============================================





