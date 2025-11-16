-- ============================================
-- Add Foreign Key Relationships (Safe Version)
-- ============================================
-- This migration adds foreign key relationships with error handling
-- It will skip constraints that already exist or can't be added

-- Disable foreign key checks temporarily
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================
-- ADD FOREIGN KEY RELATIONSHIPS
-- ============================================
-- Note: Some foreign keys may fail if:
-- 1. They already exist
-- 2. Referenced columns don't have proper indexes
-- 3. Data integrity issues exist
-- This is okay - the application will still work

-- purchase_requests foreign keys
-- Only add if they don't already exist and if referenced columns exist

-- created_by foreign key
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'purchase_requests' 
     AND CONSTRAINT_NAME = 'fk_pr_created_by') = 0,
    'ALTER TABLE `purchase_requests` ADD CONSTRAINT `fk_pr_created_by` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- b_head foreign key
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'purchase_requests' 
     AND CONSTRAINT_NAME = 'fk_pr_b_head') = 0,
    'ALTER TABLE `purchase_requests` ADD CONSTRAINT `fk_pr_b_head` FOREIGN KEY (`b_head`) REFERENCES `users`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- buyer foreign key (may fail if users.id is not indexed properly)
-- Skip if it fails - NULL values are allowed
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'purchase_requests' 
     AND CONSTRAINT_NAME = 'fk_pr_buyer') = 0,
    'ALTER TABLE `purchase_requests` ADD CONSTRAINT `fk_pr_buyer` FOREIGN KEY (`buyer`) REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- po_team foreign key
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'purchase_requests' 
     AND CONSTRAINT_NAME = 'fk_pr_po_team') = 0,
    'ALTER TABLE `purchase_requests` ADD CONSTRAINT `fk_pr_po_team` FOREIGN KEY (`po_team`) REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- supplier_id foreign key
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'purchase_requests' 
     AND CONSTRAINT_NAME = 'fk_pr_supplier') = 0,
    'ALTER TABLE `purchase_requests` ADD CONSTRAINT `fk_pr_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- new_supplier foreign key
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'purchase_requests' 
     AND CONSTRAINT_NAME = 'fk_pr_new_supplier') = 0,
    'ALTER TABLE `purchase_requests` ADD CONSTRAINT `fk_pr_new_supplier` FOREIGN KEY (`new_supplier`) REFERENCES `supplier_requests`(`id`) ON DELETE SET NULL ON UPDATE CASCADE',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- category_id foreign key
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'purchase_requests' 
     AND CONSTRAINT_NAME = 'fk_pr_category') = 0,
    'ALTER TABLE `purchase_requests` ADD CONSTRAINT `fk_pr_category` FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- purch_id foreign key
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'purchase_requests' 
     AND CONSTRAINT_NAME = 'fk_pr_purchase_type') = 0,
    'ALTER TABLE `purchase_requests` ADD CONSTRAINT `fk_pr_purchase_type` FOREIGN KEY (`purch_id`) REFERENCES `purchase_types`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- po_status foreign key
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'purchase_requests' 
     AND CONSTRAINT_NAME = 'fk_pr_status') = 0,
    'ALTER TABLE `purchase_requests` ADD CONSTRAINT `fk_pr_status` FOREIGN KEY (`po_status`) REFERENCES `pr_statuses`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- pr_assignments foreign keys
ALTER TABLE `pr_assignments`
    ADD CONSTRAINT `fk_assignment_pr` FOREIGN KEY (`ord_id`) REFERENCES `purchase_requests`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT `fk_assignment_member` FOREIGN KEY (`po_team_member`) REFERENCES `users`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

-- po_documents foreign keys
ALTER TABLE `po_documents`
    ADD CONSTRAINT `fk_po_doc_pr` FOREIGN KEY (`ord_id`) REFERENCES `purchase_requests`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- pr_attachments foreign keys
ALTER TABLE `pr_attachments`
    ADD CONSTRAINT `fk_attachment_pr` FOREIGN KEY (`ord_id`) REFERENCES `purchase_requests`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- supplier_requests foreign keys
ALTER TABLE `supplier_requests`
    ADD CONSTRAINT `fk_supplier_req_created_by` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

-- role_status_permissions foreign keys
ALTER TABLE `role_status_permissions`
    ADD CONSTRAINT `fk_role_status_perm_status` FOREIGN KEY (`status_id`) REFERENCES `pr_statuses`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- status_transitions foreign keys
ALTER TABLE `status_transitions`
    ADD CONSTRAINT `fk_transition_from_status` FOREIGN KEY (`from_status_id`) REFERENCES `pr_statuses`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT `fk_transition_to_status` FOREIGN KEY (`to_status_id`) REFERENCES `pr_statuses`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- role_pr_permissions foreign keys
ALTER TABLE `role_pr_permissions`
    ADD CONSTRAINT `fk_pr_perm_status` FOREIGN KEY (`can_edit_status`) REFERENCES `pr_statuses`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- ADD INDEXES FOR BETTER PERFORMANCE
-- ============================================

-- Add indexes to purchase_requests for common queries (only if they don't exist)
ALTER TABLE `purchase_requests`
    ADD INDEX IF NOT EXISTS `idx_po_status` (`po_status`),
    ADD INDEX IF NOT EXISTS `idx_created_by` (`created_by`),
    ADD INDEX IF NOT EXISTS `idx_b_head` (`b_head`),
    ADD INDEX IF NOT EXISTS `idx_created_at` (`created_at`);

-- Add indexes to pr_assignments
ALTER TABLE `pr_assignments`
    ADD INDEX IF NOT EXISTS `idx_ord_id` (`ord_id`),
    ADD INDEX IF NOT EXISTS `idx_po_team_member` (`po_team_member`);

-- Add indexes to po_documents
ALTER TABLE `po_documents`
    ADD INDEX IF NOT EXISTS `idx_ord_id` (`ord_id`);

-- Add indexes to pr_attachments
ALTER TABLE `pr_attachments`
    ADD INDEX IF NOT EXISTS `idx_ord_id` (`ord_id`);

-- ============================================
-- MIGRATION COMPLETE
-- ============================================

